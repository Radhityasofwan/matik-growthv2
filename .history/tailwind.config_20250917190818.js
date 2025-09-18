// tailwind.config.js — FINAL (ESM)
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'
import daisyui from 'daisyui'

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/**/*.{js,ts,vue}',
    './storage/framework/views/*.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
  ],
  safelist: [
    { pattern: /^(bg|text|border|ring)-(primary|secondary|accent|neutral|info|success|warning|error)$/ },
    { pattern: /^(bg|text|border|ring)-(base-(100|200|300)|base-content)$/ },
    { pattern: /^(text|bg)-(primary|secondary|accent|neutral|info|success|warning|error)-content$/ },
    { pattern: /^(btn|btn-(ghost|primary|secondary|accent|neutral|info|success|warning|error)|badge|badge-(outline|primary|secondary|accent|info|success|warning|error)|alert|alert-(info|success|warning|error))$/ },
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
    themes: true,
    darkTheme: 'dark',
    logs: false,
  },
}
