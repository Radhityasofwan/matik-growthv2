import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'
import daisyui from 'daisyui'

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/**/*.{js,ts,vue}',
    './storage/framework/views/*.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
  ],

  safelist: [
    // Pola warna inti agar tidak ter-purge saat digunakan secara dinamis
    { pattern: /^(bg|text|border|ring)-(primary|secondary|accent|neutral|info|success|warning|error)$/ },
    { pattern: /^(bg|text|border|ring)-(base-(100|200|300)|base-content)$/ },
    { pattern: /^(text|bg)-(primary|secondary|accent|neutral|info|success|warning|error)-content$/ },
    // Pola komponen umum
    { pattern: /^(btn|btn-(primary|secondary|accent|neutral|info|success|warning|error)|badge|badge-(outline|primary|secondary|accent|info|success|warning|error)|alert|alert-(info|success|warning|error))$/ },
  ],

  theme: {
    extend: {
      fontFamily: {
        // Menggunakan font Inter sebagai default, sesuai konfigurasi Anda
        sans: ['Inter Variable', 'Inter', ...defaultTheme.fontFamily.sans],
      },
    },
  },

  plugins: [forms, typography, daisyui],

  // Konfigurasi DaisyUI
  daisyui: {
    // Mengaktifkan semua tema bawaan DaisyUI (light, dark, cupcake, dll.)
    themes: true,
    // (Opsional) Nonaktifkan log di konsol untuk tampilan yang lebih bersih
    logs: false,
  },
}

