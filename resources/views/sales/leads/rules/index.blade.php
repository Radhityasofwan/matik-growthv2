@php
    // Helper untuk label trigger, logika ini dipertahankan
    $triggerLabel = function (string $t): string {
        return match ($t) {
            'no_chat'         => 'Belum di-chat',
            'chat_1_no_reply' => 'Sudah chat 1x (belum balas)',
            'chat_2_no_reply' => 'Sudah chat 2x (belum balas)',
            'chat_3_no_reply' => 'Sudah chat 3x (belum balas)',
            default            => \Illuminate\Support\Str::headline($t),
        };
    };
@endphp

@extends('layouts.app')

@section('title', 'Aturan Follow-up Leads')

@section('content')
<div class="container mx-auto py-6 space-y-6">
    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg" data-aos="fade-down"><div>{{ session('success') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg" data-aos="fade-down">
            <div>
                <div class="font-semibold">Terjadi kesalahan:</div>
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">Aturan Follow-up Leads</h1>
            <p class="mt-1 text-base-content/70">Atur pengingat follow-up otomatis berdasarkan kondisi chat.</p>
        </div>
        <a href="{{ route('leads.index') }}" class="btn btn-ghost">← Kembali ke Leads</a>
    </div>

    {{-- SINKRONISASI: Menggunakan komponen card dan form DaisyUI --}}
    {{-- Form Buat Aturan Baru --}}
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">Buat Aturan Baru</h2>
            <form action="{{ route('lead-follow-up-rules.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4 items-end">
                @csrf
                <div class="form-control">
                    <label class="label"><span class="label-text">Berlaku Untuk</span></label>
                    <select name="lead_id" class="select select-bordered w-full">
                        <option value="">Global (semua lead)</option>
                        @foreach($leads as $ld)
                            <option value="{{ $ld->id }}">{{ $ld->name ?: ($ld->store_name ?: 'Lead #'.$ld->id) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Kondisi</span></label>
                    <select name="condition" class="select select-bordered w-full" required>
                        @foreach($conditions as $c)
                            <option value="{{ $c }}">{{ $conditionLabels[$c] ?? $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Kirim Setelah (hari)</span></label>
                    <input type="number" name="days_after" min="0" max="365" value="3" class="input input-bordered w-full" required>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Template WA (opsional)</span></label>
                    <select name="wa_template_id" class="select select-bordered w-full">
                        <option value="">—</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Sender (opsional)</span></label>
                    <select name="waha_sender_id" class="select select-bordered w-full">
                        <option value="">—</option>
                        @foreach($senders as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->number }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control col-span-full md:col-span-1 lg:col-span-3 flex md:flex-row md:items-end md:justify-end gap-4 pt-4">
                     <label class="label cursor-pointer gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary" checked>
                        <span class="label-text">Aktif</span>
                    </label>
                    <button type="submit" class="btn btn-primary">Simpan Rule</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Aturan --}}
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
         <div class="card-body">
            <div class="flex items-center justify-between">
                <h2 class="card-title">Daftar Aturan</h2>
                <span class="text-sm text-base-content/60">{{ $rules->count() }} aturan</span>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Scope</th>
                            <th>Kondisi</th>
                            <th>Hari</th>
                            <th>Template & Sender</th>
                            <th>Status</th>
                            <th>Terakhir Jalan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rules as $rule)
                        <tr class="hover">
                            <td class="whitespace-nowrap">
                                @if($rule->lead)
                                    <div class="font-medium">{{ $rule->lead->name ?: ($rule->lead->store_name ?: 'Lead #'.$rule->lead_id) }}</div>
                                    <div class="text-xs text-base-content/60">{{ $rule->lead->email }}</div>
                                @else
                                    <span class="badge badge-ghost">Global</span>
                                @endif
                            </td>
                            <td>{{ $conditionLabels[$rule->condition] ?? $rule->condition }}</td>
                            <td>{{ $rule->days_after }} hari</td>
                            <td>
                                <div>{{ $rule->template?->name ?? '—' }}</div>
                                <div class="text-xs text-base-content/60">{{ $rule->sender?->name ?? '—' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $rule->is_active ? 'badge-success' : 'badge-ghost' }}">
                                    {{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>{{ $rule->last_run_at?->format('d M Y, H:i') ?: '—' }}</td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="#rule-edit-{{ $rule->id }}" class="btn btn-ghost btn-sm">Edit</a>
                                    <form action="{{ route('lead-follow-up-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Hapus aturan ini?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-error btn-sm btn-outline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-base-content/60">Belum ada aturan. Silakan buat aturan baru di atas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modals Edit --}}
@foreach($rules as $rule)
<div id="rule-edit-{{ $rule->id }}" class="modal">
    <div class="modal-box w-11/12 max-w-4xl">
        <form action="{{ route('lead-follow-up-rules.update', $rule) }}" method="POST">
            @csrf
            @method('PUT')
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-semibold text-lg mb-4 text-base-content">Edit Aturan</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div class="form-control">
                    <label class="label"><span class="label-text">Berlaku Untuk</span></label>
                    <select name="lead_id" class="select select-bordered w-full">
                        <option value="" @selected(!$rule->lead_id)>Global (semua lead)</option>
                        @foreach($leads as $ld)
                            <option value="{{ $ld->id }}" @selected($rule->lead_id === $ld->id)>{{ $ld->name ?: ($ld->store_name ?: 'Lead #'.$ld->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Kondisi</span></label>
                    <select name="condition" class="select select-bordered w-full" required>
                        @foreach($conditions as $c)
                            <option value="{{ $c }}" @selected($rule->condition === $c)>{{ $conditionLabels[$c] ?? $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Kirim Setelah (hari)</span></label>
                    <input type="number" name="days_after" min="0" max="365" value="{{ $rule->days_after }}" class="input input-bordered w-full" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Template WA</span></label>
                    <select name="wa_template_id" class="select select-bordered w-full">
                        <option value="">—</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" @selected($rule->wa_template_id === $tpl->id)>{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Sender</span></label>
                    <select name="waha_sender_id" class="select select-bordered w-full">
                        <option value="">—</option>
                        @foreach($senders as $s)
                            <option value="{{ $s->id }}" @selected($rule->waha_sender_id === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control items-start pt-9">
                    <label class="label cursor-pointer gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary" @checked($rule->is_active)>
                        <span class="label-text">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection
