@extends('layouts.app')

@section('title', 'Aturan Follow-up Leads')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @if (session('success'))
        <div class="alert alert-success shadow mb-4">
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow mb-4">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5 mt-2">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-semibold text-gray-800 dark:text-gray-100">Aturan Follow-up</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Atur pengingat follow-up otomatis berdasarkan kondisi chat.</p>
        </div>
        <a href="{{ route('leads.index') }}" class="btn btn-ghost">← Kembali ke Leads</a>
    </div>

    {{-- FORM CREATE --}}
    <div class="mt-6 rounded-2xl border bg-base-100 p-4">
        <h3 class="font-semibold mb-3">Buat Aturan Baru</h3>
        <form method="POST" action="{{ route('lead-follow-up-rules.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            @csrf

            <div class="md:col-span-2">
                <label class="label"><span class="label-text">Berlaku Untuk</span></label>
                <select name="lead_id" class="select select-bordered w-full">
                    <option value="">Global (semua lead)</option>
                    @foreach($leads as $l)
                        <option value="{{ $l->id }}">{{ $l->name ?? ($l->store_name ?? 'Lead #'.$l->id) }} — {{ $l->email }}</option>
                    @endforeach
                </select>
            </div>

            @php
                $labels = [
                    'no_chat'         => 'Belum di-chat',
                    'chat_1_no_reply' => 'Sudah chat 1x (belum balas)',
                    'chat_2_no_reply' => 'Sudah chat 2x (belum balas)',
                    'chat_3_no_reply' => 'Sudah chat 3x (belum balas)',
                ];
            @endphp

            <div>
                <label class="label"><span class="label-text">Kondisi</span></label>
                <select name="condition" class="select select-bordered w-full" required>
                    @foreach($conditions as $c)
                        <option value="{{ $c }}">{{ $labels[$c] ?? $c }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label"><span class="label-text">Kirim Setelah (hari)</span></label>
                <input type="number" name="days_after" min="0" value="3" class="input input-bordered w-full" required>
            </div>

            <div>
                <label class="label"><span class="label-text">Template WA (opsional)</span></label>
                <select name="wa_template_id" class="select select-bordered w-full">
                    <option value="">—</option>
                    @foreach($templates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label"><span class="label-text">Sender (opsional)</span></label>
                <select name="waha_sender_id" class="select select-bordered w-full">
                    <option value="">—</option>
                    @foreach($senders as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->number }})</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-6 flex items-center gap-3">
                <label class="label cursor-pointer">
                    <span class="label-text mr-3">Aktif</span>
                    <input type="checkbox" name="is_active" class="toggle toggle-success" checked>
                </label>
                <button type="submit" class="btn btn-primary">Simpan Rule</button>
            </div>
        </form>
    </div>

    {{-- LIST RULES --}}
    <div class="mt-6 rounded-2xl border bg-base-100">
        <div class="p-4 flex items-center justify-between">
            <h3 class="font-semibold">Daftar Aturan</h3>
            <span class="badge">{{ $rules->count() }} aturan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
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
                        <td>
                            @if($rule->lead)
                                <div class="font-medium">{{ $rule->lead->name ?? $rule->lead->store_name ?? ('Lead #'.$rule->lead_id) }}</div>
                                <div class="text-xs text-gray-500">{{ $rule->lead->email }}</div>
                            @else
                                <span class="badge badge-outline">Global</span>
                            @endif
                        </td>
                        <td><span class="badge">{{ $labels[$rule->condition] ?? $rule->condition }}</span></td>
                        <td>{{ $rule->days_after }} hr</td>
                        <td>{{ $rule->template?->name ?? '—' }}</td>
                        <td>{{ $rule->sender ? ($rule->sender->name.' ('.$rule->sender->number.')') : '—' }}</td>
                        <td>{!! $rule->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-ghost">Nonaktif</span>' !!}</td>
                        <td>{{ $rule->last_run_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-center">
                            <div class="flex items-center gap-2 justify-center">
                                <details class="dropdown">
                                    <summary class="btn btn-xs">Edit</summary>
                                    <div class="dropdown-content z-[1] p-3 shadow bg-base-100 rounded-box w-80">
                                        <form method="POST" action="{{ route('lead-follow-up-rules.update', $rule) }}" class="space-y-2">
                                            @csrf @method('PATCH')

                                            <div>
                                                <label class="label"><span class="label-text">Kondisi</span></label>
                                                <select name="condition" class="select select-bordered w-full" required>
                                                    @foreach($conditions as $c)
                                                        <option value="{{ $c }}" @selected($rule->condition === $c)>{{ $labels[$c] ?? $c }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="label"><span class="label-text">Kirim Setelah (hari)</span></label>
                                                <input type="number" name="days_after" min="0" value="{{ $rule->days_after }}" class="input input-bordered w-full" required>
                                            </div>

                                            <div>
                                                <label class="label"><span class="label-text">Template</span></label>
                                                <select name="wa_template_id" class="select select-bordered w-full">
                                                    <option value="">—</option>
                                                    @foreach($templates as $t)
                                                        <option value="{{ $t->id }}" @selected($rule->wa_template_id == $t->id)>{{ $t->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="label"><span class="label-text">Sender</span></label>
                                                <select name="waha_sender_id" class="select select-bordered w-full">
                                                    <option value="">—</option>
                                                    @foreach($senders as $s)
                                                        <option value="{{ $s->id }}" @selected($rule->waha_sender_id == $s->id)>{{ $s->name }} ({{ $s->number }})</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="label cursor-pointer">
                                                    <span class="label-text mr-3">Aktif</span>
                                                    <input type="checkbox" name="is_active" class="toggle toggle-success" {{ $rule->is_active ? 'checked' : '' }}>
                                                </label>
                                            </div>

                                            <div class="pt-1 text-right">
                                                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </details>

                                <form method="POST" action="{{ route('lead-follow-up-rules.destroy', $rule) }}" onsubmit="return confirm('Hapus aturan ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-error btn-outline">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-8">Belum ada aturan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
