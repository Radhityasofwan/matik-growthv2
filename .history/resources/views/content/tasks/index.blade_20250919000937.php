{{-- resources/views/content/tasks/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Task Board')

@push('styles')
<style>
  .kanban-column { overflow: visible; isolation: isolate; }
  .floating-menu { position: fixed; z-index: 70; min-width: 11rem; }
</style>
@endpush

@section('content')
<div x-data="taskBoard()" class="container mx-auto py-6">

  {{-- Alerts --}}
  @if (session('success'))
  <div class="alert alert-success shadow mb-4">
    <span>{{ session('success') }}</span>
  </div>
  @endif
  @if ($errors->any())
  <div class="alert alert-error shadow mb-4">
    <div>
      <strong>Terdapat kesalahan!</strong>
      <ul class="list-disc ml-5">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  </div>
  @endif

  {{-- Header --}}
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <h1 class="text-3xl font-bold text-base-content">Task Board</h1>
      <p class="text-base-content/70 mt-1">Kelola tugas dengan cepat melalui papan kanban.</p>
    </div>
    <div class="flex items-center gap-2">
      <label class="input input-bordered flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4a6 6 0 014.472 9.928l4.3 4.3a1 1 0 01-1.414 1.414l-4.3-4.3A6 6 0 1110 4zm0 2a4 4 0 100 8 4 4 0 000-8z"/></svg>
        <input x-model="filters.q" @input.debounce.250ms="applyFilters" type="text" class="grow" placeholder="Cari judul/owner..." />
      </label>
      <a href="#create_task_modal" class="btn btn-primary gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Tambah Tugas
      </a>
    </div>
  </div>

  {{-- Kanban --}}
  @php
    $tasks = $tasks ?? ['open'=>collect(), 'in_progress'=>collect(), 'review'=>collect(), 'done'=>collect()];
    $columns = [
      ['key'=>'open', 'label'=>'To Do'],
      ['key'=>'in_progress', 'label'=>'In Progress'],
      ['key'=>'review', 'label'=>'Review'],
      ['key'=>'done', 'label'=>'Done'],
    ];
    $prioColors = ['low'=>'badge-ghost','medium'=>'badge-info','high'=>'badge-warning','urgent'=>'badge-error'];
  @endphp

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mt-6">
   @foreach ($columns as $col)
@php
  $key = $col['key'];
@endphp
<div class="kanban-column card bg-base-100 border border-base-300/50 shadow-lg">
  <div class="card-body p-3">
    <div class="flex items-center justify-between px-1">
      <h3 class="font-semibold text-base-content">{{ $col['label'] }}</h3>
      <span class="badge badge-ghost">
        <span x-text="countIn('{{ $key }}')">{{ $tasks[$key]->count() }}</span> item
      </span>
    </div>

    <div class="divider my-2"></div>

    <div
      class="space-y-3 min-h-[120px]"
      :id="ids.col('{{ $key }}')"
      x-init="$nextTick(() => initSortable('{{ $key }}'))"
    >
      @forelse ($tasks[$key] as $t)
        @php
  $ownersArr = $t->owners->map(function ($u) {
      return ['id' => $u->id, 'name' => $u->name, 'wa_number' => $u->wa_number];
  })->values()->all();

  $taskPayload = [
      'id'       => $t->id,
      'title'    => $t->title,
      'priority' => $t->priority,
      'status'   => $t->status,
      'due_date' => optional($t->due_date)->format('Y-m-d'),
      'owners'   => $ownersArr,
      'creator'  => optional($t->creator)->name,
      'link'     => $t->link,
  ];

  // aman untuk atribut HTML
  $taskJson = e(json_encode(
      $taskPayload,
      JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
  ));
@endphp
        <div
  :id="ids.card({{ $t->id }})"
  class="card bg-base-100 border border-base-300/50 shadow-sm hover:shadow-md transition-all"
  data-task="{{ $taskJson }}"
            "id" => $t->id,
            "title" => $t->title,
            "priority" => $t->priority,
            "status" => $t->status,
            "due_date" => optional($t->due_date)->format("Y-m-d"),
            "owners" => $ownersArr,
            "creator" => optional($t->creator)->name,
            "link" => $t->link,
          ])'
        >
          <div class="card-body p-3">
            <div class="flex items-start justify-between gap-2">
              <a href="#" class="font-medium leading-tight hover:underline" @click.prevent="openEdit({{ $t->id }})">
                {{ $t->title }}
              </a>

              {{-- ACTION DROPDOWN --}}
              <div class="relative" x-data="floatMenu()" @keydown.escape.window="close()">
                <button type="button" class="btn btn-ghost btn-xs" @click.stop="toggle($el)" @mousedown.stop>
                  opsi
                </button>
                <template x-teleport="body">
                  <ul
                    x-show="open"
                    x-transition.opacity
                    class="menu menu-sm p-2 shadow bg-base-100 rounded-box border border-base-300/50 floating-menu"
                    :style="style"
                    @click.outside="close"
                    @mousedown.outside="close"
                  >
                    <li><a @click.prevent="openEdit({{ $t->id }}); close()">Edit</a></li>
                    <li>
                      <a>Pindah Ke…</a>
                      <ul class="bg-base-100">
                        @foreach ($columns as $c2)
                          @if ($c2['key'] !== $t->status)
                            <li><a @click.prevent="moveTo({{ $t->id }}, '{{ $c2['key'] }}', true); close()">→ {{ $c2['label'] }}</a></li>
                          @endif
                        @endforeach
                      </ul>
                    </li>
                    <li>
                      <form method="POST" action="{{ route('tasks.destroy', $t) }}" onsubmit="return confirm('Hapus tugas ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-error">Hapus</button>
                      </form>
                    </li>
                  </ul>
                </template>
              </div>
            </div>

            <div class="mt-1 flex items-center gap-2 text-xs">
              @php $b = $prioColors[$t->priority] ?? 'badge-ghost'; @endphp
              <span class="badge {{ $b }}">{{ strtoupper($t->priority) }}</span>
              <span class="opacity-60">Due: {{ $t->due_date?->format('d M Y') ?: '-' }}</span>
            </div>

            <div class="mt-2 flex items-center justify-between">
              <div class="avatar-group -space-x-4">
                @forelse ($t->owners as $owner)
                  <div class="avatar tooltip" data-tip="{{ $owner->name }}">
                    <div class="w-7 h-7 rounded-full ring ring-base-200 ring-offset-base-100">
                      <img src="https://api.dicebear.com/8.x/initials/svg?seed={{ urlencode($owner->name) }}" />
                    </div>
                  </div>
                @empty
                  <div class="text-xs opacity-60">No PIC</div>
                @endforelse
              </div>
              <a href="#" class="btn btn-ghost btn-xs" @click.prevent="openEdit({{ $t->id }})">Detail</a>
            </div>
          </div>
        </div>
      @empty
        <div class="text-center text-sm opacity-60 py-4">Belum ada tugas.</div>
      @endforelse
    </div>
  </div>
