<!DOCTYPE html>
<html lang="en" x-data="{ previewOpen: false, previewText: '', previewVars: [] }" x-cloak>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Templates - Matik Growth Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } [x-cloak]{ display:none !important; } </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
<div class="flex h-screen">
    @include('partials.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden">
        @include('partials.navbar')
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
            <div class="container mx-auto px-6 py-8">

                <div class="sm:flex sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">WhatsApp Templates</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage message templates for automation.</p>
                    </div>
                    <a href="{{ route('whatsapp.templates.create') }}" class="mt-4 sm:mt-0 text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Add New Template
                    </a>
                </div>

                @if (session('success'))
                    <div class="mt-4 p-4 text-sm text-green-800 bg-green-100 dark:bg-green-900/40 dark:text-green-200 rounded-lg border border-green-200 dark:border-green-800" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="GET" action="{{ route('whatsapp.templates.index') }}" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="q" class="sr-only">Search</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="{{ $q ?? request('q') }}"
                            placeholder="Search by name, body, or variable…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label for="status" class="sr-only">Status</label>
                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            @php $statusValue = $status ?? request('status', 'all'); @endphp
                            <option value="all" {{ $statusValue === 'all' ? 'selected' : '' }}>All status</option>
                            <option value="active" {{ $statusValue === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $statusValue === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="w-full md:w-auto text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5">
                            Apply
                        </button>
                        <a href="{{ route('whatsapp.templates.index') }}" class="w-full md:w-auto text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Reset
                        </a>
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto">
                    <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 dark:border-gray-700">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 border-b text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 border-b text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Body</th>
                                    <th class="px-6 py-3 border-b text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variables</th>
                                    <th class="px-6 py-3 border-b text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 border-b"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800">
                                @forelse($templates as $template)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-6 py-4 whitespace-no-wrap border-b dark:border-gray-700 text-sm leading-5 text-gray-900 dark:text-white font-medium">
                                        {{ $template->name }}
                                    </td>
                                    <td class="px-6 py-4 border-b dark:border-gray-700 text-sm leading-5 text-gray-500 dark:text-gray-300 max-w-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate block">{{ $template->body }}</span>
                                            <button
                                                type="button"
                                                class="shrink-0 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs underline"
                                                @click='previewText = @json($template->body ?? ""); previewVars = @json($template->variables ?? []); previewOpen = true'
                                            >
                                                Preview
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b dark:border-gray-700 text-sm leading-5 text-gray-500 dark:text-gray-300">
                                        @if(!empty($template->variables))
                                            @foreach($template->variables as $var)
                                                <span class="inline-block bg-gray-200 dark:bg-gray-600 rounded-full px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200 mr-2">
                                                    {{ '{' . '{' . $var . '}' . '}' }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b dark:border-gray-700 text-sm leading-5">
                                        @if($template->is_active)
                                            <span class="inline-flex items-center gap-1 text-green-700 bg-green-100 dark:text-green-200 dark:bg-green-900/40 px-2 py-0.5 rounded-full text-xs font-semibold">Active</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-gray-700 bg-gray-200 dark:text-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full text-xs font-semibold">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right border-b dark:border-gray-700 text-sm leading-5 font-medium">
                                        <a href="{{ route('whatsapp.templates.edit', $template) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</a>
                                        <form action="{{ route('whatsapp.templates.destroy', $template) }}" method="POST" class="inline-block ml-3" onsubmit="return confirm('Delete this template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No templates found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6">
                    {{ $templates->links() }}
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Preview Modal -->
<div
    x-show="previewOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="background: rgba(0,0,0,0.5);"
    x-transition
    @keydown.escape.window="previewOpen = false"
>
    <div class="w-full max-w-lg rounded-xl bg-white dark:bg-gray-800 shadow-xl overflow-hidden"
         x-data="{
            mode: 'filled',
            sampleMap() {
                // nilai contoh untuk penggantian; boleh kamu sesuaikan
                return {
                    name: 'Budi',
                    username: 'budi01',
                    email: 'budi@example.com',
                    date: '12 Sep 2025',
                    'first-name': 'Budi',
                };
            },
            fill(text, vars) {
                if (!text) return '';
                const baseMap = this.sampleMap();
                let out = String(text);
                (vars || []).forEach(v => {
                    const key = String(v);
                    const val = baseMap[key] ?? ('<' + key + '>');
                    // regex: {{   key   }} (dengan spasi optional)
                    const pattern = new RegExp('\\{\\{\\s*' + key.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\$&') + '\\s*\\}\\}', 'g');
                    out = out.replace(pattern, val);
                });
                return out;
            }
         }"
    >
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h4 class="text-gray-800 dark:text-gray-100 font-semibold">Preview</h4>
            <div class="flex items-center gap-2 text-sm">
                <button class="px-3 py-1 rounded-md"
                        :class="mode === 'raw' ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300'"
                        @click="mode = 'raw'">Raw</button>
                <button class="px-3 py-1 rounded-md"
                        :class="mode === 'filled' ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300'"
                        @click="mode = 'filled'">Filled</button>
            </div>
            <button class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" @click="previewOpen = false">✕</button>
        </div>
        <div class="p-5">
            <div class="space-y-2">
                <div class="max-w-[80%] rounded-2xl px-4 py-2 bg-gray-200 text-gray-800">Hi there! 👋</div>
                <div class="max-w-[80%] rounded-2xl px-4 py-2 bg-emerald-500 text-white ml-auto"
                     x-text="mode === 'raw' ? previewText : fill(previewText, previewVars)"></div>
            </div>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                Variables (e.g., <code>&#123;&#123;name&#125;&#125;</code>) will be replaced when sending.
            </p>
        </div>
        <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <button class="text-white bg-blue-600 hover:bg-blue-700 rounded-lg text-sm px-4 py-2" @click="previewOpen = false">Close</button>
        </div>
    </div>
</div>

</body>
</html>
