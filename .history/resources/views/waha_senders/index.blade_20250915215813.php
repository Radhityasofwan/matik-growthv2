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

  const polls = {};
  const pollStarted = {};
  const pollDeadline = {};
  const qrLastLoad = {};     // ts terakhir gambar QR di-load
  const qrTtlMs = 20_000;    // QR WhatsApp umumnya valid ~20–30s → pakai 20s

  const $ = (id) => document.getElementById(id);
  const show = (id) => { const n = $(id); if (n) n.classList.remove('hidden'); };
  const hide = (id) => { const n = $(id); if (n) n.classList.add('hidden'); };
  const setText = (id, t) => { const n = $(id); if (n) n.textContent = t; };
  const showErr = (id, msg) => { const n = $('scan-error-' + id); if (!n) return; n.textContent = msg; show('scan-error-' + id); };
  const clearErr = (id) => hide('scan-error-' + id);

  function loadQrThrottled(id, url){
    const now = Date.now();
    const last = qrLastLoad[id] || 0;

    // Muat jika belum pernah, atau sudah lewat TTL
    if (!last || now - last >= qrTtlMs) {
      const img = $('scan-qr-' + id);
      if (!img) return;

      // cache-bust tiap load
      const src = url + '?t=' + now;
      img.onerror = () => {
        // Kalau gagal load, paksa reload 1.2s lagi
        qrLastLoad[id] = 0;
        setTimeout(() => loadQrThrottled(id, url), 1200);
      };
      img.onload = () => { /* sukses, biarkan */ };
      img.src = src;

      qrLastLoad[id] = now;
      show('scan-qr-wrap-' + id);
    }
  }

  function startScan(id, name){
    // reset UI
    setText('scan-name-' + id, name || '');
    setText('scan-state-' + id, 'MEMULAI…');
    clearErr(id);
    hide('scan-qr-wrap-' + id);
    show('scan-loading-' + id);

    if (polls[id]) clearInterval(polls[id]);
    pollStarted[id] = false;
    pollDeadline[id] = Date.now() + 45_000; // 45s
    qrLastLoad[id] = 0; // reset agar QR dipasang sekali di awal

    // FIRE-AND-FORGET start (jangan ditunggu)
    fetch(`{{ url('waha-senders') }}/${id}/qr-start`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(async (res) => {
      let js = {}; try { js = await res.json(); } catch(e) {}
      if ((!res.ok || js.success === false) && !pollStarted[id]) {
        const msg = js.error || js.message || `HTTP ${res.status}`;
        showErr(id, `Gagal memanggil endpoint QR: ${msg}`);
      }
    }).catch(() => {
      if (!pollStarted[id]) showErr(id, 'Tidak bisa menghubungi server untuk memulai sesi.');
    });

    // POLLING status tiap 1 detik (tanpa mereload QR tiap tick)
    const tick = () => {
      if (Date.now() > (pollDeadline[id] || 0)) {
        if (polls[id]) { clearInterval(polls[id]); polls[id] = null; }
        hide('scan-loading-' + id);
        showErr(id, 'Timeout menunggu QR. Coba ulangi.');
        return;
      }

      fetch(`{{ url('waha-senders') }}/${id}/qr-status`, { headers: { 'Accept': 'application/json' } })
        .then(async (res) => {
          let js = {}; try { js = await res.json(); } catch(e) {}
          hide('scan-loading-' + id);

          if (!res.ok || js.success === false) {
            const msg = js.error || js.message || `HTTP ${res.status}`;
            showErr(id, `Gagal mengambil status: ${msg}`);
            return;
          }

          pollStarted[id] = true;
          clearErr(id);

          const state = (js.state ? String(js.state) : 'UNKNOWN').toUpperCase();
          setText('scan-state-' + id, state);

          if (state === 'FAILED') {
            // paksa sesi refresh; biar user klik ulang "Scan / Connect"
            showErr(id, 'Sesi gagal. Coba ulangi Scan / Connect.');
            if (polls[id]) { clearInterval(polls[id]); polls[id] = null; }
            return;
          }

          if (state === 'SCAN_QR_CODE') {
            // 1) inline QR (sekali pasang; tidak perlu refresh)
            if (js.qr) {
              let src = js.qr;
              if (!/^data:|^https?:\/\//.test(src) && /^[A-Za-z0-9+/=]{200,}$/.test(src)) {
                src = 'data:image/png;base64,' + src;
              }
              const img = $('scan-qr-' + id);
              if (img && img.src !== src) img.src = src;
              show('scan-qr-wrap-' + id);
            }
            // 2) proxy URL (auth/qr) → muat hanya saat awal atau tiap 20s
            else if (js.qr_url) {
              loadQrThrottled(id, js.qr_url);
            } else {
              hide('scan-qr-wrap-' + id);
            }
          } else {
            hide('scan-qr-wrap-' + id);
          }

          if (state === 'CONNECTED' || js.connected === true) {
            if (polls[id]) { clearInterval(polls[id]); polls[id] = null; }
            setTimeout(() => window.location.reload(), 600);
          }
        })
        .catch(() => {
          hide('scan-loading-' + id);
          showErr(id, 'Gagal mengambil status (network).');
        });
    };

    tick();
    polls[id] = setInterval(tick, 1000);
  }
</script>

@endsection