</div>
@endforeach
  </div>

  {{-- ======= CREATE MODAL ======= --}}
  <div id="create_task_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl">
      <form action="{{ route('tasks.store') }}" method="POST">
        @csrf
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Tambah Tugas</h3>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Judul</span></label>
            <input type="text" name="title" class="input input-bordered w-full" required>
          </div>
          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Deskripsi</span></label>
            <textarea name="description" class="textarea textarea-bordered w-full" rows="3"></textarea>
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
            <label class="label"><span class="label-text">Status</span></label>
            <select name="status" class="select select-bordered w-full">
              <option value="open" selected>To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="review">Review</option>
              <option value="done">Done</option>
            </select>
          </div>

          <div>
            <label class="label"><span class="label-text">Mulai</span></label>
            <input type="datetime-local" name="start_at" class="input input-bordered w-full">
          </div>
          <div>
            <label class="label"><span class="label-text">Due Date</span></label>
            <input type="date" name="due_date" class="input input-bordered w-full">
          </div>

          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Link (opsional)</span></label>
            <input type="url" name="link" class="input input-bordered w-full" placeholder="https://...">
          </div>

          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Pilih PIC/Owner</span></label>
            <select name="owner_ids[]" class="select select-bordered w-full" multiple size="6">
              @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
              @endforeach
            </select>
            <p class="text-xs opacity-60 mt-1">Tahan Ctrl / Cmd untuk memilih lebih dari satu.</p>
          </div>
        </div>

        <div class="modal-action mt-6">
          <a href="#" class="btn btn-ghost">Batal</a>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
  </div>

  {{-- ======= EDIT MODAL ======= --}}
  <div id="edit_task_modal" class="modal modal-bottom sm:modal-middle" x-cloak>
    <div class="modal-box w-11/12 max-w-2xl" x-show="edit.open" x-transition>
      <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click.prevent="closeEdit()">✕</a>
      <h3 class="font-bold text-lg">Edit Tugas</h3>

      <form :action="edit.action" method="POST">
        @csrf @method('PATCH')
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Judul</span></label>
            <input type="text" name="title" class="input input-bordered w-full" x-model="edit.form.title" required>
          </div>
          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Deskripsi</span></label>
            <textarea name="description" class="textarea textarea-bordered w-full" rows="3" x-model="edit.form.description"></textarea>
          </div>

          <div>
            <label class="label"><span class="label-text">Prioritas</span></label>
            <select name="priority" class="select select-bordered w-full" x-model="edit.form.priority" required>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>

          <div>
            <label class="label"><span class="label-text">Status</span></label>
            <select name="status" class="select select-bordered w-full" x-model="edit.form.status" required>
              <option value="open">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="review">Review</option>
              <option value="done">Done</option>
            </select>
          </div>

          <div>
            <label class="label"><span class="label-text">Mulai</span></label>
            <input type="datetime-local" name="start_at" class="input input-bordered w-full" x-model="edit.form.start_at">
          </div>
          <div>
            <label class="label"><span class="label-text">Due Date</span></label>
            <input type="date" name="due_date" class="input input-bordered w-full" x-model="edit.form.due_date">
          </div>

          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Link</span></label>
            <input type="url" name="link" class="input input-bordered w-full" x-model="edit.form.link">
          </div>

          <div class="md:col-span-2">
            <label class="label"><span class="label-text">PIC/Owner</span></label>
            <select name="owner_ids[]" class="select select-bordered w-full" x-ref="ownerSelect" multiple size="6">
              @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
              @endforeach
            </select>
            <p class="text-xs opacity-60 mt-1">Tahan Ctrl / Cmd untuk memilih lebih dari satu.</p>
          </div>

          {{-- Konfirmasi WA untuk update --}}
          <div class="md:col-span-2">
            <label class="label cursor-pointer justify-start gap-3">
              <input type="checkbox" class="toggle toggle-success" name="notify_wa" x-model="edit.form.notify_wa">
              <span class="label-text">Kirim notifikasi WhatsApp ke semua PIC/Owner setelah disimpan</span>
            </label>
          </div>
        </div>

        <div class="modal-action mt-6">
          <a href="#" class="btn btn-ghost" @click.prevent="closeEdit()">Batal</a>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
    <a href="#" class="modal-backdrop" @click.prevent="closeEdit()">Close</a>
  </div>

  {{-- Confirm WA setelah drag-n-drop --}}
  <div id="confirm_wa_modal" class="modal modal-bottom sm:modal-middle" x-cloak>
    <div class="modal-box">
      <h3 class="font-bold text-lg">Kirim Notifikasi WhatsApp?</h3>
      <p class="py-2 text-sm opacity-80">
        Tugas dipindahkan ke kolom <b x-text="confirmWA.toLabel"></b>. Kirim pemberitahuan WA ke semua PIC/Owner?
      </p>
      <div class="modal-action">
        <a href="#" class="btn" @click.prevent="cancelConfirmWA()">Jangan</a>
        <a href="#" class="btn btn-success" @click.prevent="proceedConfirmWA(true)">Kirim</a>
      </div>
    </div>
    <a href="#" class="modal-backdrop" @click.prevent="cancelConfirmWA()">Close</a>
  </div>

