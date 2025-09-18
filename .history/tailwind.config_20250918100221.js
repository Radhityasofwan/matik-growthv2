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
    themes: ["night"], // Tema default diatur di sini
    darkTheme: 'dark', // Tema gelap spesifik jika diperlukan
    logs: false, // Menonaktifkan log daisyUI di konsol
  },
}

