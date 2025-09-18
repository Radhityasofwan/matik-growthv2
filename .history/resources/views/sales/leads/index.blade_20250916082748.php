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
        <form action="{{ route('leads.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" name="search" placeholder="Cari nama, email, atau nama toko..." value="{{ request('search') }}" class="input input-bordered w-full">
            <select name="status" class="select select-bordered w-full">
                <option value="">Semua Status</option>
                <option value="active" @selected(request('status')=='active')>Aktif</option>
                <option value="nonactive" @selected(request('status')=='nonactive')>Tidak Aktif</option>
                <option value="converted" @selected(request('status')=='converted')>Konversi</option>
                <option value="churn" @selected(request('status')=='churn')>Dibatalkan</option>
                <option value="trial" @selected(request('status')=='trial')>Trial</option>
            </select>
            {{-- Smart Filter: Chat Status --}}
            <select name="chat_status" class="select select-bordered w-full">
                <option value="">Semua Chat Status</option>
                <option value="no_chat" @selected(request('chat_status')=='no_chat')>Belum di-chat</option>
                <option value="chat_1_no_reply" @selected(request('chat_status')=='chat_1_no_reply')>Chat 1× — belum balas</option>
                <option value="chat_2_no_reply" @selected(request('chat_status')=='chat_2_no_reply')>Chat 2× — belum balas</option>
                <option value="chat_3_no_reply" @selected(request('chat_status')=='chat_3_no_reply')>Chat 3× — belum balas</option>
            </select>
            <select name="per_page" class="select select-bordered w-full">
                @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" @selected(request('per_page',10)==$n)>{{ $n }} / halaman</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">Terapkan</button>
        </form>
    </div>

    @php
        // Ambil kumpulan item di halaman aktif
        $all = $leads->getCollection();

        // Pastikan chat_count selalu ada (fallback 0)
        $all->transform(function($l){ $l->chat_count = $l->chat_count ?? 0; return $l; });

        // Kelompokkan berdasarkan chat_count
        $group0 = $all->where('chat_count', 0);
        $group1 = $all->where('chat_count', 1);
        $group2 = $all->where('chat_count', 2);
        $group3 = $all->where('chat_count', 3);
        $group4 = $all->filter(fn($l) => (int)$l->chat_count >= 4);

        $groups = [
            ['label' => 'Belum di-chat', 'items' => $group0, 'bg' => 'bg-info/10', 'accent' => 'text-info'],
            ['label' => 'Sudah chat 1x', 'items' => $group1, 'bg' => 'bg-success/10', 'accent' => 'text-success'],
            ['label' => 'Sudah chat 2x', 'items' => $group2, 'bg' => 'bg-warning/10', 'accent' => 'text-warning'],
            ['label' => 'Sudah chat 3x', 'items' => $group3, 'bg' => 'bg-accent/10', 'accent' => 'text-accent'],
            ['label' => 'Sudah chat 4x+', 'items' => $group4, 'bg' => 'bg-error/10', 'accent' => 'text-error'],
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
                            <th>PIC</th>
                            <th>Nama</th>
                            <th>WA</th>
                            <th>Status</th>
                            <th>Chat#</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($g['items'] as $lead)
                        <tr id="lead-row-{{ $lead->id }}"
                            data-phone="{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}"
                            data-name="{{ $lead->name ?? ($lead->store_name ?? 'Pelanggan') }}"
                            data-store="{{ $lead->store_name ?? '' }}"
                            data-trial="{{ $lead->trial_ends_at?->format('d M Y') ?? '' }}">
                            <td><input type="checkbox" class="checkbox checkbox-xs lead-checkbox" value="{{ $lead->id }}" data-phone="{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}" data-name="{{ $lead->name }}"></td>
                            <td>
                                @if($lead->owner)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $lead->owner->avatar_url }}" alt="{{ $lead->owner->name }}" class="h-6 w-6 rounded-full object-cover">
                                        <span class="text-sm">{{ $lead->owner->name }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="font-medium">{{ $lead->name ?? '-' }}</td>
                            <td>{{ $lead->phone ?? '-' }}</td>
                            <td><span class="badge">
                                {{ $statusLabel[$lead->status] ?? $lead->status }}
                            </span></td>
                            <td><span class="badge badge-ghost">{{ (int)($lead->chat_count ?? 0) }}</span></td>
                            <td class="text-center">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    @if($lead->phone)
                                        {{-- WAHA modal --}}
                                        <a href="#whatsapp_modal" onclick="openWhatsAppModal({{ $lead->id }})" class="btn btn-xs btn-success">WA Sender</a>
                                        {{-- Manual link (wa.me) --}}
                                        <a class="btn btn-xs btn-outline btn-info"
                                           href="https://wa.me/{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}"
                                           target="_blank" rel="noopener">Manual</a>
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

                {{-- Subscription (muncul saat converted) --}}
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

        @if($wahaSenders->isEmpty())
            <div class="alert alert-warning my-3">Belum ada sender aktif. Tambahkan sender terlebih dulu di menu WhatsApp.</div>
        @endif

        <div class="mt-4 space-y-4">
            <div>
                <label class="label"><span class="label-text">Kirim Dari</span></label>
                <select id="wa-sender-selector" class="select select-bordered w-full" {{ $wahaSenders->isEmpty() ? 'disabled' : '' }}>
                    <option value="">-- Pilih Nomor Pengirim --</option>
                    @foreach ($wahaSenders as $sender)
                        <option value="{{ $sender->id }}">{{ $sender->name }} ({{ $sender->number }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Template Pesan</span></label>
                <select id="wa-template-selector" class="select select-bordered w-full">
                    <option value="">-- Pilih template --</option>
                    @foreach ($whatsappTemplates as $template)
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
        <p class="py-2 text-sm text-gray-500">Pesan akan dikirim ke <strong><span id="bulk-selected-count-modal">0</span></strong> lead terpilih. Gunakan placeholder <code>@{{name}}</code>.</p>

        @if($wahaSenders->isEmpty())
            <div class="alert alert-warning my-3">Belum ada sender aktif. Tambahkan sender terlebih dulu di menu WhatsApp.</div>
        @endif

        <div class="mt-4 space-y-4">
            <div>
                <label class="label"><span class="label-text">Kirim Dari</span></label>
                <select id="bulk-wa-sender-selector" class="select select-bordered w-full" {{ $wahaSenders->isEmpty() ? 'disabled' : '' }}>
                    <option value="">-- Pilih Nomor Pengirim --</option>
                    @foreach ($wahaSenders as $sender)
                        <option value="{{ $sender->id }}">{{ $sender->name }} ({{ $sender->number }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Template Pesan</span></label>
                <select id="bulk-wa-template-selector" class="select select-bordered w-full">
                    <option value="">-- Pilih template --</option>
                    @foreach ($whatsappTemplates as $template)
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
    const sanitize = s => (s||'').replace(/\D+/g, '');
    const fillTemplate = (tpl, ctx) => (tpl || '')
        .replace(/\{\{\s*name\s*\}\}/gi, ctx.name || '')
        .replace(/\{\{\s*nama\s*\}\}/gi, ctx.name || '')
        .replace(/\{\{\s*nama_pelanggan\s*\}\}/gi, ctx.name || '')
        .replace(/\{\{\s*store_name\s*\}\}/gi, ctx.store || '')
        .replace(/\{\{\s*trial_ends_at\s*\}\}/gi, ctx.trial || '');

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

    // ===== Bulk selection (per kategori & lintas kategori) =====
    const selectAllGroup = document.querySelectorAll('.group-select-all');
    const leadCheckboxes  = document.querySelectorAll('.lead-checkbox');
    const bulkBtn   = document.getElementById('bulk-whatsapp-trigger');
    const bulkCount = document.getElementById('bulk-selected-count');
    const bulkCountModal = document.getElementById('bulk-selected-count-modal');

    function chosen() {
        return Array.from(document.querySelectorAll('.lead-checkbox:checked'))
              .filter(cb => sanitize(cb.dataset.phone).length >= 7);
    }
    function refreshBulkUI() {
        const list = chosen();
        const c = list.length;
        bulkCount.textContent = c;
        if (bulkCountModal) bulkCountModal.textContent = c;
        bulkBtn.classList.toggle('hidden', c===0);
    }
    selectAllGroup.forEach(sel => sel.addEventListener('change', function(){
        const tbody = this.closest('table')?.querySelector('tbody');
        if (!tbody) return;
        tbody.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = this.checked);
        refreshBulkUI();
    }));
    leadCheckboxes.forEach(cb => cb.addEventListener('change', refreshBulkUI));

    // ===== Single WA via WAHA =====
    let currentLeadId = null;
    const waTplSel     = document.getElementById('wa-template-selector');
    const waPrev       = document.getElementById('wa-message-preview');
    const waSend       = document.getElementById('wa-send-button');
    const waSenderSel  = document.getElementById('wa-sender-selector');

    function updateSingleWaSendButton() {
        const tplSelected    = waTplSel && waTplSel.value !== '';
        const senderSelected = waSenderSel && waSenderSel.value !== '';
        const hasPhone = !!(currentLeadId && sanitize(document.getElementById(`lead-row-${currentLeadId}`)?.dataset.phone).length >= 7);
        waSend.disabled = !(tplSelected && senderSelected && hasPhone);
    }

    window.openWhatsAppModal = function(leadId) {
        currentLeadId = leadId;
        const row   = document.getElementById(`lead-row-${leadId}`);
        const name  = row?.dataset?.name || 'Pelanggan';
        const store = row?.dataset?.store || '';
        const trial = row?.dataset?.trial || '';
        document.getElementById('wa-lead-name').textContent = name;

        if (waTplSel) waTplSel.selectedIndex = 0;
        if (waSenderSel) waSenderSel.selectedIndex = 0;
        waPrev.value = '';
        // pre-fill jika ada template pertama
        updateSingleWaSendButton();
    }

    if (waTplSel) waTplSel.addEventListener('change', function(){
        if (!currentLeadId) return;
        const row   = document.getElementById(`lead-row-${currentLeadId}`);
        const ctx = {
            name:  row?.dataset?.name || '',
            store: row?.dataset?.store || '',
            trial: row?.dataset?.trial || ''
        };
        const body = this.selectedOptions?.[0]?.dataset?.body || '';
        waPrev.value = fillTemplate(body, ctx);
        updateSingleWaSendButton();
    });
    if (waSenderSel) waSenderSel.addEventListener('change', updateSingleWaSendButton);

    if (waSend) waSend.addEventListener('click', function(e){
        e.preventDefault();
        if (waSend.disabled) return;

        const row = document.getElementById(`lead-row-${currentLeadId}`);
        const phone = sanitize(row?.dataset?.phone);
        const message = waPrev.value;
        const senderId = waSenderSel.value;

        fetch('{{ route('waha.sendMessage') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
            body: JSON.stringify({ sender_id: senderId, recipient: phone, message })
        })
        .then(r => r.json().catch(()=>({})).then(d => ({ ok:r.ok, d })))
        .then(({ok,d}) => {
            const success = ok && (d?.success !== false);
            alert(success ? 'Pesan berhasil dikirim!' : ('Gagal mengirim pesan' + (d?.message ? ': '+d.message : '.')));
            if (success) location.reload();
        })
        .catch(() => alert('Terjadi kesalahan saat mengirim pesan.'));
    });

    // ===== Bulk via WAHA =====
    const bulkTplSel     = document.getElementById('bulk-wa-template-selector');
    const bulkPrev       = document.getElementById('bulk-wa-message-preview');
    const bulkSend       = document.getElementById('bulk-wa-send-button');
    const bulkSenderSel  = document.getElementById('bulk-wa-sender-selector');

    function refreshBulkSendButton() {
        const tplSelected    = bulkTplSel && bulkTplSel.value !== '';
        const senderSelected = bulkSenderSel && bulkSenderSel.value !== '';
        const list = chosen();
        bulkSend.disabled = !(tplSelected && senderSelected && list.length > 0);
        const countLbl = document.getElementById('bulk-selected-count-modal');
        if (countLbl) countLbl.textContent = list.length;
    }
    if (bulkTplSel) bulkTplSel.addEventListener('change', function(){
        bulkPrev.value = this.selectedOptions?.[0]?.dataset?.body || '';
        refreshBulkSendButton();
    });
    if (bulkSenderSel) bulkSenderSel.addEventListener('change', refreshBulkSendButton);
    leadCheckboxes.forEach(cb => cb.addEventListener('change', refreshBulkSendButton));

    if (bulkSend) bulkSend.addEventListener('click', function(){
        const list = chosen().map(cb => {
            const row = document.getElementById(`lead-row-${cb.value}`);
            return {
                name:  (row?.dataset?.name || '').trim(),
                phone: sanitize(row?.dataset?.phone)
            };
        });

        const senderId = bulkSenderSel.value;
        const tplBody  = bulkPrev.value;

        if (!tplBody || list.length === 0 || !senderId) {
            alert('Harap pilih template dan pengirim, serta setidaknya satu lead.');
            return;
        }

        fetch('{{ route('waha.sendBulkMessages') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
            body: JSON.stringify({ sender_id: senderId, recipients: list, message: tplBody })
        })
        .then(r => r.json().catch(()=>({})).then(d => ({ ok:r.ok, d })))
        .then(({ok,d}) => {
            const success = ok && (d?.success !== false);
            alert(success ? 'Pengiriman massal diproses.' : ('Gagal kirim massal' + (d?.message ? ': '+d.message : '.')));
            if (success) location.reload();
        })
        .catch(() => alert('Terjadi kesalahan saat mengirim pesan massal.'));
    });
});
</script>
@endpush
