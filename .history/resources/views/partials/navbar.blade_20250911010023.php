{{-- resources/views/partials/navbar.blade.php --}}
@php
    // Default agar aman di semua halaman
    /** @var \Illuminate\Support\Collection|\Illuminate\Notifications\DatabaseNotification[] $notifications */
    $notifications = ($notifications ?? collect());
    $unread = isset($unread)
        ? (int) $unread
        : (method_exists($notifications, 'whereNull') ? $notifications->whereNull('read_at')->count() : 0);
@endphp

<nav class="sticky top-0 z-50 bg-white shadow-md dark:bg-gray-800">
    <div class="container mx-auto flex h-16 items-center justify-between px-6 text-purple-600 dark:text-purple-300">
        <!-- Left: Brand & mobile burger -->
        <div class="flex items-center gap-2">
            <!-- Mobile hamburger -->
            <button
                type="button"
                class="btn btn-ghost btn-square md:hidden"
                @click="sidebarOpen = !sidebarOpen"
                aria-label="Toggle sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 5h14a1 1 0 110 2H3a1 1 0 110-2zm0 5h14a1 1 0 110 2H3a1 1 0 110-2zm0 5h14a1 1 0 110 2H3a1 1 0 110-2z" clip-rule="evenodd"/>
                </svg>
            </button>
            <span class="hidden md:inline font-semibold text-sm">{{ config('app.name', 'Matik Growth') }}</span>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center gap-2">
            <!-- Theme toggler (biarkan partial yang sudah ada) -->
            @include('partials.theme-toggle')

            <!-- Notifications -->
            <div class="dropdown dropdown-end">
                <button tabindex="0" type="button" role="button" aria-label="Notifications" class="btn btn-ghost btn-circle">
                    <div class="indicator">
                        <!-- bell icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2a6 6 0 00-6 6v3.586l-1.707 1.707A1 1 0 004 15h16a1 1 0 00.707-1.707L19 11.586V8a6 6 0 00-6-6z"/>
                            <path d="M8 17a4 4 0 008 0H8z"/>
                        </svg>
                        @if($unread > 0)
                            <span class="badge badge-xs indicator-item">{{ $unread > 99 ? '99+' : $unread }}</span>
                        @endif
                    </div>
                </button>
                <div tabindex="0" class="dropdown-content mt-3 z-[100] w-80 card bg-base-100 shadow">
                    <div class="card-body">
                        <span class="font-bold text-lg">Notifications</span>

                        @forelse($notifications->take(6) as $n)
                            <a href="{{ route('notifications.index') }}" class="text-sm truncate"
                               title="{{ data_get($n->data,'title', 'Notification') }}">
                                {{ data_get($n->data,'title','Notification') }}
                            </a>
                        @empty
                            <span class="text-sm opacity-70">No notifications</span>
                        @endforelse

                        <div class="card-actions grid grid-cols-2 gap-2 mt-2">
                            <form method="POST" action="{{ route('notifications.markAsRead') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm w-full">Mark all read</button>
                            </form>
                            <a href="{{ route('notifications.index') }}" class="btn btn-primary btn-sm w-full">View all</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile -->
            <div class="dropdown dropdown-end">
                <button tabindex="0" type="button" class="btn btn-ghost btn-circle avatar" aria-label="Profile menu">
                    <div class="w-9 rounded-full">
                        <img
                            alt="avatar"
                            src="{{ optional(Auth::user())->avatar_url ?: 'https://ui-avatars.com/api/?name='.urlencode(optional(Auth::user())->name ?? 'User') }}">
                    </div>
                </button>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[100] p-2 shadow bg-base-100 rounded-box w-56">
                    <li><a href="{{ route('profile') }}">Profile</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Log out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
