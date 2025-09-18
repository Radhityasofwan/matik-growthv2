@extends('layouts.app')
@section('title', 'WhatsApp – Broadcast')

@section('content')
<div class="container mx-auto py-6">

    {{-- Alerts --}}
    @if (session('error'))
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down"><div>{{ session('error') }}</div></div>
    @endif
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down"><div>{{ session('success') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
            <div>
                <strong>Terjadi kesalahan:</strong>
                <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">WhatsApp Broadcast</h1>
            <p class="mt-1 text-base-content/70">Kirim pesan massal menggunakan WAHA.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('waha-senders.index') }}" class="btn btn-secondary btn-outline btn-sm" target="_blank">Kelola Sender</a>
            <button type="button" class="btn btn-ghost btn-sm" onclick="window.location.reload()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                Refresh Sender
            </button>
        </div>
    </div>

    {{-- SINKRONISASI: Menggunakan struktur card dan form DaisyUI yang konsisten --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form Kiri --}}
        <div class="lg:col-span-2 card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up">
            <div class="card-body">
                <form method="POST" action="{{ route('whatsapp.broadcast.store') }}" id="broadcastForm">
                    @csrf

                    {{-- Kirim Dari --}}
                    <div class="form-control">
                        <label class="label"><span class="label-text">Kirim Dari</span></label>
                        <select name="sender_id" id="senderSelect" class="select select-bordered w-full" required>
                            @php $opts = collect($senders ?? []); @endphp
                            @if($opts->isEmpty())
                                <option value="" selected disabled>Belum ada sender aktif</option>
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
                    </div>

                    {{-- Daftar Penerima --}}
                    <div class="form-control mt-4">
                        <label class="label"><span class="label-text">Daftar Penerima</span></label>
                        <textarea name="recipients" class="textarea textarea-bordered w-full h-36 font-mono text-sm" placeholder="Satu baris satu penerima. Contoh:&#10;628123456789&#10;Budi, 628123456789&#10;628123456789 | Budi" required>{{ old('recipients') }}</textarea>
                        <label class="label"><span class="label-text-alt">Format: <code>628xxxx</code>, <code>Nama, 628xxxx</code>, atau <code>628xxxx | Nama</code></span></label>
                    </div>

                    {{-- Mode Pesan (Tabs) --}}
                    <div class="form-control mt-4">
                         <label class="label"><span class="label-text">Mode Pesan</span></label>
                         <div class="tabs tabs-boxed">
                            <a class="tab tab-active" id="tab_custom">Pesan Custom</a>
                            <a class="tab" id="tab_template">Gunakan Template</a>
                            {{-- Input radio tersembunyi untuk form submission --}}
                            <input type="radio" name="mode" value="custom" id="mode_custom" class="hidden" checked>
                            <input type="radio" name="mode" value="template" id="mode_template" class="hidden">
                        </div>
                    </div>

                    {{-- Konten Pesan Custom --}}
                    <div id="boxCustom" class="form-control mt-2">
                        <label class="label"><span class="label-text">Isi Pesan</span></label>
                        <textarea name="message" class="textarea textarea-bordered w-full h-36" placeholder="Tulis pesan. Gunakan @{{name}} untuk menyapa penerima.">{{ old('message') }}</textarea>
                        <label class="label"><span class="label-text-alt">Juga mendukung <code>@{{nama}}</code> dan <code>@{{nama_pelanggan}}</code>.</span></label>
                    </div>

                    {{-- Konten Template --}}
                    <div id="boxTemplate" class="mt-2 hidden space-y-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Pilih Template</span></label>
                            <select name="template_id" class="select select-bordered w-full">
                                <option value="" disabled selected>— Pilih Template —</option>
                                @foreach(($templates ?? []) as $tpl)
                                    @php $label = $tpl->name ?? ('Template #'.$tpl->id); @endphp
                                    <option value="{{ $tpl->id }}">{{ $label }}@if(isset($tpl->is_active) && !$tpl->is_active) (nonaktif) @endif</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="paramsBox">
                            <label class="label"><span class="label-text">Parameter Template</span></label>
                            <div id="paramsList" class="space-y-2"></div>
                            <div class="mt-2 flex gap-2">
                                <button type="button" id="btnAddParam" class="btn btn-sm btn-outline">+ Tambah Parameter</button>
                                <button type="button" id="btnClearParam" class="btn btn-sm btn-ghost">Bersihkan</button>
                            </div>
                            <input type="hidden" name="params_json" id="params_json" value='{{ old('params_json','{}') }}'>
                            <label class="label"><span class="label-text-alt">Isi pasangan Key → Value (contoh: <code>code → AB-123</code>).</span></label>
                        </div>
                    </div>

                    <div class="card-actions mt-6">
                        <button type="submit" class="btn btn-primary">Kirim Broadcast</button>
                        <button type="reset" class="btn btn-ghost">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Pratinjau & Tips Kanan --}}
        <div class="card bg-base-100 shadow-lg border border-base-300/50" data-aos="fade-up" data-aos-delay="100">
            <div class="card-body">
                <h3 class="card-title">Pratinjau</h3>
                <p class="text-xs text-base-content/70 mb-4">Contoh untuk 3 penerima pertama akan muncul di sini.</p>
                <div id="previewList" class="space-y-2 text-sm">
                    <div class="text-center py-8 text-base-content/60">Ketik daftar penerima & pesan untuk melihat pratinjau.</div>
                </div>

                <div class="divider my-6"></div>
                <h4 class="font-semibold mb-2 text-base-content">Tips</h4>
                <ul class="text-sm list-disc ml-5 space-y-1 text-base-content/70">
                    <li>Gunakan <code>@{{name}}</code> untuk menyapa penerima (mode Custom).</li>
                    <li>Nomor harus berformat internasional (cth: <code>628...</code>).</li>
                    <li>Kelola sender dan template melalui menu di sidebar.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@verbatim
