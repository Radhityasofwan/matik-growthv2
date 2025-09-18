@extends('layouts.app')

@section('title', 'Waha Senders')

@section('content')
<div class="container mx-auto px-6 py-8">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow mb-6">
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow mb-6">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between mb-4">
        <div>
            <h3 class="text-3xl font-medium">Waha Senders</h3>
            <p class="mt-1 text-sm opacity-70">Kelola nomor pengirim & verifikasi sesi lewat Scan QR.</p>
        </div>

        {{-- Pakai anchor -> modal :target (tanpa JS/alpine) --}}
        <a href="#create_sender_modal" class="btn btn-primary">Tambah Sender</a>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg border border-base-300">
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
                @forelse ($senders as $s)
                    <tr>
                        <td class="w-14">@if($s->is_default) ⭐ @endif</td>
                        <td>
                            <div class="font-semibold">{{ $s->name }}</div>
                            @if($s->description)
                                <div class="text-xs opacity-70">{{ $s->description }}</div>
                            @endif
                        </td>
                        <td>{{ $s->number ?? '—' }}</td>
                        <td class="font-mono text-xs">{{ $s->session ?? $s->session_name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $s->is_active ? 'badge-success' : 'badge-ghost' }}">
                                {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex gap-2 justify-end">
                                {{-- Edit --}}
                                <a href="#edit_sender_{{ $s->id }}" class="btn btn-xs">Edit</a>

                                {{-- Scan / Connect: anchor + onclick mulai proses --}}
                                <a href="#scan_sender_{{ $s->id }}"
                                   class="btn btn-xs btn-outline"
                                   onclick="startScan({{ $s->id }}, @js($s->name))">
                                    Scan / Connect
                                </a>

                                {{-- Jadikan Default --}}
                                <form action="{{ route('waha-senders.set-default', $s) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline">Jadikan Default</button>
                                </form>

                                {{-- Hapus --}}
                                <form action="{{ route('waha-senders.destroy', $s) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus sender ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-error">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Edit Modal --}}
                    <div id="edit_sender_{{ $s->id }}" class="modal">
                        <div class="modal-box max-w-xl">
                            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                            <h3 class="font-bold text-lg">Edit Sender</h3>

                            <form action="{{ route('waha-senders.update', $s) }}" method="POST" class="mt-4">
                                @csrf
                                @method('PATCH')

                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="label"><span class="label-text">Nama</span></label>
                                        <input type="text" name="name" class="input input-bordered w-full"
                                               value="{{ old('name', $s->name) }}" required>
                                    </div>

                                    <div>
                                        <label class="label"><span class="label-text">Deskripsi (opsional)</span></label>
                                        <input type="text" name="description" class="input input-bordered w-full"
                                               value="{{ old('description', $s->description) }}">
                                    </div>

                                    <div class="mt-2">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="is_default" value="1" @checked($s->is_default)>
                                            <span>Jadikan Default</span>
                                        </label>
                                    </div>

                                    <p class="text-xs opacity-70">
                                        Nomor & session akan terisi otomatis setelah tersambung via QR.
                                    </p>
                                </div>

                                <div class="modal-action">
                                    <a href="#" class="btn">Batal</a>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
                            </form>
                        </div>
                        <a href="#" class="modal-backdrop">Close</a>
                    </div>

                    {{-- Scan Modal --}}
                    <div id="scan_sender_{{ $s->id }}" class="modal">
                        <div class="modal-box max-w-2xl">
                            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                            <h3 class="font-bold text-lg">
                                Scan / Connect — <span id="scan-name-{{ $s->id }}">{{ $s->name }}</span>
                            </h3>

                            <div id="scan-loading-{{ $s->id }}" class="py-10 text-center">
                                <span class="loading loading-spinner loading-lg"></span>
                                <div class="mt-3 text-sm">Memeriksa status sesi...</div>
                            </div>

                            <div id="scan-error-{{ $s->id }}" class="alert alert-error my-4 hidden"></div>

                            <div id="scan-qr-wrap-{{ $s->id }}" class="mt-3 hidden">
                                <p class="text-sm mb-2">Scan QR berikut menggunakan WhatsApp.</p>
                                <img id="scan-qr-{{ $s->id }}" alt="QR Code"
                                     class="w-72 h-72 mx-auto rounded shadow border" />
                            </div>

                            <div class="mt-4">
                                <div class="badge" id="scan-state-{{ $s->id }}">—</div>
                            </div>

                            <div class="modal-action">
                                <a href="#" class="btn">Tutup</a>
                            </div>
                        </div>
                        <a href="#" class="modal-backdrop">Close</a>
                    </div>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 opacity-70">Belum ada sender.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3">{{ $senders->links() }}</div>
    </div>
