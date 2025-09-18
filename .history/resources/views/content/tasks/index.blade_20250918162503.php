@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
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
  .board-col-title{ font-weight: 800; letter-spacing: .2px; }
  .board-col-body{ min-height: 64vh; padding-top: 1rem; }
  @media (max-width: 640px){
    .board-col-body{ min-height: 56vh; padding-top: .75rem; }
  }

  .kanban-column.is-over{ outline: 2px dashed oklch(var(--tone)/.45); outline-offset: 4px; }
  .sortable-chosen{ transform: scale(1.02); transition: transform .15s ease; }

  .task-card{ border-radius:14px; box-shadow:0 1px 1px rgba(0,0,0,.04), 0 10px 26px -14px rgba(0,0,0,.22); }
  .task-card:hover{ box-shadow:0 1px 1px rgba(0,0,0,.06), 0 14px 32px -14px rgba(0,0,0,.28); }
  .task-card{ border-top: 4px solid oklch(var(--p)); } /* warna otomatis dari tema */
  .flash-ring{ box-shadow: 0 0 0 3px oklch(var(--su)/.55); transition: box-shadow .8s ease; }
</style>

@php
  $statusTitles = ['open'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'];
  $statusTones  = ['open'=>'tone-info','in_progress'=>'tone-primary','review'=>'tone-warning','done'=>'tone-success'];

  function prioBadge($p){
      return match($p){ 'low'=>'badge-info','medium'=>'badge-success','high'=>'badge-warning','urgent'=>'badge-error', default=>'badge-ghost' };
  }
@endphp

<div class="container mx-auto px-3 sm:px-4 lg:px-6 py-5 lg:py-8 board-surface">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-6 mb-6 lg:mb-8">
    <div class="space-y-1">
      <h1 class="text-3xl sm:text-4xl font-extrabold text-base-content">Papan Tugas (Kanban)</h1>
      <p class="text-base-content/70">Kelola alur kerja tim Anda secara visual.</p>
    </div>
    <button type="button" class="btn btn-primary shadow"
            onclick="document.getElementById('create_task_modal').showModal()">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M5 12h14"/><path d="M12 5v14"/></svg>
      Buat Tugas Baru
    </button>
  </div>

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

  <div class="flex lg:grid gap-4 lg:gap-6 overflow-x-auto snap-x lg:overflow-visible pb-2 lg:pb-0 lg:grid-cols-4">
    @foreach ($statusTitles as $status => $title)
      <section class="board-col {{ $statusTones[$status] }} min-w-[280px] sm:min-w-[320px] snap-center">
        <div class="p-3 sm:p-4 lg:p-5">
          <header class="board-col-title text-base sm:text-lg text-base-content flex items-center justify-between mb-2">
            <span>{{ $title }}</span>
            <span class="badge badge-ghost">{{ $tasks[$status]->count() }}</span>
          </header>

          <div id="{{ $status }}" class="kanban-column board-col-body space-y-3 sm:space-y-4" data-status="{{ $status }}">
            @foreach ($tasks[$status] as $task)
              @php $priorityBadge = prioBadge($task->priority); @endphp
              <article id="task-{{ $task->id }}"
                       class="card bg-base-100 shadow-md cursor-grab active:cursor-grabbing task-card"
                       data-current-status="{{ $status }}">
                <div class="card-body p-3 sm:p-4">
                  <div class="flex justify-between items-start gap-2">
                    <p class="font-semibold text-base-content leading-tight line-clamp-2">{{ $task->title }}</p>
                    <span class="badge {{ $priorityBadge }} badge-sm">{{ ucfirst($task->priority) }}</span>
                  </div>

                  @if($task->description)
                    <p class="text-sm text-base-content/70 mt-2 line-clamp-3">{{ $task->description }}</p>
                  @endif

                  <div class="card-actions justify-between items-center mt-3">
                    <div class="flex items-center gap-2 text-xs sm:text-sm text-base-content/70">
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
                          <li><a onclick="uiMoveStatus({{ $task->id }}, 'open')">Pindah ke To Do</a></li>
                          <li><a onclick="uiMoveStatus({{ $task->id }}, 'in_progress')">Pindah ke In Progress</a></li>
                          <li><a onclick="uiMoveStatus({{ $task->id }}, 'review')">Pindah ke Review</a></li>
                          <li><a onclick="uiMoveStatus({{ $task->id }}, 'done')">Pindah ke Done</a></li>
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
              </article>
            @endforeach
          </div>
        </div>
      </section>
    @endforeach
  </div>
</div>

{{-- ================ MODAL: CREATE ================ --}}
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

      <div class="form-control">
        <label class="label"><span class="label-text">Link (Opsional)</span></label>
        <input type="url" name="link" class="input input-bordered w-full" placeholder="https://example.com" />
      </div>

      <div class="modal-action mt-6">
        <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
        <button type="submit" class="btn btn-primary">Simpan Tugas</button>
      </div>
    </form>
  </div>
</dialog>

{{-- ================ MODAL: EDIT ================ --}}
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
        <label class="label"><span class="label-text">Link (Opsional)</span></label>
        <input type="url" id="edit_link" name="link" class="input input-bordered w-full" />
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

{{-- ===== Modal Konfirmasi WA (reusable) ===== --}}
<dialog id="wa_confirm_modal" class="modal">
  <div class="modal-box max-w-sm">
    <h3 class="font-bold text-lg">Kirim Notifikasi WhatsApp?</h3>
    <p class="py-2 text-base-content/70" id="wa_confirm_message">Konfirmasi pengiriman WA.</p>
    <div class="modal-action">
      <button class="btn btn-ghost" id="wa_confirm_no">No</button>
      <button class="btn btn-primary" id="wa_confirm_yes">Yes</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  function csrf(){ return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' }
  function addOver(col,on){ col.classList.toggle('is-over', !!on) }
  function flash(el){ el?.classList.add('flash-ring'); setTimeout(()=>el?.classList.remove('flash-ring'),700) }

  // ===== Reusable modal confirm (returns Promise<boolean>)
  function waConfirm(message = 'Kirim notifikasi WhatsApp untuk perubahan ini?') {
    const dlg = document.getElementById('wa_confirm_modal');
    const msg = document.getElementById('wa_confirm_message');
    const yes = document.getElementById('wa_confirm_yes');
    const no  = document.getElementById('wa_confirm_no');
    msg.textContent = message;

    return new Promise((resolve) => {
      function cleanup() {
        yes.removeEventListener('click', onYes);
        no.removeEventListener('click', onNo);
        dlg.close();
      }
      function onYes(e){ e.preventDefault(); resolve(true); cleanup(); }
      function onNo (e){ e.preventDefault(); resolve(false); cleanup(); }
      yes.addEventListener('click', onYes);
      no .addEventListener('click', onNo);
      dlg.showModal();
    });
  }

  // ===== Drag & Drop dengan modal confirm WA
  document.querySelectorAll('.kanban-column').forEach(column => {
    new Sortable(column, {
      group: 'tasks',
      animation: 150,
      ghostClass: 'bg-primary/20',
      onChoose: () => addOver(column,true),
      onUnchoose: () => addOver(column,false),
      onAdd: () => addOver(column,false),
      onEnd: async function(evt) {
        const itemEl    = evt.item;
        const toColumn  = evt.to;
        const taskId    = (itemEl.id || '').replace('task-', '');
        const newStatus = toColumn?.dataset?.status;
        const fromStatus= itemEl.getAttribute('data-current-status');
        if (!taskId || !newStatus || !fromStatus || newStatus === fromStatus) return;

        // Tampilkan modal Yes/No
        const doWa = await waConfirm('Kirim WA ke PIC/Owner untuk perubahan status ini?');
        await updateTaskStatus(taskId, newStatus, itemEl, doWa ? 1 : 0);

        // Update state lokal
        itemEl.setAttribute('data-current-status', newStatus);
        flash(itemEl);
      },
    });
  });

  // ===== Pindah status via menu dropdown (pakai modal confirm juga)
  window.uiMoveStatus = async function(taskId, newStatus) {
    const itemEl = document.getElementById('task-' + taskId);
    if (!itemEl) return;
    const cur = itemEl.getAttribute('data-current-status');
    if (cur === newStatus) return;

    const doWa = await waConfirm('Kirim WA ke PIC/Owner untuk perubahan status ini?');

    // Pindahkan kartu secara visual dulu (optimistic UI)
    const newCol = document.getElementById(newStatus);
    if (newCol) newCol.appendChild(itemEl);

    await updateTaskStatus(taskId, newStatus, itemEl, doWa ? 1 : 0);

    itemEl.setAttribute('data-current-status', newStatus);
    flash(itemEl);
  };

  async function updateTaskStatus(taskId, status, element, notify_wa = 0) {
    try {
      const r = await fetch(`/tasks/${encodeURIComponent(taskId)}/update-status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ status, notify_wa })
      });
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      await r.json();
    } catch (err) {
      console.error('Error updating status:', err);
      // fallback: reload agar state sinkron
      window.location.reload();
    }
  }

  // ===== Edit modal loader
  window.openEditModal = async function(taskId) {
    try {
      const res = await fetch(`/tasks/${encodeURIComponent(taskId)}/edit`, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const payload = await res.json();
      const task = payload.task || payload;

      const f = document.getElementById('edit_task_form');
      f.action = `/tasks/${encodeURIComponent(taskId)}`;
      document.getElementById('edit_task_id').value = task.id ?? taskId;

      document.getElementById('edit_title').value       = task.title || '';
      document.getElementById('edit_description').value = task.description || '';
      document.getElementById('edit_assignee_id').value = task.assignee_id ?? '';
      document.getElementById('edit_priority').value    = task.priority || 'medium';
      document.getElementById('edit_status').value      = task.status || 'open';
      document.getElementById('edit_due_date').value    = task.due_date ? new Date(task.due_date).toISOString().slice(0,10) : '';
      document.getElementById('edit_link').value        = task.link || '';

      document.getElementById('edit_task_modal').showModal();
    } catch (e) {
      console.error('Edit modal error:', e);
      alert('Tidak dapat memuat data tugas. Silakan coba lagi.');
    }
  };

  // ===== Submit edit → modal konfirmasi WA (Yes/No)
  const editForm = document.getElementById('edit_task_form');
  if (editForm) {
    editForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = e.target;

      const doWa = await waConfirm('Kirim WA ke PIC/Owner untuk update ini?');

      const fd = new FormData(form);
      if (doWa) fd.set('notify_wa', '1');

      try {
        const r = await fetch(form.action, {
          method: 'POST', // Laravel honor _method=PATCH
          headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
          body: fd
        });
        let data = null;
        try { data = await r.json(); } catch(_){ data = null; }
        if (!r.ok || !data) { window.location.reload(); return; }

        const task = data.task || data;
        const card = document.getElementById('task-'+task.id);
        if (card) {
          // Update kolom jika status berubah
          const toCol = document.getElementById(task.status || 'open');
          if (toCol && card.parentElement !== toCol) toCol.appendChild(card);
          card.setAttribute('data-current-status', task.status || 'open');

          // Update teks utama
          const titleEl = card.querySelector('p.font-semibold');
          if (titleEl) titleEl.textContent = task.title || '';
          const descEl = card.querySelector('p.text-sm');
          if (descEl) descEl.textContent = task.description || '';
          const prioEl = card.querySelector('.badge');
          if (prioEl) {
            const map = {low:'badge-info', medium:'badge-success', high:'badge-warning', urgent:'badge-error'};
            const cls = map[task.priority || 'medium'] || 'badge-ghost';
            prioEl.className = 'badge ' + cls + ' badge-sm';
            prioEl.textContent = (task.priority||'medium').replace(/^\w/, c=>c.toUpperCase());
          }
          const dateEl = card.querySelector('.card-actions span');
          if (dateEl) dateEl.textContent = task.due_date ? new Date(task.due_date).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' }) : 'Tanpa tenggat';
        }
        document.getElementById('edit_task_modal').close();
        flash(card);
      } catch (err) {
        console.error('Gagal update tugas:', err);
        window.location.reload();
      }
    });
  }
});
</script>
@endsection
