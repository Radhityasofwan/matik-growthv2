<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Seed awal untuk navbar (tanpa JS pun tetap ada angka awal)
        View::composer('partials.navbar', function ($view) {
            $user = auth()->user();
            $unread = $user ? $user->unreadNotifications()->count() : 0;

            // optional: 5 item terakhir untuk pre-render di dropdown
            $preview = $user
                ? $user->notifications()->latest()->limit(5)->get()->map(function ($n) {
                    $data = $n->data ?? [];
                    return [
                        'id'         => $n->id,
                        'title'      => $data['title']   ?? class_basename($n->type),
                        'message'    => $data['message'] ?? ($data['body'] ?? ''),
                        'url'        => $data['url']     ?? null,
                        'read_at'    => optional($n->read_at)->toIso8601String(),
                        'created_at' => $n->created_at->diffForHumans(),
                    ];
                })->toArray()
                : [];

            $view->with('unreadNotificationsCount', $unread);
            $view->with('notificationsPreview', $preview);
        });
    }
}
