@extends('layouts.app')
@section('title', 'WhatsApp – Broadcast')

@section('content')
<div class="container mx-auto px-6 py-8">

    {{-- Alerts (merah bila gagal) --}}
    @if (session('error'))
        <div class="alert alert-error shadow mb-6"><div>{{ session('error') }}</div></div>
    @endif
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
            <button type="button" id="btnRefreshSenders" class="btn btn-outline btn-sm inline-flex items-center gap-2">
                <span id="spinnerRefresh" class="loading loading-spinner loading-xs hidden"></span>
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
                                    @php $opts = $senders ?? collect(); @endphp
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
                                        <input type="radio" name="mode" value="custom" id="mode_custom" checked>
                                        <span>Custom Message</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="mode" value="template" id="mode_template">
                                        <span>Template</span>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Pilih mode untuk menampilkan kolom yang sesuai di bawah.</p>
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

                        {{-- Custom Message (default tampil) --}}
                        <div id="boxCustom" class="mt-5">
                            <label class="label"><span class="label-text">Pesan</span></label>
                            <textarea
                                name="message"
                                class="textarea textarea-bordered w-full h-36"
                                placeholder="Tulis pesan. Gunakan @{{name}} untuk menyapa penerima.">{{ old('message') }}</textarea>
                        </div>

                        {{-- Template (hidden by default) --}}
                        <div id="boxTemplate" class="mt-5 hidden">
                            <label class="label"><span class="label-text">Template</span></label>
                            <select name="template_id" class="select select-bordered w-full">
                                <option value="" disabled selected>— Pilih Template —</option>
                                @foreach(($templates ?? []) as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name ?? ('Template #'.$tpl->id) }}</option>
                                @endforeach
                            </select>
                            <label class="label mt-3"><span class="label-text">Params (JSON)</span></label>
                            <textarea name="params_json" class="textarea textarea-bordered w-full" placeholder='Contoh: {"code":"AB-123"}'>{{ old('params_json') }}</textarea>
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
                    <div id="previewList" class="space-y-2"></div>

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

{{-- Toggle + Refresh Sender (vanilla JS) --}}
<script>
(function () {
    const boxCustom   = document.getElementById('boxCustom');
    const boxTemplate = document.getElementById('boxTemplate');
    const radios      = document.querySelectorAll('input[name="mode"]');

    function syncMode() {
        const v = (document.querySelector('input[name="mode"]:checked') || {}).value || 'custom';
        if (v === 'template') {
            boxCustom.classList.add('hidden');
            boxTemplate.classList.remove('hidden');
        } else {
            boxTemplate.classList.add('hidden');
            boxCustom.classList.remove('hidden');
        }
    }
    radios.forEach(r => r.addEventListener('change', syncMode));
    @if(old('mode') === 'template') document.getElementById('mode_template').checked = true; @endif
    syncMode();

    const btnRefresh = document.getElementById('btnRefreshSenders');
    const spinner    = document.getElementById('spinnerRefresh');
    const select     = document.getElementById('senderSelect');

    async function refreshSenders() {
        try {
            spinner.classList.remove('hidden');
            const prev = select.value;
            select.innerHTML = '<option>Memuat...</option>';
            select.disabled  = true;

            const r  = await fetch('{{ route('waha-senders.index') }}?json=1', { headers: { 'Accept':'application/json' } });
            if (!r.ok) throw new Error('fail');
            const body = await r.json();
            const list = Array.isArray(body.data) ? body.data : [];

            // Batch status
            const ids = list.map(x => x.id).join(',');
            if (ids.length) {
                try {
                    const rs = await fetch('{{ route('waha.sessions.statusBatch') }}?ids=' + encodeURIComponent(ids));
                    if (rs.ok) {
                        const js = await rs.json();
                        const map = {};
                        (js.data || []).forEach(it => map[it.id] = it.status || it.state || null);
                        list.forEach(it => it.__status = map[it.id] || null);
                    }
                } catch(e) { /* ignore */ }
            }

            select.innerHTML = '';
            if (!list.length) {
                select.innerHTML = '<option value="">Belum ada sender</option>';
            } else {
                let defaultId = null;
                list.forEach(s => { if (s.is_default) defaultId = s.id; });
                list.forEach(s => {
                    let label = s.name ?? s.number ?? s.session ?? ('Sender #' + s.id);
                    if (s.is_default) label += ' — Default';
                    if (s.__status)   label += ` (${s.__status})`;

                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = label;
                    if (defaultId ? s.id === defaultId : String(s.id) === String(prev)) opt.selected = true;
                    select.appendChild(opt);
                });
            }
        } catch (e) {
            select.innerHTML = '<option value="">Gagal memuat sender</option>';
        } finally {
            select.disabled = false;
            spinner.classList.add('hidden');
        }
    }
    btnRefresh.addEventListener('click', refreshSenders);
})();
</script>
@endsection
