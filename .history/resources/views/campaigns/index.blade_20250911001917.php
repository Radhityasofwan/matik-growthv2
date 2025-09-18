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
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Nama Kampanye</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Owner</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Budget</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Tanggal Berakhir</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-center text-xs font-semibold uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @forelse ($campaigns as $campaign)
                    <tr>
                        <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm"><p class="font-semibold">{{ $campaign->name }}</p><p class="text-gray-500 text-xs">{{ $campaign->channel }}</p></td>
                        <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm"><span class="badge @switch($campaign->status) @case('active') badge-success @break @case('planned') badge-info @break @case('completed') badge-ghost @break @case('paused') badge-warning @break @endswitch">{{ ucfirst($campaign->status) }}</span></td>
                        <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">{{ $campaign->owner?->name ?? 'Unassigned' }}</td>
                        <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">Rp {{ number_format($campaign->budget, 0, ',', '.') }}</td>
                        <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">{{ $campaign->end_date->format('d M Y') }}</td>
                        <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm text-center">
                             <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn btn-ghost btn-xs">...</label>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40 z-10">
                                    <li><a href="{{ route('campaigns.show', $campaign) }}">Lihat Laporan</a></li>
                                    <li><a href="#edit_campaign_modal_{{ $campaign->id }}">Edit</a></li>
                                    <li>
                                        <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus kampanye ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="w-full text-left text-error p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg">Hapus</button>
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
                    <span class="text-gray-700 dark:text-gray-400">Tampilkan</span>
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
                    <span class="text-gray-700 dark:text-gray-400">entri</span>
                </div>
                <div class="mt-4 sm:mt-0">{{ $campaigns->links() }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Create Campaign Modal -->
<div id="create_campaign_modal" class="modal">
    <div class="modal-box w-11/12 max-w-3xl">
        <form action="{{ route('campaigns.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Buat Kampanye Baru</h3>

            <div class="tabs mt-4">
                <a class="tab tab-bordered tab-active" onclick="switchTab(this, 'tab-info-create')">Info Utama</a>
                <a class="tab tab-bordered" onclick="switchTab(this, 'tab-metrics-create')">Data Metrik</a>
            </div>

            <div id="tab-info-create" class="py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2"><label class="label"><span class="label-text">Nama Kampanye</span></label><input type="text" name="name" placeholder="Nama Kampanye" value="{{ old('name') }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Channel</span></label><input type="text" name="channel" placeholder="e.g., Google Ads" value="{{ old('channel') }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Status</span></label><select name="status" class="select select-bordered w-full" required><option value="planned">Planned</option><option value="active">Active</option><option value="completed">Completed</option><option value="paused">Paused</option></select></div>
                    <div><label class="label"><span class="label-text">Budget (Rp)</span></label><input type="number" name="budget" placeholder="5000000" value="{{ old('budget') }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Owner</span></label><select name="owner_id" class="select select-bordered w-full" required>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                    <div><label class="label"><span class="label-text">Tanggal Mulai</span></label><input type="date" name="start_date" value="{{ old('start_date') }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Tanggal Selesai</span></label><input type="date" name="end_date" value="{{ old('end_date') }}" class="input input-bordered w-full" required /></div>
                    <div class="col-span-2"><label class="label"><span class="label-text">Deskripsi</span></label><textarea name="description" class="textarea textarea-bordered w-full" placeholder="Deskripsi singkat kampanye">{{ old('description') }}</textarea></div>
                </div>
            </div>

            <div id="tab-metrics-create" class="py-4 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="label"><span class="label-text">Total Spent (Rp)</span></label><input type="number" step="any" name="total_spent" placeholder="0" value="{{ old('total_spent', 0) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Revenue (Rp)</span></label><input type="number" step="any" name="revenue" placeholder="0" value="{{ old('revenue', 0) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Impressions</span></label><input type="number" name="impressions" placeholder="0" value="{{ old('impressions', 0) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Link Clicks</span></label><input type="number" name="link_clicks" placeholder="0" value="{{ old('link_clicks', 0) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Hasil (Konversi)</span></label><input type="number" name="results" placeholder="0" value="{{ old('results', 0) }}" class="input input-bordered w-full" /></div>
                    <div></div>
                    <div><label class="label"><span class="label-text">LP Impressions</span></label><input type="number" name="lp_impressions" placeholder="0" value="{{ old('lp_impressions', 0) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">LP Link Clicks</span></label><input type="number" name="lp_link_clicks" placeholder="0" value="{{ old('lp_link_clicks', 0) }}" class="input input-bordered w-full" /></div>
                </div>
            </div>

            <div class="modal-action mt-6"><a href="#" class="btn btn-ghost">Batal</a><button type="submit" class="btn btn-primary">Simpan Kampanye</button></div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>

<!-- Edit Campaign Modals -->
@foreach ($campaigns as $campaign)
<div id="edit_campaign_modal_{{ $campaign->id }}" class="modal">
    <div class="modal-box w-11/12 max-w-3xl">
        <form action="{{ route('campaigns.update', $campaign) }}" method="POST">
            @csrf @method('PATCH')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Edit Kampanye: {{ $campaign->name }}</h3>

            <div class="tabs mt-4">
                <a class="tab tab-bordered tab-active" onclick="switchTab(this, 'tab-info-edit-{{ $campaign->id }}')">Info Utama</a>
                <a class="tab tab-bordered" onclick="switchTab(this, 'tab-metrics-edit-{{ $campaign->id }}')">Data Metrik</a>
            </div>

            <div id="tab-info-edit-{{ $campaign->id }}" class="py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2"><label class="label"><span class="label-text">Nama Kampanye</span></label><input type="text" name="name" value="{{ old('name', $campaign->name) }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Channel</span></label><input type="text" name="channel" value="{{ old('channel', $campaign->channel) }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Status</span></label><select name="status" class="select select-bordered w-full" required><option value="planned" @selected(old('status', $campaign->status) == 'planned')>Planned</option><option value="active" @selected(old('status', $campaign->status) == 'active')>Active</option><option value="completed" @selected(old('status', $campaign->status) == 'completed')>Completed</option><option value="paused" @selected(old('status', $campaign->status) == 'paused')>Paused</option></select></div>
                    <div><label class="label"><span class="label-text">Budget (Rp)</span></label><input type="number" name="budget" value="{{ old('budget', $campaign->budget) }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Owner</span></label><select name="owner_id" class="select select-bordered w-full" required>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('owner_id', $campaign->owner_id) == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                    <div><label class="label"><span class="label-text">Tanggal Mulai</span></label><input type="date" name="start_date" value="{{ old('start_date', $campaign->start_date->format('Y-m-d')) }}" class="input input-bordered w-full" required /></div>
                    <div><label class="label"><span class="label-text">Tanggal Selesai</span></label><input type="date" name="end_date" value="{{ old('end_date', $campaign->end_date->format('Y-m-d')) }}" class="input input-bordered w-full" required /></div>
                    <div class="col-span-2"><label class="label"><span class="label-text">Deskripsi</span></label><textarea name="description" class="textarea textarea-bordered w-full">{{ old('description', $campaign->description) }}</textarea></div>
                </div>
            </div>

            <div id="tab-metrics-edit-{{ $campaign->id }}" class="py-4 hidden">
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="label"><span class="label-text">Total Spent (Rp)</span></label><input type="number" step="any" name="total_spent" value="{{ old('total_spent', $campaign->total_spent) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Revenue (Rp)</span></label><input type="number" step="any" name="revenue" value="{{ old('revenue', $campaign->revenue) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Impressions</span></label><input type="number" name="impressions" value="{{ old('impressions', $campaign->impressions) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Link Clicks</span></label><input type="number" name="link_clicks" value="{{ old('link_clicks', $campaign->link_clicks) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">Hasil (Konversi)</span></label><input type="number" name="results" value="{{ old('results', $campaign->results) }}" class="input input-bordered w-full" /></div>
                    <div></div>
                    <div><label class="label"><span class="label-text">LP Impressions</span></label><input type="number" name="lp_impressions" value="{{ old('lp_impressions', $campaign->lp_impressions) }}" class="input input-bordered w-full" /></div>
                    <div><label class="label"><span class="label-text">LP Link Clicks</span></label><input type="number" name="lp_link_clicks" value="{{ old('lp_link_clicks', $campaign->lp_link_clicks) }}" class="input input-bordered w-full" /></div>
                </div>
            </div>

            <div class="modal-action mt-6"><a href="#" class="btn btn-ghost">Batal</a><button type="submit" class="btn btn-primary">Update Kampanye</button></div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endforeach

@endsection

@push('scripts')
<script>
    function switchTab(clickedTab, targetTabId) {
        const modal = clickedTab.closest('.modal-box');
        // Deactivate all tabs within this modal
        modal.querySelectorAll('.tabs .tab').forEach(tab => tab.classList.remove('tab-active'));
        // Hide all tab panels within this modal
        modal.querySelectorAll('[id^="tab-"]').forEach(panel => panel.classList.add('hidden'));

        // Activate the clicked tab and show the target panel
        clickedTab.classList.add('tab-active');
        document.getElementById(targetTabId).classList.remove('hidden');
    }
</script>
@endpush

