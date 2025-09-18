@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
<div class="container mx-auto py-6">

    {{-- Alerts --}}
    @if (session('success'))
    <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif
    @if ($errors->any())
    <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">Papan Tugas (Kanban)</h1>
            <p class="mt-1 text-base-content/70">Kelola alur kerja tim Anda secara visual.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="create_task_modal.showModal()">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Buat Tugas Baru
        </button>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
        <form action="{{ route('tasks.index') }}" method="GET" class="card-body p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
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
            </div>
        </form>
    </div>

    {{-- Papan Kanban --}}
    {{-- FIXED: Menambahkan kolom 'review' dan mengubah grid menjadi 4 kolom --}}
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach (['open' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'] as $status => $title)
        <div class="card bg-base-200/50" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
            <div class="card-body p-4">
                <h4 class="font-semibold text-lg text-base-content flex items-center justify-between">
                    <span>{{ $title }}</span>
                    {{-- FIXED: Menambahkan guard `?? collect()` untuk mencegah error jika status tidak ada --}}
                    <span class="badge badge-ghost">{{ ($tasks[$status] ?? collect())->count() }}</span>
                </h4>
                <div id="{{ $status }}" class="min-h-[60vh] space-y-4 kanban-column pt-4" data-status="{{ $status }}">
                    @foreach (($tasks[$status] ?? []) as $task)
                    @php
                        $color = $task->color ?? 'primary';
                        $colorClass = "border-t-4 border-{$color}";
                        $priorityBadge = match($task->priority) {
                            'low' => 'badge-info', 'medium' => 'badge-success',
                            'high' => 'badge-warning', 'urgent' => 'badge-error',
                            default => 'badge-ghost'
                        };
                    @endphp
                    <div id="task-{{ $task->id }}" class="card bg-base-100 shadow-md cursor-grab active:cursor-grabbing {{ $colorClass }}">
                        <div class="card-body p-4">
                            <div class="flex justify-between items-start gap-2">
                                <p class="font-semibold text-base-content leading-tight">{{ $task->title }}</p>
                                <span class="badge {{ $priorityBadge }} badge-sm">{{ ucfirst($task->priority) }}</span>
                            </div>

                            @if($task->description)
                            <p class="text-sm text-base-content/70 mt-2 line-clamp-3">{{ $task->description }}</p>
                            @endif

                            <div class="card-actions justify-between items-center mt-4">
                                <div class="flex items-center gap-2 text-sm text-base-content/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span @if($task->due_date && $task->due_date->isPast() && $task->status != 'done') class="text-error font-semibold" @endif>
                                        {{ $task->due_date?->format('d M Y') ?? 'Tanpa tenggat' }}
                                    </span>
                                </div>

                                <div class="flex items-center -space-x-3">
                                    @if($task->link)
                                        <a href="{{ $task->link }}" target="_blank" class="btn btn-ghost btn-xs btn-circle" data-tip="Buka Link">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                        </a>
                                    @endif
                                    @if($task->assignee)
                                        <div class="tooltip" data-tip="{{ $task->assignee->name }}">
                                            <div class="avatar">
                                                <div class="w-8 rounded-full ring ring-base-100">
                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($task->assignee->name) }}&background=random" />
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="dropdown dropdown-end">
                                        <label tabindex="0" class="btn btn-ghost btn-xs btn-circle">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg>
                                        </label>
                                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-48 z-10 border border-base-300/50">
                                            <li><a onclick="openEditModal({{ $task->id }})">Edit Tugas</a></li>
                                            <div class="divider my-1"></div>
                                            <li><a onclick="promptMoveStatus({{ $task->id }}, 'open')">Pindah ke To Do</a></li>
                                            <li><a onclick="promptMoveStatus({{ $task->id }}, 'in_progress')">Pindah ke In Progress</a></li>
                                            {{-- FIXED: Menambahkan opsi pindah ke Review --}}
                                            <li><a onclick="promptMoveStatus({{ $task->id }}, 'review')">Pindah ke Review</a></li>
                                            <li><a onclick="promptMoveStatus({{ $task->id }}, 'done')">Pindah ke Done</a></li>
                                            <div class="divider my-1"></div>
                                            <li>
                                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Anda yakin?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-full text-left text-error">Hapus Tugas</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
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

{{-- Modal Buat Tugas --}}
@php
    $colorOptions = ['primary', 'secondary', 'accent', 'info', 'success', 'warning', 'error'];
