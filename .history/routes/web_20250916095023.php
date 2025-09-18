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
use App\Http\Controllers\LeadFollowUpRuleController;

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

/** Webhook WAHA (publik, tanpa auth) */
Route::post('waha/webhook', [WahaController::class, 'webhook'])->name('waha.webhook');

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
    Route::resource('leads', LeadController::class);
    Route::post('/leads/import', [LeadController::class, 'import'])->name('leads.import');

    // -- PENAMBAHAN ROUTE SUBSCRIPTION --
    Route::resource('subscriptions', SubscriptionController::class)->except(['show', 'create', 'store', 'edit']);

    /** WhatsApp dari Leads (single & bulk) */
    Route::post('leads/{lead}/wa-send', [LeadController::class, 'waSend'])->name('leads.wa.send');
    Route::post('leads/wa-bulk-send',   [LeadController::class, 'waBulkSend'])->name('leads.wa.bulkSend');

    /** Penanda chat manual (dipanggil JS sebelum buka wa.me) */
    Route::post('leads/{lead}/mark-chatted', [LeadController::class, 'markChatted'])->name('leads.markChatted');
    /*
    |--------------------------------------------------------------------------
    | WhatsApp (Templates + Broadcast)
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
    | WAHA API (kirim pesan)
    |--------------------------------------------------------------------------
    */
    Route::prefix('waha')->name('waha.')->group(function () {
        Route::post('send-message',       [WahaController::class, 'sendMessage'])->name('sendMessage');
        Route::post('send-bulk-messages', [WahaController::class, 'sendBulkMessages'])->name('sendBulkMessages');
    });

    /*
    |--------------------------------------------------------------------------
    | Manajemen Waha Sender (CRUD + aksi + QR)
    |--------------------------------------------------------------------------
    */
    Route::resource('waha-senders', WahaSenderController::class);

    Route::post('waha-senders/{wahaSender}/set-default', [WahaSenderController::class, 'setDefault'])
        ->name('waha-senders.set-default');

    Route::get ('waha-senders/{wahaSender}/qr-status', [WahaSenderController::class, 'qrStatus'])
        ->name('waha-senders.qr.status');
    Route::post('waha-senders/{wahaSender}/qr-start',  [WahaSenderController::class, 'qrStart'])
        ->name('waha-senders.qr.start');
    Route::post('waha-senders/{wahaSender}/qr-logout', [WahaSenderController::class, 'qrLogout'])
        ->name('waha-senders.qr.logout');

    Route::get('waha-sessions/status-batch', [WahaSenderController::class, 'statusBatch'])
        ->name('waha.sessions.statusBatch');

    Route::get('/waha-senders/{wahaSender}/qr-image', [WahaSenderController::class, 'qrImage'])
        ->name('waha-senders.qr-image');

    Route::post('/waha-senders/{wahaSender}/auth-request-code', [WahaSenderController::class, 'authRequestCode'])
        ->name('waha-senders.auth-request-code');

    Route::resource('lead-follow-up-rules', LeadFollowUpRuleController::class)
        ->only(['index','store','update','destroy']);

});

require __DIR__ . '/auth.php';
