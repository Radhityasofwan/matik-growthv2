@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4" x-data="senderPage()">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Waha Senders</h1>
        <button type="button" class="btn btn-primary" @click="openCreate()">Tambah Sender</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error mb-4">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
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
                        <tr id="row-{{ $s->id }}">
                            <td>@if($s->is_default) ⭐ @endif</td>
                            <td>{{ $s->name }}</td>
                            <td>{{ $s->number }}</td>
                            <td class="font-mono text-xs">{{ $s->session }}</td>
                            <td>
                                <form method="POST" action="{{ route('waha-senders.toggle-active', $s) }}" x-on:submit.prevent="post($el)">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm {{ $s->is_active ? 'btn-success' : 'btn-secondary' }}"
                                            :class="acting && 'btn-disabled'">
                                        <span class="loading loading-spinner loading-xs mr-1" x-show="acting"></span>
                                        {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-right space-x-2">
                                <button type="button" class="btn btn-sm" @click="openEdit(@js($s))">Edit</button>

                                <form method="POST" action="{{ route('waha-senders.set-default', $s) }}" class="inline" x-on:submit.prevent="post($el)">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline" :class="acting && 'btn-disabled'">
                                        <span class="loading loading-spinner loading-xs mr-1" x-show="acting"></span>
                                        Jadikan Default
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('waha-senders.destroy', $s) }}" class="inline" x-on:submit.prevent="confirmDelete($el)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" :class="acting && 'btn-disabled'">
                                        <span class="loading loading-spinner loading-xs mr-1" x-show="acting"></span>
                                        Hapus
                                    </button>
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

    {{-- Modal Create/Edit - menggunakan modal-toggle agar kompatibel --}}
    <input type="checkbox" id="senderModal" class="modal-toggle" x-model="showModal">
    <div class="modal" x-cloak>
        <div class="modal-box max-w-lg">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg" x-text="form.id ? 'Edit Sender' : 'Tambah Sender'"></h3>
                <label for="senderModal" class="btn btn-sm btn-circle btn-ghost">✕</label>
            </div>

            <form :action="form.id ? routes.update(form.id) : routes.store"
                  method="POST"
                  x-ref="form"
                  x-on:submit.prevent="submit()"
                  class="mt-4 space-y-3">
                @csrf
                <template x-if="form.id">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="label">Nama</label>
                    <input id="sender_name" type="text" name="name" class="input input-bordered w-full" x-model="form.name" required>
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

                <div class="modal-action">
                    <label for="senderModal" class="btn" :class="saving && 'btn-disabled'">Batal</label>
                    <button type="submit" class="btn btn-primary" :class="saving && 'btn-disabled'">
                        <span class="loading loading-spinner loading-xs mr-1" x-show="saving"></span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
        <label class="modal-backdrop" for="senderModal">Close</label>
    </div>
</div>

{{-- Alpine helpers --}}
<script>
/* Pastikan fungsi tersedia secara global & terdaftar ke Alpine */
window.senderPage = function () {
    return {
        showModal: false,
        saving: false,
        acting: false,
        form: { id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false },
        routes: {
            store: "{{ route('waha-senders.store') }}",
            update(id){ return "{{ url('waha-senders') }}/" + id; },
        },

        openCreate(){
            this.form = { id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false };
            this.showModal = true;
            this.$nextTick(() => document.getElementById('sender_name')?.focus());
        },
        openEdit(s){
            this.form = {
                id:s.id,
                name:s.name ?? '',
                description:s.description ?? '',
                number:s.number ?? '',
                session:s.session ?? '',
                is_active: !!s.is_active,
                is_default: !!s.is_default
            };
            this.showModal = true;
            this.$nextTick(() => document.getElementById('sender_name')?.focus());
        },

        async submit(){
            if (this.saving) return;
            this.saving = true;
            const formEl = this.$refs.form;
            const fd = new FormData(formEl);
            fd.set('is_active', this.form.is_active ? 1 : 0);
            fd.set('is_default', this.form.is_default ? 1 : 0);
            if (this.form.id && !fd.get('_method')) fd.set('_method', 'PUT');

            try {
                const res = await fetch(formEl.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: fd
                });
                const ct = res.headers.get('content-type') || '';
                const data = ct.includes('application/json') ? await res.json() : {};
                if (!res.ok) {
                    const msg = data.message || (data.errors ? Object.values(data.errors)[0][0] : null) || 'Gagal menyimpan.';
                    throw new Error(msg);
                }
                window.location.reload();
            } catch (e) {
                alert(e.message || 'Gagal menyimpan.');
            } finally {
                this.saving = false;
            }
        },

        async post(el){
            if (this.acting) return;
            this.acting = true;
            try {
                const fd = new FormData(el);
                const res = await fetch(el.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: fd
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Aksi gagal.');
                window.location.reload();
            } catch (e) {
                alert(e.message || 'Aksi gagal.');
            } finally {
                this.acting = false;
            }
        },

        async confirmDelete(el){
            if (!confirm('Hapus sender ini?')) return;
            if (this.acting) return;
            this.acting = true;
            try {
                const fd = new FormData(el);
                fd.set('_method', 'DELETE');
                const res = await fetch(el.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: fd
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Tidak bisa menghapus.');
                window.location.reload();
            } catch (e) {
                alert(e.message || 'Tidak bisa menghapus.');
            } finally {
                this.acting = false;
            }
        }
    }
};

/* Daftarkan ke Alpine kalau inisialisasi lebih dini */
document.addEventListener('alpine:init', () => {
    if (window.Alpine) Alpine.data('senderPage', window.senderPage);
});
</script>
@endsection
