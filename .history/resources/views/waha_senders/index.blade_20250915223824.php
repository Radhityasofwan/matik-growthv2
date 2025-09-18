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
                            <a href="#edit_sender_{{ $s->id }}" class="btn btn-xs">Edit</a>

                            <a href="#scan_sender_{{ $s->id }}"
                               class="btn btn-xs btn-outline"
                               onclick="startScan({{ $s->id }}, @js($s->name))">
                                Scan / Connect
                            </a>

                            <form action="{{ route('waha-senders.set-default', $s) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline">Jadikan Default</button>
                            </form>

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
                    <div class="modal-box max-w-2xl relative overflow-hidden">
                        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                        <h3 class="font-bold text-lg">
                            Scan / Connect — <span id="scan-name-{{ $s->id }}">{{ $s->name }}</span>
                        </h3>

                        {{-- Loading --}}
                        <div id="scan-loading-{{ $s->id }}" class="py-10 text-center">
                            <span class="loading loading-spinner loading-lg"></span>
                            <div class="mt-3 text-sm">Memeriksa status sesi...</div>
                        </div>

                        {{-- Error --}}
                        <div id="scan-error-{{ $s->id }}" class="alert alert-error my-4 hidden"></div>

                        {{-- QR --}}
                        <div id="scan-qr-wrap-{{ $s->id }}" class="mt-3 hidden">
                            <p class="text-sm mb-2">Scan QR berikut menggunakan WhatsApp.</p>
                            <img id="scan-qr-{{ $s->id }}" alt="QR Code"
                                 class="w-72 h-72 mx-auto rounded shadow border" />
                        </div>

                        {{-- Pairing Code --}}
                        <div class="mt-4">
                            <button type="button" class="btn btn-outline btn-sm"
                                    onclick="requestCode({{ $s->id }})">
                                Minta Kode
                            </button>
                        </div>
                        <div id="scan-code-wrap-{{ $s->id }}" class="mt-3 hidden">
                            <p class="text-sm mb-2">
                                Atau tautkan dengan kode berikut di WhatsApp (Linked Devices → <i>Masuk dengan kode</i>).
                            </p>
                            <div class="flex items-center gap-3">
                                <div id="scan-code-{{ $s->id }}" class="text-3xl font-mono tracking-widest">———</div>
                                <button type="button" class="btn btn-xs" onclick="copyText('scan-code-{{ $s->id }}')">Salin</button>
                            </div>
                        </div>

                        {{-- State badge --}}
                        <div class="mt-4">
                            <div class="badge" id="scan-state-{{ $s->id }}">—</div>
                        </div>

                        {{-- SUCCESS VIEW --}}
                        <div id="scan-success-{{ $s->id }}" class="hidden text-center py-8">
                            <div class="mx-auto w-20 h-20 rounded-full bg-green-500/10 flex items-center justify-center">
                                <svg class="w-12 h-12 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </div>
                            <h4 class="mt-4 text-xl font-semibold">Berhasil tersambung</h4>
                            <p class="text-sm opacity-70">Nomor kamu sudah aktif & siap dipakai 🎉</p>
                        </div>
                        <div id="scan-confetti-{{ $s->id }}" class="pointer-events-none absolute inset-0 z-50 hidden"></div>

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

{{-- Create Modal --}}
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

{{-- Confetti CSS (ringan) --}}
<style>
@keyframes confetti-fall {
  0%   { transform: translateY(-100vh) rotate(0deg);   opacity: 1; }
  100% { transform: translateY(100vh)  rotate(720deg); opacity: 1; }
}
.confetti {
  position: absolute; width: 8px; height: 14px; border-radius: 2px;
  animation: confetti-fall 1.2s linear forwards;
}
</style>

