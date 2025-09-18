@extends('layouts.app')
@section('title', 'Waha Senders')

@section('content')
<div class="container mx-auto px-6 py-8">
    @if (session('success'))
        <div class="alert alert-success mb-6">{{ session('success') }}</div>
    @endif

    <div class="sm:flex sm:items-center sm:justify-between mb-4">
        <div>
            <h3 class="text-3xl font-medium">Waha Senders</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola nomor pengirim & verifikasi sesi lewat Scan QR.</p>
        </div>
        <a href="{{ route('waha-senders.index') }}#create" class="btn btn-primary">Tambah Sender</a>
    </div>

    <div class="overflow-x-auto">
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
            @foreach ($senders as $s)
                <tr>
                    <td>@if($s->is_default) ⭐ @endif</td>
                    <td>
                        <div class="font-semibold">{{ $s->name }}</div>
                        <div class="text-xs text-gray-500">{{ $s->description }}</div>
                    </td>
                    <td class="font-mono text-sm">{{ $s->number }}</td>
                    <td class="font-mono text-xs">{{ $s->session }}</td>
                    <td>
                        <span class="badge {{ $s->is_active ? 'badge-success' : 'badge-warning' }}">
                            {{ $s->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td class="text-right space-x-2">
                        {{-- Edit (session dikunci/readonly di form) --}}
                        <a href="{{ route('waha-senders.index') }}#edit_{{ $s->id }}" class="btn btn-xs">Edit</a>

                        {{-- Scan / Connect --}}
                        <button class="btn btn-xs btn-outline"
                                data-sender-id="{{ $s->id }}"
                                data-session="{{ $s->session }}"
                                onclick="openQrModal(this)">
                            Scan / Connect
                        </button>

                        {{-- Jadikan Default --}}
                        <form method="POST" action="{{ route('waha-senders.set-default', $s) }}" class="inline"
                              onsubmit="return postAndReload(event, this);">
                            @csrf
                            <button class="btn btn-xs">Jadikan Default</button>
                        </form>

                        {{-- Hapus --}}
                        <form method="POST" action="{{ route('waha-senders.destroy', $s) }}" class="inline"
                              onsubmit="return confirmDelete(event, this);">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-error">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="mt-4">{{ $senders->links() }}</div>
    </div>
</div>

{{-- QR MODAL --}}
<div id="qrModal" class="modal">
  <div class="modal-box w-11/12 max-w-3xl">
    <h3 class="font-bold text-lg">Scan / Connect</h3>
    <div id="qrState" class="mt-2 text-sm text-gray-500">Memeriksa status sesi…</div>

    <div id="qrWrap" class="mt-4 flex items-center justify-center min-h-[280px] bg-gray-50 rounded">
        <div id="qrLoading" class="text-sm text-gray-500">Memuat QR…</div>
        <img id="qrImage" src="" alt="QR" class="hidden max-h-[360px]">
    </div>

    <div class="modal-action">
      <button class="btn" onclick="closeQrModal()">Tutup</button>
    </div>
  </div>
  <a class="modal-backdrop" onclick="closeQrModal()">Close</a>
</div>

<script>
const CSRF = '{{ csrf_token() }}';

function postAndReload(e, form){
    e.preventDefault();
    fetch(form.action, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF, 'Accept':'application/json'}})
      .then(()=>location.reload());
    return false;
}
function confirmDelete(e, form){
    e.preventDefault();
    if(!confirm('Hapus sender ini?')) return false;
    fetch(form.action, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
      body: new URLSearchParams({'_method':'DELETE'})}).then(()=>location.reload());
    return false;
}

/* =======================
   QR FLOW
   - GET qr-status: cek status + ambil QR jika perlu
   - POST qr-start : paksa start session kalau belum jalan
   - Poll tiap 2s sampai connected
======================= */
let qrTimer = null;
let qrSenderId = null;

function openQrModal(btn){
    qrSenderId = btn.dataset.senderId;
    document.getElementById('qrModal').classList.add('modal-open');
    startQrFlow();
}
function closeQrModal(){
    document.getElementById('qrModal').classList.remove('modal-open');
    if(qrTimer){ clearTimeout(qrTimer); qrTimer = null; }
}

function setQrLoading(text){
    document.getElementById('qrState').textContent = text || 'Memeriksa status sesi…';
    document.getElementById('qrLoading').classList.remove('hidden');
    document.getElementById('qrImage').classList.add('hidden');
}

function setQrImage(dataUri){
    document.getElementById('qrLoading').classList.add('hidden');
    const img = document.getElementById('qrImage');
    img.src = dataUri;
    img.classList.remove('hidden');
}

function startQrFlow(){
    setQrLoading('Memeriksa status sesi…');
    pollStatus(true);
}

function pollStatus(tryStartFirst=false){
    if(!qrSenderId) return;
    const statusUrl = `{{ url('waha-senders') }}/${qrSenderId}/qr-status`;
    fetch(statusUrl, {headers:{'Accept':'application/json'}})
      .then(r=>r.json())
      .then(j=>{
          const d = j.data || {};
          if(d.connected){
              document.getElementById('qrState').textContent = 'Tersambung. Anda dapat menutup dialog ini.';
              setQrLoading('Tersambung.');
              setTimeout(()=>location.reload(), 800);
              return;
          }
          if(d.requires_qr){
              document.getElementById('qrState').textContent = 'Silakan scan QR di bawah dengan WhatsApp.';
              if(d.qr){ setQrImage(d.qr); }
              qrTimer = setTimeout(()=>pollStatus(false), 2000);
              return;
          }
          // tidak jelas/ belum start -> coba start lalu cek lagi
          if(tryStartFirst){
              const startUrl = `{{ url('waha-senders') }}/${qrSenderId}/qr-start`;
              fetch(startUrl, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
                .then(r=>{
                    if(!r.ok) throw new Error('Start failed: '+r.status);
                    return r.json();
                })
                .then(()=>{ qrTimer = setTimeout(()=>pollStatus(false), 1500); })
                .catch(err=>{
                    document.getElementById('qrState').textContent = 'Gagal memulai sesi ('+err.message+').';
                });
          } else {
              qrTimer = setTimeout(()=>pollStatus(false), 2000);
          }
      })
      .catch(()=>{
          document.getElementById('qrState').textContent = 'Gagal memanggil endpoint QR.';
      });
}
</script>
@endsection
