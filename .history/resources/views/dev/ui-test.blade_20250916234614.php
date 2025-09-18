@extends('layouts.app')

@section('title', 'UI Component & Style Test')

@section('content')
<section data-theme="softblue" class="space-y-8">
  {{-- Header --}}
  <div>
    <h1 class="text-3xl font-bold text-neutral">UI Component & Style Test</h1>
    <p class="mt-1 text-neutral/60">Verifikasi komponen visual, style, dan interaksi.</p>
  </div>

  {{-- Colors (STATIC, tanpa kelas dinamis) --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Color Palette (Tema: softblue)</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-10 gap-4 mt-4">
        @foreach ([
          ['bg-primary','primary'], ['bg-secondary','secondary'], ['bg-accent','accent'],
          ['bg-neutral','neutral'], ['bg-base-100','base-100'], ['bg-base-200','base-200'],
          ['bg-base-300','base-300'], ['bg-info','info'], ['bg-success','success'],
          ['bg-warning','warning'], ['bg-error','error']
        ] as [$cls,$name])
          <div class="text-center">
            <div class="w-full h-16 rounded-lg {{ $cls }} shadow-inner border border-black/10"></div>
            <p class="text-sm font-medium mt-2">{{ $name }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Typography --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Typography</h2>
      <div class="prose max-w-none mt-4">
        <h1>Heading 1</h1>
        <h2>Heading 2</h2>
        <h3>Heading 3</h3>
        <p>Paragraph example with <strong>bold</strong>, <em>italic</em>, and <a href="#">link</a>.</p>
        <pre><code>const hello = 'world'</code></pre>
      </div>
    </div>
  </div>

  {{-- Buttons --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Buttons</h2>
      <div class="flex flex-wrap gap-2 items-center mt-4">
        <button class="btn">Default</button>
        <button class="btn btn-primary">Primary</button>
        <button class="btn btn-secondary">Secondary</button>
        <button class="btn btn-accent">Accent</button>
        <button class="btn btn-ghost">Ghost</button>
        <button class="btn btn-link">Link</button>
        <button class="btn btn-outline">Outline</button>
        <button class="btn btn-outline btn-primary">Outline Primary</button>
        <button class="btn btn-primary" disabled>Disabled</button>
      </div>
    </div>
  </div>

  {{-- Forms --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Form Elements</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div class="form-control">
          <label class="label"><span class="label-text">Input Text</span></label>
          <input type="text" placeholder="Type here" class="input input-bordered w-full" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Input Error</span></label>
          <input type="text" placeholder="Type here" class="input input-bordered input-error w-full" />
        </div>
        <div class="form-control">
          <label class="label"><span class="label-text">Select</span></label>
          <select class="select select-bordered">
            <option disabled selected>Pick one</option>
            <option>Option 1</option>
            <option>Option 2</option>
          </select>
        </div>
        <div class="form-control">
          <label class="label cursor-pointer"><span class="label-text">Checkbox</span><input type="checkbox" class="checkbox checkbox-primary" /></label>
          <label class="label cursor-pointer"><span class="label-text">Toggle</span><input type="checkbox" class="toggle toggle-primary" checked /></label>
        </div>
      </div>
    </div>
  </div>

  {{-- Cards --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Cards</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div class="card bg-base-200">
          <div class="card-body items-center text-center">
            <h3 class="card-title">Simple Card</h3>
            <p>Standard card component.</p>
          </div>
        </div>

        <x-card.stat title="Test Stat Card" value="1,234" change="▲ 5.2% this week" changeType="success">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </x-card.stat>
      </div>
    </div>
  </div>

  {{-- Alerts & Badges --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Alerts & Badges</h2>
      <div class="flex flex-col gap-3 mt-3">
        <div role="alert" class="alert alert-info"><span>Info alert</span></div>
        <div role="alert" class="alert alert-success"><span>Success alert</span></div>
        <div role="alert" class="alert alert-warning"><span>Warning alert</span></div>
        <div role="alert" class="alert alert-error"><span>Error alert</span></div>
        <div class="flex flex-wrap gap-2">
          <div class="badge">Default</div>
          <div class="badge badge-primary">Primary</div>
          <div class="badge badge-secondary">Secondary</div>
          <div class="badge badge-accent">Accent</div>
          <div class="badge badge-outline">Outline</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Modal</h2>
      <p class="text-sm mt-2">Klik tombol untuk menguji modal.</p>
      <div class="card-actions justify-start mt-4">
        <button class="btn btn-primary" onclick="document.getElementById('test_modal').showModal()">Buka Modal</button>
      </div>
      <dialog id="test_modal" class="modal">
        <div class="modal-box">
          <h3 class="font-bold text-lg">Hello!</h3>
          <p class="py-4">Jika ini tampil, modal DaisyUI bekerja.</p>
          <div class="modal-action">
            <form method="dialog"><button class="btn">Tutup</button></form>
          </div>
        </div>
      </dialog>
    </div>
  </div>

  {{-- Table responsif (P1) --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Responsive Table</h2>
      <div class="overflow-x-auto mt-4">
        <table class="table">
          <thead><tr><th>Invoice</th><th>Customer</th><th>Status</th><th>Amount</th></tr></thead>
          <tbody>
            <tr><td>#INV-1001</td><td>John</td><td><span class="badge badge-success">Paid</span></td><td>$250</td></tr>
            <tr><td>#INV-1002</td><td>Mary</td><td><span class="badge badge-warning">Pending</span></td><td>$120</td></tr>
            <tr><td>#INV-1003</td><td>Alex</td><td><span class="badge badge-error">Failed</span></td><td>$90</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Sticky CTA (P1 mobile) --}}
  <div class="md:hidden fixed inset-x-0 bottom-0 z-50">
    <div class="mx-4 mb-4 rounded-2xl shadow-lg bg-base-100 border border-base-300 p-3 flex items-center justify-between">
      <span class="font-medium">Ready to continue?</span>
      <button class="btn btn-primary btn-sm">CTA Action</button>
    </div>
  </div>

  {{-- Chart + Skeleton (P2) --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50" data-aos="fade-up">
    <div class="card-body">
      <h2 class="card-title">Chart (ApexCharts)</h2>
      <div id="chart-skeleton" class="skeleton h-56 w-full mt-4"></div>
      <div id="chart-demo" class="mt-4"></div>
    </div>
  </div>

  {{-- Animation Test (AOS) --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Animation (AOS)</h2>
      <div class="mt-4 p-8 bg-secondary rounded-lg text-center font-semibold text-primary" data-aos="fade-up">
        AOS is Working!
      </div>
    </div>
  </div>
</section>
@endsection
