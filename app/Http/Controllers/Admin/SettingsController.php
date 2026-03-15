<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Default settings structure
     */
    private const DEFAULTS = [
        'siteName'        => 'تأمينكم',
        'siteDescription' => 'منصة مقارنة أسعار التأمين في المملكة العربية السعودية',
        'contactEmail'    => 'info@example.com',
        'phone'           => '+966 11 234 5678',
        'notifications'   => [
            'weeklyReport' => ['label' => 'تقرير أسبوعي', 'description' => 'إرسال تقرير أداء أسبوعي', 'enabled' => true],
            'systemAlerts' => ['label' => 'تنبيهات النظام', 'description' => 'إشعارات صيانة وتحديثات النظام', 'enabled' => true],
        ],
    ];

    /**
     * إرجاع إعدادات المستخدم الحالي
     */
    public function index(Request $request): JsonResponse
    {
        $user     = $request->user();
        $settings = array_merge(self::DEFAULTS, $user->settings ?? []);

        return response()->json([
            'success'  => true,
            'settings' => $settings,
            'user'     => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role ?? 'admin',
            ],
        ]);
    }

    /**
     * تحديث الإعدادات
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'siteName'        => 'sometimes|string|max:100',
            'siteDescription' => 'sometimes|string|max:500',
            'contactEmail'    => 'sometimes|email|max:255',
            'phone'           => 'sometimes|string|max:30',
            'notifications'   => 'sometimes|array',
            'notifications.*.enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $current = $user->settings ?? [];
        $user->settings = array_merge($current, $validator->validated());
        $user->save();

        return response()->json([
            'success'  => true,
            'settings' => array_merge(self::DEFAULTS, $user->settings),
            'message'  => 'تم حفظ الإعدادات بنجاح',
        ]);
    }

    /**
     * تغيير كلمة المرور
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'errors'  => ['current_password' => ['كلمة المرور الحالية غير صحيحة']],
            ], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }
}