</div>
@endsection

@push('scripts')
<script>
  // Fallback jika Sortable belum tersedia via Vite bundling
  (function ensureSortable() {
    if (!window.Sortable) {
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
      s.onload = () => console.info('Sortable (CDN) loaded');
      document.head.appendChild(s);
    }
  })();
</script>

<script>
function taskBoard() {
  return {
    // ===== State =====
    filters: { q: '' },
    edit: {
      open: false,
      id: null,
      action: '',
      form: {
        title: '', description: '', priority: 'medium', status: 'open',
        start_at: '', due_date: '', link: '',
        owner_ids: [], notify_wa: false,
      }
    },
    confirmWA: { open: false, taskId: null, to: '', toLabel: '' },

    // ===== Helpers =====
    csrf() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    },
    routes: {
      edit: (id) => `{{ url('/tasks') }}/${id}/edit`,
      updateStatus: (id) => `{{ url('/tasks') }}/${id}/status`,
      update: (id) => `{{ url('/tasks') }}/${id}`,
    },
    ids: {
      col: (key) => `col-${key}`,
      card: (id) => `task-card-${id}`,
    },
    columnLabel(key) {
      return ({ open: 'To Do', in_progress: 'In Progress', review: 'Review', done: 'Done' })[key] || key
    },
    countIn(key) {
      const el = document.getElementById(this.ids.col(key))
      return el ? el.querySelectorAll('.card[data-task]').length : 0
    },

    // ===== Sortable =====
    sortableInitDone: {},
    initSortable(key) {
      const colEl = document.getElementById(this.ids.col(key))
      if (!colEl || this.sortableInitDone[key]) return
      this.sortableInitDone[key] = true

      const self = this
      const makeSortable = () => {
        if (!window.Sortable) return setTimeout(makeSortable, 50)
        new Sortable(colEl, {
          group: 'board',
          animation: 180,
          ghostClass: 'opacity-50',
          handle: '.card',
          onAdd(evt) { self.handleDrop(evt, key) },
        })
      }
      makeSortable()
    },
    handleDrop(evt, toKey) {
      const card = evt.item;
      const data = this.readTaskData(card);
      if (!data) return;

      this.confirmWA.open   = true
      this.confirmWA.taskId = data.id
      this.confirmWA.to     = toKey
      this.confirmWA.toLabel= this.columnLabel(toKey)

      location.hash = '#confirm_wa_modal'
    },
    cancelConfirmWA() {
      this.confirmWA.open = false
      location.hash = '#'
    },
    async proceedConfirmWA(sendWA) {
      const { taskId, to } = this.confirmWA
      this.confirmWA.open = false
      location.hash = '#'

      try {
        const res = await fetch(this.routes.updateStatus(taskId), {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.csrf()
          },
          body: JSON.stringify({ status: to, notify_wa: !!sendWA })
        })
        const json = await res.json().catch(()=>({}))
        if (!res.ok || json?.success === false) {
          throw new Error(json?.message || 'Gagal mengubah status.')
        }
      } catch (e) {
        alert(e.message || 'Status gagal diupdate, mengembalikan posisi.')
        location.reload()
      }
    },

    readTaskData(cardEl) {
      try {
        const raw = cardEl?.getAttribute('data-task') || '{}'
        return JSON.parse(raw)
      } catch { return null }
    },

    // ===== Move via menu =====
    moveTo(id, to, askWA) {
      if (askWA) {
        this.confirmWA.open   = true
        this.confirmWA.taskId = id
        this.confirmWA.to     = to
        this.confirmWA.toLabel= this.columnLabel(to)
        location.hash = '#confirm_wa_modal'
        return
      }
      this.proceedMoveTo(id, to, false)
    },
    async proceedMoveTo(id, to, sendWA) {
      try {
        const res = await fetch(this.routes.updateStatus(id), {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.csrf()
          },
          body: JSON.stringify({ status: to, notify_wa: !!sendWA })
        })
        if (!res.ok) throw new Error('Gagal update status.')
        const card = document.getElementById(this.ids.card(id))
        const col  = document.getElementById(this.ids.col(to))
        if (card && col) col.prepend(card)
      } catch (e) {
        alert(e.message || 'Gagal memindahkan tugas.')
      }
    },

    // ===== Edit Modal =====
    async openEdit(id) {
      try {
        const res = await fetch(this.routes.edit(id), { headers: { 'Accept': 'application/json' } })
        const json = await res.json()
        const t = json.task || {}
        this.edit.id = id
        this.edit.action = this.routes.update(id)
        this.edit.form = {
          title: t.title || '',
          description: t.description || '',
          priority: t.priority || 'medium',
          status: t.status || 'open',
          start_at: t.start_at || '',
          due_date: t.due_date || '',
          link: t.link || '',
          owner_ids: Array.from(t.owner_ids || []),
          notify_wa: false
        }
        this.$nextTick(() => this.prefillOwnerSelect(this.edit.form.owner_ids))
        this.edit.open = true
        location.hash = '#edit_task_modal'
      } catch (e) {
        alert('Gagal memuat data tugas.')
      }
    },
    closeEdit() {
      this.edit.open = false
      location.hash = '#'
    },
    prefillOwnerSelect(ids) {
      const sel = this.$refs.ownerSelect
      if (!sel) return
      const set = new Set((ids || []).map(x => String(x)))
      Array.from(sel.options).forEach(o => o.selected = set.has(String(o.value)))
    },

    // ===== Filter client-side ringan =====
    applyFilters() {
      const q = (this.filters.q || '').toLowerCase().trim()
      document.querySelectorAll('.card[data-task]').forEach(card => {
        try {
          const data = JSON.parse(card.getAttribute('data-task') || '{}')
          const owners = (data.owners || []).map(o => (o.name || '').toLowerCase())
          const str = [data.title || '', ...owners].join(' ').toLowerCase()
          card.style.display = q === '' || str.includes(q) ? '' : 'none'
        } catch { card.style.display = '' }
      })
    }
  }
}

