import './bootstrap';

// Import dan inisialisasi Alpine.js (sudah benar)
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();


// --- TAMBAHAN UNTUK SINKRONISASI ---
// Import AOS untuk animasi saat scroll
import AOS from 'aos';
import 'aos/dist/aos.css'; // Import CSS untuk AOS

// Inisialisasi AOS
// Anda bisa menambahkan konfigurasi default di sini jika perlu
// https://github.com/michalsnik/aos
AOS.init({
  duration: 800, // durasi animasi
  once: true, // jalankan animasi hanya sekali
});
