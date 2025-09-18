@extends('layouts.app')

@section('title', 'Tasks - Matik Growth Hub')

@section('content')
<style>
  /* ===== Desain Board Baru (sesuai referensi) ===== */
  .board-surface {
    /* Menghapus latar belakang gradien kompleks */
    background-color: oklch(var(--b2)); /* Sedikit lebih gelap dari base-100 */
    padding: 1.5rem;
    border-radius: 1.25rem;
  }

  .board-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(280px, 1fr));
    gap: 1.5rem;
  }
  
  @media (max-width: 1024px) {
    .board-grid {
      display: flex;
      overflow-x: auto;
      scroll-snap-type: x mandatory;
      padding-bottom: 1rem; /* Ruang untuk scrollbar */
    }
    .board-col {
      flex: 0 0 280px; /* Lebar kolom di mobile */
      scroll-snap-align: start;
    }
  }

  .board-col {
    background-color: oklch(var(--b1)); /* Warna dasar card */
    border-radius: 0.75rem;
    padding: 0.75rem;
  }

  .board-col-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    font-weight: 600;
    color: oklch(var(--bc) / 0.8);
  }

  .title-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 9999px;
  }

  /* Warna dot sesuai status */
  .dot-open       { background-color: oklch(var(--in)); }
  .dot-in_progress{ background-color: oklch(var(--p)); }
  .dot-review     { background-color: oklch(var(--wa)); }
  .dot-done       { background-color: oklch(var(--su)); }
  
  .board-col-body {
    min-height: 60vh;
  }

  /* Desain Kartu Tugas Baru */
  .task-card {
    background-color: oklch(var(--b1));
    border-radius: 0.75rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    border: 1px solid oklch(var(--b3));
    transition: box-shadow 0.2s ease-in-out;
  }

  .task-card:hover {
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  }

  /* Drag & Drop states */
  .sortable-ghost {
    background-color: oklch(var(--b3));
    opacity: 0.5;
  }
  .sortable-chosen {
    transform: scale(1.03);
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  }

  /* Badge Prioritas Baru (lebih lembut) */
  .prio-badge {
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 9999px;
  }
  .prio-low    { background-color: oklch(var(--in) / 0.1); color: oklch(var(--in)); }
  .prio-medium { background-color: oklch(var(--su) / 0.1); color: oklch(var(--su)); }
  .prio-high   { background-color: oklch(var(--wa) / 0.1); color: oklch(var(--wa)); }
  .prio-urgent { background-color: oklch(var(--er) / 0.1); color: oklch(var(--er)); }
</style>

@php
  // Judul kolom dan warna dot
  $statusConfig = [
    'open'        => ['title' => 'Not Started', 'dot' => 'dot-open'],
    'in_progress' => ['title' => 'Doing',       'dot' => 'dot-in_progress'],
    'review'      => ['title' => 'Paused',      'dot' => 'dot-review'], // Disesuaikan dengan referensi
    'done'        => ['title' => 'Done',        'dot' => 'dot-done'],
  ];

  // Fungsi untuk class badge prioritas baru
  function prioBadgeClass($p) {
    return match($p) {
      'low'    => 'prio-low',
      'medium' => 'prio-medium',
      'high'   => 'prio-high',
      'urgent' => 'prio-urgent',
      default  => '',
    };
  }
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
              <article id="task-{{ $task->id }}" class="card task-card cursor-grab active:cursor-grabbing" onclick="openEditModal({{ $task->id }})">
                <div class="card-body p-4 space-y-2">
                  <p class="font-semibold text-base-content leading-tight">{{ $task->title }}</p>
                  
                  @if($task->priority)
                    <div>
                      <span class="prio-badge {{ prioBadgeClass($task->priority) }}">{{ ucfirst($task->priority) }}</span>
                    </div>
                  @endif

                  <div class="flex items-center justify-between text-sm text-base-content/60">
                    @if($task->due_date)
                      <span>{{ $task->due_date->format('d M') }}</span>
                    @else
                      <span></span> <!-- Spacer -->
                    @endif
                    
                    @if($task->assignee)
                      <div class="tooltip" data-tip="{{ $task->assignee->name }}">
                        <div class="avatar w-6 h-6">
                            @if ($task->assignee->avatar)
                                <img src="{{ asset('storage/' . $task->assignee->avatar) }}" alt="{{ $task->assignee->name }}" class="rounded-full" />
                            @else
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($task->assignee->name) }}&background=random&color=fff&size=24" alt="{{ $task->assignee->name }}" class="rounded-full" />
                            @endif
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

{{-- Modals tidak berubah, tetap menggunakan yang lama --}}
@include('tasks.partials.modals')

@endsection

