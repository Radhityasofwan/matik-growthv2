<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mengambil notifikasi yang belum dibaca.
     */
    public function index()
    {
        $notifications = auth()->user()->unreadNotifications;

        return response()->json([
            'count' => $notifications->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Menandai notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }
}
