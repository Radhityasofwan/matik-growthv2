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
            <button type="button" id="btnRefreshSenders" class="btn btn-outline btn-sm inline-flex items-center gap-2" onclick="window.location.reload()">
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
                                    @php $opts = collect($senders ?? []); @endphp
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
                                placeholder="Tulis pesan. Gunakan {{ '{' }}{{ 'name' }}{{ '}' }} atau @{{ '{' }}{{ 'name' }}{{ '}' }} untuk menyapa penerima.">{{ old('message') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Juga mendukung <code>{{ '{' }}{{ 'nama' }}{{ '}' }}</code> dan <code>{{ '{' }}{{ 'nama_pelanggan' }}{{ '}' }}</code>.</p>
                        </div>

                        {{-- Template (hidden by default) --}}
                        <div id="boxTemplate" class="mt-5 hidden">
                            <label class="label"><span class="label-text">Template</span></label>
                            <select name="template_id" class="select select-bordered w-full">
                                <option value="" disabled selected>— Pilih Template —</option>
                                @foreach(($templates ?? []) as $tpl)
                                    @php $label = $tpl->name ?? ('Template #'.$tpl->id); @endphp
                                    <option value="{{ $tpl->id }}">{{ $label }}@if(isset($tpl->is_active) && !$tpl->is_active) (nonaktif) @endif</option>
                                @endforeach
                            </select>

                            {{-- Params user-friendly --}}
                            <div id="paramsBox" class="mt-3">
                                <label class="label"><span class="label-text">Parameter Template</span></label>
                                <div id="paramsList" class="space-y-2"></div>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" id="btnAddParam" class="btn btn-sm btn-outline">+ Tambah Parameter</button>
                                    <button type="button" id="btnClearParam" class="btn btn-sm">Bersihkan</button>
                                </div>
                                <input type="hidden" name="params_json" id="params_json" value='{{ old('params_json','{}') }}'>
                                <p class="text-xs text-gray-500 mt-2">Isi pasangan <em>Key → Value</em> (contoh: <code>code → AB-123</code>). Berlaku untuk semua penerima.</p>
                            </div>
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
                    <p class="text-xs text-gray-500 mb-4">Contoh untuk 3 penerima pertama (ketikkan daftar penerima & pesan untuk melihat).</p>
                    <div id="previewList" class="space-y-2 text-sm"></div>

                    <div class="divider my-6"></div>
                    <h4 class="font-semibold mb-2">Tips</h4>
                    <ul class="text-sm list-disc ml-5 space-y-1">
                        <li>Gunakan <code>{{ '{' }}{{ 'name' }}{{ '}' }}</code> atau <code>@{{ '{' }}{{ 'name' }}{{ '}' }}</code> untuk menyapa penerima (mode Custom).</li>
                        <li>Nomor harus format internasional (cth: <code>628...</code>).</li>
                        <li>Kelola sender lewat tombol <strong>Kelola Sender</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
  const modeCustom = document.getElementById('mode_custom');
  const modeTpl    = document.getElementById('mode_template');
  const boxCustom  = document.getElementById('boxCustom');
  const boxTemplate= document.getElementById('boxTemplate');
  const recipients = document.querySelector('textarea[name="recipients"]');
  const message    = document.querySelector('textarea[name="message"]');
  const preview    = document.getElementById('previewList');

  // Params UI
  const paramsHidden = document.getElementById('params_json');
  const paramsList   = document.getElementById('paramsList');
  const btnAddParam  = document.getElementById('btnAddParam');
  const btnClearParam= document.getElementById('btnClearParam');

  function paramsUpdateHidden() {
    const obj = {};
    paramsList.querySelectorAll('.param-row').forEach(row => {
      const k = row.querySelector('.param-key')?.value?.trim() || '';
      const v = row.querySelector('.param-val')?.value ?? '';
      if (k) obj[k] = v;
    });
    paramsHidden.value = JSON.stringify(obj);
  }

  function addParamRow(key = '', val = '') {
    const row = document.createElement('div');
    row.className = 'param-row flex gap-2';
    row.innerHTML = `
      <input type="text" class="input input-bordered param-key w-1/3" placeholder="Key" value="${key}">
      <input type="text" class="input input-bordered param-val w-2/3" placeholder="Value" value="${val}">
      <button type="button" class="btn btn-error btn-sm px-3">x</button>
    `;
    row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', () => {
      paramsUpdateHidden(); renderPreview();
    }));
    row.querySelector('button').addEventListener('click', () => {
      row.remove(); paramsUpdateHidden(); renderPreview();
    });
    paramsList.appendChild(row);
    paramsUpdateHidden();
  }

  if (btnAddParam) btnAddParam.addEventListener('click', () => addParamRow());
  if (btnClearParam) btnClearParam.addEventListener('click', () => {
    paramsList.innerHTML = ''; paramsUpdateHidden(); renderPreview();
  });

  // preload old values
  try {
    const initial = JSON.parse(paramsHidden?.value || '{}');
    Object.entries(initial).forEach(([k,v]) => addParamRow(k, String(v)));
  } catch(e) {}

  // Token literal
  const TK_NAME  = '{{name}}';
  const TK_ANAME = '@{{name}}';
  const TK_NAMA  = '{{nama}}';
  const TK_NAMA_PELANGGAN = '{{nama_pelanggan}}';

  function toggle(){
    if (modeTpl.checked) { boxTemplate.classList.remove('hidden'); boxCustom.classList.add('hidden'); }
    else { boxCustom.classList.remove('hidden'); boxTemplate.classList.add('hidden'); }
    renderPreview();
  }
  modeCustom.addEventListener('change', toggle);
  modeTpl.addEventListener('change', toggle);

  [recipients, message].forEach(el => el && el.addEventListener('input', renderPreview));

  function parseRecipients(text){
    const lines = (text || '').split(/\r?\n/).map(s => s.trim()).filter(Boolean);
    const out = [];
    for (const line of lines) {
      let name = null, phone = null;
      if (line.includes(',')) {
        const [a,b] = line.split(',',2).map(s=>s.trim());
        const da = (a||'').replace(/\D+/g,''); const db = (b||'').replace(/\D+/g,'');
        if (da.length>=7 && db.length<7) { phone=da; name=b; }
        else if (db.length>=7 && da.length<7) { phone=db; name=a; }
        else if (da.length>=7) { phone=da; name=b; }
        else if (db.length>=7) { phone=db; name=a; }
      } else if (line.includes('|')) {
        const [a,b] = line.split('|',2).map(s=>s.trim());
        const da = (a||'').replace(/\D+/g,''); const db = (b||'').replace(/\D+/g,'');
        if (da.length>=7 && db.length<7) { phone=da; name=b; }
        else if (db.length>=7 && da.length<7) { phone=db; name=a; }
        else if (da.length>=7) { phone=da; name=b; }
        else if (db.length>=7) { phone=db; name=a; }
      } else {
        const d = line.replace(/\D+/g,'');
        if (d.length>=7) phone=d;
      }
      if (phone) out.push({phone, name: name || phone.slice(-4)});
      if (out.length>=3) break;
    }
    return out;
  }

  function renderPreview(){
    if (!preview) return;
    preview.innerHTML = '';
    const list = parseRecipients(recipients?.value || '');
    if (!list.length) return;

    let txt = '';
    if (modeCustom.checked) {
      const raw = message?.value || '';
      list.forEach(r=>{
        let t = raw;
        t = t.replaceAll(TK_NAME, r.name)
             .replaceAll(TK_ANAME, r.name)
             .replaceAll(TK_NAMA, r.name)
             .replaceAll(TK_NAMA_PELANGGAN, r.name);
        txt += `<div class="p-2 rounded border"><div class="opacity-60">→ ${r.phone} (${r.name})</div><div>${escapeHtml(t)}</div></div>`;
      });
    } else {
      let params = {};
      try { params = JSON.parse(paramsHidden?.value || '{}') || {}; } catch(e){ params = {}; }
      list.forEach(r=>{
        const ctx = Object.assign({}, params, {name:r.name, nama:r.name, nama_pelanggan:r.name, phone:r.phone});
        let t = (window.__tplBody || 'Template aktif akan dirender saat submit');
        t = t.replaceAll(TK_NAME, ctx.name)
             .replaceAll(TK_ANAME, ctx.name)
             .replaceAll(TK_NAMA, ctx.name)
             .replaceAll(TK_NAMA_PELANGGAN, ctx.name);
        txt += `<div class="p-2 rounded border"><div class="opacity-60">→ ${r.phone} (${r.name})</div><div>${escapeHtml(t)}</div></div>`;
      });
    }
    preview.innerHTML = txt;
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m])); }

  // initial
  toggle();
})();
</script>
@endsection
