<li class="relative" x-data="notifications">
    <button class="relative align-middle rounded-md focus:outline-none focus:shadow-outline-purple" @click="toggle" aria-label="Notifications" aria-haspopup="true">
        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a6 6 0 00-6 6v3.586l-1.707 1.707A1 1 0 003 15v1a1 1 0 001 1h12a1 1 0 001-1v-1a1 1 0 00-.293-.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
        </svg>
        <span x-show="count > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full" x-text="count"></span>
    </button>
    <template x-if="open">
        <ul @click.away="close" class="absolute right-0 w-56 p-2 mt-2 space-y-2 text-gray-600 bg-white border border-gray-100 rounded-md shadow-md dark:border-gray-700 dark:text-gray-300 dark:bg-gray-700">
            <template x-for="notification in notifications" :key="notification.id">
                <li class="flex">
                    <a class="inline-flex items-center justify-between w-full px-2 py-1 text-sm font-semibold transition-colors duration-150 rounded-md hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-800 dark:hover:text-gray-200" :href="notification.data.url">
                        <span x-text="notification.data.message"></span>
                    </a>
                </li>
            </template>
            <li x-show="notifications.length === 0" class="text-center text-sm py-2">No new notifications</li>
        </ul>
    </template>
</li>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('notifications', () => ({
            open: false,
            notifications: [],
            count: 0,
            toggle() {
                this.open = !this.open;
                if (this.open && this.count > 0) {
                    this.markAsRead();
                }
            },
            close() { this.open = false; },
            fetchNotifications() {
                fetch('{{ route('notifications.index') }}')
                    .then(response => response.json())
                    .then(data => {
                        this.notifications = data.notifications;
                        this.count = data.unread_count;
                    });
            },
            markAsRead() {
                 fetch('{{ route('notifications.markAsRead') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                }).then(() => {
                    this.count = 0;
                });
            },
            init() {
                this.fetchNotifications();
                setInterval(() => this.fetchNotifications(), 30000);
            }
        }));
    });
</script>

