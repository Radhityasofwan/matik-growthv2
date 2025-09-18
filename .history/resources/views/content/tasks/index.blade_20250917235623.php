@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@push('styles')
<style>
  /* ===== Kanban look & feel (khusus halaman ini saja) ===== */
  .kanban-wrap{
    background-image:
      radial-gradient(24rem 24rem at -6rem -4rem, rgba(59,130,246,.08), transparent 60%),
      radial-gradient(20rem 20rem at 110% 10%, rgba(236,72,153,.08), transparent 60%),
      radial-gradient(28rem 28rem at 30% 120%, rgba(16,185,129,.08), transparent 60%);
  }
  .kanban-column{ min-height:64vh; }
  .kanban-column.is-over{ outline:2px dashed rgba(99,102,241,.45); outline-offset:4px; }
  .kanban-column .sortable-ghost{ opacity:.65; }
  .kanban-column .sortable-chosen{ transform:scale(1.02); transition:transform .15s ease; }

  .task-card{ border-radius:14px; box-shadow:0 1px 1px rgba(0,0,0,.04), 0 6px 24px -10px rgba(0,0,0,.12); }
  .task-card:hover{ box-shadow:0 1px 1px rgba(0,0,0,.06), 0 12px 28px -12px rgba(0,0,0,.25); }

  .task-bar-primary{ border-top:4px solid var(--p); }
  .task-bar-secondary{ border-top:4px solid var(--s); }
  .task-bar-accent{ border-top:4px solid var(--a); }
  .task-bar-info{ border-top:4px solid oklch(var(--in)); }
  .task-bar-success{ border-top:4px solid oklch(var(--su)); }
  .task-bar-warning{ border-top:4px solid oklch(var(--wa)); }
  .task-bar-error{ border-top:4px solid oklch(var(--er)); }

  .prio-pill{ font-weight:700; letter-spacing:.2px; }
  .flash-ring{ box-shadow:0 0 0 3px color-mix(in oklab, oklch(var(--su)) 55%, transparent); transition:box-shadow .8s ease; }
</style>
@endpush

@section('content')
<div class="container mx-auto py-6 kanban-wrap rounded-2xl">

  {{-- Alerts --}}
  @if (session('success'))
  <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down">
    <div>
      <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  </div>
  @endif

  @if ($errors->any())
  <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
    <div>
      <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span><strong>Terdapat kesalahan!</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></span>
    </div>
  </div>
  @endif

  {{-- Header & CTA --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6" data-aos="fade-down">
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
  <div class="card bg-base-100 shadow-md border border-base-300/60" data-aos="fade-up">
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
    @foreach (['open'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $status => $title)
    <div class="card bg-base-200/60" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
      <div class="card-body p-4">
        <h4 class="font-bold text-lg text-base-content flex items-center justify-between tracking-wide">
          <span>{{ $title }}</span>
          <span class="badge badge-ghost">{{ $tasks[$status]->count() }}</span>
        </h4>

        <div id="{{ $status }}" class="kanban-column pt-4 space-y-4" data-status="{{ $status }}">
          @foreach ($tasks[$status] as $task)
            {{-- gunakan includeIf agar tidak melempar error jika cache view belum bersih --}}
            @includeIf('tasks.partials.card', ['task' => $task])
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
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"
          integrity="sha256-+Y2l1ZmcZ+1M2Kc9d0uR9sL2oW2i9i4S2y7P3g4e3RY=" crossorigin="anonymous"></script>
  <script>
    (function(){
      function csrf(){ return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' }
      function addOverState(col,on){ col.classList.toggle('is-over',!!on) }
      function flash(el){ el?.classList.add('flash-ring'); setTimeout(()=>el?.classList.remove('flash-ring'),700) }

      function ensureSortable(){
        if(!window.Sortable) return setTimeout(ensureSortable,60);
        document.querySelectorAll('.kanban-column').forEach(col=>{
          new Sortable(col,{
            group:'tasks', animation:150, ghostClass:'bg-primary/15',
            onChoose:()=>addOverState(col,true), onUnchoose:()=>addOverState(col,false), onAdd:()=>addOverState(col,false),
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
          document.getElementById('edit_due_date').value=task.due_date ? new Date(task.due_date).toISOString().slice(0,10):'';
          document.getElementById('edit_link').value=task.link||'';

          const radios=document.querySelectorAll('#edit_color_options input[name="color"]');
          let found=false; radios.forEach(r=>{ r.checked = (r.value===task.color); if(r.checked) found=true; });
          if(!found && radios[0]) radios[0].checked=true;

          document.getElementById('edit_task_modal').showModal();
        }catch(e){ console.error('Gagal load data edit:',e); alert('Tidak dapat memuat data tugas. Silakan coba lagi.'); }
      }

      window.TaskView={openEditModal,promptMoveStatus};
      document.addEventListener('DOMContentLoaded', ensureSortable, {once:true});
    })();
  </script>
@endpush
