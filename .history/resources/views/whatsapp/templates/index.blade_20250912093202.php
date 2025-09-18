<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Templates - Matik Growth Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
<div class="flex h-screen">
    @include('partials.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden">
        @include('partials.navbar')
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
            <div class="container mx-auto px-6 py-8">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">WhatsApp Templates</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage message templates for automation.</p>
                    </div>
                    <a href="{{ route('whatsapp.templates.create') }}" class="mt-4 sm:mt-0 text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Add New Template
                    </a>
                </div>

                @if (session('success'))
                    <div class="mt-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mt-8 overflow-x-auto">
                    <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 dark:border-gray-700">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 border-b text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 border-b text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Body</th>
                                    <th class="px-6 py-3 border-b text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variables</th>
                                    <th class="px-6 py-3 border-b"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800">
                                @forelse($templates as $template)
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b dark:border-gray-700 text-sm leading-5 text-gray-900 dark:text-white">{{ $template->name }}</td>
                                    <td class="px-6 py-4 border-b dark:border-gray-700 text-sm leading-5 text-gray-500 dark:text-gray-400 max-w-sm truncate">{{ $template->body }}</td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b dark:border-gray-700 text-sm leading-5 text-gray-500 dark:text-gray-400">
                                        @if($template->variables)
                                            @foreach($template->variables as $var)
                                                <span class="inline-block bg-gray-200 dark:bg-gray-600 rounded-full px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200 mr-2">{{ $var }}</span>
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right border-b dark:border-gray-700 text-sm leading-5 font-medium">
                                        <a href="{{ route('whatsapp.templates.edit', $template) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No templates found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
