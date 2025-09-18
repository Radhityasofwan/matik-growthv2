<div class="drawer-side z-10">
    <label for="my-drawer-2" class="drawer-overlay"></label>
    <ul class="menu p-4 w-64 min-h-full bg-base-200 text-base-content">
        <!-- Sidebar content here -->
        <li class="mb-4">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold">
                Matik Growth Hub
            </a>
        </li>
        <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            Dashboard
        </a></li>

        <div class="divider"></div>

        <li class="menu-title"><span>Sales</span></li>
        <li><a href="{{ route('leads.index') }}" class="{{ request()->routeIs('leads.*') ? 'active' : '' }}">Leads</a></li>
        <li><a href="{{ route('subscriptions.index') }}" class="{{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">Subscriptions</a></li>

        <li class="menu-title"><span>Marketing</span></li>
        <li><a href="{{ route('campaigns.index') }}" class="{{ request()->routeIs('campaigns.*') ? 'active' : '' }}">Campaigns</a></li>

        <li class="menu-title"><span>Productivity & Content</span></li>
        <li><a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.*') ? 'active' : '' }}">Tasks</a></li>
        <li><a href="{{ route('assets.index') }}" class="{{ request()->routeIs('assets.*') ? 'active' : '' }}">Asset Library</a></li>


        <li class="menu-title"><span>Channels</span></li>
        <li>
            <details {{ request()->routeIs('whatsapp.*') || request()->routeIs('broadcasts.*') ? 'open' : '' }}>
                <summary>WhatsApp</summary>
                <ul>
                    <li><a href="{{ route('broadcasts.create') }}" class="{{ request()->routeIs('broadcasts.*') ? 'active' : '' }}">New Broadcast</a></li>
                    <li><a href="{{ route('templates.index') }}" class="{{ request()->routeIs('templates.*') ? 'active' : '' }}">Templates</a></li>
                    <li><a href="{{ route('whatsapp.logs.index') }}" class="{{ request()->routeIs('whatsapp.logs.index') ? 'active' : '' }}">Message Logs</a></li>
                </ul>
            </details>
        </li>

    </ul>
</div>

