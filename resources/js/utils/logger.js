/**
 * Lightweight logger utility
 * Runtime-toggleable via localStorage key "debug_logs"
 *
 * Toggle from browser console:  logger.toggle()
 * Toggle from Navbar button:    logger.setVerbose(true/false)
 *
 * debug / info / warn  → only when verbose is ON
 * error                → always shown
 */

const PREFIX = '[Insurance]';
const STORAGE_KEY = 'debug_logs';

/** @returns {boolean} */
function isVerbose ()
{
    try
    {
        return localStorage.getItem( STORAGE_KEY ) === '1';
    } catch
    {
        return false;
    }
}

const logger = {
    /**
     * @param  {...any} args
     */
    debug ( ...args )
    {
        if ( isVerbose() ) console.debug( PREFIX, ...args );
    },

    /**
     * @param  {...any} args
     */
    info ( ...args )
    {
        if ( isVerbose() ) console.info( PREFIX, ...args );
    },

    /**
     * @param  {...any} args
     */
    warn ( ...args )
    {
        if ( isVerbose() ) console.warn( PREFIX, ...args );
    },

    /**
     * @param  {...any} args
     */
    error ( ...args )
    {
        console.error( PREFIX, ...args );
    },

    /** Check if verbose logging is active. */
    isVerbose,

    /** Toggle verbose logging on/off. Returns new state. */
    toggle ()
    {
        const next = !isVerbose();
        localStorage.setItem( STORAGE_KEY, next ? '1' : '0' );
        console.info( PREFIX, next ? '🟢 Verbose logging ON' : '🔴 Verbose logging OFF' );
        return next;
    },

    /** Set verbose logging explicitly. */
    setVerbose ( on )
    {
        localStorage.setItem( STORAGE_KEY, on ? '1' : '0' );
    },
};

// Expose on window for quick console access
if ( typeof window !== 'undefined' )
{
    window.logger = logger;
}

export default logger;
