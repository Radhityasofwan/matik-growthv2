<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Http\Requests\TaskRequest;
use App\Events\TaskAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * Menampilkan daftar tugas dalam format Kanban Board.
     */
    public function index(Request $request)
    {
        $query = Task::query()->with(['assignee', 'creator']);

        // Filtering
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->get()->groupBy('status');
        $users = User::orderBy('name')->get();

        // Memastikan semua kolom status ada, bahkan jika kosong
        $statuses = ['open', 'in_progress', 'done'];
        foreach ($statuses as $status) {
            if (!isset($tasks[$status])) {
                $tasks[$status] = collect();
            }
        }

        return view('content.tasks.index', compact('tasks', 'users'));
    }

    /**
     * Menyimpan tugas baru.
     */
    public function store(TaskRequest $request)
    {
        // Menambahkan creator_id dan status default
        $data = $request->validated() + [
            'creator_id' => Auth::id(),
            'status' => 'open',
        ];

        $task = Task::create($data);

        // Kirim notifikasi jika ada assignee
        if ($task->assignee) {
            event(new TaskAssigned($task, $task->assignee));
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    /**
     * Mengambil data tugas untuk modal edit.
     */
    public function edit(Task $task)
    {
        return response()->json($task->load('assignee'));
    }

    /**
     * Memperbarui tugas.
     */
    public function update(TaskRequest $request, Task $task)
    {
        $originalAssigneeId = $task->assignee_id;

        $task->update($request->validated());

        // Kirim notifikasi jika assignee berubah atau baru ditambahkan
        if ($task->assignee_id && $originalAssigneeId != $task->assignee_id) {
            event(new TaskAssigned($task, $task->assignee));
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    /**
     * Memperbarui status tugas (untuk drag-and-drop).
     */
    public function updateStatus(Request $request, Task $task)
    {
        $request->validate(['status' => ['required', Rule::in(['open', 'in_progress', 'done'])]]);
        $task->update(['status' => $request->status]);
        return response()->json(['success' => true, 'message' => 'Status tugas diperbarui.']);
    }

    /**
     * Menghapus tugas.
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }
}
