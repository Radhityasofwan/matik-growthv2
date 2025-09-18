<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Campaign - Matik Growth Hub</title>
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
                <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Create New Campaign</h3>
                <div class="mt-8 bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md">
                    @if ($errors->any())
                        <div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('campaigns.store') }}" method="POST">
                        @csrf
                        @include('campaigns._form', ['campaign' => new \App\Models\Campaign()])
                        <div class="flex justify-end mt-6">
                            <a href="{{ route('campaigns.index') }}" class="px-4 py-2 mr-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</a>
                            <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">Create Campaign</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
