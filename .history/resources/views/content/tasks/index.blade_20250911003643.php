@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
<div class="container mx-auto px-6 py-8">

    <!-- Alerts -->
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
            <span><strong>Terdapat kesalahan!</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></span>
        </div>
    </div>
    @endif

    <!-- Header & Filters -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-medium">Tasks</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola tugas tim Anda dengan Kanban Board.</p>
        </div>
        <a href="#create_task_modal" class="btn btn-primary mt-4 sm:mt-0">Buat Tugas Baru</a>
    </div>

    <!-- Filters -->
    <form action="{{ route('tasks.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-base-200 p-4 rounded-lg">
        <select name="assignee_id" class="select select-bordered w-full">
            <option value="">Semua PIC</option>
            @foreach($users as $user)
            <option value="{{ $user->id }}" @selected(request('assignee_id') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="priority" class="select select-bordered w-full">
            <option value="">Semua Prioritas</option>
            <option value="low" @selected(request('priority') == 'low')>Low</option>
            <option value="medium" @selected(request('priority') == 'medium')>Medium</option>
            <option value="high" @selected(request('priority') == 'high')>High</option>
            <option value="urgent" @selected(request('priority') == 'urgent')>Urgent</option>
        </select>
        <button type="submit" class="btn btn-secondary w-full">Filter</button>
    </form>

    <!-- Kanban Board -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach (['open' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done'] as $status => $title)
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="font-semibold text-lg mb-4 text-gray-800 dark:text-gray-200">{{ $title }} ({{ $tasks[$status]->count() }})</h4>
            <div id="{{ $status }}" class="min-h-[60vh] space-y-4 kanban-column" data-status="{{ $status }}">
                @foreach ($tasks[$status] as $task)
                <div id="task-{{ $task->id }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg shadow cursor-grab active:cursor-grabbing">
                    <div class="flex justify-between items-start">
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $task->title }}</p>
                        <span class="badge text-xs @switch($task->priority) @case('low') badge-info @break @case('medium') badge-success @break @case('high') badge-warning @break @case('urgent') badge-error @break @endswitch">{{ ucfirst($task->priority) }}</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-2">{{ $task->description }}</p>
                    <div class="mt-4 flex justify-between items-center text-sm">
                        <div class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <span @if($task->due_date && $task->due_date->isPast()) class="text-error font-semibold" @endif>{{ $task->due_date?->format('d M Y') ?? 'No due date' }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                             @if($task->assignee)
                            <div class="tooltip" data-tip="{{ $task->assignee->name }}">
                                <div class="avatar">
                                    <div class="w-6 rounded-full">
                                        <img src="https://i.pravatar.cc/40?u={{ $task->assignee->email }}" />
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn btn-ghost btn-xs">...</label>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-32 z-10">
                                    <li><a onclick="openEditModal({{ $task->id }})">Edit</a></li>
                                    <li>
                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Anda yakin?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="w-full text-left text-error p-2 hover:bg-base-200 rounded-lg">Hapus</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modals -->
@include('content.tasks.partials.modals')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@include('content.tasks.partials.scripts')
@endpush
