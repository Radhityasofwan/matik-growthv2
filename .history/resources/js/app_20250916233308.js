import '../css/app.css'
import 'alpinejs'

import AOS from 'aos'
import 'aos/dist/aos.css'

window.addEventListener('load', () => {
  AOS.init({ once: true, duration: 600 })
})