{{-- Vanilla JS --}}
<script>
  const CSRF = '{{ csrf_token() }}';

  const polls = {};
  const pollStarted = {};
  const pollDeadline = {};
  const qrLastLoad = {};
  const qrTtlMs = 20_000; // refresh QR setiap 20s

  const $ = (id) => document.getElementById(id);
  const show = (id) => { const n = $(id); if (n) n.classList.remove('hidden'); };
  const hide = (id) => { const n = $(id); if (n) n.classList.add('hidden'); };
  const setText = (id, t) => { const n = $(id); if (n) n.textContent = t; };
  const showErr = (id, msg) => { const n = $('scan-error-' + id); if (!n) return; n.textContent = msg; show('scan-error-' + id); };
  const clearErr = (id) => hide('scan-error-' + id);

  function copyText(id){
    const n = $(id); if (!n) return;
    const text = (n.innerText || n.textContent || '').trim();
    if (!text) return;
    navigator.clipboard?.writeText(text);
  }

  function requestCode(id){
    const badge = $('scan-state-' + id);
    if (badge) badge.textContent = 'REQUEST_CODE…';

    fetch(`{{ url('waha-senders') }}/${id}/auth-request-code`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(js => {
      if (!js || js.success === false) {
        const msg = (js && (js.error || js.message)) ? (js.error || js.message) : 'Gagal meminta kode.';
        showErr(id, msg);
        return;
      }
      const code = (js.code || '').toString().trim();
      const elCode = $('scan-code-' + id);
      if (elCode) elCode.textContent = code || '—';
      show('scan-code-wrap-' + id);
      if (badge) badge.textContent = (js.state || 'PAIR_WITH_CODE').toUpperCase();
    })
    .catch(() => showErr(id, 'Tidak dapat meminta kode.'));
  }

  function loadQrThrottled(id, url){
    const now = Date.now();
    const last = qrLastLoad[id] || 0;
    if (!last || now - last >= qrTtlMs) {
      const img = $('scan-qr-' + id);
      if (!img) return;
      const src = url + '?t=' + now; // cache-bust
      img.onerror = () => { qrLastLoad[id] = 0; setTimeout(() => loadQrThrottled(id, url), 1200); };
      img.onload  = () => {};
      img.src = src;
      qrLastLoad[id] = now;
      show('scan-qr-wrap-' + id);
    }
  }

  function launchConfetti(containerId){
    const box = $(containerId);
    if (!box) return;
    box.innerHTML = '';
    box.classList.remove('hidden');

    const colors = ['#22c55e','#06b6d4','#f59e0b','#ef4444','#8b5cf6'];
    const n = 36;
    const w = box.clientWidth  || 640;
    for (let i=0; i<n; i++){
      const d = document.createElement('div');
      d.className = 'confetti';
      d.style.left = Math.random() * w + 'px';
      d.style.top  = '-10px';
      d.style.background = colors[i % colors.length];
      d.style.animationDelay = (Math.random() * 0.25) + 's';
      d.style.animationDuration = (0.9 + Math.random() * 0.8) + 's';
      box.appendChild(d);
    }
    setTimeout(() => box.classList.add('hidden'), 1600);
  }

  function showSuccess(id){
    hide('scan-loading-' + id);
    hide('scan-qr-wrap-' + id);
    hide('scan-code-wrap-' + id);
    clearErr(id);
    setText('scan-state-' + id, 'CONNECTED');
    show('scan-success-' + id);
    launchConfetti('scan-confetti-' + id);

    // beri waktu user melihat indikator sukses, lalu refresh
    setTimeout(() => window.location.reload(), 1200);
  }

  function startScan(id, name){
    // Reset
    setText('scan-name-' + id, name || '');
    setText('scan-state-' + id, 'MEMULAI…');
    clearErr(id);
    hide('scan-qr-wrap-' + id);
    hide('scan-code-wrap-' + id);
    hide('scan-success-' + id);
    show('scan-loading-' + id);

    if (polls[id]) clearInterval(polls[id]);
    pollStarted[id]  = false;
    pollDeadline[id] = Date.now() + 45_000; // 45s
    qrLastLoad[id]   = 0;

    // Fire-and-forget start
    fetch(`{{ url('waha-senders') }}/${id}/qr-start`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(async (res) => {
      let js = {}; try { js = await res.json(); } catch(e) {}
      if ((!res.ok || js.success === false) && !pollStarted[id]) {
        const msg = js.error || js.message || `HTTP ${res.status}`;
        showErr(id, `Gagal memanggil endpoint QR: ${msg}`);
      }
    })
    .catch(() => { if (!pollStarted[id]) showErr(id, 'Tidak bisa menghubungi server untuk memulai sesi.'); });

    // Poll
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
          showErr(id, 'Sesi gagal. Coba ulangi Scan / Connect.');
          if (polls[id]) { clearInterval(polls[id]); polls[id] = null; }
          return;
        }

        if (state === 'SCAN_QR_CODE') {
          if (js.qr) {
            let src = js.qr;
            if (!/^data:|^https?:\/\//.test(src) && /^[A-Za-z0-9+/=]{200,}$/.test(src)) {
              src = 'data:image/png;base64,' + src;
            }
            const img = $('scan-qr-' + id);
            if (img && img.src !== src) img.src = src;
            show('scan-qr-wrap-' + id);
          } else if (js.qr_url) {
            loadQrThrottled(id, js.qr_url);
          } else {
            hide('scan-qr-wrap-' + id);
          }
        } else {
          hide('scan-qr-wrap-' + id);
        }

        const doneStates = ['CONNECTED','READY','WORKING','OPEN','AUTHENTICATED','ONLINE','LOGGED_IN','RUNNING'];
        if (js.connected === true || doneStates.includes(state)) {
          if (polls[id]) { clearInterval(polls[id]); polls[id] = null; }
          showSuccess(id);
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
