<?php

namespace App\Http\Controllers\Content;

use App\Events\TaskAssigned as TaskAssignedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WahaController;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\WahaSender;
use App\Notifications\GenericDbNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query()->with(['assignee','creator']);

        if ($request->filled('assignee_id')) $query->where('assignee_id', $request->assignee_id);
        if ($request->filled('priority'))    $query->where('priority', $request->priority);

        $tasks = $query->latest()->get()->groupBy('status');

        // pastikan key ada
        foreach (['open','in_progress','done','overdue'] as $status) {
            $tasks[$status] = $tasks[$status] ?? collect();
        }

        $users = User::orderBy('name')->get();

        return view('content.tasks.index', compact('tasks','users'));
    }

    public function store(TaskRequest $request)
    {
        $data = $request->validated() + [
            'creator_id' => Auth::id(),
            'status'     => $request->input('status','open'),
        ];

        /** @var Task $task */
        $task = Task::create($data);

        // Event lama tetap di-broadcast bila dipakai listener lain
        if ($task->assignee) {
            event(new TaskAssignedEvent($task, $task->assignee));
        }

        // In-app notif (DB) tetap
        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification(
                'Tugas Baru',
                "Anda mendapat tugas: {$task->title}.",
                route('tasks.index')
            ));
        } else {
            $request->user()?->notify(new GenericDbNotification(
                'Tugas Dibuat',
                "Tugas \"{$task->title}\" berhasil dibuat.",
                route('tasks.index')
            ));
        }

        // === NEW: WA notif ke PIC (jika punya wa_number) ===
        try {
            $assignee = $task->assignee;
            $sender   = $this->resolveSender();
            $to       = $assignee?->wa_number ? $this->waSanitize($assignee->wa_number) : null;

            if ($sender && $to) {
                $msg    = $this->buildTaskMessage($task, 'created');
                $result = $this->sendViaWaha($sender->id, $to, $msg);

                activity('wa_task_created')->performedOn($task)->causedBy($assignee)->withProperties([
                    'sender_id'   => $sender->id,
                    'assignee_id' => $assignee->id ?? null,
                    'number'      => $to,
                    'http'        => data_get($result, 'http'),
                    'status'      => data_get($result, 'status') ?? 'UNKNOWN',
                    'text'        => Str::limit($msg, 500),
                ])->log('WA task created terkirim');
            } else {
                activity('wa_task_created')->performedOn($task)->withProperties([
                    'sender_id'   => $sender?->id,
                    'assignee_id' => $assignee?->id,
                    'status'      => $assignee?->wa_number ? 'SKIPPED_NO_SENDER' : 'SKIPPED_NO_ASSIGNEE_WA',
                ])->log('WA task created di-skip');
            }
        } catch (\Throwable $e) {
            Log::error('[Task] WA on create error: '.$e->getMessage(), ['task_id' => $task->id]);
            activity('wa_task_created')->performedOn($task)->withProperties([
                'error' => $e->getMessage(),
            ])->log('WA task created gagal');
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Task $task)
    {
        return response()->json([
            'task' => [
                'id'          => $task->id,
                'title'       => $task->title,
                'description' => $task->description,
                'assignee_id' => $task->assignee_id,
                'priority'    => $task->priority,
                'status'      => $task->status,
                'due_date'    => optional($task->due_date)->toDateString(),
            ],
        ]);
    }

    public function update(TaskRequest $request, Task $task)
    {
        $oldAssignee = $task->assignee_id;
        $task->update($request->validated());

        if ($task->assignee_id && $oldAssignee != $task->assignee_id) {
            event(new TaskAssignedEvent($task, $task->assignee));
            $task->assignee->notify(new GenericDbNotification(
                'Tugas Untuk Anda',
                "Anda ditugaskan: {$task->title}.",
                route('tasks.index')
            ));

            // === NEW: WA notify saat assignee berubah ===
            try {
                $assignee = $task->assignee;
                $sender   = $this->resolveSender();
                $to       = $assignee?->wa_number ? $this->waSanitize($assignee->wa_number) : null;

                if ($sender && $to) {
                    $msg    = $this->buildTaskMessage($task, 'assigned');
                    $result = $this->sendViaWaha($sender->id, $to, $msg);

                    activity('wa_task_assigned')->performedOn($task)->causedBy($assignee)->withProperties([
                        'sender_id'   => $sender->id,
                        'assignee_id' => $assignee->id ?? null,
                        'number'      => $to,
                        'http'        => data_get($result, 'http'),
                        'status'      => data_get($result, 'status') ?? 'UNKNOWN',
                        'text'        => Str::limit($msg, 500),
                    ])->log('WA task assigned terkirim');
                } else {
                    activity('wa_task_assigned')->performedOn($task)->withProperties([
                        'sender_id'   => $sender?->id,
                        'assignee_id' => $assignee?->id,
                        'status'      => $assignee?->wa_number ? 'SKIPPED_NO_SENDER' : 'SKIPPED_NO_ASSIGNEE_WA',
                    ])->log('WA task assigned di-skip');
                }
            } catch (\Throwable $e) {
                Log::error('[Task] WA on assign error: '.$e->getMessage(), ['task_id' => $task->id]);
            }
        }

        $request->user()?->notify(new GenericDbNotification(
            'Tugas Diperbarui',
            "Tugas \"{$task->title}\" telah diperbarui.",
            route('tasks.index')
        ));

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate([
            'status'  => ['required', Rule::in(['open','in_progress','done'])],
            'send_wa' => ['nullable','boolean'], // NEW: opsi kirim WA
        ]);

        $from = $task->status;
        $task->update(['status'=>$request->status]);

        // In-app notif seperti sebelumnya
        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification(
                'Status Tugas Berubah',
                "Status \"{$task->title}\" menjadi {$task->status}.",
                route('tasks.index')
            ));
        }

        // === NEW: WA opsional saat status berubah ke in_progress/done ===
        $shouldSendWa = (bool) $request->boolean('send_wa', false);
        if ($shouldSendWa && in_array($task->status, ['in_progress','done'], true)) {
            try {
                $assignee = $task->assignee;
                $sender   = $this->resolveSender();
                $to       = $assignee?->wa_number ? $this->waSanitize($assignee->wa_number) : null;

                if ($sender && $to) {
                    $msg    = $this->buildTaskMessage($task, 'status', $from, $task->status);
                    $result = $this->sendViaWaha($sender->id, $to, $msg);

                    activity('wa_task_status')->performedOn($task)->causedBy($assignee)->withProperties([
                        'sender_id'   => $sender->id,
                        'assignee_id' => $assignee->id ?? null,
                        'number'      => $to,
                        'http'        => data_get($result, 'http'),
                        'status'      => data_get($result, 'status') ?? 'UNKNOWN',
                        'text'        => Str::limit($msg, 500),
                        'from'        => $from,
                        'to'          => $task->status,
                    ])->log('WA task status terkirim');
                } else {
                    activity('wa_task_status')->performedOn($task)->withProperties([
                        'sender_id'   => $sender?->id,
                        'assignee_id' => $assignee?->id,
                        'status'      => $assignee?->wa_number ? 'SKIPPED_NO_SENDER' : 'SKIPPED_NO_ASSIGNEE_WA',
                        'from'        => $from,
                        'to'          => $task->status,
                    ])->log('WA task status di-skip');
                }
            } catch (\Throwable $e) {
                Log::error('[Task] WA on status error: '.$e->getMessage(), ['task_id' => $task->id]);
            }
        }

        return response()->json(['success'=>true,'message'=>'Status tugas diperbarui.']);
    }

    public function destroy(Task $task)
    {
        $title = $task->title;
        $assignee = $task->assignee;
        $task->delete();

        if ($assignee) {
            $assignee->notify(new GenericDbNotification(
                'Tugas Dihapus',
                "Tugas \"{$title}\" telah dihapus.",
                route('tasks.index')
            ));
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }

    /* ===================== Helpers: WA ===================== */

    protected function resolveSender(): ?WahaSender
    {
        // Logic to resolve and return the appropriate WahaSender instance
    }
