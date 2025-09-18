{{-- Halaman ini untuk menguji coba tema-tema DaisyUI --}}
@extends('layouts.app')

@section('title', 'UI Theme Lab (Final)')

@section('content')
<div
  x-data="{
    // Tema yang akan diuji
    themes: ['softblue', 'light', 'dark'],
    currentTheme: document.documentElement.getAttribute('data-theme') || 'softblue',
    setTheme(theme) {
      this.currentTheme = theme;
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
    }
  }"
  class="container mx-auto py-8 space-y-8"
>

  {{-- Header dan Theme Switcher --}}
  <div class="text-center" data-aos="fade-down">
    <h1 class="text-4xl font-bold text-base-content">🎨 Laboratorium UI/UX Final</h1>
    <p class="text-base-content/70 mt-2">Uji coba komprehensif untuk semua komponen visual dan interaksi.</p>
    <div class="join mt-6">
        <button @click="setTheme('softblue')" class="btn join-item" :class="{'btn-primary': currentTheme === 'softblue'}">Softblue</button>
        <button @click="setTheme('light')" class="btn join-item" :class="{'btn-primary': currentTheme === 'light'}">Light</button>
        <button @click="setTheme('dark')" class="btn join-item" :class="{'btn-primary': currentTheme === 'dark'}">Dark</button>
    </div>
  </div>

  {{-- 1. Warna dan Tonalitas --}}
  <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
        <h2 class="card-title">1. Palet Warna & Tonalitas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-4 mt-4 text-center">
            <div><div class="w-full h-16 rounded-lg bg-primary shadow-inner"></div><p class="text-xs mt-1">Primary</p></div>
            <div><div class="w-full h-16 rounded-lg bg-secondary shadow-inner"></div><p class="text-xs mt-1">Secondary</p></div>
            <div><div class="w-full h-16 rounded-lg bg-accent shadow-inner"></div><p class="text-xs mt-1">Accent</p></div>
            <div><div class="w-full h-16 rounded-lg bg-neutral shadow-inner"></div><p class="text-xs mt-1">Neutral</p></div>
            <div class="col-span-2 sm:col-span-4 md:col-span-1 grid grid-cols-3 gap-2 ring-1 ring-base-300 p-2 rounded-lg">
                <div class="text-center"><div class="w-full h-12 rounded bg-base-100 ring-1 ring-inset ring-base-300"></div><p class="text-xs mt-1">Base-100</p></div>
                <div class="text-center"><div class="w-full h-12 rounded bg-base-200 ring-1 ring-inset ring-base-300"></div><p class="text-xs mt-1">Base-200</p></div>
                <div class="text-center"><div class="w-full h-12 rounded bg-base-300 ring-1 ring-inset ring-base-300"></div><p class="text-xs mt-1">Base-300</p></div>
            </div>
        </div>
    </div>
  </div>

  {{-- 2. Tipografi & Konten --}}
  <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
    <div class="card-body prose max-w-none">
        <h2 class="card-title not-prose mb-4">2. Tipografi</h2>
        <h1>Heading 1</h1>
        <p>Ini adalah paragraf standar. Dilengkapi dengan <strong>teks tebal</strong>, <em>teks miring</em>, dan <a href="#">sebuah link</a>. Kode inline seperti <code>const a = 1;</code> juga didukung.</p>
        <h2>Heading 2</h2>
        <blockquote>Ini adalah blockquote untuk menyorot teks penting. Blockquote ini dirancang untuk memiliki kontras yang baik di semua tema.</blockquote>
        <h3>Heading 3</h3>
        <ul>
            <li>List item satu</li>
            <li>List item dua</li>
        </ul>
    </div>
  </div>

  {{-- 3. Komponen Umum & Feedback --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">3. Buttons & Badges</h2>
            <div class="flex flex-wrap gap-2 mt-2">
                <button class="btn">Default</button>
                <button class="btn btn-primary">Primary</button>
                <button class="btn btn-secondary btn-outline">Outline</button>
                <button class="btn btn-accent btn-ghost">Ghost</button>
            </div>
            <div class="flex flex-wrap gap-2 mt-4">
                <span class="badge">Default</span>
                <span class="badge badge-primary">Primary</span>
                <span class="badge badge-secondary badge-outline">Outline</span>
                <span class="badge badge-accent">Accent</span>
            </div>
        </div>
      </div>
      <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">4. Alerts & Progress</h2>
             <div class="space-y-2 mt-2">
                <div class="alert alert-info"><span>Informasi penting.</span></div>
                <div class="alert alert-success"><span>Tindakan berhasil.</span></div>
            </div>
            <progress class="progress progress-primary w-full mt-4" value="70" max="100"></progress>
        </div>
      </div>
  </div>

  {{-- 5. Interaksi & Navigasi --}}
  <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
      <div class="card-body">
        <h2 class="card-title">5. Interaksi & Navigasi</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            {{-- Dropdown & Modal --}}
            <div class="space-y-4">
                <div class="dropdown">
                    <label tabindex="0" class="btn btn-primary m-1">Dropdown</label>
                    <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 border border-base-300/50">
                        <li><a>Item 1</a></li>
                        <li><a>Item 2</a></li>
                    </ul>
                </div>
                <a href="#demo_modal" class="btn">Buka Modal</a>
            </div>
            {{-- Tabs --}}
            <div x-data="{ tab: 'satu' }">
                <div class="tabs tabs-boxed">
                    <a class="tab" @click.prevent="tab = 'satu'" :class="{'tab-active': tab === 'satu'}">Tab 1</a>
                    <a class="tab" @click.prevent="tab = 'dua'" :class="{'tab-active': tab === 'dua'}">Tab 2</a>
                    <a class="tab" @click.prevent="tab = 'tiga'" :class="{'tab-active': tab === 'tiga'}">Tab 3</a>
                </div>
                <div class="p-4 bg-base-200 rounded-b-box">
                    <p x-show="tab === 'satu'">Konten untuk Tab 1.</p>
                    <p x-show="tab === 'dua'">Konten untuk Tab 2.</p>
                    <p x-show="tab === 'tiga'">Konten untuk Tab 3.</p>
                </div>
            </div>
            {{-- Collapse --}}
            <div class="collapse collapse-arrow bg-base-200">
                <input type="radio" name="my-accordion-2" checked="checked" />
                <div class="collapse-title text-xl font-medium">Accordion 1</div>
                <div class="collapse-content">
                    <p>Konten di dalam accordion.</p>
                </div>
            </div>
            {{-- Steps --}}
            <ul class="steps w-full">
              <li class="step step-primary">Register</li>
              <li class="step step-primary">Choose plan</li>
              <li class="step">Purchase</li>
              <li class="step">Receive Product</li>
            </ul>
        </div>
      </div>
  </div>

  {{-- 6. Tampilan Data --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">6. Stats & Avatar</h2>
            <div class="stats stats-vertical lg:stats-horizontal shadow mt-2">
              <div class="stat">
                <div class="stat-title">Downloads</div>
                <div class="stat-value">31K</div>
              </div>
              <div class="stat">
                <div class="stat-title">New Users</div>
                <div class="stat-value">4,200</div>
              </div>
            </div>
            <div class="avatar-group -space-x-6 mt-4">
              <div class="avatar"><div class="w-12"><img src="https://placehold.co/100x100/jpg" /></div></div>
              <div class="avatar"><div class="w-12"><img src="https://placehold.co/100x100/jpg" /></div></div>
              <div class="avatar placeholder"><div class="w-12 bg-neutral-focus text-neutral-content"><span>+99</span></div></div>
            </div>
        </div>
      </div>
      <div class="card bg-base-100 shadow-xl border border-base-300/50 md:col-span-2" data-aos="fade-up">
        <div class="card-body">
            <h2 class="card-title">7. Tabel Data</h2>
            <div class="overflow-x-auto mt-2">
              <table class="table table-zebra w-full">
                <thead><tr><th>Nama</th><th>Pekerjaan</th><th>Warna Favorit</th></tr></thead>
                <tbody>
                  <tr><td>Cy Ganderton</td><td>Quality Control Specialist</td><td>Blue</td></tr>
                  <tr><td>Hart Hagerty</td><td>Desktop Support Technician</td><td>Purple</td></tr>
                  <tr><td>Brice Swyre</td><td>Tax Accountant</td><td>Red</td></tr>
                </tbody>
              </table>
            </div>
        </div>
      </div>
  </div>

  {{-- 8. Chart (ApexCharts) --}}
  <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">8. Chart Interaktif (ApexCharts)</h2>
      <div x-data="apexChartDemo" x-init="init()" class="w-full h-80 min-h-[320px] mt-4">
        <div x-show="isLoading" class="skeleton w-full h-full"></div>
        <div id="chart-demo" x-show="!isLoading" class="w-full h-full" x-cloak></div>
      </div>
    </div>
  </div>

  {{-- 9. Form & Interaksi Lain --}}
  <div class="card bg-base-100 shadow-xl border border-base-300/50" data-aos="fade-up" x-data="{ toastVisible: false }">
    <div class="card-body">
        <h2 class="card-title">9. Form & Interaksi Lain</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
            <div class="form-control">
                <label class="label"><span class="label-text">Input Teks</span></label>
                <input type="text" placeholder="Ketik di sini" class="input input-bordered w-full" />
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text">Pilihan</span></label>
                <select class="select select-bordered"><option>Pilihan 1</option><option>Pilihan 2</option></select>
            </div>
            <div class="form-control">
                 <label class="label cursor-pointer"><span class="label-text">Checkbox</span>
                 <input type="checkbox" checked="checked" class="checkbox checkbox-primary" /></label>
            </div>
            <div class="flex items-center gap-4">
                <div class="tooltip" data-tip="Ini adalah tooltip"><button class="btn">Tooltip</button></div>
                <button class="btn btn-accent" @click="toastVisible = true; setTimeout(() => toastVisible = false, 3000)">Tampilkan Toast</button>
            </div>
        </div>
        {{-- Toast Container --}}
        <div class="toast toast-bottom toast-end" x-show="toastVisible" x-transition>
          <div class="alert alert-info"><span>Pesan baru muncul.</span></div>
        </div>
    </div>
  </div>
</div>

{{-- Modal --}}
<div id="demo_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Halo!</h3>
    <p class="py-4">Ini adalah contoh modal dari DaisyUI.</p>
    <div class="modal-action">
      <a href="#" class="btn">Tutup</a>
    </div>
  </div>
   <a href="#" class="modal-backdrop">Close</a>
</div>
@endsection

@push('scripts')
{{-- Memuat ApexCharts dari CDN untuk stabilitas --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('apexChartDemo', () => ({
        isLoading: true,
        chart: null,
        init() {
            // Menunggu ApexCharts siap
            const checkApex = () => {
                if (window.ApexCharts) {
                    setTimeout(() => {
                        this.isLoading = false;
                        this.$nextTick(() => this.renderChart());
                    }, 500);

                    // Re-render saat tema berubah
                    const observer = new MutationObserver(() => this.renderChart());
                    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
                } else {
                    setTimeout(checkApex, 50);
                }
            };
            checkApex();
        },
        renderChart() {
            const el = document.getElementById("chart-demo");
            if (!el || typeof ApexCharts === 'undefined') return;
            if (this.chart) this.chart.destroy();

            // Mendapatkan warna dari variabel CSS DaisyUI
            const styles = getComputedStyle(document.documentElement);
            const primaryColor = styles.getPropertyValue('--p').trim();
            const accentColor = styles.getPropertyValue('--a').trim();
            const baseContent = styles.getPropertyValue('--bc').trim();
            const gridColor = `hsla(${baseContent} / 0.1)`;
            const labelColor = `hsla(${baseContent} / 0.6)`;
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

            const options = {
                series: [
                    { name: 'Penjualan', data: [31, 40, 28, 51, 42, 109, 100] },
                    { name: 'Kunjungan', data: [11, 32, 45, 32, 34, 52, 41] }
                ],
                chart: { type: 'area', height: '100%', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                colors: [`hsl(${primaryColor})`, `hsl(${accentColor})`],
                fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.05, stops: [0, 95, 100] } },
                xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'], labels: { style: { colors: labelColor } }, axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false } },
                yaxis: { labels: { style: { colors: labelColor } } },
                tooltip: { theme: isDark ? 'dark' : 'light' },
                grid: { borderColor: gridColor, strokeDashArray: 4 },
                legend: { labels: { colors: labelColor } }
            };
            this.chart = new ApexCharts(el, options);
            this.chart.render();
        }
    }));
});
</script>
@endpush

