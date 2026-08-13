<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForceStepRequest;
use App\Models\AdminAction;
use App\Models\CustomerBlock;
use App\Models\CustomerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AdminCustomerForceController — إجراءات إدارية متقدمة على العملاء
 *
 * - forceStep:        تغيير خطوة العميل بالقوة
 * - getViewedStatus:  الحصول على حالة المشاهدة لعدة عملاء دفعة واحدة
 */
class AdminCustomerForceController extends Controller
{
    use NotifiesDashboard;

    /**
     * Block a customer by both the current IP address and browser session token.
     * POST /api/admin/customers/{id}/block
     */
    public function block(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();

        try {
            $customer = DB::transaction(function () use ($id, $admin) {
                $customer = CustomerProfile::lockForUpdate()->findOrFail($id);

                if (! $customer->ip_address && ! $customer->session_id) {
                    abort(422, 'لا تتوفر هوية شبكة أو جهاز لهذا العميل');
                }

                $block = CustomerBlock::firstOrCreate(
                    [
                        'ip_address' => $customer->ip_address,
                        'session_id' => $customer->session_id,
                    ],
                    [
                        'customer_profile_id' => $customer->id,
                        'blocked_by' => $admin?->id,
                    ],
                );

                CustomerProfile::query()
                    ->where(function ($query) use ($customer) {
                        if ($customer->ip_address) {
                            $query->where('ip_address', $customer->ip_address);
                        }
                        if ($customer->session_id) {
                            $customer->ip_address
                                ? $query->orWhere('session_id', $customer->session_id)
                                : $query->where('session_id', $customer->session_id);
                        }
                    })
                    ->update(['is_active' => false]);

                if ($block->wasRecentlyCreated) {
                    AdminAction::create([
                        'admin_id' => $admin?->id,
                        'action' => 'block_customer',
                        'target_type' => 'customer_profile',
                        'target_id' => $customer->id,
                        'meta' => [
                            'ip_address' => $customer->ip_address,
                            'has_session_id' => (bool) $customer->session_id,
                        ],
                    ]);
                }

                return $customer;
            });

            CustomerBlock::rememberBlocked($customer->ip_address, $customer->session_id);
            $this->notifyDashboard($customer->ip_address, 'customer_blocked');

            return response()->json([
                'success' => true,
                'message' => 'تم حظر العميل بنجاح',
                'data' => [
                    'customer_id' => $customer->id,
                    'is_active' => false,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Error blocking customer', [
                'admin_id' => $admin?->id,
                'customer_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حظر العميل',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Force customer to specific step (admin override)
     * POST /api/admin/customers/{id}/force-step
     */
    public function forceStep(ForceStepRequest $request, int $id): JsonResponse
    {
        $admin = $request->user();
        $step = (int) $request->input('step');
        $note = $request->input('note');

        // Calculate journey completion percentage (6 steps total)
        $percentage = intval(round(($step / 6) * 100));

        try {
            return DB::transaction(function () use ($id, $admin, $step, $percentage, $note) {
                // Lock customer record to prevent race conditions
                $customer = CustomerProfile::lockForUpdate()->findOrFail($id);

                // Store old values for audit
                $oldStep = $customer->current_step;
                $oldPercentage = $customer->journey_completion_percentage ?? $customer->completion_percentage;

                // Update customer journey
                $customer->current_step = $step;
                if ($customer->isFillable('journey_completion_percentage')) {
                    $customer->journey_completion_percentage = $percentage;
                }
                if ($customer->isFillable('completion_percentage')) {
                    $customer->completion_percentage = $percentage;
                }

                $customer->save();

                // Log admin action for audit trail
                AdminAction::create([
                    'admin_id'    => $admin->id,
                    'action'      => 'force_step',
                    'target_type' => 'customer_profile',
                    'target_id'   => $customer->id,
                    'meta'        => [
                        'from_step'       => $oldStep,
                        'to_step'         => $step,
                        'from_percentage' => $oldPercentage,
                        'to_percentage'   => $percentage,
                        'note'            => $note,
                    ],
                ]);

                // Notify admin dashboard (flush cache + broadcast on private dashboard channel)
                $this->notifyDashboard($customer->ip_address, 'admin_forced_step');

                Log::info('Admin forced customer step', [
                    'admin_id'    => $admin->id,
                    'customer_id' => $customer->id,
                    'from_step'   => $oldStep,
                    'to_step'     => $step,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث خطوة العميل بنجاح',
                    'data'    => $customer->fresh(),
                ], 200);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error forcing customer step', [
                'admin_id'    => $admin->id,
                'customer_id' => $id,
                'error'       => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث خطوة العميل',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get viewed status for multiple customers (batch)
     * POST /api/admin/customers/viewed-status
     */
    public function getViewedStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ips'   => 'required|array|max:100',
            'ips.*' => 'string|max:45',
        ]);

        $ips = $request->input('ips', []);

        if (empty($ips)) {
            return response()->json([
                'success' => true,
                'data'    => [],
            ]);
        }

        $customers = CustomerProfile::whereIn('ip_address', $ips)
            ->select('ip_address', 'data_viewed')
            ->get()
            ->keyBy('ip_address');

        return response()->json([
            'success' => true,
            'data'    => $customers,
        ]);
    }
}
