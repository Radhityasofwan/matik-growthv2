<script>
    function notifications() {
        return {
            unreadCount: 0,
            notifications: [],
            isLoading: true,
            notifOpen: false, // State to control the dropdown visibility
            init() {
                this.fetchNotifications();
                setInterval(() => {
                    this.fetchNotifications();
                }, 30000); // Poll every 30 seconds
            },
            toggle() {
                this.notifOpen = !this.notifOpen;
                if (this.notifOpen) {
                    this.fetchNotifications();
                }
            },
            fetchNotifications() {
                fetch('{{ route('notifications.index') }}')
                    .then(response => response.json())
                    .then(data => {
                        this.unreadCount = data.count;
                        this.notifications = data.notifications;
                        this.isLoading = false;
                    });
            },
            markAsRead(id, url) {
                fetch(`/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                }).then(response => {
                    if (response.ok) {
                        window.location.href = url;
                    }
                });
            },
            timeAgo(dateString) {
                const date = new Date(dateString);
                const seconds = Math.floor((new Date() - date) / 1000);
                let interval = seconds / 31536000;
                if (interval > 1) return Math.floor(interval) + " years ago";
                interval = seconds / 2592000;
                if (interval > 1) return Math.floor(interval) + " months ago";
                interval = seconds / 86400;
                if (interval > 1) return Math.floor(interval) + " days ago";
                interval = seconds / 3600;
                if (interval > 1) return Math.floor(interval) + " hours ago";
                interval = seconds / 60;
                if (interval > 1) return Math.floor(interval) + " minutes ago";
                return Math.floor(seconds) + " seconds ago";
            }
        }
    }
</script>

<header class="flex-shrink-0 flex items-center justify-between px-6 py-3 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
    <div>
        <!-- Mobile sidebar toggle -->
    </div>
    <div class="flex items-center" x-data="{ profileOpen: false }">
        <!-- Dark Mode Toggle -->
        <button @click="darkMode = !darkMode" class="mr-4 text-gray-600 dark:text-gray-300 focus:outline-none">
            <svg x-show="!darkMode" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
            <svg x-show="darkMode" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </button>

        <!-- Notifications Dropdown -->
        <div class="relative" x-data="notifications()">
            <button @click="toggle()" class="relative text-gray-600 dark:text-gray-300 hover:text-gray-700 focus:outline-none">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span x-show="unreadCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full" x-text="unreadCount"></span>
            </button>
            <div x-show="notifOpen" @click.away="notifOpen = false" class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-700 rounded-lg shadow-xl overflow-hidden z-10" style="display: none;">
                <div class="py-2 px-4 text-sm font-semibold text-gray-700 dark:text-gray-200 border-b dark:border-gray-600">Notifications</div>
                <div class="divide-y divide-gray-100 dark:divide-gray-600 max-h-96 overflow-y-auto">
                    <template x-if="isLoading">
                        <div class="p-4 text-center text-gray-500 dark:text-gray-400">Loading...</div>
                    </template>
                    <template x-if="!isLoading && notifications.length === 0">
                        <div class="p-4 text-center text-gray-500 dark:text-gray-400">No new notifications.</div>
                    </template>
                    <template x-for="notification in notifications" :key="notification.id">
                        <a :href="notification.data.url" @click.prevent="markAsRead(notification.id, notification.data.url)" class="flex items-start px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-600">
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="notification.data.message"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="timeAgo(notification.created_at)"></p>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <!-- Profile Dropdown -->
        <div class="relative ml-4">
            <button @click="profileOpen = !profileOpen" class="flex items-center focus:outline-none">
                <img class="h-8 w-8 rounded-full object-cover" src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
                <span class="ml-2 hidden md:inline text-sm font-medium dark:text-gray-300">{{ auth()->user()->name }}</span>
            </button>
            <div x-show="profileOpen" @click.away="profileOpen = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-xl z-10" style="display: none;">
                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Profile</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Logout</button>
                </form>
            </div>
        </div>
    </div>
</header>

