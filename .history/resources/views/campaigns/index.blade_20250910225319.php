@extends('layouts.app')

@section('title', 'Campaigns - Matik Growth Hub')

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
            <span><strong>Terdapat kesalahan!</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></span>
        </div>
    </div>
    @endif

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Campaigns</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola semua kampanye marketing Anda.</p>
        </div>
        <a href="#create_campaign_modal" class="btn btn-primary mt-4 sm:mt-0">Buat Kampanye</a>
    </div>

    <!-- Filters -->
    <div class="mt-6">
        <form action="{{ route('campaigns.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" placeholder="Cari nama kampanye..." value="{{ request('search') }}" class="input input-bordered w-full">
            <select name="status" class="select select-bordered w-full">
                <option value="">Semua Status</option>
                <option value="planned" @selected(request('status') == 'planned')>Planned</option>
                <option value="active" @selected(request('status') == 'active')>Active</option>
                <option value="completed" @selected(request('status') == 'completed')>Completed</option>
                <option value="paused" @selected(request('status') == 'paused')>Paused</option>
            </select>
            <select name="owner_id" class="select select-bordered w-full">
                <option value="">Semua Owner</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(request('owner_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary w-full">Filter</button>
        </form>
    </div>

    <!-- Campaigns Table -->
    <div class="mt-8 overflow-x-auto">
        <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Nama Kampanye</th>
                        <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Owner</th>
                        <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Budget</th>
                        <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Tanggal Berakhir</th>
                        <th class="px-5 py-3 border-b-2 text-center text-xs font-semibold uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @forelse ($campaigns as $campaign)
                    <tr>
                        <td class="px-5 py-5 border-b text-sm"><p class="font-semibold">{{ $campaign->name }}</p><p class="text-gray-500 text-xs">{{ $campaign->channel }}</p></td>
                        <td class="px-5 py-5 border-b text-sm"><span class="badge @switch($campaign->status) @case('active') badge-success @break @case('planned') badge-info @break @case('completed') badge-ghost @break @case('paused') badge-warning @break @endswitch">{{ ucfirst($campaign->status) }}</span></td>
                        <td class="px-5 py-5 border-b text-sm">{{ $campaign->owner->name }}</td>
                        <td class="px-5 py-5 border-b text-sm">Rp {{ number_format($campaign->budget, 0, ',', '.') }}</td>
                        <td class="px-5 py-5 border-b text-sm">{{ $campaign->end_date->format('d M Y') }}</td>
                        <td class="px-5 py-5 border-b text-sm text-center">
                             <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn btn-ghost btn-xs">...</label>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40 z-10">
                                    <li><a href="{{ route('campaigns.show', $campaign) }}">Lihat Laporan</a></li>
                                    <li><a href="#edit_campaign_modal_{{ $campaign->id }}">Edit</a></li>
                                    <li>
                                        <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus kampanye ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="w-full text-left text-error p-2 hover:bg-gray-100 rounded-lg">Hapus</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-10">Tidak ada kampanye ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col sm:flex-row items-center justify-between">
                <div class="flex items-center space-x-2 text-sm">
                    <span class="text-gray-700">Tampilkan</span>
                    <form action="{{ url()->current() }}" method="GET">
                         @foreach(request()->except('per_page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <select name="per_page" onchange="this.form.submit()" class="select select-bordered select-sm">
                            <option value="10" @selected(request('per_page', 10) == 10)>10</option>
                            <option value="25" @selected(request('per_page') == 25)>25</option>
                            <option value="50" @selected(request('per_page') == 50)>50</option>
                        </select>
                    </form>
                    <span class="text-gray-700">entri</span>
                </div>
                <div class="mt-4 sm:mt-0">{{ $campaigns->links() }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('campaigns.partials.modals')

@endsection
