{{-- potongan atas file tetap seperti sebelumnya --}}
@extends('layouts.app')
@section('title', 'WhatsApp - Broadcast & Senders')

@section('content')
@php
    use Illuminate\Support\Facades\Schema;
    $senders = $senders
        ?? \App\Models\WahaSender::query()
            ->when(Schema::hasColumn('waha_senders','is_active'), fn($q)=>$q->where('is_active', true))
            ->when(Schema::hasColumn('waha_senders','is_default'), fn($q)=>$q->orderByDesc('is_default'))
            ->when(Schema::hasColumn('waha_senders','name'), fn($q)=>$q->orderBy('name'), fn($q)=>$q->orderBy('id'))
            ->get();
@endphp

<div class="container mx-auto px-6 py-8" x-data="broadcastPage()">
    {{-- Alerts (tetap) --}}
    @if (session('success'))
        <div class="alert alert-success shadow mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m7 10a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Terdapat kesalahan!</strong></span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">WhatsApp</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kirim broadcast & kelola nomor pengirim (sender).</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="btn btn-secondary" @click="openSenderModal()">Kelola Sender</button>
            <button type="button" class="btn" @click="refreshSenders()">Refresh Sender</button>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Form --}}
        <div class="xl:col-span-2 card bg-base-100 shadow">
            <div class="card-body">
                <form method="POST" action="{{ route('whatsapp.broadcast.store') }}" x-ref="form">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label"><span class="label-text">Kirim Dari</span></label>
                            <select class="select select-bordered w-full"
                                    name="sender_id"
                                    x-model="form.sender_id"
                                    required>
                                <template x-if="senders.length === 0">
                                    <option value="">Belum ada sender aktif</option>
                                </template>
                                <template x-for="s in senders" :key="s.id">
                                    <option :value="s.id" x-text="senderLabel(s)"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="label"><span class="label-text">Mode Pesan</span></label>
                            <div class="flex items-center gap-6 mt-2">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="mode" value="custom" class="radio" x-model="form.mode">
                                    <span>Custom Message</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="mode" value="template" class="radio" x-model="form.mode">
                                    <span>Template</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Recipients --}}
                    <div class="mt-5">
                        <label class="label"><span class="label-text">Daftar Penerima</span></label>
                        <textarea name="recipients"
                                  class="textarea textarea-bordered w-full h-36"
                                  placeholder="Satu baris satu penerima. Contoh:
