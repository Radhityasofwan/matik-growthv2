@extends('layouts.app')

@section('title', 'Waha Senders')

@section('content')
<div class="container mx-auto px-6 py-8" x-data="page()">

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
                <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <div class="sm:flex sm:items-center sm:justify-between mb-4">
        <div>
            <h3 class="text-gray-200 text-3xl font-medium">Waha Senders</h3>
            <p class="mt-1 text-sm text-gray-400">Kelola nomor pengirim & verifikasi sesi lewat Scan QR.</p>
        </div>
        <button type="button" class="btn btn-primary" @click="openCreate()">Tambah Sender</button>
    </div>

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
                            @if(!empty($s->description))
                                <div class="text-xs opacity-70">{{ $s->description }}</div>
                            @endif
                        </td>
                        <td>{{ $s->number }}</td>
                        <td class="font-mono text-xs">
                            {{ $s->session_name ?? $s->session ?? 'default' }}
                        </td>
                        <td>
                            <span class="badge {{ $s->is_active ? 'badge-success' : 'badge-ghost' }}">
                                {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex gap-2 justify-end">
                                <button class="btn btn-xs" @click="openEdit(@js($s))">Edit</button>

                                <button class="btn btn-xs btn-outline"
                                        @click="openScan({ id: {{ $s->id }} , name: @js($s->name) })">
                                    Scan / Connect
                                </button>

                                <form method="POST" action="{{ route('waha-senders.set-default', $s) }}"
                                      x-on:submit.prevent="post($el)">
                                    @csrf
                                    <button class="btn btn-xs btn-outline">Jadikan Default</button>
                                </form>

                                <form method="POST" action="{{ route('waha-senders.destroy', $s) }}"
                                      x-on:submit.prevent="confirmDelete($el)">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-error">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-10 opacity-70">Belum ada sender.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3">
            {{ $senders->links() }}
        </div>
    </div>

    {{-- Modal Create/Edit --}}
    <div class="modal" :class="{ 'modal-open': modalForm.open }" @keydown.escape.window="closeForm()">
        <div class="modal-box max-w-xl">
            <h3 class="font-bold text-lg" x-text="modalForm.id ? 'Edit Sender' : 'Tambah Sender'"></h3>

            <form :action="modalForm.id ? routes.update(modalForm.id) : routes.store"
                  method="POST" x-ref="form" x-on:submit.prevent="submitForm()">
                @csrf
                <template x-if="modalForm.id">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                <div class="mt-4 grid grid-cols-1 gap-3">
                    <div>
                        <label class="label"><span class="label-text">Nama</span></label>
                        <input type="text" name="name" class="input input-bordered w-full"
                               x-model="modalForm.name" required>
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Deskripsi (opsional)</span></label>
                        <input type="text" name="description" class="input input-bordered w-full"
                               x-model="modalForm.description">
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Nomor WhatsApp</span></label>
                        <input type="text" name="number" class="input input-bordered w-full"
                               x-model="modalForm.number" placeholder="628xxxx" required>
                    </div>

                    {{-- Session: otomatis saat tersambung. Tidak bisa diedit. --}}
                    <input type="hidden" name="session" :value="modalForm.session">

                    <div class="flex items-center gap-6 mt-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" x-model="modalForm.is_active">
                            <span>Aktif</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_default" x-model="modalForm.is_default">
                            <span>Jadikan Default</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Nama sesi akan terisi <em>otomatis</em> setelah perangkat
                        tersambung melalui Scan QR.</p>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn" @click="closeForm()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Scan / Connect --}}
    <div class="modal" :class="{ 'modal-open': modalScan.open }" @keydown.escape.window="closeScan()">
        <div class="modal-box max-w-2xl">
            <div class="flex items-start justify-between">
                <h3 class="font-bold text-lg">
                    Scan / Connect
                    <span class="opacity-70" x-text="modalScan.name ? `— ${modalScan.name}` : ''"></span>
                </h3>
                <button class="btn btn-ghost btn-xs" @click="closeScan()">✕</button>
            </div>

            <template x-if="modalScan.loading">
                <div class="py-10 text-center">
                    <span class="loading loading-spinner loading-lg"></span>
                    <div class="mt-3 text-sm">Memeriksa status sesi...</div>
                </div>
            </template>

            <template x-if="modalScan.error">
                <div class="alert alert-error my-4">
                    <div x-text="modalScan.error"></div>
                </div>
            </template>

            <div x-show="modalScan.qr" class="mt-3">
                <p class="text-sm mb-2">Scan QR berikut menggunakan WhatsApp di perangkat Anda.</p>
                <img :src="modalScan.qr" alt="QR Code" class="w-72 h-72 mx-auto rounded shadow border" />
            </div>

            <div x-show="modalScan.state" class="mt-4">
                <div class="badge" :class="modalScan.state==='CONNECTED' ? 'badge-success' : 'badge-warning'">
                    <span x-text="modalScan.state"></span>
                </div>
            </div>

            <div class="modal-action">
                <button class="btn" @click="closeScan()">Tutup</button>
            </div>
        </div>
    </div>

