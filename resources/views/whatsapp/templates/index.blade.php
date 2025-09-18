@extends('layouts.app')

@section('title', 'WA Templates - Matik Growth Hub')

@section('content')
@php
    // Logika internal untuk pemrosesan variabel dipertahankan
    $allowedCanonical = ['lead.status', 'lead.store_name', 'lead.phone', 'lead.registered_at', 'lead.expiry_date', 'lead.email', 'owner.name'];
    function canonicalizeVar($keyRaw) {
        $k = strtolower(trim((string)$keyRaw));
        $k = preg_replace('/^\{\{|\}\}$/', '', trim($k));
        $variants = array_unique([$k, str_replace('-', '_', $k), str_replace('_', '-', $k)]);
        foreach ($variants as $v) {
            if (in_array($v, ['lead.status', 'lead.store_name', 'lead.phone', 'lead.registered_at', 'lead.expiry_date', 'lead.email', 'owner.name'], true)) return $v;
            $map = ['lead.nama_toko' => 'lead.store_name', 'lead.toko' => 'lead.store_name', 'store_name' => 'lead.store_name', 'nama_toko' => 'lead.store_name', 'toko' => 'lead.store_name', 'phone' => 'lead.phone', 'telepon' => 'lead.phone', 'whatsapp' => 'lead.phone', 'lead.telepon' => 'lead.phone', 'lead.whatsapp' => 'lead.phone', 'registered_at' => 'lead.registered_at', 'tanggal_daftar' => 'lead.registered_at', 'lead.tanggal_daftar'=> 'lead.registered_at', 'created_at' => 'lead.registered_at', 'lead.created_at' => 'lead.registered_at', 'expiry_date' => 'lead.expiry_date', 'end_date' => 'lead.expiry_date', 'tanggal_habis' => 'lead.expiry_date', 'expired_at' => 'lead.expiry_date', 'lead.tanggal_habis'=> 'lead.expiry_date', 'lead.expired_at'   => 'lead.expiry_date', 'lead.end_date' => 'lead.expiry_date', 'email' => 'lead.email', 'lead.mail' => 'lead.email', 'owner' => 'owner.name', 'owner_name' => 'owner.name', 'owner.name' => 'owner.name', 'nama_owner' => 'owner.name', 'pemilik' => 'owner.name', 'nama_pemilik' => 'owner.name'];
            if (array_key_exists($v, $map)) return $map[$v];
        }
        return null;
    }
    function ctx_get_scalar($ctx, $canonicalKey) {
        if (!is_array($ctx) || !$canonicalKey) return null;
        $segments = explode('.', $canonicalKey); $cur = $ctx;
        foreach ($segments as $seg) {
            if (!is_array($cur) || !array_key_exists($seg, $cur)) return null;
            $cur = $cur[$seg];
        }
        return is_scalar($cur) ? $cur : null;
    }
    function extractVarsFromBody($text) {
        preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', (string)$text, $m);
        return array_values(array_unique($m[1] ?? []));
    }
    function renderFilled($text, $vars, $ctx) {
        $filled = (string) $text;
        if (!is_array($vars) || count($vars) === 0) $vars = extractVarsFromBody($filled);
        $cleanVars = [];
        foreach ($vars as $v) {
            $v = preg_replace('/^\s*\{\{\s*|\s*\}\}\s*$/', '', (string)$v);
            if ($canon = canonicalizeVar($v)) $cleanVars[] = ['raw' => $v, 'canon' => $canon];
        }
        foreach ($cleanVars as $item) {
            $val = (string) (ctx_get_scalar($ctx, $item['canon']) ?? '<'.$item['raw'].'>');
            $filled = preg_replace('/\{\{\s*' . preg_quote($item['raw'], '/') . '\s*\}\}/', $val, $filled);
        }
        return $filled;
    }
    $openCreate = $errors->any() && old('_form') === 'create';
    $openEditId = $errors->any() && old('_form') === 'edit' ? (int) old('_id') : null;
    $availableVars = $allowedCanonical;
@endphp

