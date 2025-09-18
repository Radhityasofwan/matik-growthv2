<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\Content\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Sales\LeadController;
use App\Http\Controllers\Sales\SubscriptionController;
use App\Http\Controllers\WhatsApp\TemplateController;
use Illuminate\Support\Facades\Route;


// Public routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/healthz', [HealthCheckController::class, 'check']);

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile.show');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Sales Module
    Route::resource('leads', LeadController::class);
    Route::resource('subscriptions', SubscriptionController::class)->only(['index']);

    // WhatsApp Module
    Route::resource('whatsapp/templates', TemplateController::class)->except(['show']);

    // Tasking Module
    Route::resource('tasks', TaskController::class)->except(['show', 'edit', 'create']);

    // Campaign Module
    Route::resource('campaigns', CampaignController::class);

    // Reports
    Route::get('/reports/sales-funnel-pdf', [DashboardController::class, 'exportSalesFunnelPdf'])->name('reports.sales-funnel-pdf');
    Route::get('/reports/sales-funnel-excel', [DashboardController::class, 'exportSalesFunnelExcel'])->name('reports.sales-funnel-excel');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
});

