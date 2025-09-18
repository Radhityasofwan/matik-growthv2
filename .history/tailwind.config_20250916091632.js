/** @type {import('tailwindcss').Config} */
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        forms,
        require("daisyui")
    ],

    // Konfigurasi DaisyUI untuk tema "AquaLeads"
    daisyui: {
        themes: [
          {
            softblue: {
              "primary": "#3B82F6",          // Aksen utama, tombol primer
              "secondary": "#ECF4FF",        // Latar belakang hover, item aktif
              "accent": "#1FB2A6",           // Warna aksen sekunder (opsional)
              "neutral": "#1E293B",          // Warna teks gelap utama
              "base-100": "#ffffff",         // Latar belakang card, sidebar, navbar (putih bersih)
              "base-200": "#F8FAFC",         // Latar belakang utama halaman
              "base-300": "#E2E8F0",         // Warna border
              "info": "#3ABFF8",
              "success": "#10B981",
              "warning": "#F59E0B",
              "error": "#EF4444",
            },
          },
        ],
        darkTheme: false, // Kita akan handle dark mode manual via class 'dark' jika dibutuhkan nanti
    },
};
