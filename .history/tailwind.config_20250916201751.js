import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
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

    // --- PASTIKAN BAGIAN INI SESUAI ---
    // Konfigurasi ini mendefinisikan tema 'softblue' dan menjadikannya
    // satu-satunya tema yang aktif untuk menghindari konflik warna.
    daisyui: {
        themes: [
          {
            softblue: {
              "primary": "#3B82F6",       // Biru untuk tombol utama, link, status aktif
              "secondary": "#ECF4FF",     // Biru pucat untuk latar belakang section/hover
              "accent": "#1FB2A6",       // Warna aksen (opsional)
              "neutral": "#1E293B",      // Abu-abu gelap untuk teks utama
              "base-100": "#ffffff",     // Latar belakang card/komponen (putih)
              "base-200": "#F8FAFC",     // Latar belakang halaman utama (putih keabuan)
              "base-300": "#E2E8F0",     // Warna border
              "info": "#3ABFF8",
              "success": "#10B981",
              "warning": "#F59E0B",
              "error": "#EF4444",
            },
          },
        ],
      },
};

