// 1) Alpine
import Alpine from 'alpinejs'
window.Alpine = Alpine
Alpine.start()

// 2) Styles
import '../css/app.css'

// 3) AOS
import AOS from 'aos'
import 'aos/dist/aos.css'

// 4) Charts
import ApexCharts from 'apexcharts'

function initDemoChart() {
  const el = document.querySelector('#chart-demo')
  if (!el) return

  const options = {
    chart: {
      type: 'area',
      height: 300,
      animations: { enabled: true },
      toolbar: { show: false },
    },
    series: [
      { name: 'Sales', data: [31, 40, 28, 51, 42, 109, 100] },
      { name: 'Revenue', data: [11, 32, 45, 32, 34, 52, 41] },
    ],
    xaxis: { categories: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] },
    colors: ['#3B82F6', '#F472B6'],
    stroke: { curve: 'smooth', width: 3 },
    fill: {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1, stops: [0, 90, 100] }
    },
    dataLabels: { enabled: false },
    grid: { borderColor: 'rgba(226,232,240,.4)' },
  }

  const chart = new ApexCharts(el, options)
  chart.render().then(() => document.getElementById('chart-skeleton')?.classList.add('hidden'))
}

// 5) Boot interaksi
window.addEventListener('load', () => {
  AOS.init({ once: true, duration: 600 })
  initDemoChart()
})
