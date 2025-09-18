/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  darkMode: 'class', // Enable class-based dark mode
  theme: {
    extend: {
        fontFamily: {
            sans: ['Inter', 'sans-serif'],
        },
    },
  },
  plugins: [
    require('daisyui'), // Tambahkan plugin DaisyUI
  ],
  daisyui: {
    themes: ["light", "dark"], // Aktifkan tema light & dark dari DaisyUI
  },
}
