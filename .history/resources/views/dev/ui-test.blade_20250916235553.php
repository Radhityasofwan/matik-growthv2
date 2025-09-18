@extends('layouts.app')

@section('title', 'UI Component & Style Test')

@section('content')
<section data-theme="softblue" class="space-y-8">

  {{-- ========= PALETTE & SEMANTICS (FIX) ========= --}}
  <div class="card bg-base-100 shadow-sm border border-base-300/50">
    <div class="card-body">
      <h2 class="card-title">Color Palette (Tema: softblue)</h2>

      {{-- Swatches utama (kelas statis) --}}
      @php
        $swatches = [
          ['bg-primary',  'primary'],
          ['bg-secondary','secondary'],
          ['bg-accent',   'accent'],
          ['bg-neutral',  'neutral'],
          ['bg-base-100', 'base-100'],
          ['bg-base-200', 'base-200'],
          ['bg-base-300', 'base-300'],
          ['bg-info',     'info'],
          ['bg-success',  'success'],
          ['bg-warning',  'warning'],
          ['bg-error',    'error'],
        ];
      @endphp

      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 gap-4 mt-4">
        @foreach ($swatches as [$cls,$name])
          <div class="text-center">
            <div class="w-full h-16 rounded-lg {{ $cls }} shadow-inner border border-black/10"></div>
            <p class="text-sm font-medium mt-2">{{ $name }}</p>
          </div>
        @endforeach
      </div>

      {{-- Konten vs background (cek kontras) --}}
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="rounded-xl p-4 bg-primary text-primary-content border border-primary">
          <div class="font-semibold">Primary / Primary-Content</div>
          <div class="text-sm opacity-80">Button / link utama harus terbaca jelas.</div>
        </div>
        <div class="rounded-xl p-4 bg-secondary text-secondary-content border border-secondary">
          <div class="font-semibold">Secondary / Secondary-Content</div>
          <div class="text-sm opacity-80">Elemen sekunder & highlight.</div>
        </div>
        <div class="rounded-xl p-4 bg-accent text-accent-content border border-accent">
          <div class="font-semibold">Accent / Accent-Content</div>
          <div class="text-sm opacity-80">Aksen & chip.</div>
        </div>
      </div>

      {{-- Semantic matrix (bg/text/border/ring) --}}
      @php
        $sem = ['primary','secondary','accent','neutral','info','success','warning','error'];
      @endphp
      <div class="mt-6">
        <h3 class="font-semibold mb-3">Semantic Matrix</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
          @foreach ($sem as $k)
            <div class="rounded-xl p-4 bg-base-200">
              <div class="flex items-center justify-between mb-2">
                <span class="font-medium capitalize">{{ $k }}</span>
                <span class="badge badge-{{ $k }}">badge-{{ $k }}</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 rounded border {{ 'border-'.$k }}">border-{{ $k }}</span>
                <span class="px-2 py-1 rounded {{ 'text-'.$k }}">text-{{ $k }}</span>
                <span class="px-2 py-1 rounded {{ 'bg-'.$k }} text-white/90">bg-{{ $k }}</span>
                <button class="btn btn-sm {{ 'btn-'.$k }}">btn-{{ $k }}</button>
              </div>
              <div class="mt-3 p-3 rounded bg-base-100 ring-2 {{ 'ring-'.$k }}">
                ring-{{ $k }}
              </div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Base panel tones (untuk card/background) --}}
      <div class="mt-6">
        <h3 class="font-semibold mb-3">Base Panels</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="p-4 rounded-xl bg-base-100 border border-base-300">
            <div class="font-medium">bg-base-100</div>
            <div class="text-sm text-neutral/70">Default card background.</div>
          </div>
          <div class="p-4 rounded-xl bg-base-200 border border-base-300">
            <div class="font-medium">bg-base-200</div>
            <div class="text-sm text-neutral/70">Page section background.</div>
          </div>
          <div class="p-4 rounded-xl bg-base-300 border border-base-300">
            <div class="font-medium">bg-base-300</div>
            <div class="text-sm text-neutral/70">Dividers & subtle panels.</div>
          </div>
        </div>
      </div>

    </div>
  </div>
  {{-- ========= /PALETTE & SEMANTICS ========= --}}

  {{-- (opsional) sisanya: typography, buttons, forms, dsb. tetap seperti sebelumnya --}}
</section>
@endsection
