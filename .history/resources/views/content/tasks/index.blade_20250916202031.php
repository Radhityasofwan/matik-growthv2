@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">

    {{-- Alerts --}}
    @if (session('success'))
    <div class="alert alert-success shadow-lg mb-6">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif
    @if ($errors->any())
    <div class="alert alert-error shadow-lg mb-6">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>
                <strong>Terdapat kesalahan!</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </span>
        </div>
    </div>
    @endif

    {{-- Header & CTA --}}
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Tasks</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola tugas tim Anda dengan Kanban Board.</p>
        </div>
        <a href="#create_task_modal" class="btn btn-primary mt-4 sm:mt-0">Buat Tugas Baru</a>
    </div>

    {{-- Filters --}}
    <form action="{{ route('tasks.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-base-200 p-4 rounded-lg">
        <select name="assignee_id" class="select select-bordered w-full">
            <option value="">Semua PIC</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(request('assignee_id') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="priority" class="select select-bordered w-full">
            <option value="">Semua Prioritas</option>
            <option value="low" @selected(request('priority') == 'low')>Low</option>
            <option value="medium" @selected(request('priority') == 'medium')>Medium</option>
            <option value="high" @selected(request('priority') == 'high')>High</option>
            <option value="urgent" @selected(request('priority') == 'urgent')>Urgent</option>
        </select>
        <button type="submit" class="btn btn-secondary w-full">Filter</button>
    </form>

    {{-- Kanban Board --}}
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach (['open' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done'] as $status => $title)
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="font-semibold text-lg mb-4 text-gray-800 dark:text-gray-200">
                {{ $title }} ({{ $tasks[$status]->count() }})
            </h4>
            <div id="{{ $status }}" class="min-h-[60vh] space-y-4 kanban-column" data-status="{{ $status }}">
                @foreach ($tasks[$status] as $task)
                <div id="task-{{ $task->id }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg shadow cursor-grab active:cursor-grabbing">
                    <div class="flex justify-between items-start gap-2">
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $task->title }}</p>
                        <span class="badge text-xs
                            @switch($task->priority)
                                @case('low') badge-info @break
                                @case('medium') badge-success @break
                                @case('high') badge-warning @break
                                @case('urgent') badge-error @break
                            @endswitch">{{ ucfirst($task->priority) }}</span>
                    </div>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-2">{{ $task->description }}</p>

                    <div class="mt-4 flex justify-between items-center text-sm">
                        <div class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span @if($task->due_date && $task->due_date->isPast() && $task->status != 'done') class="text-error font-semibold" @endif>
                                {{ $task->due_date?->format('d M Y') ?? 'No due date' }}
                            </span>
                        </div>

                        <div class="flex items-center space-x-2">
                            @if($task->assignee)
                                <div class="tooltip" data-tip="{{ $task->assignee->name }}">
                                    <div class="avatar">
                                        <div class="w-6 rounded-full">
                                            <img src="https://i.pravatar.cc/40?u={{ $task->assignee->email }}" />
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn btn-ghost btn-xs">...</label>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-44 z-10">
                                    <li><a onclick="openEditModal({{ $task->id }})">Edit</a></li>
                                    <li>
                                        <button class="w-full text-left"
                                            onclick="promptMoveStatus({{ $task->id }}, 'in_progress')">
                                            Pindah ke In Progress
                                        </button>
                                    </li>
                                    <li>
                                        <button class="w-full text-left"
                                            onclick="promptMoveStatus({{ $task->id }}, 'done')">
                                            Pindah ke Done
                                        </button>
                                    </li>
                                    <li>
                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Anda yakin?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="w-full text-left text-error p-2 hover:bg-base-200 rounded-lg">Hapus</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Create Task Modal --}}
<div id="create_task_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Buat Tugas Baru</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="label"><span class="label-text">Judul Tugas</span></label>
                    <input type="text" name="title" placeholder="Nama tugas" class="input input-bordered w-full" required />
                </div>

                <div>
                    <label class="label"><span class="label-text">Deskripsi</span></label>
                    <textarea name="description" class="textarea textarea-bordered w-full" placeholder="Deskripsi singkat"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="label"><span class="label-text">PIC (Assignee)</span></label>
                        <select name="assignee_id" class="select select-bordered w-full">
                            <option value="">Pilih User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label"><span class="label-text">Prioritas</span></label>
                        <select name="priority" class="select select-bordered w-full" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div>
                        <label class="label"><span class="label-text">Tenggat Waktu</span></label>
                        <input type="date" name="due_date" class="input input-bordered w-full" />
                    </div>
                </div>
            </div>

            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Tugas</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- Edit Task Modal --}}
