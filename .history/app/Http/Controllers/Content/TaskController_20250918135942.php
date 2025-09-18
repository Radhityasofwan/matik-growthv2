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

        // Mengurutkan berdasarkan pin, lalu tanggal pembuatan
        $tasksCollection = $query->orderBy('is_pinned', 'desc')->latest('created_at')->get()->groupBy('status');

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
        ];
        $task = Task::create($data);

        if ($task->assignee) {
            event(new TaskAssigned($task, $task->assignee));
            $task->assignee->notify(new GenericDbNotification('Tugas Baru', "Anda mendapat tugas: {$task->title}.", route('tasks.index')));
            SendTaskWhatsAppJob::dispatch($task->id, 'created');
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Task $task)
    {
        return response()->json(['task' => $task]);
    }

    public function update(TaskRequest $request, Task $task)
    {
        $oldAssignee = $task->assignee_id;
        $task->update($request->validated());

        if ($task->assignee_id && $oldAssignee != $task->assignee_id) {
            event(new TaskAssigned($task, $task->assignee));
            $task->assignee->notify(new GenericDbNotification('Tugas Untuk Anda', "Anda ditugaskan: {$task->title}.", route('tasks.index')));
            SendTaskWhatsAppJob::dispatch($task->id, 'created');
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate(['status'=>['required', Rule::in(['open','in_progress','review','done'])]]);
        $from = $task->status;
        $task->update(['status'=>$request->status]);

        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification('Status Tugas Berubah', "Status \"{$task->title}\" menjadi {$task->status}.", route('tasks.index')));
        }
        if ($request->boolean('notify_wa', false) && $task->assignee && in_array($task->status, ['in_progress','review','done'], true)) {
            SendTaskWhatsAppJob::dispatch($task->id, 'status_changed', ['from'=>$from, 'to'=>$task->status]);
        }

        return response()->json(['success'=>true,'message'=>'Status tugas diperbarui.']);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        // Return JSON response for AJAX request
        return response()->json(['success' => true, 'message' => 'Tugas berhasil dihapus.']);
    }

    // --- New Actions for Dropdown ---

    public function togglePin(Task $task)
    {
        $task->update(['is_pinned' => !$task->is_pinned]);
        return response()->json(['success' => true, 'is_pinned' => $task->is_pinned]);
    }

    public function duplicate(Task $task)
    {
        $newTask = $task->replicate();
        $newTask->title = $task->title . ' (Copy)';
        $newTask->created_at = now();
        $newTask->updated_at = now();
        $newTask->is_pinned = false; // Duplikat tidak ikut di-pin
        $newTask->save();

        return response()->json(['success' => true, 'message' => 'Tugas berhasil diduplikasi.']);
    }
}

