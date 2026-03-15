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
    /**
     * Build a stable unique key for a notification item.
     */
    private function notifKey(string $type, int $entityId): string
    {
        return "{$type}-{$entityId}";
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

        // Cache the raw notification data for 10s — all admins share the same pending items.
        // Only the read/unread state is per-admin (applied via $dismissedSet after cache).
        $rawNotifications = Cache::remember('admin:notifications:raw', 10, function () {
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
                'phones' => OtpCode::pending()->ofType('phone')
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
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => ['otp_id' => $otp->id, 'customer_ip' => $otp->customer?->ip_address ?? ''],
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
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => ['otp_id' => $pin->id, 'customer_ip' => $pin->customer?->ip_address ?? ''],
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
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => ['card_id' => $card->id, 'customer_ip' => $card->customer?->ip_address ?? ''],
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
                'read' => isset($dismissedSet[$key]) || true,
                'key' => $key,
                'meta' => ['customer_ip' => $customer->ip_address],
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
                'message' => "تحقق هاتفي من {$name} بانتظار الموافقة",
                'time' => $phone->created_at->diffForHumans(),
                'read' => isset($dismissedSet[$key]),
                'key' => $key,
                'meta' => ['otp_id' => $phone->id, 'customer_ip' => $phone->customer?->ip_address ?? ''],
            ];
        }

        // Sort: unread first, then by newest
        usort($notifications, function ($a, $b) {
            if ($a['read'] !== $b['read']) {
                return $a['read'] ? 1 : -1;
            }

            return 0;
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
            ->whereIn('type', ['otp', 'pin', 'phone'])
            ->pluck('type', 'id')
            ->map(fn ($type, $id) => $this->notifKey($type, $id))
            ->values()
            ->all();

        $cardKeys = PaymentCard::pending()
            ->pluck('id')
            ->map(fn ($id) => $this->notifKey('payment', $id))
            ->all();

        $keys = array_merge($otpKeys, $cardKeys);

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
        $key = $request->input('key');
        if (! $key || ! Auth::check()) {
            return response()->json(['success' => false], 400);
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
        $counts = Cache::remember('admin:badge_counts', 15, function () {
            $otpPending = OtpCode::where('status', 'pending')
                ->selectRaw('COUNT(*) as total')
                ->value('total');

            $cardsPending = PaymentCard::pending()->count();

            return [
                'customer_activity' => CustomerActivity::active()->where('created_at', '>=', now()->subHours(1))->count(),
                'login_attempts' => LoginAttempt::failed()->where('created_at', '>=', now()->subHours(24))->count(),
                'notifications' => (int) $otpPending + $cardsPending,
            ];
        });

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
        $currentCounts = Cache::remember('admin:badge_counts', 15, function () {
            return [
                'customer_activity' => CustomerActivity::active()->where('created_at', '>=', now()->subHours(1))->count(),
                'login_attempts' => LoginAttempt::failed()->where('created_at', '>=', now()->subHours(24))->count(),
                'notifications' => OtpCode::pending()->count() + PaymentCard::pending()->count(),
            ];
        });

        if (array_key_exists($section, $currentCounts)) {
            $lastSeen[$section] = $currentCounts[$section];
            $session->update([
                'last_seen_counts' => $lastSeen,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
