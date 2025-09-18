@extends('layouts.app')
@section('title', 'Waha Senders')

@section('content')
<style>[x-cloak]{display:none!important}</style>
<div class="container mx-auto px-6 py-8" x-data="sendersPage()" x-cloak>

    @if (session('success'))
        <div class="alert alert-success shadow mb-6">
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow mb-6">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Waha Senders</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola nomor pengirim & verifikasi sesi lewat Scan QR.</p>
        </div>
        <div>
            <button class="btn btn-primary" @click="openCreate()">Tambah Sender</button>
        </div>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
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
                    @php $sess = $s->session ?? $s->session_name; @endphp
                    <tr>
                        <td>@if($s->is_default) ⭐ @endif</td>
                        <td>
                            <div class="font-semibold">{{ $s->name }}</div>
                            <div class="text-xs text-gray-500">{{ $s->description }}</div>
                        </td>
                        <td class="font-mono">{{ $s->number }}</td>
                        <td class="font-mono text-xs">
                            {{ $sess ?: '— belum diverifikasi —' }}
                        </td>
                        <td>
                            <span class="badge {{ $s->is_active ? 'badge-success' : 'badge-ghost' }}">
                                {{ $s->is_active ? 'Aktif (Terhubung)' : 'Tidak Aktif' }}
                            </span>
                        </td>
                        <td class="text-right space-x-2">
                        <button type="button" class="btn btn-sm" @click='openEdit(@json($s))'>Edit</button>
                            <button type="button" class="btn btn-sm btn-outline" @click="openQr(@js($s))">
                                Scan / Connect
                            </button>

                            <form method="POST" action="{{ route('waha-senders.set-default', $s) }}" class="inline" x-on:submit.prevent="post($el)">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline">Jadikan Default</button>
                            </form>

                            <form method="POST" action="{{ route('waha-senders.destroy', $s) }}" class="inline" x-on:submit.prevent="confirmDelete($el)">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-5 py-5 border-t">
            {{ $senders->links() }}
        </div>
    </div>

    {{-- Modal Create/Edit (tanpa field Session) --}}
    <div class="modal" :class="{ 'modal-open': showModal }">
        <div class="modal-box max-w-xl">
            <h3 class="font-bold text-lg" x-text="form.id ? 'Edit Sender' : 'Tambah Sender'"></h3>

            <form :action="form.id ? routes.update(form.id) : routes.store" method="POST" x-ref="form" x-on:submit.prevent="submit()">
                @csrf
                <template x-if="form.id">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="label">Nama</label>
                        <input type="text" name="name" class="input input-bordered w-full" x-model="form.name" required>
                    </div>
                    <div>
                        <label class="label">Deskripsi</label>
                        <input type="text" name="description" class="input input-bordered w-full" x-model="form.description">
                    </div>
                    <div>
                        <label class="label">Nomor</label>
                        <input type="text" name="number" class="input input-bordered w-full" x-model="form.number" placeholder="628xxxx" required>
                    </div>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" x-model="form.is_active">
                            <span>Aktif</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_default" x-model="form.is_default">
                            <span>Jadikan Default</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500">Session akan dibuat otomatis saat klik <strong>Scan / Connect</strong>.</p>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn" @click="close()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal QR --}}
    <div class="modal" :class="{ 'modal-open': showQR }">
        <div class="modal-box max-w-2xl">
            <button type="button" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" @click="closeQR()">✕</button>
            <h3 class="font-bold text-lg">Scan / Connect</h3>
            <p class="text-sm text-gray-500 mb-3">
                Session: <span class="font-mono" x-text="qr.session || '-'"></span>
            </p>

            <template x-if="qr.status === 'CONNECTED'">
                <div class="alert alert-success mb-4">Perangkat sudah terhubung.</div>
            </template>

            <template x-if="qr.status !== 'CONNECTED'">
                <div class="mb-4">
                    <div class="flex items-center gap-3 mb-2">
                        <button type="button" class="btn btn-outline btn-sm" :disabled="qr.loading" @click="restartSession()">Start / Restart Session</button>
                        <span class="text-xs text-gray-500" x-text="qr.message"></span>
                    </div>
                    <div class="border rounded p-4" style="min-height:280px; display:flex; align-items:center; justify-content:center; background: var(--fallback-b2, oklch(var(--b2)))">
                        <img :src="qr.image" alt="QR Code" class="max-h-64" x-show="qr.image">
                        <div class="text-sm text-gray-500" x-show="!qr.image">QR belum tersedia. Klik “Start / Restart Session”, lalu tunggu beberapa detik…</div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Auto refresh tiap 4–5 detik.</div>
                </div>
            </template>

            <div class="modal-action">
                <button type="button" class="btn" @click="closeQR()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function sendersPage() {
    return {
        showModal:false, showQR:false, poller:null,
        form:{ id:null, name:'', description:'', number:'', is_active:false, is_default:false },
        qr:{ senderId:null, session:'', image:null, status:'', message:'', loading:false },
        routes:{
            store: "{{ route('waha-senders.store') }}",
            update(id){ return "{{ url('waha-senders') }}/" + id; },
            qr(id){ return "{{ url('waha-senders') }}/" + id + "/qr"; },
            restart(id){ return "{{ url('waha-senders') }}/" + id + "/restart"; },
        },

        openCreate(){ this.form = { id:null, name:'', description:'', number:'', is_active:false, is_default:false }; this.showModal = true; },
        openEdit(s){ this.form = { id:s.id, name:s.name, description:s.description, number:s.number, is_active:!!s.is_active, is_default:!!s.is_default }; this.showModal = true; },
        close(){ this.showModal = false; },

        submit(){
            const formEl = this.$refs.form;
            const fd = new FormData(formEl);
            fd.set('is_active', this.form.is_active ? 1 : 0);
            fd.set('is_default', this.form.is_default ? 1 : 0);
            fetch(formEl.action, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body:fd })
                .then(r => r.ok ? r.json() : r.json().then(Promise.reject))
                .then(() => location.reload())
                .catch(err => alert(err?.message ?? 'Gagal menyimpan.'));
        },

        post(el){ fetch(el.action, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} }).then(()=>location.reload()); },
        confirmDelete(el){ if(!confirm('Hapus sender ini?')) return; fetch(el.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:new URLSearchParams({'_method':'DELETE'})}).then(()=>location.reload()); },

        /* QR modal */
        openQr(s){
            this.qr.senderId = s.id;
            this.qr.session  = s.session ?? s.session_name ?? '';
            this.qr.image    = null; this.qr.status=''; this.qr.message='Memeriksa status…';
            this.showQR = true;
            this.fetchQr();
            this.startPolling();
        },
        closeQR(){ this.showQR = false; this.stopPolling(); },
        startPolling(){ this.stopPolling(); this.poller = setInterval(()=>this.fetchQr(), 4500); },
        stopPolling(){ if(this.poller){ clearInterval(this.poller); this.poller=null; } },
        fetchQr(){
            if(!this.qr.senderId) return;
            fetch(this.routes.qr(this.qr.senderId), { headers:{'Accept':'application/json'} })
                .then(r => r.json())
                .then(j => {
                    this.qr.status  = j.status ?? '';
                    this.qr.message = j.message ?? '';
                    this.qr.image   = j.qr ?? null;
                    this.qr.session = j.session ?? this.qr.session;
                    if (this.qr.status === 'CONNECTED') this.stopPolling();
                })
                .catch(()=>{ /* ignore */ });
        },
        restartSession(){
            if(!this.qr.senderId) return;
            this.qr.loading = true;
            fetch(this.routes.restart(this.qr.senderId), { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} })
                .then(r => r.json()).then(j => { this.qr.session = j.session ?? this.qr.session; this.fetchQr(); })
                .finally(()=>{ this.qr.loading = false; });
        },
    }
}
</script>
@endsection
