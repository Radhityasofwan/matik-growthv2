<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with(['assignee', 'creator'])
            ->latest()
            ->get()
            ->groupBy('status');

        $users = User::pluck('name', 'id');

        // Ensure all status keys exist for the board
        $statuses = ['open', 'in_progress', 'done', 'overdue'];
        $groupedTasks = [];
        foreach ($statuses as $status) {
            $groupedTasks[$status] = $tasks->get($status, collect());
        }

        return view('content.tasks.index', [
            'tasks' => $groupedTasks,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        Task::create($validated + ['creator_id' => Auth::id(), 'status' => 'open']);

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $task->update($validated);

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate(['status' => 'required|in:open,in_progress,done,overdue']);

        $task->update(['status' => $request->status]);

        // Log this activity if needed
        activity()
           ->performedOn($task)
           ->causedBy(Auth::user())
           ->log("Task status changed to {$request->status}");

        return response()->json(['message' => 'Task status updated successfully.']);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }
}
