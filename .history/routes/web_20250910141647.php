<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Sales\LeadController;
use App\Http\Controllers\Content\TaskController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WhatsApp\TemplateController;

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

// ===================================================================
// PUBLIC ROUTES
// ===================================================================
// Routes accessible by anyone, including guests.

// Health check endpoint for monitoring application status.
Route::get('/healthz', [HealthCheckController::class, 'check']);

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});


// ===================================================================
// AUTHENTICATED ROUTES
// ===================================================================
// Routes that require user to be logged in.

Route::middleware('auth')->group(function () {
    // Redirect root URL to the dashboard for a cleaner structure
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Auth & Profile
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('profile', [AuthController::class, 'profile'])->name('profile.show');
    Route::post('profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Sales Module
    Route::resource('leads', LeadController::class);

    // WhatsApp Automation Module
    Route::resource('whatsapp/templates', TemplateController::class)->except(['show']);

    // Tasking & Content Module
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Campaign Orchestration Module
    Route::resource('campaigns', CampaignController::class);

    // Reports Module
    Route::get('reports/sales-funnel/{format}', [DashboardController::class, 'downloadSalesFunnelReport'])
        ->name('reports.sales.funnel')
        ->where('format', 'pdf|excel');

    // Notifications Module
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

