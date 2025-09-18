@extends('layouts.app')
@section('title', 'Leads - Matik Growth Hub')

@section('content')
<div class="container mx-auto py-6">

    {{-- Alerts (Sudah theme-aware) --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m7 10a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><strong>Terdapat kesalahan!</strong>
                    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </span>
            </div>
        </div>
    @endif

    {{-- Header Halaman --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4" data-aos="fade-down">
        <div>
            {{-- FIXED: Menggunakan warna teks theme-aware --}}
            <h1 class="text-3xl font-bold text-base-content">Manajemen Leads</h1>
            <p class="text-base-content/70 mt-1">Lacak dan kelola semua calon pelanggan Anda di satu tempat.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="#create_lead_modal" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Tambah Lead
            </a>
            <a href="#bulk_whatsapp_modal" id="bulk-whatsapp-trigger" class="btn btn-success hidden">
                Kirim WA (<span id="bulk-selected-count">0</span>)
            </a>
             <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-secondary">Opsi Lain</label>
                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-10 border border-base-300/50">
                    <li><a href="#import_modal">Import dari File</a></li>
                    <li><a href="{{ route('lead-follow-up-rules.index') }}">Atur Reminder Follow Up</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-md border border-base-300/50 mt-6" data-aos="fade-up">
        <form action="{{ route('leads.index') }}" method="GET" class="card-body p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <input type="text" name="search" placeholder="Cari nama, email, atau nama toko..." value="{{ e(request('search')) }}" class="input input-bordered w-full sm:col-span-2 md:col-span-1">
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
                <button type="submit" class="btn btn-primary">Terapkan</button>
            </div>
        </form>
    </div>

    @php
        $all = $leads->getCollection();
        $groups = [
            ['label' => 'Belum di-chat', 'items' => $all->where('chat_count', 0), 'bg' => 'bg-info/10', 'accent' => 'text-info', 'delay' => 50],
            ['label' => 'Sudah chat 1x', 'items' => $all->where('chat_count', 1), 'bg' => 'bg-success/10', 'accent' => 'text-success', 'delay' => 100],
            ['label' => 'Sudah chat 2x', 'items' => $all->where('chat_count', 2), 'bg' => 'bg-warning/10', 'accent' => 'text-warning', 'delay' => 150],
            ['label' => 'Sudah chat 3x', 'items' => $all->where('chat_count', 3), 'bg' => 'bg-accent/10', 'accent' => 'text-accent', 'delay' => 200],
            ['label' => 'Sudah chat 4x+', 'items' => $all->where('chat_count', '>=', 4), 'bg' => 'bg-error/10', 'accent' => 'text-error', 'delay' => 250],
        ];
        $statusLabel = ['trial'=>'Trial','active'=>'Aktif','nonactive'=>'Tidak Aktif','converted'=>'Konversi','churn'=>'Dibatalkan'];
    @endphp

    {{-- Kategori / Kolom Status Chat --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        @foreach($groups as $g)
        <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="{{ $g['delay'] }}">
            <div class="card-body p-0">
                <div class="p-4 border-b border-base-300/50 flex items-center justify-between">
                    <h4 class="font-semibold {{ $g['accent'] }}">{{ $g['label'] }}</h4>
                    <span class="badge badge-ghost">{{ $g['items']->count() }} lead</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="p-3"><label><input type="checkbox" class="checkbox checkbox-xs group-select-all"></label></th>
                                <th>Nama</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($g['items'] as $lead)
                            <tr id="lead-row-{{ $lead->id }}" class="hover"
                                data-phone="{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}"
                                data-name="{{ e($lead->name ?? ($lead->store_name ?? 'Pelanggan')) }}">
                                <td class="p-3"><label><input type="checkbox" class="checkbox checkbox-xs lead-checkbox" value="{{ $lead->id }}" data-phone="{{ preg_replace('/\D+/', '', (string)($lead->phone ?? '')) }}" data-name="{{ e($lead->name) }}"></label></td>
                                <td>
                                    <a href="{{ route('leads.show', $lead) }}" class="link link-hover link-primary font-medium">{{ $lead->name ?? '-' }}</a>
                                    <div class="text-xs text-base-content/60">{{ $lead->phone ?? '-' }}</div>
                                </td>
                                <td><span class="badge badge-outline">{{ $statusLabel[$lead->status] ?? $lead->status }}</span></td>
                                <td class="text-center">
                                    <div class="dropdown dropdown-end">
                                        <label tabindex="0" class="btn btn-ghost btn-xs">opsi</label>
                                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40 z-10 border border-base-300/50">
                                            @if($lead->phone)
                                                <li><a href="#whatsapp_modal" onclick="openWhatsAppModal({{ $lead->id }})">Kirim WA</a></li>
                                                <li><a href="#" onclick="return manualChat({{ $lead->id }})">Chat Manual</a></li>
                                            @endif
                                            <li><a href="#edit_lead_modal_{{ $lead->id }}" onclick="prepareEditModal({{ $lead->id }})">Edit</a></li>
                                            <li>
                                                <form action="{{ route('leads.destroy', $lead) }}" method="POST" onsubmit="return confirm('Hapus lead ini secara permanen?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-error w-full text-left">Hapus</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-base-content/60 py-8">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-6" data-aos="fade-up">
        {{ $leads->links() }}
    </div>
</div>

{{-- ===== Modals ===== --}}

{{-- Create --}}
<div id="create_lead_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('leads.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg text-base-content">Tambah Lead Baru</h3>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" value="{{ e(old('name')) }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" value="{{ e(old('email')) }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">No. Whatsapp</span></label>
                    <input type="text" name="phone" value="{{ e(old('phone')) }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Nama Toko</span></label>
                    <input type="text" name="store_name" value="{{ e(old('store_name')) }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Tanggal Daftar</span></label>
                    <input id="create_registered_at" type="datetime-local" name="registered_at" value="{{ e(old('registered_at', now()->format('Y-m-d\TH:i'))) }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Tanggal Habis Trial</span></label>
                    <input id="create_trial_ends_at" type="date" name="trial_ends_at" value="{{ e(old('trial_ends_at', now()->addDays(7)->format('Y-m-d'))) }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select name="status" class="select select-bordered w-full" required>
                        <option value="trial" @selected(old('status', 'trial')=='trial')>Trial</option>
                        <option value="active" @selected(old('status')=='active')>Aktif</option>
                        <option value="nonactive" @selected(old('status')=='nonactive')>Tidak Aktif</option>
                        <option value="converted" @selected(old('status')=='converted')>Konversi</option>
                        <option value="churn" @selected(old('status')=='churn')>Dibatalkan</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Owner</span></label>
                    <select name="owner_id" class="select select-bordered w-full" required>
                        @forelse($users as $user)
                            <option value="{{ $user->id }}" @selected(old('owner_id', auth()->id()) == $user->id)>{{ $user->name }}</option>
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
@foreach ($leads->items() as $lead)
<div id="edit_lead_modal_{{ $lead->id }}" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('leads.update', $lead) }}" method="POST">
            @csrf @method('PATCH')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg text-base-content">Edit Lead: {{ $lead->name }}</h3>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" value="{{ e(old('name', $lead->name)) }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" value="{{ e(old('email', $lead->email)) }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">No. Whatsapp</span></label>
                    <input type="text" name="phone" value="{{ e(old('phone', $lead->phone)) }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Nama Toko</span></label>
                    <input type="text" name="store_name" value="{{ e(old('store_name', $lead->store_name)) }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Tanggal Daftar</span></label>
                    <input id="edit_registered_at_{{ $lead->id }}" type="datetime-local" name="registered_at" value="{{ e(old('registered_at', $lead->created_at?->format('Y-m-d\TH:i'))) }}" class="input input-bordered w-full edit-registered-at" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Tanggal Habis Trial</span></label>
                    <input id="edit_trial_ends_at_{{ $lead->id }}" type="date" name="trial_ends_at" value="{{ e(old('trial_ends_at', $lead->trial_ends_at?->format('Y-m-d'))) }}" class="input input-bordered w-full edit-trial-ends-at" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select name="status" class="select select-bordered w-full status-selector" data-lead-id="{{ $lead->id }}" required>
                        <option value="trial" @selected(old('status', $lead->status)=='trial')>Trial</option>
                        <option value="active" @selected(old('status', $lead->status)=='active')>Aktif</option>
                        <option value="nonactive" @selected(old('status', $lead->status)=='nonactive')>Tidak Aktif</option>
                        <option value="converted" @selected(old('status', $lead->status)=='converted')>Konversi</option>
                        <option value="churn" @selected(old('status', $lead->status)=='churn')>Dibatalkan</option>
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
                <div id="subscription_form_{{ $lead->id }}" class="hidden md:col-span-2 mt-2 pt-4 border-t border-base-300/50 space-y-4">
                    <h4 class="font-semibold text-md text-base-content">Detail Langganan</h4>
                    <div><label class="label"><span class="label-text">Nama Paket</span></label>
                        <input type="text" name="plan" value="{{ e(old('plan', $lead->subscription->plan ?? '')) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Jumlah (Rp)</span></label>
                        <input type="number" name="amount" value="{{ e(old('amount', $lead->subscription->amount ?? '')) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Siklus</span></label>
                        <select name="cycle" class="select select-bordered w-full">
                            <option value="monthly" @selected(old('cycle', $lead->subscription->cycle ?? '')=='monthly')>Bulanan</option>
                            <option value="yearly" @selected(old('cycle', $lead->subscription->cycle ?? '')=='yearly')>Tahunan</option>
                        </select></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="label"><span class="label-text">Mulai</span></label>
                            <input type="date" name="start_date" value="{{ e(old('start_date', optional($lead->subscription)->start_date ? $lead->subscription->start_date->format('Y-m-d') : now()->format('Y-m-d'))) }}" class="input input-bordered w-full" /></div>
                        <div><label class="label"><span class="label-text">Berakhir (Opsional)</span></label>
                            <input type="date" name="end_date" value="{{ e(old('end_date', optional($lead->subscription)->end_date ? $lead->subscription->end_date->format('Y-m-d') : '')) }}" class="input input-bordered w-full" /></div>
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

{{-- Import Modal --}}
<div id="import_modal" class="modal">
    <div class="modal-box">
        <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg text-base-content">Import Leads</h3>
            <p class="py-2 text-sm text-base-content/70">Pilih file .xlsx atau .csv untuk diimpor. Pastikan kolom sesuai dengan format yang ditentukan.</p>
            <div class="form-control w-full mt-4">
                 <input type="file" name="file" accept=".xlsx,.csv,.txt" class="file-input file-input-bordered w-full" required>
            </div>
            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- Single WhatsApp Modal (WAHA) --}}
<div id="whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg text-base-content">Kirim WhatsApp</h3>
        <p class="py-2 text-sm text-base-content/70">Kirim ke <strong id="wa-lead-name"></strong>.</p>

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
        <h3 class="font-bold text-lg text-base-content">Kirim WhatsApp Massal</h3>
        <p class="py-2 text-sm text-base-content/70">Pesan akan dikirim ke <strong><span id="bulk-selected-count-modal">0</span></strong> lead terpilih. Gunakan placeholder <code>@{{name}}</code>.</p>

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
    const fillTemplate = (tpl, name) => (tpl || '')
        .replace(/\{\{\s*name\s*\}\}/gi, name)
        .replace(/\{\{\s*nama\s*\}\}/gi, name)
        .replace(/\{\{\s*nama_pelanggan\s*\}\}/gi, name);
    const sanitize = s => (s||'').replace(/\D+/g, '');

    // ===== Min date logic =====
    function setMinDate(regInput, endInput) {
        if (!regInput || !endInput) return;
        try {
            const dt = new Date(regInput.value || new Date());
            const yyyy = dt.getFullYear();
            const mm = String(dt.getMonth()+1).padStart(2,'0');
            const dd = String(dt.getDate()).padStart(2,'0');
            endInput.min = `${yyyy}-${mm}-${dd}`;
            if (endInput.value && endInput.value < endInput.min) endInput.value = endInput.min;
        } catch(e) {
            console.error("Invalid date value for minDate logic:", regInput.value);
        }
    }
    const createReg = document.getElementById('create_registered_at');
    const createEnd = document.getElementById('create_trial_ends_at');
    setMinDate(createReg, createEnd);
    if (createReg) createReg.addEventListener('change', ()=>setMinDate(createReg, createEnd));

    // FIXED: Menggunakan selector berbasis class untuk menghindari error sintaks dari perulangan PHP
    document.querySelectorAll('.modal').forEach(modal => {
        const regInput = modal.querySelector('.edit-registered-at');
        const endInput = modal.querySelector('.edit-trial-ends-at');
        if (regInput && endInput) {
            setMinDate(regInput, endInput);
            regInput.addEventListener('change', () => setMinDate(regInput, endInput));
        }
    });


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
        if(bulkCount) bulkCount.textContent = c;
        if (bulkCountModal) bulkCountModal.textContent = c;
        if(bulkBtn) bulkBtn.classList.toggle('hidden', c===0);
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
        const row = currentLeadId ? document.getElementById(`lead-row-${currentLeadId}`) : null;
        const hasPhone = !!(row && sanitize(row.dataset.phone).length >= 7);
        if(waSend) waSend.disabled = !(tplSelected && senderSelected && hasPhone);
    }

    window.openWhatsAppModal = function(leadId) {
        currentLeadId = leadId;
        const row = document.getElementById(`lead-row-${leadId}`);
        const leadNameEl = document.getElementById('wa-lead-name');
        if(leadNameEl) leadNameEl.textContent = row?.dataset?.name || 'Pelanggan';
        if (waTplSel) waTplSel.selectedIndex = 0;
        if (waSenderSel) waSenderSel.selectedIndex = 0;
        if(waPrev) waPrev.value = '';
        updateSingleWaSendButton();
    }

    if (waTplSel) waTplSel.addEventListener('change', function(){
        const body = this.selectedOptions?.[0]?.dataset?.body || '';
        if (!currentLeadId) return;
        const row = document.getElementById(`lead-row-${currentLeadId}`);
        const name = row?.dataset?.name || '';
        if(waPrev) waPrev.value = fillTemplate(body, name);
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

        // gunakan route WAHA bawaan
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
    const bulkTplSel = document.getElementById('bulk-wa-template-selector');
    const bulkPrev = document.getElementById('bulk-wa-message-preview');
    const bulkSend = document.getElementById('bulk-wa-send-button');
    const bulkSenderSel = document.getElementById('bulk-wa-sender-selector');
    const bulkCountModalEl = document.getElementById('bulk-selected-count-modal');

    function updateBulkWaSendButton() {
        const tplSelected = bulkTplSel && bulkTplSel.value !== '';
        const senderSelected = bulkSenderSel && bulkSenderSel.value !== '';
        const selected = calcSelected();
        if(bulkSend) bulkSend.disabled = !(tplSelected && senderSelected && selected.length > 0);
        if (bulkCountModalEl) bulkCountModalEl.textContent = selected.length;
    }
    if (bulkTplSel) bulkTplSel.addEventListener('change', function(){
        if(bulkPrev) bulkPrev.value = this.selectedOptions?.[0]?.dataset?.body || '';
        updateBulkWaSendButton();
    });
    if (bulkSenderSel) bulkSenderSel.addEventListener('change', updateBulkWaSendButton);
    leadCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkWaSendButton));

    if (bulkSend) bulkSend.addEventListener('click', function(){
        const selected = calcSelected().map(cb => ({ name: (cb.dataset.name || '').trim(), phone: sanitize(cb.dataset.phone) }));
        const senderId = bulkSenderSel.value;
        const tplBody = bulkPrev.value;

        if (!tplBody || selected.length === 0 || !senderId) {
            alert('Harap pilih template dan pengirim, serta setidaknya satu lead.');
            return;
        }

        fetch('{{ route('waha.sendBulkMessages') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
            body: JSON.stringify({ sender_id: senderId, recipients: selected, message: tplBody })
        })
        .then(r => r.json().catch(()=>({})).then(d => ({ ok:r.ok, d })))
        .then(({ok,d}) => {
            const success = ok && (d?.success !== false);
            alert(success ? 'Pengiriman massal diproses.' : ('Gagal kirim massal' + (d?.message ? ': '+d.message : '.')));
            if (success) location.reload();
        })
        .catch(() => alert('Terjadi kesalahan saat mengirim pesan massal.'));
    });

    // ===== Manual WA link (wa.me) + penanda chat_count =====
    window.manualChat = function(leadId) {
        const row = document.getElementById(`lead-row-${leadId}`);
        const phone = sanitize(row?.dataset?.phone);
        if (!phone) return false;

        // tandai di server (pastikan route('leads.markChatted') ada)
        fetch('{{ route('leads.markChatted', ['lead' => 'LEAD_ID']) }}'.replace('LEAD_ID', leadId), {
            method: 'POST',
            headers: {'Content-Type': 'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
            body: JSON.stringify({})
        }).finally(() => {
            // buka wa.me
            const url = `https://wa.me/${phone}`;
            window.open(url, '_blank');
            // muat ulang agar kategori ter-update
            setTimeout(()=>location.reload(), 800);
        });

        return false; // prevent default link
    };
});
</script>
@endpush

