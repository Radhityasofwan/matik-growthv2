@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
{{-- ================== STYLE KHUSUS HALAMAN ================== --}}
<style>
  .board-surface{
    border-radius: 1rem;
    background:
      radial-gradient(42rem 24rem at -12% -10%, oklch(var(--p)/.08), transparent 60%),
      radial-gradient(36rem 22rem at 112% 0%,  oklch(var(--a)/.08), transparent 62%),
      linear-gradient(180deg, oklch(var(--b1)), oklch(var(--b1)));
  }
  .tone-primary   { --tone: var(--p); }
  .tone-info      { --tone: var(--in); }
  .tone-warning   { --tone: var(--wa); }
  .tone-success   { --tone: var(--su); }

  .board-col{
    border-radius: .9rem;
    background:
      linear-gradient(180deg, oklch(var(--tone)/.10), transparent 55%),
      linear-gradient(180deg, oklch(var(--b2)), oklch(var(--b3)));
    border: 1px solid oklch(var(--b3));
  }
  .board-col-body{ min-height: 64vh; padding-top: 1rem; }
  .board-col-title{ font-weight: 800; letter-spacing: .2px; }

  /* Drag & Drop (sinkron referensi) */
  .kanban-column.is-over{ outline: 2px dashed oklch(var(--tone)/.45); outline-offset: 4px; }
  .sortable-chosen{ transform: scale(1.02); transition: transform .15s ease; }

  /* Kartu – warna fleksibel via CSS var per kartu */
  .task-card{ border-radius:14px; box-shadow:0 1px 1px rgba(0,0,0,.04), 0 10px 26px -14px rgba(0,0,0,.22); }
  .task-card:hover{ box-shadow:0 1px 1px rgba(0,0,0,.06), 0 14px 32px -14px rgba(0,0,0,.28); }
  .task-card{ border-top: 4px solid var(--card-color, oklch(var(--p))); }

  .flash-ring{ box-shadow: 0 0 0 3px oklch(var(--su)/.55); transition: box-shadow .8s ease; }
</style>

@php
  // Judul & nada kolom
  $statusTitles = ['open'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'];
  $statusTones  = ['open'=>'tone-info','in_progress'=>'tone-primary','review'=>'tone-warning','done'=>'tone-success'];

  // Badge prioritas
  function prioBadge($p){
      return match($p){ 'low'=>'badge-info','medium'=>'badge-success','high'=>'badge-warning','urgent'=>'badge-error', default=>'badge-ghost' };
  }

  // Konversi nilai warna fleksibel dari DB -> CSS valid
  // - HEX/RGB/HSL/nama warna dipakai langsung
  // - Token DaisyUI (primary, success, ...) diubah ke oklch(var(--token))
  function cssColor($val){
      $v = trim(strtolower((string)$val));
      if ($v === '') return '#3b82f6';
      $map = [
        'primary'=>'oklch(var(--p))',
        'secondary'=>'oklch(var(--s))',
        'accent'=>'oklch(var(--a))',
        'info'=>'oklch(var(--in))',
        'success'=>'oklch(var(--su))',
        'warning'=>'oklch(var(--wa))',
        'error'=>'oklch(var(--er))',
        'neutral'=>'oklch(var(--n))',
      ];
      if (isset($map[$v])) return $map[$v];
      if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $v)) return $v;
      if (preg_match('/^rgba?\s*\([\d\s.,%]+\)$/i', $v)) return $v;
      if (preg_match('/^hsla?\s*\([\d\s.,%]+\)$/i', $v)) return $v;
      if (preg_match('/^[a-z]+$/i', $v)) return $v; // nama warna CSS (e.g. red, blue)
      return '#3b82f6';
  }
@endphp

