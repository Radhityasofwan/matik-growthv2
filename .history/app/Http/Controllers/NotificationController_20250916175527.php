<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class NotificationController extends Controller
{
    /** Halaman daftar notifikasi */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest()->paginate(20);
        $unread        = $user->unreadNotifications()->count();

        // Fallback info dari activity log jika benar-benar kosong
        $activities = [];
        if ($notifications->count() === 0) {
            $activities = Activity::with('causer')->latest()->limit(20)->get();
        }

        return view('notifications.index', compact('notifications', 'activities', 'unread'));
    }

    /** Feed JSON untuk navbar (dipolling Alpine) */
    public function feed(Request $request)
    {
        $user = $request->user();

        $items = $user->notifications()->latest()->limit(8)->get()->map(function ($n) {
            $data = (array) ($n->data ?? []);
            return [
                'id'         => (string) $n->id,
                'title'      => $data['title']   ?? class_basename($n->type),
                'message'    => $data['message'] ?? ($data['body'] ?? ''),
                'url'        => $data['url']     ?? null,
                'read_at'    => optional($n->read_at)->toIso8601String(),
                'created_at' => $n->created_at->diffForHumans(),
                'source'     => 'notification',
            ];
        })->toArray();

        $unread = (int) $user->unreadNotifications()->count();

        // Fallback ke activity_log bila kosong (read-only)
        if (count($items) === 0) {
            $items = Activity::with('causer')->latest()->limit(8)->get()->map(function ($a) {
                $desc  = trim((string) $a->description) ?: 'Aktivitas sistem';
                $title = $a->log_name ? ucwords(str_replace(['_', '-'], ' ', $a->log_name)) : 'Aktivitas';
                $actor = $a->causer?->name ? $a->causer->name.' ' : '';
                return [
                    'id'         => 'act-'.$a->id,
                    'title'      => $title,
                    'message'    => $actor.$desc,
                    'url'        => null,
                    'read_at'    => null,
                    'created_at' => $a->created_at?->diffForHumans() ?? '',
                    'source'     => 'activity',
                ];
            })->toArray();
            $unread = 0;
        }

        return response()->json(
            ['unread_count' => $unread, 'notifications' => $items],
            200,
            ['Cache-Control' => 'no-store']
        );
    }

    /** Tandai satu notifikasi dibaca */
    public function markRead(Request $request, string $id)
    {
        $n = $request->user()->notifications()->whereKey($id)->firstOrFail();
        if (is_null($n->read_at)) {
            $n->markAsRead();
        }

        // Jika tombol "Buka" dari halaman index membawa redirect URL, arahkan ke sana
        $redirect = $request->input('redirect');

        // Jika dipanggil via fetch/AJAX (navbar), balas JSON
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()
            || str_contains((string) $request->header('Accept'), 'application/json')) {
            return response()->json(['success' => true]);
        }

        // Jika bukan AJAX, redirect saja
        if (is_string($redirect) && $redirect !== '') {
            return redirect()->to($redirect);
        }

        return back()->with('status', 'Notifikasi ditandai sebagai dibaca.');
    }

    /** Tandai semua sebagai dibaca */
    public function markAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        // Jika dipanggil dari navbar (AJAX), balas JSON
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()
            || str_contains((string) $request->header('Accept'), 'application/json')) {
            return response()->json(['success' => true]);
        }

        // Jika submit form biasa dari halaman /notifications, redirect kembali
        return back()->with('status', 'Semua notifikasi ditandai sebagai dibaca.');
    }
}
