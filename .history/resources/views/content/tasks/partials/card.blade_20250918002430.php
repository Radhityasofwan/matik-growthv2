@php
  // Dapatkan warna yang diteruskan dari parent; jika tidak ada, tetap variatif
  $color = $taskColor ?? ($task->color ?? 'primary');
  $barCls = [
    'primary'=>'task-bar-primary','secondary'=>'task-bar-secondary','accent'=>'task-bar-accent',
    'info'=>'task-bar-info','success'=>'task-bar-success','warning'=>'task-bar-warning','error'=>'task-bar-error',
  ][$color] ?? 'task-bar-primary';

  $prioBadge = match($task->priority) {
    'low'=>'badge-info','medium'=>'badge-success','high'=>'badge-warning','urgent'=>'badge-error',
    default => 'badge-ghost',
  };
@endphp

<div id="task-{{ $task->id }}" class="card task-card bg-base-100 {{ $barCls }} cursor-grab active:cursor-grabbing">
  <div class="card-body p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="font-semibold text-base-content leading-tight line-clamp-2">{{ $task->title }}</p>
        @if($task->description)
          <p class="text-sm text-base-content/70 mt-1 line-clamp-3">{{ $task->description }}</p>
        @endif
      </div>
      <span class="badge {{ $prioBadge }} prio-pill badge-sm">{{ ucfirst($task->priority) }}</span>
    </div>

    <div class="card-actions justify-between items-center mt-4">
      <div class="flex items-center gap-2 text-sm text-base-content/70">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span @if($task->due_date && $task->due_date->isPast() && $task->status !== 'done') class="text-error font-semibold" @endif>
          {{ $task->due_date?->format('d M Y') ?? 'Tanpa tenggat' }}
        </span>
      </div>

      <div class="flex items-center gap-1">
        @if($task->link)
          <a href="{{ $task->link }}" target="_blank" class="btn btn-ghost btn-xs btn-circle" data-tip="Buka Link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
          </a>
        @endif

        @if($task->assignee)
          <div class="tooltip" data-tip="{{ $task->assignee->name }}">
            <div class="avatar">
              <div class="w-8 rounded-full ring ring-base-100">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($task->assignee->name) }}&background=random" />
              </div>
            </div>
          </div>
        @endif

        <div class="dropdown dropdown-end">
          <label tabindex="0" class="btn btn-ghost btn-xs btn-circle">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
            </svg>
          </label>
          <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-48 z-10 border border-base-300/50">
            <li><a onclick="TaskView.openEditModal({{ $task->id }})">Edit Tugas</a></li>
            <div class="divider my-1"></div>
            <li><a onclick="TaskView.promptMoveStatus({{ $task->id }}, 'open')">Pindah ke To Do</a></li>
            <li><a onclick="TaskView.promptMoveStatus({{ $task->id }}, 'in_progress')">Pindah ke In Progress</a></li>
            <li><a onclick="TaskView.promptMoveStatus({{ $task->id }}, 'review')">Pindah ke Review</a></li>
            <li><a onclick="TaskView.promptMoveStatus({{ $task->id }}, 'done')">Pindah ke Done</a></li>
            <div class="divider my-1"></div>
            <li>
              <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Anda yakin?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full text-left text-error">Hapus Tugas</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
