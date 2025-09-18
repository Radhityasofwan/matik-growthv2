{{-- Halaman ini untuk menguji coba tema-tema DaisyUI --}}
@extends('layouts.app')

@section('title', 'UI Theme Lab')

@section('content')
<div
  x-data="{
    // Daftar tema yang akan ditampilkan di switcher
    themes: ['softblue', 'light', 'dark', 'cupcake', 'bumblebee', 'emerald', 'corporate', 'synthwave', 'retro', 'cyberpunk', 'valentine', 'halloween', 'garden', 'forest', 'aqua', 'lofi', 'pastel', 'fantasy', 'wireframe', 'black', 'luxury', 'dracula', 'cmyk', 'autumn', 'business', 'acid', 'lemonade', 'night', 'coffee', 'winter'],
    currentTheme: document.documentElement.getAttribute('data-theme') || 'softblue',
    setTheme(theme) {
      this.currentTheme = theme;
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
    }
  }"
  class="container mx-auto py-6"
>

  <div class="text-center" data-aos="fade-down">
    <h1 class="text-4xl font-bold text-base-content">🎨 Laboratorium Tema DaisyUI</h1>
    <p class="text-base-content/70 mt-2">Pilih tema di bawah untuk melihat perubahan pada semua komponen.</p>
  </div>

  {{-- Theme Switcher --}}
  <div class="my-8 p-4 bg-base-200 rounded-box" data-aos="fade-up">
    <div class="form-control">
      <label class="label"><span class="label-text">Pilih Tema</span></label>
      <select x-model="currentTheme" @change="setTheme($event.target.value)" class="select select-bordered w-full">
        <template x-for="theme in themes" :key="theme">
          <option :value="theme" x-text="theme.charAt(0).toUpperCase() + theme.slice(1)"></option>
        </template>
      </select>
    </div>
  </div>

  {{-- Kontainer Komponen Uji Coba --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Card -->
    <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
      <figure><img src="https://placehold.co/400x225/jpg" alt="Shoes" /></figure>
      <div class="card-body">
        <h2 class="card-title">
          Card Title
          <div class="badge badge-secondary">NEW</div>
        </h2>
        <p>Ini adalah contoh komponen card yang akan berubah warna sesuai tema.</p>
        <div class="card-actions justify-end">
          <div class="badge badge-outline">Fashion</div>
          <div class="badge badge-outline">Products</div>
        </div>
      </div>
    </div>

    <!-- Buttons & Alerts -->
    <div class="space-y-6" data-aos="fade-up" data-aos-delay="100">
      <div class="card bg-base-100 shadow-xl border border-base-300/50">
        <div class="card-body">
          <h2 class="card-title">Buttons</h2>
          <div class="flex flex-wrap gap-2 mt-2">
            <button class="btn">Default</button>
            <button class="btn btn-primary">Primary</button>
            <button class="btn btn-secondary">Secondary</button>
            <button class="btn btn-accent">Accent</button>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-xl border border-base-300/50">
        <div class="card-body">
            <h2 class="card-title">Alerts</h2>
            <div class="space-y-2 mt-2">
                <div class="alert alert-success"><span>Pesan sukses.</span></div>
                <div class="alert alert-warning"><span>Peringatan.</span></div>
                <div class="alert alert-error"><span>Terjadi kesalahan.</span></div>
            </div>
        </div>
      </div>

    </div>

  </div>
</div>
@endsection
