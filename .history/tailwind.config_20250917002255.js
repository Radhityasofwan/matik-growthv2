// tailwind.config.js
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'
import daisyui from 'daisyui'

/** @type {import('tailwindcss').Config} */
export default {
  // Aktifkan dark variant Tailwind via class
  darkMode: 'class',

  content: [
    './resources/views/**/*.blade.php',
    './resources/**/*.{js,ts,vue}',
    './storage/framework/views/*.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
  ],

  safelist: [
    // utilitas warna semantik DaisyUI (agar selalu ter-generate)
    { pattern: /^(bg|text|border|ring)-(primary|secondary|accent|neutral|info|success|warning|error)$/ },
    // varian base untuk panel/card
    'bg-base-100', 'bg-base-200', 'bg-base-300',
    // komponen umum DaisyUI
    { pattern: /^(btn|btn-(primary|secondary|accent)|badge|badge-(outline|primary|secondary|accent)|alert|alert-(info|success|warning|error))$/ },
  ],

  theme: {
    extend: {
      fontFamily: {
        // Inter Variable → Inter → sistem
        sans: ['Inter Variable', 'Inter', ...defaultTheme.fontFamily.sans],
      },
    },
  },

  plugins: [forms, typography, daisyui],

  daisyui: {
    // Sertakan softblue + dark + light agar bisa diswitch
    themes: [
      {
        softblue: {
          primary:   '#3B82F6',
          secondary: '#ECF4FF',
          accent:    '#1FB2A6',
          neutral:   '#1E293B',
          'base-100':'#ffffff',
          'base-200':'#F8FAFC',
          'base-300':'#E2E8F0',
          info:      '#3ABFF8',
          success:   '#10B981',
          warning:   '#F59E0B',
          error:     '#EF4444',
        },
      },
      'dark',
      'light',
    ],
  },
}
