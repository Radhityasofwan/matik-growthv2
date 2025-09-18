{{-- resources/views/partials/sidebar.blade.php --}}

{{-- MOBILE OVERLAY --}}
<div
  x-show="isMobileMenuOpen"
  x-transition.opacity
  @click="isMobileMenuOpen = false"
  class="fixed inset-0 z-40 bg-black/50 lg:hidden"
  x-cloak
  aria-hidden="true"
></div>

{{-- SIDEBAR --}}
<aside
  id="app-sidebar"
  role="dialog"
  aria-modal="true"
  class="fixed inset-y-0 left-0 z-50 flex flex-col bg-base-100 border-r border-base-300/50 transition-all duration-300 ease-in-out will-change-transform"
  :class="{
    // MOBILE drawer behavior
    'w-72 translate-x-0 shadow-2xl': isMobileMenuOpen,
    'w-72 -translate-x-full': !isMobileMenuOpen,

    // DESKTOP hover collapse / expand
    'lg:translate-x-0 lg:shadow-none lg:w-20': true
  }"
  @mouseenter="sidebarHover = true"
  @mouseleave="sidebarHover = false"
  x-cloak
>
  {{-- BRAND --}}
  <div class="flex h-20 shrink-0 items-center border-b border-base-300/50 px-4"
       :class="sidebarHover ? 'justify-start' : 'justify-center lg:justify-center'">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
      <x-application-logo class="h-8 w-8 shrink-0 text-primary"/>
      <span
        class="origin-left whitespace-nowrap text-lg font-bold text-base-content hidden lg:block"
        :class="sidebarHover ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none'"
      >
        MatikGrowth
      </span>
    </a>
  </div>

  {{-- MENU --}}
  @php
    $navGroups = [
      'Main' => [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
        ['route' => 'notifications.index', 'label' => 'Notifications', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>'],
      ],
      'Sales' => [
        ['route' => 'leads.index', 'label' => 'Leads', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
        ['route' => 'subscriptions.index', 'label' => 'Subscriptions', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>'],
        ['route' => 'campaigns.index', 'label' => 'Campaigns', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>'],
      ],
      'WhatsApp' => [
        ['route' => 'whatsapp.broadcast.create', 'label' => 'Broadcast', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>'],
        ['route' => 'whatsapp.templates.index', 'label' => 'WA Templates', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M13 8H7"/><path d="M17 12H7"/></svg>'],
        ['route' => 'lead-follow-up-rules.index', 'label' => 'Follow Up Rules (Lead)', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 12H3"/><path d="m3 12 4-4"/><path d="m3 12 4 4"/><path d="M21 12h-6"/><path d="m15 12 4-4"/><path d="m15 12 4 4"/><path d="M12 21V3"/></svg>'],
        ['route' => 'owner-follow-up-rules.index', 'label' => 'Follow Up Rules (Owner)', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h6"/><path d="m9 12-4-4"/><path d="m9 12-4 4"/><path d="M12 3v18"/><path d="M15 12h6"/><path d="m15 12 4-4"/><path d="m15 12 4 4"/></svg>'],
        ['route' => 'waha-senders.index', 'label' => 'WA Senders', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>'],
      ],
      'Content' => [
        ['route' => 'tasks.index', 'label' => 'Tasks', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 22h2a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16"/><path d="M9 12l2 2 4-4"/></svg>'],
        ['route' => 'assets.index', 'label' => 'Assets', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>'],
      ],
    ];
  @endphp

  <nav class="flex-1 overflow-y-auto">
    <ul class="menu p-4 space-y-1">
      @foreach ($navGroups as $group => $links)
        <li class="menu-title"><span :class="sidebarHover ? '' : 'lg:opacity-0 lg:pointer-events-none'">{{ $group }}</span></li>

        @foreach ($links as $link)
          @php $isActive = request()->routeIs($link['route'] . '*'); @endphp
          <li>
            <a
              href="{{ route($link['route']) }}"
              @click="$dispatch('sidebar:navigated')"
              class="gap-3 {{ $isActive ? 'bg-primary text-primary-content' : '' }}"
              :class="sidebarHover ? '' : 'lg:justify-center'"
            >
              {!! $link['icon'] !!}
              <span class="whitespace-nowrap hidden lg:inline" :class="sidebarHover ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                {{ $link['label'] }}
              </span>
            </a>
          </li>
        @endforeach
      @endforeach
    </ul>
  </nav>

  {{-- FOOT (optional) --}}
  <div class="p-4 border-t border-base-300/50 hidden lg:block">
    <div class="text-xs text-base-content/50" :class="sidebarHover ? '' : 'opacity-0 pointer-events-none'">
      v2
    </div>
  </div>
</aside>
