@extends('layouts.app')

@section('title', 'WhatsApp — Broadcast & Senders')

@section('content')
<div class="container mx-auto px-6 py-8" x-data="broadcastPage()">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6">
             <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><strong>Error!</strong> Mohon periksa kembali form Anda.</span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">WhatsApp</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kirim broadcast & kelola nomor pengirim (sender).</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-2">
            <button type="button" class="btn btn-secondary btn-sm" @click="openSenderModal()">Kelola Sender</button>
            <button type="button" class="btn btn-ghost btn-sm" @click="loadSenders()">Refresh Sender</button>
        </div>
    </div>

    {{-- Form Broadcast --}}
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form utama --}}
        <div class="lg:col-span-2">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
                <form id="broadcastForm" method="POST" action="{{ route('whatsapp.broadcast.store') }}" @submit="onSubmit">
                    @csrf

                    {{-- Sender --}}
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="label font-medium">Kirim Dari</label>
                            <select name="sender_id" x-ref="senderSelect" class="select select-bordered w-full" required>
                                <option value="">Memuat daftar sender...</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Default sender akan ditandai ⭐.</p>
                        </div>

                        {{-- Mode --}}
                        <div>
                            <label class="label font-medium">Mode Pesan</label>
                            <div class="flex items-center gap-6 mt-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="mode" value="custom" x-model="mode" checked>
                                    <span>Custom Message</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="mode" value="template" x-model="mode">
                                    <span>Template</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Custom Message --}}
                    <div class="mt-5" x-show="mode === 'custom'">
                        <label class="label font-medium">Isi Pesan</label>
                        <textarea
                            name="message"
                            x-model="message"
                            class="textarea textarea-bordered w-full h-36"
                            placeholder="Gunakan @{{name}} untuk nama penerima."
                        >{{ old('message') }}</textarea>
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                            <span>Placeholder: <code>@{{name}}</code>, <code>@{{nama}}</code>, <code>@{{nama_pelanggan}}</code></span>
                            <span x-text="`Karakter: ${message.length}`"></span>
                        </div>
                    </div>

                    {{-- Template --}}
                    <div class="mt-5" x-show="mode === 'template'">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="label font-medium">Pilih Template</label>
                                <select name="template_id" class="select select-bordered w-full">
                                    <option value="">-- Pilih Template --</option>
                                    @foreach($templates as $tpl)
                                        <option value="{{ $tpl->id }}" @selected(old('template_id') == $tpl->id)>
                                            {{ $tpl->name ?? ('Template #'.$tpl->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label font-medium">Template Params (JSON)</label>
                                <textarea
                                    name="params_json"
                                    class="textarea textarea-bordered w-full h-24"
                                    placeholder='contoh: {"name":"Budi","code":"1234"}'
                                >{{ old('params_json') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Recipients --}}
                    <div class="mt-5">
                        <label class="label font-medium">Daftar Penerima</label>
                        <textarea
                            name="recipients"
                            x-model="recipientsRaw"
                            class="textarea textarea-bordered w-full h-44"
                            placeholder="Satu baris satu penerima. Contoh:
628123456789
Budi, 628123456789
628123456789 | Budi"
                        >{{ old('recipients') }}</textarea>
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                            <span>Format: <code>628xxxx</code>, <code>Nama, 628xxxx</code>, <code>628xxxx | Nama</code></span>
                            <span x-text="`Ditemukan: ${parsed.length} nomor valid`"></span>
                        </div>
                    </div>

                    {{-- Aksi --}}
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="reset" class="btn" @click="resetPreview()">Reset</button>
                        <button type="submit" class="btn btn-primary" :disabled="submitting" x-text="submitting ? 'Mengirim...' : 'Kirim Broadcast'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel Preview & Tips --}}
        <div>
            <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
                <h4 class="font-semibold mb-3">Pratinjau</h4>
                <template x-if="mode === 'custom'">
                    <div>
                        <p class="text-xs text-gray-500 mb-2">Contoh untuk 3 penerima pertama:</p>
                        <div class="space-y-2">
                            <template x-for="r in previewList" :key="r.phone">
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded">
                                    <div class="text-xs text-gray-500">Ke: <span x-text="r.phone"></span> (<span x-text="r.name"></span>)</div>
                                    <div class="mt-1 text-sm whitespace-pre-wrap" x-text="message.replaceAll('@{{name}}', r.name).replaceAll('@{{nama}}', r.name).replaceAll('@{{nama_pelanggan}}', r.name)"></div>
                                </div>
                            </template>
                            <p class="text-xs text-gray-500" x-show="parsed.length === 0">Belum ada nomor valid.</p>
                        </div>
                    </div>
                </template>

                <div class="mt-6">
                    <h4 class="font-semibold mb-2">Tips</h4>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>Gunakan <code>@{{name}}</code> untuk menyapa penerima.</li>
                        <li>Nomor harus format internasional (cth: <code>628…</code>).</li>
                        <li>Kelola sender (tambah/edit/hapus) lewat tombol <strong>Kelola Sender</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL KELOLA SENDER ===================== --}}
    <div class="modal" :class="{'modal-open': showSenderModal}" x-cloak>
        <div class="modal-box w-11/12 max-w-4xl">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg">Kelola Sender</h3>
                <button class="btn btn-sm btn-circle btn-ghost" @click="closeSenderModal()">✕</button>
            </div>

            {{-- Toolbar --}}
            <div class="mt-4 flex items-center justify-between">
                <div class="text-sm text-gray-500">Total: <span x-text="senders.length"></span> sender</div>
                <button class="btn btn-sm btn-primary" @click="openCreate()">Tambah Sender</button>
            </div>

            {{-- Table --}}
            <div class="mt-4 overflow-x-auto">
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
                        <template x-if="senders.length === 0">
                            <tr><td colspan="6" class="text-center py-8 text-sm text-gray-500">Belum ada sender.</td></tr>
                        </template>
                        <template x-for="s in senders" :key="s.id">
                            <tr>
                                <td x-text="s.is_default ? '⭐' : ''"></td>
                                <td x-text="s.name"></td>
                                <td x-text="s.number"></td>
                                <td class="font-mono text-xs" x-text="s.session"></td>
                                <td>
                                    <button class="btn btn-xs" :class="s.is_active ? 'btn-success' : 'btn-secondary'" @click="toggleActive(s)">
                                        <span x-text="s.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                    </button>
                                </td>
                                <td class="text-right space-x-2">
                                    <button class="btn btn-xs" @click="openEdit(s)">Edit</button>
                                    <button class="btn btn-xs btn-outline" @click="setDefault(s)">Jadikan Default</button>
                                    <button class="btn btn-xs btn-error" @click="removeSender(s)">Hapus</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Form Create/Edit --}}
            <div class="mt-6 p-4 rounded bg-gray-50 dark:bg-gray-700">
                <h4 class="font-semibold mb-3" x-text="form.id ? 'Edit Sender' : 'Tambah Sender'"></h4>
                <form @submit.prevent="submitSender">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nama</label>
                            <input type="text" class="input input-bordered w-full" x-model="form.name" required>
                        </div>
                        <div>
                            <label class="label">Nomor</label>
                            <input type="text" class="input input-bordered w-full" x-model="form.number" placeholder="628xxxx" required>
                        </div>
                        <div>
                            <label class="label">Session</label>
                            <input type="text" class="input input-bordered w-full font-mono text-sm" x-model="form.session" required>
                        </div>
                        <div>
                            <label class="label">Deskripsi</label>
                            <input type="text" class="input input-bordered w-full" x-model="form.description">
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-6">
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_active"><span>Aktif</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_default"><span>Jadikan Default</span></label>
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button type="button" class="btn" @click="openCreate()">Reset</button>
                        <button type="submit" class="btn btn-primary" :disabled="savingSender" x-text="savingSender ? 'Menyimpan...' : 'Simpan Sender'"></button>
                    </div>
                </form>
            </div>

        </div>
        <a class="modal-backdrop" @click="closeSenderModal()">Close</a>
    </div>
    {{-- =================== END MODAL KELOLA SENDER =================== --}}

