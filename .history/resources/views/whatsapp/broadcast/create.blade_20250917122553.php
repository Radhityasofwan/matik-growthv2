@extends('layouts.app')
@section('title', 'WhatsApp – Broadcast')

@section('content')
<div class="container mx-auto py-6">

    {{-- Alerts --}}
    @if (session('error'))
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down"><div>{{ session('error') }}</div></div>
    @endif
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down"><div>{{ session('success') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">WhatsApp Broadcast</h1>
            <p class="mt-1 text-base-content/70">Kirim pesan massal menggunakan WAHA.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('waha-senders.index') }}" class="btn btn-secondary btn-outline btn-sm" target="_blank">Kelola Sender</a>
            <button type="button" class="btn btn-ghost btn-sm" onclick="window.location.reload()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                Refresh Sender
            </button>
        </div>
    </div>

    {{-- Form utama --}}
    {{-- FIXED: Menambahkan x-data utama untuk mengelola seluruh form --}}
    <div x-data="broadcastManager(@json($all_leads), @json($lead_statuses), @json($templates ?? []), '{{ old('recipients') }}', '{{ old('message') }}', '{{ old('params_json','{}') }}')" x-init="init()">
        <form method="POST" action="{{ route('whatsapp.broadcast.store') }}" id="broadcastForm">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Kolom Kiri: Form isian --}}
                <div class="lg:col-span-2 card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up">
                    <div class="card-body">
                            {{-- Kirim Dari --}}
                            <div class="form-control">
                                <label class="label"><span class="label-text">Kirim Dari</span></label>
                                <select name="sender_id" id="senderSelect" class="select select-bordered w-full" required>
                                    @php $opts = collect($senders ?? []); @endphp
                                    @if($opts->isEmpty())
                                        <option value="" selected disabled>Belum ada sender aktif</option>
                                    @else
                                        @foreach ($opts as $s)
                                            @php
                                                $label = $s->name ?? $s->number ?? $s->session ?? ('Sender #'.$s->id);
                                                if (!empty($s->is_default)) $label .= ' — Default';
                                            @endphp
                                            <option value="{{ $s->id }}" @selected($s->is_default)>{{ $label }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- UI Daftar Penerima --}}
                            <div class="mt-4">
                                <label class="label"><span class="label-text">Daftar Penerima</span></label>
                                <div class="tabs tabs-boxed">
                                    <a class="tab" :class="{'tab-active': recipientsTab === 'manual'}" @click="recipientsTab = 'manual'">Input Manual</a>
                                    <a class="tab" :class="{'tab-active': recipientsTab === 'select'}" @click="recipientsTab = 'select'">Pilih dari Leads</a>
                                    <a class="tab" :class="{'tab-active': recipientsTab === 'status'}" @click="recipientsTab = 'status'">Berdasarkan Status</a>
                                </div>

                                {{-- Panel Input Manual --}}
                                <div x-show="recipientsTab === 'manual'" class="mt-2">
                                     <textarea x-model="manualInput" @input.debounce.500ms="updateRecipients" class="textarea textarea-bordered w-full h-36 font-mono text-sm" placeholder="Satu baris satu penerima. Contoh:&#10;628123456789&#10;Budi, 628123456789&#10;628123456789 | Budi"></textarea>
                                     <label class="label"><span class="label-text-alt">Format: <code>628xxxx</code>, <code>Nama, 628xxxx</code>, atau <code>628xxxx | Nama</code></span></label>
                                </div>

                                {{-- Panel Pilih dari Leads --}}
                                <div x-show="recipientsTab === 'select'" class="mt-2 border bg-base-200/50 rounded-box p-4" x-cloak>
                                    <input type="text" x-model="searchTerm" placeholder="Cari nama atau nomor..." class="input input-bordered w-full mb-2">
                                    <div class="max-h-60 overflow-y-auto">
                                        <table class="table table-compact w-full">
                                            <tbody>
                                                <template x-for="lead in filteredLeads" :key="lead.id">
                                                    <tr>
                                                        <td>
                                                            <label class="label cursor-pointer justify-start gap-3">
                                                                <input type="checkbox" :value="lead.id" x-model="selectedLeadIds" @change="updateRecipients" class="checkbox checkbox-primary checkbox-sm">
                                                                <span class="label-text" x-text="lead.name"></span>
                                                            </label>
                                                        </td>
                                                        <td x-text="lead.phone" class="text-right text-base-content/60"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {{-- Panel Berdasarkan Status --}}
                                <div x-show="recipientsTab === 'status'" class="mt-2 border bg-base-200/50 rounded-box p-4" x-cloak>
                                     <p class="text-sm mb-2">Pilih satu atau lebih status lead.</p>
                                     <div class="flex flex-wrap gap-x-4 gap-y-2">
                                         <template x-for="status in availableStatuses" :key="status">
                                            <label class="label cursor-pointer justify-start gap-2">
                                                <input type="checkbox" :value="status" x-model="selectedStatuses" @change="updateRecipients" class="checkbox checkbox-primary checkbox-sm">
                                                <span class="label-text capitalize" x-text="status"></span>
                                            </label>
                                         </template>
                                     </div>
                                </div>

                                {{-- Hidden textarea untuk menyimpan hasil akhir --}}
                                <textarea name="recipients" x-model="finalRecipients" class="hidden" required></textarea>
                            </div>

                            {{-- Mode Pesan (Tabs) --}}
                            <div class="form-control mt-4">
                                <label class="label"><span class="label-text">Mode Pesan</span></label>
                                <div class="tabs tabs-boxed">
                                    <a class="tab" :class="{'tab-active': messageMode === 'custom'}" @click="messageMode = 'custom'">Pesan Custom</a>
                                    <a class="tab" :class="{'tab-active': messageMode === 'template'}" @click="messageMode = 'template'">Gunakan Template</a>
                                    <input type="radio" name="mode" value="custom" class="hidden" :checked="messageMode === 'custom'">
                                    <input type="radio" name="mode" value="template" class="hidden" :checked="messageMode === 'template'">
                                </div>
                            </div>

                            {{-- Konten Pesan Custom --}}
                            <div x-show="messageMode === 'custom'" class="form-control mt-2" x-cloak>
                                <label class="label"><span class="label-text">Isi Pesan</span></label>
                                <textarea name="message" x-model="customMessage" @input.debounce.500ms="renderPreview" class="textarea textarea-bordered w-full h-36" placeholder="Tulis pesan. Gunakan @{{name}} untuk menyapa penerima."></textarea>
                                <label class="label"><span class="label-text-alt">Juga mendukung <code>@{{nama}}</code> dan <code>@{{nama_pelanggan}}</code>.</span></label>
                            </div>

                            {{-- Konten Template --}}
                            <div x-show="messageMode === 'template'" class="mt-2 space-y-4" x-cloak>
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Pilih Template</span></label>
                                    <select name="template_id" x-model="selectedTemplateId" class="select select-bordered w-full">
                                        <option value="" disabled>— Pilih Template —</option>
                                        <template x-for="tpl in templates" :key="tpl.id">
                                            <option :value="tpl.id" x-text="tpl.name + (!tpl.is_active ? ' (nonaktif)' : '')"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="label"><span class="label-text">Parameter Template</span></label>
                                    <div class="space-y-2">
                                        <template x-for="(param, index) in params" :key="index">
                                            <div class="flex gap-2">
                                                <input type="text" x-model="param.key" class="input input-bordered input-sm w-1/3" placeholder="Key">
                                                <input type="text" x-model="param.value" class="input input-bordered input-sm w-2/3" placeholder="Value">
                                                <button type="button" @click="removeParam(index)" class="btn btn-error btn-sm btn-circle btn-outline">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="mt-2 flex gap-2">
                                        <button type="button" @click="addParam" class="btn btn-sm btn-outline">+ Tambah Parameter</button>
                                        <button type="button" @click="params = []" class="btn btn-sm btn-ghost">Bersihkan</button>
                                    </div>
                                    <input type="hidden" name="params_json" :value="JSON.stringify(paramsObject)">
                                    <label class="label"><span class="label-text-alt">Isi pasangan Key → Value (contoh: <code>code → AB-123</code>).</span></label>
                                </div>
                            </div>

                            <div class="card-actions mt-6">
                                <button type="submit" class="btn btn-primary">Kirim Broadcast</button>
                                <button type="reset" @click="$dispatch('form-reset')" class="btn btn-ghost">Reset</button>
                            </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Pratinjau & Tips --}}
                <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-body">
                        <h3 class="card-title">Pratinjau</h3>
                        <p class="text-xs text-base-content/70 mb-4">Contoh untuk 3 penerima pertama akan muncul di sini.</p>
                        <div id="previewList" class="space-y-2 text-sm">
                            <div class="text-center py-8 text-base-content/60">Pilih atau ketik daftar penerima & pesan untuk melihat pratinjau.</div>
                        </div>

                        <div class="divider my-6"></div>
                        <h4 class="font-semibold mb-2 text-base-content">Tips</h4>
                        <ul class="text-sm list-disc ml-5 space-y-1 text-base-content/70">
                            <li>Gunakan <code>@{{name}}</code> untuk menyapa penerima (mode Custom).</li>
                            <li>Nomor harus berformat internasional (cth: <code>628...</code>).</li>
                            <li>Kelola sender dan template melalui menu di sidebar.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('broadcastManager', (allLeads = [], availableStatuses = [], templates = [], oldRecipients, oldMessage, oldParams) => ({
        // State untuk penerima
        recipientsTab: 'manual',
        manualInput: oldRecipients,
        searchTerm: '',
        selectedLeadIds: [],
        selectedStatuses: [],
        finalRecipients: oldRecipients,
        allLeads: allLeads,
        availableStatuses: availableStatuses,

        // State untuk pesan
        messageMode: 'custom',
        customMessage: oldMessage,
        templates: templates,
        selectedTemplateId: '',
        params: [],

        init() {
            this.$watch(['recipientsTab', 'manualInput', 'selectedLeadIds', 'selectedStatuses'], () => this.updateRecipients());
            this.$watch(['messageMode', 'customMessage', 'params', 'selectedTemplateId'], () => this.renderPreview());
            this.$on('form-reset', () => this.resetForm());

            try {
                const initialParams = JSON.parse(oldParams);
                this.params = Object.entries(initialParams).map(([key, value]) => ({ key, value }));
            } catch(e) { this.params = []; }

            this.updateRecipients();
        },

        resetForm() {
            this.recipientsTab = 'manual';
            this.manualInput = '';
            this.selectedLeadIds = [];
            this.selectedStatuses = [];
            this.messageMode = 'custom';
            this.customMessage = '';
            this.selectedTemplateId = '';
            this.params = [];
        },

        get filteredLeads() {
            if (!this.searchTerm) return this.allLeads;
            const term = this.searchTerm.toLowerCase();
            return this.allLeads.filter(lead =>
                (lead.name || '').toLowerCase().includes(term) ||
                (lead.phone || '').includes(term)
            );
        },

        updateRecipients() {
            const recipients = new Map();
            const sanitize = s => (s||'').replace(/\D+/g, '');

            if (this.recipientsTab === 'manual') {
                (this.manualInput || '').split(/\r?\n/).forEach(line => {
                    let name = null, phone = null; line = line.trim(); if (!line) return;
                    if (line.includes(',')) { [name, phone] = line.split(',', 2).map(s => s.trim()); }
                    else if (line.includes('|')) { [phone, name] = line.split('|', 2).map(s => s.trim()); }
                    else { phone = line; }
                    phone = sanitize(phone);
                    if (phone.length >= 7 && !recipients.has(phone)) recipients.set(phone, name || `+${phone}`);
                });
            } else if (this.recipientsTab === 'select') {
                this.allLeads.forEach(lead => {
                    if (this.selectedLeadIds.includes(String(lead.id))) {
                        const phone = sanitize(lead.phone);
                        if (phone.length >= 7 && !recipients.has(phone)) recipients.set(phone, lead.name);
                    }
                });
            } else if (this.recipientsTab === 'status') {
                 this.allLeads.forEach(lead => {
                    if (this.selectedStatuses.includes(lead.status)) {
                        const phone = sanitize(lead.phone);
                        if (phone.length >= 7 && !recipients.has(phone)) recipients.set(phone, lead.name);
                    }
                });
            }
            this.finalRecipients = Array.from(recipients, ([phone, name]) => `${name}, ${phone}`).join('\n');
            this.renderPreview();
        },

        renderPreview() {
            const preview = document.getElementById('previewList');
            const recipients = new Map();
            (this.finalRecipients || '').split(/\r?\n/).forEach(line => {
                if (!line.trim()) return;
                let [name, phone] = line.split(',', 2).map(s => s.trim());
                if (name && phone) recipients.set(phone, name);
            });

            if (recipients.size === 0) {
                preview.innerHTML = '<div class="text-center py-8 text-base-content/60">Pilih atau ketik penerima & pesan untuk melihat pratinjau.</div>';
                return;
            }

            let txt = '';
            let tpl = '';
            if (this.messageMode === 'custom') {
              tpl = this.customMessage || '';
            } else {
              const selectedTpl = this.templates.find(t => t.id == this.selectedTemplateId);
              tpl = selectedTpl ? selectedTpl.body : 'Pilih template untuk melihat pratinjau.';
              this.params.forEach(p => {
                  if(p.key) {
                      const pattern = new RegExp(`\\{\\{\\s*${p.key}\\s*\\}\\}`, 'gi');
                      tpl = tpl.replaceAll(pattern, p.value);
                  }
              });
            }

            let count = 0;
            for (const [phone, name] of recipients) {
                if(count >= 3) break;
                let t = tpl.replaceAll('@{{name}}', name).replaceAll('@{{nama}}', name).replaceAll('@{{nama_pelanggan}}', name);
                txt += `<div class="p-3 rounded-lg border border-base-300 bg-base-200"><div class="font-semibold text-xs text-base-content/60">→ ${phone} (${name})</div><div class="mt-1 whitespace-pre-wrap">${this.escapeHtml(t)}</div></div>`;
                count++;
            }
            preview.innerHTML = txt;
        },

        addParam() { this.params.push({ key: '', value: '' }); },
        removeParam(index) { this.params.splice(index, 1); },
        get paramsObject() {
            return this.params.reduce((obj, item) => {
                if (item.key) obj[item.key] = item.value;
                return obj;
            }, {});
        },
        escapeHtml(s) { return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m])); }
    }));
});
</script>
@endpush
