@extends('layouts.app')

@section('title', 'Waha Senders')

@section('content')
<div class="container mx-auto px-6 py-8" x-data="page()">

    @if (session('success'))
        <div class="alert alert-success shadow mb-6"><div>{{ session('success') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow mb-6">
            <div><strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <div class="sm:flex sm:items-center sm:justify-between mb-4">
        <div>
            <h3 class="text-3xl font-medium">Waha Senders</h3>
            <p class="mt-1 text-sm opacity-70">Kelola nomor pengirim & verifikasi sesi lewat Scan QR.</p>
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
                            <button class="btn btn-xs" @click="openEdit(@js($s))">Edit</button>
                            <button class="btn btn-xs btn-outline"
                                    @click="openScan({ id: {{ $s->id }}, name: @js($s->name) })">
                                Scan / Connect
                            </button>
                            <form method="POST" action="{{ route('waha-senders.set-default', $s) }}"
                                  x-on:submit.prevent="post($el)">
                                @csrf
                                <button class="btn btn-xs btn-outline">Jadikan Default</button>
                            </form>
                            <form method="POST" action="{{ route('waha-senders.destroy', $s) }}"
                                  x-on:submit.prevent="confirmDelete($el)">
                                @csrf @method('DELETE')
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
        <div class="px-4 py-3">{{ $senders->links() }}</div>
    </div>

    {{-- Modal Create/Edit (MINIMAL) --}}
    <div class="modal" :class="{ 'modal-open': form.open }" @keydown.escape.window="closeForm()">
        <div class="modal-box max-w-xl">
            <h3 class="font-bold text-lg" x-text="form.id ? 'Edit Sender' : 'Tambah Sender'"></h3>

            <form :action="form.id ? routes.update(form.id) : routes.store"
                  method="POST" x-ref="formEl" x-on:submit.prevent="submitForm()">
                @csrf
                <template x-if="form.id">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                <div class="mt-4 grid grid-cols-1 gap-3">
                    <div>
                        <label class="label"><span class="label-text">Nama</span></label>
                        <input type="text" name="name" class="input input-bordered w-full"
                               x-model="form.name" required>
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Deskripsi (opsional)</span></label>
                        <input type="text" name="description" class="input input-bordered w-full"
                               x-model="form.description">
                    </div>
                    <div class="mt-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_default" x-model="form.is_default">
                            <span>Jadikan Default</span>
                        </label>
                    </div>
                    <p class="text-xs opacity-70">Nomor & session akan terisi otomatis setelah tersambung via QR.</p>
                </div>

                <div class="modal-action">
                    <button type="button" class="btn" @click="closeForm()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Scan / Connect --}}
    <div class="modal" :class="{ 'modal-open': scan.open }" @keydown.escape.window="closeScan()">
        <div class="modal-box max-w-2xl">
            <div class="flex items-start justify-between">
                <h3 class="font-bold text-lg">
                    Scan / Connect <span class="opacity-70" x-text="scan.name ? '— '+scan.name : ''"></span>
                </h3>
                <button class="btn btn-ghost btn-xs" @click="closeScan()">✕</button>
            </div>

            <template x-if="scan.loading">
                <div class="py-10 text-center">
                    <span class="loading loading-spinner loading-lg"></span>
                    <div class="mt-3 text-sm">Memeriksa status sesi...</div>
                </div>
            </template>

            <template x-if="scan.error">
                <div class="alert alert-error my-4">
                    <div x-text="scan.error"></div>
                </div>
            </template>

            <div x-show="scan.qr" class="mt-3">
                <p class="text-sm mb-2">Scan QR berikut menggunakan WhatsApp.</p>
                <img :src="scan.qr" alt="QR Code" class="w-72 h-72 mx-auto rounded shadow border" />
            </div>

            <div x-show="scan.state" class="mt-4">
                <div class="badge" :class="scan.state==='CONNECTED' ? 'badge-success' : 'badge-warning'">
                    <span x-text="scan.state"></span>
                </div>
            </div>

            <div class="modal-action">
                <button class="btn" @click="closeScan()">Tutup</button>
            </div>
        </div>
    </div>

</div>

<script>
function page(){
    return {
        routes: {
            store:  "{{ route('waha-senders.store') }}",
            update(id){ return "{{ url('waha-senders') }}/" + id; },
            qrStart(id){ return "{{ url('waha-senders') }}/" + id + "/qr-start"; },
            qrStatus(id){ return "{{ url('waha-senders') }}/" + id + "/qr-status"; },
        },
        form: { open:false, id:null, name:'', description:'', is_default:false },
        scan: { open:false, id:null, name:'', loading:false, error:'', qr:'', state:'', poll:null },

        openCreate(){ this.form = { open:true, id:null, name:'', description:'', is_default:false }; },
        openEdit(s){
            this.form = { open:true, id:s.id, name:s.name ?? '', description:s.description ?? '', is_default: !!s.is_default };
        },
        closeForm(){ this.form.open=false; },

        submitForm(){
            const el = this.$refs.formEl;
            const fd = new FormData(el);
            fd.set('is_default', this.form.is_default ? 1 : 0);

            fetch(el.action, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body:fd })
                .then(r => r.ok ? (r.headers.get('content-type')?.includes('json') ? r.json() : {}) : r.json().then(e=>Promise.reject(e)))
                .then(()=>window.location.reload())
                .catch(e => alert(e?.message ?? 'Gagal menyimpan.'));
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

        /* Scan / Connect */
        openScan({id, name}){
            this.scan = { open:true, id, name, loading:true, error:'', qr:'', state:'', poll:null };

            fetch(this.routes.qrStart(id), { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} })
                .catch(()=>{})
                .finally(()=> this.startPolling());
        },
        startPolling(){
            const tick = () => {
                fetch(this.routes.qrStatus(this.scan.id), { headers:{'Accept':'application/json'} })
                    .then(r=>r.json())
                    .then(({success, data, message})=>{
                        this.scan.loading=false;
                        if(!success){ this.scan.error = message ?? 'Gagal memanggil endpoint QR.'; return; }
                        this.scan.state = data.state ?? '';
                        this.scan.qr    = data.qr ?? '';
                        if (this.scan.state === 'CONNECTED') {
                            clearInterval(this.scan.poll); this.scan.poll=null;
                            setTimeout(()=>window.location.reload(), 600);
                        }
                    })
                    .catch(()=>{ this.scan.loading=false; this.scan.error='Gagal mengambil status.'; });
            };
            tick(); this.scan.poll = setInterval(tick, 2000);
        },
        closeScan(){ if(this.scan.poll) clearInterval(this.scan.poll); this.scan.open=false; },
    }
}
</script>
@endsection
