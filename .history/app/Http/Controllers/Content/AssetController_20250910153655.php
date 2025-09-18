<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\ContentAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $assets = ContentAsset::latest()->paginate(12);
        return view('content.assets.index', compact('assets'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:image,video,document',
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        $path = $request->file('file')->store('public/assets');

        ContentAsset::create([
            'name' => $request->name,
            'type' => $request->type,
            'path' => $path,
            'url' => Storage::url($path),
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('assets.index')->with('success', 'Asset uploaded successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContentAsset $asset)
    {
        // Delete the file from storage
        Storage::delete($asset->path);

        // Delete the record from the database
        $asset->delete();

        return redirect()->route('assets.index')->with('success', 'Asset deleted successfully.');
    }
}
