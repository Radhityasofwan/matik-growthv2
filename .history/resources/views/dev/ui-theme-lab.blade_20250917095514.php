@extends('layouts.app')

@section('title', 'DaisyUI Full Lab')

@section('content')
<div class="space-y-10">

  {{-- Header --}}
  <section class="space-y-4">
    <h1 class="text-2xl font-bold">DaisyUI Full Lab</h1>
    <p class="text-base-content/70">
      Halaman ini menguji <strong>semua komponen utama</strong> DaisyUI + util Tailwind.
      Jika ada bagian yang tidak memiliki warna/gaya, berarti tema belum terpasang/ter-compile.
    </p>
    <div class="flex gap-2">
      <button class="btn btn-sm" @click="setTheme('softblue')">Softblue</button>
      <button class="btn btn-sm" @click="setTheme('dark')">Dark</button>
      <button class="btn btn-sm" @click="setTheme(theme === 'dark' ? 'softblue' : 'dark')">Toggle Theme</button>
    </div>
  </section>

  {{-- Palette --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Palette & Tokens</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
      @foreach (['primary','secondary','accent','neutral','info','success','warning','error','base-100','base-200','base-300'] as $color)
        <div class="rounded-lg p-4 text-sm font-medium text-base-100 flex flex-col justify-between"
             :class="'bg-' + '{{ $color }}'">
          <span>{{ $color }}</span>
          <span class="text-xs opacity-80">Class: bg-{{ $color }}</span>
        </div>
      @endforeach
    </div>
  </section>

  {{-- Typography --}}
  <section class="prose max-w-none">
    <h2>Typography & Helpers</h2>
    <h1>Heading 1</h1>
    <h2>Heading 2</h2>
    <p>
      Paragraf dengan <a href="#">link</a> dan <code>inline code</code>.
      Tekan <kbd>⌘</kbd>+<kbd>K</kbd> untuk search.
    </p>
    <pre><code>console.log('Hello DaisyUI');</code></pre>
  </section>

  {{-- Buttons --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Buttons</h2>
    <div class="flex flex-wrap gap-2">
      <button class="btn btn-primary">Primary</button>
      <button class="btn btn-secondary">Secondary</button>
      <button class="btn btn-accent">Accent</button>
      <button class="btn btn-info">Info</button>
      <button class="btn btn-success">Success</button>
      <button class="btn btn-warning">Warning</button>
      <button class="btn btn-error">Error</button>
      <button class="btn btn-outline">Outline</button>
      <button class="btn btn-ghost">Ghost</button>
    </div>
  </section>

  {{-- Alerts & Badges --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Alerts & Badges</h2>
    <div class="space-y-2">
      <div class="alert alert-info">Info alert</div>
      <div class="alert alert-success">Success alert</div>
      <div class="alert alert-warning">Warning alert</div>
      <div class="alert alert-error">Error alert</div>
    </div>
    <div class="flex gap-2">
      <span class="badge badge-primary">Primary</span>
      <span class="badge badge-secondary">Secondary</span>
      <span class="badge badge-accent">Accent</span>
      <span class="badge badge-info">Info</span>
      <span class="badge badge-success">Success</span>
      <span class="badge badge-warning">Warning</span>
      <span class="badge badge-error">Error</span>
    </div>
  </section>

  {{-- Forms --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Forms</h2>
    <form class="space-y-4 max-w-md">
      <label class="form-control">
        <span class="label-text">Email</span>
        <input type="email" placeholder="email@domain.com" class="input input-bordered w-full" />
      </label>
      <label class="form-control">
        <span class="label-text">Password</span>
        <input type="password" placeholder="••••••••" class="input input-bordered w-full" />
      </label>
      <label class="flex items-center gap-2">
        <input type="checkbox" class="checkbox" /> <span>Ingat saya</span>
      </label>
      <button class="btn btn-primary w-full">Login</button>
    </form>
  </section>

  {{-- Table --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Table</h2>
    <div class="overflow-x-auto">
      <table class="table table-zebra w-full">
        <thead>
          <tr>
            <th></th><th>Nama</th><th>Pekerjaan</th><th>Favorit</th>
          </tr>
        </thead>
        <tbody>
          <tr><th>1</th><td>Cy Ganderton</td><td>Quality Control</td><td>Red</td></tr>
          <tr><th>2</th><td>Hart Hagerty</td><td>Designer</td><td>Blue</td></tr>
          <tr><th>3</th><td>Brice Swyre</td><td>Developer</td><td>Purple</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  {{-- Cards & Stats --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Cards & Stats</h2>
    <div class="grid md:grid-cols-3 gap-4">
      <div class="card bg-base-100 shadow">
        <div class="card-body">
          <h3 class="card-title">Card Title</h3>
          <p>Isi card contoh</p>
          <div class="card-actions">
            <button class="btn btn-primary">Action</button>
          </div>
        </div>
      </div>
      <div class="stats shadow">
        <div class="stat">
          <div class="stat-title">Leads</div>
          <div class="stat-value">123</div>
          <div class="stat-desc">↗︎ 23%</div>
        </div>
      </div>
      <div class="card bg-primary text-primary-content shadow">
        <div class="card-body">
          <h3 class="card-title">Themed Card</h3>
          <p>Dengan warna primary</p>
        </div>
      </div>
    </div>
  </section>

  {{-- Modal & Dropdown --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Modal & Dropdown</h2>
    <label for="lab-modal" class="btn btn-primary">Open Modal</label>
    <input type="checkbox" id="lab-modal" class="modal-toggle" />
    <div class="modal">
      <div class="modal-box">
        <h3 class="font-bold">Hello!</h3>
        <p>Isi modal disini</p>
        <div class="modal-action">
          <label for="lab-modal" class="btn">Close</label>
        </div>
      </div>
    </div>
    <div class="dropdown">
      <label tabindex="0" class="btn btn-secondary m-1">Dropdown</label>
      <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
        <li><a>Item 1</a></li>
        <li><a>Item 2</a></li>
      </ul>
    </div>
  </section>

  {{-- Tabs, Collapse, Progress --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Tabs, Collapse, Progress</h2>
    <div class="tabs tabs-boxed">
      <a class="tab tab-active">Tab 1</a>
      <a class="tab">Tab 2</a>
      <a class="tab">Tab 3</a>
    </div>
    <div class="collapse collapse-arrow border border-base-300 bg-base-100">
      <input type="checkbox" />
      <div class="collapse-title">Klik untuk expand</div>
      <div class="collapse-content">Isi konten collapse</div>
    </div>
    <progress class="progress progress-primary w-56" value="70" max="100"></progress>
  </section>

  {{-- Steps & Avatars --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Steps & Avatars</h2>
    <ul class="steps">
      <li class="step step-primary">Step 1</li>
      <li class="step step-primary">Step 2</li>
      <li class="step">Step 3</li>
    </ul>
    <div class="avatar-group -space-x-6">
      <div class="avatar"><div class="w-12"><img src="https://i.pravatar.cc/100?img=1" /></div></div>
      <div class="avatar"><div class="w-12"><img src="https://i.pravatar.cc/100?img=2" /></div></div>
      <div class="avatar placeholder"><div class="w-12 bg-neutral text-neutral-content">+99</div></div>
    </div>
  </section>

  {{-- Toast & Tooltip --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Toast & Tooltip</h2>
    <div class="toast toast-start">
      <div class="alert alert-info">Toast info contoh</div>
    </div>
    <button class="btn" data-tip="Tooltip contoh">Hover me</button>
  </section>

</div>
@endsection
