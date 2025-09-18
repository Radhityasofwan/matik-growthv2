@extends('layouts.app')

@section('title', 'UI Theme Lab')

@section('content')
<section x-data="{ theme: 'softblue', sidebar: false }" :data-theme="theme" class="space-y-10">

  {{-- THEME SWITCHER --}}
  <div class="flex justify-between items-center">
    <h1 class="text-3xl font-extrabold">🎨 UI/UX Theme Lab</h1>
    <div class="flex gap-2">
      <button class="btn btn-sm btn-primary" @click="theme='softblue'">Softblue</button>
      <button class="btn btn-sm btn-secondary" @click="theme='light'">Light</button>
      <button class="btn btn-sm btn-accent" @click="theme='dark'">Dark</button>
    </div>
  </div>
  <p class="text-neutral/70">Halaman laboratorium untuk menguji semua komponen visual, interaksi, efek, dan chart.</p>

  {{-- HERO SECTION --}}
  <div class="rounded-2xl p-8 bg-gradient-to-r from-brand-500 via-highlight-pink to-highlight-cyan text-white shadow-xl" data-aos="fade-up">
    <h2 class="text-4xl font-extrabold">Modern • Fresh • Catchy</h2>
    <p class="mt-2 text-lg opacity-90">UI yang responsif, interaktif, dan selaras dengan tema <strong>softblue</strong>, <strong>light</strong>, dan <strong>dark</strong>.</p>
  </div>

  {{-- COLOR SWATCHES --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">🎨 Color Palette</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 gap-4 mt-4">
        @foreach (['primary','secondary','accent','neutral','base-100','base-200','base-300','info','success','warning','error'] as $c)
          <div class="text-center">
            <div class="w-full h-16 rounded-lg bg-{{ $c }} shadow-inner border border-black/10"></div>
            <p class="text-xs mt-1">{{ $c }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- TYPOGRAPHY --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50 prose max-w-none" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">✍️ Typography</h2>
      <h1>Heading 1</h1>
      <h2>Heading 2</h2>
      <h3>Heading 3</h3>
      <p>Body text <strong>bold</strong>, <em>italic</em>, <a href="#">link</a>.</p>
      <blockquote>Inspirational quote goes here.</blockquote>
      <pre><code>console.log('Code block');</code></pre>
    </div>
  </div>

  {{-- BUTTONS & BADGES --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">🔘 Buttons & Badges</h2>
      <div class="flex flex-wrap gap-2">
        <button class="btn">Default</button>
        <button class="btn btn-primary">Primary</button>
        <button class="btn btn-secondary">Secondary</button>
        <button class="btn btn-accent">Accent</button>
        <button class="btn btn-outline btn-primary">Outline</button>
        <button class="btn btn-ghost">Ghost</button>
        <span class="badge badge-primary">Primary</span>
        <span class="badge badge-success">Success</span>
        <span class="badge badge-error">Error</span>
      </div>
    </div>
  </div>

  {{-- FORMS --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">📝 Forms</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <input type="text" placeholder="Text input" class="input input-bordered w-full" />
        <input type="text" placeholder="Error input" class="input input-bordered input-error w-full" />
        <select class="select select-bordered w-full">
          <option>Option 1</option><option>Option 2</option>
        </select>
        <label class="label cursor-pointer">
          <span class="label-text">Checkbox</span>
          <input type="checkbox" class="checkbox checkbox-primary" checked />
        </label>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50 overflow-x-auto" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">📊 Table</h2>
      <table class="table table-zebra">
        <thead>
          <tr><th>#</th><th>Name</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr><td>1</td><td>Alice</td><td><span class="badge badge-success">Active</span></td></tr>
          <tr><td>2</td><td>Bob</td><td><span class="badge badge-error">Inactive</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- INTERACTIVE CARDS --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4" data-aos="fade-up">
    <div class="card bg-base-200 hover:scale-105 transition duration-300">
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
        <p>Baseline example.</p>
      </div>
    </div>
  </div>

  {{-- ALERTS --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body space-y-2">
      <h2 class="card-title">⚠️ Alerts</h2>
      <div class="alert alert-info">Info message</div>
      <div class="alert alert-success">Success message</div>
      <div class="alert alert-warning">Warning message</div>
      <div class="alert alert-error">Error message</div>
    </div>
  </div>

  {{-- MODAL --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">🪟 Modal</h2>
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
      <h2 class="card-title">📈 Interactive Chart</h2>
      <div id="chart-skeleton" class="skeleton h-64 w-full mt-4"></div>
      <div id="chart-demo" class="mt-4"></div>
    </div>
  </div>

  {{-- AOS DEMO --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">✨ AOS Animation</h2>
      <div class="mt-4 p-8 bg-secondary rounded-lg text-center font-semibold text-primary" data-aos="zoom-in">
        Zoom In on Scroll
      </div>
    </div>
  </div>

</section>
@endsection
