@extends('layouts.app')
@section('title', 'Cek Session WAHA')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Cek Session WAHA</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Base: <code>{{ $base }}</code> — Endpoint: <code>/api/sendText</code>
            </p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('waha-senders.index') }}" class="btn btn-secondary btn-sm" target="_blank">Kelola Sender</a>
            <a href="{{ route('waha.sessions.check') }}" class="btn btn-outline btn-sm">Jalankan Ulang</a>
        </div>
    </div>

    <div class="overflow-x-auto card">
        <div class="card-body">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Sender</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Hasil</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $r)
                        @php
                            $s = $r['sender'];
                            $badge = $r['exists'] === true ? 'badge-success' : ($r['exists'] === false ? 'badge-error' : 'badge-warning');
                            $label = $r['exists'] === true ? 'VALID' : ($r['exists'] === false ? 'TIDAK ADA' : 'TIDAK PASTI');
                        @endphp
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $s->name ?? ('Sender #'.$s->id) }}</div>
                                <div class="text-xs text-gray-500">{{ $s->number }}</div>
                                <div class="text-xs">{{ $s->is_default ? '★ Default' : '' }}</div>
                            </td>
                            <td class="font-mono text-sm">{{ $s->session }}</td>
                            <td><span class="badge">{{ $r['status'] }}</span></td>
                            <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                            <td class="text-sm">{{ $r['note'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-8">Tidak ada sender.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <details class="mt-6">
                <summary class="cursor-pointer">Lihat respons mentah</summary>
                <pre class="bg-base-200 p-3 rounded text-xs overflow-x-auto">{{ json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        </div>
    </div>

    <div class="mt-6 text-sm prose max-w-none">
        <h4>Cara memperbaiki bila TIDAK ADA</h4>
        <ol>
            <li>Buka dashboard WAHA dan lihat <em>nama session</em> yang aktif (CONNECTED).</li>
            <li>Ubah field <strong>session</strong> pada sender agar <em>persis sama</em> dengan nama di WAHA (case-sensitive).</li>
            <li>Kembali ke halaman ini dan klik <strong>Jalankan Ulang</strong>.</li>
        </ol>
    </div>
</div>
@endsection
