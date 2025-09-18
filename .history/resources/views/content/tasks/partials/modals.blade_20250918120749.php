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

      <div class="form-control">
          <label class="label"><span class="label-text">Warna Kartu</span></label>
          <input type="text" name="color" value="primary"
                 class="input input-bordered w-full" placeholder="#3b82f6 atau primary" />
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

       <div class="form-control">
          <label class="label"><span class="label-text">Warna Kartu</span></label>
          <input type="text" id="edit_color_text" name="color"
                 class="input input-bordered w-full" placeholder="#3b82f6 atau primary" />
      </div>

      <div class="form-control">
        <label class="label"><span class="label-text">Status</span></label>
        <select id="edit_status" name="status" class="select select-bordered w-full" required>
          <option value="open">Not Started</option>
          <option value="in_progress">Doing</option>
          <option value="review">Paused</option>
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
