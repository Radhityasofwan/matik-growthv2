@extends('layouts.app')

@section('title', 'WA Templates - Matik Growth Hub')

@section('content')
@php
    /**
     * Helper: ambil nilai nested dari array context dengan key dot-notation.
     * Mendukung var dengan '-' atau '_' (dicoba keduanya).
     */
    function ctx_get($ctx, $key) {
        if (!is_array($ctx) || $key === null || $key === '') return null;

        $candidates = [$key];
        if (str_contains($key, '-')) {
            $candidates[] = str_replace('-', '_', $key);
        }
        if (str_contains($key, '_')) {
            $candidates[] = str_replace('_', '-', $key);
        }

        foreach ($candidates as $cand) {
            $segments = explode('.', $cand);
            $cur = $ctx;
            $ok = true;
            foreach ($segments as $seg) {
                if (is_array($cur) && array_key_exists($seg, $cur)) {
                    $cur = $cur[$seg];
                    continue;
                }
                $alt1 = str_replace('-', '_', $seg);
                $alt2 = str_replace('_', '-', $seg);
                if (is_array($cur) && array_key_exists($alt1, $cur)) {
                    $cur = $cur[$alt1]; continue;
                }
                if (is_array($cur) && array_key_exists($alt2, $cur)) {
                    $cur = $cur[$alt2]; continue;
                }
                $ok = false; break;
            }
            if ($ok) return $cur;
        }
        return null;
    }

    /**
     * Resolve nilai variabel dari $previewContext:
     * - Hanya menerima nilai SKALAR. Jika array/object → anggap tidak valid.
     * - Coba exact match; jika tidak ada, coba sinonim; jika tidak ada juga → fallback "<var>".
     */
    function resolveVar($key, $ctx) {
        // 1) exact dot-notation (dengan variasi -/_)
        $val = ctx_get($ctx, $key);
        if (!is_null($val) && !is_array($val) && !is_object($val) && $val !== '') {
            return (string) $val;
        }

        // 2) sinonim umum
        $base = strtolower($key);
        $synCandidates = [];
        if (in_array($base, ['name', 'full_name'])) {
            $synCandidates = ['lead.name', 'user.name', 'company.name', 'app.name'];
        } elseif (in_array($base, ['first-name', 'firstname', 'first_name'])) {
            $synCandidates = ['lead.first_name'];
        } elseif (in_array($base, ['last-name', 'lastname', 'last_name'])) {
            $synCandidates = ['lead.last_name'];
        } elseif (in_array($base, ['email'])) {
            $synCandidates = ['lead.email', 'user.email', 'company.email'];
        } elseif (in_array($base, ['phone', 'tel', 'whatsapp'])) {
            $synCandidates = ['lead.phone', 'user.phone', 'company.phone'];
        } elseif (in_array($base, ['company', 'company-name'])) {
            $synCandidates = ['company.name'];
        } elseif (in_array($base, ['app', 'app-name'])) {
            $synCandidates = ['app.name'];
        } elseif (in_array($base, ['date', 'today'])) {
            $synCandidates = ['date.today'];
        } elseif ($base === 'now' || $base === 'datetime') {
            $synCandidates = ['date.now'];
        } elseif ($base === 'amount') {
            $synCandidates = ['subscription.amount'];
        } elseif ($base === 'plan') {
            $synCandidates = ['subscription.plan'];
        } elseif (in_array($base, ['cycle', 'billing-cycle'])) {
            $synCandidates = ['subscription.cycle'];
        } elseif (in_array($base, ['end_date', 'end-date', 'expired_at', 'expiry_date'])) {
            $synCandidates = ['subscription.end_date'];
        }

        foreach ($synCandidates as $cand) {
            $v = ctx_get($ctx, $cand);
            if (!is_null($v) && !is_array($v) && !is_object($v) && $v !== '') {
                return (string) $v;
            }
        }

        // 3) fallback jelas
        return '<' . $key . '>';
    }

    /**
     * Ekstrak variabel dari body (untuk data lama ketika kolom `variables` kosong).
     */
    function extractVarsFromBody($text) {
        $vars = [];
        if (preg_match_all('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', (string)$text, $m)) {
            $vars = array_values(array_unique($m[1] ?? []));
        }
        return $vars;
    }

    /**
     * Ganti semua {{var}} di $text berdasarkan array $vars & context DB.
     * Selalu pastikan replacement adalah string.
     */
    function renderFilled($text, $vars, $ctx) {
        $filled = (string) $text;

        // Jika kolom variables kosong (data lama), ekstrak dari body
        if (!is_array($vars) || count($vars) === 0) {
            $vars = extractVarsFromBody($filled);
        }

        // normalisasi vars jika ada yang tersimpan dengan {{ }}
        $vars = array_map(fn($v) => preg_replace('/^\s*\{\{\s*|\s*\}\}\s*$/', '', (string)$v), $vars);
        $vars = array_values(array_unique(array_filter($vars, fn($x) => $x !== '')));

        foreach ($vars as $v) {
            $val = (string) resolveVar($v, $ctx); // PASTIKAN STRING
            $pattern = '/\{\{\s*' . preg_quote($v, '/') . '\s*\}\}/';
            $filled = preg_replace($pattern, $val, $filled);
        }
        return $filled;
    }

    /**
     * Flatten key context (untuk menampilkan daftar variabel yang tersedia).
     */
    function flatten_keys($arr, $prefix = '') {
        $out = [];
        foreach ($arr as $k => $v) {
            $key = $prefix === '' ? $k : ($prefix . '.' . $k);
            if (is_array($v)) {
                $out = array_merge($out, flatten_keys($v, $key));
            } else {
                $out[] = $key;
            }
        }
        return $out;
    }

    // Auto-open modal saat error validasi
    $openCreate = $errors->any() && old('_form') === 'create';
    $openEditId = $errors->any() && old('_form') === 'edit' ? (int) old('_id') : null;

    // Daftar variabel dari DB context untuk panduan
    $availableVars = isset($previewContext) && is_array($previewContext) ? flatten_keys($previewContext) : [];
    $popularSynonyms = ['name','first-name','last-name','email','phone','date','now','plan','amount','cycle','end_date'];
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
                            $normVars = $template->normalizedVariables();
                            $filledPreview = renderFilled($template->body, $normVars, $previewContext ?? []);
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
                                    // Jika kolom variables kosong, tampilkan hasil ekstraksi dari body agar user tetap lihat daftar
                                    $varsForDisplay = (is_array($normVars) && count($normVars)>0) ? $normVars : extractVarsFromBody($template->body);
                                @endphp
                                @if (!empty($varsForDisplay))
                                    @foreach ($varsForDisplay as $var)
                                        <span class="badge badge-ghost mr-2">{{ '{' . '{' . $var . '}' . '}' }}</span>
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
                        @php $shouldOpen = $openEditId !== null && $openEditId === (int)$template->id; @endphp
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
                                                Variabel terdeteksi:
                                                @php
                                                    $varsDisplayEdit = (is_array($normVars) && count($normVars)>0) ? $normVars : extractVarsFromBody($template->body);
                                                @endphp
                                                @if (!empty($varsDisplayEdit))
                                                    @foreach ($varsDisplayEdit as $v)
                                                        <span class="badge badge-ghost mr-1">{{ '{' . '{' . $v . '}' . '}' }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="opacity-60">tidak ada</span>
                                                @endif
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
                        <div class="font-semibold">Panduan variabel:</div>
                        <p>Gunakan placeholder seperti <code>&#123;&#123;lead.name&#125;&#125;</code>, <code>&#123;&#123;user.email&#125;&#125;</code>, <code>&#123;&#123;subscription.plan&#125;&#125;</code>, <code>&#123;&#123;date.today&#125;&#125;</code>, dll.</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($availableVars as $k)
                                <span class="badge">{{ '{' . '{' . $k . '}' . '}' }}</span>
                            @endforeach
                            @foreach ($popularSynonyms as $k)
                                <span class="badge badge-ghost">{{ '{' . '{' . $k . '}' . '}' }}</span>
                            @endforeach
                        </div>
                        <p class="opacity-70">Tips: gunakan <code>lead.first-name</code> / <code>lead.first_name</code> untuk nama depan; sistem otomatis memahami tanda <code>-</code> dan <code>_</code>.</p>
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
