import { createI18n } from 'vue-i18n';
import ar from './locales/ar.json';
import en from './locales/en.json';

/**
 * Vue I18n instance
 * Default locale: Arabic (ar)
 */
const i18n = createI18n( {
    legacy: false,
    locale: localStorage.getItem( 'locale' ) || 'ar',
    fallbackLocale: 'ar',
    messages: { ar, en },
} );

/**
 * Switch between Arabic and English locales.
 * Persists the choice in localStorage and updates the document direction.
 */
export function switchLocale ()
{
    const newLocale = i18n.global.locale.value === 'ar' ? 'en' : 'ar';
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
