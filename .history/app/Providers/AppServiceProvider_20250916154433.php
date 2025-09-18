<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Seed awal untuk navbar
        View::composer('partials.navbar', function ($view) {
            $user = auth()->user();

            $unread = $user ? $user->unreadNotifications()->count() : 0;
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
                        'source'     => 'notification',
                    ];
                })->toArray()
                : [];

            // Fallback: jika notifications kosong, pakai activity terbaru untuk preview
            if ($user && count($preview) === 0) {
                $preview = Activity::with('causer')->latest()->limit(5)->get()->map(function ($a) {
                    $desc = trim((string) $a->description) ?: 'Aktivitas sistem';
                    $title = $a->log_name ? ucwords(str_replace(['_', '-'], ' ', $a->log_name)) : 'Aktivitas';
                    $actor = $a->causer?->name ? $a->causer->name . ' ' : '';
                    return [
                        'id'         => 'act-'.$a->id,
                        'title'      => $title,
                        'message'    => $actor . $desc,
                        'url'        => null,
                        'read_at'    => null,
                        'created_at' => $a->created_at?->diffForHumans() ?? '',
                        'source'     => 'activity',
                    ];
                })->toArray();
            }

            $view->with('unreadNotificationsCount', $unread);
            $view->with('notificationsPreview', $preview);
        });
    }
}
