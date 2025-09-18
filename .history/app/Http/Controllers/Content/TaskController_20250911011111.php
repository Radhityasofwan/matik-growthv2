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

        // Filter
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Ambil dan kelompokkan per status
        $tasks = $query->latest()->get()->groupBy('status');

        // Pastikan key selalu ada agar Blade tidak error
        foreach (['open', 'in_progress', 'done'] as $status) {
            if (!isset($tasks[$status])) {
                $tasks[$status] = collect();
            }
        }

        $users = User::orderBy('name')->get();

        return view('content.tasks.index', compact('tasks', 'users'));
    }

    /**
     * Menyimpan tugas baru.
     */
    public function store(TaskRequest $request)
    {
        // Tambahkan creator dan status default
        $data = $request->validated() + [
            'creator_id' => Auth::id(),
            'status'     => $request->input('status', 'open'),
        ];

        $task = Task::create($data);

        // Kirim notifikasi jika ada assignee
        if ($task->assignee) {
            event(new TaskAssigned($task, $task->assignee));
        }

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tugas berhasil dibuat.');
    }

    /**
     * Endpoint data untuk modal edit (JSON).
     * Dipanggil oleh fetch(`/tasks/{id}/edit`) dari front-end.
     */
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
                // Format aman untuk <input type="date">
                'due_date'    => optional($task->due_date)->toDateString(), // "YYYY-MM-DD" atau null
            ],
        ]);
    }

    /**
     * Memperbarui tugas.
     */
    public function update(TaskRequest $request, Task $task)
    {
        $originalAssigneeId = $task->assignee_id;

        $task->update($request->validated());

        // Kirim notifikasi jika assignee berubah/baru
        if ($task->assignee_id && $originalAssigneeId != $task->assignee_id) {
            event(new TaskAssigned($task, $task->assignee));
        }

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    /**
     * Memperbarui status tugas (drag-and-drop kolom).
     * Kolom di board: open, in_progress, done.
     */
    public function updateStatus(Request $request, Task $task)
    {
        $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'done'])],
        ]);

        $task->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status tugas diperbarui.',
        ]);
    }

    /**
     * Menghapus tugas.
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tugas berhasil dihapus.');
    }
}
