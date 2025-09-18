@extends('layouts.app')

@section('title', 'Subscriptions - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">

    <!-- Alerts -->
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
                <span><strong>Error!</strong> Mohon periksa kembali form Anda.</span>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Subscriptions</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola semua langganan pelanggan.</p>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="mt-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        <form action="{{ route('subscriptions.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" placeholder="Cari berdasarkan nama pelanggan..." value="{{ $request->search ?? '' }}" class="input input-bordered w-full">
            <select name="status" class="select select-bordered w-full">
                <option value="">Semua Status</option>
                <option value="active" @selected(($request->status ?? '') == 'active')>Active</option>
                <option value="paused" @selected(($request->status ?? '') == 'paused')>Paused</option>
                <option value="cancelled" @selected(($request->status ?? '') == 'cancelled')>Cancelled</option>
            </select>
            <select name="cycle" class="select select-bordered w-full">
                <option value="">Semua Siklus</option>
                <option value="monthly" @selected(($request->cycle ?? '') == 'monthly')>Bulanan</option>
                <option value="yearly" @selected(($request->cycle ?? '') == 'yearly')>Tahunan</option>
            </select>
            <button type="submit" class="btn btn-secondary w-full">Filter</button>
        </form>
    </div>

    @if($subscriptions->isEmpty())
        <div class="text-center py-20">
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Tidak ada langganan ditemukan</h3>
            <p class="mt-1 text-sm text-gray-500">Tidak ada data langganan yang sesuai dengan kriteria Anda.</p>
        </div>
    @else
        <!-- Subscriptions Table -->
        <div class="mt-8 overflow-x-auto">
            <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Pelanggan</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Paket</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Jumlah</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Siklus</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Status</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Tanggal Berakhir</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        @foreach ($subscriptions as $sub)
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $sub->lead->name }}</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">{{ $sub->lead->email }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">{{ $sub->plan }}</td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">Rp {{ number_format($sub->amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">{{ ucfirst($sub->cycle) }}</td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <span class="badge @switch($sub->status) @case('active') badge-success @break @case('paused') badge-warning @break @case('cancelled') badge-error @break @endswitch">
                                    {{ ucfirst($sub->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">{{ $sub->end_date?->format('d M Y') ?? 'N/A' }}</td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm text-right">
                                <div class="dropdown dropdown-end">
                                    <label tabindex="0" class="btn btn-ghost btn-xs">...</label>
                                    <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                                        <li><a href="#edit_sub_modal_{{ $sub->id }}">Edit</a></li>
                                        <li>
                                            <form action="{{ route('subscriptions.destroy', $sub) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus langganan ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full text-left text-error">Hapus</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col sm:flex-row items-center justify-between">
                    <!-- Per Page Dropdown -->
                    <div class="flex items-center space-x-2 text-sm">
                        <span class="text-gray-700 dark:text-gray-400">Show</span>
                        <form action="{{ route('subscriptions.index') }}" method="GET" class="inline-block">
                            <!-- Preserve existing filters -->
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="cycle" value="{{ request('cycle') }}">

                            <select name="per_page" onchange="this.form.submit()" class="select select-bordered select-sm w-full max-w-xs">
                                <option value="10" @selected(request('per_page', 10) == 10)>10</option>
                                <option value="25" @selected(request('per_page') == 25)>25</option>
                                <option value="50" @selected(request('per_page') == 50)>50</option>
                                <option value="100" @selected(request('per_page') == 100)>100</option>
                            </select>
                        </form>
                        <span class="text-gray-700 dark:text-gray-400">entries</span>
                    </div>

                    <!-- Pagination Links -->
                    <div class="mt-4 sm:mt-0">
                        {{ $subscriptions->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Edit Subscription Modals -->
@foreach ($subscriptions as $sub)
<div id="edit_sub_modal_{{ $sub->id }}" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('subscriptions.update', $sub) }}" method="POST">
            @csrf
            @method('PATCH')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Edit Langganan untuk {{ $sub->lead->name }}</h3>
            <div class="mt-4 space-y-4">
                 <div><label class="label"><span class="label-text">Nama Paket</span></label><input type="text" name="plan" value="{{ old('plan', $sub->plan) }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Jumlah (Rp)</span></label><input type="number" name="amount" value="{{ old('amount', $sub->amount) }}" class="input input-bordered w-full" required /></div>
                <div><label class="label"><span class="label-text">Siklus Tagihan</span></label><select name="cycle" class="select select-bordered w-full" required><option value="monthly" @selected(old('cycle', $sub->cycle) == 'monthly')>Bulanan</option><option value="yearly" @selected(old('cycle', $sub->cycle) == 'yearly')>Tahunan</option></select></div>
                <div><label class="label"><span class="label-text">Status</span></label><select name="status" class="select select-bordered w-full" required><option value="active" @selected(old('status', $sub->status) == 'active')>Aktif</option><option value="paused" @selected(old('status', $sub->status) == 'paused')>Dijeda</option><option value="cancelled" @selected(old('status', $sub->status) == 'cancelled')>Dibatalkan</option></select></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="label"><span class="label-text">Tanggal Mulai</span></label><input type="date" name="start_date" value="{{ old('start_date', $sub->start_date->format('Y-m-d')) }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Tanggal Berakhir (Opsional)</span></label><input type="date" name="end_date" value="{{ old('end_date', $sub->end_date?->format('Y-m-d')) }}" class="input input-bordered w-full" /></div>
                </div>
            </div>
            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Update Langganan</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endforeach

@endsection