<div class="container mx-auto px-4 lg:px-6 py-6 lg:py-8 board-surface">

  {{-- ===== Header & CTA ===== --}}
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-6 mb-6 lg:mb-8">
    <div class="space-y-1">
      <h1 class="text-3xl lg:text-4xl font-extrabold text-base-content">Papan Tugas (Kanban)</h1>
      <p class="text-base-content/70">Kelola alur kerja tim Anda secara visual.</p>
    </div>
    <button type="button" class="btn btn-primary shadow"
            onclick="document.getElementById('create_task_modal').showModal()">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M5 12h14"/><path d="M12 5v14"/></svg>
      Buat Tugas Baru
    </button>
  </div>

  {{-- ===== Filters ===== --}}
  <div class="card bg-base-100 shadow-md border border-base-300/60 mb-6 lg:mb-8">
    <form action="{{ route('tasks.index') }}" method="GET" class="card-body p-4 lg:p-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] gap-3 lg:gap-4">
        <select name="assignee_id" class="select select-bordered w-full">
          <option value="">Semua PIC</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}" @selected(request('assignee_id') == $user->id)>{{ $user->name }}</option>
          @endforeach
        </select>
        <select name="priority" class="select select-bordered w-full">
          <option value="">Semua Prioritas</option>
          <option value="low" @selected(request('priority')=='low')>Low</option>
          <option value="medium" @selected(request('priority')=='medium')>Medium</option>
          <option value="high" @selected(request('priority')=='high')>High</option>
          <option value="urgent" @selected(request('priority')=='urgent')>Urgent</option>
        </select>
        <button type="submit" class="btn btn-primary w-full lg:w-auto">Filter</button>
      </div>
    </form>
  </div>

  {{-- ===== Kanban Board ===== --}}
  <div class="flex lg:grid gap-5 lg:gap-6 overflow-x-auto snap-x lg:overflow-visible pb-2 lg:pb-0 lg:grid-cols-4">
    @foreach ($statusTitles as $status => $title)
      <section class="board-col {{ $statusTones[$status] }} min-w-[320px] snap-center">
        <div class="p-4 lg:p-5">
          <header class="board-col-title text-base lg:text-lg text-base-content flex items-center justify-between mb-2">
            <span>{{ $title }}</span>
            <span class="badge badge-ghost">{{ $tasks[$status]->count() }}</span>
          </header>

          <div id="{{ $status }}" class="kanban-column board-col-body space-y-4" data-status="{{ $status }}">
            @foreach ($tasks[$status] as $task)
              @php
                $priorityBadge = prioBadge($task->priority);
                $css = cssColor($task->color ?? '');
              @endphp

              <div id="task-{{ $task->id }}"
                   class="card bg-base-100 shadow-md cursor-grab active:cursor-grabbing task-card"
                   style="--card-color: {{ $css }};">
                <div class="card-body p-4">
                  <div class="flex justify-between items-start gap-2">
                    <p class="font-semibold text-base-content leading-tight line-clamp-2">{{ $task->title }}</p>
                    <span class="badge {{ $priorityBadge }} badge-sm">{{ ucfirst($task->priority) }}</span>
                  </div>

                  @if($task->description)
                    <p class="text-sm text-base-content/70 mt-2 line-clamp-3">{{ $task->description }}</p>
                  @endif

                  <div class="card-actions justify-between items-center mt-4">
                    <div class="flex items-center gap-2 text-sm text-base-content/70">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span @if($task->due_date && $task->due_date->isPast() && $task->status!=='done') class="text-error font-semibold" @endif>
                        {{ $task->due_date?->format('d M Y') ?? 'Tanpa tenggat' }}
                      </span>
                    </div>

                    <div class="flex items-center gap-1">
                      @if($task->link)
                        <a href="{{ $task->link }}" target="_blank" class="btn btn-ghost btn-xs btn-circle" data-tip="Buka Link">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                          </svg>
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
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2z" />
                          </svg>
                        </label>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-48 z-10 border border-base-300/50">
                          <li><a onclick="openEditModal({{ $task->id }})">Edit Tugas</a></li>
                          <div class="divider my-1"></div>
                          <li><a onclick="promptMoveStatus({{ $task->id }}, 'open')">Pindah ke To Do</a></li>
                          <li><a onclick="promptMoveStatus({{ $task->id }}, 'in_progress')">Pindah ke In Progress</a></li>
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
      </section>
    @endforeach
  </div>
</div>

