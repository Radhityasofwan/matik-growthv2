<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Sales\LeadController;
use App\Http\Controllers\Sales\SubscriptionController;
use App\Http\Controllers\WhatsApp\TemplateController;
use App\Http\Controllers\WhatsApp\BroadcastController;
use App\Http\Controllers\WhatsApp\LogController;
use App\Http\Controllers\Content\TaskController;
use App\Http\Controllers\Content\AssetController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// --- Public Routes ---
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');


// --- Authenticated Routes ---
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Sales Module
    Route::resource('leads', LeadController::class);
    Route::resource('subscriptions', SubscriptionController::class);

    // WhatsApp Module
    // --- FIX: Menggunakan name() untuk memastikan nama rute konsisten ---
    Route::resource('whatsapp-templates', TemplateController::class)
         ->except(['show'])
         ->names('whatsapp.templates');

    Route::get('whatsapp/broadcast', [BroadcastController::class, 'create'])->name('whatsapp.broadcast.create');
    Route::post('whatsapp/broadcast', [BroadcastController::class, 'store'])->name('whatsapp.broadcast.store');
    Route::get('whatsapp/logs', [LogController::class, 'index'])->name('whatsapp.logs.index');

    // Content & Tasking Module
    Route::resource('tasks', \App\Http\Controllers\Content\TaskController::class)
        ->except(['show', 'create', 'edit']);

    // Endpoint edit JSON untuk modal
    Route::get('tasks/{task}/edit', [\App\Http\Controllers\Content\TaskController::class, 'edit'])
        ->name('tasks.edit');

    Route::post('tasks/{task}/update-status', [\App\Http\Controllers\Content\TaskController::class, 'updateStatus'])
        ->name('tasks.updateStatus');

    // Campaign Module
    Route::resource('campaigns', CampaignController::class);

    // Reports
    Route::get('reports/sales-funnel-pdf', [DashboardController::class, 'exportSalesFunnelPdf'])->name('reports.sales-funnel.pdf');
    Route::get('reports/sales-funnel-excel', [DashboardController::class, 'exportSalesFunnelExcel'])->name('reports.sales-funnel.excel');

    Route::resource('assets', AssetController::class)
    ->only(['index','store','destroy'])
    ->names('assets');

    // Stream preview & download agar tidak bergantung pada /storage
    Route::get('assets/preview', [AssetController::class, 'preview'])->name('assets.preview');
    Route::get('assets/download', [AssetController::class, 'download'])->name('assets.download');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');

    // Profile
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
});