628123456789
Budi, 628123456789
628123456789 | Budi"
                                  x-model="form.recipients"
                                  @input="updatePreview()"
                                  required></textarea>
                        <p class="text-xs text-gray-500 mt-1">Format: <code>628xxxx</code>, <code>Nama, 628xxxx</code>, atau <code>628xxxx | Nama</code></p>
                    </div>

                    {{-- Custom Message --}}
                    <div class="mt-5" x-show="form.mode === 'custom'">
                        <label class="label"><span class="label-text">Pesan</span></label>
                        <textarea name="message"
                                  class="textarea textarea-bordered w-full h-32"
                                  placeholder="Tulis pesan. Gunakan @{{name}} untuk menyapa penerima."
                                  x-model="form.message"
                                  @input="updatePreview()"
                                  :required="form.mode==='custom'"></textarea>
                    </div>

                    {{-- Template --}}
                    <div class="mt-5" x-show="form.mode === 'template'">
                        <label class="label"><span class="label-text">Template</span></label>
                        <select name="template_id"
                                class="select select-bordered w-full"
                                x-model="form.template_id"
                                :required="form.mode==='template'">
                            <option value="">— Pilih Template —</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name ?? ('Template #'.$tpl->id) }}</option>
                            @endforeach
                        </select>
                        <label class="label mt-4"><span class="label-text">Params (opsional, JSON object)</span></label>
                        <textarea name="params_json"
                                  class="textarea textarea-bordered w-full h-24 font-mono text-xs"
                                  placeholder='{"name":"Budi","order_id":"INV-001"}'
                                  x-model="form.params_json"></textarea>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <button type="button" class="btn" @click="resetForm()">Reset</button>
                        <button type="submit"
                                class="btn btn-primary text-white"
                                :class="{ 'btn-disabled': !canSubmit }"
                                :disabled="!canSubmit">
                            Kirim Broadcast
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Preview (tetap) --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title">Pratinjau</h3>
                <p class="text-xs text-gray-500 mb-2">Contoh untuk 3 penerima pertama.</p>
                <template x-if="preview.items.length === 0">
                    <div class="text-sm text-gray-400">Belum ada penerima valid.</div>
                </template>
                <ul class="space-y-3" x-show="preview.items.length > 0">
                    <template x-for="p in preview.items.slice(0,3)" :key="p.phone">
                        <li class="p-3 rounded border border-base-300">
                            <div class="text-sm font-medium" x-text="p.name + ' (' + p.phone + ')'"></div>
                            <div class="text-sm text-gray-600 whitespace-pre-line mt-2" x-show="form.mode==='custom'" x-text="p.preview"></div>
                            <div class="text-xs text-gray-500 mt-1" x-show="form.mode==='template'">Mode template: isi pesan ditentukan oleh template WA.</div>
                        </li>
                    </template>
                </ul>

                <div class="divider my-4"></div>
                <h4 class="font-semibold">Tips</h4>
                <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                    <li>Gunakan <code class="font-mono">@{{name}}</code> untuk menyapa penerima (mode Custom).</li>
                    <li>Nomor harus format internasional (cth: 628...).</li>
                    <li>Kelola sender lewat tombol <strong>Kelola Sender</strong> (tambah/edit/hapus/aktif/nonaktif, set default).</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ========= Modal Kelola Sender ========= --}}
    <div class="modal" :class="{'modal-open': senderModalOpen}">
        <div class="modal-box max-w-4xl">
            <h3 class="font-bold text-lg mb-1">Kelola Sender</h3>
            <p class="text-xs text-gray-500 mb-4">Tambah/edit/hapus sender, atur aktif & default.</p>

            <div class="overflow-x-auto border rounded">
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
                        <tr><td colspan="6" class="text-center text-sm text-gray-500 p-4">Belum ada data</td></tr>
                    </template>
                    <template x-for="s in senders" :key="s.id">
                        <tr>
                            <td x-text="s.is_default ? '⭐' : ''"></td>
                            <td x-text="s.name"></td>
                            <td x-text="s.number"></td>
                            <td class="font-mono text-xs" x-text="s.session"></td>
                            <td>
                                <form :action="routes.toggleActive(s.id)" method="POST" x-on:submit.prevent="post($el)">
                                    @csrf
                                    <button type="submit" class="btn btn-xs" :class="s.is_active ? 'btn-success' : 'btn-ghost'">
                                        <span x-text="s.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-right space-x-2">
                                <button class="btn btn-xs" @click="openEdit(s)">Edit</button>
                                <form :action="routes.setDefault(s.id)" method="POST" class="inline" x-on:submit.prevent="post($el)">
                                    @csrf
                                    <button class="btn btn-xs btn-outline">Jadikan Default</button>
                                </form>
                                <form :action="routes.destroy(s.id)" method="POST" class="inline" x-on:submit.prevent="confirmDelete($el)">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-error">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-4 bg-base-200 rounded">
                <h4 class="font-semibold mb-2" x-text="formSender.id ? 'Edit Sender' : 'Tambah Sender'"></h4>
                <form :action="formSender.id ? routes.update(formSender.id) : routes.store"
                      method="POST" x-ref="senderForm" x-on:submit.prevent="submitSender()">
                    @csrf
                    <template x-if="formSender.id"><input type="hidden" name="_method" value="PUT"></template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="label"><span class="label-text">Nama</span></label>
                            <input type="text" name="name" class="input input-bordered w-full" x-model="formSender.name" required>
                        </div>
                        <div>
                            <label class="label"><span class="label-text">Deskripsi</span></label>
                            <input type="text" name="description" class="input input-bordered w-full" x-model="formSender.description">
                        </div>
                        <div>
                            <label class="label"><span class="label-text">Nomor</span></label>
                            <input type="text" name="number" class="input input-bordered w-full" x-model="formSender.number" placeholder="628xxxx" required>
                        </div>
                        <div>
                            <label class="label"><span class="label-text">Session</span></label>
                            <input type="text" name="session" class="input input-bordered w-full font-mono text-sm" x-model="formSender.session" required>
                        </div>
                        <div class="flex items-center gap-6 mt-1">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" x-model="formSender.is_active">
                                <span>Aktif</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_default" x-model="formSender.is_default">
                                <span>Default</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <button type="button" class="btn" @click="resetSenderForm()">Bersihkan</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>

            <div class="modal-action">
                <button class="btn" @click="closeSenderModal()">Tutup</button>
            </div>
        </div>
    </div>
    {{-- ========= /Modal Kelola Sender ========= --}}
</div>

