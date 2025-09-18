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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query()->with(['assignee','creator'])->orderByDesc('is_pinned')->latest();

        if ($request->filled('assignee_id')) $query->where('assignee_id', $request->assignee_id);
        if ($request->filled('priority'))    $query->where('priority', $request->priority);

        $tasksCollection = $query->get()->groupBy('status');

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
            'status'     => 'open',
        ];
        $task = Task::create($data);

        if ($task->assignee) {
            event(new TaskAssigned($task, $task->assignee));
            $task->assignee->notify(new GenericDbNotification(
                'Tugas Baru',
                "Anda mendapat tugas: {$task->title}.",
                route('tasks.index')
            ));
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
            $task->assignee->notify(new GenericDbNotification(
                'Tugas Untuk Anda', "Anda ditugaskan: {$task->title}.", route('tasks.index')
            ));
            SendTaskWhatsAppJob::dispatch($task->id, 'created');
        }

        return response()->json(['task' => $task, 'message' => 'Tugas berhasil diperbarui.']);
    }


    public function updateStatus(Request $request, Task $task)
    {
        $request->validate(['status'=>['required', Rule::in(['open','in_progress','review','done'])]]);
        $from = $task->status;
        $task->update(['status'=>$request->status]);

        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification(
                'Status Tugas Berubah', "Status \"{$task->title}\" menjadi {$task->status}.", route('tasks.index')
            ));
        }
        
        if ((bool) $request->boolean('notify_wa', false) && $task->assignee && in_array($task->status, ['in_progress','review','done'], true)) {
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
            $assignee->notify(new GenericDbNotification('Tugas Dihapus', "Tugas \"{$title}\" telah dihapus.", route('tasks.index')));
        }
        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }

    // --- Aksi Dropdown Baru ---

    public function togglePin(Task $task)
    {
        $task->update(['is_pinned' => !$task->is_pinned]);
        return response()->json(['is_pinned' => $task->is_pinned]);
    }

    public function duplicate(Task $task)
    {
        $newTask = $task->replicate()->fill([
            'title' => $task->title . ' (Copy)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $newTask->save();
        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diduplikasi.');
    }

    public function copyLink(Task $task)
    {
        // Di dunia nyata, ini bisa menjadi URL yang lebih spesifik
        $url = route('tasks.index') . '#task-' . $task->id; 
        return response()->json(['url' => $url]);
    }
}
