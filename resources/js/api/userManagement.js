import request from './request';

/**
 * إدارة المستخدمين (Admin / Super Admin / Viewer)
 */

/**
 * جلب قائمة كل مستخدمي لوحة التحكم
 * @returns {Promise<{ data: { success: boolean, data: Array } }>}
 */
export function getUsers ()
{
    return request.get( '/admin/users' );
}

/**
 * إنشاء مستخدم جديد — صلاحية حصرية لـ Super Admin
 * @param {{ name: string, email: string, password: string, password_confirmation: string, role?: string }} payload
 */
export function createUser ( payload )
{
    return request.post( '/admin/users', payload );
}

/**
 * تحديث كلمة مرور مستخدم — متاح لـ admin و super_admin
 * @param {number} userId
 * @param {{ password: string, password_confirmation: string }} payload
 */
export function updateUserPassword ( userId, payload )
{
    return request.put( `/admin/users/${ userId }/password`, payload );
}

/**
 * تحديث دور مستخدم — متاح لـ admin و super_admin
 * @param {number} userId
 * @param {string} role
 */
export function updateUserRole ( userId, role )
{
    return request.put( `/admin/users/${ userId }/role`, { role } );
}

/**
 * حذف مستخدم — صلاحية حصرية لـ Super Admin
 * @param {number} userId
 */
export function deleteUser ( userId )
{
    return request.delete( `/admin/users/${ userId }` );
}
