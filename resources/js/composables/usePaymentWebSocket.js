import { ref, onUnmounted } from 'vue';
import logger from '@/utils/logger';
import { getEcho } from '@/services/echo';

/**
 * usePaymentWebSocket
 *
 * Reusable composable for the WebSocket + polling pattern shared across
 * PaymentWaitingPage, OtpPage, and CardPinPage.
 *
 * @param {Object} options
 * @param {string}   options.channelPrefix   — e.g. 'payment', 'otp', 'pin'
 * @param {string}   options.approvedEvent   — e.g. 'PaymentApproved', 'OtpApproved', 'PinApproved'
 * @param {string}   options.rejectedEvent   — e.g. 'PaymentRejected', 'OtpRejected', 'PinRejected'
 * @param {Function} options.onApproved      — callback(event) when approved
 * @param {Function} options.onRejected      — callback(event) when rejected
 * @param {Function} options.pollFn          — async function that performs one poll check. Should call
 *                                             onApproved/onRejected internally when status is conclusive.
 * @param {number}   [options.pollInterval=5000] — polling interval in ms
 * @param {string}   [options.logTag='WS']   — prefix for log messages
 */
export function usePaymentWebSocket ( options )
{
    const {
        channelPrefix,
        approvedEvent,
        rejectedEvent,
        onApproved,
        onRejected,
        pollFn,
        pollInterval = 5000,
        logTag = 'WS',
    } = options;

    const status = ref( 'pending' ); // pending | approved | rejected
    const rejectionReason = ref( '' );

    let echoChannel = null;
    let isUnmounted = false;
    let pollTimer = null;
    let customerIpValue = '';

    // ─── WebSocket Setup ────────────────────────────────────────────

    /**
     * Subscribe to the Echo channel and start polling as a safety net.
     * @param {string} customerIp — the IP segment for the channel name
     */
    async function setup ( customerIp )
    {
        // Reset unmounted flag so composable can be reused (e.g. modal reopen)
        isUnmounted = false;
        customerIpValue = customerIp;

        if ( !customerIp )
        {
            logger.warn( `[${ logTag }] No customer IP — using polling only` );
            startPolling();
            return;
        }

        const channelName = `${ channelPrefix }.${ customerIp }`;

        try
        {
            const echo = await getEcho();
            if ( isUnmounted ) return;

            if ( !echo )
            {
                logger.warn( `[${ logTag }] Echo/Reverb not available — falling back to polling` );
                startPolling();
                return;
            }

            logger.debug( `[${ logTag }] Subscribing to channel:`, channelName );

            echoChannel = echo.channel( channelName );

            // Listen using dot-prefixed event names only to avoid duplicate handler invocation
            echoChannel.listen( `.${ approvedEvent }`, handleApproved );
            echoChannel.listen( `.${ rejectedEvent }`, handleRejected );

            logger.debug( `[${ logTag }] WebSocket listener setup complete` );
        } catch ( err )
        {
            logger.warn( `[${ logTag }] Echo setup failed for ${ channelName }:`, err?.message || err );
        }

        // Always start polling alongside WebSocket
        startPolling();
    }

    // ─── Event Handlers ─────────────────────────────────────────────

    function handleApproved ( event )
    {
        if ( isUnmounted || status.value !== 'pending' ) return;
        status.value = 'approved';
        stopPolling();
        onApproved( event );
    }

    function handleRejected ( event )
    {
        if ( isUnmounted || status.value !== 'pending' ) return;
        status.value = 'rejected';
        rejectionReason.value = event.reason || '';
        stopPolling();
        onRejected( event );
    }

    // ─── Polling ────────────────────────────────────────────────────

    function startPolling ()
    {
        if ( pollTimer || !pollFn ) return;

        // Immediate first poll — catches approvals that happened before WS subscribed
        runPoll();

        pollTimer = setInterval( () => runPoll(), pollInterval );
    }

    async function runPoll ()
    {
        if ( isUnmounted ) { stopPolling(); return; }
        if ( status.value !== 'pending' ) return;
        try
        {
            await pollFn( { handleApproved, handleRejected } );
        } catch ( err )
        {
            logger.warn( `[${ logTag }] Poll error:`, err?.message || err );
        }
    }

    function stopPolling ()
    {
        if ( pollTimer )
        {
            clearInterval( pollTimer );
            pollTimer = null;
        }
    }

    // ─── Cleanup ────────────────────────────────────────────────────

    function cleanup ()
    {
        isUnmounted = true;
        stopPolling();

        if ( echoChannel && customerIpValue )
        {
            try
            {
                window.Echo?.leave( `${ channelPrefix }.${ customerIpValue }` );
            } catch { /* silent */ }
            echoChannel = null;
        }
    }

    // Auto-cleanup on component unmount
    onUnmounted( cleanup );

    return {
        /** Reactive status: 'pending' | 'approved' | 'rejected' */
        status,
        /** Reactive rejection reason */
        rejectionReason,
        /** Start listening — call from onMounted once you have customerIp */
        setup,
        /** Manually stop everything (auto-called on unmount) */
        cleanup,
        /** Stop polling only */
        stopPolling,
    };
}

