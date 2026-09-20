<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    /**
     * قائمة جميع المستخدمين
     */
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'created_at', 'updated_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * إنشاء مستخدم جديد — صلاحية حصرية لـ super_admin
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (! $authUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'إنشاء مستخدمين جدد متاح فقط للمشرف العام (Super Admin).',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['sometimes', 'nullable', 'string', Rule::in(User::ROLES)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // Least-privilege default — creator must explicitly opt into a higher tier.
            'role' => $request->input('role') ?: 'viewer',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'data' => $user->only('id', 'name', 'email', 'role', 'created_at'),
        ], 201);
    }

    /**
     * تحديث كلمة مرور مستخدم
     */
    public function updatePassword(Request $request, User $user): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        // Only allow: self password change, or any admin/super_admin changing others
        if ($user->id !== $authUser->id && ! $authUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بتغيير كلمة مرور مستخدم آخر.',
            ], 403);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث كلمة المرور بنجاح',
        ]);
    }

    /**
     * تغيير دور مستخدم — متاح لـ admin و super_admin
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if ($user->id === $authUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تغيير دورك الخاص.',
            ], 422);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(User::ROLES)],
        ]);

        $user->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث دور المستخدم بنجاح',
            'data' => $user->only('id', 'name', 'email', 'role', 'created_at'),
        ]);
    }

    /**
     * حذف مستخدم — صلاحية حصرية لـ super_admin
     */
    public function destroy(User $user): JsonResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (! $authUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'حذف المستخدمين متاح فقط للمشرف العام (Super Admin).',
            ], 403);
        }

        if ($user->id === $authUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف حسابك الخاص',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }
}