{{-- ================== MODALS ================== --}}
<dialog id="create_task_modal" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
    <h3 class="font-bold text-lg text-base-content">Buat Tugas Baru</h3>
    <form action="{{ route('tasks.store') }}" method="POST" class="mt-4 space-y-4" id="create_form">
      @csrf
      <div class="form-control">
        <label class="label"><span class="label-text">Judul Tugas</span></label>
        <input type="text" name="title" class="input input-bordered w-full" required />
      </div>
      <div class="form-control">
        <label class="label"><span class="label-text">Deskripsi</span></label>
        <textarea name="description" class="textarea textarea-bordered w-full" placeholder="Deskripsi singkat"></textarea>
      </div>
      <div class="form-control">
        <label class="label"><span class="label-text">Link (Opsional)</span></label>
        <input type="url" name="link" class="input input-bordered w-full" placeholder="https://example.com" />
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

      {{-- Warna kartu fleksibel --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="form-control">
          <label class="label"><span class="label-text">Pilih Warna (HEX)</span></label>
          <input type="color" id="create_color_picker" value="#3b82f6" class="input w-20 h-10 p-0 border border-base-300 rounded" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Atau ketik warna (HEX/RGB/HSL/nama)</span></label>
          <input type="text" name="color" id="create_color_text" value="#3b82f6"
                 class="input input-bordered w-full" placeholder="#3b82f6 atau rgb(59,130,246) atau blue" />
        </div>
      </div>

      <div class="modal-action mt-6">
        <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
        <button type="submit" class="btn btn-primary">Simpan Tugas</button>
      </div>
    </form>
  </div>
</dialog>

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

      {{-- Warna kartu fleksibel --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="form-control">
          <label class="label"><span class="label-text">Pilih Warna (HEX)</span></label>
          <input type="color" id="edit_color_picker" value="#3b82f6"
                 class="input w-20 h-10 p-0 border border-base-300 rounded" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Atau ketik warna (HEX/RGB/HSL/nama)</span></label>
          <input type="text" id="edit_color_text" name="color" value="#3b82f6"
                 class="input input-bordered w-full" placeholder="#3b82f6 atau rgb(59,130,246) atau blue" />
        </div>
      </div>

      <div class="form-control">
        <label class="label"><span class="label-text">Status</span></label>
        <select id="edit_status" name="status" class="select select-bordered w-full" required>
          <option value="open">To Do</option>
          <option value="in_progress">In Progress</option>
          <option value="review">Review</option>
          <option value="done">Done</option>
        </select>
      </div>

      <div class="modal-action mt-6">
        <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
        <button type="submit" class="btn btn-primary">Update Tugas</button>
      </div>
    </form>
  </div>
</dialog>

{{-- ================== JS: Sortable (mengikuti referensi) ================== --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

  // ====== DRAG & DROP (sesuai referensi) ======
  document.querySelectorAll('.kanban-column').forEach(column => {
    new Sortable(column, {
      group: 'tasks',
      animation: 150,
      ghostClass: 'bg-primary/20',
      onEnd: function(evt) {
        const itemEl    = evt.item;
        const toColumn  = evt.to;
        const taskId    = (itemEl.id || '').replace('task-', '');
        const newStatus = toColumn?.dataset?.status;
        if (!taskId || !newStatus) return;
        promptAndUpdateStatus(taskId, newStatus, itemEl);
      },
    });
  });

  // ====== ACTION: pindah status via menu ======
  window.promptMoveStatus = function(taskId, newStatus) {
    const itemEl   = document.getElementById('task-' + taskId);
    const newCol   = document.getElementById(newStatus);
    if (itemEl && newCol) newCol.appendChild(itemEl);
    promptAndUpdateStatus(taskId, newStatus, itemEl);
  };

  // ====== UPDATE STATUS ke server ======
  function promptAndUpdateStatus(taskId, status, element) {
    let notify_wa = 0;
    if (['in_progress','review','done'].includes(status)) {
      if (confirm('Kirim notifikasi WA ke PIC untuk perubahan status ini?')) notify_wa = 1;
    }
    updateTaskStatus(taskId, status, element, notify_wa);
  }

  function updateTaskStatus(taskId, status, element, notify_wa = 0) {
    const url   = `/tasks/${encodeURIComponent(taskId)}/update-status`;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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
    .then(() => { element?.classList.add('flash-ring'); setTimeout(()=>element?.classList.remove('flash-ring'), 700); })
    .catch(err => console.error('Error updating status:', err));
  }

  // ====== EDIT MODAL ======
  window.openEditModal = async function(taskId) {
    try {
      const res = await fetch(`/tasks/${encodeURIComponent(taskId)}/edit`, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const payload = await res.json();
      const task = payload.task || payload;

      const form = document.getElementById('edit_task_form');
      form.action = `/tasks/${encodeURIComponent(taskId)}`;
      document.getElementById('edit_task_id').value = task.id ?? taskId;

      document.getElementById('edit_title').value       = task.title || '';
      document.getElementById('edit_description').value = task.description || '';
      document.getElementById('edit_assignee_id').value = task.assignee_id ?? '';
      document.getElementById('edit_priority').value    = task.priority || 'medium';
      document.getElementById('edit_status').value      = task.status || 'open';
      document.getElementById('edit_due_date').value    = task.due_date ? new Date(task.due_date).toISOString().slice(0,10) : '';
      document.getElementById('edit_link').value        = task.link || '';

      // Warna fleksibel dari DB
      const colorText  = document.getElementById('edit_color_text');
      const colorPick  = document.getElementById('edit_color_picker');
      const colVal     = (task.color || '#3b82f6').trim();
      colorText.value  = colVal;
      const hexMatch = colVal.match(/^#([0-9a-f]{6}|[0-9a-f]{3})$/i);
      colorPick.value  = hexMatch ? (hexMatch[0].length===4 ? normalizeHex3(hexMatch[0]) : hexMatch[0]) : '#3b82f6';

      document.getElementById('edit_task_modal').showModal();
    } catch (e) {
      console.error('Edit modal error:', e);
      alert('Tidak dapat memuat data tugas. Silakan coba lagi.');
    }
  };

  // ====== SYNC COLOR PICKER <-> TEXT (Create & Edit) ======
  const createPicker = document.getElementById('create_color_picker');
  const createText   = document.getElementById('create_color_text');
  if (createPicker && createText) {
    createPicker.addEventListener('input', () => createText.value = createPicker.value);
  }

  const editPicker = document.getElementById('edit_color_picker');
  const editText   = document.getElementById('edit_color_text');
  if (editPicker && editText) {
    editPicker.addEventListener('input', () => editText.value = editPicker.value);
  }

  function normalizeHex3(h){
    // #abc -> #aabbcc
    const r=h[1], g=h[2], b=h[3]; return `#${r}${r}${g}${g}${b}${b}`;
  }
});
</script>
@endsection
