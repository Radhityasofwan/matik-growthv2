// resources/js/app.js — FINAL

// Alpine core
import Alpine from 'alpinejs'
window.Alpine = Alpine

// CSS utama (Tailwind + DaisyUI + font)
import '../css/app.css'

// AOS (scroll animations)
import AOS from 'aos'
import 'aos/dist/aos.css'

// ApexCharts (opsional, untuk chart demo + skeleton anti-CLS)
import ApexCharts from 'apexcharts'

// ------------------------------
// Alpine Components
// ------------------------------
Alpine.data('notifBell', (el) => {
  const unreadInit = parseInt(el?.dataset?.unread || '0', 10) || 0
  let preview = []
  try { preview = JSON.parse(el?.dataset?.preview || '[]') } catch { preview = [] }

  const feedUrl = el?.dataset?.feed || ''
  const readBase = el?.dataset?.readBase || '' // contoh: "/notifications"
  const markAllUrl = el?.dataset?.readAll || ''

  return {
    open: false,
    unread: unreadInit,
    items: (preview || []).map(n => ({ source: n.source ?? 'notification', ...n })),
    pollMs: 15000,
    timer: null,
    csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
    feedUrl()    { return feedUrl },
    readUrl(id)  { return `${readBase}/${encodeURIComponent(id)}/read` },
    markAllUrl() { return markAllUrl },

    init() {
      this.fetchFeed()
      this.timer = setInterval(() => this.fetchFeed(), this.pollMs)
      document.addEventListener('close-all', () => this.open = false)
    },
    async fetchFeed() {
      if (!this.feedUrl()) return
      try {
        const res = await fetch(this.feedUrl(), {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        if (!res.ok) return
        const json = await res.json()
        this.unread = json.unread_count || 0
        this.items  = (json.notifications || []).map(n => ({ source: n.source ?? 'notification', ...n }))
      } catch (e) { console.error('Gagal mengambil notifikasi:', e) }
    },
    async markReadIfNotification(n) {
      if (n.source !== 'notification' || n.read_at) return
      try {
        const url = this.readUrl(n.id)
        if (!url) return
        await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        n.read_at = new Date().toISOString()
        if (this.unread > 0) this.unread--
      } catch (e) { console.error('Gagal menandai notifikasi:', e) }
    },
    async markAll() {
      const url = this.markAllUrl()
      if (!url) return
      if (!this.items.some(x => x.source === 'notification' && !x.read_at)) return
      try {
        await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        this.items = this.items.map(n => n.source === 'notification'
          ? { ...n, read_at: new Date().toISOString() }
          : n)
        this.unread = 0
      } catch (e) { console.error('Gagal menandai semua notifikasi:', e) }
    },
    async go(n) {
      await this.markReadIfNotification(n)
      if (n.url) window.location.href = n.url
    }
  }
})

// ------------------------------
// AOS Init (hormat prefers-reduced-motion)
// ------------------------------
function initAOS() {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  AOS.init({
    once: true,
    duration: prefersReducedMotion ? 0 : 600,
    easing: 'ease-out-cubic',
    disable: prefersReducedMotion
  })
}

// ------------------------------
// ApexCharts Demo + Skeleton anti-CLS
// ------------------------------
function initDemoChart() {
  const el = document.querySelector('#chart-demo')
  if (!el) return
  const options = {
    chart: { type: 'area', height: 320, animations: { enabled: true }, toolbar: { show: false },
             fontFamily: 'Inter Variable, Inter, sans-serif' },
    series: [
      { name: 'Sales',   data: [31, 40, 28, 51, 42, 109, 100] },
      { name: 'Revenue', data: [11, 32, 45, 32, 34, 52, 41] },
    ],
    xaxis: { categories: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], axisBorder: { show:false },
             labels: { style: { colors: '#94a3b8' } } },
    yaxis: { labels: { style: { colors: '#94a3b8' } } },
    colors: ['#3B82F6', '#F472B6'],
    stroke: { curve: 'smooth', width: 3 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0,90,100] } },
    dataLabels: { enabled: false },
    grid: { borderColor: 'rgba(226,232,240,.4)', strokeDashArray: 4 },
    tooltip: { theme: 'dark' },
    legend: { position:'top', horizontalAlign:'left', labels:{ colors:'#64748b' } },
  }
  const chart = new ApexCharts(el, options)
  chart.render().then(() => document.getElementById('chart-skeleton')?.classList.add('hidden'))
}

// ------------------------------
// Boot
// ------------------------------
document.addEventListener('DOMContentLoaded', () => {
  initAOS()
  initDemoChart()
})

Alpine.start()
