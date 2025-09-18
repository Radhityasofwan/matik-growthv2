@extends('layouts.app')

@section('title', 'WA Templates - Matik Growth Hub')

@section('content')
@php
    // nilai contoh untuk preview "FILLED"
    $sampleMap = [
        'name'       => 'Budi',
        'username'   => 'budi01',
        'email'      => 'budi@example.com',
        'date'       => '12 Sep 2025',
        'first-name' => 'Budi',
    ];
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
        <a href="#create_template_modal" class="btn btn-primary">Add New Template</a>
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
                            // build "FILLED" preview secara server-side, sesuai regex controller
                            $filled = (string) $template->body;
                            foreach (($template->variables ?? []) as $v) {
                                $val = $sampleMap[$v] ?? ('<' . $v . '>');
                                $pattern = '/\{\{\s*' . preg_quote($v, '/') . '\s*\}\}/';
                                $filled = preg_replace($pattern, $val, $filled);
                            }
                        @endphp
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $template->name }}</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="truncate block max-w-[520px] text-gray-700 dark:text-gray-300">{{ $template->body }}</span>
                                    <a href="#preview_template_{{ $template->id }}" class="link link-primary text-xs">Preview</a>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 text-sm">
                                @if (!empty($template->variables))
                                    @foreach ($template->variables as $var)
                                        <span class="badge badge-ghost mr-2">
                                            {{ '{' . '{' . $var . '}' . '}' }}
                                        </span>
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
                                    <a href="#edit_template_{{ $template->id }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</a>
                                    <form action="{{ route('whatsapp.templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this template?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Preview Modal (per item) --}}
                        <div id="preview_template_{{ $template->id }}" class="modal">
                            <div class="modal-box w-11/12 max-w-2xl">
                                <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                                <h3 class="font-bold text-lg">Preview: {{ $template->name }}</h3>
                                <div class="mt-4">
                                    <div class="tabs">
                                        <a class="tab tab-bordered tab-active">Filled</a>
                                        <a class="tab tab-bordered">Raw</a>
                                    </div>
                                    <div class="mt-4 grid gap-3">
                                        <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-900 dark:text-emerald-100">
                                            <div class="text-xs opacity-70 mb-1">Filled</div>
                                            <div class="whitespace-pre-wrap break-words">{{ $filled }}</div>
                                        </div>
                                        <div class="p-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100">
                                            <div class="text-xs opacity-70 mb-1">Raw</div>
                                            <div class="whitespace-pre-wrap break-words">{{ $template->body }}</div>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Variables example: <code>&#123;&#123;name&#125;&#125;</code>
                                        </p>
                                    </div>
                                </div>
                                <div class="modal-action">
                                    <a href="#" class="btn">Close</a>
                                </div>
                            </div>
                            <a href="#" class="modal-backdrop">Close</a>
                        </div>

                        {{-- Edit Modal (per item) --}}
                        <div id="edit_template_{{ $template->id }}" class="modal">
                            <div class="modal-box w-11/12 max-w-2xl">
                                <form action="{{ route('whatsapp.templates.update', $template) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
                                    <h3 class="font-bold text-lg">Edit Template</h3>

                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label class="label"><span class="label-text">Name</span></label>
                                            <input type="text" name="name" value="{{ old('name', $template->name) }}" class="input input-bordered w-full" required />
                                        </div>
                                        <div>
                                            <label class="label"><span class="label-text">Body</span></label>
                                            <textarea name="body" rows="5" class="textarea textarea-bordered w-full" required>{{ old('body', $template->body) }}</textarea>
                                            <div class="text-xs text-gray-500 mt-1">Use placeholders like <code>&#123;&#123;name&#125;&#125;</code>.</div>
                                        </div>
                                        <div class="form-control">
                                            <label class="label cursor-pointer justify-start gap-3">
                                                <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary" @checked(old('is_active', $template->is_active)) />
                                                <span class="label-text">Active</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="modal-action">
                                        <a href="#" class="btn btn-ghost">Cancel</a>
                                        <button type="submit" class="btn btn-primary">Update Template</button>
                                    </div>
                                </form>
                            </div>
                            <a href="#" class="modal-backdrop">Close</a>
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

            {{-- Footer: pagination --}}
            <div class="px-5 py-5 bg-white dark:bg-gray-800 border-t flex flex-col sm:flex-row items-center justify-between">
                <div></div>
                <div class="mt-4 sm:mt-0">
                    {{ $templates->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Modal (single) --}}
<div id="create_template_modal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl">
        <form action="{{ route('whatsapp.templates.store') }}" method="POST">
            @csrf
            <a href="#" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</a>
            <h3 class="font-bold text-lg">Create New Template</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="label"><span class="label-text">Name</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label"><span class="label-text">Body</span></label>
                    <textarea name="body" rows="5" class="textarea textarea-bordered w-full" required>{{ old('body') }}</textarea>
                    <div class="text-xs text-gray-500 mt-1">Use placeholders like <code>&#123;&#123;name&#125;&#125;</code>.</div>
                </div>
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="is_active" value="1" class="toggle toggle-primary" @checked(old('is_active', true)) />
                        <span class="label-text">Active</span>
                    </label>
                </div>
            </div>

            <div class="modal-action">
                <a href="#" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Template</button>
            </div>
        </form>
    </div>
    <a href="#" class="modal-backdrop">Close</a>
</div>
@endsection
