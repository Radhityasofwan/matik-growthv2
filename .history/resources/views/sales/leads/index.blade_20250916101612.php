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
                    <option value="{{ $n }}" @selected(request('per_page',10)===$n)>{{ $n }} / halaman</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">Terapkan</button>
        </form>
    </div>

    {{-- ================== RULE MANAGEMENT (ringan) ================== --}}
    @php
        // guard semua variabel agar aman
        $followUpRules   = ($followUpRules   ?? collect())->values();
        $ruleConditions  = ($ruleConditions  ?? ['no_chat','chat_1_no_reply','chat_2_no_reply','chat_3_no_reply']);
        $templates       = ($templates       ?? ($whatsappTemplates ?? collect()));
        $senders         = ($senders         ?? ($wahaSenders ?? collect()));

        $condLabels = [
            'no_chat'         => 'Belum di-chat',
            'chat_1_no_reply' => 'Sudah 1x chat (belum balas)',
            'chat_2_no_reply' => 'Sudah 2x chat (belum balas)',
            'chat_3_no_reply' => 'Sudah 3x chat (belum balas)',
        ];

        // siapkan lookup rule aktif: global & per-lead
        $activeRules      = $followUpRules->where('is_active', true);
        $activeGlobal     = $activeRules->whereNull('lead_id')->groupBy('condition');   // cond => [rules]
        $activePerLeadMap = $activeRules->whereNotNull('lead_id')->groupBy('lead_id');  // lead_id => [rules]

        // fungsi sederhana menentukan kondisi lead berbasis chat_count (tanpa last_reply_at supaya ringan)
        $resolveCond = function($lead) {
            $c = (int) ($lead->chat_count ?? 0);
            return $c <= 0 ? 'no_chat' : ($c === 1 ? 'chat_1_no_reply' : ($c === 2 ? 'chat_2_no_reply' : 'chat_3_no_reply'));
        };
    @endphp

    <div id="rules" class="mt-8 rounded-2xl border p-4 bg-base-100">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-semibold text-gray-800 dark:text-gray-100">Aturan Follow-up (Ringkas)</h4>
            <a href="{{ route('lead-follow-up-rules.index') }}" class="link link-primary text-sm">Kelola di halaman penuh →</a>
        </div>

        {{-- form buat rule cepat --}}
        <form action="{{ route('lead-follow-up-rules.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-6 gap-3 items-end">
            @csrf
            <div class="lg:col-span-2">
                <label class="label"><span class="label-text">Berlaku Untuk</span></label>
                <select name="lead_id" class="select select-bordered w-full">
                    <option value="">Global (semua lead)</option>
                    @foreach(($leads->getCollection() ?? collect()) as $ld)
                        <option value="{{ $ld->id }}">{{ $ld->name }} — {{ $ld->email }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Kondisi</span></label>
                <select name="condition" class="select select-bordered w-full" required>
                    @foreach($ruleConditions as $c)
                        <option value="{{ $c }}">{{ $condLabels[$c] ?? $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Kirim setelah (hari)</span></label>
                <input name="days_after" type="number" min="0" value="3" class="input input-bordered w-full" required>
            </div>
            <div>
                <label class="label"><span class="label-text">Template WA (opsional)</span></label>
                <select name="wa_template_id" class="select select-bordered w-full">
                    <option value="">—</option>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Sender (opsional)</span></label>
                <select name="waha_sender_id" class="select select-bordered w-full">
                    <option value="">—</option>
                    @foreach($senders as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->number }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3">
                <label class="label"><span class="label-text">Aktif</span></label>
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" class="toggle toggle-success" value="1" checked>
                <button type="submit" class="btn btn-primary btn-sm ml-auto">Simpan Rule</button>
            </div>
        </form>

        {{-- daftar rule --}}
        <div class="mt-4 overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Scope</th>
                        <th>Kondisi</th>
                        <th>Hari</th>
                        <th>Template</th>
                        <th>Sender</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $listRules = $followUpRules; @endphp
                    @forelse($listRules as $rule)
                        <tr>
                            <td>
                                @if($rule->lead_id)
                                    <span class="badge badge-outline">Lead: {{ $rule->lead->name ?? ('#'.$rule->lead_id) }}</span>
                                @else
                                    <span class="badge badge-info">Global</span>
                                @endif
                            </td>
                            <td><code>{{ $rule->condition }}</code></td>
                            <td>{{ $rule->days_after }} hr</td>
                            <td>{{ $rule->template->name ?? '—' }}</td>
                            <td>{{ $rule->sender ? ($rule->sender->name.' ('.$rule->sender->number.')') : '—' }}</td>
                            <td>
                                <span class="badge {{ $rule->is_active ? 'badge-success' : 'badge-ghost' }}">{{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                @if($rule->last_run_at)
                                    <span class="ml-2 text-xs text-gray-500">Terakhir: {{ $rule->last_run_at->format('d M Y H:i') }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex gap-2 justify-end">
                                    <form action="{{ route('lead-follow-up-rules.update', $rule) }}" method="POST" class="inline-flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="toggle_active" value="1">
                                        <button class="btn btn-xs">{{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form action="{{ route('lead-follow-up-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Hapus aturan ini?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-xs btn-error btn-outline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-500">Belum ada aturan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{-- ================== /RULE MANAGEMENT ================== --}}

    @php
        // Kelompokkan berdasarkan chat_count untuk tampilan kolom kategori
        $all   = $leads->getCollection(); // item halaman saat ini
        $g0    = $all->filter(fn($l)=> (int)($l->chat_count ?? 0) === 0);
        $g1    = $all->filter(fn($l)=> (int)($l->chat_count ?? 0) === 1);
        $g2    = $all->filter(fn($l)=> (int)($l->chat_count ?? 0) === 2);
        $g3    = $all->filter(fn($l)=> (int)($l->chat_count ?? 0) === 3);
        $g4    = $all->filter(fn($l)=> (int)($l->chat_count ?? 0) >= 4);

        $groups = [
            ['label' => 'Belum di-chat', 'items' => $g0, 'bg' => 'bg-info/10',    'accent' => 'text-info'],
            ['label' => 'Sudah chat 1x', 'items' => $g1, 'bg' => 'bg-success/10', 'accent' => 'text-success'],
            ['label' => 'Sudah chat 2x', 'items' => $g2, 'bg' => 'bg-warning/10', 'accent' => 'text-warning'],
            ['label' => 'Sudah chat 3x', 'items' => $g3, 'bg' => 'bg-accent/10',  'accent' => 'text-accent'],
            ['label' => 'Sudah chat 4x+', 'items' => $g4,'bg' => 'bg-error/10',   'accent' => 'text-error'],
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
                            <th>Rule</th>
                            <th>Chat#</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($g['items'] as $lead)
                        @php
                            // tentukan rule yang berlaku untuk lead ini (khusus > global)
                            $thisCond   = $resolveCond($lead);
                            $leadRules  = $activePerLeadMap->get($lead->id) ?? collect();
                            $ruleForLead= $leadRules->firstWhere('condition', $thisCond);
                            $ruleGlobal = optional($activeGlobal->get($thisCond))->first();
                            $ruleActive = $ruleForLead ?: $ruleGlobal; // bisa null bila belum diset
                        @endphp
                        <tr id="lead-row-{{ $lead->id }}"
                            data-phone="{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}"
                            data-name="{{ $lead->name ?? ($lead->store_name ?? 'Pelanggan') }}">
                            <td><input type="checkbox" class="checkbox checkbox-xs lead-checkbox" value="{{ $lead->id }}" data-phone="{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}" data-name="{{ $lead->name }}"></td>
                            <td class="font-medium">{{ $lead->name ?? '-' }}</td>
                            <td>{{ $lead->phone ?? '-' }}</td>
                            <td><span class="badge">{{ $statusLabel[$lead->status] ?? $lead->status }}</span></td>
                            <td>
                                @if($ruleActive)
                                    <span class="badge badge-outline">{{ $condLabels[$thisCond] ?? $thisCond }}</span>
                                    @if($ruleForLead)
                                        <span class="badge badge-info ml-1">Khusus</span>
                                    @else
                                        <span class="badge badge-ghost ml-1">Global</span>
                                    @endif
                                @else
                                    <span class="badge badge-ghost">—</span>
                                @endif
                            </td>
                            <td><span class="badge badge-ghost">{{ (int)($lead->chat_count ?? 0) }}</span></td>
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

{{-- Create --}}
<div id="create_lead_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('leads.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Tambah Lead Baru</h3>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">No. Whatsapp</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Nama Toko</span></label>
                    <input type="text" name="store_name" value="{{ old('store_name') }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Tanggal Daftar</span></label>
                    <input id="create_registered_at" type="datetime-local" name="registered_at" value="{{ old('registered_at', now()->format('Y-m-d\TH:i')) }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Tanggal Habis Trial</span></label>
                    <input id="create_trial_ends_at" type="date" name="trial_ends_at" value="{{ old('trial_ends_at', now()->addDays(7)->format('Y-m-d')) }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select name="status" class="select select-bordered w-full" required>
                        <option value="active" @selected(old('status')=='active')>Aktif</option>
                        <option value="nonactive" @selected(old('status')=='nonactive')>Tidak Aktif</option>
                        <option value="converted" @selected(old('status')=='converted')>Konversi</option>
                        <option value="churn" @selected(old('status')=='churn')>Dibatalkan</option>
                        <option value="trial" @selected(old('status')=='trial')>Trial</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Owner</span></label>
                    <select name="owner_id" class="select select-bordered w-full" required>
                        @forelse($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @empty
                            <option disabled>Tidak ada user</option>
                        @endforelse
                    </select>
                </div>
            </div>

            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Lead</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- Edit --}}
@foreach ($leads as $lead)
<div id="edit_lead_modal_{{ $lead->id }}" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('leads.update', $lead) }}" method="POST">
            @csrf @method('PATCH')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Edit Lead: {{ $lead->name }}</h3>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" value="{{ old('name', $lead->name) }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" value="{{ old('email', $lead->email) }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">No. Whatsapp</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $lead->phone) }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Nama Toko</span></label>
                    <input type="text" name="store_name" value="{{ old('store_name', $lead->store_name) }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Tanggal Daftar</span></label>
                    <input id="edit_registered_at_{{ $lead->id }}" type="datetime-local" name="registered_at" value="{{ old('registered_at', $lead->created_at?->format('Y-m-d\TH:i')) }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Tanggal Habis Trial</span></label>
                    <input id="edit_trial_ends_at_{{ $lead->id }}" type="date" name="trial_ends_at" value="{{ old('trial_ends_at', $lead->trial_ends_at?->format('Y-m-d')) }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select name="status" class="select select-bordered w-full status-selector" data-lead-id="{{ $lead->id }}" required>
                        <option value="active" @selected(old('status', $lead->status)=='active')>Aktif</option>
                        <option value="nonactive" @selected(old('status', $lead->status)=='nonactive')>Tidak Aktif</option>
                        <option value="converted" @selected(old('status', $lead->status)=='converted')>Konversi</option>
                        <option value="churn" @selected(old('status', $lead->status)=='churn')>Dibatalkan</option>
                        <option value="trial" @selected(old('status', $lead->status)=='trial')>Trial</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Owner</span></label>
                    <select name="owner_id" class="select select-bordered w-full" required>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('owner_id', $lead->owner_id) == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <a href="{{ route('leads.show', $lead) }}" class="btn btn-xs btn-outline">Detail</a>

                {{-- Subscription placeholder (muncul kalau converted) --}}
                <div id="subscription_form_{{ $lead->id }}" class="hidden md:col-span-2 mt-2 pt-4 border-t space-y-4">
                    <h4 class="font-semibold text-md">Detail Langganan</h4>
                    <div><label class="label"><span class="label-text">Nama Paket</span></label>
                        <input type="text" name="plan" value="{{ old('plan', $lead->subscription->plan ?? '') }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Jumlah (Rp)</span></label>
                        <input type="number" name="amount" value="{{ old('amount', $lead->subscription->amount ?? '') }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Siklus</span></label>
                        <select name="cycle" class="select select-bordered w-full">
                            <option value="monthly" @selected(old('cycle', $lead->subscription->cycle ?? '')=='monthly')>Bulanan</option>
                            <option value="yearly" @selected(old('cycle', $lead->subscription->cycle ?? '')=='yearly')>Tahunan</option>
                        </select></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="label"><span class="label-text">Mulai</span></label>
                            <input type="date" name="start_date" value="{{ old('start_date', optional($lead->subscription)->start_date ? $lead->subscription->start_date->format('Y-m-d') : now()->format('Y-m-d')) }}" class="input input-bordered w-full" /></div>
                        <div><label class="label"><span class="label-text">Berakhir (Opsional)</span></label>
                            <input type="date" name="end_date" value="{{ old('end_date', optional($lead->subscription)->end_date ? $lead->subscription->end_date->format('Y-m-d') : '') }}" class="input input-bordered w-full" /></div>
                    </div>
                </div>
            </div>

            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Update Lead</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endforeach

{{-- Single WhatsApp Modal (WAHA) --}}
<div id="whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Kirim WhatsApp</h3>
        <p class="py-2 text-sm text-gray-500">Kirim ke <strong id="wa-lead-name"></strong>.</p>

        @if(($wahaSenders ?? collect())->isEmpty())
            <div class="alert alert-warning my-3">Belum ada sender aktif. Tambahkan sender terlebih dulu di menu WhatsApp.</div>
        @endif

        <div class="mt-4 space-y-4">
            <div>
                <label class="label"><span class="label-text">Kirim Dari</span></label>
                <select id="wa-sender-selector" class="select select-bordered w-full" {{ ($wahaSenders ?? collect())->isEmpty() ? 'disabled' : '' }}>
                    <option value="">-- Pilih Nomor Pengirim --</option>
                    @foreach (($wahaSenders ?? collect()) as $sender)
                        <option value="{{ $sender->id }}">{{ $sender->name }} ({{ $sender->number }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Template Pesan</span></label>
                <select id="wa-template-selector" class="select select-bordered w-full">
                    <option value="">-- Pilih template --</option>
                    @foreach (($whatsappTemplates ?? collect()) as $template)
                        <option value="{{ $template->id }}" data-body="{{ e($template->body) }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>
            <textarea id="wa-message-preview" class="textarea textarea-bordered w-full h-32" placeholder="Pratinjau pesan..."></textarea>
        </div>
        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Batal</a>
            <button id="wa-send-button" class="btn btn-success" disabled>Kirim</button>
        </div>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- Bulk WhatsApp Modal (WAHA) --}}
<div id="bulk_whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Kirim WhatsApp Massal</h3>
        <p class="py-2 text-sm text-gray-500">Pesan akan dikirim ke <strong><span id="bulk-selected-count-modal">0</span></strong> lead terpilih.</p>

        @if(($wahaSenders ?? collect())->isEmpty())
            <div class="alert alert-warning my-3">Belum ada sender aktif. Tambahkan sender terlebih dulu di menu WhatsApp.</div>
        @endif

        <div class="mt-4 space-y-4">
            <div>
                <label class="label"><span class="label-text">Kirim Dari</span></label>
                <select id="bulk-wa-sender-selector" class="select select-bordered w-full" {{ ($wahaSenders ?? collect())->isEmpty() ? 'disabled' : '' }}>
                    <option value="">-- Pilih Nomor Pengirim --</option>
                    @foreach (($wahaSenders ?? collect()) as $sender)
                        <option value="{{ $sender->id }}">{{ $sender->name }} ({{ $sender->number }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Template Pesan</span></label>
                <select id="bulk-wa-template-selector" class="select select-bordered w-full">
                    <option value="">-- Pilih template --</option>
                    @foreach (($whatsappTemplates ?? collect()) as $template)
                        <option value="{{ $template->id }}" data-body="{{ e($template->body) }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>
            <textarea id="bulk-wa-message-preview" class="textarea textarea-bordered w-full h-32" placeholder="Pratinjau pesan massal..."></textarea>
        </div>
        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Batal</a>
            <button id="bulk-wa-send-button" class="btn btn-success" disabled>Kirim</button>
        </div>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = '{{ csrf_token() }}';

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

    // ===== Bulk selection =====
    const selectAllGroup = document.querySelectorAll('.group-select-all');
    const leadCheckboxes = document.querySelectorAll('.lead-checkbox');
    const bulkBtn = document.getElementById('bulk-whatsapp-trigger');
    const bulkCount = document.getElementById('bulk-selected-count');
    const bulkCountModal = document.getElementById('bulk-selected-count-modal');

    function selectedCbs() {
        return Array.from(document.querySelectorAll('.lead-checkbox:checked'))
              .filter(cb => sanitize(cb.dataset.phone).length >= 7);
    }
    function updateBulkUI() {
        const count = selectedCbs().length;
        bulkCount.textContent = count;
        if (bulkCountModal) bulkCountModal.textContent = count;
        bulkBtn.classList.toggle('hidden', count===0);
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

        const row = document.getElementById(`lead-row-${currentLeadId}`);
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

    function updateBulkWaSendButton() {
        const tplSelected = bulkTplSel && bulkTplSel.value !== '';
        const senderSelected = bulkSenderSel && bulkSenderSel.value !== '';
        const count = selectedCbs().length;
        bulkSend.disabled = !(tplSelected && senderSelected && count > 0);
        const label = document.getElementById('bulk-selected-count-modal');
        if (label) label.textContent = count;
    }
    if (bulkTplSel) bulkTplSel.addEventListener('change', function(){
        bulkPrev.value = this.selectedOptions?.[0]?.dataset?.body || '';
        updateBulkWaSendButton();
    });
    if (bulkSenderSel) bulkSenderSel.addEventListener('change', updateBulkWaSendButton);
    leadCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkWaSendButton));

    if (bulkSend) bulkSend.addEventListener('click', function(){
        const selectedIds = selectedCbs().map(cb => parseInt(cb.value, 10));
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
