@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
<style>
  /* ===== Desain Board Baru (sesuai referensi) ===== */
  .board-surface {
    background-color: oklch(var(--b2));
    padding: 1.5rem;
    border-radius: 1.25rem;
  }
  .board-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(280px, 1fr));
    gap: 1.5rem;
  }
  @media (max-width: 1024px) {
    .board-grid { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 1rem; }
    .board-col { flex: 0 0 280px; scroll-snap-align: start; }
  }
  .board-col { background-color: oklch(var(--b1)); border-radius: 0.75rem; padding: 0.75rem; }
  .board-col-title { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; font-weight: 600; color: oklch(var(--bc) / 0.8); }
  .title-dot { width: 0.5rem; height: 0.5rem; border-radius: 9999px; }
  .dot-open { background-color: oklch(var(--in)); }
  .dot-in_progress { background-color: oklch(var(--p)); }
  .dot-review { background-color: oklch(var(--wa)); }
  .dot-done { background-color: oklch(var(--su)); }
  .board-col-body { min-height: 60vh; }
  .task-card { background-color: oklch(var(--b1)); border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); border: 1px solid oklch(var(--b3)); transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out; }
  .task-card:hover { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
  .task-card.is-pinned { border-left: 4px solid oklch(var(--wa)); }
  .sortable-ghost { background-color: oklch(var(--b3)); opacity: 0.5; }
  .sortable-chosen { transform: scale(1.03); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
  .prio-badge { padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 500; border-radius: 9999px; }
  .prio-low { background-color: oklch(var(--in) / 0.1); color: oklch(var(--in)); }
  .prio-medium { background-color: oklch(var(--su) / 0.1); color: oklch(var(--su)); }
  .prio-high { background-color: oklch(var(--wa) / 0.1); color: oklch(var(--wa)); }
  .prio-urgent { background-color: oklch(var(--er) / 0.1); color: oklch(var(--er)); }
</style>

@php
  $statusConfig = [
    'open'        => ['title' => 'Not Started', 'dot' => 'dot-open'],
    'in_progress' => ['title' => 'Doing',       'dot' => 'dot-in_progress'],
    'review'      => ['title' => 'Paused',      'dot' => 'dot-review'],
    'done'        => ['title' => 'Done',        'dot' => 'dot-done'],
  ];
  function prioBadgeClass($p) { return match($p) { 'low'=>'prio-low', 'medium'=>'prio-medium', 'high'=>'prio-high', 'urgent'=>'prio-urgent', default=>'' }; }
@endphp

<div class="container mx-auto px-3 sm:px-4 lg:px-6 py-5 lg:py-8">
  {{-- Header & CTA --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 lg:mb-8">
    <div class="space-y-1">
      <h1 class="text-3xl sm:text-4xl font-extrabold text-base-content">Papan Tugas</h1>
      <p class="text-base-content/70">Kelola alur kerja tim Anda secara visual.</p>
    </div>
    <button type="button" class="btn btn-primary shadow" onclick="document.getElementById('create_task_modal').showModal()">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
      Buat Tugas Baru
    </button>
  </div>
  
  {{-- Board --}}
  <div class="board-surface">
    <div class="board-grid">
      @foreach ($statusConfig as $status => $config)
        <section class="board-col">
          <header class="board-col-title">
            <span class="title-dot {{ $config['dot'] }}"></span>
            <span>{{ $config['title'] }}</span>
            <span class="ml-auto text-sm font-normal text-base-content/50">{{ $tasks[$status]->count() }}</span>
          </header>

          <div id="{{ $status }}" class="kanban-column board-col-body space-y-3" data-status="{{ $status }}">
            @foreach ($tasks[$status] as $task)
              <article id="task-{{ $task->id }}" class="card task-card cursor-grab active:cursor-grabbing" :class="{ 'is-pinned': {{ $task->is_pinned ? 'true' : 'false' }} }">
                <div class="card-body p-4 space-y-2">
                  <div class="flex justify-between items-start gap-2">
                    <p class="font-semibold text-base-content leading-tight flex-1" @click="openEditModal({{ $task->id }})">{{ $task->title }}</p>
                    
                    {{-- Dropdown Aksi --}}
                    <div class="dropdown dropdown-end">
                      <label tabindex="0" class="btn btn-ghost btn-xs btn-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2z" /></svg>
                      </label>
                      <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-10 border border-base-300/50">
                        <li><a @click.prevent="togglePin({{ $task->id }})">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.052A2 2 0 0113 7v6a2 2 0 004 0V7a2 2 0 012-2.052M12 21a2 2 0 002-2v-6a2 2 0 00-4 0v6a2 2 0 002 2z" /></svg>
                          <span x-text="document.getElementById('task-{{ $task->id }}').classList.contains('is-pinned') ? 'Unpin Task' : 'Pin Task'"></span>
                        </a></li>
                        <li><a @click="openEditModal({{ $task->id }})"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z" /></svg>Edit Property</a></li>
                        <div class="divider my-1"></div>
                        <li><a @click.prevent="copyLink({{ $task->id }})"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>Copy Link</a></li>
                        <li><form method="POST" action="{{ route('tasks.duplicate', $task) }}" onsubmit="return confirm('Duplikasi tugas ini?')">@csrf<button type="submit" class="w-full text-left"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>Duplicate</button></form></li>
                        <div class="divider my-1"></div>
                        <li><form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Anda yakin ingin menghapus tugas ini?')">@csrf @method('DELETE')<button type="submit" class="w-full text-left text-error"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>Delete</button></form></li>
                      </ul>
                    </div>
                  </div>

                  @if($task->progress > 0)
                  <progress class="progress progress-primary w-full" value="{{ $task->progress }}" max="100"></progress>
                  @endif
                  
                  @if($task->priority)
                    <div><span class="prio-badge {{ prioBadgeClass($task->priority) }}">{{ ucfirst($task->priority) }}</span></div>
                  @endif

                  <div class="flex items-center justify-between text-sm text-base-content/60">
                    @if($task->due_date)<span>{{ $task->due_date->format('d M') }}</span>@else<span></span>@endif
                    @if($task->assignee)
                      <div class="tooltip" data-tip="{{ $task->assignee->name }}">
                        <div class="avatar w-6 h-6">
                            @if ($task->assignee->avatar)<img src="{{ asset('storage/' . $task->assignee->avatar) }}" alt="{{ $task->assignee->name }}" class="rounded-full" />
                            @else<img src="https://ui-avatars.com/api/?name={{ urlencode($task->assignee->name) }}&background=random&color=fff&size=24" alt="{{ $task->assignee->name }}" class="rounded-full" />@endif
                        </div>
                      </div>
                    @endif
                  </div>
                </div>
              </article>
            @endforeach
          </div>
        </section>
      @endforeach
    </div>
  </div>
</div>

@include('tasks.partials.modals')
@endsection

