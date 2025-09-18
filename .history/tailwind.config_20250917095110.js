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
    { pattern: /^(btn|btn-(primary|secondary|accent|neutral|info|success|warning|error)|badge|badge-(outline|primary|secondary|accent|info|success|warning|error)|alert|alert-(info|success|warning|error))$/ },
    { pattern: /^progress-(primary|success|warning|error)$/ },
    { pattern: /^tabs(-boxed)?$/ },
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
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
      },
    },
  },

  plugins: [forms, typography, daisyui],

  daisyui: {
    // ← penting: include built-in light + dark untuk fallback yang lengkap
    themes: [
      'light',
      'dark',
      {
        softblue: {
          primary:   '#3B82F6',
          'primary-content': '#FFFFFF',
          secondary: '#ECF4FF',
          'secondary-content': '#1E293B',
          accent:    '#1FB2A6',
          neutral:   '#1E293B',
          'base-100':'#ffffff',
          'base-200':'#F8FAFC',
          'base-300':'#E2E8F0',
          'base-content': '#1E293B',
          info:      '#3ABFF8',
          success:   '#10B981',
          warning:   '#F59E0B',
          error:     '#EF4444',
        },
      },
    ],
  },
}
