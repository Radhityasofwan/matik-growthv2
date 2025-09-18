// Alpine inti
import Alpine from 'alpinejs'
window.Alpine = Alpine

// CSS utama
import '../css/app.css'

// --------------------------------------------------
// GLOBAL UI STORE (ringan)
// --------------------------------------------------
Alpine.store('ui', {
  isMobileMenuOpen: false,
  isSearchOpen: false,
  sidebarHover: false,
})

// --------------------------------------------------
// UTIL: helper kecil
// --------------------------------------------------
const prefersReducedMotion = () =>
  window.matchMedia('(prefers-reduced-motion: reduce)').matches

const hasAOSNodes = () => !!document.querySelector('[data-aos]')

// --------------------------------------------------
// OPTIONAL: AOS (dinamis, hanya bila perlu)
// --------------------------------------------------
async function maybeInitAOS() {
  if (prefersReducedMotion() || !hasAOSNodes()) return
  const [{ default: AOS }] = await Promise.all([
    import('aos'),
    import('aos/dist/aos.css'),
  ])
  AOS.init({
    once: true,
    duration: 500,
    easing: 'ease-out-cubic',
    // Hindari repaint besar
    anchorPlacement: 'top-bottom',
  })
}

// --------------------------------------------------
// NOTIFIKASI (malas: aktif saat dibuka saja)
// --------------------------------------------------
Alpine.data('notifBell', (el) => {
  const initialUnread = parseInt(el?.dataset?.unread || '0', 10) || 0
  let preview = []
  try { preview = JSON.parse(el?.dataset?.preview || '[]') } catch { preview = [] }

  const feedUrl = el?.dataset?.feed || ''
  const readBase = el?.dataset?.readBase || ''
  const markAllUrl = el?.dataset?.readAll || ''

  let polling = null
  let controller = null
  let openObserverBound = false

  const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

  const readUrl = (id) => (readBase ? `${readBase}/${encodeURIComponent(id)}/read` : '')

  const startPolling = (fn, ms = 15000) => {
    if (polling) return
    polling = setInterval(fn, ms)
  }
  const stopPolling = () => {
    if (polling) { clearInterval(polling); polling = null }
  }
  const abortInFlight = () => {
    if (controller) { controller.abort(); controller = null }
  }

  const onVisChange = (fn) => {
    const h = () => (document.hidden ? stopPolling() : fn())
    document.addEventListener('visibilitychange', h, { passive: true })
    return () => document.removeEventListener('visibilitychange', h)
  }

  return {
    open: false,
    unread: initialUnread,
    items: (preview || []).map(n => ({ source: n.source ?? 'notification', ...n })),

    init() {
      // Hubungkan lifecycle open/close sekali saja (hemat)
      if (!openObserverBound) {
        this.$watch('open', (v) => {
          if (v) {
            this.fetchFeed()
            startPolling(() => this.fetchFeed(), 15000)
          } else {
            stopPolling()
            abortInFlight()
          }
        })
        onVisChange(() => this.fetchFeed())
        openObserverBound = true
      }
    },

    async fetchFeed() {
      if (!feedUrl) return
      try {
        abortInFlight()
        controller = new AbortController()
        const res = await fetch(feedUrl, {
          headers: { 'Accept': 'application/json' },
          signal: controller.signal,
          cache: 'no-store',
        })
        if (!res.ok) return
        const json = await res.json()
        this.unread = json.unread_count ?? this.unread
        const list = Array.isArray(json.notifications) ? json.notifications : []
        this.items = list.map(n => ({ source: n.source ?? 'notification', ...n }))
      } catch (e) {
        if (e.name !== 'AbortError') console.error('notif feed error:', e)
      } finally {
        controller = null
      }
    },

    async markReadIfNotification(n) {
      if (n.source !== 'notification' || n.read_at) return
      const url = readUrl(n.id)
      if (!url) return
      try {
        await fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
          cache: 'no-store',
        })
        n.read_at = new Date().toISOString()
        if (this.unread > 0) this.unread--
      } catch (e) { console.error('notif read error:', e) }
    },

    async markAll() {
      if (!markAllUrl || !this.items.some(i => i.source === 'notification' && !i.read_at)) return
      try {
        await fetch(markAllUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
          cache: 'no-store',
        })
        this.items = this.items.map(n => n.source === 'notification'
          ? { ...n, read_at: new Date().toISOString() }
          : n)
        this.unread = 0
      } catch (e) { console.error('notif markAll error:', e) }
    },

    async go(n) {
      await this.markReadIfNotification(n)
      if (n.url) window.location.href = n.url
      this.open = false
    },
  }
})

// --------------------------------------------------
// BOOT ringan
// --------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  // Dinamis AOS
  maybeInitAOS()

  // Esc: tutup drawer/modal
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      Alpine.store('ui').isMobileMenuOpen = false
      Alpine.store('ui').isSearchOpen = false
    }
  }, { passive: true })
})

Alpine.start()
