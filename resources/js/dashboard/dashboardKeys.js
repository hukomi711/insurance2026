/**
 * Symbol-based injection keys for the dashboard layout.
 *
 * Using Symbols instead of plain strings avoids accidental collisions
 * when multiple provide/inject trees coexist.
 *
 * @see https://vuejs.org/guide/components/provide-inject.html#working-with-symbol-keys
 */

/** Ref<object|null> — the customer object that LiveChatDropdown should open */
export const OPEN_CHAT_TARGET = Symbol( 'openChatTarget' );
