@extends('layouts.app')

@section('title', 'DaisyUI Full Lab')

@section('content')
<div
  x-data="themeLab()"
  x-init="init()"
  class="space-y-10"
>
  {{-- Controls --}}
  <section class="space-y-4">
    <h1 class="text-2xl font-bold">DaisyUI Full Lab</h1>
    <p class="text-base-content/70">
      Uji seluruh komponen & warna DaisyUI. Pilih tema bawaan di bawah ini (plus <strong>softblue</strong> custom).
    </p>

    <div class="flex flex-wrap items-center gap-2">
      <select class="select select-bordered select-sm w-full sm:w-72" x-model="current" @change="apply(current)">
        <optgroup label="Built-in Themes">
          <template x-for="name in builtins" :key="name">
            <option :value="name" x-text="name"></option>
          </template>
        </optgroup>
        <optgroup label="Custom">
          <option value="softblue">softblue</option>
        </optgroup>
      </select>
      <button class="btn btn-sm" @click="apply('light')">Light</button>
      <button class="btn btn-sm" @click="apply('dark')">Dark</button>
      <button class="btn btn-sm" @click="toggle()">Toggle</button>
    </div>
    <p class="text-xs opacity-70">Aktif: <span class="font-mono" x-text="document.documentElement.getAttribute('data-theme')"></span></p>
  </section>

  {{-- Palette & Tokens (pakai *-content agar kontras benar) --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Palette & Tokens</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
      @php
        $tokens = [
          ['primary', 'primary-content'],
          ['secondary', 'secondary-content'],
          ['accent', 'accent-content'],
          ['neutral', 'neutral-content'],
          ['info', 'info-content'],
          ['success', 'success-content'],
          ['warning', 'warning-content'],
          ['error', 'error-content'],
          ['base-100', 'base-content'],
          ['base-200', 'base-content'],
          ['base-300', 'base-content'],
        ];
      @endphp
      @foreach ($tokens as [$bg, $tc])
        <div class="rounded-lg p-4 text-sm font-medium flex flex-col justify-between border border-base-300"
             class="{{$bg}} {{$tc}}"
             :class="'bg-{{ $bg }} text-{{ $tc }}'">
          <span>{{ $bg }}</span>
          <span class="text-xs opacity-90">Class: bg-{{ $bg }} · text-{{ $tc }}</span>
        </div>
      @endforeach
    </div>
  </section>

  {{-- Buttons --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Buttons</h2>
    <div class="flex flex-wrap gap-2">
      <button class="btn">Default</button>
      <button class="btn btn-primary">Primary</button>
      <button class="btn btn-secondary">Secondary</button>
      <button class="btn btn-accent">Accent</button>
      <button class="btn btn-neutral">Neutral</button>
      <button class="btn btn-info">Info</button>
      <button class="btn btn-success">Success</button>
      <button class="btn btn-warning">Warning</button>
      <button class="btn btn-error">Error</button>
      <button class="btn btn-outline">Outline</button>
      <button class="btn btn-ghost">Ghost</button>
      <button class="btn btn-link">Link</button>
    </div>
  </section>

  {{-- Alerts & Badges --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Alerts & Badges</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2">
      <div class="alert alert-info">Info alert</div>
      <div class="alert alert-success">Success alert</div>
      <div class="alert alert-warning">Warning alert</div>
      <div class="alert alert-error">Error alert</div>
    </div>
    <div class="flex flex-wrap gap-2">
      <span class="badge">Default</span>
      <span class="badge badge-outline">Outline</span>
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
    <form class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-4xl">
      <label class="form-control">
        <span class="label-text">Email</span>
        <input type="email" placeholder="you@example.com" class="input input-bordered w-full" />
      </label>
      <label class="form-control">
        <span class="label-text">Password</span>
        <input type="password" placeholder="••••••••" class="input input-bordered w-full" />
      </label>
      <label class="form-control sm:col-span-2">
        <span class="label-text">Select</span>
        <select class="select select-bordered">
          <option>Option 1</option><option>Option 2</option>
        </select>
      </label>
      <div class="flex items-center gap-4 sm:col-span-2">
        <label class="label cursor-pointer gap-2">
          <span class="label-text">Checkbox</span>
          <input type="checkbox" class="checkbox checkbox-primary" checked>
        </label>
        <label class="label cursor-pointer gap-2">
          <span class="label-text">Toggle</span>
          <input type="checkbox" class="toggle toggle-primary" checked>
        </label>
        <div class="join">
          <input class="radio join-item radio-primary" type="radio" name="r" checked/>
          <input class="radio join-item radio-primary" type="radio" name="r"/>
          <input class="radio join-item radio-primary" type="radio" name="r"/>
        </div>
      </div>
      <button class="btn btn-primary sm:col-span-2">Submit</button>
    </form>
  </section>

  {{-- Table --}}
  <section class="space-y-4">
    <h2 class="text-xl font-semibold">Table</h2>
    <div class="overflow-x-auto">
      <table class="table table-zebra min-w-[720px]">
        <thead>
          <tr><th>#</th><th>Nama</th><th>Role</th><th>Warna</th></tr>
        </thead>
        <tbody>
          <tr><th>1</th><td>Cy Ganderton</td><td>QC</td><td>Red</td></tr>
          <tr><th>2</th><td>Hart Hagerty</td><td>Designer</td><td>Blue</td></tr>
          <tr><th>3</th><td>Brice Swyre</td><td>Dev</td><td>Purple</td></tr>
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
          <div class="card-actions justify-end">
            <button class="btn btn-primary">Action</button>
          </div>
        </div>
      </div>
      <div class="stats shadow bg-base-100">
        <div class="stat">
          <div class="stat-title">Leads</div>
          <div class="stat-value">123</div>
          <div class="stat-desc text-success">↗︎ 23%</div>
        </div>
      </div>
      <div class="card bg-primary text-primary-content shadow">
        <div class="card-body">
          <h3 class="card-title">Primary Card</h3>
          <p>Card dengan kontras otomatis</p>
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
    <div class="toast">
      <div class="alert alert-info">Toast info contoh</div>
    </div>
    <div class="tooltip" data-tip="Tooltip contoh">
      <button class="btn">Hover me</button>
    </div>
  </section>
</div>

@push('scripts')
<script>
function themeLab(){
  // daftar themes built-in (urutan favorit); diisi cepat tanpa fetch
  const builtins = [
    'light','dark','cupcake','bumblebee','emerald','corporate','synthwave','retro','cyberpunk',
    'valentine','halloween','garden','forest','aqua','lofi','pastel','fantasy','wireframe','black',
    'luxury','dracula'
  ]

  // definisi tema custom 'softblue' injeksi via CSS variables saat dipilih
  const SOFTBLUE_CSS = `
  :root[data-theme="softblue"]{
    --p:#3B82F6; --pc:#ffffff;
    --s:#ECF4FF; --sc:#1E293B;
    --a:#1FB2A6; --ac:#ffffff;
    --n:#1E293B; --nc:#E0E6F1;
    --b1:#ffffff; --b2:#F8FAFC; --b3:#E2E8F0; --bc:#1E293B;
    --in:#3ABFF8; --su:#10B981; --wa:#F59E0B; --er:#EF4444;
  }`

  let styleTag

  return {
    builtins,
    current: localStorage.getItem('theme') || document.documentElement.getAttribute('data-theme') || 'light',

    init(){
      // jika saat ini softblue, injeksikan CSS
      if(this.current === 'softblue') this.injectSoftblue()
    },
    apply(name){
      this.current = name
      localStorage.setItem('theme', name)
      document.documentElement.setAttribute('data-theme', name)
      document.documentElement.classList.toggle('dark', name === 'dark')

      if(name === 'softblue') this.injectSoftblue()
      else this.removeSoftblue()
    },
    toggle(){
      const now = document.documentElement.getAttribute('data-theme') || 'light'
      const next = now === 'dark' ? 'light' : 'dark'
      this.apply(next)
    },
    injectSoftblue(){
      if(styleTag) return
      styleTag = document.createElement('style')
      styleTag.id = 'softblue-theme-vars'
      styleTag.textContent = SOFTBLUE_CSS
      document.head.appendChild(styleTag)
    },
    removeSoftblue(){
      if(styleTag){ styleTag.remove(); styleTag = null }
    }
  }
}
</script>
@endpush
@endsection
