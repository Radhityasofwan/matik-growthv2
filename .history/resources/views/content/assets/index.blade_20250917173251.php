@extends('layouts.app')

@section('title', 'Asset Library')

@section('content')
<div class="container mx-auto py-6">

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6" data-aos="fade-down">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Terdapat kesalahan!</strong>
                    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </span>
            </div>
        </div>
    @endif

    {{-- SINKRONISASI: Header Halaman yang konsisten --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8" data-aos="fade-down">
        <div>
            <h1 class="text-3xl font-bold text-base-content">Asset Library</h1>
            <p class="mt-1 text-base-content/70">Kelola file dan media tim Anda di satu tempat terpusat.</p>
        </div>
        @if($canUpload)
        <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
            @csrf
            <input type="file" name="file" required class="file-input file-input-bordered file-input-primary file-input-sm w-full max-w-xs" />
            <button type="submit" class="btn btn-primary btn-sm">Upload File</button>
        </form>
        @endif
    </div>

    @if($files->isEmpty())
      {{-- SINKRONISASI: Tampilan state kosong yang lebih baik --}}
      <div class="text-center py-20 card bg-base-100 mt-8 border border-base-300/50 shadow-lg" data-aos="fade-up">
            <div class="card-body items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                <h3 class="mt-4 text-lg font-medium text-base-content">Library Kosong</h3>
                <p class="mt-1 text-sm text-base-content/60">Upload file pertama Anda untuk memulai.</p>
            </div>
        </div>
    @else
      {{-- SINKRONISASI: Grid kartu yang dioptimalkan dengan DaisyUI --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach ($files as $f)
          <div class="card bg-base-100 shadow-lg border border-base-300/50 transition-all duration-300 hover:shadow-2xl" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4) * 50 }}">
            <figure class="bg-base-200 aspect-video">
              <a href="{{ route('assets.preview', ['path' => $f->path]) }}" target="_blank" class="w-full h-full flex items-center justify-center p-2">
                @if($f->is_image)
                  <img src="{{ route('assets.preview', ['path' => $f->path]) }}" alt="{{ $f->name }}" class="object-contain max-h-48">
                @else
                  <div class="text-base-content/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                  </div>
                @endif
              </a>
            </figure>
            <div class="card-body p-4">
              <h2 class="card-title text-sm truncate" title="{{ $f->name }}">{{ $f->name }}</h2>
              <p class="text-xs text-base-content/60">
                {{ number_format($f->size / 1024, 1) }} KB · {{ \Carbon\Carbon::createFromTimestamp($f->last_modified)->diffForHumans() }}
              </p>
              <div class="card-actions justify-end mt-2">
                <a href="{{ route('assets.download', ['path' => $f->path]) }}" class="btn btn-outline btn-sm">Download</a>
                <form action="{{ route('assets.destroy', ['asset' => 0]) }}" method="POST" onsubmit="return confirm('Hapus file ini?')">
                  @csrf @method('DELETE')
                  <input type="hidden" name="path" value="{{ $f->path }}">
                  <button type="submit" class="btn btn-error btn-outline btn-sm">Hapus</button>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
</div>
@endsection
