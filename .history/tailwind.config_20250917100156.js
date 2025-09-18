import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'
import daisyui from 'daisyui'

export default {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/**/*.{js,ts,vue}',
    './storage/framework/views/*.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
  ],

  safelist: [
    // warna inti + content (agar tak terpurge ketika dinamis)
    { pattern: /^(bg|text|border|ring)-(primary|secondary|accent|neutral|info|success|warning|error)$/ },
    { pattern: /^(bg|text|border|ring)-(base-(100|200|300)|base-content)$/ },
    { pattern: /^(text|bg)-(primary|secondary|accent|neutral|info|success|warning|error)-content$/ },
    // komponen umum
    { pattern: /^(btn|btn-(primary|secondary|accent|neutral|info|success|warning|error)|badge|badge-(outline|primary|secondary|accent|info|success|warning|error)|alert|alert-(info|success|warning|error))$/ },
    { pattern: /^progress-(primary|success|warning|error)$/ },
    { pattern: /^tabs(-boxed)?$/ },
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter Variable', 'Inter', ...defaultTheme.fontFamily.sans],
      },
    },
  },

  plugins: [forms, typography, daisyui],

  daisyui: {
    // Gunakan seluruh built-in themes DaisyUI
    themes: true,
    // (opsional) tampilkan lebih ringkas di console
    logs: false,
  },
}

// NOTE: kita tidak mendefinisikan tema 'softblue' di sini agar built-in aman 100%.
// Kita akan menginjeksikan 'softblue' via data-theme CSS di halaman lab (lihat file di bawah).
