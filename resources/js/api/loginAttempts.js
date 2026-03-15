import request from './request';

/**
 * جلب قائمة محاولات تسجيل الدخول مع البحث والتصفية والإحصائيات
 * @param {Object} params - { page, search, status, per_page }
 */
export function fetchLoginAttempts ( params = {} )
{
    return request.get( '/admin/login-attempts', { params } );
}
