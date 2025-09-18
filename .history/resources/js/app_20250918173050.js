// 1) Alpine
import Alpine from 'alpinejs'
window.Alpine = Alpine

// 2) CSS (Ini akan di-handle oleh Vite)
import '../css/app.css'

// 3) AOS
import AOS from 'aos'
import 'aos/dist/aos.css'

// 4) ApexCharts (opsional)
import ApexCharts from 'apexcharts'
window.ApexCharts = ApexCharts;

// ---------------- Alpine Global Store (UI) ----------------
Alpine.store('ui', {
  isMobileMenuOpen: false,
  isSearchOpen: false,
  sidebarHover: false,
})

// ---------------- Alpine Components (notif) ----------------
Alpine.data('notifBell', (el) => {
  const unreadInit = parseInt(el?.dataset?.unread || '0', 10) || 0
  let preview = []
  try { preview = JSON.parse(el?.dataset?.preview || '[]') } catch { preview = [] }
  const feedUrl = el?.dataset?.feed || ''
  const readBase = el?.dataset?.readBase || ''
  const markAllUrl = el?.dataset?.readAll || ''

  return {
    open: false,
    unread: unreadInit,
    items: (preview || []).map(n => ({ source: n.source ?? 'notification', ...n })),
    pollMs: 15000,
    timer: null,
    csrf() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    },
    feedUrl() { return feedUrl },
    readUrl(id) { return readBase ? `${readBase}/${encodeURIComponent(id)}/read` : '' },
    markAllUrl() { return markAllUrl },
    init() {
      if (this.feedUrl()) {
        this.fetchFeed()
        this.timer = setInterval(() => this.fetchFeed(), this.pollMs)
      }
      document.addEventListener('close-all', () => this.open = false)
    },
    async fetchFeed() {
      try {
        const res = await fetch(this.feedUrl(), { headers: { 'Accept': 'application/json' } })
        if (!res.ok) return
        const json = await res.json()
        this.unread = json.unread_count ?? this.unread
        this.items = (json.notifications || []).map(n => ({ source: n.source ?? 'notification', ...n }))
      } catch (e) {
        console.error('notifBell fetch error:', e)
      }
    },
    async markReadIfNotification(n) {
      if (n.source !== 'notification' || n.read_at) return
      const url = this.readUrl(n.id)
      if (!url) return
      try {
        await fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
        })
        n.read_at = new Date().toISOString()
        if (this.unread > 0) this.unread--
      } catch (e) {
        console.error('notifBell markRead error:', e)
      }
    },
    async markAll() {
      const url = this.markAllUrl()
      if (!url) return
      if (!this.items.some(x => x.source === 'notification' && !x.read_at)) return
      try {
        await fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' }
        })
        this.items = this.items.map(n => n.source === 'notification' ? { ...n, read_at: new Date().toISOString() } : n)
        this.unread = 0
      } catch (e) {
        console.error('notifBell markAll error:', e)
      }
    },
    async go(n) {
      await this.markReadIfNotification(n)
      if (n.url) window.location.href = n.url
    }
  }
})

// ---------------- AOS Init ----------------
function initAOS() {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  AOS.init({
    once: true,
    duration: prefersReducedMotion ? 0 : 600,
    easing: 'ease-out-cubic',
    disable: prefersReducedMotion
  })
}

<script>
/* ===== Dropdown stabil tanpa lib eksternal ===== */
(function(){
  const OPEN_CLASS = 'block';
  const HIDE_CLASS = 'hidden';

  function closeAllMenus(exceptId = null){
    document.querySelectorAll('[id^="menu-task-"]').forEach(el=>{
      if (exceptId && el.id === exceptId) return;
      el.classList.remove(OPEN_CLASS);
      el.classList.add(HIDE_CLASS);
      // sync aria
      const btn = document.querySelector(`[data-menu-toggle="${el.id}"]`);
      if (btn) btn.setAttribute('aria-expanded', 'false');
      el.setAttribute('aria-hidden', 'true');
    });
  }

  window.closeMenuById = function(id){
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove(OPEN_CLASS);
    el.classList.add(HIDE_CLASS);
    const btn = document.querySelector(`[data-menu-toggle="${id}"]`);
    if (btn) btn.setAttribute('aria-expanded', 'false');
    el.setAttribute('aria-hidden', 'true');
  };

  function toggleMenu(id, triggerBtn){
    const menu = document.getElementById(id);
    if (!menu) return;
    const willOpen = menu.classList.contains(HIDE_CLASS);
    closeAllMenus(willOpen ? id : null);
    if (willOpen) {
      menu.classList.remove(HIDE_CLASS);
      menu.classList.add(OPEN_CLASS);
      triggerBtn?.setAttribute('aria-expanded','true');
      menu.setAttribute('aria-hidden','false');
      // fokus pertama item
      const first = menu.querySelector('button, [role="menuitem"]');
      if (first) setTimeout(()=>first.focus(), 0);
    } else {
      closeMenuById(id);
    }
  }

  // klik pemicu
  document.addEventListener('click', (e)=>{
    const t = e.target.closest('[data-menu-toggle]');
    if (t){
      e.preventDefault();
      const id = t.getAttribute('data-menu-toggle');
      toggleMenu(id, t);
      return;
    }
    // klik di luar => tutup
    const inMenu = e.target.closest('[id^="menu-task-"]');
    const inBtn  = e.target.closest('[data-menu-toggle]');
    if (!inMenu && !inBtn){
      closeAllMenus();
    }
  }, true);

  // tutup saat ESC, scroll, resize
  document.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape') closeAllMenus();
  });
  window.addEventListener('scroll', ()=>closeAllMenus(), true);
  window.addEventListener('resize', ()=>closeAllMenus());

  // aksesibilitas: panah atas/bawah untuk navigasi
  document.addEventListener('keydown', (e)=>{
    const cur = e.target.closest('[id^="menu-task-"]');
    if (!cur) return;
    const items = [...cur.querySelectorAll('button,[role="menuitem"]')];
    if (!items.length) return;
    const idx = items.indexOf(e.target);
    if (e.key === 'ArrowDown'){
      e.preventDefault();
      items[(idx+1) % items.length].focus();
    } else if (e.key === 'ArrowUp'){
      e.preventDefault();
      items[(idx-1+items.length) % items.length].focus();
    }
  });
})();
</script>

// ---------------- Boot ----------------
document.addEventListener('DOMContentLoaded', () => {
  initAOS()

  // Esc untuk tutup mobile drawer/search
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      Alpine.store('ui').isMobileMenuOpen = false
      Alpine.store('ui').isSearchOpen = false
    }
  })

})

Alpine.start()

