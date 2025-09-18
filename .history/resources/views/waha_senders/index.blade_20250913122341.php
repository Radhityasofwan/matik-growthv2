@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4" x-data="senderPage()" x-init="load()">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Waha Senders</h1>
        <div class="flex items-center gap-2">
            <input type="text" class="input input-bordered" placeholder="Cari nama / nomor…" x-model.debounce.300ms="q">
            <button class="btn btn-ghost btn-sm" :class="{'loading': loading}" @click="load()" :disabled="loading">Muat Ulang</button>
            <button class="btn btn-primary" @click="openCreate()">Tambah Sender</button>
        </div>
    </div>

    {{-- alert server (fallback non-JS) --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body overflow-x-auto">
            <template x-if="error">
                <div class="alert alert-error mb-4" x-text="error"></div>
            </template>

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
                    <template x-if="loading">
                        <tr><td colspan="6" class="text-center py-8">Memuat data…</td></tr>
                    </template>

                    <template x-for="s in paged" :key="s.id">
                        <tr>
                            <td class="text-lg" x-text="s.is_default ? '⭐' : ''"></td>
                            <td x-text="s.name"></td>
                            <td x-text="s.number"></td>
                            <td class="font-mono text-xs break-all" x-text="s.session"></td>
                            <td>
                                <button class="btn btn-sm"
                                    :class="s.is_active ? 'btn-success' : 'btn-secondary'"
                                    @click="toggleActive(s)"
                                    :disabled="busyId === s.id">
                                    <span x-show="busyId === s.id" class="loading loading-spinner loading-xs mr-1"></span>
                                    <span x-text="s.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                </button>
                            </td>
                            <td class="text-right space-x-2">
                                <button class="btn btn-sm" @click="openEdit(s)">Edit</button>

                                <button class="btn btn-sm btn-outline"
                                    @click="setDefault(s)"
                                    :disabled="s.is_default || busyId === s.id">
                                    <span x-show="busyId === s.id" class="loading loading-spinner loading-xs mr-1"></span>
                                    Jadikan Default
                                </button>

                                <button class="btn btn-sm btn-danger"
                                    @click="confirmDelete(s)"
                                    :disabled="busyId === s.id">
                                    <span x-show="busyId === s.id" class="loading loading-spinner loading-xs mr-1"></span>
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!loading && filtered.length === 0">
                        <tr><td colspan="6" class="text-center py-8">Tidak ada data.</td></tr>
                    </template>
                </tbody>
            </table>

            {{-- pagination client-side --}}
            <div class="mt-4 flex items-center justify-between" x-show="pages > 1">
                <div>Menampilkan <span x-text="startIdx+1"></span>–<span x-text="endIdx"></span> dari <span x-text="filtered.length"></span></div>
                <div class="join">
                    <button class="btn btn-sm join-item" @click="prev()" :disabled="page===1">«</button>
                    <button class="btn btn-sm join-item" disabled x-text="page + ' / ' + pages"></button>
                    <button class="btn btn-sm join-item" @click="next()" :disabled="page===pages">»</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Create/Edit --}}
    <div class="modal" :class="{ 'modal-open': showModal }" @keydown.escape.window="close()">
        <div class="modal-box w-full max-w-2xl">
            <h3 class="font-bold text-lg" x-text="form.id ? 'Edit Sender' : 'Tambah Sender'"></h3>

            <template x-if="formError">
                <div class="alert alert-error my-3" x-text="formError"></div>
            </template>

            <form x-ref="form" @submit.prevent="submit()">
                @csrf
                <div class="mt-4 grid md:grid-cols-2 gap-3">
                    <div class="md:col-span-1">
                        <label class="label">Nama</label>
                        <input type="text" name="name" class="input input-bordered w-full" x-model="form.name" required maxlength="100">
                    </div>
                    <div class="md:col-span-1">
                        <label class="label">Nomor</label>
                        <input type="text" name="number" class="input input-bordered w-full" x-model="form.number" placeholder="628xxxx" required maxlength="30"
                               @input="form.number = (form.number||'').replace(/\D+/g,'')">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Deskripsi</label>
                        <input type="text" name="description" class="input input-bordered w-full" x-model="form.description" maxlength="255">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Session</label>
                        <input type="text" name="session" class="input input-bordered w-full font-mono text-sm" x-model="form.session" required maxlength="150">
                    </div>
                    <div class="md:col-span-2 flex items-center gap-6">
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
                    <button type="button" class="btn" @click="close()" :disabled="saving">Batal</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="saving" class="loading loading-spinner loading-xs mr-1"></span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Alpine helpers --}}
