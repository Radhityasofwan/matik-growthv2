@extends('layouts.app')

@section('title', 'Waha Senders - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><strong>Error!</strong> Mohon periksa kembali form Anda.</span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Waha Senders</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola nomor pengirim dan sesi WAHA.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="#create_sender_modal" class="btn btn-primary">Tambah Sender</a>
        </div>
    </div>

    @if($senders->isEmpty())
        <div class="text-center py-20">
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Belum ada sender</h3>
            <p class="mt-1 text-sm text-gray-500">Tambahkan minimal satu nomor pengirim untuk mulai mengirim pesan.</p>
        </div>
    @else
        {{-- Table --}}
        <div class="mt-8 overflow-x-auto">
            <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Default</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Nama</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Nomor</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Session</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Aktif</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        @foreach ($senders as $s)
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                @if($s->is_default) <span title="Default">⭐</span> @endif
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $s->name }}</p>
                                @if($s->description)
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">{{ $s->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">{{ $s->number }}</td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-xs font-mono break-all">{{ $s->session }}</td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <form action="{{ route('waha-senders.toggle-active', $s) }}" method="POST" class="inline ajax-post" data-success="Status pengirim diperbarui.">
                                    @csrf
                                    <button type="submit" class="btn btn-xs {{ $s->is_active ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm text-right">
                                <div class="dropdown dropdown-end">
                                    <label tabindex="0" class="btn btn-ghost btn-xs">...</label>
                                    <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                                        <li><a href="#edit_sender_modal_{{ $s->id }}">Edit</a></li>
                                        <li>
                                            <form action="{{ route('waha-senders.set-default', $s) }}" method="POST" class="ajax-post" data-success="Default diperbarui.">
                                                @csrf
                                                <button type="submit" class="w-full text-left" @disabled($s->is_default)>Jadikan Default</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('waha-senders.destroy', $s) }}" method="POST" onsubmit="return confirm('Hapus sender ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full text-left text-error">Hapus</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Footer: pagination --}}
                <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col sm:flex-row items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-400">
                        Menampilkan {{ $senders->firstItem() }}–{{ $senders->lastItem() }} dari {{ $senders->total() }} data
                    </div>
                    <div class="mt-4 sm:mt-0">
                        {{ $senders->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Create Modal --}}
<div id="create_sender_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('waha-senders.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Tambah Sender</h3>
            <div class="mt-4 grid md:grid-cols-2 gap-4">
                <div>
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered w-full" maxlength="100" required>
                </div>
                <div>
                    <label class="label"><span class="label-text">Nomor</span></label>
                    <input type="text" name="number" value="{{ old('number') }}" class="input input-bordered w-full" maxlength="30" placeholder="628xxxx" required oninput="this.value=this.value.replace(/\D+/g,'')">
                </div>
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Deskripsi (opsional)</span></label>
                    <input type="text" name="description" value="{{ old('description') }}" class="input input-bordered w-full" maxlength="255">
                </div>
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Session</span></label>
                    <input type="text" name="session" value="{{ old('session') }}" class="input input-bordered w-full font-mono text-sm" maxlength="150" required>
                </div>
                <div class="md:col-span-2 flex items-center gap-6">
                    {{-- kirim 0 jika unchecked --}}
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Aktif</span>
                    </label>
                    <input type="hidden" name="is_default" value="0">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1">
                        <span>Jadikan Default</span>
                    </label>
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

{{-- Edit Modals --}}
@foreach ($senders as $s)
<div id="edit_sender_modal_{{ $s->id }}" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('waha-senders.update', $s) }}" method="POST">
            @csrf
            @method('PUT')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Edit Sender: {{ $s->name }}</h3>
            <div class="mt-4 grid md:grid-cols-2 gap-4">
                <div>
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" value="{{ old('name', $s->name) }}" class="input input-bordered w-full" maxlength="100" required>
                </div>
                <div>
                    <label class="label"><span class="label-text">Nomor</span></label>
                    <input type="text" name="number" value="{{ old('number', $s->number) }}" class="input input-bordered w-full" maxlength="30" placeholder="628xxxx" required oninput="this.value=this.value.replace(/\D+/g,'')">
                </div>
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Deskripsi (opsional)</span></label>
                    <input type="text" name="description" value="{{ old('description', $s->description) }}" class="input input-bordered w-full" maxlength="255">
                </div>
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Session</span></label>
                    <input type="text" name="session" value="{{ old('session', $s->session) }}" class="input input-bordered w-full font-mono text-sm" maxlength="150" required>
                </div>
                <div class="md:col-span-2 flex items-center gap-6">
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $s->is_active))>
                        <span>Aktif</span>
                    </label>
                    <input type="hidden" name="is_default" value="0">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $s->is_default))>
                        <span>Jadikan Default</span>
                    </label>
                </div>
            </div>
            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endforeach

@endsection

@push('scripts')
<script>
/**
 * Aksi khusus (toggle-active & set-default) di controllermu return JSON.
 * Supaya UX konsisten seperti index lain, kita submit pakai fetch lalu reload.
 */
document.addEventListener('DOMContentLoaded', function () {
    const ajaxForms = document.querySelectorAll('form.ajax-post');
    ajaxForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; btn.classList.add('loading'); }
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j)))
            .then(() => {
                // kalau mau flash message, bisa gunakan session via redirect dari controller,
                // tapi karena controller return JSON, cukup reload saja.
                location.reload();
            })
            .catch(err => {
                alert(err?.message || 'Aksi gagal diproses.');
                if (btn) { btn.disabled = false; btn.classList.remove('loading'); }
            });
        });
    });
});
</script>
@endpush
