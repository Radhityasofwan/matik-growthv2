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
      {
        dark: {
          primary:   '#2563EB',
          secondary: '#374151',
          accent:    '#F472B6',
          neutral:   '#1F2937',
          'base-100':'#111827',
          'base-200':'#1F2937',
          'base-300':'#374151',
          info:      '#38BDF8',
          success:   '#22C55E',
          warning:   '#FACC15',
          error:     '#F87171',
        },
      },
      {
        light: {
          primary:   '#2563EB',
          secondary: '#F3F4F6',
          accent:    '#D946EF',
          neutral:   '#374151',
          'base-100':'#ffffff',
          'base-200':'#F9FAFB',
          'base-300':'#E5E7EB',
          info:      '#0EA5E9',
          success:   '#16A34A',
          warning:   '#EAB308',
          error:     '#DC2626',
        },
      },
    ],
  },
}
