{{-- resources/views/test/daisyui-lab.blade.php --}}
@extends('layouts.app')

@section('title', 'DaisyUI Full Lab')

@section('content')
<div class="space-y-8">

  {{-- HEADER / CONTROLS --}}
  <section class="card bg-base-100 border border-base-300 shadow-sm" data-aos="fade-up">
    <div class="card-body">
      <div class="flex flex-wrap items-center gap-3 justify-between">
        <h1 class="text-2xl font-bold">DaisyUI Full Lab</h1>
        <div class="flex items-center gap-2">
          <button class="btn btn-outline btn-sm" @click="setTheme('softblue')">Softblue</button>
          <button class="btn btn-outline btn-sm" @click="setTheme('dark')">Dark</button>
          <button class="btn btn-primary btn-sm" @click="setTheme(theme === 'dark' ? 'softblue' : 'dark')">
            Toggle Theme
          </button>
        </div>
      </div>
      <p class="opacity-70">Halaman ini menguji <span class="font-semibold">semua komponen utama</span> DaisyUI + util Tailwind.
      Jika ada bagian yang tidak memiliki warna/gaya, bagian itu kemungkinan tidak ter-compile atau tema tidak terpasang.</p>
    </div>
  </section>

  {{-- PALETTE / TOKENS --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Palette & Tokens</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
        @php
          $tokens = [
            ['bg-primary','text-primary-content','primary'],
            ['bg-secondary','text-secondary-content','secondary'],
            ['bg-accent','text-accent-content','accent'],
            ['bg-neutral','text-neutral-content','neutral'],
            ['bg-info','text-info-content','info'],
            ['bg-success','text-success-content','success'],
            ['bg-warning','text-warning-content','warning'],
            ['bg-error','text-error-content','error'],
            ['bg-base-100','text-base-content','base-100'],
            ['bg-base-200','text-base-content','base-200'],
            ['bg-base-300','text-base-content','base-300'],
          ];
        @endphp
        @foreach($tokens as [$bg,$tc,$name])
          <div class="rounded-xl p-4 border border-base-300 flex flex-col gap-2 items-start {{$bg}} {{$tc}}">
            <span class="badge">{{$name}}</span>
            <span class="text-xs opacity-90">Class: {{$bg}}</span>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- TYPOGRAPHY / KBD / CODE / LINK --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body space-y-4">
      <h2 class="card-title">Typography & Helpers</h2>
      <div class="prose max-w-none">
        <h1>Heading 1</h1>
        <h2>Heading 2</h2>
        <p>Paragraf <a href="#" class="link link-primary">dengan link</a> dan <code>inline code</code>. Tekan <kbd class="kbd kbd-sm">⌘</kbd> + <kbd class="kbd kbd-sm">K</kbd> untuk search.</p>
        <pre><code class="language-js">console.log('Hello DaisyUI');</code></pre>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <span class="badge">Badge</span>
        <span class="badge badge-outline">Outline</span>
        <span class="badge badge-primary">Primary</span>
        <span class="badge badge-secondary">Secondary</span>
        <span class="badge badge-accent">Accent</span>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <span class="loading loading-spinner"></span>
        <span class="loading loading-dots"></span>
        <span class="loading loading-bars"></span>
      </div>
    </div>
  </section>

  {{-- BUTTONS / DROPDOWN / TOOLTIP --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body space-y-4">
      <h2 class="card-title">Buttons & Dropdown & Tooltip</h2>
      <div class="flex flex-wrap gap-2">
        <button class="btn">Default</button>
        <button class="btn btn-primary">Primary</button>
        <button class="btn btn-secondary">Secondary</button>
        <button class="btn btn-accent">Accent</button>
        <button class="btn btn-neutral">Neutral</button>
        <button class="btn btn-ghost">Ghost</button>
        <button class="btn btn-link">Link</button>
        <button class="btn btn-info">Info</button>
        <button class="btn btn-success">Success</button>
        <button class="btn btn-warning">Warning</button>
        <button class="btn btn-error">Error</button>
        <button class="btn btn-outline">Outline</button>
        <button class="btn btn-primary btn-sm">Small</button>
        <button class="btn btn-primary btn-lg">Large</button>
      </div>

      <div class="flex flex-wrap gap-4 items-center">
        <div class="dropdown">
          <div tabindex="0" role="button" class="btn">Dropdown</div>
          <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 border border-base-300">
            <li><a>Item 1</a></li>
            <li><a>Item 2</a></li>
          </ul>
        </div>

        <div class="tooltip" data-tip="Tooltip default">
          <button class="btn">Hover me</button>
        </div>
        <div class="tooltip tooltip-primary" data-tip="Tooltip primary">
          <button class="btn btn-outline btn-primary">Hover me</button>
        </div>
      </div>
    </div>
  </section>

  {{-- ALERTS / TOAST / AVATAR / INDICATOR --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body space-y-4">
      <h2 class="card-title">Alerts, Toast, Avatar, Indicator</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="alert alert-info"><span>Info: perubahan disimpan.</span></div>
        <div class="alert alert-success"><span>Success: data tersimpan.</span></div>
        <div class="alert alert-warning"><span>Warning: periksa kembali input.</span></div>
        <div class="alert alert-error"><span>Error: gagal memuat data.</span></div>
      </div>

      <div class="flex items-center gap-4">
        <div class="avatar">
          <div class="w-12 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
            <img src="https://i.pravatar.cc/150?img=32" alt="avatar">
          </div>
        </div>
        <div class="indicator">
          <span class="indicator-item badge badge-primary">New</span>
          <button class="btn">Inbox</button>
        </div>
        <button class="btn btn-secondary" onclick="document.getElementById('lab-toast')?.classList.toggle('hidden')">Show Toast</button>
      </div>

      <div id="lab-toast" class="toast hidden">
        <div class="alert alert-success">
          <span>Toast success muncul!</span>
        </div>
      </div>
    </div>
  </section>

  {{-- FORMS / INPUTS --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body space-y-4">
      <h2 class="card-title">Forms</h2>
      <form class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <label class="form-control">
          <div class="label"><span class="label-text">Email</span></div>
          <input type="email" class="input input-bordered" placeholder="you@example.com">
          <div class="label"><span class="label-text-alt">We'll never share your email.</span></div>
        </label>
        <label class="form-control">
          <div class="label"><span class="label-text">Password</span></div>
          <input type="password" class="input input-bordered" placeholder="••••••••">
        </label>
        <label class="form-control sm:col-span-2">
          <div class="label"><span class="label-text">Select</span></div>
          <select class="select select-bordered">
            <option>Option 1</option><option>Option 2</option>
          </select>
        </label>
        <label class="form-control sm:col-span-2">
          <div class="label"><span class="label-text">Textarea</span></div>
          <textarea class="textarea textarea-bordered" rows="3" placeholder="Type here"></textarea>
        </label>
        <div class="flex flex-wrap gap-4 sm:col-span-2">
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
          <input type="range" min="0" max="100" value="25" class="range range-primary w-64">
          <input type="file" class="file-input file-input-bordered" />
        </div>
      </form>
    </div>
  </section>

  {{-- CARDS / STATS / PROGRESS / STEPS --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body space-y-6">
      <h2 class="card-title">Cards, Stats, Progress, Steps</h2>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 shadow">
          <div class="card-body">
            <h3 class="card-title">Card</h3>
            <p>Contoh card biasa.</p>
            <div class="card-actions justify-end">
              <button class="btn btn-primary btn-sm">Action</button>
            </div>
          </div>
        </div>
        <div class="stats shadow bg-base-100">
          <div class="stat">
            <div class="stat-title">Total Leads</div>
            <div class="stat-value">65</div>
            <div class="stat-desc text-success">▲ 47 minggu ini</div>
          </div>
        </div>
        <div class="flex flex-col gap-2">
          <progress class="progress progress-primary w-full" value="40" max="100"></progress>
          <progress class="progress progress-success w-full" value="70" max="100"></progress>
          <progress class="progress progress-error w-full" value="20" max="100"></progress>
        </div>
        <ul class="steps">
          <li class="step step-primary">Start</li>
          <li class="step step-primary">Config</li>
          <li class="step">Review</li>
          <li class="step">Done</li>
        </ul>
      </div>
    </div>
  </section>

  {{-- TABLES (responsive + zebra) --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body space-y-4">
      <h2 class="card-title">Tables</h2>
      <div class="overflow-x-auto h-scroll-snap">
        <table class="table table-zebra min-w-[720px] h-snap">
          <thead>
            <tr>
              <th>Tanggal</th><th>Order</th><th>Status</th><th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>Mon</td><td>#INV-1001</td><td><span class="badge badge-success">Paid</span></td><td class="text-right">Rp 1.200.000</td></tr>
            <tr><td>Tue</td><td>#INV-1002</td><td><span class="badge badge-warning">Pending</span></td><td class="text-right">Rp 850.000</td></tr>
            <tr><td>Wed</td><td>#INV-1003</td><td><span class="badge badge-error">Failed</span></td><td class="text-right">Rp 0</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  {{-- TABS / BREADCRUMB / PAGINATION / COLLAPSE --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body space-y-6">
      <h2 class="card-title">Tabs, Breadcrumbs, Pagination, Collapse</h2>

      <div role="tablist" class="tabs tabs-boxed">
        <a role="tab" class="tab tab-active">Tab 1</a>
        <a role="tab" class="tab">Tab 2</a>
        <a role="tab" class="tab">Tab 3</a>
      </div>

      <div class="breadcrumbs">
        <ul>
          <li><a>Home</a></li>
          <li><a>Library</a></li>
          <li>Data</li>
        </ul>
      </div>

      <div class="join">
        <button class="join-item btn">«</button>
        <button class="join-item btn btn-active">1</button>
        <button class="join-item btn">2</button>
        <button class="join-item btn">3</button>
        <button class="join-item btn">»</button>
      </div>

      <div class="collapse collapse-arrow bg-base-200">
        <input type="checkbox" />
        <div class="collapse-title text-lg font-medium">Apa itu DaisyUI?</div>
        <div class="collapse-content"><p>Component library untuk Tailwind CSS.</p></div>
      </div>
    </div>
  </section>

  {{-- MODAL (checkbox) / DIALOG --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Modal</h2>
      <label for="lab-modal" class="btn btn-primary">Open Modal</label>
      <input type="checkbox" id="lab-modal" class="modal-toggle" />
      <div class="modal" role="dialog">
        <div class="modal-box">
          <h3 class="font-bold text-lg">Halo!</h3>
          <p class="py-4">Ini modal uji DaisyUI.</p>
          <div class="modal-action">
            <label for="lab-modal" class="btn">Close</label>
          </div>
        </div>
        <label class="modal-backdrop" for="lab-modal">Close</label>
      </div>
    </div>
  </section>

  {{-- CHART (ApexCharts) + SKELETON anti-CLS --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Chart (ApexCharts)</h2>
      <div id="chart-skeleton" class="skeleton w-full h-80 rounded-xl"></div>
      <div id="chart-demo" class="w-full h-80"></div>
    </div>
  </section>

  {{-- NAV / MENU (vertical) --}}
  <section class="card bg-base-100 border border-base-300" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Vertical Menu</h2>
      <ul class="menu bg-base-200 rounded-box w-full sm:w-80">
        <li class="menu-title">Main</li>
        <li><a class="active">Dashboard</a></li>
        <li><a>Notifications</a></li>
        <li class="menu-title">Sales</li>
        <li><a>Leads</a></li>
        <li><a>Subscriptions</a></li>
      </ul>
    </div>
  </section>

</div>
@endsection