/**
 * Dropdown mengambang:
 * - Teleport ke body
 * - Reposition saat scroll/resize
 * - Close on click outside & Escape
 * - Satu menu aktif pada satu waktu
 */
function floatMenu() {
  return {
    open: false,
    style: '',
    anchor: null,
    onScroll: null,
    onResize: null,
    toggle(btn) {
      if (this.open) { this.close(); return; }
      document.dispatchEvent(new CustomEvent('close-all')); // tutup menu lain
      this.anchor = btn;
      this.place();
      this.bind();
      this.open = true;
    },
    close() {
      this.open = false;
      this.unbind();
      this.style = '';
      this.anchor = null;
    },
    place() {
      if (!this.anchor) return;
      const rect = this.anchor.getBoundingClientRect();
      const top  = rect.bottom + 6 + window.scrollY;
      const left = Math.max(8 + window.scrollX, rect.right - 180 + window.scrollX);
      this.style = `top:${top}px;left:${left}px`;
    },
    bind() {
      this.onScroll = () => { if (this.open) this.place(); }
      this.onResize = () => { if (this.open) this.place(); }
      window.addEventListener('scroll', this.onScroll, true);
      window.addEventListener('resize', this.onResize, { passive: true });
      document.addEventListener('close-all', () => { if (this.open) this.close(); }, { once: true });
    },
    unbind() {
      if (this.onScroll) window.removeEventListener('scroll', this.onScroll, true);
      if (this.onResize) window.removeEventListener('resize', this.onResize);
      this.onScroll = null; this.onResize = null;
    }
  }
}
</script>
@endpush
