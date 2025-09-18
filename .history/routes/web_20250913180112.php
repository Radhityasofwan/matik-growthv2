<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Sales\LeadController;
use App\Http\Controllers\Sales\SubscriptionController;
use App\Http\Controllers\WhatsApp\TemplateController;
use App\Http\Controllers\WhatsApp\BroadcastController;
use App\Http\Controllers\Content\TaskController;
use App\Http\Controllers\Content\AssetController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WahaController;
use App\Http\Controllers\WahaSenderController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});
Route::post('logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Sales
    |--------------------------------------------------------------------------
    */
    Route::resource('leads', LeadController::class)->except(['show']);
    Route::post('/leads/import', [LeadController::class, 'import'])->name('leads.import');
    Route::resource('subscriptions', SubscriptionController::class);

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (Templates + Broadcast)
    | - 1 menu utama: whatsapp.broadcast.create
    | - Kelola Sender ada di halaman terpisah (AJAX refresh dari waha-senders.index?json=1)
    |--------------------------------------------------------------------------
    */
    Route::resource('whatsapp-templates', TemplateController::class)
        ->except(['show'])
        ->names('whatsapp.templates');

    Route::get('whatsapp/broadcast',  [BroadcastController::class, 'create'])->name('whatsapp.broadcast.create');
    Route::post('whatsapp/broadcast', [BroadcastController::class, 'store'])->name('whatsapp.broadcast.store');

    /*
    |--------------------------------------------------------------------------
    | Content & Tasking
    |--------------------------------------------------------------------------
    */
    Route::resource('tasks', TaskController::class)->except(['show', 'create', 'edit']);
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::post('tasks/{task}/update-status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');

    /*
    |--------------------------------------------------------------------------
    | Campaigns
    |--------------------------------------------------------------------------
    */
    Route::resource('campaigns', CampaignController::class);

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    */
    Route::resource('assets', AssetController::class)->only(['index','store','destroy'])->names('assets');
    Route::get('assets/preview',  [AssetController::class, 'preview'])->name('assets.preview');
    Route::get('assets/download', [AssetController::class, 'download'])->name('assets.download');

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',   [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | WAHA API (kirim pesan + kontrol sesi)
    |--------------------------------------------------------------------------
    */
    Route::prefix('waha')->name('waha.')->group(function () {
        // Messages
        Route::post('send-message',       [WahaController::class, 'sendMessage'])->name('sendMessage');
        Route::post('send-bulk-messages', [WahaController::class, 'sendBulkMessages'])->name('sendBulkMessages');

        // Sessions & utilities
        Route::get ('sessions/{wahaSender}/status', [WahaController::class, 'status'])->name('sessions.status');
        Route::post('sessions/{wahaSender}/start',  [WahaController::class, 'start'])->name('sessions.start');
        Route::post('sessions/{wahaSender}/logout', [WahaController::class, 'logout'])->name('sessions.logout');
        Route::get ('sessions/{wahaSender}/qr',     [WahaController::class, 'qr'])->name('sessions.qr');
        Route::get ('sessions/status',              [WahaController::class, 'statusBatch'])->name('sessions.statusBatch'); // ?ids=1,2,3
        Route::post('check-number',                 [WahaController::class, 'checkNumber'])->name('checkNumber');
    });

    /*
    |--------------------------------------------------------------------------
    | Manajemen Waha Sender (CRUD + JSON untuk dropdown/refresh)
    |--------------------------------------------------------------------------
    */
    Route::resource('waha-senders', WahaSenderController::class)
        ->except(['show','create','edit']); // index, store, update, destroy

    Route::post('waha-senders/{wahaSender}/toggle-active', [WahaSenderController::class, 'toggleActive'])
        ->name('waha-senders.toggle-active');

    Route::post('waha-senders/{wahaSender}/set-default', [WahaSenderController::class, 'setDefault'])
        ->name('waha-senders.set-default');

    /** QR / Status / Start session */
    Route::get('waha-senders/{wahaSender}/qr', [WahaSenderController::class, 'qr'])
        ->name('waha-senders.qr');
    Route::post('waha-senders/{wahaSender}/restart', [WahaSenderController::class, 'restart'])
        ->name('waha-senders.restart');

});

Route::get('waha/sessions/check', [\App\Http\Controllers\WahaController::class, 'checkSessions'])
    ->name('waha.sessions.check');

require __DIR__.'/auth.php';
