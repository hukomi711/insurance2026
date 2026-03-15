import { companies } from './companies';
import { vehiclePlans } from './plans';
import { formatPrice } from '@/utils/formatters';

// Re-export for backward compatibility
export { formatPrice };

// دالة للحصول على بيانات الشركة
export function getCompany( companyId ) {
    return companies.find( c => c.id === companyId );
}

// دالة للحصول على بيانات الخطة مع الشركة
export function getPlanWithCompany( planId ) {
    const plan = vehiclePlans.find( p => p.id === planId );
    if ( !plan ) return null;
    return { ...plan, company: getCompany( plan.companyId ) };
}
