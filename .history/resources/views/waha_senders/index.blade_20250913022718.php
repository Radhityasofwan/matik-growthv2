@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4" x-data="senderPage()">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Waha Senders</h1>
        <button class="btn btn-primary" @click="openCreate()">Tambah Sender</button>
    </div>

    {{-- alert --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Default</th>
                        <th>Nama</th>
                        <th>Nomor</th>
                        <th>Session</th>
                        <th>Aktif</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($senders as $s)
                        <tr>
                            <td>@if($s->is_default) ⭐ @endif</td>
                            <td>{{ $s->name }}</td>
                            <td>{{ $s->number }}</td>
                            <td class="font-mono text-xs">{{ $s->session }}</td>
                            <td>
                                <form method="POST" action="{{ route('waha-senders.toggle-active', $s) }}" x-on:submit.prevent="post($el)">
                                    @csrf
                                    <button class="btn btn-sm {{ $s->is_active ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-right space-x-2">
                                <button class="btn btn-sm" @click="openEdit(@js($s))">Edit</button>

                                <form method="POST" action="{{ route('waha-senders.set-default', $s) }}" class="inline" x-on:submit.prevent="post($el)">
                                    @csrf
                                    <button class="btn btn-sm btn-outline">
                                        Jadikan Default
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('waha-senders.destroy', $s) }}" class="inline" x-on:submit.prevent="confirmDelete($el)">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $senders->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Create/Edit --}}
    <div class="modal" :class="{ 'modal-open': showModal }">
        <div class="modal-box">
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
                    <div>
                        <label class="label">Session</label>
                        <input type="text" name="session" class="input input-bordered w-full font-mono text-sm" x-model="form.session" required>
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
                </div>

                <div class="modal-action">
                    <button type="button" class="btn" @click="close()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Alpine helpers --}}
<script>
function senderPage() {
    return {
        showModal: false,
        form: { id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false },
        routes: {
            store: "{{ route('waha-senders.store') }}",
            update(id){ return "{{ url('waha-senders') }}/" + id; },
        },
        openCreate(){ this.form = { id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false }; this.showModal = true; },
        openEdit(s){ this.form = { id:s.id, name:s.name, description:s.description, number:s.number, session:s.session, is_active:!!s.is_active, is_default:!!s.is_default }; this.showModal = true; },
        close(){ this.showModal = false; },
        submit(){
            const formEl = this.$refs.form;
            const fd = new FormData(formEl);
            // checkbox manual (agar boolean terkirim saat unchecked)
            fd.set('is_active', this.form.is_active ? 1 : 0);
            fd.set('is_default', this.form.is_default ? 1 : 0);

            fetch(formEl.action, {
                method: (this.form.id ? 'POST' : 'POST'),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: fd
            })
            .then(r => r.ok ? r.json() : r.json().then(e => Promise.reject(e)))
            .then(() => { window.location.reload(); })
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
        }
    }
}
</script>
@endsection
