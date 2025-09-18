<li class="relative" x-data="notifications()" x-cloak>
  <button
    class="relative align-middle rounded-md focus:outline-none focus:shadow-outline-purple"
    @click="toggle" aria-label="Notifications" aria-haspopup="true">
    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
      <path d="M10 2a6 6 0 00-6 6v3.586l-1.707 1.707A1 1 0 003 15v1a1 1 0 001 1h12a1 1 0 001-1v-1a1 1 0 00-.293-.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
    </svg>
    <span x-show="count > 0"
      class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1
             text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2
             bg-red-600 rounded-full"
      x-text="count"></span>
  </button>

  <div
    x-show="open"
    @click.outside="close"
    x-transition.origin.top.right
    class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
           rounded-md shadow-md z-50 overflow-hidden">
    <ul class="divide-y divide-gray-100 dark:divide-gray-700 max-h-80 overflow-auto">
      <template x-if="notifications.length === 0">
        <li class="p-4 text-sm text-gray-500 dark:text-gray-300 text-center">No new notifications</li>
      </template>

      <template x-for="n in notifications" :key="n.id">
        <li>
          <a class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700"
             :href="n.data?.url || '#'" @click="close">
            <div class="mt-0.5">
              <span class="block text-sm font-medium text-gray-800 dark:text-gray-100" x-text="n.data?.title || 'Notification'"></span>
              <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="n.data?.message || ''"></span>
              <span class="block text-[11px] text-gray-400 dark:text-gray-500" x-text="n.created_at_human || ''"></span>
            </div>
          </a>
        </li>
      </template>
    </ul>
    <div class="p-2 bg-gray-50 dark:bg-gray-700 flex justify-between">
      <button class="text-xs px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-600"
              @click="refresh">Refresh</button>
      <button class="text-xs px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-600"
              @click="markAsRead">Mark all as read</button>
    </div>
  </div>
</li>

@push('scripts')
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('notifications', () => ({
      open: false,
      notifications: [],
      count: 0,
      toggle(){ this.open = !this.open; if (this.open && this.count>0) this.markAsRead(); },
      close(){ this.open = false; },
      async refresh(){
        try{
          const res = await fetch('{{ route('notifications.index') }}', {
            headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
          });
          if(!res.ok) return;
          const data = await res.json();
          this.notifications = (data.notifications || []).map(n => ({
            ...n, created_at_human: n.created_at_human ?? ''
          }));
          this.count = data.unread_count ?? 0;
        }catch(e){ console.error('notif refresh', e); }
      },
      async markAsRead(){
        try{
          await fetch('{{ route('notifications.markAsRead') }}', {
            method:'POST',
            headers:{
              'Accept':'application/json',
              'Content-Type':'application/json',
              'X-Requested-With':'XMLHttpRequest',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: '{}'
          });
          this.count = 0;
          this.refresh();
        }catch(e){ console.error('notif read', e); }
      },
      init(){
        this.refresh();
        setInterval(()=>this.refresh(), 30000);
      }
    }));
  });
</script>
@endpush
