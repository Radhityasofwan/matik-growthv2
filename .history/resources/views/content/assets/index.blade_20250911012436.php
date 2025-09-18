@extends('layouts.app')

@section('title', 'Asset Library')

@section('content')
<div class="container mx-auto px-6 py-8">

    @if (session('success'))
        <div class="alert alert-success shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error shadow-lg mb-6">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Terdapat kesalahan!</strong>
                    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </span>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-3xl font-medium text-gray-800 dark:text-gray-100">Asset Library</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola file tim Anda di satu tempat.</p>
        </div>
        @if($canUpload)
        <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="file" required class="file-input file-input-bordered file-input-sm w-64" />
            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
        </form>
        @endif
    </div>

    @if($files->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-10 text-center text-gray-500 dark:text-gray-400">
            Belum ada file. Gunakan form upload di kanan atas.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($files as $f)
            <div class="card bg-base-100 shadow-xl">
                <figure class="bg-base-200 aspect-video">
                    @if($f->is_image)
                        <img src="{{ $f->url }}" alt="{{ $f->name }}" class="object-contain max-h-48 p-2">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-gray-400">
                            {{-- ikon file --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                        </div>
                    @endif
                </figure>
                <div class="card-body">
                    <h2 class="card-title text-sm truncate" title="{{ $f->name }}">{{ $f->name }}</h2>
                    <p class="text-xs text-gray-500">
                        {{ number_format($f->size / 1024, 1) }} KB · {{ \Carbon\Carbon::createFromTimestamp($f->last_modified)->diffForHumans() }}
                    </p>
                    <div class="card-actions justify-end">
                        <a href="{{ $f->url }}" target="_blank" class="btn btn-outline btn-sm">Preview</a>
                        <a href="{{ $f->url }}" download class="btn btn-outline btn-sm">Download</a>
                        <form action="{{ route('assets.destroy', ['asset' => 0]) }}" method="POST" onsubmit="return confirm('Hapus file ini?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="path" value="{{ $f->path }}">
                            <button type="submit" class="btn btn-error btn-sm text-white">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
