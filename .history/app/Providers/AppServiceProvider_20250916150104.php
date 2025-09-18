use Illuminate\Support\Facades\View;

public function boot(): void
{
    // … kode boot lain …

    // Seed awal untuk navbar (tanpa JS pun tetap ada angka awal)
    View::composer('partials.navbar', function ($view) {
        $user = auth()->user();
        $unread = $user ? $user->unreadNotifications()->count() : 0;

        // optional: 3–8 item terakhir untuk pre-render di dropdown
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
