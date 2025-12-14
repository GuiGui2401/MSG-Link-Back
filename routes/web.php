<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminWebController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Weylo est principalement une API. Les routes web sont utilisées
| pour les redirections et les pages publiques simples.
|
*/

Route::get('/', function () {
    return redirect()->route('admin.login');
});

/*
|--------------------------------------------------------------------------
| Admin Dashboard Routes
|--------------------------------------------------------------------------
*/

// Admin Auth Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminWebController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminWebController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
});

// Protected Admin Routes
Route::prefix('admin')->middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard', [AdminWebController::class, 'dashboard']);
    Route::get('/analytics', [AdminWebController::class, 'analytics'])->name('admin.analytics');
    Route::get('/revenue', [AdminWebController::class, 'revenue'])->name('admin.revenue');
    Route::get('/settings', [AdminWebController::class, 'settings'])->name('admin.settings');

    // Profile
    Route::get('/profile', [AdminWebController::class, 'profile'])->name('admin.profile');
    Route::put('/profile', [AdminWebController::class, 'updateProfile'])->name('admin.profile.update');
    Route::put('/profile/password', [AdminWebController::class, 'updatePassword'])->name('admin.profile.password');
    Route::delete('/profile/avatar', [AdminWebController::class, 'deleteAvatar'])->name('admin.profile.avatar.delete');

    // Cache management
    Route::post('/cache/clear', [AdminWebController::class, 'clearCache'])->name('admin.cache.clear');
    Route::post('/cache/config', [AdminWebController::class, 'clearConfigCache'])->name('admin.cache.config');

    // Team Management (admins & moderators)
    Route::prefix('team')->group(function () {
        Route::get('/', [AdminWebController::class, 'team'])->name('admin.team.index');
        Route::get('/create', [AdminWebController::class, 'createTeamMember'])->name('admin.team.create');
        Route::post('/', [AdminWebController::class, 'storeTeamMember'])->name('admin.team.store');
    });

    // Users Management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminWebController::class, 'users'])->name('admin.users.index');
        Route::get('/{user}', [AdminWebController::class, 'showUser'])->name('admin.users.show');
        Route::get('/{user}/edit', [AdminWebController::class, 'editUser'])->name('admin.users.edit');
        Route::put('/{user}', [AdminWebController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/{user}', [AdminWebController::class, 'destroyUser'])->name('admin.users.destroy');
        Route::post('/{user}/ban', [AdminWebController::class, 'banUser'])->name('admin.users.ban');
        Route::post('/{user}/unban', [AdminWebController::class, 'unbanUser'])->name('admin.users.unban');
    });

    // Moderation
    Route::prefix('moderation')->group(function () {
        Route::get('/', [AdminWebController::class, 'moderation'])->name('admin.moderation.index');
        Route::get('/reports/{report}', [AdminWebController::class, 'showReport'])->name('admin.moderation.report');
        Route::post('/reports/{report}/resolve', [AdminWebController::class, 'resolveReport'])->name('admin.moderation.resolve');
        Route::post('/reports/{report}/dismiss', [AdminWebController::class, 'dismissReport'])->name('admin.moderation.dismiss');
        Route::delete('/reports/{report}/content', [AdminWebController::class, 'deleteReportedContent'])->name('admin.moderation.delete-content');
        Route::post('/reports/{report}/resolve-and-ban', [AdminWebController::class, 'resolveAndBan'])->name('admin.moderation.resolve-and-ban');
    });

    // Confessions
    Route::prefix('confessions')->group(function () {
        Route::get('/', [AdminWebController::class, 'confessions'])->name('admin.confessions.index');
        Route::get('/{confession}', [AdminWebController::class, 'showConfession'])->name('admin.confessions.show');
        Route::post('/{confession}/approve', [AdminWebController::class, 'approveConfession'])->name('admin.confessions.approve');
        Route::post('/{confession}/reject', [AdminWebController::class, 'rejectConfession'])->name('admin.confessions.reject');
        Route::delete('/{confession}', [AdminWebController::class, 'destroyConfession'])->name('admin.confessions.destroy');
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [AdminWebController::class, 'payments'])->name('admin.payments.index');
        Route::get('/{payment}', [AdminWebController::class, 'showPayment'])->name('admin.payments.show');
    });

    // Messages
    Route::prefix('messages')->group(function () {
        Route::get('/', [AdminWebController::class, 'messages'])->name('admin.messages.index');
        Route::delete('/{message}', [AdminWebController::class, 'destroyMessage'])->name('admin.messages.destroy');
    });

    // Gifts
    Route::prefix('gifts')->group(function () {
        Route::get('/', [AdminWebController::class, 'gifts'])->name('admin.gifts.index');
    });

    // Withdrawals
    Route::prefix('withdrawals')->group(function () {
        Route::get('/', [AdminWebController::class, 'withdrawals'])->name('admin.withdrawals.index');
        Route::get('/export', [AdminWebController::class, 'exportWithdrawals'])->name('admin.withdrawals.export');
        Route::get('/{withdrawal}', [AdminWebController::class, 'showWithdrawal'])->name('admin.withdrawals.show');
        Route::post('/{withdrawal}/process', [AdminWebController::class, 'processWithdrawal'])->name('admin.withdrawals.process');
        Route::post('/{withdrawal}/reject', [AdminWebController::class, 'rejectWithdrawal'])->name('admin.withdrawals.reject');
    });
});

// Redirection vers le profil utilisateur (pour les liens partagés)
Route::get('/u/{username}', function ($username) {
    // Rediriger vers le frontend
    $frontendUrl = config('msglink.urls.frontend');
    return redirect("{$frontendUrl}/u/{$username}");
})->name('user.profile');

// Page d'installation PWA
Route::get('/install', function () {
    return view('install');
})->name('pwa.install');

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});
