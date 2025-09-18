import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './storage/framework/views/*.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
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
    logs: false, 
    themes: [
      {
        softblue: {
          "primary": "#3b82f6",        // Blue-500
          "secondary": "#60a5fa",      // Blue-400
          "accent": "#93c5fd",         // Blue-300
          "neutral": "#374151",        // Gray-700
          "base-100": "#ffffff",       // White
          "info": "#0ea5e9",           // Sky-500
          "success": "#22c55e",        // Green-500
          "warning": "#f59e0b",        // Amber-500
          "error": "#ef4444",          // Red-500
        },
      },
      'light', 'dark', 'cupcake', 'bumblebee', 'emerald', 'corporate', 'synthwave', 'retro', 'cyberpunk', 'valentine', 'halloween', 'garden', 'forest', 'aqua', 'lofi', 'pastel', 'fantasy', 'wireframe', 'black', 'luxury', 'dracula', 'cmyk', 'autumn', 'business', 'acid', 'lemonade', 'night', 'coffee', 'winter'
    ],
  },
}