@endphp
<dialog id="create_task_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
        <h3 class="font-bold text-lg text-base-content">Buat Tugas Baru</h3>
        <form action="{{ route('tasks.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div class="form-control">
                <label class="label"><span class="label-text">Judul Tugas</span></label>
                <input type="text" name="title" placeholder="Nama tugas" class="input input-bordered w-full" required />
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Deskripsi</span></label>
                <textarea name="description" class="textarea textarea-bordered w-full" placeholder="Deskripsi singkat"></textarea>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Link (Opsional)</span></label>
                <input type="url" name="link" placeholder="https://example.com" class="input input-bordered w-full" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text">PIC (Assignee)</span></label>
                    <select name="assignee_id" class="select select-bordered w-full">
                        <option value="">Pilih User</option>
                        @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Prioritas</span></label>
                    <select name="priority" class="select select-bordered w-full" required>
                        <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Tenggat Waktu</span></label>
                    <input type="date" name="due_date" class="input input-bordered w-full" />
                </div>
            </div>
            <div>
                <label class="label"><span class="label-text">Warna Kartu</span></label>
                <div class="flex flex-wrap gap-2">
                    @foreach($colorOptions as $color)
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-2">
                          <input type="radio" name="color" class="radio radio-{{$color}}" value="{{$color}}" @checked($loop->first) />
                          <span class="label-text capitalize">{{$color}}</span>
                        </label>
                      </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-action mt-6">
                <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
                <button type="submit" class="btn btn-primary">Simpan Tugas</button>
            </div>
        </form>
    </div>
</dialog>

{{-- Modal Edit Tugas --}}
<dialog id="edit_task_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
        <h3 class="font-bold text-lg text-base-content">Edit Tugas</h3>
        <form id="edit_task_form" action="" method="POST" class="mt-4 space-y-4">
            @csrf @method('PATCH')
            <input type="hidden" id="edit_task_id" name="id">
            <div class="form-control">
                <label class="label"><span class="label-text">Judul Tugas</span></label>
                <input type="text" id="edit_title" name="title" class="input input-bordered w-full" required />
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Deskripsi</span></label>
                <textarea id="edit_description" name="description" class="textarea textarea-bordered w-full"></textarea>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Link (Opsional)</span></label>
                <input type="url" id="edit_link" name="link" class="input input-bordered w-full" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text">PIC (Assignee)</span></label>
                    <select id="edit_assignee_id" name="assignee_id" class="select select-bordered w-full">
                        <option value="">Pilih User</option>
                        @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Prioritas</span></label>
                    <select id="edit_priority" name="priority" class="select select-bordered w-full" required>
                        <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Tenggat Waktu</span></label>
                    <input type="date" id="edit_due_date" name="due_date" class="input input-bordered w-full" />
                </div>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Status</span></label>
                <select id="edit_status" name="status" class="select select-bordered w-full" required>
                    <option value="open">To Do</option>
                    <option value="in_progress">In Progress</option>
                    {{-- FIXED: Menambahkan opsi Review --}}
                    <option value="review">Review</option>
                    <option value="done">Done</option>
                </select>
            </div>
             <div>
                <label class="label"><span class="label-text">Warna Kartu</span></label>
                <div id="edit_color_options" class="flex flex-wrap gap-2">
                    @foreach($colorOptions as $color)
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-2">
                          <input type="radio" name="color" class="radio radio-{{$color}}" value="{{$color}}" />
                          <span class="label-text capitalize">{{$color}}</span>
                        </label>
                      </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-action mt-6">
                 <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
                <button type="submit" class="btn btn-primary">Update Tugas</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.kanban-column').forEach(column => {
        new Sortable(column, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'bg-primary/20',
            onEnd: function(evt) {
                const itemEl   = evt.item;
                const toColumn = evt.to;
                const taskId   = itemEl.id.replace('task-', '');
                const newStatus= toColumn.dataset.status;
                updateTaskStatus(taskId, newStatus, itemEl);
            },
        });
    });

    window.promptMoveStatus = function(taskId, newStatus) {
        const itemEl = document.getElementById('task-' + taskId);
        const newColumn = document.getElementById(newStatus);
        if (newColumn) {
            newColumn.appendChild(itemEl);
        }
        updateTaskStatus(taskId, newStatus, itemEl);
    };

    function updateTaskStatus(taskId, status, element) {
        const url   = `/tasks/${taskId}/update-status`;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({ status })
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .catch(err => console.error('Error updating status:', err));
    }
});

async function openEditModal(taskId) {
    try {
        const res = await fetch(`/tasks/${taskId}/edit`, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const payload = await res.json();
        const task = payload.task || payload;

        const form = document.getElementById('edit_task_form');
        form.action = `/tasks/${taskId}`;

        document.getElementById('edit_title').value = task.title || '';
        document.getElementById('edit_description').value = task.description || '';
        document.getElementById('edit_assignee_id').value = task.assignee_id ?? '';
        document.getElementById('edit_priority').value = task.priority || 'medium';
        document.getElementById('edit_status').value = task.status || 'open';
        document.getElementById('edit_due_date').value = task.due_date ? new Date(task.due_date).toISOString().slice(0, 10) : '';
        document.getElementById('edit_link').value = task.link || '';

        const colorOptions = document.querySelectorAll('#edit_color_options input[name="color"]');
        let colorFound = false;
        colorOptions.forEach(radio => {
            if(radio.value === task.color) {
                radio.checked = true;
                colorFound = true;
            } else {
                radio.checked = false;
            }
        });
        if(!colorFound) colorOptions[0].checked = true;

        document.getElementById('edit_task_modal').showModal();

    } catch (e) {
        console.error('Edit modal error:', e);
        alert('Tidak dapat memuat data tugas. Silakan coba lagi.');
    }
}
</script>
@endpush

