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
        $query = Task::query()->with(['owners','creator']);

        if ($request->filled('assignee_id')) {
            $query->whereHas('owners', fn($q) => $q->where('users.id', $request->assignee_id));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasksCollection = $query->latest()->get()->groupBy('status');

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

        // Sinkron owner ke pivot
        $owners = $request->input('owner_ids', []);
        $task->owners()->sync($owners);

        // In-app notif ke semua owner
        foreach ($task->owners as $owner) {
            event(new TaskAssigned($task, $owner));
            $owner->notify(new GenericDbNotification(
                'Tugas Baru',
                "Anda mendapat tugas: {$task->title}.",
                route('tasks.index')
            ));
        }

        // ✅ WA: dispatch SEKALI untuk semua PIC/Owner (job akan resolve recipients)
        SendTaskWhatsAppJob::dispatch($task->id, 'created');

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Task $task)
    {
        // Kembalikan owner_ids agar front-end bisa pre-select multipilih
        return response()->json([
            'task' => [
                'id'          => $task->id,
                'title'       => $task->title,
                'description' => $task->description,
                'owner_ids'   => $task->owners->pluck('id'),
                'priority'    => $task->priority,
                'status'      => $task->status,
                'start_at'    => optional($task->start_at)->format('Y-m-d\TH:i'),
                'due_date'    => optional($task->due_date)->toDateString(),
                'link'        => $task->link,
            ],
        ]);
    }

    public function update(TaskRequest $request, Task $task)
    {
        $data = $request->validated();
        $task->update($data);

        // Sinkron owner
        $owners = $request->input('owner_ids', []);
        $task->owners()->sync($owners);

        // In-app notif
        foreach ($task->owners as $owner) {
            $owner->notify(new GenericDbNotification(
                'Tugas Diperbarui',
                "Tugas \"{$task->title}\" telah diperbarui.",
                route('tasks.index')
            ));
        }

        // ✅ WA optional → kirim SEKALI jika diminta
        if ($request->boolean('notify_wa')) {
            SendTaskWhatsAppJob::dispatch($task->id, 'updated');
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate([
            'status' => ['required', Rule::in(['open','in_progress','review','done','overdue'])],
            'notify_wa' => ['sometimes','boolean'],
        ]);

        $from = $task->status;
        $to   = $request->string('status')->toString();

        // Update status
        $task->update(['status' => $to]);

        // In-app notif untuk semua owner
        foreach ($task->owners as $owner) {
            $owner->notify(new GenericDbNotification(
                'Status Tugas Berubah',
                "Status \"{$task->title}\" menjadi {$task->status}.",
                route('tasks.index')
            ));
        }

        // ✅ WA optional — dispatch SEKALI, job mengirim ke semua PIC/Owner
        if ($request->boolean('notify_wa')) {
            SendTaskWhatsAppJob::dispatch($task->id, 'status_changed', ['from'=>$from,'to'=>$to]);
        }

        return response()->json(['success'=>true,'message'=>'Status tugas diperbarui.']);
    }

    public function destroy(Task $task)
    {
        $title = $task->title;
        $owners = $task->owners()->get(); // ambil dulu sebelum delete
        $task->delete();

        foreach ($owners as $owner) {
            $owner->notify(new GenericDbNotification(
                'Tugas Dihapus',
                "Tugas \"{$title}\" telah dihapus.",
                route('tasks.index')
            ));
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }
}
