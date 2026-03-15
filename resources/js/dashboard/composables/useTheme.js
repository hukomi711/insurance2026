import { ref, watch, readonly } from 'vue';

const STORAGE_KEY = 'admin-theme';
const VALID_THEMES = [ 'light', 'dark' ];

/**
 * Composable for admin dashboard theme management.
 * Persists preference to localStorage and applies data-admin-theme attribute.
 */
const currentTheme = ref( loadSaved() );

function loadSaved ()
{
    try
    {
        const saved = localStorage.getItem( STORAGE_KEY );
        if ( VALID_THEMES.includes( saved ) ) return saved;
    } catch ( _e ) { /* SSR / private browsing */ }
    return 'light'; // default
}

function applyTheme ( theme )
{
    const el = document.querySelector( '[data-admin-theme]' );
    if ( el ) el.setAttribute( 'data-admin-theme', theme );
}

watch( currentTheme, ( val ) =>
{
    try { localStorage.setItem( STORAGE_KEY, val ); } catch ( _e ) { /* noop */ }
    applyTheme( val );
} );

export function useTheme ()
{
    /** Toggle between light and dark */
    function toggle ()
    {
        currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
    }

    /** Set theme explicitly */
    function setTheme ( theme )
    {
        if ( VALID_THEMES.includes( theme ) )
        {
            currentTheme.value = theme;
        }
    }

    /** Initialize — call once in DashboardLayout onMounted */
    function init ()
    {
        applyTheme( currentTheme.value );
    }

    const isDark = ref( currentTheme.value === 'dark' );
    watch( currentTheme, ( val ) => { isDark.value = val === 'dark'; } );

    return {
        theme: readonly( currentTheme ),
        isDark: readonly( isDark ),
        toggle,
        setTheme,
        init,
    };
}
