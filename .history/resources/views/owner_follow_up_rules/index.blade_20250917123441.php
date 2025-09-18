@php
    // Helper untuk label trigger, logika ini dipertahankan
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

@extends('layouts.app')

@section('title', 'Follow Up Rules (Owner)')

@section('content')
<div
    x-data="{
        create: {
            trigger: '{{ old('trigger', $triggers[0] ?? 'on_send') }}',
            daysRequired() { return ['on_trial_ends_at','on_due_at'].includes(this.trigger); }
        },
        editRow: {{ $errors->any() && old('_form') === 'edit' ? old('rule_id', 'null') : 'null' }},
        setEdit(id) { this.editRow = (this.editRow === id ? null : id); }
    }"
    class="container mx-auto py-6 space-y-6"
>
    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg" data-aos="fade-down"><span>{{ session('success') }}</span></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg" data-aos="fade-down">
            <div>
                <div class="font-semibold">Terjadi kesalahan:</div>
                <ul class="list-disc list-inside">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">Owner Follow-Up Rules</h1>
            <p class="mt-1 text-base-content/70">Aturan notifikasi otomatis ke <strong>Owner</strong> berdasarkan aktivitas Lead.</p>
        </div>
        <a href="{{ route('lead-follow-up-rules.index') }}" class="btn btn-ghost">
            Lihat Rules Lead &rarr;
        </a>
    </div>

    {{-- SINKRONISASI: Menggunakan komponen card dan form DaisyUI --}}
    {{-- Form Buat Aturan Baru --}}
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">Buat Aturan Baru</h2>
            <form method="POST" action="{{ route('owner-follow-up-rules.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                @csrf
                <input type="hidden" name="_form" value="create">

                <div class="form-control">
                    <label class="label"><span class="label-text">Scope Aturan</span></label>
                    <select name="lead_id" class="select select-bordered w-full">
                        <option value="">Global (untuk semua lead)</option>
                        @foreach ($leads as $lead)
                            <option value="{{ $lead->id }}" @selected(old('lead_id') == $lead->id)>{{ $lead->name ?? 'Lead #'.$lead->id }}</option>
                        @endforeach
                    </select>
                    <label class="label"><span class="label-text-alt">Pilih "Global" untuk aturan umum.</span></label>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Trigger</span></label>
                    <select name="trigger" x-model="create.trigger" class="select select-bordered w-full">
                        @foreach ($triggers as $t)
                            <option value="{{ $t }}">{{ $triggerLabel($t) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control" x-show="create.daysRequired()" x-cloak>
                    <label class="label"><span class="label-text">Kirim Notifikasi (Hari H-N)</span></label>
                    <input type="number" name="days_before" min="0" max="365" value="{{ old('days_before', 1) }}" class="input input-bordered w-full" />
                    <label class="label"><span class="label-text-alt">Contoh: 1 = H-1, 3 = H-3.</span></label>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Template Pesan (Opsional)</span></label>
                    <select name="template_id" class="select select-bordered w-full">
                        <option value="">Gunakan teks default</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}" @selected(old('template_id') == $tpl->id)>{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text">Sender (Opsional)</span></label>
                    <select name="sender_id" class="select select-bordered w-full">
                        <option value="">Gunakan sender default</option>
                        @foreach ($senders as $s)
                            <option value="{{ $s->id }}" @selected(old('sender_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control md:col-span-2">
                    <label class="label cursor-pointer justify-start gap-4">
                        <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary" @checked(!old() || old('is_active'))>
                        <span class="label-text font-medium">Aktifkan Aturan Ini</span>
                    </label>
                </div>

                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="btn btn-primary">Simpan Aturan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Aturan yang Ada --}}
    <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <h2 class="card-title">Daftar Aturan</h2>
                <span class="text-sm text-base-content/60">Total: {{ $rules->total() }}</span>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Scope</th>
                            <th>Trigger</th>
                            <th>Template & Sender</th>
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
                            <tr class="hover">
                                <td><div class="font-medium">{{ $scope }}</div></td>
                                <td>
                                    <div>{{ $triggerLabel($rule->trigger) }}</div>
                                    @if($isDateBased)<div class="text-xs text-base-content/60">H-{{ (int) $rule->days_before }}</div>@endif
                                </td>
                                <td>
                                    <div>{{ $rule->template?->name ?? 'Teks Default' }}</div>
                                    <div class="text-xs text-base-content/60">{{ $rule->sender?->name ?? 'Sender Default' }}</div>
                                </td>
                                <td>
                                    @if($rule->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-ghost">Nonaktif</span>
                                    @endif
                                </td>
                                <td>{{ $rule->last_run_at ? $rule->last_run_at->timezone(config('app.timezone'))->format('d M Y, H:i') : '—' }}</td>
                                <td class="text-right">
                                    <button class="btn btn-ghost btn-sm" @click="setEdit({{ $rule->id }})">Edit</button>
                                </td>
                            </tr>
                            {{-- Baris Edit Inline --}}
                            <tr x-show="editRow === {{ $rule->id }}" x-cloak>
                                <td colspan="6" class="p-0">
                                    <div class="bg-base-200/50 p-4">
                                        <form method="POST" action="{{ route('owner-follow-up-rules.update', $rule) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4"
                                              x-data="{ trigger: '{{ $rule->trigger }}', needsDays(){ return ['on_trial_ends_at','on_due_at'].includes(this.trigger) } }">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="_form" value="edit"><input type="hidden" name="rule_id" value="{{ $rule->id }}">

                                            <div class="form-control"><label class="label"><span class="label-text">Scope</span></label><select name="lead_id" class="select select-bordered select-sm w-full"> <option value="">Global</option>@foreach ($leads as $lead)<option value="{{ $lead->id }}" @selected($rule->lead_id === $lead->id)>{{ $lead->name ?? 'Lead #'.$lead->id }}</option>@endforeach</select></div>
                                            <div class="form-control"><label class="label"><span class="label-text">Trigger</span></label><select name="trigger" x-model="trigger" class="select select-bordered select-sm w-full">@foreach ($triggers as $t)<option value="{{ $t }}" @selected($rule->trigger === $t)>{{ $triggerLabel($t) }}</option>@endforeach</select></div>
                                            <div class="form-control" x-show="needsDays()"><label class="label"><span class="label-text">Days Before</span></label><input type="number" name="days_before" min="0" max="365" value="{{ $rule->days_before ?? 1 }}" class="input input-bordered input-sm w-full" /></div>
                                            <div class="form-control"><label class="label"><span class="label-text">Template</span></label><select name="template_id" class="select select-bordered select-sm w-full"><option value="">Teks Default</option>@foreach ($templates as $tpl)<option value="{{ $tpl->id }}" @selected($rule->template_id === $tpl->id)>{{ $tpl->name }}</option>@endforeach</select></div>
                                            <div class="form-control"><label class="label"><span class="label-text">Sender</span></label><select name="sender_id" class="select select-bordered select-sm w-full"><option value="">Sender Default</option>@foreach ($senders as $s)<option value="{{ $s->id }}" @selected($rule->sender_id === $s->id)>{{ $s->name }}</option>@endforeach</select></div>
                                            <div class="form-control"><label class="label cursor-pointer justify-start gap-2"><span class="label-text">Aktif</span><input type="checkbox" name="is_active" value="1" class="toggle toggle-sm toggle-primary" @checked($rule->is_active)></label></div>

                                            <div class="md:col-span-3 flex justify-end gap-2 items-center">
                                                <form method="POST" action="{{ route('owner-follow-up-rules.destroy', $rule) }}" onsubmit="return confirm('Hapus rule ini?')">@csrf @method('DELETE')<button class="btn btn-error btn-sm btn-outline">Hapus</button></form>
                                                <button type="button" class="btn btn-ghost btn-sm" @click="setEdit(null)">Batal</button>
                                                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-base-content/60 py-8">Belum ada aturan. Buat aturan baru di atas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rules->hasPages())
            <div class="pt-4">
                {{ $rules->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
