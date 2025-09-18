@extends('layouts.app')

@section('content')
<div class="p-4">
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    @foreach ($columns as $col)
      <div class="bg-base-200 rounded-lg p-3">
        <h3 class="font-semibold mb-2">{{ $col['label'] }}</h3>

        <div class="space-y-3 min-h-[120px]" id="ids.col('{{ $col['key'] }}')">
          @forelse ($tasks[$col['key']] as $t)
            @php
              $ownersArr = $t->owners->map(function ($u) {
                  return [
                      'id'   => $u->id,
                      'name' => $u->name,
                      'wa'   => $u->wa_number,
                  ];
              })->values()->all();

              $taskPayload = [
                  'id'       => $t->id,
                  'title'    => $t->title,
                  'priority' => $t->priority,
                  'status'   => $t->status,
                  'due_date' => optional($t->due_date)->format('Y-m-d'),
                  'owners'   => $ownersArr,
                  'creator'  => optional($t->creator)->name,
                  'link'     => $t->link,
              ];

              $taskJson = e(json_encode(
                  $taskPayload,
                  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
              ));
            @endphp

            <div
              id="ids.card({{ $t->id }})"
              class="card bg-base-100 border border-base-300/50 shadow-sm hover:shadow-md transition-all"
              data-task="{{ $taskJson }}"
            >
              <div class="card-body p-3">
                <div class="flex justify-between items-start">
                  <h4 class="font-medium text-sm">{{ $t->title }}</h4>

                  <!-- Dropdown opsi -->
                  <div class="dropdown dropdown-end">
                    <button tabindex="0" class="btn btn-xs btn-ghost">⋮</button>
                    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-md shadow p-1 w-40">
                      <li><a href="{{ route('tasks.edit', $t) }}">Edit</a></li>
                      <li>
                        <form method="POST" action="{{ route('tasks.destroy', $t) }}">
                          @csrf @method('DELETE')
                          <button type="submit" onclick="return confirm('Hapus task ini?')">Hapus</button>
                        </form>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="text-xs text-gray-500 mt-1">
                  {{ ucfirst($t->status) }}
                  @if($t->due_date)
                    • due {{ $t->due_date->format('d M Y') }}
                  @endif
                </div>
              </div>
            </div>
          @empty
            <div class="text-sm text-gray-400">Tidak ada task</div>
          @endforelse
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
