<!-- Create Task Modal -->
<div id="create_task_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Buat Tugas Baru</h3>
            <div class="mt-4 space-y-4">
                <div><label class="label"><span class="label-text">Judul Tugas</span></label><input type="text" name="title" placeholder="Nama tugas" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Deskripsi</span></label><textarea name="description" class="textarea textarea-bordered w-full" placeholder="Deskripsi singkat"></textarea></div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><label class="label"><span class="label-text">PIC (Assignee)</span></label><select name="assignee_id" class="select select-bordered w-full"><option value="">Pilih User</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                    <div><label class="label"><span class="label-text">Prioritas</span></label><select name="priority" class="select select-bordered w-full" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                    <div><label class="label"><span class="label-text">Tenggat Waktu</span></label><input type="date" name="due_date" class="input input-bordered w-full" /></div>
                </div>
            </div>
            <div class="modal-action mt-6"><a href="#" class="btn btn-ghost">Batal</a><button type="submit" class="btn btn-primary">Simpan Tugas</button></div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

<!-- Edit Task Modal -->
<div id="edit_task_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form id="edit_task_form" action="" method="POST">
            @csrf @method('PATCH')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Edit Tugas</h3>
            <div class="mt-4 space-y-4">
                <input type="hidden" id="edit_task_id" name="id">
                <div><label class="label"><span class="label-text">Judul Tugas</span></label><input type="text" id="edit_title" name="title" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Deskripsi</span></label><textarea id="edit_description" name="description" class="textarea textarea-bordered w-full"></textarea></div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><label class="label"><span class="label-text">PIC (Assignee)</span></label><select id="edit_assignee_id" name="assignee_id" class="select select-bordered w-full"><option value="">Pilih User</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                    <div><label class="label"><span class="label-text">Prioritas</span></label><select id="edit_priority" name="priority" class="select select-bordered w-full" required><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                    <div><label class="label"><span class="label-text">Tenggat Waktu</span></label><input type="date" id="edit_due_date" name="due_date" class="input input-bordered w-full" /></div>
                </div>
                <div><label class="label"><span class="label-text">Status</span></label><select id="edit_status" name="status" class="select select-bordered w-full" required><option value="open">Open</option><option value="in_progress">In Progress</option><option value="done">Done</option></select></div>
            </div>
            <div class="modal-action mt-6"><a href="#" class="btn btn-ghost">Batal</a><button type="submit" class="btn btn-primary">Update Tugas</button></div>
        </form>
    </div>
     <a href="#" class="modal-backdrop">Close</a>
</div>
