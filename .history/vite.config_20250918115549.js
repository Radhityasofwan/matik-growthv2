import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import path from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources'),
    },
  },
  optimizeDeps: {
    // Menambahkan 'sortablejs' untuk membantu Vite menemukannya
    include: ['alpinejs', 'aos', 'apexcharts', 'sortablejs'],
  },
  build: {
    sourcemap: false,
    cssMinify: true,
  },
})

