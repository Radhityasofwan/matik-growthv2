<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Fetch the user's latest notifications.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // --- OPTIMIZED: Fetch the 20 latest notifications in a single query ---
        $notifications = $user->notifications()->latest()->limit(20)->get();

        // --- OPTIMIZED: Map the collection just once ---
        $formattedNotifications = $notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'data' => $n->data,
                'created_at' => $n->created_at,
                'created_at_human' => $n->created_at?->diffForHumans(),
            ];
        });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $formattedNotifications,
        ]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}

