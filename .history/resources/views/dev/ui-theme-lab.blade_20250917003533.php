@extends('layouts.app')

@section('title', 'UI Theme Lab')

@section('content')
<section x-data="{ theme: 'softblue' }" :data-theme="theme" class="space-y-8">

  {{-- THEME SWITCHER --}}
  <div class="flex justify-between items-center">
    <h1 class="text-3xl font-extrabold">🎨 UI/UX Theme Lab</h1>
    <div class="flex gap-2">
      <button class="btn btn-sm btn-primary" @click="theme='softblue'">Softblue</button>
      <button class="btn btn-sm btn-secondary" @click="theme='light'">Light</button>
      <button class="btn btn-sm btn-accent" @click="theme='dark'">Dark</button>
    </div>
  </div>
  <p class="text-neutral/70">Eksperimen seluruh komponen, warna, interaksi, dan chart di satu halaman.</p>

  {{-- HEADER GRADIENT --}}
  <div class="rounded-2xl p-6 bg-gradient-to-r from-brand-500 via-highlight-pink to-highlight-cyan text-white shadow-lg" data-aos="fade-up">
    <h2 class="text-4xl font-extrabold">Modern & Catchy</h2>
    <p class="mt-1 opacity-90">Softblue, Light, Dark — clean, interaktif, dan responsif.</p>
  </div>

  {{-- COLOR SWATCHES --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Color Palette</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 gap-4 mt-4">
        <div class="w-full h-16 rounded-lg bg-primary"></div>
        <div class="w-full h-16 rounded-lg bg-secondary"></div>
        <div class="w-full h-16 rounded-lg bg-accent"></div>
        <div class="w-full h-16 rounded-lg bg-neutral"></div>
        <div class="w-full h-16 rounded-lg bg-base-100 border"></div>
        <div class="w-full h-16 rounded-lg bg-base-200 border"></div>
        <div class="w-full h-16 rounded-lg bg-base-300 border"></div>
        <div class="w-full h-16 rounded-lg bg-info"></div>
        <div class="w-full h-16 rounded-lg bg-success"></div>
        <div class="w-full h-16 rounded-lg bg-warning"></div>
        <div class="w-full h-16 rounded-lg bg-error"></div>
      </div>
    </div>
  </div>

  {{-- TYPOGRAPHY --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body prose max-w-none">
      <h2 class="card-title">Typography</h2>
      <h1>Heading 1</h1>
      <h2>Heading 2</h2>
      <h3>Heading 3</h3>
      <p>Body text <strong>bold</strong>, <em>italic</em>, <a href="#">link</a>.</p>
      <code>Code snippet</code>
    </div>
  </div>

  {{-- BUTTONS --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Buttons</h2>
      <div class="flex flex-wrap gap-2 mt-4">
        <button class="btn">Default</button>
        <button class="btn btn-primary">Primary</button>
        <button class="btn btn-secondary">Secondary</button>
        <button class="btn btn-accent">Accent</button>
        <button class="btn btn-outline btn-primary">Outline</button>
        <button class="btn btn-ghost">Ghost</button>
        <button class="btn btn-link">Link</button>
        <button class="btn btn-primary" disabled>Disabled</button>
      </div>
    </div>
  </div>

  {{-- FORMS --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Forms</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <input type="text" placeholder="Input text" class="input input-bordered w-full" />
        <input type="text" placeholder="Input error" class="input input-bordered input-error w-full" />
        <select class="select select-bordered">
          <option>Option 1</option><option>Option 2</option>
        </select>
        <label class="label cursor-pointer">
          <span class="label-text">Checkbox</span>
          <input type="checkbox" class="checkbox checkbox-primary" checked />
        </label>
      </div>
    </div>
  </div>

  {{-- INTERACTIVE CARDS --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4" data-aos="fade-up">
    <div class="card bg-base-200 hover:scale-105 transition-transform duration-300">
      <div class="card-body text-center">
        <h3 class="card-title">Hover Scale</h3>
        <p>Micro interaction.</p>
      </div>
    </div>
    <div class="card bg-base-200 hover:bg-gradient-to-r hover:from-brand-400 hover:to-brand-600 text-neutral-content transition-all duration-500">
      <div class="card-body text-center">
        <h3 class="card-title">Gradient Hover</h3>
        <p>Catchy and modern.</p>
      </div>
    </div>
    <div class="card bg-base-200">
      <div class="card-body text-center">
        <h3 class="card-title">Static Card</h3>
        <p>Just for baseline.</p>
      </div>
    </div>
  </div>

  {{-- MODAL --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Modal</h2>
      <button class="btn btn-primary mt-2" onclick="demo_modal.showModal()">Open Modal</button>
      <dialog id="demo_modal" class="modal">
        <div class="modal-box">
          <h3 class="font-bold text-lg">Hello!</h3>
          <p class="py-4">Modal dari DaisyUI berfungsi baik.</p>
          <div class="modal-action">
            <form method="dialog"><button class="btn">Close</button></form>
          </div>
        </div>
      </dialog>
    </div>
  </div>

  {{-- CHART --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Interactive Chart (ApexCharts)</h2>
      <div id="chart-skeleton" class="skeleton h-64 w-full mt-4"></div>
      <div id="chart-demo" class="mt-4"></div>
    </div>
  </div>

  {{-- AOS DEMO --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">AOS Animation</h2>
      <div class="mt-4 p-8 bg-secondary rounded-lg text-center font-semibold text-primary" data-aos="fade-up">
        Fade Up on Scroll
      </div>
    </div>
  </div>

</section>
@endsection
