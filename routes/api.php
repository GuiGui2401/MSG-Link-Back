<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ConfessionController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\GiftController;
use App\Http\Controllers\Api\V1\PremiumController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ==================== AUTH ROUTES (Public) ====================
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // ==================== PUBLIC USER PROFILE ====================
    Route::get('/users/{username}', [UserController::class, 'show']);

    // ==================== PUBLIC CONFESSIONS FEED ====================
    Route::get('/confessions', [ConfessionController::class, 'index']);
    Route::get('/confessions/{confession}', [ConfessionController::class, 'show']);

    // ==================== PUBLIC GIFTS CATALOG ====================
    Route::get('/gifts', [GiftController::class, 'index']);
    Route::get('/gifts/{gift}', [GiftController::class, 'show']);

    // ==================== PREMIUM PRICING ====================
    Route::get('/premium/pricing', [PremiumController::class, 'pricing']);

    // ==================== PAYMENT WEBHOOKS (Public) ====================
    Route::prefix('payments')->group(function () {
        Route::post('/webhook/cinetpay', [PaymentController::class, 'webhookCinetPay']);
        Route::post('/webhook/ligosapp', [PaymentController::class, 'webhookLigosApp']);
        Route::post('/webhook/intouch', [PaymentController::class, 'webhookIntouch']);
    });

    // ==================== AUTHENTICATED ROUTES ====================
    Route::middleware('auth:sanctum')->group(function () {

        // ==================== AUTH ====================
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
            Route::post('/resend-email-verification', [AuthController::class, 'resendEmailVerification']);
            Route::post('/verify-phone', [AuthController::class, 'verifyPhone']);
        });

        // ==================== USERS ====================
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::put('/profile', [UserController::class, 'updateProfile']);
            Route::put('/settings', [UserController::class, 'updateSettings']);
            Route::put('/password', [UserController::class, 'changePassword']);
            Route::post('/avatar', [UserController::class, 'uploadAvatar']);
            Route::delete('/avatar', [UserController::class, 'deleteAvatar']);
            Route::get('/dashboard', [UserController::class, 'dashboard']);
            Route::post('/fcm-token', [UserController::class, 'saveFcmToken']);
            Route::delete('/account', [UserController::class, 'deleteAccount']);
            Route::get('/share-link', [UserController::class, 'shareLink']);
            
            // Blocages
            Route::get('/blocked', [UserController::class, 'blockedUsers']);
            Route::post('/{username}/block', [UserController::class, 'block']);
            Route::delete('/{username}/block', [UserController::class, 'unblock']);
        });

        // ==================== MESSAGES ANONYMES ====================
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::get('/sent', [MessageController::class, 'sent']);
            Route::get('/stats', [MessageController::class, 'stats']);
            Route::post('/read-all', [MessageController::class, 'markAllAsRead']);
            Route::get('/{message}', [MessageController::class, 'show']);
            Route::post('/send/{username}', [MessageController::class, 'send']);
            Route::post('/{message}/reveal', [MessageController::class, 'reveal']);
            Route::post('/{message}/report', [MessageController::class, 'report']);
            Route::delete('/{message}', [MessageController::class, 'destroy']);
        });

        // ==================== CONFESSIONS ====================
        Route::prefix('confessions')->group(function () {
            Route::get('/received', [ConfessionController::class, 'received']);
            Route::get('/sent', [ConfessionController::class, 'sent']);
            Route::get('/stats', [ConfessionController::class, 'stats']);
            Route::post('/', [ConfessionController::class, 'store']);
            Route::post('/{confession}/like', [ConfessionController::class, 'like']);
            Route::delete('/{confession}/like', [ConfessionController::class, 'unlike']);
            Route::post('/{confession}/reveal', [ConfessionController::class, 'reveal']);
            Route::post('/{confession}/report', [ConfessionController::class, 'report']);
            Route::delete('/{confession}', [ConfessionController::class, 'destroy']);
        });

        // ==================== CHAT ====================
        Route::prefix('chat')->group(function () {
            Route::get('/conversations', [ChatController::class, 'conversations']);
            Route::post('/conversations', [ChatController::class, 'start']);
            Route::get('/conversations/{conversation}', [ChatController::class, 'show']);
            Route::get('/conversations/{conversation}/messages', [ChatController::class, 'messages']);
            Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
            Route::post('/conversations/{conversation}/read', [ChatController::class, 'markAsRead']);
            Route::post('/conversations/{conversation}/gift', [GiftController::class, 'sendInConversation']);
            Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy']);
            Route::get('/stats', [ChatController::class, 'stats']);
            Route::get('/user-status/{username}', [ChatController::class, 'userStatus']);
            Route::post('/presence', [ChatController::class, 'updatePresence']);
        });

        // ==================== CADEAUX ====================
        Route::prefix('gifts')->group(function () {
            Route::get('/received', [GiftController::class, 'received']);
            Route::get('/sent', [GiftController::class, 'sent']);
            Route::get('/stats', [GiftController::class, 'stats']);
            Route::post('/send', [GiftController::class, 'send']);
        });

        // ==================== PREMIUM ====================
        Route::prefix('premium')->group(function () {
            Route::get('/subscriptions', [PremiumController::class, 'index']);
            Route::get('/subscriptions/active', [PremiumController::class, 'active']);
            Route::post('/subscribe/message/{message}', [PremiumController::class, 'subscribeToMessage']);
            Route::post('/subscribe/conversation/{conversation}', [PremiumController::class, 'subscribeToConversation']);
            Route::post('/cancel/{subscription}', [PremiumController::class, 'cancel']);
            Route::get('/check', [PremiumController::class, 'check']);
        });

        // ==================== WALLET ====================
        Route::prefix('wallet')->group(function () {
            Route::get('/', [WalletController::class, 'index']);
            Route::get('/transactions', [WalletController::class, 'transactions']);
            Route::get('/stats', [WalletController::class, 'stats']);
            Route::get('/withdrawal-methods', [WalletController::class, 'withdrawalMethods']);
            Route::post('/withdraw', [WalletController::class, 'withdraw']);
            Route::get('/withdrawals', [WalletController::class, 'withdrawals']);
            Route::get('/withdrawals/{withdrawal}', [WalletController::class, 'showWithdrawal']);
            Route::delete('/withdrawals/{withdrawal}', [WalletController::class, 'cancelWithdrawal']);
        });

        // ==================== NOTIFICATIONS ====================
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{notification}', [NotificationController::class, 'destroy']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        });

        // ==================== PAYMENTS ====================
        Route::prefix('payments')->group(function () {
            Route::get('/status/{reference}', [PaymentController::class, 'checkStatus']);
        });

        // ==================== ADMIN ROUTES ====================
        Route::middleware('admin')->prefix('admin')->group(function () {

            // Dashboard
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);
            Route::get('/analytics', [AdminDashboardController::class, 'analytics']);
            Route::get('/revenue', [AdminDashboardController::class, 'revenue']);
            Route::get('/recent-activity', [AdminDashboardController::class, 'recentActivity']);

            // Users Management
            Route::prefix('users')->group(function () {
                Route::get('/', [AdminUserController::class, 'index']);
                Route::get('/stats', [AdminUserController::class, 'stats']);
                Route::get('/{user}', [AdminUserController::class, 'show']);
                Route::put('/{user}', [AdminUserController::class, 'update']);
                Route::post('/{user}/ban', [AdminUserController::class, 'ban']);
                Route::post('/{user}/unban', [AdminUserController::class, 'unban']);
                Route::delete('/{user}', [AdminUserController::class, 'destroy']);
                Route::get('/{user}/logs', [AdminUserController::class, 'adminLogs']);
            });

            // Moderation
            Route::prefix('moderation')->group(function () {
                // Confessions
                Route::get('/confessions', [ModerationController::class, 'confessions']);
                Route::post('/confessions/{confession}/approve', [ModerationController::class, 'approveConfession']);
                Route::post('/confessions/{confession}/reject', [ModerationController::class, 'rejectConfession']);
                Route::delete('/confessions/{confession}', [ModerationController::class, 'deleteConfession']);

                // Reports
                Route::get('/reports', [ModerationController::class, 'reports']);
                Route::get('/reports/{report}', [ModerationController::class, 'showReport']);
                Route::post('/reports/{report}/resolve', [ModerationController::class, 'resolveReport']);
                Route::post('/reports/{report}/dismiss', [ModerationController::class, 'dismissReport']);
                Route::post('/reports/{report}/resolve-and-ban', [ModerationController::class, 'resolveAndBan']);

                // Stats
                Route::get('/stats', [ModerationController::class, 'stats']);
            });

            // Withdrawals
            Route::prefix('withdrawals')->group(function () {
                Route::get('/', [AdminWithdrawalController::class, 'index']);
                Route::get('/stats', [AdminWithdrawalController::class, 'stats']);
                Route::get('/export', [AdminWithdrawalController::class, 'export']);
                Route::get('/{withdrawal}', [AdminWithdrawalController::class, 'show']);
                Route::post('/{withdrawal}/process', [AdminWithdrawalController::class, 'process']);
                Route::post('/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject']);
            });
        });
    });
});