</div>

{{-- Create Modal (pola :target, tanpa JS) --}}
<div id="create_sender_modal" class="modal">
    <div class="modal-box max-w-xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Tambah Sender</h3>

        <form action="{{ route('waha-senders.store') }}" method="POST" class="mt-4">
            @csrf

            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" class="input input-bordered w-full" required>
                </div>

                <div>
                    <label class="label"><span class="label-text">Deskripsi (opsional)</span></label>
                    <input type="text" name="description" class="input input-bordered w-full">
                </div>

                <div class="mt-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1">
                        <span>Jadikan Default</span>
                    </label>
                </div>

                <p class="text-xs opacity-70">
                    Nomor & session akan terisi otomatis setelah tersambung via QR.
                </p>
            </div>

            <div class="modal-action">
                <a href="#" class="btn">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- Vanilla JS untuk proses Scan/QR --}}
<script>
  const CSRF = '{{ csrf_token() }}';

  function el(id){ return document.getElementById(id); }
  function show(id){ const n = el(id); if(n){ n.classList.remove('hidden'); } }
  function hide(id){ const n = el(id); if(n){ n.classList.add('hidden'); } }
  function setText(id, txt){ const n = el(id); if(n){ n.textContent = txt; } }

  const polls = {}; // { [senderId]: intervalId }

  function showErrorFor(id, msg){
    const box = el('scan-error-' + id);
    if (box) { box.textContent = msg || 'Terjadi kesalahan.'; show('scan-error-' + id); }
  }

  function startScan(id, name){
    // Reset UI
    setText('scan-name-' + id, name || '');
    setText('scan-state-' + id, 'MEMULAI…');
    hide('scan-error-' + id);
    hide('scan-qr-wrap-' + id);
    show('scan-loading-' + id);

    // 1) Mulai sesi (POST) — kita TIDAK break kalau gagal, supaya polling tetap jalan.
    fetch(`{{ url('waha-senders') }}/${id}/qr-start`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(async (res) => {
      let js = {};
      try { js = await res.json(); } catch(e) {}
      if (!res.ok || js.success === false) {
        const msg = js.error || js.message || `HTTP ${res.status}`;
        // tampilkan error tapi tetap lanjut ke polling
        showErrorFor(id, `Gagal memanggil endpoint QR: ${msg}`);
      }
    })
    .catch(() => {
      // network error saat start — tampilkan, tapi tetap lanjut polling
      showErrorFor(id, 'Tidak bisa menghubungi server untuk memulai sesi.');
    })
    .finally(() => {
      // 2) Polling status/QR
      if (polls[id]) clearInterval(polls[id]);

      const tick = () => {
        fetch(`{{ url('waha-senders') }}/${id}/qr-status`, { headers: { 'Accept': 'application/json' } })
          .then(async (res) => {
            let js = {};
            try { js = await res.json(); } catch(e) {}

            hide('scan-loading-' + id);

            if (!res.ok || js.success === false) {
              const msg = js.error || js.message || `HTTP ${res.status}`;
              showErrorFor(id, `Gagal mengambil status: ${msg}`);
              return;
            }

            // Controller mengembalikan top-level: state, qr, connected
            const state = (js.state ? String(js.state) : 'UNKNOWN').toUpperCase();
            setText('scan-state-' + id, state);

            if (js.qr) {
              const img = el('scan-qr-' + id);
              if (img) img.src = js.qr;
              show('scan-qr-wrap-' + id);
            } else {
              // tidak ada QR; sembunyikan agar UI tidak menampilkan QR lawas
              hide('scan-qr-wrap-' + id);
            }

            // Selesai bila sudah tersambung
            if (state === 'CONNECTED' || js.connected === true) {
              if (polls[id]) { clearInterval(polls[id]); polls[id] = null; }
              setText('scan-state-' + id, 'CONNECTED');
              setTimeout(() => window.location.reload(), 600);
            }
          })
          .catch(() => {
            hide('scan-loading-' + id);
            showErrorFor(id, 'Gagal mengambil status (network).');
          });
      };

      // panggil pertama kali lalu interval 2 detik
      tick();
      polls[id] = setInterval(tick, 2000);
    });
  }
</script>
@endsection
