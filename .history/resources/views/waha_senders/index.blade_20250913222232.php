@extends('layouts.app')

@section('title', 'Waha Senders')

@section('content')
<div class="container mx-auto px-6 py-8">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success mb-6">
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error mb-6">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Waha Senders</h1>
            <p class="text-sm text-gray-500">Kelola nomor pengirim & verifikasi sesi lewat Scan QR.</p>
        </div>
        <a href="#create_sender_modal" class="btn btn-primary">Tambah Sender</a>
    </div>

    <div class="card">
        <div class="card-body overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Default</th>
                        <th>Nama</th>
                        <th>Nomor</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($senders as $s)
                        @php
                            $sessionVal = $s->session_name ?? $s->session ?? '';
                            $label = $s->name ?? $s->display_name ?? ('Sender #'.$s->id);
                        @endphp
                        <tr class="relative"> {{-- relative agar tidak ketutup overlay lain --}}
                            <td>@if($s->is_default) ⭐ @endif</td>
                            <td>
                                <div class="font-semibold">{{ $label }}</div>
                                @if(($s->description ?? null))
                                    <div class="text-xs text-gray-500">{{ $s->description }}</div>
                                @endif
                            </td>
                            <td class="font-mono text-xs">{{ $s->number }}</td>
                            <td class="font-mono text-xs">{{ $sessionVal ?: '—' }}</td>
                            <td>
                                <span class="badge {{ $s->is_active ? 'badge-success' : 'badge-ghost' }}">
                                    {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            {{-- Aksi – gunakan z-10 agar selalu di atas --}}
                            <td class="text-right space-x-2 relative z-10">
                                {{-- Edit (modal anchor) --}}
                                <a href="#edit_sender_modal_{{ $s->id }}" class="btn btn-sm">Edit</a>

                                {{-- Scan / Connect --}}
                                <button type="button"
                                        class="btn btn-sm btn-outline"
                                        data-id="{{ $s->id }}"
                                        data-session="{{ $sessionVal }}"
                                        onclick="scanConnect(this)">
                                    Scan / Connect
                                </button>

                                {{-- Jadikan Default --}}
                                <form method="POST" action="{{ route('waha-senders.set-default', $s) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline">Jadikan Default</button>
                                </form>

                                {{-- Hapus --}}
                                <form method="POST" action="{{ route('waha-senders.destroy', $s) }}" class="inline" onsubmit="return confirm('Hapus sender ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>

                        {{-- Modal Edit --}}
                        <div id="edit_sender_modal_{{ $s->id }}" class="modal">
                            <div class="modal-box max-w-xl">
                                <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                                <h3 class="font-bold text-lg mb-4">Edit Sender</h3>
                                <form method="POST" action="{{ route('waha-senders.update', $s) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="label">Nama</label>
                                            <input name="name" class="input input-bordered w-full" value="{{ old('name', $s->name) }}" required>
                                        </div>
                                        <div>
                                            <label class="label">Nomor</label>
                                            <input name="number" class="input input-bordered w-full" value="{{ old('number', $s->number) }}" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="label">Deskripsi</label>
                                            <input name="description" class="input input-bordered w-full" value="{{ old('description', $s->description) }}">
                                        </div>
                                        <div>
                                            <label class="label">Session</label>
                                            <input name="session" class="input input-bordered w-full" value="{{ old('session', $sessionVal) }}" required>
                                        </div>
                                        <div class="flex items-center gap-4 mt-6">
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="is_active" value="1" @checked($s->is_active)>
                                                <span>Aktif</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="is_default" value="1" @checked($s->is_default)>
                                                <span>Jadikan Default</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="modal-action">
                                        <a href="#" class="btn">Batal</a>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                            <a href="#" class="modal-backdrop">Close</a>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-sm text-gray-500 py-8">Belum ada sender.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">{{ $senders->links() }}</div>
        </div>
    </div>
</div>

{{-- Modal Create --}}
<div id="create_sender_modal" class="modal">
    <div class="modal-box max-w-xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg mb-4">Tambah Sender</h3>
        <form method="POST" action="{{ route('waha-senders.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Nama</label>
                    <input name="name" class="input input-bordered w-full" required>
                </div>
                <div>
                    <label class="label">Nomor</label>
                    <input name="number" class="input input-bordered w-full" placeholder="628xxxx" required>
                </div>
                <div class="md:col-span-2">
                    <label class="label">Deskripsi</label>
                    <input name="description" class="input input-bordered w-full">
                </div>
                <div>
                    <label class="label">Session</label>
                    <input name="session" class="input input-bordered w-full" placeholder="nama session di WAHA" required>
                </div>
                <div class="flex items-center gap-4 mt-6">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Aktif</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1">
                        <span>Jadikan Default</span>
                    </label>
                </div>
            </div>

            <div class="modal-action">
                <a href="#" class="btn">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- Modal QR --}}
<div id="qr_modal" class="modal">
    <div class="modal-box max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg mb-4">Scan / Connect</h3>
        <div id="qr_content" class="prose max-w-none text-sm">
            <p>Memeriksa status sesi…</p>
        </div>
        <div class="modal-action">
            <a href="#" class="btn">Tutup</a>
        </div>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- JS minimal & tanpa Alpine untuk pastikan tombol bekerja --}}
<script>
async function scanConnect(btn) {
    // Buka modal QR dulu (tidak ketutup overlay)
    location.hash = '#qr_modal';

    const id = btn?.dataset?.id;
    const sess = btn?.dataset?.session || '';
    const box = document.getElementById('qr_content');
    box.innerHTML = '<p class="text-sm">Memeriksa status sesi…</p>';

    // Rute opsional – jika belum ada akan 404, kita tampilkan petunjuk
    const base = '{{ url('/') }}';
    const statusUrl = `${base}/waha-senders/${id}/qr-status`;
    const startUrl  = `${base}/waha-senders/${id}/qr-start`;

    try {
        let r = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
        if (!r.ok) throw new Error('Status check failed: ' + r.status);
        let j = await r.json();

        if (j.exists) {
            box.innerHTML = `<div class="alert alert-success"><div>Sesi <code>${sess || '(default)'}</code> sudah aktif/terhubung.</div></div>`;
            return;
        }

        box.innerHTML = `<p>Sesi <code>${sess || '(default)'}</code> belum aktif. Meminta QR…</p>`;
        r = await fetch(startUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }});
        if (!r.ok) throw new Error('Start failed: ' + r.status);
        j = await r.json();

        // Tampilkan JSON mentah (server WAHA berbeda-beda; beberapa mengembalikan SVG/URL)
        const pretty = document.createElement('pre');
        pretty.className = 'text-xs whitespace-pre-wrap';
        pretty.textContent = JSON.stringify(j, null, 2);
        box.innerHTML = '<p>QR / respons dari WAHA:</p>';
        box.appendChild(pretty);
    } catch (e) {
        box.innerHTML = `<div class="alert alert-error"><div>Gagal memanggil endpoint QR. Pastikan route qr-status/qr-start sudah dibuat. Pesan: ${e.message}</div></div>`;
    }
}
</script>
@endsection