<div class="container mx-auto py-6">

    {{-- Alerts --}}
    @if (session('success'))
    <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down"><div><svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>{{ session('success') }}</span></div></div>
    @endif
    @if ($errors->any())
    <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down"><div><svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span><strong>Terdapat kesalahan!</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></span></div></div>
    @endif

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">WhatsApp Templates</h1>
            <p class="mt-1 text-base-content/70">Kelola template pesan untuk automasi dan broadcast.</p>
        </div>
        <a href="#create_template_modal" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Template Baru
        </a>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-md border border-base-300/50 mt-6" data-aos="fade-up">
        <form action="{{ route('whatsapp.templates.index') }}" method="GET" class="card-body p-4">
             <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <input type="text" name="q" value="{{ request('q', $q ?? '') }}" placeholder="Cari berdasarkan nama atau isi..." class="input input-bordered w-full">
                <select name="status" class="select select-bordered w-full">
                    @php $statusValue = $status ?? request('status', 'all'); @endphp
                    <option value="all" {{ $statusValue === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="active" {{ $statusValue === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ $statusValue === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-secondary flex-grow">Filter</button>
                    <a href="{{ route('whatsapp.templates.index') }}" class="btn btn-ghost">Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Tabel Templates --}}
    <div class="card bg-base-100 shadow-lg border border-base-300/50 mt-8" data-aos="fade-up">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Nama Template</th>
                        <th class="max-w-xs">Isi Pesan</th>
                        <th>Variabel</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr class="hover">
                            <td class="font-semibold text-base-content">{{ $template->name }}</td>
                            <td class="max-w-xs">
                                <p class="truncate text-sm">{{ $template->body }}</p>
                            </td>
                            <td>
                                @php
                                    $varsRaw = is_array($template->variables) && count($template->variables) > 0 ? $template->variables : extractVarsFromBody($template->body);
                                    $displayAllowed = collect($varsRaw)->map(fn($v) => canonicalizeVar($v))->filter()->unique()->all();
                                @endphp
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($displayAllowed as $canon)
                                        <span class="badge badge-ghost text-xs">{{ '{' . '{' . $canon . '}' . '}' }}</span>
                                    @empty
                                        <span class="text-base-content/60 text-xs">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if ($template->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-neutral">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                 <div class="dropdown dropdown-end">
                                    <label tabindex="0" class="btn btn-ghost btn-xs">opsi</label>
                                    <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-48 z-10 border border-base-300/50">
                                        <li><a href="#preview_template_{{ $template->id }}">Pratinjau</a></li>
                                        <li><a href="#edit_template_{{ $template->id }}">Edit</a></li>
                                        <div class="divider my-1"></div>
                                        <li>
                                            <form action="{{ route('whatsapp.templates.destroy', ['whatsapp_template' => $template->id]) }}" method="POST" onsubmit="return confirm('Hapus template ini?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full text-left text-error">Hapus</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-16 text-base-content/60">Tidak ada template ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
     <div class="mt-6" data-aos="fade-up">
        {{ $templates->links() }}
    </div>
</div>

{{-- Modals --}}
@foreach ($templates as $template)
    {{-- PREVIEW modal --}}
    <div id="preview_template_{{ $template->id }}" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg text-base-content">Pratinjau: {{ $template->name }}</h3>
            <div class="mt-4 space-y-3">
                <div class="p-3 rounded-lg bg-success/10 text-success-content">
                    <div class="text-xs opacity-70 mb-1 font-semibold">Hasil Jadi (dengan data contoh)</div>
                    @php
                        $varsRaw = is_array($template->variables) && count($template->variables) > 0 ? $template->variables : [];
                        $filledPreview = renderFilled($template->body, $varsRaw, $previewContext ?? []);
                    @endphp
                    <div class="whitespace-pre-wrap break-words font-mono text-sm">{{ $filledPreview }}</div>
                </div>
                <div class="p-3 rounded-lg bg-base-200">
                    <div class="text-xs opacity-70 mb-1 font-semibold">Template Asli</div>
                    <div class="whitespace-pre-wrap break-words font-mono text-sm">{{ $template->body }}</div>
                </div>
            </div>
            <div class="modal-action">
                <a href="#" class="btn">Tutup</a>
            </div>
        </div>
    </div>

    {{-- EDIT modal --}}
    <div id="edit_template_{{ $template->id }}" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <form action="{{ route('whatsapp.templates.update', ['whatsapp_template' => $template->id]) }}" method="POST">
                @csrf @method('PATCH')
                <input type="hidden" name="_form" value="edit"><input type="hidden" name="_id" value="{{ $template->id }}">
                <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                <h3 class="font-bold text-lg text-base-content">Edit Template</h3>

                <div class="mt-4 space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Nama Template</span></label>
                        <input type="text" name="name" value="{{ old('_form')==='edit' && (int)old('_id')===(int)$template->id ? old('name', $template->name) : $template->name }}" class="input input-bordered w-full" required />
                        @error('name') @if($openEditId === (int)$template->id)<div class="text-error text-xs mt-1">{{ $message }}</div>@endif @enderror
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Isi Pesan</span></label>
                        <textarea name="body" rows="5" class="textarea textarea-bordered w-full font-mono text-sm" required>{{ old('_form')==='edit' && (int)old('_id')===(int)$template->id ? old('body', $template->body) : $template->body }}</textarea>
                        @error('body') @if($openEditId === (int)$template->id)<div class="text-error text-xs mt-1">{{ $message }}</div>@endif @enderror
                        <div class="text-xs text-base-content/60 mt-2 space-y-1">
                            <div class="font-semibold">Variabel yang didukung:</div>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($availableVars as $k)<span class="badge badge-ghost">{{ '{' . '{' . $k . '}' . '}' }}</span>@endforeach
                            </div>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary" @checked(old('_form')==='edit' && (int)old('_id')===(int)$template->id ? old('is_active', $template->is_active) : $template->is_active) />
                            <span class="label-text">Aktifkan Template</span>
                        </label>
                    </div>
                </div>

                <div class="modal-action">
                    <a href="#" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">Update Template</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

{{-- CREATE modal --}}
<div id="create_template_modal" class="modal {{ $openCreate ? 'modal-open' : '' }}">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('whatsapp.templates.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="create">
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg text-base-content">Template Baru</h3>

            <div class="mt-4 space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text">Nama Template</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered w-full" required />
                    @error('name')<div class="text-error text-xs mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Isi Pesan</span></label>
                    <textarea name="body" rows="5" class="textarea textarea-bordered w-full font-mono text-sm" required>{{ old('body') }}</textarea>
                    @error('body')<div class="text-error text-xs mt-1">{{ $message }}</div>@enderror
                    <div class="text-xs text-base-content/60 mt-3 space-y-2">
                        <div class="font-semibold">Variabel yang didukung:</div>
                        <div class="flex flex-wrap gap-1">
                            @foreach ($availableVars as $k)<span class="badge badge-ghost">{{ '{' . '{' . $k . '}' . '}' }}</span>@endforeach
                        </div>
                        <p class="opacity-70">Alias seperti <code>&#123;&#123;nama_toko&#125;&#125;</code> akan dipetakan otomatis.</p>
                    </div>
                </div>
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary" @checked(old('is_active', true)) />
                        <span class="label-text">Aktifkan Template</span>
                    </label>
                </div>
            </div>

            <div class="modal-action">
                <a href="#" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Template</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchTab(clickedTab, targetTabId) {
        const modal = clickedTab.closest('.modal-box');
        if (!modal) return;

        modal.querySelectorAll('.tabs .tab').forEach(tab => tab.classList.remove('tab-active'));
        modal.querySelectorAll('[id^="tab-"]').forEach(panel => panel.classList.add('hidden'));

        clickedTab.classList.add('tab-active');
        const targetPanel = document.getElementById(targetTabId);
        if(targetPanel) targetPanel.classList.remove('hidden');
    }
</script>
@endpush
