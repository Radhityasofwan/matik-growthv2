<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Task Board - Matik Growth Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sortable-ghost {
            background: #d1d5db;
            opacity: 0.5;
        }
        .task-card {
            cursor: grab;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
<div class="flex h-screen" x-data="{ isModalOpen: false, modalData: {}, isEdit: false }">
    @include('partials.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden">
        @include('partials.navbar')
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
            <div class="container mx-auto px-6 py-8">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Task Board</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Drag and drop to manage tasks.</p>
                    </div>
                    <button @click="isModalOpen = true; isEdit = false; modalData = {}" class="mt-4 sm:mt-0 text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Add New Task
                    </button>
                </div>

                @if (session('success'))
                    <div class="mt-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Task Board -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="task-board">
                    @foreach($tasks as $status => $taskList)
                    <div class="bg-gray-200 dark:bg-gray-800 rounded-lg p-4">
                        <h4 class="font-semibold text-lg text-gray-800 dark:text-white capitalize mb-4">{{ str_replace('_', ' ', $status) }}</h4>
                        <div id="status-{{$status}}" data-status="{{$status}}" class="space-y-4 min-h-[200px] task-list">
                            @foreach($taskList as $task)
                            <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow task-card" data-task-id="{{ $task->id }}"
                                 @click="isModalOpen = true; isEdit = true; modalData = {{ json_encode($task) }}">
                                <h5 class="font-bold dark:text-white">{{ $task->title }}</h5>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($task->description, 50) }}</p>
                                <div class="mt-3 flex justify-between items-center">
                                    <span class="text-xs text-gray-500 dark:text-gray-300">Due: {{ $task->due_date ? $task->due_date->format('d M') : 'N/A' }}</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        @switch($task->priority)
                                            @case('low') bg-blue-100 text-blue-800 @break
                                            @case('medium') bg-yellow-100 text-yellow-800 @break
                                            @case('high') bg-orange-100 text-orange-800 @break
                                            @case('urgent') bg-red-100 text-red-800 @break
                                        @endswitch">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </main>
    </div>

    <!-- Task Modal -->
    <div x-show="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="isModalOpen = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-8 w-full max-w-2xl" @click.away="isModalOpen = false">
            <h2 class="text-2xl font-bold mb-4 dark:text-white" x-text="isEdit ? 'Edit Task' : 'Create New Task'"></h2>
            <form :action="isEdit ? `/tasks/${modalData.id}` : '/tasks'" method="POST">
                @csrf
                <template x-if="isEdit">
                    @method('PUT')
                </template>

                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
                    <input type="text" name="title" id="title" :value="modalData.title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
                    <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white" x-text="modalData.description"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Priority</label>
                        <select name="priority" id="priority" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                            <option value="low" :selected="modalData.priority == 'low'">Low</option>
                            <option value="medium" :selected="modalData.priority == 'medium'">Medium</option>
                            <option value="high" :selected="modalData.priority == 'high'">High</option>
                            <option value="urgent" :selected="modalData.priority == 'urgent'">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Due Date</label>
                        <input type="date" name="due_date" id="due_date" :value="modalData.due_date ? modalData.due_date.substring(0, 10) : ''" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="assignee_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Assign To</label>
                    <select name="assignee_id" id="assignee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:text-white">
                        <option value="">Unassigned</option>
                        @foreach($users as $id => $name)
                        <option :value="{{ $id }}" :selected="modalData.assignee_id == {{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="button" @click="isModalOpen = false" class="px-4 py-2 mr-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700" x-text="isEdit ? 'Update Task' : 'Create Task'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const taskLists = document.querySelectorAll('.task-list');
    taskLists.forEach(list => {
        new Sortable(list, {
            group: 'shared',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                const itemEl = evt.item;
                const taskId = itemEl.dataset.taskId;
                const newStatus = evt.to.dataset.status;

                fetch(`/tasks/${taskId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Success:', data.message);
                    // Optional: show a success toast notification
                })
                .catch((error) => {
                    console.error('Error:', error);
                    // Optional: revert the drag and show an error
                });
            },
        });
    });
});
</script>

</body>
</html>
