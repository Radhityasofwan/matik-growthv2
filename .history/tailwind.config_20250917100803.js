// tailwind.config.js — STABLE
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
    // warna utama + base tokens agar aman saat production build
    { pattern: /^(bg|text|border|ring)-(primary|secondary|accent|neutral|info|success|warning|error)$/ },
    { pattern: /^(bg|text|border|ring)-(base-(100|200|300)|base-content)$/ },
    { pattern: /^(text|bg)-(primary|secondary|accent|neutral|info|success|warning|error)-content$/ },
    // komponen umum
    { pattern: /^(btn|btn-(primary|secondary|accent|neutral|info|success|warning|error))$/ },
    { pattern: /^(badge|badge-(outline|primary|secondary|accent|info|success|warning|error))$/ },
    { pattern: /^(alert|alert-(info|success|warning|error))$/ },
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
    // Gunakan SELURUH tema bawaan DaisyUI (light, dark, cupcake, emerald, corporate, dracula, dll)
    themes: true,
    logs: false,
  },
}
