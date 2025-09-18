<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\Content\AssetController;
use App\Http\Controllers\Content\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Sales\LeadController;
use App\Http\Controllers\Sales\SubscriptionController;
use App\Http\Controllers\WhatsApp\BroadcastController;
use App\Http\Controllers\WhatsApp\LogController;
use App\Http\Controllers\WhatsApp\TemplateController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile.show');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/sales-funnel-pdf', [DashboardController::class, 'exportSalesFunnelPdf'])->name('reports.sales.pdf');
    Route::get('/reports/sales-funnel-excel', [DashboardController::class, 'exportSalesFunnelExcel'])->name('reports.sales.excel');

    // Sales Module
    Route::resource('leads', LeadController::class);
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');

    // WhatsApp Module
    Route::resource('whatsapp/templates', TemplateController::class);
    Route::get('whatsapp/logs', [LogController::class, 'index'])->name('whatsapp.logs.index');
    Route::get('whatsapp/broadcasts/create', [BroadcastController::class, 'create'])->name('broadcasts.create'); // <-- NEW
    Route::post('whatsapp/broadcasts', [BroadcastController::class, 'store'])->name('broadcasts.store'); // <-- NEW

    // Content & Tasking
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::put('tasks/status/{task}', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::resource('assets', AssetController::class)->only(['index', 'store', 'destroy']);

    // Campaign Module
    Route::resource('campaigns', CampaignController::class);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

