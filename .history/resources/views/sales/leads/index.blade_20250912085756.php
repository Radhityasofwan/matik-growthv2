@extends('layouts.app')

@section('title', 'Leads - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">

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
                <span>
                    <strong>Terdapat kesalahan!</strong>
                    <ul class="list-disc ml-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </span>
            </div>
        </div>
    @endif

    {{-- Header + Actions --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="text-2xl md:text-3xl font-semibold text-gray-800 dark:text-gray-100">Leads</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lacak dan kelola calon pelanggan Anda.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            {{-- Import --}}
            <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv,.txt" class="file-input file-input-bordered file-input-sm max-w-xs" required>
                <button type="submit" class="btn btn-secondary btn-sm">Import</button>
            </form>

            {{-- Bulk WA trigger (shown when rows selected) --}}
            <a href="#bulk_whatsapp_modal" id="bulk-whatsapp-trigger" class="btn btn-success btn-sm hidden">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="mr-2" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
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
                <option value="trial" @selected(request('status')=='trial')>Trial</option>
                <option value="active" @selected(request('status')=='active')>Active</option>
                <option value="nonactive" @selected(request('status')=='nonactive')>Nonactive</option>
                <option value="converted" @selected(request('status')=='converted')>Converted</option>
                <option value="churn" @selected(request('status')=='churn')>Churn</option>
            </select>
            <select name="per_page" class="select select-bordered w-full">
                @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" @selected(request('per_page',10)==$n)>{{ $n }} / halaman</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">Terapkan</button>
        </form>
    </div>

    @if($leads->isEmpty() && !request()->query())
        {{-- Empty State --}}
        <div class="text-center py-20">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Belum ada lead</h3>
            <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan lead pertama Anda atau impor dari file.</p>
            <div class="mt-6 flex gap-2 justify-center">
                <a href="#create_lead_modal" class="btn btn-primary">Lead Baru</a>
            </div>
        </div>
    @else
        {{-- Table --}}
        <div class="mt-4 overflow-x-auto">
            <div class="inline-block min-w-full shadow-md rounded-2xl overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 border-b-2 border-gray-200 dark:border-gray-600">
                                <input type="checkbox" id="select-all-checkbox" class="checkbox checkbox-sm" />
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Nama Owner</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Nama Toko</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tanggal Daftar</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tanggal Habis</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">No. Whatsapp</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Email</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        @forelse($leads as $lead)
                        <tr id="lead-row-{{ $lead->id }}" data-phone="{{ $lead->phone }}" data-name="{{ $lead->name }}">
                            {{-- checkbox --}}
                            <td class="px-4 py-4 border-b border-gray-200 dark:border-gray-700">
                                <input type="checkbox" class="checkbox checkbox-sm lead-checkbox" value="{{ $lead->id }}" data-phone="{{ $lead->phone }}" data-name="{{ $lead->name }}">
                            </td>

                            {{-- Status --}}
                            <td class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <span class="badge
                                    @switch($lead->status)
                                        @case('trial') badge-info @break
                                        @case('active') badge-success @break
                                        @case('nonactive') badge-ghost @break
                                        @case('converted') badge-accent @break
                                        @case('churn') badge-error @break
                                    @endswitch">
                                    {{ ucfirst($lead->status) }}
                                </span>
                            </td>

                            {{-- Owner --}}
                            <td class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->owner?->name ?? '-' }}
                            </td>

                            {{-- Store --}}
                            <td class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 text-sm font-medium">
                                {{ $lead->store_name ?? '-' }}
                            </td>

                            {{-- Created --}}
                            <td class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->created_at?->format('d M Y H:i:s') }}
                            </td>

                            {{-- Trial ends / subscription end --}}
                            <td class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->trial_ends_at?->format('d M Y') ?? ($lead->subscription?->end_date?->format('d M Y') ?? '-') }}
                            </td>

                            {{-- Phone --}}
                            <td class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->phone ?? '-' }}
                            </td>

                            {{-- Email --}}
                            <td class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->email }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-center gap-3">
                                    {{-- WhatsApp --}}
                                    @if($lead->phone)
                                        <a href="#whatsapp_modal" onclick="openWhatsAppModal({{ $lead->id }})" class="tooltip text-green-500 hover:text-green-600" data-tip="Kirim WhatsApp">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
                                        </a>
                                    @else
                                        <span class="tooltip text-gray-400 cursor-not-allowed" data-tip="Nomor WA tidak ada">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326z"/></svg>
                                        </span>
                                    @endif

                                    {{-- Menu --}}
                                    <div class="dropdown dropdown-end">
                                        <label tabindex="0" class="btn btn-ghost btn-xs">...</label>
                                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40 z-10">
                                            <li><a href="#edit_lead_modal_{{ $lead->id }}" onclick="prepareEditModal({{ $lead->id }})">Edit</a></li>
                                            <li>
                                                <form action="{{ route('leads.destroy', $lead) }}" method="POST" onsubmit="return confirm('Hapus lead ini secara permanen?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-full text-left text-error p-2">Hapus</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="px-5 py-10 text-center text-gray-500">Tidak ada lead ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Footer --}}
                <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col sm:flex-row items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Menampilkan {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} dari {{ $leads->total() }} entri
                    </div>
                    <div class="mt-4 sm:mt-0">
                        {{ $leads->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- ===== Modals ===== --}}

