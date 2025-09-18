@extends('layouts.app')
@section('title', 'Follow-up Rules — Leads')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow mb-4">
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow mb-4">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="text-2xl md:text-3xl font-semibold text-gray-800 dark:text-gray-100">Follow-up Rules</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Aturan dinamis untuk pengingat follow-up WhatsApp.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="#create_rule_modal" class="btn btn-primary">Tambah Rule</a>
        </div>
    </div>

    {{-- Tips --}}
    <div class="mt-4 rounded-xl border border-base-300 bg-base-100 p-4 text-sm">
        <div class="font-medium mb-1">Cara kerja singkat</div>
        <ul class="list-disc ml-5 space-y-1">
            <li><b>Scope</b>: <i>Global</i> (semua lead) atau khusus 1 lead.</li>
            <li><b>Condition</b>: <code>no_chat</code>, <code>chat_1_no_reply</code>, <code>chat_2_no_reply</code>, <code>chat_3_no_reply</code>.</li>
            <li><b>Days After</b>: jarak hari dari anchor waktu condition (mis. last chat).</li>
            <li><b>Template</b>: opsional. Jika kosong, dipakai pesan default.</li>
            <li><b>Sender</b>: jika kosong, dipakai WAHA sender default yang aktif.</li>
            <li>Scheduler berjalan per 15 menit: <code>send:lead-follow-ups</code>.</li>
        </ul>
    </div>

    {{-- Table --}}
    <div class="mt-6 overflow-x-auto">
        <div class="inline-block min-w-full shadow-md rounded-2xl overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase">Aktif</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase">Scope</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase">Condition</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase">Days After</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase">Template</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase">Sender</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase">Last Run</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase">Updated By</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @php
                        $condLabels = [
                            'no_chat' => 'Belum di-chat',
                            'chat_1_no_reply' => 'Chat 1× — belum balas',
                            'chat_2_no_reply' => 'Chat 2× — belum balas',
                            'chat_3_no_reply' => 'Chat 3× — belum balas',
                        ];
                    @endphp

                    @forelse ($rules as $rule)
                        <tr>
                            <td class="px-5 py-4 border-b text-sm">
                                <span class="badge {{ $rule->is_active ? 'badge-success' : 'badge-ghost' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 border-b text-sm">
                                @if($rule->lead)
                                    <div class="font-medium">{{ $rule->lead->name }}</div>
                                    <div class="text-xs text-gray-500">📧 {{ $rule->lead->email }} — 📱 {{ $rule->lead->phone }}</div>
                                @else
                                    <span class="badge badge-info">Global</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 border-b text-sm">
                                <span class="badge badge-outline">{{ $condLabels[$rule->condition] ?? $rule->condition }}</span>
                            </td>
                            <td class="px-5 py-4 border-b text-sm">
                                {{ $rule->days_after }} hari
                            </td>
                            <td class="px-5 py-4 border-b text-sm">
                                {{ $rule->template?->name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 border-b text-sm">
                                @if($rule->sender)
                                    <div class="font-medium">{{ $rule->sender->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $rule->sender->number }}</div>
                                @else
                                    <span class="text-gray-400">Default Active</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 border-b text-sm">
                                {{ $rule->last_run_at ? $rule->last_run_at->diffForHumans() : '—' }}
                            </td>
                            <td class="px-5 py-4 border-b text-sm">
                                {{ $rule->updater?->name ?? $rule->creator?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4 border-b">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="#edit_rule_modal_{{ $rule->id }}" class="btn btn-xs btn-ghost">Edit</a>
                                    <form action="{{ route('lead-follow-up-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Hapus rule ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-error text-white">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Edit Modal --}}
                        <div id="edit_rule_modal_{{ $rule->id }}" class="modal">
                            <div class="modal-box w-11/12 max-w-2xl">
                                <form action="{{ route('lead-follow-up-rules.update', $rule) }}" method="POST">
                                    @csrf @method('PUT')
                                    <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                                    <h3 class="font-bold text-lg mb-2">Edit Rule</h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="label"><span class="label-text">Scope (Opsional)</span></label>
                                            <select name="lead_id" class="select select-bordered w-full">
                                                <option value="">Global (semua lead)</option>
                                                @foreach($leads as $lead)
                                                    <option value="{{ $lead->id }}" @selected($rule->lead_id == $lead->id)>
                                                        {{ $lead->name }} — {{ $lead->email }} — {{ $lead->phone }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="label"><span class="label-text">Condition</span></label>
                                            <select name="condition" class="select select-bordered w-full" required>
                                                @foreach($conditions as $c)
                                                    <option value="{{ $c }}" @selected($rule->condition === $c)>{{ $condLabels[$c] ?? $c }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="label"><span class="label-text">Days After</span></label>
                                            <input type="number" name="days_after" min="1" max="365" value="{{ old('days_after', $rule->days_after) }}" class="input input-bordered w-full" required />
                                        </div>

                                        <div>
                                            <label class="label"><span class="label-text">Template (Opsional)</span></label>
                                            <select name="wa_template_id" class="select select-bordered w-full" {{ $templates->isEmpty() ? 'disabled' : '' }}>
                                                <option value="">— Tanpa Template —</option>
                                                @foreach($templates as $tpl)
                                                    <option value="{{ $tpl->id }}" @selected($rule->wa_template_id == $tpl->id)>{{ $tpl->name }}</option>
                                                @endforeach
                                            </select>
                                            @if($templates->isEmpty())
                                                <p class="text-xs text-gray-500 mt-1">Tabel template belum tersedia / kosong.</p>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="label"><span class="label-text">Sender (Opsional)</span></label>
                                            <select name="waha_sender_id" class="select select-bordered w-full">
                                                <option value="">— Default Active —</option>
                                                @foreach($senders as $s)
                                                    <option value="{{ $s->id }}" @selected($rule->waha_sender_id == $s->id)>{{ $s->name }} ({{ $s->number }}) {{ $s->is_default ? '• default' : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="cursor-pointer flex items-center gap-2">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" class="checkbox" @checked($rule->is_active)>
                                                <span class="label-text">Aktifkan Rule</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="modal-action mt-6">
                                        <a href="#" class="btn btn-ghost">Batal</a>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                            <a href="#" class="modal-backdrop">Close</a>
                        </div>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-gray-500">Belum ada rule. Klik <b>Tambah Rule</b> untuk membuat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col sm:flex-row items-center justify-between">
                <div class="text-sm text-gray-500">
                    Menampilkan {{ $rules->firstItem() ?? 0 }}–{{ $rules->lastItem() ?? 0 }} dari {{ $rules->total() }} entri
                </div>
                <div class="mt-4 sm:mt-0">
                    {{ $rules->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div id="create_rule_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('lead-follow-up-rules.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg mb-2">Tambah Rule</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Scope (Opsional)</span></label>
                    <select name="lead_id" class="select select-bordered w-full">
                        <option value="">Global (semua lead)</option>
                        @foreach($leads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->name }} — {{ $lead->email }} — {{ $lead->phone }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Condition</span></label>
                    <select name="condition" class="select select-bordered w-full" required>
                        @foreach($conditions as $c)
                            <option value="{{ $c }}">{{ $condLabels[$c] ?? $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Days After</span></label>
                    <input type="number" name="days_after" min="1" max="365" value="{{ old('days_after', 3) }}" class="input input-bordered w-full" required />
                </div>

                <div>
                    <label class="label"><span class="label-text">Template (Opsional)</span></label>
                    <select name="wa_template_id" class="select select-bordered w-full" {{ $templates->isEmpty() ? 'disabled' : '' }}>
                        <option value="">— Tanpa Template —</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                    @if($templates->isEmpty())
                        <p class="text-xs text-gray-500 mt-1">Tabel template belum tersedia / kosong.</p>
                    @endif
                </div>

                <div>
                    <label class="label"><span class="label-text">Sender (Opsional)</span></label>
                    <select name="waha_sender_id" class="select select-bordered w-full">
                        <option value="">— Default Active —</option>
                        @foreach($senders as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->number }}) {{ $s->is_default ? '• default' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="cursor-pointer flex items-center gap-2">
                        <input type="hidden" name="is_active" value="1">
                        <input type="checkbox" name="is_active" value="1" class="checkbox" checked>
                        <span class="label-text">Aktifkan Rule</span>
                    </label>
                </div>
            </div>

            <div class="modal-action mt-6">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Rule</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endsection
