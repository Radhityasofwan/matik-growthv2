{{-- Partial form yang konsisten dipakai di Create & Edit --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="form-control md:col-span-2">
        <label class="label"><span class="label-text">Nama Kampanye</span></label>
        <input type="text" name="name" placeholder="Nama Kampanye"
               value="{{ old('name', $campaign->name) }}"
               class="input input-bordered w-full" required />
    </div>

    <div class="form-control">
        <label class="label"><span class="label-text">Channel</span></label>
        <input type="text" name="channel" placeholder="e.g., Google Ads"
               value="{{ old('channel', $campaign->channel) }}"
               class="input input-bordered w-full" required />
    </div>

    <div class="form-control">
        <label class="label"><span class="label-text">Status</span></label>
        <select name="status" class="select select-bordered w-full" required>
            @foreach (['planned'=>'Planned','active'=>'Active','completed'=>'Completed','paused'=>'Paused'] as $val => $label)
                <option value="{{ $val }}" @selected(old('status', $campaign->status) === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-control">
        <label class="label"><span class="label-text">Budget (Rp)</span></label>
        <input type="number" name="budget"
               value="{{ old('budget', $campaign->budget) }}"
               class="input input-bordered w-full" required />
    </div>

    <div class="form-control">
        <label class="label"><span class="label-text">Owner</span></label>
        <select name="owner_id" class="select select-bordered w-full" required>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('owner_id', $campaign->owner_id) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-control">
        <label class="label"><span class="label-text">Tanggal Mulai</span></label>
        <input type="date" name="start_date"
               value="{{ old('start_date', optional($campaign->start_date)->format('Y-m-d')) }}"
               class="input input-bordered w-full" required />
    </div>

    <div class="form-control">
        <label class="label"><span class="label-text">Tanggal Selesai</span></label>
        <input type="date" name="end_date"
               value="{{ old('end_date', optional($campaign->end_date)->format('Y-m-d')) }}"
               class="input input-bordered w-full" required />
    </div>

    <div class="form-control md:col-span-2">
        <label class="label"><span class="label-text">Deskripsi</span></label>
        <textarea name="description" class="textarea textarea-bordered w-full" placeholder="Deskripsi singkat kampanye">{{ old('description', $campaign->description) }}</textarea>
    </div>
</div>

{{-- Optional: blok metrik (gunakan di halaman jika perlu) --}}
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="form-control">
        <label class="label"><span class="label-text">Total Spent (Rp)</span></label>
        <input type="number" step="any" name="total_spent"
               value="{{ old('total_spent', $campaign->total_spent) }}"
               class="input input-bordered w-full" />
    </div>
    <div class="form-control">
        <label class="label"><span class="label-text">Revenue (Rp)</span></label>
        <input type="number" step="any" name="revenue"
               value="{{ old('revenue', $campaign->revenue) }}"
               class="input input-bordered w-full" />
    </div>
    <div class="form-control">
        <label class="label"><span class="label-text">Impressions</span></label>
        <input type="number" name="impressions"
               value="{{ old('impressions', $campaign->impressions) }}"
               class="input input-bordered w-full" />
    </div>
    <div class="form-control">
        <label class="label"><span class="label-text">Link Clicks</span></label>
        <input type="number" name="link_clicks"
               value="{{ old('link_clicks', $campaign->link_clicks) }}"
               class="input input-bordered w-full" />
    </div>
    <div class="form-control md:col-span-2">
        <label class="label"><span class="label-text">Hasil (Konversi)</span></label>
        <input type="number" name="results"
               value="{{ old('results', $campaign->results) }}"
               class="input input-bordered w-full" />
    </div>
</div>
