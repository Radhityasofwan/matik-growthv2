@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
{{-- ===== CSS khusus halaman ini (adaptif DaisyUI) ===== --}}
<style>
  /* Permukaan papan ala whiteboard/soft chalk dengan tint warna tema */
  .board-surface{
    border-radius: 1rem;
    background-image:
      radial-gradient(40rem 24rem at -10% -10%, color-mix(in oklab, oklch(var(--p)) 10%, oklch(var(--b1)) 90%), transparent 55%),
      radial-gradient(36rem 20rem at 110% 0%, color-mix(in oklab, oklch(var(--a)) 10%, oklch(var(--b1)) 90%), transparent 60%),
      linear-gradient(180deg, color-mix(in oklab, oklch(var(--b2)) 90%, transparent 10%), oklch(var(--b1)));
  }
  /* Kolom dengan nada warna (tone) yang elegan */
  .board-col{ border-radius: 0.9rem; border:1px solid color-mix(in oklab, var(--tone, oklch(var(--b3))) 30%, oklch(var(--b3)) 70%); }
  .board-col-header{ font-weight: 800; letter-spacing: .2px; }
  .board-col-body{ min-height: 64vh; padding-top: 1rem; }
  .tone-primary   { --tone: oklch(var(--p)); }
  .tone-secondary { --tone: oklch(var(--s)); }
  .tone-accent    { --tone: oklch(var(--a)); }
  .tone-info      { --tone: oklch(var(--in)); }
  .tone-success   { --tone: oklch(var(--su)); }
  .tone-warning   { --tone: oklch(var(--wa)); }
  .tone-error     { --tone: oklch(var(--er)); }
  .board-col{
    background: linear-gradient(180deg,
      color-mix(in oklab, var(--tone) 8%, oklch(var(--b1)) 92%),
      color-mix(in oklab, var(--tone) 4%, oklch(var(--b2)) 96%)
    );
  }

  /* Drag-n-drop states */
  .kanban-column.is-over{ outline:2px dashed color-mix(in oklab, var(--tone, oklch(var(--p))) 45%, transparent); outline-offset:4px; }
  .kanban-column .sortable-ghost{ opacity:.65; }
  .kanban-column .sortable-chosen{ transform:scale(1.02); transition:transform .15s ease; }

  /* Kartu tugas: stripe warna + bayangan lembut */
  .task-card{ border-radius:14px; box-shadow:0 1px 1px rgba(0,0,0,.04), 0 10px 26px -14px rgba(0,0,0,.22); }
  .task-card:hover{ box-shadow:0 1px 1px rgba(0,0,0,.06), 0 14px 32px -14px rgba(0,0,0,.28); }
  .task-bar-primary   { border-top:4px solid var(--p); }
  .task-bar-secondary { border-top:4px solid var(--s); }
  .task-bar-accent    { border-top:4px solid var(--a); }
  .task-bar-info      { border-top:4px solid oklch(var(--in)); }
  .task-bar-success   { border-top:4px solid oklch(var(--su)); }
  .task-bar-warning   { border-top:4px solid oklch(var(--wa)); }
  .task-bar-error     { border-top:4px solid oklch(var(--er)); }
  .prio-pill{ font-weight:700; letter-spacing:.2px; }

  /* Highlight setelah update status */
  .flash-ring{ box-shadow:0 0 0 3px color-mix(in oklab, oklch(var(--su)) 55%, transparent); transition: box-shadow .8s ease; }
</style>

