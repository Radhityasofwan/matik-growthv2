{{-- Create Lead Modal --}}
<div id="create_lead_modal" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <form action="{{ route('leads.store') }}" method="POST">
      @csrf
      <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
      <h3 class="font-bold text-lg">Tambah Lead Baru</h3>

      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="label"><span class="label-text">Nama</span></label>
          <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered w-full" required />
        </div>
        <div>
          <label class="label"><span class="label-text">Email</span></label>
          <input type="email" name="email" value="{{ old('email') }}" class="input input-bordered w-full" required />
        </div>
        <div>
          <label class="label"><span class="label-text">No. Whatsapp</span></label>
          <input type="text" name="phone" value="{{ old('phone') }}" class="input input-bordered w-full" />
        </div>
        <div>
          <label class="label"><span class="label-text">Nama Toko</span></label>
          <input type="text" name="store_name" value="{{ old('store_name') }}" class="input input-bordered w-full" />
        </div>

        <div>
          <label class="label"><span class="label-text">Tanggal Daftar</span></label>
          <input id="create_registered_at" type="datetime-local" name="registered_at"
                 value="{{ old('registered_at', now()->format('Y-m-d\TH:i')) }}"
                 class="input input-bordered w-full" />
        </div>
        <div>
          <label class="label"><span class="label-text">Tanggal Habis Trial</span></label>
          <input id="create_trial_ends_at" type="date" name="trial_ends_at"
                 value="{{ old('trial_ends_at', now()->addDays(7)->format('Y-m-d')) }}"
                 class="input input-bordered w-full" />
        </div>

        <div>
          <label class="label"><span class="label-text">Status</span></label>
          <select name="status" class="select select-bordered w-full" required>
            <option value="active" @selected(old('status')=='active')>Aktif</option>
            <option value="nonactive" @selected(old('status')=='nonactive')>Tidak Aktif</option>
            <option value="converted" @selected(old('status')=='converted')>Konversi</option>
            <option value="churn" @selected(old('status')=='churn')>Dibatalkan</option>
            <option value="trial" @selected(old('status')=='trial')>Trial</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="label"><span class="label-text">Owner</span></label>
          <select name="owner_id" class="select select-bordered w-full" required>
            @forelse($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }}</option>
            @empty
              <option disabled>Tidak ada user</option>
            @endforelse
          </select>
        </div>
      </div>

      <div class="modal-action mt-6">
        <a href="#" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Lead</button>
      </div>
    </form>
  </div>
  <a href="#" class="modal-backdrop">Close</a>
</div>
