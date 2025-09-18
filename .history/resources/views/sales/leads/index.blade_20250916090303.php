@extends('layouts.app')
@section('title', 'Leads - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m7 10a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><strong>Terdapat kesalahan!</strong>
                    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="text-2xl md:text-3xl font-semibold text-gray-800 dark:text-gray-100">Leads</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Lacak dan kelola calon pelanggan Anda.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv,.txt" class="file-input file-input-bordered file-input-sm max-w-xs" required>
                <button type="submit" class="btn btn-secondary btn-sm">Import</button>
            </form>

            <a href="#bulk_whatsapp_modal" id="bulk-whatsapp-trigger" class="btn btn-success btn-sm hidden">
                Kirim WhatsApp (<span id="bulk-selected-count">0</span>)
            </a>

            <a href="#create_lead_modal" class="btn btn-primary">Tambah Lead</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mt-6">
        <form action="{{ route('leads.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" placeholder="Cari nama, email, atau nama toko..." value="{{ request('search') }}" class="input input-bordered w-full">
            <select name="status" class="select select-bordered w-full">
                <option value="">Semua Status</option>
                <option value="active" @selected(request('status')=='active')>Aktif</option>
                <option value="nonactive" @selected(request('status')=='nonactive')>Tidak Aktif</option>
                <option value="converted" @selected(request('status')=='converted')>Konversi</option>
                <option value="churn" @selected(request('status')=='churn')>Dibatalkan</option>
                <option value="trial" @selected(request('status')=='trial')>Trial</option>
            </select>
            <select name="per_page" class="select select-bordered w-full">
                @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" @selected(request('per_page',10)==$n)>{{ $n }} / halaman</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">Terapkan</button>
        </form>
    </div>

    {{-- =========================
         UI: Automation Rules (ringan)
       ========================= --}}
    <div class="mt-6 rounded-2xl border shadow-sm p-4 bg-base-100">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h4 class="font-semibold">Automation Rules</h4>
                <p class="text-xs text-gray-500">Aturan follow-up otomatis. Global = berlaku untuk semua lead.</p>
            </div>
            <a href="#create_rule_modal" class="btn btn-sm btn-primary">Tambah Rule</a>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th>Scope</th>
                    <th>Kondisi</th>
                    <th>Kirim setelah</th>
                    <th>Template</th>
                    <th>Sender</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($followUpRules as $rule)
                    <tr>
                        <td>
                            @if($rule->lead_id)
                                <span class="badge badge-outline">Lead: {{ $rule->lead?->name ?? ('#'.$rule->lead_id) }}</span>
                            @else
                                <span class="badge badge-info">Global</span>
                            @endif
                        </td>
                        <td><code>{{ $rule->condition }}</code></td>
                        <td>{{ $rule->days_after }} hari</td>
                        <td>{{ $rule->template?->name ?? '-' }}</td>
                        <td>{{ $rule->sender?->name ? ($rule->sender->name.' ('.$rule->sender->number.')') : '-' }}</td>
                        <td>
                            @if($rule->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-ghost">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Toggle aktif --}}
                                <form action="{{ route('lead-follow-up-rules.update', $rule) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $rule->is_active ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-xs {{ $rule->is_active ? 'btn-outline' : 'btn-success' }}">
                                        {{ $rule->is_active ? 'Matikan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                {{-- Edit --}}
                                <a href="#edit_rule_{{ $rule->id }}" class="btn btn-xs">Edit</a>

                                {{-- Delete --}}
                                <form action="{{ route('lead-follow-up-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Hapus rule ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-error btn-outline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-500">Belum ada rule.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========= GROUPING LEADS ========= --}}
    @php
        $all = $leads->getCollection();

        // helper untuk tentukan kondisi lead saat ini
        $cond = function($l) {
            $cc = (int)($l->chat_count ?? 0);
            $lr = $l->last_reply_at ?? null;
            $lc = $l->last_wa_chat_at ?? null;
            if ($cc <= 0) return 'no_chat';
            if ($cc === 1 && (is_null($lr) || $lr < $lc)) return 'chat_1_no_reply';
            if ($cc === 2 && (is_null($lr) || $lr < $lc)) return 'chat_2_no_reply';
            if ($cc === 3 && (is_null($lr) || $lr < $lc)) return 'chat_3_no_reply';
            return 'other';
        };

        $group0 = $all->filter(fn($l)=>$cond($l)==='no_chat');
        $group1 = $all->filter(fn($l)=>$cond($l)==='chat_1_no_reply');
        $group2 = $all->filter(fn($l)=>$cond($l)==='chat_2_no_reply');
        $group3 = $all->filter(fn($l)=>$cond($l)==='chat_3_no_reply');
        $group4 = $all->filter(fn($l)=>$cond($l)==='other');

        $groups = [
            ['label' => 'Belum di-chat',  'items' => $group0, 'bg' => 'bg-info/10',    'accent' => 'text-info'],
            ['label' => 'Sudah chat 1x',  'items' => $group1, 'bg' => 'bg-success/10', 'accent' => 'text-success'],
            ['label' => 'Sudah chat 2x',  'items' => $group2, 'bg' => 'bg-warning/10', 'accent' => 'text-warning'],
            ['label' => 'Sudah chat 3x',  'items' => $group3, 'bg' => 'bg-accent/10',  'accent' => 'text-accent'],
            ['label' => 'Lainnya',        'items' => $group4, 'bg' => 'bg-error/10',   'accent' => 'text-error'],
        ];

        $statusLabel = ['trial'=>'Trial','active'=>'Aktif','nonactive'=>'Tidak Aktif','converted'=>'Konversi','churn'=>'Dibatalkan'];
    @endphp

    {{-- Kategori containers --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        @foreach($groups as $g)
        <div class="rounded-2xl p-4 border shadow-sm {{ $g['bg'] }}">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold {{ $g['accent'] }}">{{ $g['label'] }}</h4>
                <span class="badge">{{ $g['items']->count() }} item</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="checkbox checkbox-xs group-select-all"></th>
                            <th>Nama</th>
                            <th>WA</th>
                            <th>Status</th>
                            <th>Chat#</th>
                            <th>Auto</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($g['items'] as $lead)
                        @php
                            $thisCond = $cond($lead);
                            $ruleForLead = optional($activePerLead->get($lead->id))->get($thisCond);
                            $ruleGlobal  = $activeGlobals->get($thisCond);
                            $ruleActive  = $ruleForLead ?: $ruleGlobal; // prioritas rule khusus lead
                        @endphp
                        <tr id="lead-row-{{ $lead->id }}"
                            data-phone="{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}"
                            data-name="{{ $lead->name ?? ($lead->store_name ?? 'Pelanggan') }}">
                            <td><input type="checkbox" class="checkbox checkbox-xs lead-checkbox" value="{{ $lead->id }}" data-phone="{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}" data-name="{{ $lead->name }}"></td>
                            <td class="font-medium">{{ $lead->name ?? '-' }}</td>
                            <td>{{ $lead->phone ?? '-' }}</td>
                            <td><span class="badge">{{ $statusLabel[$lead->status] ?? $lead->status }}</span></td>
                            <td><span class="badge badge-ghost">{{ (int)($lead->chat_count ?? 0) }}</span></td>
                            <td>
                                @if($ruleActive)
                                    @php
                                        $base = $thisCond === 'no_chat' ? ($lead->created_at ?? null) : ($lead->last_wa_chat_at ?? null);
                                        $due  = $base ? \Carbon\Carbon::parse($base)->addDays($ruleActive->days_after) : null;
                                    @endphp
                                    <span class="badge badge-success" title="Due: {{ $due?->format('d M Y H:i') ?? '-' }}">
                                        Auto {{ $ruleActive->days_after }}d
                                    </span>
                                @else
                                    <span class="badge badge-ghost">Manual</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if($lead->phone)
                                        <a href="#whatsapp_modal" onclick="openWhatsAppModal({{ $lead->id }})" class="btn btn-xs btn-success">WA Sender</a>
                                        <a href="#" onclick="return manualChat({{ $lead->id }})" class="btn btn-xs btn-outline btn-info">Manual</a>
                                    @endif
                                    <a href="#edit_lead_modal_{{ $lead->id }}" onclick="prepareEditModal({{ $lead->id }})" class="btn btn-xs">Edit</a>
                                    <form action="{{ route('leads.destroy', $lead) }}" method="POST" onsubmit="return confirm('Hapus lead ini?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-error btn-outline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-gray-500">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="px-5 py-5 bg-base-100 border-t mt-6 flex flex-col sm:flex-row items-center justify-between rounded-2xl">
        <div class="text-sm text-gray-500">Menampilkan {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} dari {{ $leads->total() }} entri</div>
        <div class="mt-4 sm:mt-0">{{ $leads->links() }}</div>
    </div>
</div>

{{-- ===== Modals ===== --}}

{{-- Create Lead --}}
@include('sales.leads.partials._create_modal')

{{-- Edit Lead (per item) --}}
@foreach ($leads as $lead)
    @include('sales.leads.partials._edit_modal', ['lead' => $lead, 'users' => $users])
@endforeach

{{-- Single WhatsApp Modal (WAHA) --}}
@include('sales.leads.partials._wa_single_modal')

{{-- Bulk WhatsApp Modal (WAHA) --}}
@include('sales.leads.partials._wa_bulk_modal')

{{-- Create Rule Modal --}}
<div id="create_rule_modal" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
    <h3 class="font-bold text-lg">Tambah Rule</h3>

    <form action="{{ route('lead-follow-up-rules.store') }}" method="POST" class="mt-4 space-y-4">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label"><span class="label-text">Scope</span></label>
          <select id="rule_scope_create" class="select select-bordered w-full" name="scope">
            <option value="global">Global</option>
            <option value="lead">Khusus Lead</option>
          </select>
        </div>
        <div id="rule_lead_wrap_create" class="hidden">
          <label class="label"><span class="label-text">Lead</span></label>
          <select class="select select-bordered w-full" name="lead_id">
            <option value="">- pilih lead (halaman ini) -</option>
            @foreach($leads as $l)
              <option value="{{ $l->id }}">{{ $l->name }} — {{ $l->email }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="label"><span class="label-text">Kondisi</span></label>
          <select name="condition" class="select select-bordered w-full" required>
            @foreach($ruleConditions as $c)
              <option value="{{ $c }}">{{ $c }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label"><span class="label-text">Kirim setelah (hari)</span></label>
          <input type="number" min="0" name="days_after" value="3" class="input input-bordered w-full" required>
        </div>

        <div>
          <label class="label"><span class="label-text">Template</span></label>
          <select name="wa_template_id" class="select select-bordered w-full">
            <option value="">- tanpa template -</option>
            @foreach($whatsappTemplates as $tpl)
              <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label"><span class="label-text">Sender</span></label>
          <select name="waha_sender_id" class="select select-bordered w-full">
            <option value="">- otomatis -</option>
            @foreach($wahaSenders as $s)
              <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->number }})</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="modal-action">
        <a href="#" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Rule</button>
      </div>
    </form>
  </div>
  <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- Edit Rule Modals --}}
@foreach($followUpRules as $rule)
<div id="edit_rule_{{ $rule->id }}" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
    <h3 class="font-bold text-lg">Edit Rule #{{ $rule->id }}</h3>

    <form action="{{ route('lead-follow-up-rules.update', $rule) }}" method="POST" class="mt-4 space-y-4">
      @csrf @method('PATCH')
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label"><span class="label-text">Scope</span></label>
          <select id="rule_scope_edit_{{ $rule->id }}" class="select select-bordered w-full" name="scope">
            <option value="global" @selected(!$rule->lead_id)>Global</option>
            <option value="lead"   @selected($rule->lead_id)>Khusus Lead</option>
          </select>
        </div>
        <div id="rule_lead_wrap_edit_{{ $rule->id }}" class="{{ $rule->lead_id ? '' : 'hidden' }}">
          <label class="label"><span class="label-text">Lead</span></label>
          <select class="select select-bordered w-full" name="lead_id">
            <option value="">- pilih lead (halaman ini) -</option>
            @foreach($leads as $l)
              <option value="{{ $l->id }}" @selected($rule->lead_id===$l->id)>{{ $l->name }} — {{ $l->email }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="label"><span class="label-text">Kondisi</span></label>
          <select name="condition" class="select select-bordered w-full" required>
            @foreach($ruleConditions as $c)
              <option value="{{ $c }}" @selected($rule->condition===$c)>{{ $c }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label"><span class="label-text">Kirim setelah (hari)</span></label>
          <input type="number" min="0" name="days_after" value="{{ $rule->days_after }}" class="input input-bordered w-full" required>
        </div>

        <div>
          <label class="label"><span class="label-text">Template</span></label>
          <select name="wa_template_id" class="select select-bordered w-full">
            <option value="">- tanpa template -</option>
            @foreach($whatsappTemplates as $tpl)
              <option value="{{ $tpl->id }}" @selected($rule->wa_template_id===$tpl->id)>{{ $tpl->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label"><span class="label-text">Sender</span></label>
          <select name="waha_sender_id" class="select select-bordered w-full">
            <option value="">- otomatis -</option>
            @foreach($wahaSenders as $s)
              <option value="{{ $s->id }}" @selected($rule->waha_sender_id===$s->id)>{{ $s->name }} ({{ $s->number }})</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="label"><span class="label-text">Status</span></label>
          <select name="is_active" class="select select-bordered w-full">
            <option value="1" @selected($rule->is_active)>Aktif</option>
            <option value="0" @selected(!$rule->is_active)>Nonaktif</option>
          </select>
        </div>
      </div>

      <div class="modal-action">
        <a href="#" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Update Rule</button>
      </div>
    </form>
  </div>
  <a href="#" class="modal-backdrop">Close</a>
</div>
@endforeach
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = '{{ csrf_token() }}';

    // === Scope toggle for rule create/edit ===
    const toggleWrap = (sel, wrap) => {
        const show = sel.value === 'lead';
        wrap.classList.toggle('hidden', !show);
    };
    const createScope = document.getElementById('rule_scope_create');
    const createWrap  = document.getElementById('rule_lead_wrap_create');
    if (createScope && createWrap) {
        toggleWrap(createScope, createWrap);
        createScope.addEventListener('change', ()=>toggleWrap(createScope, createWrap));
    }
    @foreach($followUpRules as $rule)
        (function(){
            const s = document.getElementById('rule_scope_edit_{{ $rule->id }}');
            const w = document.getElementById('rule_lead_wrap_edit_{{ $rule->id }}');
            if (s && w) {
                s.addEventListener('change', ()=>toggleWrap(s, w));
            }
        })();
    @endforeach

    // ===== Helpers =====
    const fillTemplate = (tpl, name) => (tpl || '')
        .replace(/\{\{\s*name\s*\}\}/gi, name)
        .replace(/\{\{\s*nama\s*\}\}/gi, name)
        .replace(/\{\{\s*nama_pelanggan\s*\}\}/gi, name);
    const sanitize = s => (s||'').replace(/\D+/g, '');

    // ===== Min date logic =====
    function setMinDate(regInput, endInput) {
        if (!regInput || !endInput) return;
        const dt = new Date(regInput.value || new Date());
        const yyyy = dt.getFullYear();
        const mm = String(dt.getMonth()+1).padStart(2,'0');
        const dd = String(dt.getDate()).padStart(2,'0');
        endInput.min = `${yyyy}-${mm}-${dd}`;
        if (endInput.value && endInput.value < endInput.min) endInput.value = endInput.min;
    }
    const createReg = document.getElementById('create_registered_at');
    const createEnd = document.getElementById('create_trial_ends_at');
    setMinDate(createReg, createEnd);
    if (createReg) createReg.addEventListener('change', ()=>setMinDate(createReg, createEnd));
    @foreach($leads as $lead)
      const er{{ $lead->id }} = document.getElementById('edit_registered_at_{{ $lead->id }}');
      const ee{{ $lead->id }} = document.getElementById('edit_trial_ends_at_{{ $lead->id }}');
      setMinDate(er{{ $lead->id }}, ee{{ $lead->id }});
      if (er{{ $lead->id }}) er{{ $lead->id }}.addEventListener('change', ()=>setMinDate(er{{ $lead->id }}, ee{{ $lead->id }}));
    @endforeach

    // ===== Subscription form toggle =====
    function toggleSubscriptionForm(leadId, status) {
        const form = document.getElementById(`subscription_form_${leadId}`);
        if (form) form.classList.toggle('hidden', status !== 'converted');
    }
    window.prepareEditModal = function(leadId) {
        const sel = document.querySelector(`#edit_lead_modal_${leadId} .status-selector`);
        if (sel) toggleSubscriptionForm(leadId, sel.value);
    }
    document.querySelectorAll('.status-selector').forEach(sel=>{
        sel.addEventListener('change', function(){ toggleSubscriptionForm(this.dataset.leadId, this.value); });
    });

    // ===== Bulk selection (across groups) =====
    const selectAllGroup = document.querySelectorAll('.group-select-all');
    const leadCheckboxes = document.querySelectorAll('.lead-checkbox');
    const bulkBtn = document.getElementById('bulk-whatsapp-trigger');
    const bulkCount = document.getElementById('bulk-selected-count');
    const bulkCountModal = document.getElementById('bulk-selected-count-modal');

    function calcSelected() {
        return Array.from(document.querySelectorAll('.lead-checkbox:checked'))
              .filter(cb => sanitize(cb.dataset.phone).length >= 7);
    }
    function updateBulkUI() {
        const selected = calcSelected();
        const c = selected.length;
        bulkCount.textContent = c;
        if (bulkCountModal) bulkCountModal.textContent = c;
        bulkBtn.classList.toggle('hidden', c===0);
    }
    selectAllGroup.forEach(sel => sel.addEventListener('change', function(){
        const tbody = this.closest('table')?.querySelector('tbody');
        if (!tbody) return;
        tbody.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkUI();
    }));
    leadCheckboxes.forEach(cb=>cb.addEventListener('change', updateBulkUI));

    // ===== Single WA via WAHA =====
    let currentLeadId = null;
    const waTplSel = document.getElementById('wa-template-selector');
    const waPrev = document.getElementById('wa-message-preview');
    const waSend = document.getElementById('wa-send-button');
    const waSenderSel = document.getElementById('wa-sender-selector');

    function updateSingleWaSendButton() {
        const tplSelected = waTplSel && waTplSel.value !== '';
        const senderSelected = waSenderSel && waSenderSel.value !== '';
        const hasPhone = !!(currentLeadId && sanitize(document.getElementById(`lead-row-${currentLeadId}`)?.dataset.phone).length >= 7);
        waSend.disabled = !(tplSelected && senderSelected && hasPhone);
    }

    window.openWhatsAppModal = function(leadId) {
        currentLeadId = leadId;
        const row = document.getElementById(`lead-row-${leadId}`);
        document.getElementById('wa-lead-name').textContent = row?.dataset?.name || 'Pelanggan';
        if (waTplSel) waTplSel.selectedIndex = 0;
        if (waSenderSel) waSenderSel.selectedIndex = 0;
        waPrev.value = '';
        updateSingleWaSendButton();
    }

    if (waTplSel) waTplSel.addEventListener('change', function(){
        const body = this.selectedOptions?.[0]?.dataset?.body || '';
        if (!currentLeadId) return;
        const row = document.getElementById(`lead-row-${currentLeadId}`);
        const name = row?.dataset?.name || '';
        waPrev.value = fillTemplate(body, name);
        updateSingleWaSendButton();
    });
    if (waSenderSel) waSenderSel.addEventListener('change', updateSingleWaSendButton);

    if (waSend) waSend.addEventListener('click', function(e){
        e.preventDefault();
        if (waSend.disabled) return;

        const message = waPrev.value;
        const senderId = waSenderSel.value;

        fetch('{{ route('leads.wa.send', ['lead' => 'LEAD_ID']) }}'.replace('LEAD_ID', currentLeadId), {
            method: 'POST',
            headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
            body: JSON.stringify({ sender_id: senderId, message })
        })
        .then(r => r.json().catch(()=>({})).then(d => ({ ok:r.ok, d })))
        .then(({ok,d}) => {
            const success = ok && (d?.success !== false);
            alert(success ? 'Pesan berhasil dikirim!' : ('Gagal mengirim pesan' + (d?.error ? ': '+d.error : '.')));
            if (success) location.reload();
        })
        .catch(() => alert('Terjadi kesalahan saat mengirim pesan.'));
    });

    // ===== Bulk via WAHA =====
    const bulkTplSel = document.getElementById('bulk-wa-template-selector');
    const bulkPrev = document.getElementById('bulk-wa-message-preview');
    const bulkSend = document.getElementById('bulk-wa-send-button');
    const bulkSenderSel = document.getElementById('bulk-wa-sender-selector');
    const bulkCountModalEl = document.getElementById('bulk-selected-count-modal');

    function updateBulkWaSendButton() {
        const tplSelected = bulkTplSel && bulkTplSel.value !== '';
        const senderSelected = bulkSenderSel && bulkSenderSel.value !== '';
        const selected = calcSelected();
        bulkSend.disabled = !(tplSelected && senderSelected && selected.length > 0);
        if (bulkCountModalEl) bulkCountModalEl.textContent = selected.length;
    }
    if (bulkTplSel) bulkTplSel.addEventListener('change', function(){
        bulkPrev.value = this.selectedOptions?.[0]?.dataset?.body || '';
        updateBulkWaSendButton();
    });
    if (bulkSenderSel) bulkSenderSel.addEventListener('change', updateBulkWaSendButton);
    leadCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkWaSendButton));

    if (bulkSend) bulkSend.addEventListener('click', function(){
        const selectedIds = calcSelected().map(cb => parseInt(cb.value, 10));
        const senderId = bulkSenderSel.value;
        const tplBody = bulkPrev.value;

        if (!tplBody || selectedIds.length === 0 || !senderId) {
            alert('Harap pilih template dan pengirim, serta setidaknya satu lead.');
            return;
        }

        fetch('{{ route('leads.wa.bulkSend') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
            body: JSON.stringify({ sender_id: senderId, message: tplBody, lead_ids: selectedIds })
        })
        .then(r => r.json().catch(()=>({})).then(d => ({ ok:r.ok, d })))
        .then(({ok,d}) => {
            const success = ok && (d?.success !== false);
            alert(success ? 'Pengiriman massal diproses.' : ('Gagal kirim massal' + (d?.error ? ': '+d.error : '.')));
            if (success) location.reload();
        })
        .catch(() => alert('Terjadi kesalahan saat mengirim pesan massal.'));
    });

    // ===== Manual WA link (wa.me) + penanda chat_count =====
    window.manualChat = function(leadId) {
        const row = document.getElementById(`lead-row-${leadId}`);
        const phone = sanitize(row?.dataset?.phone);
        if (!phone) return false;

        fetch('{{ route('leads.markChatted', ['lead' => 'LEAD_ID']) }}'.replace('LEAD_ID', leadId), {
            method: 'POST',
            headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
            body: JSON.stringify({})
        }).finally(() => {
            window.open(`https://wa.me/${phone}`, '_blank');
            setTimeout(()=>location.reload(), 800);
        });

        return false;
    };
});
</script>
@endpush
