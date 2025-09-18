@extends('layouts.app')

@section('title', 'UI Theme Lab')

@section('content')
<section
  x-data="{
    theme: 'softblue',
    setTheme(t){
      this.theme = t
      // update seluruh halaman (html[data-theme] + class dark)
      document.documentElement.setAttribute('data-theme', t)
      document.documentElement.classList.toggle('dark', t === 'dark')
    }
  }"
  x-init="
    // ambil tema awal dari <html data-theme>
    theme = document.documentElement.getAttribute('data-theme') || 'softblue'
    document.documentElement.classList.toggle('dark', theme === 'dark')
  "
  class="space-y-10"
>

  {{-- THEME SWITCHER --}}
  <div class="flex justify-between items-center">
    <h1 class="text-3xl font-extrabold">🎨 UI/UX Theme Lab</h1>
    <div class="flex gap-2">
      <button class="btn btn-sm btn-primary"  @click="setTheme('softblue')">Softblue</button>
      <button class="btn btn-sm btn-secondary"@click="setTheme('light')">Light</button>
      <button class="btn btn-sm btn-accent"   @click="setTheme('dark')">Dark</button>
    </div>
  </div>
  <p class="text-neutral/70">Halaman laboratorium untuk menguji komponen visual, interaksi, efek, dan chart secara end-to-end.</p>

  {{-- HERO SECTION (gradient brand) --}}
  <div class="rounded-2xl p-8 bg-gradient-to-r from-brand-500 via-highlight-pink to-highlight-cyan text-white shadow-xl" data-aos="fade-up">
    <h2 class="text-4xl font-extrabold">Modern • Fresh • Catchy</h2>
    <p class="mt-2 text-lg opacity-90">UI responsif & interaktif selaras dengan tema <b>softblue</b>, <b>light</b>, dan <b>dark</b>.</p>
  </div>

  {{-- CHART interaktif (ApexCharts) --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Interactive Chart (ApexCharts)</h2>
      <div id="chart-skeleton" class="skeleton h-64 w-full mt-4"></div>
      <div id="chart-demo" class="mt-4"></div>
    </div>
  </div>

  {{-- COLOR SWATCHES (semua kelas statis agar tidak ter-purge) --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">🎨 Color Palette</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 gap-4 mt-4">
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-primary shadow-inner border border-black/10"></div><p class="text-xs mt-1">primary</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-secondary shadow-inner border border-black/10"></div><p class="text-xs mt-1">secondary</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-accent shadow-inner border border-black/10"></div><p class="text-xs mt-1">accent</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-neutral shadow-inner border border-black/10"></div><p class="text-xs mt-1">neutral</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-base-100 shadow-inner border border-black/10"></div><p class="text-xs mt-1">base-100</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-base-200 shadow-inner border border-black/10"></div><p class="text-xs mt-1">base-200</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-base-300 shadow-inner border border-black/10"></div><p class="text-xs mt-1">base-300</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-info shadow-inner border border-black/10"></div><p class="text-xs mt-1">info</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-success shadow-inner border border-black/10"></div><p class="text-xs mt-1">success</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-warning shadow-inner border border-black/10"></div><p class="text-xs mt-1">warning</p></div>
        <div class="text-center"><div class="w-full h-16 rounded-lg bg-error shadow-inner border border-black/10"></div><p class="text-xs mt-1">error</p></div>
      </div>

      {{-- demo kontras Tailwind dark: --}}
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-4 rounded-xl bg-base-100 dark:bg-neutral text-neutral dark:text-base-100 border border-base-300">
          <div class="font-semibold">Tailwind <code>dark:</code> variant</div>
          <div class="text-sm opacity-80">Box ini berubah mengikuti kelas <code>dark</code> pada &lt;html&gt;.</div>
        </div>
        <div class="p-4 rounded-xl bg-primary text-primary-content border border-primary">
          <div class="font-semibold">DaisyUI Primary</div>
          <div class="text-sm opacity-80">Kontras teks mengikuti <code>*-content</code>.</div>
        </div>
        <div class="p-4 rounded-xl bg-secondary text-secondary-content border border-secondary">
          <div class="font-semibold">DaisyUI Secondary</div>
          <div class="text-sm opacity-80">Highlight sekunder.</div>
        </div>
      </div>
    </div>
  </div>

  {{-- TYPOGRAPHY --}}
  <div class="card bg-base-100 shadow-md border border-base-300/50" data-aos="fade-up">
    <div class="card-body prose max-w-none">
      <h2 class="card-title">✍️ Typography</h2>
      <h1>Heading 1</h1>
      <h2>Heading 2</h2>
      <h3>Heading 3</h3>
      <p>Body text dengan <strong>bold</strong>, <em>italic</em>, <a href="#">link</a>, dan <code>inline code</code>.</p>
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
        <button class="btn btn-link">Link</button>
        <button class="btn btn-primary" disabled>Disabled</button>
        <span class="badge">Default</span>
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
        <div class="form-control">
          <label class="label"><span class="label-text">Text</span></label>
          <input type="text" placeholder="Text input" class="input input-bordered w-full" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Text (Error)</span></label>
          <input type="text" placeholder="Error input" class="input input-bordered input-error w-full" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Select</span></label>
          <select class="select select-bordered w-full">
            <option>Option 1</option><option>Option 2</option>
          </select>
        </div>
        <div class="form-control">
          <label class="label cursor-pointer"><span class="label-text">Checkbox</span>
            <input type="checkbox" class="checkbox checkbox-primary" checked />
          </label>
        </div>
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
