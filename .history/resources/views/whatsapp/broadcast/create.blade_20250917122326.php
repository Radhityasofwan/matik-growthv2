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

                        {{-- SINKRONISASI: UI Daftar Penerima yang baru dan interaktif --}}
                        <div x-data="recipientsManager(@json($all_leads), @json($lead_statuses))" x-init="init()" class="mt-4">
                            <label class="label"><span class="label-text">Daftar Penerima</span></label>
                            <div class="tabs tabs-boxed">
                                <a class="tab" :class="{'tab-active': activeTab === 'manual'}" @click="activeTab = 'manual'">Input Manual</a>
                                <a class="tab" :class="{'tab-active': activeTab === 'select'}" @click="activeTab = 'select'">Pilih dari Leads</a>
                                <a class="tab" :class="{'tab-active': activeTab === 'status'}" @click="activeTab = 'status'">Berdasarkan Status</a>
                            </div>

                            {{-- Panel Input Manual --}}
                            <div x-show="activeTab === 'manual'" class="mt-2">
                                 <textarea x-model="manualInput" @input.debounce.500ms="updateRecipients" class="textarea textarea-bordered w-full h-36 font-mono text-sm" placeholder="Satu baris satu penerima. Contoh:&#10;628123456789&#10;Budi, 628123456789&#10;628123456789 | Budi"></textarea>
                                 <label class="label"><span class="label-text-alt">Format: <code>628xxxx</code>, <code>Nama, 628xxxx</code>, atau <code>628xxxx | Nama</code></span></label>
                            </div>

                            {{-- Panel Pilih dari Leads --}}
                            <div x-show="activeTab === 'select'" class="mt-2 border bg-base-200/50 rounded-box p-4" x-cloak>
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
                            <div x-show="activeTab === 'status'" class="mt-2 border bg-base-200/50 rounded-box p-4" x-cloak>
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
                                <a class="tab tab-active" id="tab_custom">Pesan Custom</a>
                                <a class="tab" id="tab_template">Gunakan Template</a>
                                <input type="radio" name="mode" value="custom" id="mode_custom" class="hidden" checked>
                                <input type="radio" name="mode" value="template" id="mode_template" class="hidden">
                            </div>
                        </div>

                        {{-- Konten Pesan Custom --}}
                        <div id="boxCustom" class="form-control mt-2">
                            <label class="label"><span class="label-text">Isi Pesan</span></label>
                            <textarea name="message" class="textarea textarea-bordered w-full h-36" placeholder="Tulis pesan. Gunakan @{{name}} untuk menyapa penerima.">{{ old('message') }}</textarea>
                            <label class="label"><span class="label-text-alt">Juga mendukung <code>@{{nama}}</code> dan <code>@{{nama_pelanggan}}</code>.</span></label>
                        </div>

                        {{-- Konten Template --}}
                        <div id="boxTemplate" class="mt-2 hidden space-y-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Pilih Template</span></label>
                                <select name="template_id" class="select select-bordered w-full">
                                    <option value="" disabled selected>— Pilih Template —</option>
                                    @foreach(($templates ?? []) as $tpl)
                                        @php $label = $tpl->name ?? ('Template #'.$tpl->id); @endphp
                                        <option value="{{ $tpl->id }}">{{ $label }}@if(isset($tpl->is_active) && !$tpl->is_active) (nonaktif) @endif</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="paramsBox">
                                <label class="label"><span class="label-text">Parameter Template</span></label>
                                <div id="paramsList" class="space-y-2"></div>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" id="btnAddParam" class="btn btn-sm btn-outline">+ Tambah Parameter</button>
                                    <button type="button" id="btnClearParam" class="btn btn-sm btn-ghost">Bersihkan</button>
                                </div>
                                <input type="hidden" name="params_json" id="params_json" value='{{ old('params_json','{}') }}'>
                                <label class="label"><span class="label-text-alt">Isi pasangan Key → Value (contoh: <code>code → AB-123</code>).</span></label>
                            </div>
                        </div>

                        <div class="card-actions mt-6">
                            <button type="submit" class="btn btn-primary">Kirim Broadcast</button>
                            <button type="reset" class="btn btn-ghost">Reset</button>
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

