import './bootstrap';
import Alpine from 'alpinejs';
import AOS from 'aos';
import 'aos/dist/aos.css';

// Komponen Alpine untuk bel notifikasi
Alpine.data('notifBell', (el) => {
    const unreadInit = parseInt(el.dataset.unread || '0', 10) || 0;
    let preview = [];
    try { preview = JSON.parse(el.dataset.preview || '[]'); } catch (e) { preview = []; }

    return {
        unread: unreadInit,
        items: preview,
        pollMs: 15000,
        timer: null,
        csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; },

        init() {
            this.fetchFeed();
            this.timer = setInterval(() => this.fetchFeed(), this.pollMs);
        },

        async fetchFeed() {
            try {
                const res = await fetch('{{ route("notifications.feed") }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const json = await res.json();
                this.unread = json.unread_count || 0;
                this.items  = json.notifications || [];
            } catch (e) { console.error('Gagal mengambil notifikasi:', e); }
        },

        async markReadIfNotification(n) {
            if (n.read_at) return;
            try {
                await fetch(`/notifications/${encodeURIComponent(n.id)}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                n.read_at = new Date().toISOString();
                if (this.unread > 0) this.unread--;
            } catch (e) { console.error('Gagal menandai notifikasi:', e); }
        },

        async markAll() {
            if (!this.items.some(x => !x.read_at)) return;
            try {
                await fetch('{{ route("notifications.read_all") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.items = this.items.map(n => ({ ...n, read_at: new Date().toISOString() }));
                this.unread = 0;
            } catch (e) { console.error('Gagal menandai semua notifikasi:', e); }
        },

        async go(n) {
            await this.markReadIfNotification(n);
            if (n.url) window.location.href = n.url;
        }
    }
});

window.Alpine = Alpine;
Alpine.start();
AOS.init({ once: true, duration: 600 });
