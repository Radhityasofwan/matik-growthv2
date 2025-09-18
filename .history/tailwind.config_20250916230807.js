import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'
import daisyui from 'daisyui'

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/**/*.{js,ts,vue}',
  ],
  theme: {
    extend: {
      fontFamily: { sans: ['Inter', ...defaultTheme.fontFamily.sans] },
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
          error:     '#EF4444'
        }
      }
    ]
  }
}