<script>
function broadcastPage(){
    return {
        // data awal dari server
        senders: @json($senders->values()),
        form: { sender_id:'', mode:'custom', recipients:'', message:'', template_id:'', params_json:'' },
        preview: { items: [] },
        senderModalOpen: false,
        formSender: { id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false },

        get canSubmit(){
            const hasSender = !!this.form.sender_id;
            const hasRecipients = this.preview.items.length > 0;
            if (this.form.mode === 'custom') return hasSender && hasRecipients && !!(this.form.message||'').trim();
            return hasSender && hasRecipients && !!this.form.template_id;
        },

        // UI helpers
        senderLabel(s){ return (s.is_default ? '⭐ ' : '') + (s.name ?? ('Sender #' + s.id)) + ' (' + s.number + ')'; },
        sanitizePhone(v){ return (v||'').replace(/\D+/g,''); },

        // parse recipients + preview
        parseRecipients(){
            const raw = (this.form.recipients || '').split(/\r?\n/).map(l => l.trim()).filter(Boolean);
            const out = [];
            for (const line of raw){
                let name=null, phone=null;
                if (line.includes(',')){
                    const [a,b] = line.split(',',2).map(x=>x.trim());
                    const da=this.sanitizePhone(a), db=this.sanitizePhone(b);
                    if (da.length>=7){ phone=da; name=b; }
                    else if (db.length>=7){ phone=db; name=a; }
                } else if (line.includes('|')) {
                    const [a,b] = line.split('|',2).map(x=>x.trim());
                    const da=this.sanitizePhone(a), db=this.sanitizePhone(b);
                    if (da.length>=7){ phone=da; name=b; }
                    else if (db.length>=7){ phone=db; name=a; }
                } else {
                    const d=this.sanitizePhone(line);
                    if (d.length>=7) phone=d;
                }
                if (phone){
                    const nm = (name && name.length) ? name : ('User ' + phone.slice(-4));
                    const prev = this.form.mode==='custom' ? this.fillTemplate(this.form.message, nm) : '';
                    out.push({name:nm, phone, preview:prev});
                }
            }
            return out;
        },
        fillTemplate(tpl, name){
            return (tpl || '')
                .replace(/\{\{\s*name\s*\}\}/gi, name)
                .replace(/\{\{\s*nama\s*\}\}/gi, name)
                .replace(/\{\{\s*nama_pelanggan\s*\}\}/gi, name);
        },
        updatePreview(){ this.preview.items = this.parseRecipients(); },
        resetForm(){ this.form = { sender_id:'', mode:'custom', recipients:'', message:'', template_id:'', params_json:'' }; this.preview.items=[]; },

        // ====== Kelola Sender ======
        openSenderModal(){ this.senderModalOpen = true; },
        closeSenderModal(){ this.senderModalOpen = false; this.resetSenderForm(); },
        resetSenderForm(){ this.formSender = { id:null, name:'', description:'', number:'', session:'', is_active:true, is_default:false }; },
        openEdit(s){ this.formSender = JSON.parse(JSON.stringify(s)); this.senderModalOpen = true; },

        // refresh daftar sender (dropdown + tabel) TANPA reload
        refreshSenders(){
            fetch("{{ route('waha-senders.index') }}", { headers:{ 'Accept':'application/json' }})
                .then(r => r.json())
                .then(json => {
                    if (json?.success) {
                        this.senders = json.data || [];
                        // jika sender_id sebelumnya tidak ada lagi, kosongkan
                        if (!this.senders.find(x => String(x.id) === String(this.form.sender_id))) {
                            this.form.sender_id = '';
                        }
                    }
                })
                .catch(()=>alert('Gagal memuat daftar sender.'));
        },

        submitSender(){
            const el = this.$refs.senderForm;
            const fd = new FormData(el);
            fd.set('is_active', this.formSender.is_active ? 1 : 0);
            fd.set('is_default', this.formSender.is_default ? 1 : 0);

            fetch(el.action, {
                method: (this.formSender.id ? 'POST' : 'POST'),
                headers: { 'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' },
                body: fd
            })
            .then(r => r.json())
            .then(json => {
                if (json?.success) {
                    this.refreshSenders();
                    this.resetSenderForm(); // bersihkan form agar siap tambah lagi
                } else {
                    alert('Gagal menyimpan sender.');
                }
            })
            .catch(() => alert('Gagal menyimpan sender.'));
        },

        post(el){
            fetch(el.action, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} })
                .then(()=>this.refreshSenders());
        },
        confirmDelete(el){
            if (!confirm('Hapus sender ini?')) return;
            fetch(el.action, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body:new URLSearchParams({'_method':'DELETE'}) })
                .then(()=>this.refreshSenders());
        },
        routes: {
            store: "{{ route('waha-senders.store') }}",
            update(id){ return "{{ url('waha-senders') }}/"+id; },
            destroy(id){ return "{{ url('waha-senders') }}/"+id; },
            toggleActive(id){ return "{{ url('waha-senders') }}/"+id+"/toggle-active"; },
            setDefault(id){ return "{{ url('waha-senders') }}/"+id+"/set-default"; },
        },
    }
}
</script>
@endsection
