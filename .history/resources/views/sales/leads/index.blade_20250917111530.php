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
        <div class="card shadow-lg border border-base-300/50 {{ $g['bg'] }}" data-aos="fade-up" data-aos-delay="{{ $g['delay'] }}">
            <div class="card-body p-0">
                <div class="p-4 border-b border-base-content/10 flex items-center justify-between">
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
                {{-- Form fields... --}}
            </div>
            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Lead</button>
            </div>
        </form>
    </div>
    {{-- FIXED: Menghapus modal-backdrop dari sini --}}
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
                {{-- Form fields... --}}
            </div>
            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Update Lead</button>
            </div>
        </form>
    </div>
    {{-- FIXED: Menghapus modal-backdrop dari sini --}}
</div>
@endforeach

{{-- Import Modal --}}
<div id="import_modal" class="modal">
    <div class="modal-box">
        <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg text-base-content">Import Leads</h3>
            <p class="py-2 text-sm text-base-content/70">Pilih file .xlsx atau .csv untuk diimpor.</p>
            <div class="form-control w-full mt-4">
                 <input type="file" name="file" accept=".xlsx,.csv,.txt" class="file-input file-input-bordered w-full" required>
            </div>
            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
    {{-- FIXED: Menghapus modal-backdrop dari sini --}}
</div>

{{-- Single WhatsApp Modal (WAHA) --}}
<div id="whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg text-base-content">Kirim WhatsApp</h3>
        <p class="py-2 text-sm text-base-content/70">Kirim ke <strong id="wa-lead-name"></strong>.</p>
        {{-- Form fields... --}}
        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Batal</a>
            <button id="wa-send-button" class="btn btn-success" disabled>Kirim</button>
        </div>
    </div>
    {{-- FIXED: Menghapus modal-backdrop dari sini --}}
</div>

{{-- Bulk WhatsApp Modal (WAHA) --}}
<div id="bulk_whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg text-base-content">Kirim WhatsApp Massal</h3>
        <p class="py-2 text-sm text-base-content/70">Kirim ke <strong><span id="bulk-selected-count-modal">0</span></strong> lead terpilih.</p>
        {{-- Form fields... --}}
        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Batal</a>
            <button id="bulk-wa-send-button" class="btn btn-success" disabled>Kirim</button>
        </div>
    </div>
    {{-- FIXED: Menghapus modal-backdrop dari sini --}}
</div>
@endsection

@push('scripts')
{{-- Skrip fungsionalitas dipertahankan karena tidak berhubungan dengan styling --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = '{{ csrf_token() }}';

    // ... sisa skrip tidak berubah ...

});
</script>
@endpush

