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

/**
 * جلب إعدادات الموقع العامة (واتساب، بيانات التواصل)
 */
export function fetchSiteSettings ()
{
    return request.get( '/admin/site-settings' );
}

/**
 * تحديث إعدادات الموقع العامة
 */
export function updateSiteSettings ( data )
{
    return request.put( '/admin/site-settings', data );
}