<div id="edit_task_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form id="edit_task_form" action="" method="POST">
            @csrf @method('PATCH')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Edit Tugas</h3>

            <div class="mt-4 space-y-4">
                <input type="hidden" id="edit_task_id" name="id">

                <div>
                    <label class="label"><span class="label-text">Judul Tugas</span></label>
                    <input type="text" id="edit_title" name="title" class="input input-bordered w-full" required />
                </div>

                <div>
                    <label class="label"><span class="label-text">Deskripsi</span></label>
                    <textarea id="edit_description" name="description" class="textarea textarea-bordered w-full"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="label"><span class="label-text">PIC (Assignee)</span></label>
                        <select id="edit_assignee_id" name="assignee_id" class="select select-bordered w-full">
                            <option value="">Pilih User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label"><span class="label-text">Prioritas</span></label>
                        <select id="edit_priority" name="priority" class="select select-bordered w-full" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div>
                        <label class="label"><span class="label-text">Tenggat Waktu</span></label>
                        <input type="date" id="edit_due_date" name="due_date" class="input input-bordered w-full" />
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select id="edit_status" name="status" class="select select-bordered w-full" required>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>
            </div>

            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Update Tugas</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Drag & drop antar kolom
    document.querySelectorAll('.kanban-column').forEach(column => {
        new Sortable(column, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'bg-blue-100',
            onEnd: function(evt) {
                const itemEl   = evt.item;
                const toColumn = evt.to;
                const taskId   = itemEl.id.replace('task-', '');
                const newStatus= toColumn.dataset.status;

                // Jika ke in_progress / done, tanya opsi kirim WA
                let notify_wa = 0;
                if (newStatus === 'in_progress' || newStatus === 'done') {
                    notify_wa = confirm('Kirim notifikasi WA ke PIC untuk status ini?') ? 1 : 0;
                }
                updateTaskStatus(taskId, newStatus, itemEl, notify_wa);
            },
        });
    });

    // tombol cepat di dropdown: Pindah status + opsi WA
    window.promptMoveStatus = function(taskId, newStatus) {
        let notify_wa = 0;
        if (newStatus === 'in_progress' || newStatus === 'done') {
            notify_wa = confirm('Kirim notifikasi WA ke PIC untuk status ini?') ? 1 : 0;
        }
        const itemEl = document.getElementById('task-' + taskId);
        updateTaskStatus(taskId, newStatus, itemEl, notify_wa);
    };

    function updateTaskStatus(taskId, status, element, notify_wa = 0) {
        const url   = `/tasks/${taskId}/update-status`;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status, notify_wa })
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            if (data.success) {
                element.style.borderColor = 'green';
                setTimeout(() => { element.style.borderColor = ''; }, 800);
            } else {
                console.error('Failed to update task status');
                element.style.borderColor = 'red';
                setTimeout(() => { element.style.borderColor = ''; }, 1000);
            }
        })
        .catch(err => {
            console.error('Error updating status:', err);
            element.style.borderColor = 'red';
            setTimeout(() => { element.style.borderColor = ''; }, 1000);
        });
    }
});

// --- Edit Modal ---
async function openEditModal(taskId) {
    try {
        const res = await fetch(`/tasks/${taskId}/edit`, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            const text = await res.text();
            throw new Error(`HTTP ${res.status} – ${text.slice(0, 200)}`);
        }

        const payload = await res.json();
        const task = payload.task || payload;

        // Set form action PATCH /tasks/{id}
        const form = document.getElementById('edit_task_form');
        form.action = `/tasks/${taskId}`;

        // Isi field
        document.getElementById('edit_title').value       = task.title || '';
        document.getElementById('edit_description').value = task.description || '';
        document.getElementById('edit_assignee_id').value = task.assignee_id ?? '';
        document.getElementById('edit_priority').value    = task.priority || 'medium';
        document.getElementById('edit_status').value      = task.status || 'open';
        document.getElementById('edit_due_date').value    = normalizeDateForInput(task.due_date);

        // Buka modal
        location.hash = 'edit_task_modal';

    } catch (e) {
        console.error('Edit modal error:', e);
        alert('Tidak dapat memuat data tugas. Silakan coba lagi.');
    }
}

function normalizeDateForInput(val) {
    if (!val) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val; // sudah yyyy-mm-dd
    const d = new Date(val);
    return isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10);
}
</script>
@endpush
