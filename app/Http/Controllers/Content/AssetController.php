<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function index()
    {
        $disk = Storage::disk('public');
        $dir  = 'assets';

        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $files = collect($disk->files($dir))
            ->map(function ($path) use ($disk) {
                return (object) [
                    'path'          => $path,                    // assets/xxx.ext
                    'name'          => basename($path),
                    'size'          => $disk->size($path),
                    'last_modified' => $disk->lastModified($path),
                    'mime'          => $this->guessMime($path),
                    'is_image'      => $this->isImage($path),
                    // NB: url() tidak dipakai untuk link agar tidak 404; rute khusus dipakai di Blade
                    'url'           => $disk->url($path),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return view('content.assets.index', [
            'files'     => $files,
            'canUpload' => true,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required','file','max:51200'], // 50MB
        ]);

        $file = $request->file('file');
        $dir  = 'assets';
        $ext  = $file->getClientOriginalExtension();
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $name = $base . '-' . now()->format('YmdHis') . '-' . Str::random(6) . ($ext ? '.'.$ext : '');

        $file->storeAs($dir, $name, 'public');

        return redirect()->route('assets.index')->with('success', 'File berhasil diunggah.');
    }

    public function destroy(Request $request)
    {
        $request->validate(['path' => ['required','string']]);
        $path = $request->string('path');

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

    /** ====== BARU: PREVIEW & DOWNLOAD TANPA /storage ====== */

    // Stream konten untuk preview inline (image/pdf, dll)
    public function preview(Request $request)
    {
        $request->validate(['path' => ['required','string']]);
        $path = $request->string('path');

        if (!str_starts_with($path, 'assets/')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        // StreamedResponse dengan Content-Type yang sesuai
        return $disk->response($path, basename($path), [
            // biarkan inline untuk preview
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    // Paksa unduhan (attachment)
    public function download(Request $request)
    {
        $request->validate(['path' => ['required','string']]);
        $path = $request->string('path');

        if (!str_starts_with($path, 'assets/')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, basename($path));
    }

    /** ====== Helpers ====== */

    private function isImage(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','svg']);
    }

    private function guessMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg','jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            'gif'        => 'image/gif',
            'webp'       => 'image/webp',
            'svg'        => 'image/svg+xml',
            'pdf'        => 'application/pdf',
            'csv'        => 'text/csv',
            'xlsx'       => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'        => 'application/vnd.ms-excel',
            'docx'       => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'        => 'application/msword',
            'pptx'       => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppt'        => 'application/vnd.ms-powerpoint',
            'mp4'        => 'video/mp4',
            'mp3'        => 'audio/mpeg',
            default      => 'application/octet-stream',
        };
    }
}
