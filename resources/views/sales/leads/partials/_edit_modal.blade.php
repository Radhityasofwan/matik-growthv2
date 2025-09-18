{{-- Edit Lead Modal --}}
<div id="edit_lead_modal_{{ $lead->id }}" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <form action="{{ route('leads.update', $lead) }}" method="POST">
      @csrf @method('PATCH')
      <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
      <h3 class="font-bold text-lg">Edit Lead: {{ $lead->name }}</h3>

      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="label"><span class="label-text">Nama</span></label>
          <input type="text" name="name" value="{{ old('name', $lead->name) }}" class="input input-bordered w-full" required />
        </div>
        <div>
          <label class="label"><span class="label-text">Email</span></label>
          <input type="email" name="email" value="{{ old('email', $lead->email) }}" class="input input-bordered w-full" required />
        </div>
        <div>
          <label class="label"><span class="label-text">No. Whatsapp</span></label>
          <input type="text" name="phone" value="{{ old('phone', $lead->phone) }}" class="input input-bordered w-full" />
        </div>
        <div>
          <label class="label"><span class="label-text">Nama Toko</span></label>
          <input type="text" name="store_name" value="{{ old('store_name', $lead->store_name) }}" class="input input-bordered w-full" />
        </div>

        <div>
          <label class="label"><span class="label-text">Tanggal Daftar</span></label>
          <input id="edit_registered_at_{{ $lead->id }}" type="datetime-local" name="registered_at"
                 value="{{ old('registered_at', $lead->created_at?->format('Y-m-d\TH:i')) }}"
                 class="input input-bordered w-full" />
        </div>
        <div>
          <label class="label"><span class="label-text">Tanggal Habis Trial</span></label>
          <input id="edit_trial_ends_at_{{ $lead->id }}" type="date" name="trial_ends_at"
                 value="{{ old('trial_ends_at', $lead->trial_ends_at?->format('Y-m-d')) }}"
                 class="input input-bordered w-full" />
        </div>

        <div>
          <label class="label"><span class="label-text">Status</span></label>
          <select name="status" class="select select-bordered w-full status-selector"
                  data-lead-id="{{ $lead->id }}" required>
            <option value="active"    @selected(old('status', $lead->status)=='active')>Aktif</option>
            <option value="nonactive" @selected(old('status', $lead->status)=='nonactive')>Tidak Aktif</option>
            <option value="converted" @selected(old('status', $lead->status)=='converted')>Konversi</option>
            <option value="churn"     @selected(old('status', $lead->status)=='churn')>Dibatalkan</option>
            <option value="trial"     @selected(old('status', $lead->status)=='trial')>Trial</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="label"><span class="label-text">Owner</span></label>
          <select name="owner_id" class="select select-bordered w-full" required>
            @foreach($users as $user)
              <option value="{{ $user->id }}" @selected(old('owner_id', $lead->owner_id) == $user->id)>{{ $user->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Subscription (muncul saat converted) --}}
        <div id="subscription_form_{{ $lead->id }}" class="hidden md:col-span-2 mt-2 pt-4 border-t space-y-4">
          <h4 class="font-semibold text-md">Detail Langganan</h4>
          <div>
            <label class="label"><span class="label-text">Nama Paket</span></label>
            <input type="text" name="plan" value="{{ old('plan', $lead->subscription->plan ?? '') }}" class="input input-bordered w-full" />
          </div>
          <div>
            <label class="label"><span class="label-text">Jumlah (Rp)</span></label>
            <input type="number" name="amount" value="{{ old('amount', $lead->subscription->amount ?? '') }}" class="input input-bordered w-full" />
          </div>
          <div>
            <label class="label"><span class="label-text">Siklus</span></label>
            <select name="cycle" class="select select-bordered w-full">
              <option value="monthly" @selected(old('cycle', $lead->subscription->cycle ?? '')=='monthly')>Bulanan</option>
              <option value="yearly"  @selected(old('cycle', $lead->subscription->cycle ?? '')=='yearly')>Tahunan</option>
            </select>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="label"><span class="label-text">Mulai</span></label>
              <input type="date" name="start_date"
                     value="{{ old('start_date', optional($lead->subscription)->start_date ? $lead->subscription->start_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                     class="input input-bordered w-full" />
            </div>
            <div>
              <label class="label"><span class="label-text">Berakhir (Opsional)</span></label>
              <input type="date" name="end_date"
                     value="{{ old('end_date', optional($lead->subscription)->end_date ? $lead->subscription->end_date->format('Y-m-d') : '') }}"
                     class="input input-bordered w-full" />
            </div>
          </div>
        </div>
      </div>

      <div class="modal-action mt-6">
        <a href="#" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Update Lead</button>
      </div>
    </form>
  </div>
  <a href="#" class="modal-backdrop">Close</a>
</div>
