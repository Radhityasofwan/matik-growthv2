<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class NotificationController extends Controller
{
    // Halaman daftar notifikasi (Blade)
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest()->paginate(20);

        // Jika kosong sama sekali, fallback tampilkan activity terbaru sebagai info
        $activities = [];
        if ($notifications->count() === 0) {
            $activities = Activity::with('causer')->latest()->limit(20)->get();
        }

        return view('notifications.index', compact('notifications', 'activities'));
    }

    // Feed JSON untuk navbar (dipolling Alpine)
    public function feed(Request $request)
    {
        $user = $request->user();

        // Ambil Laravel notifications dulu
        $items = $user->notifications()->latest()->limit(8)->get()->map(function ($n) {
            $data = $n->data ?? [];
            return [
                'id'         => $n->id,
                'title'      => $data['title']   ?? class_basename($n->type),
                'message'    => $data['message'] ?? ($data['body'] ?? ''),
                'url'        => $data['url']     ?? null,
                'read_at'    => optional($n->read_at)->toIso8601String(),
                'created_at' => $n->created_at->diffForHumans(),
                'source'     => 'notification',
            ];
        })->toArray();

        $unread = (int) $user->unreadNotifications()->count();

        // Jika benar-benar kosong, fallback ke activity_log
        if (count($items) === 0) {
            $items = Activity::with('causer')->latest()->limit(8)->get()->map(function ($a) {
                $desc = trim((string) $a->description) ?: 'Aktivitas sistem';
                $title = $a->log_name ? ucwords(str_replace(['_', '-'], ' ', $a->log_name)) : 'Aktivitas';
                $actor = $a->causer?->name ? $a->causer->name . ' ' : '';
                return [
                    'id'         => 'act-'.$a->id, // pseudo id (read-only)
                    'title'      => $title,
                    'message'    => $actor . $desc,
                    'url'        => null,
                    'read_at'    => null, // dianggap unread untuk visual
                    'created_at' => $a->created_at?->diffForHumans() ?? '',
                    'source'     => 'activity', // penanda: bukan Laravel Notification
                ];
            })->toArray();

            // unread tetap 0 (karena tidak tersimpan sebagai notification)
            $unread = 0;
        }

        return response()->json([
            'unread_count'  => $unread,
            'notifications' => $items,
        ]);
    }

    // Tandai 1 notifikasi dibaca
    public function markRead(Request $request, string $id)
    {
        $n = $request->user()->notifications()->whereKey($id)->firstOrFail();
        if (is_null($n->read_at)) $n->markAsRead();

        return response()->json(['success' => true]);
    }

    // Tandai semua dibaca
    public function markAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}
