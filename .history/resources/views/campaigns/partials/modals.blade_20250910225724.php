<!-- Create Campaign Modal -->
<div id="create_campaign_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('campaigns.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Buat Kampanye Baru</h3>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-2"><label class="label"><span class="label-text">Nama Kampanye</span></label><input type="text" name="name" placeholder="Nama Kampanye" value="{{ old('name') }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Channel</span></label><input type="text" name="channel" placeholder="e.g., Google Ads" value="{{ old('channel') }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Status</span></label><select name="status" class="select select-bordered w-full" required><option value="planned">Planned</option><option value="active">Active</option><option value="completed">Completed</option><option value="paused">Paused</option></select></div>
                <div><label class="label"><span class="label-text">Budget (Rp)</span></label><input type="number" name="budget" placeholder="5000000" value="{{ old('budget') }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Revenue (Rp)</span></label><input type="number" name="revenue" placeholder="0" value="{{ old('revenue') }}" class="input input-bordered w-full" /></div>
                <div><label class="label"><span class="label-text">Tanggal Mulai</span></label><input type="date" name="start_date" value="{{ old('start_date') }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Tanggal Selesai</span></label><input type="date" name="end_date" value="{{ old('end_date') }}" class="input input-bordered w-full" required /></div>
                <div class="col-span-2"><label class="label"><span class="label-text">Owner</span></label><select name="owner_id" class="select select-bordered w-full" required>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-span-2"><label class="label"><span class="label-text">Deskripsi</span></label><textarea name="description" class="textarea textarea-bordered w-full" placeholder="Deskripsi singkat kampanye">{{ old('description') }}</textarea></div>
            </div>
            <div class="modal-action mt-6"><a href="#" class="btn btn-ghost">Batal</a><button type="submit" class="btn btn-primary">Simpan Kampanye</button></div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

<!-- Edit Campaign Modals -->
@foreach ($campaigns as $campaign)
<div id="edit_campaign_modal_{{ $campaign->id }}" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('campaigns.update', $campaign) }}" method="POST">
            @csrf @method('PATCH')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Edit Kampanye: {{ $campaign->name }}</h3>
             <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-2"><label class="label"><span class="label-text">Nama Kampanye</span></label><input type="text" name="name" value="{{ old('name', $campaign->name) }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Channel</span></label><input type="text" name="channel" value="{{ old('channel', $campaign->channel) }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Status</span></label><select name="status" class="select select-bordered w-full" required><option value="planned" @selected(old('status', $campaign->status) == 'planned')>Planned</option><option value="active" @selected(old('status', $campaign->status) == 'active')>Active</option><option value="completed" @selected(old('status', $campaign->status) == 'completed')>Completed</option><option value="paused" @selected(old('status', $campaign->status) == 'paused')>Paused</option></select></div>
                <div><label class="label"><span class="label-text">Budget (Rp)</span></label><input type="number" name="budget" value="{{ old('budget', $campaign->budget) }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Revenue (Rp)</span></label><input type="number" name="revenue" value="{{ old('revenue', $campaign->revenue) }}" class="input input-bordered w-full" /></div>
                <div><label class="label"><span class="label-text">Tanggal Mulai</span></label><input type="date" name="start_date" value="{{ old('start_date', $campaign->start_date->format('Y-m-d')) }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Tanggal Selesai</span></label><input type="date" name="end_date" value="{{ old('end_date', $campaign->end_date->format('Y-m-d')) }}" class="input input-bordered w-full" required /></div>
                <div class="col-span-2"><label class="label"><span class="label-text">Owner</span></label><select name="owner_id" class="select select-bordered w-full" required>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('owner_id', $campaign->owner_id) == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-span-2"><label class="label"><span class="label-text">Deskripsi</span></label><textarea name="description" class="textarea textarea-bordered w-full">{{ old('description', $campaign->description) }}</textarea></div>
            </div>
            <div class="modal-action mt-6"><a href="#" class="btn btn-ghost">Batal</a><button type="submit" class="btn btn-primary">Update Kampanye</button></div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endforeach
