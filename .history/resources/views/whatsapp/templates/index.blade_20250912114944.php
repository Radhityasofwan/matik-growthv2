@extends('layouts.app')

@section('title', 'WA Templates - Matik Growth Hub')

@section('content')
@php
    /** ==================== VARIABEL YANG DIIZINKAN ===================== *
     * Hanya ini yang boleh dipakai & di-preview:
     *  - lead.status
     *  - lead.store_name   (alias: lead.nama_toko, lead.toko, store_name, nama_toko, toko)
     *  - lead.phone        (alias: phone, telepon, whatsapp)
     *  - lead.registered_at(alia: registered_at, tanggal_daftar, created_at)
     *  - lead.expiry_date  (alias: expiry_date, end_date, tanggal_habis, expired_at)
     *  - lead.email        (alias: email)
     *  - owner.name        (alias: owner, owner_name, nama_owner, pemilik, nama_pemilik)
     * ==================================================================== */

    // Canonical list (untuk ditampilkan sebagai panduan)
    $allowedCanonical = [
        'lead.status',
        'lead.store_name',
        'lead.phone',
        'lead.registered_at',
        'lead.expiry_date',
        'lead.email',
        'owner.name',
    ];

    // Map sinonim -> canonical
    function canonicalizeVar($keyRaw) {
        $k = strtolower(trim((string)$keyRaw));
        $k = preg_replace('/\s+/', '', $k);

        // hilangkan {{ }} jika ada
        $k = preg_replace('/^\{\{|\}\}$/', '', $k);
        $k = trim($k);

        // normalisasi minus/underscore (kita tes beberapa varian di bawah)
        $variants = array_unique([
            $k,
            str_replace('-', '_', $k),
            str_replace('_', '-', $k),
        ]);

        foreach ($variants as $v) {
            // langsung canonical
            if (in_array($v, [
                'lead.status',
                'lead.store_name',
                'lead.phone',
                'lead.registered_at',
                'lead.expiry_date',
                'lead.email',
                'owner.name',
            ], true)) return $v;

            // alias ke canonical
            $map = [
                // store_name
                'lead.nama_toko' => 'lead.store_name',
                'lead.toko'      => 'lead.store_name',
                'store_name'     => 'lead.store_name',
                'nama_toko'      => 'lead.store_name',
                'toko'           => 'lead.store_name',

                // phone
                'phone'          => 'lead.phone',
                'telepon'        => 'lead.phone',
                'whatsapp'       => 'lead.phone',
                'lead.telepon'   => 'lead.phone',
                'lead.whatsapp'  => 'lead.phone',

                // registered_at
                'registered_at'     => 'lead.registered_at',
                'tanggal_daftar'    => 'lead.registered_at',
                'lead.tanggal_daftar'=> 'lead.registered_at',
                'created_at'        => 'lead.registered_at',
                'lead.created_at'   => 'lead.registered_at',

                // expiry_date
                'expiry_date'       => 'lead.expiry_date',
                'end_date'          => 'lead.expiry_date',
                'tanggal_habis'     => 'lead.expiry_date',
                'expired_at'        => 'lead.expiry_date',
                'lead.tanggal_habis'=> 'lead.expiry_date',
                'lead.expired_at'   => 'lead.expiry_date',
                'lead.end_date'     => 'lead.expiry_date',

                // email
                'email'          => 'lead.email',
                'lead.mail'      => 'lead.email',

                // owner.name
                'owner'          => 'owner.name',
                'owner_name'     => 'owner.name',
                'owner.name'     => 'owner.name',
                'nama_owner'     => 'owner.name',
                'pemilik'        => 'owner.name',
                'nama_pemilik'   => 'owner.name',
            ];

            if (array_key_exists($v, $map)) {
                return $map[$v];
            }
        }

        return null; // tidak diizinkan
    }

    /** Ambil nilai dari context berdasarkan canonical key (scalar only). */
    function ctx_get_scalar($ctx, $canonicalKey) {
        if (!is_array($ctx) || !$canonicalKey) return null;
        $segments = explode('.', $canonicalKey);
        $cur = $ctx;
        foreach ($segments as $seg) {
            if (!is_array($cur) || !array_key_exists($seg, $cur)) {
                return null;
            }
            $cur = $cur[$seg];
        }
        if (is_array($cur) || is_object($cur)) return null;
        return $cur;
    }

    /** Ekstrak {{var}} dari body (fallback jika kolom variables kosong). */
    function extractVarsFromBody($text) {
        $vars = [];
        if (preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', (string)$text, $m)) {
            $vars = array_values(array_unique($m[1] ?? []));
        }
        return $vars;
    }

    /** Render preview: hanya ganti var yang diizinkan (canonicalized). */
    function renderFilled($text, $vars, $ctx) {
        $filled = (string) $text;

        // fallback ambil dari body bila variables kosong
        if (!is_array($vars) || count($vars) === 0) {
            $vars = extractVarsFromBody($filled);
        }

        // normalisasi dan filter hanya yang diizinkan
        $cleanVars = [];
        foreach ($vars as $v) {
            $v = (string)$v;
            $v = preg_replace('/^\s*\{\{\s*|\s*\}\}\s*$/', '', $v);
            $canon = canonicalizeVar($v);
            if ($canon) {
                $cleanVars[] = ['raw' => $v, 'canon' => $canon];
            }
        }

        foreach ($cleanVars as $item) {
            $raw   = $item['raw'];   // nama var yang dipakai di body
            $canon = $item['canon']; // canonical key untuk ambil nilai
            $val   = (string) (ctx_get_scalar($ctx, $canon) ?? '<'.$raw.'>');
            $pattern = '/\{\{\s*' . preg_quote($raw, '/') . '\s*\}\}/';
            $filled  = preg_replace($pattern, $val, $filled);
        }

        return $filled;
    }

    // Auto-open modal saat error validasi
    $openCreate = $errors->any() && old('_form') === 'create';
    $openEditId = $errors->any() && old('_form') === 'edit' ? (int) old('_id') : null;

    // Variabel panduan (hanya canonical)
    $availableVars = $allowedCanonical;
