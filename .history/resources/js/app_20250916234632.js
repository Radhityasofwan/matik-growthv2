import '../css/app.css'
import 'alpinejs'

import AOS from 'aos'
import 'aos/dist/aos.css'

import ApexCharts from 'apexcharts'

function initDemoChart() {
  const el = document.querySelector('#chart-demo')
  if (!el) return
  const options = {
    chart: { type: 'area', height: 240, animations: { enabled: true } },
    series: [{ name: 'Sales', data: [31, 40, 28, 51, 42, 109, 100] }],
    xaxis: { categories: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] },
    colors: ['#3B82F6'], // selaras dengan primary softblue
    stroke: { curve: 'smooth' },
    dataLabels: { enabled: false },
    grid: { borderColor: 'rgba(226,232,240,.6)' },
  }
  const chart = new ApexCharts(el, options)
  chart.render().then(() => document.getElementById('chart-skeleton')?.classList.add('hidden'))
}

window.addEventListener('load', () => {
  AOS.init({ once: true, duration: 600 })
  initDemoChart()
})
