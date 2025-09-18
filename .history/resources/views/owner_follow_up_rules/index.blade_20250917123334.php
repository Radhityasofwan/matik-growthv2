@php
    // Helper label trigger & format tampil
    $triggerLabel = function (string $t): string {
        return match ($t) {
            'on_send'          => 'Saat Mengirim ke Lead',
            'on_success'       => 'Jika Sukses Kirim',
            'on_fail'          => 'Jika Gagal Kirim',
            'on_trial_ends_at' => 'Menjelang Trial Berakhir (H-N)',
            'on_due_at'        => 'Menjelang Jatuh Tempo (H-N)',
            default            => \Illuminate\Support\Str::headline($t),
        };
    };
@endphp

{{-- Jika proyekmu memakai layout standar, gunakan section berikut --}}
@extends('layouts.app')

@section('title', 'Follow Up Rules (Owner)')

@section('content')
<div
    x-data="{
        create: {
            trigger: '{{ $triggers[0] ?? 'on_send' }}',
            daysRequired() { return ['on_trial_ends_at','on_due_at'].includes(this.trigger); }
        },
        editRow: null,
        setEdit(id) { this.editRow = (this.editRow === id ? null : id); }
    }"
    class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
>
    {{-- Heading & Help --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Owner Follow-Up Rules</h1>
            <p class="text-sm text-neutral/70 mt-1">
                Aturan notifikasi otomatis ke <strong>Owner (wa_number)</strong> — terpisah dari rule Lead.
                Gunakan <em>Trigger</em> berdasarkan proses kirim ke lead atau jadwal H-N (trial/due).
            </p>
        </div>
        <a href="{{ route('lead-follow-up-rules.index') }}" class="btn btn-ghost">
            &larr; Rules Lead
        </a>
    </div>

    {{-- Flash & Validation --}}
    @if (session('success'))
        <div class="alert alert-success shadow-sm">
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error">
            <div class="font-semibold">Terjadi kesalahan:</div>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Create Rule --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h2 class="card-title">Buat Aturan Owner</h2>
            <form method="POST" action="{{ route('owner-follow-up-rules.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf

                {{-- Scope: Global / by Lead --}}
                <div>
                    <label class="label"><span class="label-text">Scope</span></label>
                    <select name="lead_id" class="select select-bordered w-full">
                        <option value="">Global (semua lead)</option>
                        @foreach ($leads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->name ?? 'Lead #'.$lead->id }} @if($lead->email) ({{ $lead->email }}) @endif</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-neutral/60 mt-1">Kosongkan untuk aturan global; pilih Lead untuk aturan spesifik.</p>
                </div>

                {{-- Trigger --}}
                <div>
                    <label class="label"><span class="label-text">Trigger</span></label>
                    <select name="trigger" x-model="create.trigger" class="select select-bordered w-full">
                        @foreach ($triggers as $t)
                            <option value="{{ $t }}">{{ $triggerLabel($t) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Days Before (only for date-based triggers) --}}
                <div x-show="create.daysRequired()">
                    <label class="label"><span class="label-text">Days Before (H-N)</span></label>
                    <input type="number" name="days_before" min="0" max="365" value="1" class="input input-bordered w-full" />
                    <p class="text-xs text-neutral/60 mt-1">Contoh: 1 = H-1, 3 = H-3. Untuk overdue pakai 0.</p>
                </div>

                {{-- Template --}}
                <div>
                    <label class="label"><span class="label-text">Template WA (Owner) (opsional)</span></label>
                    <select name="template_id" class="select select-bordered w-full">
                        <option value="">(Pakailah fallback teks default)</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sender --}}
                <div>
                    <label class="label"><span class="label-text">Sender (opsional)</span></label>
                    <select name="sender_id" class="select select-bordered w-full">
                        <option value="">(Pakai sender bawaan)</option>
                        @foreach ($senders as $s)
                            <option value="{{ $s->id }}">
                                {{ $s->name }} @if($s->number) ({{ $s->number }}) @endif @if($s->is_default) — default @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Active --}}
                <div class="flex items-center gap-3">
                    <label class="label"><span class="label-text">Aktif</span></label>
                    <input type="checkbox" name="is_active" class="toggle toggle-primary" checked>
                </div>

                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="btn btn-primary">Simpan Rule</button>
                </div>
            </form>
        </div>
    </div>

    {{-- List Rules --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <h2 class="card-title">Daftar Aturan</h2>
                <span class="text-sm text-neutral/60">Total: {{ $rules->total() }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Scope</th>
                            <th>Trigger</th>
                            <th>Template</th>
                            <th>Sender</th>
                            <th>Status</th>
                            <th>Terakhir Jalan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            @php
                                $isDateBased = in_array($rule->trigger, ['on_trial_ends_at','on_due_at'], true);
                                $scope = $rule->lead ? ($rule->lead->name ?? 'Lead #'.$rule->lead_id) : 'Global';
                            @endphp
                            <tr class="align-top">
                                <td class="whitespace-nowrap">
                                    <div class="font-medium">{{ $scope }}</div>
                                    @if($rule->lead && $rule->lead->email)
                                        <div class="text-xs text-neutral/60">{{ $rule->lead->email }}</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    <div>{{ $triggerLabel($rule->trigger) }}</div>
                                    @if($isDateBased)
                                        <div class="text-xs text-neutral/60">H-{{ (int) $rule->days_before }}</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    {{ $rule->template?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap">
                                    @if($rule->sender)
                                        <div class="font-medium">{{ $rule->sender->name }}</div>
                                        @if($rule->sender->number)
                                            <div class="text-xs text-neutral/60">{{ $rule->sender->number }}</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    @if($rule->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    {{ $rule->last_run_at ? $rule->last_run_at->timezone(config('app.timezone'))->format('d M Y H:i') : '—' }}
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button class="btn btn-ghost btn-sm" @click="setEdit({{ $rule->id }})">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('owner-follow-up-rules.destroy', $rule) }}" onsubmit="return confirm('Hapus rule ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-error">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            {{-- Inline Edit Row --}}
                            <tr x-show="editRow === {{ $rule->id }}" x-cloak>
                                <td colspan="7" class="bg-base-200/40">
                                    <form method="POST" action="{{ route('owner-follow-up-rules.update', $rule) }}" class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4"
                                          x-data="{ trigger: '{{ $rule->trigger }}', needsDays(){ return ['on_trial_ends_at','on_due_at'].includes(this.trigger) } }">
                                        @csrf @method('PUT')

                                        {{-- Scope --}}
                                        <div>
                                            <label class="label"><span class="label-text">Scope</span></label>
                                            <select name="lead_id" class="select select-bordered w-full">
                                                <option value="">Global (semua lead)</option>
                                                @foreach ($leads as $lead)
                                                    <option value="{{ $lead->id }}" @selected($rule->lead_id === $lead->id)>
                                                        {{ $lead->name ?? 'Lead #'.$lead->id }} @if($lead->email) ({{ $lead->email }}) @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Trigger --}}
                                        <div>
                                            <label class="label"><span class="label-text">Trigger</span></label>
                                            <select name="trigger" x-model="trigger" class="select select-bordered w-full">
                                                @foreach ($triggers as $t)
                                                    <option value="{{ $t }}" @selected($rule->trigger === $t)>{{ $triggerLabel($t) }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Days Before --}}
                                        <div x-show="needsDays()">
                                            <label class="label"><span class="label-text">Days Before (H-N)</span></label>
                                            <input type="number" name="days_before" min="0" max="365" value="{{ $rule->days_before ?? 1 }}" class="input input-bordered w-full" />
                                        </div>

                                        {{-- Template --}}
                                        <div>
                                            <label class="label"><span class="label-text">Template WA (Owner)</span></label>
                                            <select name="template_id" class="select select-bordered w-full">
                                                <option value="">(Fallback teks default)</option>
                                                @foreach ($templates as $tpl)
                                                    <option value="{{ $tpl->id }}" @selected($rule->template_id === $tpl->id)>{{ $tpl->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Sender --}}
                                        <div>
                                            <label class="label"><span class="label-text">Sender</span></label>
                                            <select name="sender_id" class="select select-bordered w-full">
                                                <option value="">(Bawaan)</option>
                                                @foreach ($senders as $s)
                                                    <option value="{{ $s->id }}" @selected($rule->sender_id === $s->id)>
                                                        {{ $s->name }} @if($s->number) ({{ $s->number }}) @endif @if($s->is_default) — default @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Active --}}
                                        <div class="flex items-center gap-3">
                                            <label class="label"><span class="label-text">Aktif</span></label>
                                            <input type="checkbox" name="is_active" class="toggle toggle-primary" @checked($rule->is_active)>
                                        </div>

                                        <div class="md:col-span-3 flex justify-end gap-2">
                                            <button type="button" class="btn btn-ghost" @click="setEdit(null)">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-neutral/60 py-8">
                                    Belum ada aturan. Buat aturan baru di atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $rules->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
