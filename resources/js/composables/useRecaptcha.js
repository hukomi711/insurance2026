import logger from '@/utils/logger';

const SCRIPT_ID = 'google-recaptcha-script';
const SCRIPT_SRC = 'https://www.google.com/recaptcha/api.js?render=explicit';
const READY_TIMEOUT_MS = 7000;

let scriptPromise = null;

function getSiteKey ()
{
    const meta = document.querySelector( 'meta[name="recaptcha-site-key"]' );
    return meta?.getAttribute( 'content' ) || '';
}

function getEnabledFlag ()
{
    const meta = document.querySelector( 'meta[name="recaptcha-enabled"]' );
    return meta?.getAttribute( 'content' ) === 'true';
}

function waitForGrecaptchaReady ()
{
    const startedAt = Date.now();
    return new Promise( ( resolve, reject ) =>
    {
        const tick = () =>
        {
            const grecaptcha = window.grecaptcha;
            if ( grecaptcha && typeof grecaptcha.render === 'function' )
            {
                return resolve( grecaptcha );
            }

            if ( Date.now() - startedAt >= READY_TIMEOUT_MS )
            {
                return reject( new Error( 'reCAPTCHA ready timeout' ) );
            }

            setTimeout( tick, 50 );
        };

        tick();
    } );
}

function loadScript ()
{
    if ( scriptPromise ) return scriptPromise;

    scriptPromise = new Promise( ( resolve, reject ) =>
    {
        const existing = document.getElementById( SCRIPT_ID );
        if ( existing )
        {
            waitForGrecaptchaReady().then( resolve ).catch( reject );
            return;
        }

        const script = document.createElement( 'script' );
        script.id = SCRIPT_ID;
        script.src = SCRIPT_SRC;
        script.async = true;
        script.defer = true;
        script.onload = () =>
        {
            waitForGrecaptchaReady().then( resolve ).catch( reject );
        };
        script.onerror = () => reject( new Error( 'Failed to load reCAPTCHA script' ) );

        document.head.appendChild( script );
    } ).catch( err =>
    {
        scriptPromise = null;
        throw err;
    } );

    return scriptPromise;
}

export async function getRecaptchaToken ( action = 'submit_form' )
{
    if ( !getEnabledFlag() ) return '';

    const siteKey = getSiteKey();
    if ( !siteKey )
    {
        logger.warn( '[reCAPTCHA] site key is missing while CAPTCHA is enabled' );
        return '';
    }

    const grecaptcha = await loadScript();

    return new Promise( ( resolve, reject ) =>
    {
        const container = document.createElement( 'div' );
        container.style.position = 'fixed';
        container.style.left = '-9999px';
        container.style.top = '0';
        document.body.appendChild( container );

        let widgetId = null;
        const cleanup = () =>
        {
            try
            {
                if ( widgetId !== null && typeof grecaptcha.reset === 'function' )
                {
                    grecaptcha.reset( widgetId );
                }
            }
            catch
            {
                // noop
            }

            container.remove();
        };

        try
        {
            widgetId = grecaptcha.render( container, {
                sitekey: siteKey,
                size: 'invisible',
                callback: token =>
                {
                    cleanup();
                    resolve( String( token || '' ) );
                },
                'error-callback': () =>
                {
                    cleanup();
                    reject( new Error( 'reCAPTCHA verification failed' ) );
                },
                'expired-callback': () =>
                {
                    cleanup();
                    reject( new Error( 'reCAPTCHA token expired' ) );
                },
            } );

            // For invisible v2, execute by widget id only.
            // We keep `action` in the function signature for future v3 support.
            void action;
            grecaptcha.execute( widgetId );
        }
        catch ( err )
        {
            cleanup();
            reject( err instanceof Error ? err : new Error( 'reCAPTCHA execution failed' ) );
        }
    } );
}

export function isRecaptchaEnabled ()
{
    return getEnabledFlag();
}
