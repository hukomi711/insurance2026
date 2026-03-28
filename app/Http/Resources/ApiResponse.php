<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;

/**
 * API Response Helper
 * توحيد استجابات API مع دعم اللغتين العربية والإنجليزية
 */
class ApiResponse
{
    /**
     * استجابة ناجحة
     */
    public static function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'تمت العملية بنجاح',
            'en' => 'Operation completed successfully',
        ];

        return response()->json([
            'success' => true,
            'message' => $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            'data'    => $data,
        ], $code);
    }

    /**
     * استجابة فشل
     */
    public static function error(string $message, mixed $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * استجابة validation error
     */
    public static function validationError(array $errors, ?string $message = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'البيانات المدخلة غير صحيحة',
            'en' => 'The given data was invalid',
        ];

        return self::error(
            $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            $errors,
            422
        );
    }

    /**
     * استجابة غير مصرح (Unauthorized)
     */
    public static function unauthorized(?string $message = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'غير مصرح بالوصول',
            'en' => 'Unauthorized access',
        ];

        return self::error(
            $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            null,
            401
        );
    }

    /**
     * استجابة ممنوع (Forbidden)
     */
    public static function forbidden(?string $message = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'لا تملك الصلاحية للقيام بهذا الإجراء',
            'en' => 'You do not have permission to perform this action',
        ];

        return self::error(
            $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            null,
            403
        );
    }

    /**
     * استجابة غير موجود (Not Found)
     */
    public static function notFound(?string $message = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'الصفحة أو البيانات المطلوبة غير موجودة',
            'en' => 'The requested resource was not found',
        ];

        return self::error(
            $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            null,
            404
        );
    }

    /**
     * استجابة خطأ في الخادم
     */
    public static function serverError(?string $message = null, mixed $debug = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'حدث خطأ في الخادم، يرجى المحاولة مرة أخرى',
            'en' => 'An internal server error occurred. Please try again later',
        ];

        $response = [
            'success' => false,
            'message' => $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
        ];

        if (config('app.debug') && $debug !== null) {
            $response['debug'] = $debug;
        }

        return response()->json($response, 500);
    }

    /**
     * استجابة مع pagination
     */
    public static function paginated(mixed $data, ?string $message = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'تم جلب البيانات بنجاح',
            'en' => 'Data retrieved successfully',
        ];

        return response()->json([
            'success' => true,
            'message' => $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            'data'    => $data->items(),
            'meta'    => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'from'         => $data->firstItem(),
                'to'           => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last'  => $data->url($data->lastPage()),
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * استجابة عند الإنشاء بنجاح (Created)
     */
    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'تم الإنشاء بنجاح',
            'en' => 'Resource created successfully',
        ];

        return self::success(
            $data,
            $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            201
        );
    }

    /**
     * استجابة عند التحديث بنجاح
     */
    public static function updated(mixed $data = null, ?string $message = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'تم التحديث بنجاح',
            'en' => 'Resource updated successfully',
        ];

        return self::success(
            $data,
            $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            200
        );
    }

    /**
     * استجابة عند الحذف بنجاح
     */
    public static function deleted(?string $message = null): JsonResponse
    {
        $locale = app()->getLocale();

        $defaultMessages = [
            'ar' => 'تم الحذف بنجاح',
            'en' => 'Resource deleted successfully',
        ];

        return self::success(
            null,
            $message ?? $defaultMessages[$locale] ?? $defaultMessages['ar'],
            200
        );
    }

    /**
     * استجابة بدون محتوى (No Content)
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
