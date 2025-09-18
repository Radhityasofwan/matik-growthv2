@extends('layouts.app')

@section('title', 'Campaigns - Matik Growth Hub')

@section('content')
<div class="container mx-auto py-6">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>
                    <strong>Terdapat kesalahan!</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">Campaigns</h1>
            <p class="mt-1 text-base-content/70">Kelola dan lacak semua kampanye marketing Anda.</p>
        </div>
        <a href="#create_campaign_modal" class="btn btn-primary mt-4 sm:mt-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Buat Kampanye
        </a>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-md border border-base-300/50 mt-6" data-aos="fade-up">
        <form action="{{ route('campaigns.index') }}" method="GET" class="card-body p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <input type="text" name="search" placeholder="Cari nama kampanye..." value="{{ request('search') }}" class="input input-bordered w-full">
                <select name="status" class="select select-bordered w-full">
                    <option value="">Semua Status</option>
                    @foreach (['planned'=>'Planned','active'=>'Active','completed'=>'Completed','paused'=>'Paused'] as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="owner_id" class="select select-bordered w-full">
                    <option value="">Semua Owner</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(request('owner_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary w-full">Filter</button>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="card bg-base-100 shadow-lg border border-base-300/50 mt-8" data-aos="fade-up">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Nama Kampanye</th>
                        <th>Status</th>
                        <th>Owner</th>
                        <th>Budget</th>
                        <th>Tanggal Berakhir</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        @php
                            $statusClass = match($campaign->status){
                                'active' => 'badge-success',
                                'planned' => 'badge-info',
                                'completed' => 'badge-ghost',
                                'paused' => 'badge-warning',
                                default => 'badge-outline'
                            };
                        @endphp
                        <tr class="hover">
                            <td>
                                <div class="font-semibold text-base-content">{{ $campaign->name }}</div>
                                <div class="text-xs text-base-content/60">{{ $campaign->channel }}</div>
                            </td>
                            <td><span class="badge {{ $statusClass }}">{{ ucfirst($campaign->status) }}</span></td>
                            <td>{{ $campaign->owner?->name ?? 'Unassigned' }}</td>
                            <td>Rp {{ number_format((float) $campaign->budget, 0, ',', '.') }}</td>
                            <td>{{ optional($campaign->end_date)->format('d M Y') ?? '—' }}</td>
                            <td class="text-right">
                                <div class="dropdown dropdown-end">
                                    <label tabindex="0" class="btn btn-ghost btn-xs">opsi</label>
                                    <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-48 z-10 border border-base-300/50">
                                        <li><a href="{{ route('campaigns.show', $campaign) }}">Lihat Laporan</a></li>
                                        <li><a href="#edit_campaign_modal_{{ $campaign->id }}">Edit</a></li>
                                        <div class="divider my-1"></div>
                                        <li>
                                            <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus kampanye ini?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full text-left text-error">Hapus</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-10 text-base-content/60">Tidak ada kampanye ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6" data-aos="fade-up">
        {{ $campaigns->links() }}
    </div>
</div>
@endsection

{{-- ===== Modal & Scripts dipush ke luar <main> agar tidak ter-clip oleh contain-paint ===== --}}

@push('modals')
    {{-- Create Campaign Modal --}}
    <div id="create_campaign_modal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box w-11/12 max-w-3xl max-h-[80vh] overflow-y-auto">
            <form action="{{ route('campaigns.store') }}" method="POST">
                @csrf
                <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                <h3 class="font-bold text-lg text-base-content">Buat Kampanye Baru</h3>

                <div class="tabs tabs-boxed mt-4">
                    <a class="tab tab-active" onclick="switchTab(this, 'tab-info-create')">Info Utama</a>
                    <a class="tab" onclick="switchTab(this, 'tab-metrics-create')">Data Metrik</a>
                </div>

                <div id="tab-info-create" class="py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control col-span-2"><label class="label"><span class="label-text">Nama Kampanye</span></label><input type="text" name="name" placeholder="Nama Kampanye" value="{{ old('name') }}" class="input input-bordered w-full" required /></div>
                        <div class="form-control"><label class="label"><span class="label-text">Channel</span></label><input type="text" name="channel" placeholder="e.g., Google Ads" value="{{ old('channel') }}" class="input input-bordered w-full" required /></div>
                        <div class="form-control"><label class="label"><span class="label-text">Status</span></label><select name="status" class="select select-bordered w-full" required>
                            @foreach (['planned'=>'Planned','active'=>'Active','completed'=>'Completed','paused'=>'Paused'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('status')===$val)>{{ $label }}</option>
                            @endforeach
                        </select></div>
                        <div class="form-control"><label class="label"><span class="label-text">Budget (Rp)</span></label><input type="number" name="budget" placeholder="5000000" value="{{ old('budget') }}" class="input input-bordered w-full" required /></div>
                        <div class="form-control"><label class="label"><span class="label-text">Owner</span></label><select name="owner_id" class="select select-bordered w-full" required>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('owner_id')==$user->id)>{{ $user->name }}</option>@endforeach</select></div>
                        <div class="form-control"><label class="label"><span class="label-text">Tanggal Mulai</span></label><input type="date" name="start_date" value="{{ old('start_date') }}" class="input input-bordered w-full" required /></div>
                        <div class="form-control"><label class="label"><span class="label-text">Tanggal Selesai</span></label><input type="date" name="end_date" value="{{ old('end_date') }}" class="input input-bordered w-full" required /></div>
                        <div class="form-control col-span-2"><label class="label"><span class="label-text">Deskripsi</span></label><textarea name="description" class="textarea textarea-bordered w-full" placeholder="Deskripsi singkat kampanye">{{ old('description') }}</textarea></div>
                    </div>
                </div>

                <div id="tab-metrics-create" class="py-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control"><label class="label"><span class="label-text">Total Spent (Rp)</span></label><input type="number" step="any" name="total_spent" value="{{ old('total_spent', 0) }}" class="input input-bordered w-full" /></div>
                        <div class="form-control"><label class="label"><span class="label-text">Revenue (Rp)</span></label><input type="number" step="any" name="revenue" value="{{ old('revenue', 0) }}" class="input input-bordered w-full" /></div>
                        <div class="form-control"><label class="label"><span class="label-text">Impressions</span></label><input type="number" name="impressions" value="{{ old('impressions', 0) }}" class="input input-bordered w-full" /></div>
                        <div class="form-control"><label class="label"><span class="label-text">Link Clicks</span></label><input type="number" name="link_clicks" value="{{ old('link_clicks', 0) }}" class="input input-bordered w-full" /></div>
                        <div class="form-control"><label class="label"><span class="label-text">Hasil (Konversi)</span></label><input type="number" name="results" value="{{ old('results', 0) }}" class="input input-bordered w-full" /></div>
                    </div>
                </div>

                <div class="modal-action mt-6">
                    <a href="#" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Kampanye</button>
                </div>
            </form>
        </div>
        <a href="#" class="modal-backdrop">Close</a>
    </div>

    {{-- Edit Campaign Modals --}}
    @foreach ($campaigns as $campaign)
        <div id="edit_campaign_modal_{{ $campaign->id }}" class="modal modal-bottom sm:modal-middle">
            <div class="modal-box w-11/12 max-w-3xl max-h-[80vh] overflow-y-auto">
                <form action="{{ route('campaigns.update', $campaign) }}" method="POST">
                    @csrf @method('PATCH')
                    <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                    <h3 class="font-bold text-lg text-base-content">Edit Kampanye: {{ $campaign->name }}</h3>

                    <div class="tabs tabs-boxed mt-4">
                        <a class="tab tab-active" onclick="switchTab(this, 'tab-info-edit-{{ $campaign->id }}')">Info Utama</a>
                        <a class="tab" onclick="switchTab(this, 'tab-metrics-edit-{{ $campaign->id }}')">Data Metrik</a>
                    </div>

                    <div id="tab-info-edit-{{ $campaign->id }}" class="py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control col-span-2"><label class="label"><span class="label-text">Nama Kampanye</span></label><input type="text" name="name" value="{{ old('name', $campaign->name) }}" class="input input-bordered w-full" required /></div>
                            <div class="form-control"><label class="label"><span class="label-text">Channel</span></label><input type="text" name="channel" value="{{ old('channel', $campaign->channel) }}" class="input input-bordered w-full" required /></div>
                            <div class="form-control"><label class="label"><span class="label-text">Status</span></label>
                                <select name="status" class="select select-bordered w-full" required>
                                    @foreach (['planned'=>'Planned','active'=>'Active','completed'=>'Completed','paused'=>'Paused'] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('status', $campaign->status) === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-control"><label class="label"><span class="label-text">Budget (Rp)</span></label><input type="number" name="budget" value="{{ old('budget', $campaign->budget) }}" class="input input-bordered w-full" required /></div>
                            <div class="form-control"><label class="label"><span class="label-text">Owner</span></label>
                                <select name="owner_id" class="select select-bordered w-full" required>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" @selected(old('owner_id', $campaign->owner_id) == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-control"><label class="label"><span class="label-text">Tanggal Mulai</span></label><input type="date" name="start_date" value="{{ old('start_date', optional($campaign->start_date)->format('Y-m-d')) }}" class="input input-bordered w-full" required /></div>
                            <div class="form-control"><label class="label"><span class="label-text">Tanggal Selesai</span></label><input type="date" name="end_date" value="{{ old('end_date', optional($campaign->end_date)->format('Y-m-d')) }}" class="input input-bordered w-full" required /></div>
                            <div class="form-control col-span-2"><label class="label"><span class="label-text">Deskripsi</span></label><textarea name="description" class="textarea textarea-bordered w-full">{{ old('description', $campaign->description) }}</textarea></div>
                        </div>
                    </div>

                    <div id="tab-metrics-edit-{{ $campaign->id }}" class="py-4 hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control"><label class="label"><span class="label-text">Total Spent (Rp)</span></label><input type="number" step="any" name="total_spent" value="{{ old('total_spent', $campaign->total_spent) }}" class="input input-bordered w-full" /></div>
                            <div class="form-control"><label class="label"><span class="label-text">Revenue (Rp)</span></label><input type="number" step="any" name="revenue" value="{{ old('revenue', $campaign->revenue) }}" class="input input-bordered w-full" /></div>
                            <div class="form-control"><label class="label"><span class="label-text">Impressions</span></label><input type="number" name="impressions" value="{{ old('impressions', $campaign->impressions) }}" class="input input-bordered w-full" /></div>
                            <div class="form-control"><label class="label"><span class="label-text">Link Clicks</span></label><input type="number" name="link_clicks" value="{{ old('link_clicks', $campaign->link_clicks) }}" class="input input-bordered w-full" /></div>
                            <div class="form-control"><label class="label"><span class="label-text">Hasil (Konversi)</span></label><input type="number" name="results" value="{{ old('results', $campaign->results) }}" class="input input-bordered w-full" /></div>
                        </div>
                    </div>

                    <div class="modal-action mt-6">
                        <a href="#" class="btn btn-ghost">Batal</a>
                        <button type="submit" class="btn btn-primary">Update Kampanye</button>
                    </div>
                </form>
            </div>
            <a href="#" class="modal-backdrop">Close</a>
        </div>
    @endforeach
@endpush

@push('scripts')
<script>
    // Tab switcher: scoped per modal
    function switchTab(clickedTab, targetTabId) {
        const modal = clickedTab.closest('.modal-box');
        if (!modal) return;

        modal.querySelectorAll('.tabs .tab').forEach(tab => tab.classList.remove('tab-active'));
        modal.querySelectorAll('[id^="tab-"]').forEach(panel => panel.classList.add('hidden'));

        clickedTab.classList.add('tab-active');
        const targetPanel = document.getElementById(targetTabId);
        if (targetPanel) targetPanel.classList.remove('hidden');
    }
</script>
@endpush
