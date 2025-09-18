// resources/js/app.js (FINAL delta kecil)

import Alpine from 'alpinejs'
window.Alpine = Alpine
Alpine.start()

import '../css/app.css'

import AOS from 'aos'
import 'aos/dist/aos.css'

import ApexCharts from 'apexcharts'

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

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// 🔧 init secepat DOM siap (tidak menunggu semua asset)
document.addEventListener('DOMContentLoaded', () => {
  if (!document.body.classList.contains('aos-initialized')) {
AOS.init({
  once: true,
  duration: prefersReducedMotion ? 0 : 600,
  easing: 'ease-out-cubic',
  disable: prefersReducedMotion
})
    document.body.classList.add('aos-initialized')
  }
  initDemoChart()
})