</div>

{{-- Alpine Helpers --}}
<script>
function broadcastPage() {
    return {
        // state
        mode: 'custom',
        message: @json(old('message', '')),
        recipientsRaw: @json(old('recipients', '')),
        parsed: [],
        previewList: [],
        submitting: false,

        // sender modal state
        showSenderModal: false,
        senders: [],
        savingSender: false,
        form: { id:null, name:'', number:'', session:'', description:'', is_active:true, is_default:false },

        // routes
        routes: {
            index:  "{{ route('waha-senders.index') }}",
            store:  "{{ route('waha-senders.store') }}",
            update: "{{ url('waha-senders') }}/",            // + id
            destroy:"{{ url('waha-senders') }}/",            // + id
            toggle: "{{ url('waha-senders') }}/",            // + id + '/toggle-active'
            setdef: "{{ url('waha-senders') }}/",            // + id + '/set-default'
            broadcastStore: "{{ route('whatsapp.broadcast.store') }}",
            csrf: "{{ csrf_token() }}",
        },

        // ----------------- Broadcast -----------------
        parseRecipients() {
            const rows = (this.recipientsRaw || '').split(/\r?\n/).map(r => r.trim()).filter(Boolean);
            const out = [];
            for (const line of rows) {
                let name = null, phone = null;
                if (line.includes(',')) {
                    const [a,b] = line.split(',',2).map(s => s.trim());
                    const da = (a||'').replace(/\D+/g,''); const db = (b||'').replace(/\D+/g,'');
                    if (da.length >= 7) { phone = da; name = b; }
                    else if (db.length >= 7) { phone = db; name = a; }
                } else if (line.includes('|')) {
                    const [a,b] = line.split('|',2).map(s => s.trim());
                    const da = (a||'').replace(/\D+/g,''); const db = (b||'').replace(/\D+/g,'');
                    if (da.length >= 7) { phone = da; name = b; }
                    else if (db.length >= 7) { phone = db; name = a; }
                } else {
                    const d = line.replace(/\D+/g,'');
                    if (d.length >= 7) phone = d;
                }
                if (phone) out.push({ phone, name: name || phone.slice(-4) });
            }
            this.parsed = out;
            this.previewList = out.slice(0,3);
        },

        resetPreview() {
            this.message = '';
            this.recipientsRaw = '';
            this.parseRecipients();
        },

        async onSubmit(e) {
            this.submitting = true;
            // biarkan form submit normal (server side validation & redirect)
            // tapi tetap pastikan parsed sudah ada supaya user aware
            this.parseRecipients();
            e.target.submit();
        },

        async loadSenders() {
            const sel = this.$refs.senderSelect;
            sel.innerHTML = '<option value="">Memuat...</option>';
            try {
                const res = await fetch(this.routes.index, { headers: { 'Accept':'application/json' } });
                const json = await res.json();
                const data = json?.data ?? [];
                this.senders = data;

                sel.innerHTML = '<option value="">-- Pilih Nomor Pengirim --</option>';
                for (const s of data) {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = (s.is_default ? '⭐ ' : '') + `${s.name} (${s.number})`;
                    sel.appendChild(opt);
                }
                const def = data.find(x => x.is_default);
                if (def) sel.value = def.id;
            } catch (e) {
                console.error(e);
                sel.innerHTML = '<option value="">Gagal memuat sender</option>';
            }
        },

        // ----------------- Sender Modal -----------------
        openSenderModal() { this.showSenderModal = true; this.openCreate(); this.loadSenders(); },
        closeSenderModal() { this.showSenderModal = false; },

        openCreate(){ this.form = { id:null, name:'', number:'', session:'', description:'', is_active:true, is_default:false }; },
        openEdit(s){ this.form = { id:s.id, name:s.name, number:s.number, session:s.session, description:s.description || '', is_active:!!s.is_active, is_default:!!s.is_default }; },

        async submitSender(){
            this.savingSender = true;
            try{
                const fd = new FormData();
                for (const [k,v] of Object.entries(this.form)) fd.append(k, (k==='is_active'||k==='is_default') ? (v?1:0) : v ?? '');
                const url = this.form.id ? (this.routes.update + this.form.id) : this.routes.store;
                const method = this.form.id ? 'POST' : 'POST'; // Laravel spoof _method for PUT
                if (this.form.id) fd.append('_method','PUT');
                fd.append('_token', this.routes.csrf);

                const res = await fetch(url, { method, body: fd, headers:{ 'Accept':'application/json' } });
                if (!res.ok) throw new Error('Gagal menyimpan sender.');
                this.openCreate();
                await this.loadSenders();
            } catch(e){ alert(e.message || e); }
            finally{ this.savingSender = false; }
        },

        async toggleActive(s){
            try{
                const res = await fetch(this.routes.toggle + s.id + '/toggle-active', {
                    method:'POST', headers:{ 'X-CSRF-TOKEN': this.routes.csrf, 'Accept':'application/json' }
                });
                if (!res.ok) throw new Error('Gagal mengubah status.');
                await this.loadSenders();
            } catch(e){ alert(e.message || e); }
        },

        async setDefault(s){
            try{
                const res = await fetch(this.routes.setdef + s.id + '/set-default', {
                    method:'POST', headers:{ 'X-CSRF-TOKEN': this.routes.csrf, 'Accept':'application/json' }
                });
                if (!res.ok) throw new Error('Gagal set default.');
                await this.loadSenders();
            } catch(e){ alert(e.message || e); }
        },

        async removeSender(s){
            if (!confirm('Hapus sender ini?')) return;
            try{
                const res = await fetch(this.routes.destroy + s.id, {
                    method:'POST',
                    headers:{ 'X-CSRF-TOKEN': this.routes.csrf, 'Accept':'application/json' },
                    body: new URLSearchParams({ '_method':'DELETE' })
                });
                if (!res.ok) throw new Error('Gagal menghapus.');
                await this.loadSenders();
            } catch(e){ alert(e.message || e); }
        },

        // init
        init(){
            this.loadSenders();
            this.parseRecipients();
            this.$watch('recipientsRaw', () => this.parseRecipients());
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') this.loadSenders();
            });
        }
    }
}
</script>
@endsection