</div>

{{-- Alpine Logic --}}
<script>
function page() {
    return {
        routes: {
            store:  "{{ route('waha-senders.store') }}",
            update(id){ return "{{ url('waha-senders') }}/" + id; },
            qrStart(id){ return "{{ url('waha-senders') }}/" + id + "/qr-start"; },
            qrStatus(id){ return "{{ url('waha-senders') }}/" + id + "/qr-status"; },
            qrLogout(id){ return "{{ url('waha-senders') }}/" + id + "/qr-logout"; },
        },

        modalForm: { open:false, id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false },
        modalScan: { open:false, id:null, name:'', loading:false, error:'', qr:'', state:'', poll:null },

        /* ---------- Create/Edit ---------- */
        openCreate(){
            this.modalForm = { open:true, id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false };
        },
        openEdit(s){
            this.modalForm = {
                open:true,
                id: s.id,
                name: s.name ?? '',
                description: s.description ?? '',
                number: s.number ?? '',
                session: '', // lock: akan otomatis setelah konek
                is_active: !!s.is_active,
                is_default: !!s.is_default,
            };
        },
        closeForm(){ this.modalForm.open = false; },

        submitForm(){
            const formEl = this.$refs.form;
            const fd = new FormData(formEl);

            // pastikan boolean terkirim
            fd.set('is_active', this.modalForm.is_active ? 1 : 0);
            fd.set('is_default', this.modalForm.is_default ? 1 : 0);

            fetch(formEl.action, {
                method: this.modalForm.id ? 'POST' : 'POST',
                headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body: fd
            })
            .then(r => r.ok ? r.json() : r.json().then(e => Promise.reject(e)))
            .then(() => window.location.reload())
            .catch(err => alert(err?.message ?? 'Gagal menyimpan.'));
        },

        post(el){
            fetch(el.action, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} })
                .then(()=>window.location.reload());
        },
        confirmDelete(el){
            if (!confirm('Hapus sender ini?')) return;
            fetch(el.action, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body:new URLSearchParams({'_method':'DELETE'}) })
                .then(()=>window.location.reload());
        },

        /* ---------- Scan / Connect ---------- */
        openScan({id, name}){
            this.modalScan = { open:true, id, name, loading:true, error:'', qr:'', state:'', poll:null };

            // 1) start session (minta QR)
            fetch(this.routes.qrStart(id), {
                method:'POST',
                headers:{ 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' }
            })
            .catch(() => {/* biarkan lanjut ke polling meski start gagal */})
            .finally(() => {
                // 2) mulai polling status/qr
                this.startPolling();
            });
        },
        startPolling(){
            const tick = () => {
                fetch(this.routes.qrStatus(this.modalScan.id), { headers:{'Accept':'application/json'} })
                    .then(r => r.json())
                    .then(({success, data, message}) => {
                        this.modalScan.loading = false;
                        if (!success) {
                            this.modalScan.error = message ?? 'Gagal memanggil endpoint QR.';
                            return;
                        }
                        this.modalScan.state = data.state ?? '';
                        this.modalScan.qr    = data.qr    ?? '';

                        if (this.modalScan.state === 'CONNECTED') {
                            // selesai: hentikan polling dan refresh tabel
                            clearInterval(this.modalScan.poll);
                            this.modalScan.poll = null;
                            setTimeout(()=>{ window.location.reload(); }, 600);
                        }
                    })
                    .catch(() => {
                        this.modalScan.loading = false;
                        this.modalScan.error = 'Gagal mengambil status.';
                    });
            };

            tick(); // first call segera
            this.modalScan.poll = setInterval(tick, 2000);
        },
        closeScan(){
            if (this.modalScan.poll) clearInterval(this.modalScan.poll);
            this.modalScan.open = false;
        },
    }
}
</script>
@endsection