{{-- Create Lead --}}
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
                    <label class="label"><span class="label-text">Tanggal Habis Trial (opsional)</span></label>
                    <input type="date" name="trial_ends_at" value="{{ old('trial_ends_at') }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select name="status" class="select select-bordered w-full" required>
                        <option value="trial" @selected(old('status')=='trial')>Trial</option>
                        <option value="active" @selected(old('status')=='active')>Active</option>
                        <option value="nonactive" @selected(old('status')=='nonactive')>Nonactive</option>
                        <option value="converted" @selected(old('status')=='converted')>Converted</option>
                        <option value="churn" @selected(old('status')=='churn')>Churn</option>
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

{{-- Edit Lead Modals --}}
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
                    <label class="label"><span class="label-text">Tanggal Habis Trial (opsional)</span></label>
                    <input type="date" name="trial_ends_at" value="{{ old('trial_ends_at', $lead->trial_ends_at?->format('Y-m-d')) }}" class="input input-bordered w-full" />
                </div>
                <div>
                    <label class="label"><span class="label-text">Status</span></label>
                    <select name="status" class="select select-bordered w-full status-selector" data-lead-id="{{ $lead->id }}" required>
                        <option value="trial" @selected(old('status', $lead->status) == 'trial')>Trial</option>
                        <option value="active" @selected(old('status', $lead->status) == 'active')>Active</option>
                        <option value="nonactive" @selected(old('status', $lead->status) == 'nonactive')>Nonactive</option>
                        <option value="converted" @selected(old('status', $lead->status) == 'converted')>Converted</option>
                        <option value="churn" @selected(old('status', $lead->status) == 'churn')>Churn</option>
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

                {{-- Subscription (shown if status converted) --}}
                <div id="subscription_form_{{ $lead->id }}" class="hidden md:col-span-2 mt-2 pt-4 border-t border-gray-200 dark:border-gray-600 space-y-4">
                    <h4 class="font-semibold text-md">Detail Langganan</h4>
                    <div>
                        <label class="label"><span class="label-text">Nama Paket</span></label>
                        <input type="text" name="plan" value="{{ old('plan', $lead->subscription->plan ?? '') }}" class="input input-bordered w-full" />
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Jumlah (Rp)</span></label>
                        <input type="number" name="amount" value="{{ old('amount', $lead->subscription->amount ?? '') }}" class="input input-bordered w-full" />
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Siklus Tagihan</span></label>
                        <select name="cycle" class="select select-bordered w-full">
                            <option value="monthly" @selected(old('cycle', $lead->subscription->cycle ?? '') == 'monthly')>Bulanan</option>
                            <option value="yearly" @selected(old('cycle', $lead->subscription->cycle ?? '') == 'yearly')>Tahunan</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label"><span class="label-text">Tanggal Mulai</span></label>
                            <input type="date" name="start_date" value="{{ old('start_date', optional($lead->subscription)->start_date ? $lead->subscription->start_date->format('Y-m-d') : now()->format('Y-m-d')) }}" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label"><span class="label-text">Tanggal Berakhir (Opsional)</span></label>
                            <input type="date" name="end_date" value="{{ old('end_date', optional($lead->subscription)->end_date ? $lead->subscription->end_date->format('Y-m-d') : '') }}" class="input input-bordered w-full" />
                        </div>
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

{{-- Single WhatsApp Modal --}}
<div id="whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Pilih Template WhatsApp</h3>
        <p class="py-2 text-sm text-gray-500">Kirim ke <strong id="wa-lead-name"></strong>.</p>
        <div class="mt-4 space-y-2">
            <select id="wa-template-selector" class="select select-bordered w-full">
                <option disabled selected>-- Pilih template --</option>
                @foreach ($whatsappTemplates as $template)
                    <option value="{{ $template->body }}">{{ $template->name }}</option>
                @endforeach
            </select>
            <textarea id="wa-message-preview" class="textarea textarea-bordered w-full h-32" placeholder="Pratinjau pesan..."></textarea>
        </div>
        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Batal</a>
            <a id="wa-send-button" href="#" target="_blank" class="btn btn-success btn-disabled">Kirim via WhatsApp</a>
        </div>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

