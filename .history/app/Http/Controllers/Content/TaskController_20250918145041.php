<?php

namespace App\Http\Controllers\Content;

use App\Events\TaskAssigned;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Jobs\SendTaskWhatsAppJob;
use App\Models\Comment;
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

        // Notifikasi ke PIC (assignee) + WhatsApp
        if ($task->assignee) {
            event(new TaskAssigned($task, $task->assignee));
            $task->assignee->notify(new GenericDbNotification('Tugas Baru', "Anda mendapat tugas: {$task->title}.", route('tasks.index')));
            // WA ke PIC dan Owner (meta untuk downstream)
            SendTaskWhatsAppJob::dispatch($task->id, 'created', ['notify' => ['assignee' => true, 'owner' => true]]);
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

        // Perubahan PIC
        if ($task->assignee_id && $oldAssignee != $task->assignee_id) {
            event(new TaskAssigned($task, $task->assignee));
            $task->assignee->notify(new GenericDbNotification('Tugas Untuk Anda', "Anda ditugaskan: {$task->title}.", route('tasks.index')));
            SendTaskWhatsAppJob::dispatch($task->id, 'created', ['notify' => ['assignee' => true, 'owner' => true]]);
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate(['status'=>['required', Rule::in(['open','in_progress','review','done'])]]);
        $from = $task->status;
        $task->update(['status'=>$request->status]);

        // Notif DB ke PIC
        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification('Status Tugas Berubah', "Status \"{$task->title}\" menjadi {$task->status}.", route('tasks.index')));
        }

        // WA otomatis (dengan konfirmasi dari UI). Kirim ke PIC dan Owner agar sinkron.
        if ($request->boolean('notify_wa', false) && in_array($task->status, ['in_progress','review','done'], true)) {
            SendTaskWhatsAppJob::dispatch($task->id, 'status_changed', [
                'from'=>$from,
                'to'=>$task->status,
                'notify' => ['assignee' => true, 'owner' => true],
            ]);
        }

        return response()->json(['success'=>true,'message'=>'Status tugas diperbarui.']);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['success' => true, 'message' => 'Tugas berhasil dihapus.']);
    }

    // --- Dropdown Actions ---

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

    /** Edit Icon (emoji string) tanpa mengubah TaskRequest */
    public function updateIcon(Request $request, Task $task)
    {
        $request->validate(['icon' => ['nullable','string','max:16']]);
        $task->update(['icon' => $request->input('icon') ?: null]);

        // Notifikasi ringan ke PIC (opsional)
        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification('Icon Tugas Diubah', "Icon untuk \"{$task->title}\" diperbarui.", route('tasks.index')));
        }

        return response()->json(['success' => true]);
    }

    /** Tambah komentar pada task */
    public function addComment(Request $request, Task $task)
    {
        $data = $request->validate([
            'content' => ['required','string','max:2000'],
        ]);

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'content' => $data['content'],
        ]);

        // Notifikasi WA/DB ke PIC dan owner agar tidak terlewat
        if ($task->assignee) {
            $task->assignee->notify(new GenericDbNotification('Komentar Baru pada Tugas', "Komentar baru untuk: {$task->title}.", route('tasks.index')));
        }
        if ($task->creator && $task->creator_id !== $task->assignee_id) {
            $task->creator->notify(new GenericDbNotification('Komentar Baru pada Tugas (Owner)', "Komentar baru untuk: {$task->title}.", route('tasks.index')));
        }

        SendTaskWhatsAppJob::dispatch($task->id, 'comment_added', [
            'comment_id' => $comment->id,
            'notify' => ['assignee' => true, 'owner' => true],
        ]);

        return response()->json(['success' => true]);
    }
}
