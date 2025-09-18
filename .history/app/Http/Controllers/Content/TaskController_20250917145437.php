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

        $tasks = $query->latest()->get()->groupBy('status');

        // FIXED: Memastikan semua kolom status, termasuk 'review', selalu ada
        foreach (['open','in_progress','review','done'] as $status) {
            $tasks[$status] = $tasks[$status] ?? collect();
        }

        $users = User::orderBy('name')->get();

        return view('content.tasks.index', compact('tasks','users'));
    }

    public function store(TaskRequest $request)
    {
        // FIXED: Menambahkan 'link' dan 'color' ke data yang divalidasi
        $data = $request->validated() + [
            'creator_id' => Auth::id(),
            'status'     => $request->input('status','open'),
            'link'       => $request->input('link'),
            'color'      => $request->input('color'),
        ];
        $task = Task::create($data);

        // Event + notifikasi in-app
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

        // Notifikasi WA (jika ada assignee)
        if ($task->assignee) {
            // Menggunakan dispatch agar tidak memblokir response
            SendTaskWhatsAppJob::dispatch($task->id, 'created');
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Task $task)
    {
        // FIXED: Menambahkan 'link' dan 'color' ke response JSON
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

        // FIXED: Menambahkan 'link' dan 'color' ke data yang akan diupdate
        $data = $request->validated() + [
            'link'  => $request->input('link'),
            'color' => $request->input('color'),
        ];
        $task->update($data);

        if ($task->assignee_id && $oldAssignee != $task->assignee_id) {
            event(new TaskAssigned($task, $task->assignee));
            $task->assignee->notify(new GenericDbNotification(
                'Tugas Untuk Anda',
                "Anda ditugaskan: {$task->title}.",
                route('tasks.index')
            ));
            SendTaskWhatsAppJob::dispatch($task->id, 'created');
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
        // FIXED: Menambahkan 'review' ke dalam aturan validasi
        $request->validate(['status'=>['required', Rule::in(['open','in_progress','review','done'])]]);
        $from = $task->status;
        $task->update(['status'=>$request->status]);

        // Notifikasi in-app
        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification(
                'Status Tugas Berubah',
                "Status \"{$task->title}\" menjadi {$task->status}.",
                route('tasks.index')
            ));
        }

        // Notifikasi WA opsional
        $shouldNotifyWA = (bool) $request->boolean('notify_wa', false);
        // FIXED: Menambahkan 'review' ke dalam kondisi pengiriman notifikasi WA
        if ($shouldNotifyWA && $task->assignee && in_array($task->status, ['in_progress','review','done'], true)) {
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
