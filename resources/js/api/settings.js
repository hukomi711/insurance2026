import request from './request';

/**
 * جلب إعدادات المستخدم الحالي
 */
export function fetchSettings ()
{
    return request.get( '/admin/settings' );
}

/**
 * تحديث الإعدادات
 */
export function updateSettings ( data )
{
    return request.put( '/admin/settings', data );
}

/**
 * تغيير كلمة المرور
 */
export function changePassword ( data )
{
    return request.post( '/admin/settings/password', data );
}