<script>
(function(){
  // Elemen mode
  const tabCustom = document.getElementById('tab_custom');
  const tabTpl    = document.getElementById('tab_template');
  const modeCustom = document.getElementById('mode_custom');
  const modeTpl    = document.getElementById('mode_template');

  // Kontainer
  const boxCustom  = document.getElementById('boxCustom');
  const boxTemplate= document.getElementById('boxTemplate');

  // Input utama
  const recipients = document.querySelector('textarea[name="recipients"]');
  const message    = document.querySelector('textarea[name="message"]');
  const preview    = document.getElementById('previewList');

  // Params UI
  const paramsHidden = document.getElementById('params_json');
  const paramsList   = document.getElementById('paramsList');
  const btnAddParam  = document.getElementById('btnAddParam');
  const btnClearParam= document.getElementById('btnClearParam');

  function paramsUpdateHidden() {
    if (!paramsList || !paramsHidden) return;
    const obj = {};
    paramsList.querySelectorAll('.param-row').forEach(row => {
      const k = row.querySelector('.param-key')?.value?.trim() || '';
      const v = row.querySelector('.param-val')?.value ?? '';
      if (k) obj[k] = v;
    });
    paramsHidden.value = JSON.stringify(obj);
  }

  function addParamRow(key = '', val = '') {
    if (!paramsList) return;
    const row = document.createElement('div');
    row.className = 'param-row flex gap-2';
    row.innerHTML = `
      <input type="text" class="input input-bordered input-sm param-key w-1/3" placeholder="Key" value="${key}">
      <input type="text" class="input input-bordered input-sm param-val w-2/3" placeholder="Value" value="${val}">
      <button type="button" class="btn btn-error btn-sm btn-circle btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
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
    if(paramsList) paramsList.innerHTML = '';
    paramsUpdateHidden();
    renderPreview();
  });

  try {
    const initial = JSON.parse(paramsHidden?.value || '{}');
    Object.entries(initial).forEach(([k,v]) => addParamRow(k, String(v)));
  } catch(e) {}

  const TK_NAME  = '{{name}}';
  const TK_ANAME = '@{{name}}';
  const TK_NAMA  = '{{nama}}';
  const TK_NAMA_PELANGGAN = '{{nama_pelanggan}}';

  function toggleMode(mode) {
    if (mode === 'template') {
        modeTpl.checked = true;
        modeCustom.checked = false;
        tabTpl.classList.add('tab-active');
        tabCustom.classList.remove('tab-active');
        boxTemplate.classList.remove('hidden');
        boxCustom.classList.add('hidden');
    } else {
        modeCustom.checked = true;
        modeTpl.checked = false;
        tabCustom.classList.add('tab-active');
        tabTpl.classList.remove('tab-active');
        boxCustom.classList.remove('hidden');
        boxTemplate.classList.add('hidden');
    }
    renderPreview();
  }
  if (tabCustom) tabCustom.addEventListener('click', () => toggleMode('custom'));
  if (tabTpl) tabTpl.addEventListener('click', () => toggleMode('template'));

  if(recipients) recipients.addEventListener('input', renderPreview);
  if(message) message.addEventListener('input', renderPreview);

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
    const list = parseRecipients(recipients?.value || '');
    if (!list.length) {
        preview.innerHTML = '<div class="text-center py-8 text-base-content/60">Ketik daftar penerima & pesan untuk melihat pratinjau.</div>';
        return;
    }

    let txt = '';
    let tpl = '';
    if (modeCustom.checked) {
      tpl = message?.value || '';
    } else {
      tpl = 'Template aktif akan dirender saat submit.';
    }

    list.forEach(r=>{
        let t = tpl;
        t = t.replaceAll(TK_NAME, r.name)
             .replaceAll(TK_ANAME, r.name)
             .replaceAll(TK_NAMA, r.name)
             .replaceAll(TK_NAMA_PELANGGAN, r.name);
        txt += `<div class="p-3 rounded-lg border border-base-300 bg-base-200"><div class="font-semibold text-xs text-base-content/60">→ ${r.phone} (${r.name})</div><div class="mt-1 whitespace-pre-wrap">${escapeHtml(t)}</div></div>`;
    });

    preview.innerHTML = txt;
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#0-39;' }[m])); }

  toggleMode('custom');
})();
</script>
@endverbatim
@endsection
