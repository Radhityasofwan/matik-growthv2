<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    /**
     * Tampilkan daftar aset dari storage/public/assets
     */
    public function index()
    {
        $disk = Storage::disk('public');
        $dir  = 'assets';

        // pastikan folder ada
        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $files = collect($disk->files($dir))
            ->map(function ($path) use ($disk) {
                $meta = [
                    'path'         => $path,                                   // e.g. assets/file.jpg
                    'name'         => basename($path),
                    'url'          => $disk->url($path),
                    'size'         => $disk->size($path),
                    'last_modified'=> $disk->lastModified($path),
                    'mime'         => $this->guessMime($path),
                    'is_image'     => $this->isImage($path),
                ];
                return (object) $meta;
            })
            ->sortByDesc('last_modified')
            ->values();

        return view('content.assets.index', [
            'files' => $files,
            'canUpload' => true, // jika mau batasi per role, ubah di sini
        ]);
    }

    /**
     * Upload file baru ke storage/public/assets
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB
        ]);

        $file = $request->file('file');

        $dir = 'assets';
        $ext = $file->getClientOriginalExtension();
        $name = Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 80, '');
        $safeBase = Str::slug($name) ?: 'file';
        $filename = $safeBase . '-' . now()->format('YmdHis') . '-' . Str::random(6) . ($ext ? '.' . $ext : '');

        $file->storeAs($dir, $filename, 'public');

        return redirect()
            ->route('assets.index')
            ->with('success', 'File berhasil diunggah.');
    }

    /**
     * Hapus file berdasarkan path relative di disk public.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $request->input('path');

        // lindungi agar hanya folder assets yang bisa dihapus
        if (!str_starts_with($path, 'assets/')) {
            return redirect()->route('assets.index')->withErrors('Path tidak valid.');
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
            return redirect()->route('assets.index')->with('success', 'File berhasil dihapus.');
        }

        return redirect()->route('assets.index')->withErrors('File tidak ditemukan.');
    }

    private function isImage(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
    }

    private function guessMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'svg'         => 'image/svg+xml',
            'pdf'         => 'application/pdf',
            'csv'         => 'text/csv',
            'xlsx'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'         => 'application/vnd.ms-excel',
            'docx'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'         => 'application/msword',
            'pptx'        => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppt'         => 'application/vnd.ms-powerpoint',
            'mp4'         => 'video/mp4',
            'mp3'         => 'audio/mpeg',
            default       => 'application/octet-stream',
        };
    }
}
