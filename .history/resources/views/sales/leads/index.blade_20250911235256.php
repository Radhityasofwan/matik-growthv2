@extends('layouts.app')

@section('title', 'Leads - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">

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
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between gap-3">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Leads</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lacak dan kelola calon pelanggan Anda.</p>
        </div>

        <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2 sm:items-center">
            {{-- Import form --}}
            <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv,.txt" class="file-input file-input-bordered file-input-sm max-w-xs" required>
                <button type="submit" class="btn btn-secondary btn-sm">Import</button>
            </form>

            <a href="#create_lead_modal" class="btn btn-primary">Tambah Lead</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mt-6">
        <form id="filter-form" action="{{ route('leads.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" placeholder="Cari nama, email, atau nama toko..." value="{{ request('search') }}" class="input input-bordered w-full">
            <select name="status" class="select select-bordered w-full">
                <option value="">Semua Status</option>
                <option value="trial" @selected(request('status') == 'trial')>Trial</option>
                <option value="active" @selected(request('status') == 'active')>Active</option>
                <option value="converted" @selected(request('status') == 'converted')>Converted</option>
                <option value="churn" @selected(request('status') == 'churn')>Churn</option>
            </select>

            {{-- Per Page --}}
            <select name="per_page" class="select select-bordered w-full">
                @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" @selected(request('per_page', 10) == $n)>{{ $n }} per halaman</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-secondary w-full md:w-auto">Terapkan</button>
        </form>
    </div>

    @if($leads->isEmpty() && !request()->query())
        {{-- Empty State --}}
        <div class="text-center py-20">
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Belum ada lead</h3>
            <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan lead pertama Anda atau impor dari file.</p>
            <div class="mt-6 flex gap-2 justify-center">
                <a href="#create_lead_modal" class="btn btn-primary">Lead Baru</a>
                <label for="import-file-trigger" class="btn btn-secondary">Import</label>
            </div>
        </div>
    @else
        {{-- Leads Table --}}
        <div class="mt-4 overflow-x-auto">
            <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Nama Owner</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Nama Toko</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tanggal Daftar</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tanggal Habis</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">No. Whatsapp</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Email</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-gray-800">
                        @forelse ($leads as $lead)
                        <tr id="lead-row-{{ $lead->id }}" data-phone="{{ $lead->phone }}" data-name="{{ $lead->name }}">
                            {{-- Status --}}
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <span class="badge
                                    @switch($lead->status)
                                        @case('trial') badge-info @break
                                        @case('active') badge-success @break
                                        @case('converted') badge-accent @break
                                        @case('churn') badge-error @break
                                    @endswitch">
                                    {{ ucfirst($lead->status) }}
                                </span>
                            </td>

                            {{-- Nama Owner --}}
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->owner?->name ?? '-' }}
                            </td>

                            {{-- Nama Toko --}}
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->store_name ?? '-' }}
                            </td>

                            {{-- Tanggal Daftar --}}
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ optional($lead->created_at)->format('d M Y H:i:s') }}
                            </td>

                            {{-- Tanggal Habis --}}
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->trial_ends_at?->format('d M Y') ?? ($lead->subscription?->end_date?->format('d M Y') ?? '-') }}
                            </td>

                            {{-- No. Whatsapp --}}
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->phone ?? '-' }}
                            </td>

                            {{-- Email --}}
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                {{ $lead->email }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">Tidak ada lead ditemukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Footer: pagination --}}
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

{{-- Modals --}}

{{-- Create Lead Modal --}}
<div id="create_lead_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('leads.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Tambah Lead Baru</h3>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Nama</span></label>
                    <input type="text" name="name" placeholder="Masukkan nama lengkap" value="{{ old('name') }}" class="input input-bordered w-full" required />
                </div>

                <div>
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" placeholder="Masukkan email" value="{{ old('email') }}" class="input input-bordered w-full" required />
                </div>

                <div>
                    <label class="label"><span class="label-text">No. Whatsapp</span></label>
                    <input type="text" name="phone" placeholder="cth: 6281234567890" value="{{ old('phone') }}" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label"><span class="label-text">Nama Toko</span></label>
                    <input type="text" name="store_name" placeholder="cth: MATIK LAUNDRY" value="{{ old('store_name') }}" class="input input-bordered w-full" />
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
                        <option value="converted" @selected(old('status')=='converted')>Converted</option>
                        <option value="churn" @selected(old('status')=='churn')>Churn</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Owner</span></label>
                    <select name="owner_id" class="select select-bordered w-full" required>
                        @forelse($users as $user)
                            <option value="{{ $user->id }}" @selected(old('owner_id')==$user->id)>{{ $user->name }}</option>
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

                {{-- Subscription Form (muncul saat status converted) --}}
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

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tampilkan form subscription hanya saat status = converted
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
});
</script>
@endpush
