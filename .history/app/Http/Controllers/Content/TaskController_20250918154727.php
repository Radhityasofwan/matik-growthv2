<?php

namespace App\Http\Controllers\Content;

use App\Events\TaskAssigned;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Jobs\SendTaskWhatsAppJob;
use App\Models\Task;
use App\Models\User;
use App\Notifications\GenericDbNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query()->with(['assignee','creator']);

        if ($request->filled('assignee_id')) $query->where('assignee_id', $request->assignee_id);
        if ($request->filled('priority'))    $query->where('priority', $request->priority);

        $tasksCollection = $query->latest()->get()->groupBy('status');

        // Pastikan semua status ada
        $tasks = [
            'open'        => $tasksCollection->get('open', collect()),
            'in_progress' => $tasksCollection->get('in_progress', collect()),
            'review'      => $tasksCollection->get('review', collect()),
            'done'        => $tasksCollection->get('done', collect()),
        ];

        $users = User::orderBy('name')->get();

        return view('content.tasks.index', compact('tasks','users'));
    }

    public function store(TaskRequest $request)
    {
        $data = $request->validated() + [
            'creator_id' => Auth::id(),
            'status'     => $request->input('status','open'),
            'link'       => $request->input('link'),
            'color'      => $request->input('color'),
        ];

        $task = Task::create($data);

        // In-app notifications
        if ($task->assignee) {
            event(new TaskAssigned($task, $task->assignee));
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

        // WA otomatis (created). Job diharapkan mengirim ke PIC/owner yang relevan.
        if ($task->assignee) {
            SendTaskWhatsAppJob::dispatch($task->id, 'created');
        }

        // Jika klien minta JSON (mis. AJAX), berikan payload ringkas
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'task' => [
                    'id'          => $task->id,
                    'title'       => $task->title,
                    'description' => $task->description,
                    'link'        => $task->link,
                    'color'       => $task->color,
                    'assignee_id' => $task->assignee_id,
                    'priority'    => $task->priority,
                    'status'      => $task->status,
                    'due_date'    => optional($task->due_date)->toDateString(),
                ],
            ]);
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
                'link'        => $task->link,
                'color'       => $task->color,
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

        $data = $request->validated() + [
            'link'  => $request->input('link'),
            'color' => $request->input('color'),
        ];

        $task->update($data);

        // Jika assignee berubah → in-app + WA created (re-assign semantics)
        if ($task->assignee_id && $oldAssignee != $task->assignee_id) {
            event(new TaskAssigned($task, $task->assignee));
            $task->assignee->notify(new GenericDbNotification(
                'Tugas Untuk Anda',
                "Anda ditugaskan: {$task->title}.",
                route('tasks.index')
            ));

            SendTaskWhatsAppJob::dispatch($task->id, 'created');
        }

        // In-app notif ke actor
        $request->user()?->notify(new GenericDbNotification(
            'Tugas Diperbarui',
            "Tugas \"{$task->title}\" telah diperbarui.",
            route('tasks.index')
        ));

        // Opsi WA untuk update (checkbox di modal edit)
        if ($request->boolean('notify_wa', false)) {
            SendTaskWhatsAppJob::dispatch($task->id, 'updated');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'task' => [
                    'id'          => $task->id,
                    'title'       => $task->title,
                    'description' => $task->description,
                    'link'        => $task->link,
                    'color'       => $task->color,
                    'assignee_id' => $task->assignee_id,
                    'priority'    => $task->priority,
                    'status'      => $task->status,
                    'due_date'    => optional($task->due_date)->toDateString(),
                ],
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate(['status'=>['required', Rule::in(['open','in_progress','review','done'])]]);

        $from = $task->status;
        $task->update(['status'=>$request->status]);

        // In-app ke assignee
        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification(
                'Status Tugas Berubah',
                "Status \"{$task->title}\" menjadi {$task->status}.",
                route('tasks.index')
            ));
        }

        // WA otomatis untuk pindah kolom (drag & drop) — tanpa konfirmasi
        if ($task->assignee) {
            SendTaskWhatsAppJob::dispatch($task->id, 'status_changed', ['from'=>$from, 'to'=>$task->status]);
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
}
