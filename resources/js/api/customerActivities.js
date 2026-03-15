import request from './request';

/**
 * جلب قائمة أنشطة العملاء مع البحث والتصفية والإحصائيات
 * @param {Object} params - { page, search, stage, status, per_page }
 */
export function fetchCustomerActivities ( params = {} )
{
    return request.get( '/admin/customer-activities', { params } );
}