{{-- Bulk WhatsApp Modal --}}
<div id="bulk_whatsapp_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
        <h3 class="font-bold text-lg">Kirim WhatsApp Massal</h3>
        <p class="py-2 text-sm text-gray-500">Pesan akan dikirim ke <strong><span id="bulk-selected-count-modal">0</span></strong> lead terpilih. Gunakan placeholder <code>@{{name}}</code>.</p>
        <div class="mt-4 space-y-2">
            <select id="bulk-wa-template-selector" class="select select-bordered w-full">
                <option disabled selected>-- Pilih template --</option>
                @foreach ($whatsappTemplates as $template)
                    <option value="{{ $template->body }}">{{ $template->name }}</option>
                @endforeach
            </select>
            <textarea id="bulk-wa-message-preview" class="textarea textarea-bordered w-full h-32" placeholder="Pratinjau pesan massal..."></textarea>
        </div>
        <div class="modal-action mt-6">
            <a href="#" class="btn btn-ghost">Batal</a>
            <button id="bulk-wa-send-button" class="btn btn-success btn-disabled">Kirim</button>
        </div>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // === Subscription form toggle (Edit) ===
    function toggleSubscriptionForm(leadId, status) {
        const form = document.getElementById(`subscription_form_${leadId}`);
        if (form) form.classList.toggle('hidden', status !== 'converted');
    }
    window.prepareEditModal = function(leadId) {
        const selector = document.querySelector(`#edit_lead_modal_${leadId} .status-selector`);
        if (selector) toggleSubscriptionForm(leadId, selector.value);
    }
    document.querySelectorAll('.status-selector').forEach(sel => {
        sel.addEventListener('change', function () {
            toggleSubscriptionForm(this.dataset.leadId, this.value);
        });
    });

    // === Select / Bulk ===
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const leadCheckboxes = document.querySelectorAll('.lead-checkbox');
    const bulkTrigger = document.getElementById('bulk-whatsapp-trigger');
    const bulkCount = document.getElementById('bulk-selected-count');
    const bulkCountModal = document.getElementById('bulk-selected-count-modal');

    function updateBulkUI() {
        const selected = document.querySelectorAll('.lead-checkbox:checked');
        const count = selected.length;
        bulkCount.textContent = count;
        bulkCountModal.textContent = count;
        bulkTrigger.classList.toggle('hidden', count === 0);
    }
    selectAllCheckbox.addEventListener('change', function () {
        leadCheckboxes.forEach(cb => cb.checked = this.checked);
        updateBulkUI();
    });
    leadCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkUI));

    // === Single WhatsApp ===
    let currentLeadId = null;
    const waTemplateSelector = document.getElementById('wa-template-selector');
    const waMessagePreview = document.getElementById('wa-message-preview');
    const waSendButton = document.getElementById('wa-send-button');

    window.openWhatsAppModal = function(leadId) {
        currentLeadId = leadId;
        const row = document.getElementById(`lead-row-${leadId}`);
        document.getElementById('wa-lead-name').textContent = row.dataset.name;
        waTemplateSelector.selectedIndex = 0;
        waMessagePreview.value = '';
        waSendButton.classList.add('btn-disabled');
    }

    waTemplateSelector.addEventListener('change', function() {
        if (!currentLeadId || !this.value) return;
        const row = document.getElementById(`lead-row-${currentLeadId}`);
        const name = row.dataset.name;
        const phone = row.dataset.phone;
        const finalMsg = this.value.replace(/\{\{name\}\}/g, name).replace(/\{\{nama_pelanggan\}\}/g, name);
        waMessagePreview.value = finalMsg;
        waSendButton.href = `https://wa.me/${phone}?text=${encodeURIComponent(finalMsg)}`;
        waSendButton.classList.remove('btn-disabled');
    });

    // === Bulk WhatsApp ===
    const bulkWaTemplateSelector = document.getElementById('bulk-wa-template-selector');
    const bulkWaMessagePreview = document.getElementById('bulk-wa-message-preview');
    const bulkWaSendButton = document.getElementById('bulk-wa-send-button');

    bulkWaTemplateSelector.addEventListener('change', function() {
        bulkWaMessagePreview.value = this.value || '';
        const selected = document.querySelectorAll('.lead-checkbox:checked');
        bulkWaSendButton.classList.toggle('btn-disabled', !this.value || selected.length === 0);
    });

    bulkWaSendButton.addEventListener('click', function() {
        const selected = Array.from(document.querySelectorAll('.lead-checkbox:checked'));
        const tpl = bulkWaTemplateSelector.value;
        if (!tpl || selected.length === 0) return;

        selected.forEach((cb, i) => {
            const name = cb.dataset.name;
            const phone = cb.dataset.phone;
            if (!phone) return;
            const msg = tpl.replace(/\{\{name\}\}/g, name).replace(/\{\{nama_pelanggan\}\}/g, name);
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            setTimeout(() => window.open(url, '_blank'), i * 250);
        });
    });
});
</script>
@endpush