<script>
function senderPage() {
    // === ROUTES sesuai web.php ===
    const ROUTE_INDEX_JSON   = @json(route('waha-senders.index', ['json' => 1]));
    const ROUTE_STORE        = @json(route('waha-senders.store'));
    // resource param = {waha_sender}
    const ROUTE_UPDATE_TPL   = @json(route('waha-senders.update',  ['waha_sender' => '__ID__']));
    const ROUTE_DESTROY_TPL  = @json(route('waha-senders.destroy', ['waha_sender' => '__ID__']));
    // custom param = {wahaSender}
    const ROUTE_TOGGLE_TPL   = @json(route('waha-senders.toggle-active', ['wahaSender' => '__ID__']));
    const ROUTE_SETDEF_TPL   = @json(route('waha-senders.set-default',   ['wahaSender' => '__ID__']));
    const CSRF               = @json(csrf_token());
    const r = (tpl, id) => tpl.replace('__ID__', id);

    return {
        // state
        loading:false, saving:false, error:'', formError:'', busyId:null,
        // data
        rows:[], q:'', page:1, perPage:10,
        // modal
        showModal:false,
        form:{ id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false },

        // computed
        get filtered(){
            if(!this.q) return this.rows;
            const k=this.q.toLowerCase();
            return this.rows.filter(s =>
                (s.name||'').toLowerCase().includes(k) ||
                (s.number||'').toLowerCase().includes(k) ||
                (s.session||'').toLowerCase().includes(k)
            );
        },
        get pages(){ return Math.max(1, Math.ceil(this.filtered.length/this.perPage)); },
        get startIdx(){ return (this.page-1)*this.perPage; },
        get endIdx(){ return Math.min(this.filtered.length, this.startIdx+this.perPage); },
        get paged(){ return this.filtered.slice(this.startIdx, this.endIdx); },

        // lifecycle
        async load(){
            this.loading=true; this.error='';
            try{
                const res = await fetch(ROUTE_INDEX_JSON, { headers:{Accept:'application/json'} });
                const json = await res.json().catch(()=>({}));
                if(!res.ok) throw json;
                this.rows = (json.data||[]).map(x=>({
                    id:x.id, name:x.name ?? '(tanpa nama)', number:x.number ?? '-', session:x.session,
                    is_active:!!x.is_active, is_default:!!x.is_default, description:x.description ?? ''
                }));
                this.rows.sort((a,b)=> a.is_default!==b.is_default ? (a.is_default?-1:1) : (a.name||'').localeCompare(b.name||''));
                this.page=1;
            }catch(e){ this.error = e?.message || 'Gagal memuat data.'; }
            finally{ this.loading=false; }
        },

        // pagination
        next(){ if(this.page<this.pages) this.page++; },
        prev(){ if(this.page>1) this.page--; },

        // modal
        openCreate(){ this.form={ id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false }; this.formError=''; this.showModal=true; },
        openEdit(s){ this.form={ id:s.id, name:s.name, description:s.description||'', number:s.number||'', session:s.session, is_active:!!s.is_active, is_default:!!s.is_default }; this.formError=''; this.showModal=true; },
        close(){ if(!this.saving) this.showModal=false; },

        // CREATE/UPDATE
        async submit(){
            this.saving=true; this.formError='';
            try{
                const fd = new FormData();
                fd.set('name', this.form.name);
                fd.set('description', this.form.description || '');
                fd.set('number', (this.form.number||'').replace(/\D+/g,''));
                fd.set('session', this.form.session);
                fd.set('is_active', this.form.is_active?1:0);
                fd.set('is_default', this.form.is_default?1:0);

                const isEdit = !!this.form.id;
                const url = isEdit ? r(ROUTE_UPDATE_TPL, this.form.id) : ROUTE_STORE;
                if(isEdit) fd.set('_method','PUT');

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
                    body: fd
                });
                const json = await res.json().catch(()=>({}));
                if(!res.ok){
                    if(json?.errors){
                        const first = Object.values(json.errors)[0];
                        throw new Error(Array.isArray(first)?first[0]:first);
                    }
                    throw new Error(json?.message || 'Gagal menyimpan.');
                }

                await this.load();
                this.showModal=false;
            }catch(e){ this.formError = e?.message || 'Gagal menyimpan.'; }
            finally{ this.saving=false; }
        },

        // TOGGLE ACTIVE (POST -> waha-senders/{wahaSender}/toggle-active)
        async toggleActive(s){
            this.busyId = s.id;
            try{
                const res = await fetch(r(ROUTE_TOGGLE_TPL, s.id), {
                    method:'POST',
                    headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' }
                });
                const json = await res.json().catch(()=>({}));
                if(!res.ok) throw new Error(json?.message || 'Gagal memperbarui status.');
                const row = this.rows.find(x=>x.id===s.id);
                if(row) row.is_active = !!json.data?.is_active;
            }catch(e){ alert(e?.message || 'Gagal memperbarui status.'); }
            finally{ this.busyId=null; }
        },

        // SET DEFAULT (POST -> waha-senders/{wahaSender}/set-default)
        async setDefault(s){
            if(s.is_default) return;
            this.busyId = s.id;
            try{
                const res = await fetch(r(ROUTE_SETDEF_TPL, s.id), {
                    method:'POST',
                    headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' }
                });
                const json = await res.json().catch(()=>({}));
                if(!res.ok) throw new Error(json?.message || 'Gagal set default.');

                this.rows.forEach(x=>x.is_default=false);
                const row = this.rows.find(x=>x.id===s.id);
                if(row){ row.is_default = true; row.is_active = true; }
                this.rows.sort((a,b)=> a.is_default!==b.is_default ? (a.is_default?-1:1) : (a.name||'').localeCompare(b.name||''));
            }catch(e){ alert(e?.message || 'Gagal set default.'); }
            finally{ this.busyId=null; }
        },

        // DESTROY (DELETE -> waha-senders/{waha_sender})
        async confirmDelete(s){
            if(!confirm('Hapus sender ini?')) return;
            this.busyId = s.id;
            try{
                const body = new URLSearchParams({ _method:'DELETE' });
                const res = await fetch(r(ROUTE_DESTROY_TPL, s.id), {
                    method:'POST',
                    headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json', 'Content-Type':'application/x-www-form-urlencoded' },
                    body
                });
                if(!res.ok){
                    const json = await res.json().catch(()=>({}));
                    throw new Error(json?.message || 'Gagal menghapus.');
                }
                this.rows = this.rows.filter(x=>x.id!==s.id);
            }catch(e){ alert(e?.message || 'Gagal menghapus.'); }
            finally{ this.busyId=null; }
        },
    }
}
</script>
@endsection
