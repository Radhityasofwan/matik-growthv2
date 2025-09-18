// resources/js/app.js — FINAL STABLE (tanpa perubahan logika, siap dipakai)
import Alpine from 'alpinejs'
window.Alpine = Alpine

import AOS from 'aos'
import 'aos/dist/aos.css'

import ApexCharts from 'apexcharts'

// ---------- Alpine Components ----------
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
    csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
    feedUrl()    { return feedUrl },
    readUrl(id)  { return readBase ? `${readBase}/${encodeURIComponent(id)}/read` : '' },
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
        this.items  = (json.notifications || []).map(n => ({ source: n.source ?? 'notification', ...n }))
      } catch (e) { console.error('notifBell fetch error:', e) }
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
      } catch (e) { console.error('notifBell markRead error:', e) }
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
        this.items = this.items.map(n => n.source === 'notification'
          ? { ...n, read_at: new Date().toISOString() }
          : n)
        this.unread = 0
      } catch (e) { console.error('notifBell markAll error:', e) }
    },
    async go(n) {
      await this.markReadIfNotification(n)
      if (n.url) window.location.href = n.url
    }
  }
})

// ---------- AOS Init ----------
function initAOS() {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  AOS.init({
    once: true,
    duration: prefersReducedMotion ? 0 : 600,
    easing: 'ease-out-cubic',
    disable: prefersReducedMotion
  })
}

// ---------- Demo Chart (opsional) ----------
function initDemoChart() {
  const el = document.querySelector('#chart-demo')
  if (!el || typeof ApexCharts === 'undefined') return

  const chart = new ApexCharts(el, {
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
  })

  chart.render().then(() => document.getElementById('chart-skeleton')?.classList.add('hidden'))
}

// ---------- Boot ----------
document.addEventListener('DOMContentLoaded', () => {
  initAOS()
  initDemoChart()
})

Alpine.start()