@endphp

<div class="container mx-auto px-6 py-8">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><strong>Error!</strong> Mohon periksa kembali form Anda.</span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">WhatsApp Templates</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage message templates for automation.</p>
        </div>
        <label for="create_template_modal" class="btn btn-primary">Add New Template</label>
    </div>

    {{-- Filters --}}
    <div class="mt-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        <form action="{{ route('whatsapp.templates.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="q" value="{{ request('q', $q ?? '') }}" placeholder="Search by name, body, or variable..." class="input input-bordered w-full">
            <select name="status" class="select select-bordered w-full">
                @php $statusValue = $status ?? request('status', 'all'); @endphp
                <option value="all" {{ $statusValue === 'all' ? 'selected' : '' }}>All status</option>
                <option value="active" {{ $statusValue === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $statusValue === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-secondary w-full md:w-auto">Apply</button>
                <a href="{{ route('whatsapp.templates.index') }}" class="btn w-full md:w-auto">Reset</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="mt-6 overflow-x-auto">
        <div class="inline-block min-w-full shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Name</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Body</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Variables</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 dark:border-gray-600"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @forelse ($templates as $template)
                        @php
                            // gunakan kolom variables; bila kosong, ekstrak dari body
                            $varsRaw = is_array($template->variables) && count($template->variables) > 0
                                ? $template->variables
                                : [];

                            $filledPreview = renderFilled($template->body, $varsRaw, $previewContext ?? []);
                        @endphp
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $template->name }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="truncate block max-w-[520px] text-gray-700 dark:text-gray-300">{{ $template->body }}</span>
                                    <label for="preview_template_{{ $template->id }}" class="btn btn-ghost btn-xs" title="Preview" aria-label="Preview">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </label>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                @php
                                    // tampilkan hanya variabel yang diizinkan (canonical list)
                                    $detected = count($varsRaw) ? $varsRaw : extractVarsFromBody($template->body);
                                    $displayAllowed = [];
                                    foreach ($detected as $v) {
                                        $canon = canonicalizeVar($v);
                                        if ($canon && !in_array($canon, $displayAllowed, true)) {
                                            $displayAllowed[] = $canon;
                                        }
                                    }
                                @endphp
                                @if (!empty($displayAllowed))
                                    @foreach ($displayAllowed as $canon)
                                        <span class="badge badge-ghost mr-2">{{ '{' . '{' . $canon . '}' . '}' }}</span>
                                    @endforeach
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                @if ($template->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm text-right">
                                <div class="flex items-center gap-3 justify-end">
                                    <label for="edit_template_{{ $template->id }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 cursor-pointer">Edit</label>

                                    <form action="{{ route('whatsapp.templates.destroy', ['whatsapp_template' => $template->id]) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('Delete this template?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- PREVIEW modal --}}
                        <input type="checkbox" id="preview_template_{{ $template->id }}" class="modal-toggle" />
                        <div class="modal">
                            <div class="modal-box w-11/12 max-w-2xl">
                                <label for="preview_template_{{ $template->id }}" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</label>
                                <h3 class="font-bold text-lg">Preview: {{ $template->name }}</h3>

                                <div class="mt-4 grid gap-3">
                                    <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-900 dark:text-emerald-100">
                                        <div class="text-xs opacity-70 mb-1">Filled (variables → teks asli)</div>
                                        <div class="whitespace-pre-wrap break-words">{{ $filledPreview }}</div>
                                    </div>
                                    <div class="p-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100">
                                        <div class="text-xs opacity-70 mb-1">Raw</div>
                                        <div class="whitespace-pre-wrap break-words">{{ $template->body }}</div>
                                    </div>
                                </div>

                                <div class="modal-action">
                                    <label for="preview_template_{{ $template->id }}" class="btn">Close</label>
                                </div>
                            </div>
                            <label class="modal-backdrop" for="preview_template_{{ $template->id }}">Close</label>
                        </div>

                        {{-- EDIT modal --}}
                        @php
                            $shouldOpen = $openEditId !== null && $openEditId === (int)$template->id;
                        @endphp
                        <input type="checkbox" id="edit_template_{{ $template->id }}" class="modal-toggle" {{ $shouldOpen ? 'checked' : '' }} />
                        <div class="modal">
                            <div class="modal-box w-11/12 max-w-2xl">
                                <form action="{{ route('whatsapp.templates.update', ['whatsapp_template' => $template->id]) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="_form" value="edit">
                                    <input type="hidden" name="_id" value="{{ $template->id }}">

                                    <label for="edit_template_{{ $template->id }}" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</label>
                                    <h3 class="font-bold text-lg">Edit Template</h3>

                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label class="label"><span class="label-text">Name</span></label>
                                            <input
                                                type="text"
                                                name="name"
                                                value="{{ old('_form')==='edit' && (int)old('_id')===(int)$template->id ? old('name', $template->name) : $template->name }}"
                                                class="input input-bordered w-full" required />
                                            @error('name')
                                                @if($shouldOpen)
                                                    <div class="text-error text-xs mt-1">{{ $message }}</div>
                                                @endif
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="label"><span class="label-text">Body</span></label>
                                            <textarea name="body" rows="5" class="textarea textarea-bordered w-full" required>{{ old('_form')==='edit' && (int)old('_id')===(int)$template->id ? old('body', $template->body) : $template->body }}</textarea>
                                            @error('body')
                                                @if($shouldOpen)
                                                    <div class="text-error text-xs mt-1">{{ $message }}</div>
                                                @endif
                                            @enderror

                                            <div class="text-xs text-gray-500 mt-2">
                                                <div class="font-semibold mb-1">Variabel yang didukung:</div>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($availableVars as $k)
                                                        <span class="badge badge-ghost">{{ '{' . '{' . $k . '}' . '}' }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-control">
                                            <label class="label cursor-pointer justify-start gap-3">
                                                <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary"
                                                    @checked(old('_form')==='edit' && (int)old('_id')===(int)$template->id ? old('is_active', $template->is_active) : $template->is_active) />
                                                <span class="label-text">Active</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="modal-action">
                                        <label for="edit_template_{{ $template->id }}" class="btn btn-ghost">Cancel</label>
                                        <button type="submit" class="btn btn-primary">Update Template</button>
                                    </div>
                                </form>
                            </div>
                            <label class="modal-backdrop" for="edit_template_{{ $template->id }}">Close</label>
                        </div>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center text-gray-500 dark:text-gray-400">
                                No templates found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col sm:flex-row items-center justify-between">
                <div></div>
                <div class="mt-4 sm:mt-0">
                    {{ $templates->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CREATE modal --}}
<input type="checkbox" id="create_template_modal" class="modal-toggle" {{ $openCreate ? 'checked' : '' }} />
<div class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('whatsapp.templates.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="create">

            <label for="create_template_modal" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</label>
            <h3 class="font-bold text-lg">Create New Template</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="label"><span class="label-text">Name</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered w-full" required />
                    @error('name') <div class="text-error text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="label"><span class="label-text">Body</span></label>
                    <textarea name="body" rows="5" class="textarea textarea-bordered w-full" required>{{ old('body') }}</textarea>
                    @error('body') <div class="text-error text-xs mt-1">{{ $message }}</div> @enderror

                    <div class="text-xs text-gray-500 mt-3 space-y-2">
                        <div class="font-semibold">Variabel yang didukung:</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($availableVars as $k)
                                <span class="badge">{{ '{' . '{' . $k . '}' . '}' }}</span>
                            @endforeach
                        </div>
                        <p class="opacity-70">Kamu juga bisa menulis alias seperti <code>&#123;&#123;nama_toko&#125;&#125;</code> atau <code>&#123;&#123;tanggal_habis&#125;&#125;</code>—sistem akan memetakan otomatis.</p>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary" @checked(old('is_active', true)) />
                        <span class="label-text">Active</span>
                    </label>
                </div>
            </div>

            <div class="modal-action">
                <label for="create_template_modal" class="btn btn-ghost">Cancel</label>
                <button type="submit" class="btn btn-primary">Create Template</button>
            </div>
        </form>
    </div>
    <label class="modal-backdrop" for="create_template_modal">Close</label>
</div>
@endsection
