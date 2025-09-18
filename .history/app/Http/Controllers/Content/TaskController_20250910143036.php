<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Http\Requests\TaskRequest; // <-- Import TaskRequest
use App\Events\TaskAssigned;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tasks = Task::with('assignee')->get()->groupBy('status');
        $users = User::all();

        // Ensure all statuses have an entry, even if empty
        $statuses = ['open', 'in_progress', 'done'];
        foreach ($statuses as $status) {
            if (!isset($tasks[$status])) {
                $tasks[$status] = collect();
            }
        }

        return view('content.tasks.index', compact('tasks', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaskRequest $request)
    {
        $task = Task::create($request->validated());

        event(new TaskAssigned($task));

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        $users = User::all();
        // This is for fetching data for the modal, returns JSON
        return response()->json([
            'task' => $task,
            'users' => $users,
            'update_url' => route('tasks.update', $task)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskRequest $request, Task $task)
    {
        $originalAssigneeId = $task->assignee_id;
        $task->update($request->validated());

        // Trigger event only if assignee is changed
        if ($originalAssigneeId != $task->assignee_id) {
            event(new TaskAssigned($task));
        }

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function updateStatus(Request $request, Task $task)
    {
        $request->validate(['status' => ['required', Rule::in(['open', 'in_progress', 'done'])]]);

        $task->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Task status updated.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }
}