@verbatim
<script>
// Skrip fungsionalitas pesan, tab, dan parameter dipertahankan dengan sedikit penyesuaian
(function(){
  // Elemen mode
  const tabCustom = document.getElementById('tab_custom');
  const tabTpl    = document.getElementById('tab_template');
  const modeCustom = document.getElementById('mode_custom');
  const modeTpl    = document.getElementById('mode_template');

  // Kontainer
  const boxCustom  = document.getElementById('boxCustom');
  const boxTemplate= document.getElementById('boxTemplate');

  // Input utama
  const message    = document.querySelector('textarea[name="message"]');
  const preview    = document.getElementById('previewList');

  // Params UI
  const paramsHidden = document.getElementById('params_json');
  const paramsList   = document.getElementById('paramsList');
  const btnAddParam  = document.getElementById('btnAddParam');
  const btnClearParam= document.getElementById('btnClearParam');

  function paramsUpdateHidden() {
    if (!paramsList || !paramsHidden) return;
    const obj = {};
    paramsList.querySelectorAll('.param-row').forEach(row => {
      const k = row.querySelector('.param-key')?.value?.trim() || '';
      const v = row.querySelector('.param-val')?.value ?? '';
      if (k) obj[k] = v;
    });
    paramsHidden.value = JSON.stringify(obj);
  }

  function addParamRow(key = '', val = '') {
    if (!paramsList) return;
    const row = document.createElement('div');
    row.className = 'param-row flex gap-2';
    row.innerHTML = `
      <input type="text" class="input input-bordered input-sm param-key w-1/3" placeholder="Key" value="${key}">
      <input type="text" class="input input-bordered input-sm param-val w-2/3" placeholder="Value" value="${val}">
      <button type="button" class="btn btn-error btn-sm btn-circle btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    `;
    row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', () => {
      paramsUpdateHidden();
      Alpine.store('recipients').triggerPreviewUpdate();
    }));
    row.querySelector('button').addEventListener('click', () => {
      row.remove();
      paramsUpdateHidden();
      Alpine.store('recipients').triggerPreviewUpdate();
    });
    paramsList.appendChild(row);
    paramsUpdateHidden();
  }

  if (btnAddParam) btnAddParam.addEventListener('click', () => addParamRow());
  if (btnClearParam) btnClearParam.addEventListener('click', () => {
    if(paramsList) paramsList.innerHTML = '';
    paramsUpdateHidden();
    Alpine.store('recipients').triggerPreviewUpdate();
  });

  try {
    const initial = JSON.parse(paramsHidden?.value || '{}');
    Object.entries(initial).forEach(([k,v]) => addParamRow(k, String(v)));
  } catch(e) {}

  function toggleMode(mode) {
    if (mode === 'template') {
        modeTpl.checked = true;
        modeCustom.checked = false;
        tabTpl.classList.add('tab-active');
        tabCustom.classList.remove('tab-active');
        boxTemplate.classList.remove('hidden');
        boxCustom.classList.add('hidden');
    } else {
        modeCustom.checked = true;
        modeTpl.checked = false;
        tabCustom.classList.add('tab-active');
        tabTpl.classList.remove('tab-active');
        boxCustom.classList.remove('hidden');
        boxTemplate.classList.add('hidden');
    }
    Alpine.store('recipients').triggerPreviewUpdate();
  }
  if (tabCustom) tabCustom.addEventListener('click', () => toggleMode('custom'));
  if (tabTpl) tabTpl.addEventListener('click', () => toggleMode('template'));

  if(message) message.addEventListener('input', () => Alpine.store('recipients').triggerPreviewUpdate());

  toggleMode('custom');
})();
</script>
@endverbatim

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('recipientsManager', (allLeads = [], availableStatuses = []) => ({
        activeTab: 'manual',
        manualInput: '{{ old('recipients') }}',
        searchTerm: '',
        selectedLeadIds: [],
        selectedStatuses: [],
        finalRecipients: '{{ old('recipients') }}',
        allLeads: allLeads,
        availableStatuses: availableStatuses,

        init() {
            // Menjadikan fungsi update preview global
            Alpine.store('recipients', {
                triggerPreviewUpdate: () => this.renderPreview()
            });
            this.updateRecipients();
        },

        get filteredLeads() {
            if (!this.searchTerm) return this.allLeads;
            const term = this.searchTerm.toLowerCase();
            return this.allLeads.filter(lead =>
                lead.name.toLowerCase().includes(term) ||
                lead.phone.includes(term)
            );
        },

        updateRecipients() {
            const recipients = new Map();
            const sanitize = s => (s||'').replace(/\D+/g, '');

            // 1. Proses dari input manual
            (this.manualInput || '').split(/\r?\n/).forEach(line => {
                let name = null, phone = null;
                line = line.trim();
                if (!line) return;

                if (line.includes(',')) {
                    [name, phone] = line.split(',', 2).map(s => s.trim());
                } else if (line.includes('|')) {
                    [phone, name] = line.split('|', 2).map(s => s.trim());
                } else {
                    phone = line;
                }
                phone = sanitize(phone);
                if (phone.length >= 7) {
                    if (!recipients.has(phone)) recipients.set(phone, name || `+${phone}`);
                }
            });

            // 2. Proses dari lead yang dipilih
            this.allLeads.forEach(lead => {
                if (this.selectedLeadIds.includes(String(lead.id))) {
                    const phone = sanitize(lead.phone);
                    if (phone.length >= 7 && !recipients.has(phone)) {
                        recipients.set(phone, lead.name);
                    }
                }
            });

            // 3. Proses dari status yang dipilih
            this.allLeads.forEach(lead => {
                if (this.selectedStatuses.includes(lead.status)) {
                    const phone = sanitize(lead.phone);
                    if (phone.length >= 7 && !recipients.has(phone)) {
                        recipients.set(phone, lead.name);
                    }
                }
            });

            // Format ulang untuk textarea
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
                preview.innerHTML = '<div class="text-center py-8 text-base-content/60">Pilih atau ketik daftar penerima & pesan untuk melihat pratinjau.</div>';
                return;
            }

            let txt = '';
            let tpl = '';
            const modeCustom = document.getElementById('mode_custom');
            const message = document.querySelector('textarea[name="message"]');

            if (modeCustom.checked) {
              tpl = message?.value || '';
            } else {
              tpl = 'Template aktif akan dirender di server saat broadcast dikirim.';
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

        escapeHtml(s) {
            return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m]));
        }
    }));
});
</script>
@endpush

