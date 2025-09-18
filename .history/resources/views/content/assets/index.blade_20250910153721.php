@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-8 max-w-7xl">
    <div class="py-8">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-semibold leading-tight">Content Asset Library</h2>
                <span class="text-base-content/70">Manage your marketing images, videos, and documents.</span>
            </div>
            <div>
                <label for="add-asset-modal" class="btn btn-primary">Add New Asset</label>
            </div>
        </div>

        @if (session('success'))
            <div role="alert" class="alert alert-success mt-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-6">
            @forelse ($assets as $asset)
                <div class="card bg-base-100 shadow-xl">
                    <figure class="px-4 pt-4 h-48 bg-base-200">
                        @if($asset->type == 'image')
                            <img src="{{ $asset->url }}" alt="{{ $asset->name }}" class="object-contain h-full w-full rounded-xl" />
                        @else
                        <div class="flex flex-col items-center justify-center h-full w-full">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                             <span class="text-sm mt-2">{{ $asset->type }}</span>
                        </div>
                        @endif
                    </figure>
                    <div class="card-body p-4 items-center text-center">
                        <h2 class="card-title text-sm">{{ Str::limit($asset->name, 25) }}</h2>
                        <div class="card-actions">
                             <a href="{{ $asset->url }}" target="_blank" class="btn btn-xs btn-outline">View</a>
                             <form action="{{ route('assets.destroy', $asset) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this asset?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-error btn-outline">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-10">
                     <p class="font-semibold">No assets found.</p>
                     <p class="text-sm text-base-content/60">Click "Add New Asset" to upload your first file.</p>
                </div>
            @endforelse
        </div>
         <div class="mt-6">
            {{ $assets->links() }}
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<input type="checkbox" id="add-asset-modal" class="modal-toggle" />
<div class="modal" role="dialog">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Add a New Content Asset</h3>
    <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
        @csrf
        <div>
            <label class="label"><span class="label-text">Asset Name</span></label>
            <input type="text" name="name" placeholder="e.g., Q4 Promo Banner" class="input input-bordered w-full" required />
        </div>
        <div>
            <label class="label"><span class="label-text">Asset Type</span></label>
            <select name="type" class="select select-bordered w-full" required>
                <option value="image">Image</option>
                <option value="video">Video</option>
                <option value="document">Document</option>
            </select>
        </div>
        <div>
            <label class="label"><span class="label-text">File</span></label>
            <input type="file" name="file" class="file-input file-input-bordered w-full" required />
        </div>
        <div class="modal-action">
            <label for="add-asset-modal" class="btn">Cancel</label>
            <button type="submit" class="btn btn-primary">Save Asset</button>
        </div>
    </form>
  </div>
   <label class="modal-backdrop" for="add-asset-modal">Close</label>
</div>
@endsection
