@extends('layouts.app')
@section('title', 'WhatsApp – Broadcast')

@section('content')
<div class="container mx-auto px-6 py-8" x-data="broadcastPage()">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow mb-6"><div>{{ session('success') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow mb-6">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <div class="sm:flex sm:items-center sm:justify-between mb-4">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">WhatsApp Broadcast</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kirim pesan massal menggunakan WAHA.</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('waha-senders.index') }}" class="btn btn-secondary btn-sm" target="_blank">Kelola Sender</a>
            <button type="button" class="btn btn-outline btn-sm inline-flex items-center gap-2" :class="{ 'btn-disabled': refreshing }" @click="refreshSenders()">
                <span class="loading loading-spinner loading-xs" x-show="refreshing"></span>
                <span>Refresh Sender</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form kiri --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('whatsapp.broadcast.store') }}" id="broadcastForm">
                        @csrf

                        {{-- Kirim Dari + Mode --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label"><span class="label-text">Kirim Dari</span></label>
                                <select name="sender_id" id="senderSelect" class="select select-bordered w-full" required>
                                    @php
                                        // Controller create() sebaiknya mengirim $senders (aktif, urut default desc lalu name/id)
                                        $opts = $senders ?? collect();
                                    @endphp
                                    @if($opts->isEmpty())
                                        <option value="" selected>Belum ada sender</option>
                                    @else
                                        @foreach ($opts as $s)
                                            @php
                                                $label = $s->name ?? $s->number ?? $s->session ?? ('Sender #'.$s->id);
                                                if (!empty($s->is_default)) $label .= ' — Default';
                                            @endphp
                                            <option value="{{ $s->id }}" @selected($s->is_default)>{{ $label }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <p class="text-xs mt-1 text-gray-500">Default sender akan ditandai.</p>
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Mode Pesan</span></label>
                                <div class="flex items-center gap-6">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="mode" value="custom" x-model="form.mode" checked>
                                        <span>Custom Message</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="mode" value="template" x-model="form.mode">
                                        <span>Template</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Daftar Penerima --}}
                        <div class="mt-4">
                            <label class="label"><span class="label-text">Daftar Penerima</span></label>
                            <textarea name="recipients" class="textarea textarea-bordered w-full h-36" placeholder="Satu baris satu penerima. Contoh:
628123456789
Budi, 628123456789
628123456789 | Budi" required>{{ old('recipients') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Format: <code>628xxxx</code>, <code>Nama, 628xxxx</code>, atau <code>628xxxx | Nama</code></p>
                        </div>

                        {{-- Custom Message --}}
                        <div class="mt-5" x-show="form.mode === 'custom'">
                            <label class="label"><span class="label-text">Pesan</span></label>
                            <textarea
                                name="message"
                                class="textarea textarea-bordered w-full h-36"
                                placeholder="Tulis pesan. Gunakan @{{name}} untuk menyapa penerima."
                                x-model="form.message"
                                :required="form.mode==='custom'"></textarea>
                        </div>

                        {{-- Template --}}
                        <div class="mt-5" x-show="form.mode === 'template'">
                            <label class="label"><span class="label-text">Template</span></label>
                            <select name="template_id" class="select select-bordered w-full" :required="form.mode==='template'">
                                <option value="" disabled selected>— Pilih Template —</option>
                                @foreach(($templates ?? []) as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name ?? ('Template #'.$tpl->id) }}</option>
                                @endforeach
                            </select>
                            <label class="label mt-3"><span class="label-text">Params (JSON)</span></label>
                            <textarea name="params_json" class="textarea textarea-bordered w-full" placeholder='Contoh: {"code":"AB-123"}'></textarea>
                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            <button type="reset" class="btn">Reset</button>
                            <button type="submit" class="btn btn-primary">Kirim Broadcast</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Pratinjau / Tips --}}
        <div>
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Pratinjau</h3>
                    <p class="text-xs text-gray-500 mb-4">Contoh untuk 3 penerima pertama.</p>
                    <div class="space-y-2">
                        <template x-for="(it, idx) in previewList" :key="idx">
                            <div class="p-2 rounded bg-gray-50 text-sm" x-text="it"></div>
                        </template>
                    </div>

                    <div class="divider my-6"></div>
                    <h4 class="font-semibold mb-2">Tips</h4>
                    <ul class="text-sm list-disc ml-5 space-y-1">
                        <li>Gunakan <code>@{{name}}</code> untuk menyapa penerima (mode Custom).</li>
                        <li>Nomor harus format internasional (cth: <code>628...</code>).</li>
                        <li>Kelola sender lewat tombol <strong>Kelola Sender</strong> (membuka tab indeks).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Inline script (tanpa @push) --}}
<script>
function broadcastPage() {
    return {
        form: { mode: 'custom', message: '' },
        previewList: [],
        refreshing: false,

        buildSenderLabel(s) {
            let label = s.name ?? s.number ?? s.session ?? ('Sender #' + s.id);
            if (s.is_default) label += ' — Default';
            return label;
        },

        refreshSenders() {
            if (this.refreshing) return;
            this.refreshing = true;

            const select = document.getElementById('senderSelect');
            const prev = select.value;
            select.innerHTML = '<option>Memuat...</option>';
            select.disabled = true;

            const url = '{{ route('waha-senders.index') }}' + '?json=1';

            fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(async (r) => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const body = await r.json();
                const list = Array.isArray(body.data) ? body.data : [];

                select.innerHTML = '';
                if (list.length === 0) {
                    select.innerHTML = '<option value="">Belum ada sender</option>';
                    return;
                }

                // pilih default kalau ada; jika tidak, pertahankan pilihan sebelumnya
                let defaultId = null;
                list.forEach(s => { if (s.is_default) defaultId = s.id; });

                list.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = this.buildSenderLabel(s);
                    if (defaultId ? s.id === defaultId : s.id == prev) opt.selected = true;
                    select.appendChild(opt);
                });
            })
            .catch(() => {
                select.innerHTML = '<option value="">Gagal memuat sender</option>';
            })
            .finally(() => {
                select.disabled = false;
                this.refreshing = false;
            });
        }
    }
}
</script>
@endsection
