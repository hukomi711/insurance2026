import { createI18n } from 'vue-i18n';
import ar from './locales/ar.json';

/**
 * Vue I18n instance
 * Default locale: Arabic (ar)
 * English locale is lazy-loaded on first use to reduce initial bundle size.
 */
const savedLocale = localStorage.getItem( 'locale' ) || 'ar';

const i18n = createI18n( {
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'ar',
    messages: { ar },
} );

/**
 * Load English locale on demand.
 * Returns immediately if already loaded.
 */
let enLoaded = false;
async function loadEnglish ()
{
    if ( enLoaded ) return;
    const { default: en } = await import( './locales/en.json' );
    i18n.global.setLocaleMessage( 'en', en );
    enLoaded = true;
}

// If user previously chose English, load it immediately (before first render)
export const i18nReady = savedLocale === 'en' ? loadEnglish() : Promise.resolve();

/**
 * Switch between Arabic and English locales.
 * Persists the choice in localStorage and updates the document direction.
 */
export async function switchLocale ()
{
    const newLocale = i18n.global.locale.value === 'ar' ? 'en' : 'ar';
    if ( newLocale === 'en' ) await loadEnglish();
    i18n.global.locale.value = newLocale;
    localStorage.setItem( 'locale', newLocale );
    document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = newLocale;
}

/**
 * Get the current locale value.
 */
export function getLocale ()
{
    return i18n.global.locale.value;
}

export default i18n;
