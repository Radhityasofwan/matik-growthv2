@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
<style>
    /* Mengembalikan style board yang lebih berwarna */
    .board-surface {
        border-radius: 1rem;
        background: radial-gradient(42rem 24rem at -12% -10%, oklch(var(--p)/.08), transparent 60%),
                    radial-gradient(36rem 22rem at 112% 0%,  oklch(var(--a)/.08), transparent 62%),
                    linear-gradient(180deg, oklch(var(--b1)), oklch(var(--b1)));
    }
    .tone-primary { --tone: var(--p); }
    .tone-info { --tone: var(--in); }
    .tone-warning { --tone: var(--wa); }
    .tone-success { --tone: var(--su); }
    .tone-neutral { --tone: var(--n); }

    .board-col {
        border-radius: .9rem;
        background: linear-gradient(180deg, oklch(var(--tone)/.10), transparent 55%),
                    linear-gradient(180deg, oklch(var(--b2)), oklch(var(--b3)));
        border: 1px solid oklch(var(--b3));
        min-height: 70vh;
    }
    .task-card {
        border-top: 4px solid var(--card-color, oklch(var(--p)));
    }
    .flash-ring {
        box-shadow: 0 0 0 3px oklch(var(--su)/.55);
        transition: box-shadow .8s ease;
    }
</style>

@php
  // Konfigurasi Kolom
  $statusConfig = [
      'open' => ['title' => 'Not Started', 'color' => 'neutral', 'tone' => 'tone-neutral'],
      'in_progress' => ['title' => 'Doing', 'color' => 'info', 'tone' => 'tone-info'],
      'review' => ['title' => 'Paused', 'color' => 'warning', 'tone' => 'tone-warning'],
      'done' => ['title' => 'Done', 'color' => 'success', 'tone' => 'tone-success'],
  ];

  // Badge Prioritas
  function prioBadge($p){
      return match($p){ 
          'low'=>'badge-info', 'medium'=>'badge-success', 'high'=>'badge-warning', 'urgent'=>'badge-error', 
          default=>'badge-ghost' 
      };
  }

  // Konversi warna DB ke CSS
  function cssColor($val){
      $v = trim(strtolower((string)$val));
      if ($v === '') return 'oklch(var(--p))';
      $map = [
        'primary'=>'oklch(var(--p))','secondary'=>'oklch(var(--s))','accent'=>'oklch(var(--a))',
        'info'=>'oklch(var(--in))','success'=>'oklch(var(--su))','warning'=>'oklch(var(--wa))',
        'error'=>'oklch(var(--er))','neutral'=>'oklch(var(--n))',
      ];
      return $map[$v] ?? $v;
  }
@endphp

<div class="container mx-auto px-4 lg:px-6 py-8 board-surface">

  {{-- Header & CTA --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div class="space-y-1">
      <h1 class="text-3xl font-bold text-base-content">Papan Tugas</h1>
      <p class="text-base-content/70">Kelola alur kerja tim Anda secara visual.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('create_task_modal').showModal()">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
      Buat Tugas Baru
    </button>
  </div>

  {{-- Board --}}
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    @foreach ($statusConfig as $status => $config)
      <section class="board-col {{ $config['tone'] }} rounded-lg p-4">
        <header class="flex items-center gap-2 mb-4">
          <span class="w-2.5 h-2.5 rounded-full bg-{{ $config['color'] }}"></span>
          <h2 class="font-semibold text-base-content">{{ $config['title'] }}</h2>
          <span class="badge badge-ghost badge-sm">{{ $tasks[$status]->count() }}</span>
        </header>
        <div 
            id="{{ $status }}" 
            class="kanban-column space-y-3" 
            data-status="{{ $status }}"
        >
          @foreach ($tasks[$status] as $task)
            <article 
                id="task-{{ $task->id }}"
                class="card bg-base-100 shadow-md cursor-grab active:cursor-grabbing task-card relative"
                style="--card-color: {{ cssColor($task->color ?? '') }}; @if($task->is_pinned) order: -1; @endif"
            >
                @if($task->is_pinned)
                    <div class="tooltip tooltip-left absolute top-2 right-2" data-tip="Pinned">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
                           <path d="M5.5 3a.5.5 0 01.5.5V12a.5.5 0 01-1 0V3.5a.5.5 0 01.5-.5zM8 3a.5.5 0 01.5.5v9a.5.5 0 01-1 0V3.5A.5.5 0 018 3zM10.5 3a.5.5 0 01.5.5v9a.5.5 0 01-1 0V3.5a.5.5 0 01.5-.5zm2 0a.5.5 0 01.5.5v9a.5.5 0 01-1 0V3.5a.5.5 0 01.5-.5z" />
                           <path d="M2 2a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V2z" />
                        </svg>
                    </div>
                @endif

                <div class="card-body p-3">
                    @if($task->progress > 0)
                    <progress class="progress progress-primary w-full h-1 absolute top-0 left-0 rounded-t-box" value="{{ $task->progress }}" max="100"></progress>
                    @endif

                    <div class="flex justify-between items-start gap-2 pt-2">
                        <div class="flex items-center gap-2">
                            @if($task->icon)
                                <span class="text-lg">{{ $task->icon }}</span>
                            @endif
                            <p class="font-medium text-base-content leading-tight">{{ $task->title }}</p>
                        </div>
                        <div class="dropdown dropdown-end">
                            <label tabindex="0" class="btn btn-ghost btn-xs btn-circle -mr-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 10a2 2 0 100 4 2 2 0 000-4z" /></svg>
                            </label>
                            <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 z-20 border border-base-300/50">
                                <li><a onclick="togglePin({{ $task->id }})"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5v14l7-3 7 3V5l-7 3-7-3z" /></svg> {{$task->is_pinned ? 'Unpin Task' : 'Pin Task'}}</a></li>
                                <div class="divider my-1"></div>
                                <li><a onclick="openEditModal({{ $task->id }})"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z" /></svg> Edit Property</a></li>
                                <div class="divider my-1"></div>
                                <li><a onclick="copyLink({{ $task->id }})"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg> Copy Link</a></li>
                                <li><a onclick="duplicateTask({{ $task->id }})"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg> Duplicate</a></li>
                                <div class="divider my-1"></div>
                                <li><a onclick="deleteTask({{ $task->id }})" class="text-error"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg> Delete</a></li>
                                <div class="divider my-1"></div>
                                <li><a onclick="commentOnTask({{ $task->id }})"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg> Comment</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="badge {{ prioBadge($task->priority) }} badge-sm">{{ ucfirst($task->priority) }}</span>
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <div class="flex items-center gap-2 text-xs text-base-content/60">
                            @if($task->due_date)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <span @if($task->due_date->isPast() && $task->status !=='done') class="text-error font-semibold" @endif>
                                {{ $task->due_date->format('d M') }}
                            </span>
                            @endif
                        </div>
                        @if($task->assignee)
                            <div class="tooltip" data-tip="{{ $task->assignee->name }}">
                                <div class="avatar w-8 h-8">
                                    <div class="rounded-full ring ring-base-300 ring-offset-base-100 ring-offset-1">
                                    @if ($task->assignee->avatar)
                                        <img src="{{ asset('storage/' . $task->assignee->avatar) }}" alt="Avatar" />
                                    @else
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($task->assignee->name) }}&background=random&color=fff" alt="Avatar" />
                                    @endif
                                    </div>
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

{{-- Toast Container --}}
<div id="toast-container" class="toast toast-bottom toast-end z-50"></div>

@include('content.tasks.partials.modals')
@endsection