@php
  use Illuminate\Support\Facades\View;

  $statusTitles = ['open'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'];
  // Nada warna per kolom agar papan terlihat “berkapur” elegan
  $statusTones  = ['open'=>'tone-info','in_progress'=>'tone-primary','review'=>'tone-warning','done'=>'tone-success'];

  // Palet otomatis untuk variasi kartu (mengikuti DaisyUI)
  $autoPalette  = ['primary','secondary','accent','success','warning','info','error'];
@endphp

<div class="container mx-auto py-6 board-surface">

  {{-- Header & CTA --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-extrabold text-base-content">Papan Tugas (Kanban)</h1>
      <p class="mt-1 text-base-content/70">Kelola alur kerja tim Anda secara visual.</p>
    </div>
    <button type="button" class="btn btn-success text-success-content shadow" onclick="document.getElementById('create_task_modal').showModal()">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
      Buat Tugas Baru
    </button>
  </div>

  {{-- Filters --}}
  <div class="card bg-base-100 shadow-md border border-base-300/60">
    <form action="{{ route('tasks.index') }}" method="GET" class="card-body p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-[1fr_1fr_auto] gap-3">
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
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
    </form>
  </div>

  {{-- Papan Kanban --}}
  <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    @foreach ($statusTitles as $status => $title)
      <div class="board-col {{ $statusTones[$status] }} bg-base-200/60 border">
        <div class="p-4">
          <h4 class="board-col-header text-lg text-base-content flex items-center justify-between">
            <span>{{ $title }}</span>
            <span class="badge badge-ghost">{{ $tasks[$status]->count() }}</span>
          </h4>

          <div id="{{ $status }}" class="kanban-column board-col-body space-y-4" data-status="{{ $status }}">
            @foreach ($tasks[$status] as $task)
              @php
                // Tentukan warna kartu: pakai yang tersimpan; jika kosong, gunakan palet otomatis deterministik
                $auto = $autoPalette[ ($loop->index + ($task->id ?? 0)) % count($autoPalette) ];
                $taskColor = $task->color ?? $auto;
              @endphp

              @if(View::exists('tasks.partials.card'))
                @include('tasks.partials.card', ['task' => $task, 'taskColor' => $taskColor])
              @else
                {{-- Fallback inline agar kartu tetap tampil --}}
                @php
                  $bar = [
                    'primary'=>'task-bar-primary','secondary'=>'task-bar-secondary','accent'=>'task-bar-accent',
                    'info'=>'task-bar-info','success'=>'task-bar-success','warning'=>'task-bar-warning','error'=>'task-bar-error',
                  ][$taskColor] ?? 'task-bar-primary';
                  $prio = match($task->priority){
                    'low'=>'badge-info','medium'=>'badge-success','high'=>'badge-warning','urgent'=>'badge-error', default=>'badge-ghost'
                  };
                @endphp
                <div id="task-{{ $task->id }}" class="card task-card bg-base-100 {{ $bar }} cursor-grab active:cursor-grabbing">
                  <div class="card-body p-4">
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <p class="font-semibold text-base-content leading-tight line-clamp-2">{{ $task->title }}</p>
                        @if($task->description)
                          <p class="text-sm text-base-content/70 mt-1 line-clamp-3">{{ $task->description }}</p>
                        @endif
                      </div>
                      <span class="badge {{ $prio }} prio-pill badge-sm">{{ ucfirst($task->priority) }}</span>
                    </div>
                    <div class="card-actions justify-between items-center mt-4">
                      <div class="flex items-center gap-2 text-sm text-base-content/70">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span @if($task->due_date && $task->due_date->isPast() && $task->status!=='done') class="text-error font-semibold" @endif>
                          {{ $task->due_date?->format('d M Y') ?? 'Tanpa tenggat' }}
                        </span>
                      </div>
                      <div class="flex items-center gap-1">
                        @if($task->link)
                          <a href="{{ $task->link }}" target="_blank" class="btn btn-ghost btn-xs btn-circle" data-tip="Buka Link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                          </a>
                        @endif
                        @if($task->assignee)
                          <div class="tooltip" data-tip="{{ $task->assignee->name }}">
                            <div class="avatar"><div class="w-8 rounded-full ring ring-base-100">
                              <img src="https://ui-avatars.com/api/?name={{ urlencode($task->assignee->name) }}&background=random" />
                            </div></div>
                          </div>
                        @endif
                        <div class="dropdown dropdown-end">
                          <label tabindex="0" class="btn btn-ghost btn-xs btn-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                          </label>
                          <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-48 z-10 border border-base-300/50">
                            <li><a onclick="TaskView.openEditModal({{ $task->id }})">Edit Tugas</a></li>
                            <div class="divider my-1"></div>
                            <li><a onclick="TaskView.promptMoveStatus({{ $task->id }}, 'open')">Pindah ke To Do</a></li>
                            <li><a onclick="TaskView.promptMoveStatus({{ $task->id }}, 'in_progress')">Pindah ke In Progress</a></li>
                            <li><a onclick="TaskView.promptMoveStatus({{ $task->id }}, 'review')">Pindah ke Review</a></li>
                            <li><a onclick="TaskView.promptMoveStatus({{ $task->id }}, 'done')">Pindah ke Done</a></li>
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
              @endif
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>

{{-- Modal Buat Tugas --}}
@php $colorOptions=['primary','secondary','accent','info','success','warning','error']; @endphp
<dialog id="create_task_modal" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
    <h3 class="font-bold text-lg text-base-content">Buat Tugas Baru</h3>
    <form action="{{ route('tasks.store') }}" method="POST" class="mt-4 space-y-4">
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
      <div>
        <label class="label"><span class="label-text">Warna Kartu</span></label>
        <div class="flex flex-wrap gap-2">
          @foreach($colorOptions as $c)
          <label class="label cursor-pointer justify-start gap-2">
            <input type="radio" name="color" class="radio radio-{{$c}}" value="{{$c}}" @checked($loop->first) />
            <span class="label-text capitalize">{{$c}}</span>
          </label>
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
          <option value="review">Review</option>
          <option value="done">Done</option>
        </select>
      </div>
      <div>
        <label class="label"><span class="label-text">Warna Kartu</span></label>
        <div id="edit_color_options" class="flex flex-wrap gap-2">
          @foreach($colorOptions as $c)
            <label class="label cursor-pointer justify-start gap-2">
              <input type="radio" name="color" class="radio radio-{{$c}}" value="{{$c}}" />
              <span class="label-text capitalize">{{$c}}</span>
            </label>
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

{{-- ===== JS Kanban (inline) ===== --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"
        integrity="sha256-+Y2l1ZmcZ+1M2Kc9d0uR9sL2oW2i9i4S2y7P3g4e3RY=" crossorigin="anonymous"></script>
<script>
(function(){
  function csrf(){ return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' }
  function addOver(col,on){ col.classList.toggle('is-over', !!on) }
  function flash(el){ el?.classList.add('flash-ring'); setTimeout(()=>el?.classList.remove('flash-ring'),700) }

  function initDnD(){
    if(!window.Sortable){ return setTimeout(initDnD,60); }
    document.querySelectorAll('.kanban-column').forEach(col=>{
      new Sortable(col,{
        group:'tasks', animation:150, ghostClass:'bg-primary/15',
        onChoose:()=>addOver(col,true), onUnchoose:()=>addOver(col,false), onAdd:()=>addOver(col,false),
        onEnd:(evt)=>{
          const el=evt.item, to=evt.to;
          const id=(el.id||'').replace('task-','');
          const status=to?.dataset?.status||'open';
          if(!id) return;
          promptAndUpdateStatus(id,status,el);
        }
      })
    })
  }

  function promptMoveStatus(id,status){
    const el=document.getElementById('task-'+id);
    const col=document.getElementById(status);
    if(col && el) col.appendChild(el);
    promptAndUpdateStatus(id,status,el);
  }

  function promptAndUpdateStatus(id,status,el){
    let notify_wa=0;
    if(['in_progress','review','done'].includes(status)){
      if(confirm('Kirim notifikasi WA ke PIC untuk perubahan status ini?')) notify_wa=1;
    }
    updateTaskStatus(id,status,el,notify_wa);
  }

  async function updateTaskStatus(id,status,el,notify_wa=0){
    try{
      const r=await fetch(`/tasks/${encodeURIComponent(id)}/update-status`,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},
        body:JSON.stringify({status,notify_wa})
      });
      if(!r.ok) throw new Error('HTTP '+r.status);
      flash(el);
    }catch(e){ console.error('Update status gagal:',e); }
  }

  async function openEditModal(id){
    try{
      const r=await fetch(`/tasks/${encodeURIComponent(id)}/edit`,{headers:{'Accept':'application/json'}});
      if(!r.ok) throw new Error('HTTP '+r.status);
      const payload=await r.json(); const task=payload.task||payload;

      const f=document.getElementById('edit_task_form');
      f.action=`/tasks/${encodeURIComponent(id)}`;
      document.getElementById('edit_task_id').value=task.id ?? id;
      document.getElementById('edit_title').value=task.title||'';
      document.getElementById('edit_description').value=task.description||'';
      document.getElementById('edit_assignee_id').value=task.assignee_id ?? '';
      document.getElementById('edit_priority').value=task.priority||'medium';
      document.getElementById('edit_status').value=task.status||'open';
      document.getElementById('edit_due_date').value=task.due_date ? new Date(task.due_date).toISOString().slice(0,10) : '';
      document.getElementById('edit_link').value=task.link || '';

      const radios=document.querySelectorAll('#edit_color_options input[name="color"]');
      let found=false; radios.forEach(r=>{ r.checked=(r.value===task.color); if(r.checked) found=true; });
      if(!found && radios[0]) radios[0].checked=true;

      document.getElementById('edit_task_modal').showModal();
    }catch(e){ console.error('Gagal load data edit:',e); alert('Tidak dapat memuat data tugas. Silakan coba lagi.'); }
  }

  window.TaskView={ openEditModal, promptMoveStatus };
  document.addEventListener('DOMContentLoaded', initDnD, { once:true });
})();
</script>
@endsection
