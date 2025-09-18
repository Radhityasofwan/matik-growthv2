@extends('layouts.app')

@section('title', 'UI/UX Theme Lab')

@section('content')
<section
    x-data="{
      theme: 'softblue',
      toast: { show:false, msg:'' },
      setTheme(t){ this.theme=t; document.documentElement.classList.toggle('dark', t==='dark') },
      notify(m){ this.toast.msg=m; this.toast.show=true; setTimeout(()=>this.toast.show=false,1500) }
    }"
    x-init="setTheme(theme)"
    :data-theme="theme"
    class="space-y-8">

  {{-- HEADER + THEME SWITCHER --}}
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-3xl font-bold text-neutral">UI/UX Theme Lab</h1>
      <p class="mt-1 text-neutral/60">End-to-end test: Tailwind dark:, DaisyUI themes, Alpine, AOS, ApexCharts, Forms, Typography.</p>
    </div>
    <div class="flex items-center gap-2">
      <div class="join">
        <button class="join-item btn btn-outline" @click="setTheme('softblue')">softblue</button>
        <button class="join-item btn btn-outline" @click="setTheme('light')">light</button>
        <button class="join-item btn btn-outline" @click="setTheme('dark')">dark</button>
      </div>
      <button class="btn btn-primary" @click="notify('Primary action!')">Primary</button>
    </div>
  </div>

  {{-- PALETTE (semua kelas statis agar pasti render) --}}
  @php
    $sw = [
      ['bg-primary','primary'],['bg-secondary','secondary'],['bg-accent','accent'],
      ['bg-neutral','neutral'],['bg-base-100','base-100'],['bg-base-200','base-200'],
      ['bg-base-300','base-300'],['bg-info','info'],['bg-success','success'],
      ['bg-warning','warning'],['bg-error','error'],
    ];
    $sem = ['primary','secondary','accent','neutral','info','success','warning','error'];
  @endphp

  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Color Palette (tema: <span class="lowercase" x-text="theme"></span>)</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 gap-4 mt-4">
        @foreach ($sw as [$cls,$name])
          <div class="text-center">
            <div class="w-full h-16 rounded-lg {{ $cls }} shadow-inner border border-black/10"></div>
            <p class="text-sm font-medium mt-2">{{ $name }}</p>
          </div>
        @endforeach
      </div>

      {{-- Tailwind dark: demo (bg/text berubah saat theme='dark') --}}
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-4 rounded-xl bg-base-100 dark:bg-neutral text-neutral dark:text-base-100 border border-base-300">
          <div class="font-semibold">Tailwind <code>dark:</code> variant</div>
          <div class="text-sm opacity-80">Box ini mengikuti class <code>dark</code> pada &lt;html&gt;.</div>
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

      {{-- Semantic matrix --}}
      <div class="mt-6">
        <h3 class="font-semibold mb-3">Semantic Matrix</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
          @foreach ($sem as $k)
            <div class="rounded-xl p-4 bg-base-200">
              <div class="flex items-center justify-between mb-2">
                <span class="font-medium capitalize">{{ $k }}</span>
                <span class="badge {{ 'badge-'.$k }}">badge-{{ $k }}</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 rounded border {{ 'border-'.$k }}">border-{{ $k }}</span>
                <span class="px-2 py-1 rounded {{ 'text-'.$k }}">text-{{ $k }}</span>
                <span class="px-2 py-1 rounded {{ 'bg-'.$k }} text-white/90">bg-{{ $k }}</span>
                <button class="btn btn-sm {{ 'btn-'.$k }}">btn-{{ $k }}</button>
              </div>
              <div class="mt-3 p-3 rounded bg-base-100 ring-2 {{ 'ring-'.$k }}">ring-{{ $k }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- TYPOGRAPHY --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Typography (@tailwindcss/typography)</h2>
      <article class="prose max-w-none mt-4">
        <h1>Heading 1</h1><h2>Heading 2</h2><h3>Heading 3</h3>
        <p>Body text with <strong>bold</strong>, <em>italic</em>, <a href="#">link</a>, <code>inline code</code>.</p>
        <pre><code>const hello = 'world'</code></pre>
        <ul><li>Item</li><li>Item</li></ul>
        <blockquote>Prose blockquote example.</blockquote>
      </article>
    </div>
  </div>

  {{-- FORMS --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Forms (@tailwindcss/forms)</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div class="form-control">
          <label class="label"><span class="label-text">Text</span></label>
          <input type="text" class="input input-bordered w-full" placeholder="Type here">
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Text (Error)</span></label>
          <input type="text" class="input input-bordered input-error w-full" placeholder="Invalid value">
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Select</span></label>
          <select class="select select-bordered"><option disabled selected>Pilih</option><option>1</option><option>2</option></select>
        </div>
        <div class="form-control">
          <label class="label cursor-pointer"><span class="label-text">Checkbox</span><input type="checkbox" class="checkbox checkbox-primary" checked></label>
          <label class="label cursor-pointer"><span class="label-text">Toggle</span><input type="checkbox" class="toggle toggle-primary" checked></label>
        </div>
        <div class="md:col-span-2 flex flex-wrap items-center gap-4">
          <input type="range" min="0" max="100" value="40" class="range range-primary w-64">
          <div class="rating">
            <input type="radio" name="r" class="mask mask-star-2 bg-warning"/><input type="radio" name="r" class="mask mask-star-2 bg-warning" checked/>
            <input type="radio" name="r" class="mask mask-star-2 bg-warning"/><input type="radio" name="r" class="mask mask-star-2 bg-warning"/><input type="radio" name="r" class="mask mask-star-2 bg-warning"/>
          </div>
          <progress class="progress progress-primary w-64" value="45" max="100"></progress>
        </div>
      </div>
    </div>
  </div>

  {{-- COMPONENTS: Buttons / Badges / Alerts / Tabs / Collapse / Dropdown / Tooltip --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Components</h2>
      <div class="flex flex-wrap gap-2 mt-3">
        <button class="btn">Default</button><button class="btn btn-primary">Primary</button>
        <button class="btn btn-secondary">Secondary</button><button class="btn btn-accent">Accent</button>
        <button class="btn btn-outline">Outline</button><button class="btn btn-outline btn-primary">Outline Primary</button>
        <button class="btn btn-ghost">Ghost</button><button class="btn btn-link">Link</button><button class="btn btn-primary" disabled>Disabled</button>
      </div>

      <div class="flex flex-wrap gap-2 mt-4">
        <div class="badge">Default</div><div class="badge badge-primary">Primary</div>
        <div class="badge badge-secondary">Secondary</div><div class="badge badge-accent">Accent</div>
        <div class="badge badge-outline">Outline</div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
        <div role="alert" class="alert alert-info"><span>Info alert</span></div>
        <div role="alert" class="alert alert-success"><span>Success alert</span></div>
        <div role="alert" class="alert alert-warning"><span>Warning alert</span></div>
        <div role="alert" class="alert alert-error"><span>Error alert</span></div>
      </div>

      <div class="tabs tabs-boxed mt-4">
        <a class="tab tab-active">Tab 1</a><a class="tab">Tab 2</a><a class="tab">Tab 3</a>
      </div>

      <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="collapse bg-base-200">
          <input type="checkbox"><div class="collapse-title text-lg font-medium">Accordion / Collapse</div>
          <div class="collapse-content"><p>Konten ini harus muncul/tersembunyi.</p></div>
        </div>

        <div class="flex items-center gap-3">
          <div class="dropdown">
            <button tabindex="0" class="btn btn-outline">Dropdown</button>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-40 p-2 shadow">
              <li><a>Item 1</a></li><li><a>Item 2</a></li>
            </ul>
          </div>
          <button class="btn btn-primary tooltip" data-tip="Tooltip example">Hover me</button>
        </div>
      </div>
    </div>
  </div>

  {{-- DATA: Table + Stat Card --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Data Components</h2>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        <div class="lg:col-span-2 overflow-x-auto">
          <table class="table">
            <thead><tr><th>Invoice</th><th>Customer</th><th>Status</th><th>Amount</th></tr></thead>
            <tbody>
              <tr><td>#INV-1001</td><td>John</td><td><span class="badge badge-success">Paid</span></td><td>$250</td></tr>
              <tr><td>#INV-1002</td><td>Mary</td><td><span class="badge badge-warning">Pending</span></td><td>$120</td></tr>
              <tr><td>#INV-1003</td><td>Alex</td><td><span class="badge badge-error">Failed</span></td><td>$90</td></tr>
            </tbody>
          </table>
        </div>
        <x-card.stat title="Revenue" value="$12,340" change="▲ 8.1% MoM" changeType="success">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </x-card.stat>
      </div>
    </div>
  </div>

  {{-- MODAL / DRAWER / TOAST --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Modal, Drawer, Toast</h2>
      <div class="flex flex-wrap items-center gap-2 mt-3">
        <button class="btn btn-primary" onclick="document.getElementById('demo_modal').showDialog?.() ?? document.getElementById('demo_modal').showModal()">Open Modal</button>
        <button class="btn btn-secondary" @click="$dispatch('toggle-drawer')">Open Drawer</button>
        <button class="btn btn-accent" @click="notify('Saved!')">Show Toast</button>
      </div>

      <dialog id="demo_modal" class="modal">
        <div class="modal-box">
          <h3 class="font-bold text-lg">Modal Title</h3>
          <p class="py-4">Jika ini tampil, modal bekerja.</p>
          <div class="modal-action">
            <form method="dialog"><button class="btn">Close</button></form>
          </div>
        </div>
      </dialog>

      <div x-data="{open:false}" @toggle-drawer.window="open=!open" class="drawer" :class="open ? 'drawer-open' : ''">
        <input type="checkbox" class="drawer-toggle" :checked="open" />
        <div class="drawer-content"></div>
        <div class="drawer-side">
          <label @click="open=false" class="drawer-overlay"></label>
          <ul class="menu p-4 w-80 min-h-full bg-base-200 text-base-content">
            <li class="menu-title">Side Menu</li>
            <li><a>Dashboard</a></li><li><a>Settings</a></li><li><a>Profile</a></li>
          </ul>
        </div>
      </div>

      <div class="toast toast-end" x-show="toast.show" x-transition>
        <div class="alert alert-success"><span x-text="toast.msg"></span></div>
      </div>
    </div>
  </div>

  {{-- CHART + SKELETON --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Chart (ApexCharts)</h2>
      <div id="chart-skeleton" class="skeleton h-56 w-full mt-4"></div>
      <div id="chart-demo" class="mt-4"></div>
    </div>
  </div>

  {{-- STICKY CTA MOBILE --}}
  <div class="md:hidden fixed inset-x-0 bottom-0 z-50">
    <div class="mx-4 mb-4 rounded-2xl shadow-lg bg-base-100 border border-base-300 p-3 flex items-center justify-between">
      <span class="font-medium">Ready to continue?</span>
      <button class="btn btn-primary btn-sm" @click="notify('Proceeding…')">Continue</button>
    </div>
  </div>
</section>
@endsection
