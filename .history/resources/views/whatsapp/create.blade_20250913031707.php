@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 max-w-5xl" x-data="broadcastPage()">
    <div class="flex items-center justify-between my-6">
        <h1 class="text-xl font-semibold">WhatsApp Broadcast</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('waha-senders.index') }}" target="_blank" class="btn btn-sm">Kelola Sender</a>
            <button type="button" class="btn btn-sm" @click="loadSenders()">Refresh Sender</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-6">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.broadcast.store') }}">
                @csrf

                {{-- Sender --}}
                <div class="mb-5">
                    <label class="label font-medium">Kirim Dari</label>
                    <select name="sender_id" x-ref="senderSelect" class="input input-bordered w-full" required>
                        <option value="">Memuat daftar sender...</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Pilih nomor pengirim. Default akan ditandai ⭐.</p>
                </div>

                {{-- Mode --}}
                <div class="mb-5">
                    <label class="label font-medium">Mode</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="mode" value="custom" x-model="mode" checked>
                            <span>Custom Message</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="mode" value="template" x-model="mode">
                            <span>Template</span>
                        </label>
                    </div>
                </div>

                {{-- Custom Message --}}
                <div class="mb-5" x-show="mode === 'custom'">
                    <label class="label font-medium">Pesan</label>
                    <textarea name="message" class="textarea textarea-bordered w-full h-32" placeholder="Tulis pesan. Gunakan {{'{{name}}'}} untuk nama penerima.">{{ old('message') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Placeholder yang didukung: <code>{{'{{name}}'}}</code>, <code>{{'{{nama}}'}}</code>, <code>{{'{{nama_pelanggan}}'}}</code></p>
                </div>

                {{-- Template --}}
                <div class="mb-5" x-show="mode === 'template'">
                    <label class="label font-medium">Template</label>
                    <select name="template_id" class="input input-bordered w-full">
                        <option value="">-- Pilih Template --</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" @selected(old('template_id') == $tpl->id)>{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                    <label class="label font-medium mt-4">Template Params (JSON)</label>
                    <textarea name="params_json" class="textarea textarea-bordered w-full h-28" placeholder='contoh: {"name":"Budi","code":"1234"}'>{{ old('params_json') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Isi hanya jika template Anda butuh parameter.</p>
                </div>

                {{-- Recipients --}}
                <div class="mb-5">
                    <label class="label font-medium">Daftar Penerima</label>
                    <textarea name="recipients" class="textarea textarea-bordered w-full h-40" placeholder="Satu baris satu penerima. Contoh:
628123456789
Budi, 628123456789
628123456789 | Budi">{{ old('recipients') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Format didukung: <code>628xxxx</code>, <code>Nama, 628xxxx</code>, <code>628xxxx | Nama</code></p>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="reset" class="btn">Reset</button>
                    <button type="submit" class="btn btn-primary">Kirim Broadcast</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Alpine helpers --}}
<script>
function broadcastPage() {
    return {
        mode: 'custom',
        async loadSenders() {
            const sel = this.$refs.senderSelect;
            sel.innerHTML = '<option value="">Memuat...</option>';
            try {
                const res = await fetch("{{ route('waha-senders.index') }}", { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                const data = json?.data ?? [];
                sel.innerHTML = '<option value="">-- Pilih Nomor Pengirim --</option>';
                data.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = (s.is_default ? '⭐ ' : '') + `${s.name} (${s.number})`;
                    sel.appendChild(opt);
                });
                // auto-pilih default jika ada
                const def = data.find(x => x.is_default);
                if (def) sel.value = def.id;
            } catch (e) {
                sel.innerHTML = '<option value="">Gagal memuat sender</option>';
                console.error(e);
            }
        },
        init() {
            this.loadSenders();
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') this.loadSenders();
            });
        }
    }
}
</script>
@endsection
