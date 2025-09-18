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
    { pattern: /^(bg|text|border|ring)-(primary|secondary|accent|neutral|info|success|warning|error)$/ },
    'bg-base-100', 'bg-base-200', 'bg-base-300',
    { pattern: /^(btn|btn-(primary|secondary|accent)|badge|badge-(outline|primary|secondary|accent)|alert|alert-(info|success|warning|error))$/ },
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter Variable', 'Inter', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        brand: {
          50:  '#f0f6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6', // base
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        highlight: {
          pink: '#ec4899',
          lime: '#84cc16',
          cyan: '#06b6d4',
        },
      },
    },
  },

  plugins: [forms, typography, daisyui],

  daisyui: {
    themes: [
      {
        softblue: {
          primary:   '#3B82F6',
          'primary-content': '#FFFFFF', // Teks putih untuk kontras di atas primary
          secondary: '#ECF4FF',
          'secondary-content': '#1E293B', // Teks gelap untuk kontras di atas secondary
          accent:    '#1FB2A6',
          neutral:   '#1E293B',
          'base-100':'#ffffff',
          'base-200':'#F8FAFC',
          'base-300':'#E2E8F0',
          'base-content': '#1E293B', // Warna teks default untuk background terang
          info:      '#3ABFF8',
          success:   '#10B981',
          warning:   '#F59E0B',
          error:     '#EF4444',
        },
      },
      {
        dark: {
          primary:   '#2563EB',
          'primary-content': '#FFFFFF', // Teks putih untuk kontras di atas primary (PENTING untuk menu aktif)
          secondary: '#374151',
          'secondary-content': '#E0E6F1', // Teks terang untuk kontras di atas secondary
          accent:    '#F472B6',
          neutral:   '#1F2937',
          'base-100':'#111827',
          'base-200':'#1F2937',
          'base-300':'#374151',
          'base-content': '#E0E6F1', // Warna teks default (putih pudar) untuk background gelap
          info:      '#38BDF8',
          success:   '#22C55E',
          warning:   '#FACC15',
          error:     '#F87171',
        },
      },
    ],
  },
}
