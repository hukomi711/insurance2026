import DOMPurify from 'dompurify';

/**
 * Composable for sanitizing HTML content to prevent XSS attacks.
 * Uses DOMPurify under the hood.
 */
export function useSanitizer ()
{
    /**
     * Sanitize rich text (HTML) content.
     * Allows safe tags like headings, paragraphs, lists, links, images,
     * and inline formatting — but strips scripts, event handlers, etc.
     *
     * @param {string} html - Raw HTML string
     * @returns {string} Sanitized HTML string
     */
    function sanitizeRichText ( html )
    {
        if ( !html ) return '';

        return DOMPurify.sanitize( html, {
            ALLOWED_TAGS: [
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'p', 'br', 'hr',
                'ul', 'ol', 'li',
                'a', 'strong', 'em', 'b', 'i', 'u', 's',
                'blockquote', 'pre', 'code',
                'img', 'figure', 'figcaption',
                'table', 'thead', 'tbody', 'tr', 'th', 'td',
                'div', 'span',
            ],
            ALLOWED_ATTR: [
                'href', 'target', 'rel',
                'src', 'alt', 'width', 'height',
                'class', 'id', 'dir', 'lang',
                'colspan', 'rowspan',
            ],
            ALLOW_DATA_ATTR: false,
        } );
    }

    /**
     * Sanitize plain text (strip ALL HTML tags).
     *
     * @param {string} text - Raw text that may contain HTML
     * @returns {string} Plain text with no HTML
     */
    function sanitizePlainText ( text )
    {
        if ( !text ) return '';
        return DOMPurify.sanitize( text, { ALLOWED_TAGS: [] } );
    }

    return { sanitizeRichText, sanitizePlainText };
}
