<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // halaman daftar (Blade), bukan JSON feed
        $notifications = $user->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function feed(Request $request)
    {
        $user = $request->user();

        $items = $user->notifications()->latest()->limit(8)->get()->map(function ($n) {
            $data = $n->data ?? [];
            return [
                'id'         => $n->id,
                'title'      => $data['title']   ?? class_basename($n->type),
                'message'    => $data['message'] ?? ($data['body'] ?? ''),
                'url'        => $data['url']     ?? null,
                'read_at'    => optional($n->read_at)->toIso8601String(),
                'created_at' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $items,
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $n = $request->user()->notifications()->whereKey($id)->firstOrFail();
        if (is_null($n->read_at)) $n->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}
