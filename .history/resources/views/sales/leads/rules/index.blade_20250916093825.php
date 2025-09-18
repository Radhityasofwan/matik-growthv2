{{-- resources/views/sales/leads/rules/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Aturan Follow-up Leads')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow mb-6">
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow mb-6">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 dark:text-gray-100">Aturan Follow-up</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Atur pengingat follow-up otomatis berdasarkan kondisi chat.
            </p>
        </div>
        <a href="{{ route('leads.index') }}" class="btn btn-ghost">← Kembali ke Leads</a>
    </div>

    @php
        $conditionLabels = [
            'no_chat'        => 'Belum di-chat',
            'chat_1_no_reply'=> 'Sudah chat 1x (belum balas)',
            'chat_2_no_reply'=> 'Sudah chat 2x (belum balas)',
            'chat_3_no_reply'=> 'Sudah chat 3x (belum balas)',
        ];
    @endphp

    {{-- Buat Aturan Baru --}}
    <div class="mt-6 p-5 bg-base-100 rounded-2xl shadow border">
        <h3 class="font-semibold mb-4">Buat Aturan Baru</h3>

        <form action="{{ route('lead-follow-up-rules.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
            @csrf

            {{-- Scope --}}
            <div class="lg:col-span-3">
                <label class="label"><span class="label-text">Berlaku Untuk</span></label>
                <select name="lead_id" class="select select-bordered w-full">
                    <option value="">Global (semua lead)</option>
                    @foreach($leads as $ld)
                        <option value="{{ $ld->id }}">
                            {{ $ld->name ?: ($ld->store_name ?: 'Lead #'.$ld->id) }} — {{ $ld->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Condition --}}
            <div class="lg:col-span-3">
                <label class="label"><span class="label-text">Kondisi</span></label>
                <select name="condition" class="select select-bordered w-full" required>
                    @foreach($conditions as $c)
                        <option value="{{ $c }}">{{ $conditionLabels[$c] ?? $c }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Days --}}
            <div class="lg:col-span-2">
                <label class="label"><span class="label-text">Kirim Setelah (hari)</span></label>
                <input type="number" name="days_after" min="0" max="365" value="3" class="input input-bordered w-full" required>
            </div>

            {{-- Template --}}
            <div class="lg:col-span-2">
                <label class="label"><span class="label-text">Template WA (opsional)</span></label>
                <select name="wa_template_id" class="select select-bordered w-full">
                    <option value="">—</option>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sender --}}
            <div class="lg:col-span-2">
                <label class="label"><span class="label-text">Sender (opsional)</span></label>
                <select name="waha_sender_id" class="select select-bordered w-full">
                    <option value="">—</option>
                    @foreach($senders as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->number }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Active + Submit --}}
            <div class="lg:col-span-12 flex items-center gap-4 mt-2">
                <label class="label cursor-pointer">
                    <span class="label-text mr-3">Aktif</span>
                    <input type="checkbox" name="is_active" class="toggle toggle-success" checked>
                </label>
                <button type="submit" class="btn btn-primary">Simpan Rule</button>
            </div>
        </form>
    </div>

    {{-- Daftar Aturan --}}
    <div class="mt-6 p-5 bg-base-100 rounded-2xl shadow border">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">Daftar Aturan</h3>
            <span class="text-sm text-gray-500">{{ $rules->count() }} aturan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Scope</th>
                        <th>Kondisi</th>
                        <th>Hari</th>
                        <th>Template</th>
                        <th>Sender</th>
                        <th>Aktif</th>
                        <th>Terakhir Jalan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td class="whitespace-nowrap">
                            @if($rule->lead)
                                <div class="font-medium">{{ $rule->lead->name ?: ($rule->lead->store_name ?: 'Lead #'.$rule->lead_id) }}</div>
                                <div class="text-xs text-gray-500">{{ $rule->lead->email }}</div>
                            @else
                                <span class="badge badge-ghost">Global</span>
                            @endif
                        </td>
                        <td>{{ $conditionLabels[$rule->condition] ?? $rule->condition }}</td>
                        <td>{{ $rule->days_after }} hr</td>
                        <td>{{ $rule->template?->name ?: '—' }}</td>
                        <td>
                            @if($rule->sender)
                                {{ $rule->sender->name }} <span class="text-xs text-gray-500">({{ $rule->sender->number }})</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $rule->is_active ? 'badge-success' : 'badge-ghost' }}">
                                {{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>{{ $rule->last_run_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="#rule-edit-{{ $rule->id }}" class="btn btn-xs">Edit</a>
                                <form action="{{ route('lead-follow-up-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Hapus aturan ini?');" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-error btn-outline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Modal Edit --}}
                    <div id="rule-edit-{{ $rule->id }}" class="modal">
                        <div class="modal-box w-11/12 max-w-3xl">
                            <form action="{{ route('lead-follow-up-rules.update', $rule) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                                <h3 class="font-semibold text-lg mb-4">Edit Aturan</h3>

                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                                    {{-- Scope --}}
                                    <div class="lg:col-span-3">
                                        <label class="label"><span class="label-text">Berlaku Untuk</span></label>
                                        <select name="lead_id" class="select select-bordered w-full">
                                            <option value="" @selected(!$rule->lead_id)>Global (semua lead)</option>
                                            @foreach($leads as $ld)
                                                <option value="{{ $ld->id }}" @selected($rule->lead_id === $ld->id)>
                                                    {{ $ld->name ?: ($ld->store_name ?: 'Lead #'.$ld->id) }} — {{ $ld->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Condition --}}
                                    <div class="lg:col-span-3">
                                        <label class="label"><span class="label-text">Kondisi</span></label>
                                        <select name="condition" class="select select-bordered w-full" required>
                                            @foreach($conditions as $c)
                                                <option value="{{ $c }}" @selected($rule->condition === $c)>{{ $conditionLabels[$c] ?? $c }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Days --}}
                                    <div class="lg:col-span-2">
                                        <label class="label"><span class="label-text">Kirim Setelah (hari)</span></label>
                                        <input type="number" name="days_after" min="0" max="365" value="{{ $rule->days_after }}" class="input input-bordered w-full" required>
                                    </div>

                                    {{-- Template --}}
                                    <div class="lg:col-span-2">
                                        <label class="label"><span class="label-text">Template WA (opsional)</span></label>
                                        <select name="wa_template_id" class="select select-bordered w-full">
                                            <option value="">—</option>
                                            @foreach($templates as $tpl)
                                                <option value="{{ $tpl->id }}" @selected($rule->wa_template_id === $tpl->id)>{{ $tpl->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Sender --}}
                                    <div class="lg:col-span-2">
                                        <label class="label"><span class="label-text">Sender (opsional)</span></label>
                                        <select name="waha_sender_id" class="select select-bordered w-full">
                                            <option value="">—</option>
                                            @foreach($senders as $s)
                                                <option value="{{ $s->id }}" @selected($rule->waha_sender_id === $s->id)>{{ $s->name }} ({{ $s->number }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="lg:col-span-12 flex items-center gap-4 mt-2">
                                        <label class="label cursor-pointer">
                                            <span class="label-text mr-3">Aktif</span>
                                            <input type="checkbox" name="is_active" class="toggle toggle-success" @checked($rule->is_active)>
                                        </label>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </div>

                            </form>
                        </div>
                        <a href="#" class="modal-backdrop">Close</a>
                    </div>
                    {{-- /Modal Edit --}}

                @empty
                    <tr><td colspan="8" class="text-center text-gray-500">Belum ada aturan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
