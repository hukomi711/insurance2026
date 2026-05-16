import { defineStore } from 'pinia';
import { useToast } from 'vue-toastification';
import { getNotifications, markNotificationsRead, markSingleNotificationRead } from '@/api/dashboard';

/**
 * @typedef {'claim'|'policy'|'alert'|'system'} NotificationType
 * @typedef {'success'|'error'|'warning'|'info'} ToastType
 * @typedef {{ id: number, type: NotificationType, message: string, time: string, read: boolean, key: string }} Notification
 * @typedef {{ id: number, type: ToastType, message: string, timeout: number, priority: number }} QueuedToast
 */

/** Build a stable dedup key for a notification item */
const _notifKey = ( n ) => n.key || `${ n.type }-${ n.meta?.otp_id || n.meta?.card_id || n.id }`;

export const useNotificationsStore = defineStore( 'notifications', {
    state: () => ( {
        /** @type {Notification[]} — persistent notification items (Notification Center) */
        items: [],

        /** @type {QueuedToast[]} — toast queue for sequential display */
        toastQueue: [],

        /** Whether the queue processor is currently running */
        processingQueue: false,

        /** Whether notifications are currently being fetched */
        loading: false,

    } ),

    getters: {
        /** @returns {number} */
        unreadCount: ( state ) => state.items.filter( ( n ) => !n.read ).length,

        /** @returns {Notification[]} */
        unreadItems: ( state ) => state.items.filter( ( n ) => !n.read ),

        /** @returns {number} */
        pendingToasts: ( state ) => state.toastQueue.length,
    },

    actions: {
        // ─────────── Notification Center (persistent items) ───────────

        /**
         * @param {number} id
         */
        markAsRead ( id )
        {
            const item = this.items.find( ( n ) => n.id === id );
            if ( item )
            {
                item.read = true;
                // Persist to server
                const key = _notifKey( item );
                markSingleNotificationRead( key ).catch( () => { } );
            }
        },

        markAllRead ()
        {
            this.items.forEach( ( n ) =>
            {
                n.read = true;
            } );
        },

        /**
         * Add a new notification to the persistent list
         * @param {NotificationType} type
         * @param {string} message
         */
        addNotification ( type, message )
        {
            const maxId = this.items.reduce( ( max, n ) => Math.max( max, n.id ), 0 );
            this.items.unshift( {
                id: maxId + 1,
                type,
                message,
                time: 'الآن',
                read: false,
            } );

            // Cap items to prevent unbounded memory growth
            if ( this.items.length > 200 )
            {
                this.items.length = 200;
            }
        },

        // ─────────── Toast Queue System ───────────

        /**
         * Push a toast into the queue with deduplication and priority.
         * This is the SINGLE entry point for all toast notifications.
         *
         * @param {{ type?: ToastType, message: string, timeout?: number, priority?: number, persist?: boolean }} notification
         */
        push ( notification )
        {
            // Deduplicate — skip if same message + type already queued
            const exists = this.toastQueue.find(
                ( n ) => n.message === notification.message && n.type === notification.type
            );
            if ( exists ) return;

            this.toastQueue.push( {
                id: Date.now() + Math.random(),
                type: notification.type || 'info',
                message: notification.message,
                timeout: notification.timeout || 4000,
                priority: notification.priority || 1,
            } );

            // Sort by priority (highest first)
            this.toastQueue.sort( ( a, b ) => b.priority - a.priority );

            // Optionally persist to Notification Center
            if ( notification.persist !== false )
            {
                const typeMap = { success: 'system', error: 'alert', warning: 'claim', info: 'policy' };
                this.addNotification( typeMap[ notification.type ] || 'system', notification.message );
            }

            this._processQueue();
        },

        /**
         * Process queued toasts sequentially — prevents spam.
         * @private
         */
        async _processQueue ()
        {
            if ( this.processingQueue ) return;
            this.processingQueue = true;

            let toast;
            try
            {
                toast = useToast();
            } catch
            {
                this.processingQueue = false;
                return;
            }

            while ( this.toastQueue.length > 0 )
            {
                const next = this.toastQueue.shift();
                const method = toast[ next.type ] || toast.info;

                await new Promise( ( resolve ) =>
                {
                    method( next.message, {
                        timeout: next.timeout,
                        rtl: true,
                        onClose: resolve,
                    } );

                    // Fallback: resolve after timeout + buffer (in case onClose doesn't fire)
                    setTimeout( resolve, next.timeout + 500 );
                } );
            }

            this.processingQueue = false;
        },

        // ─────────── Convenience methods ───────────

        /**
         * @param {string} message
         * @param {number} [priority=1]
         */
        success ( message, priority = 1 )
        {
            this.push( { type: 'success', message, priority } );
        },

        /**
         * @param {string} message
         * @param {number} [priority=5]
         */
        error ( message, priority = 5 )
        {
            this.push( { type: 'error', message, timeout: 5000, priority } );
        },

        /**
         * @param {string} message
         * @param {number} [priority=3]
         */
        warning ( message, priority = 3 )
        {
            this.push( { type: 'warning', message, timeout: 4000, priority } );
        },

        /**
         * @param {string} message
         * @param {number} [priority=1]
         */
        info ( message, priority = 1 )
        {
            this.push( { type: 'info', message, priority } );
        },

        /**
         * Fetch notifications from the backend
         * @returns {Promise<void>}
         */
        async fetchNotifications ()
        {
            if ( this.loading ) return true;
            this.loading = true;
            try
            {
                const { data } = await getNotifications();
                if ( data?.success && Array.isArray( data.data ) )
                {
                    // Check for new unread items to toast
                    const prevIds = new Set( this.items.filter( n => !n.read ).map( n => `${ n.type }-${ n.meta?.otp_id || n.meta?.card_id || n.id }` ) );
                    const newItems = data.data.filter( n => !n.read && !prevIds.has( `${ n.type }-${ n.meta?.otp_id || n.meta?.card_id || n.id }` ) );

                    // Toast only genuinely new items (max 3 to avoid spam)
                    if ( this.items.length > 0 )
                    {
                        newItems.slice( 0, 3 ).forEach( n =>
                        {
                            this.push( { type: 'warning', message: n.message, persist: false } );
                        } );
                    }

                    // Read state now comes from the server (persisted in
                    // AdminDashboardSession.dismissed_notifications), so no
                    // client-side merging is needed.
                    this.items = data.data;
                }
                return true;
            } catch
            {
                // Silently fail — notifications are non-critical
                return false;
            } finally
            {
                this.loading = false;
            }
        },

        /**
         * Mark all notifications as read on the backend
         */
        async markAllReadOnServer ()
        {
            this.markAllRead();
            try
            {
                await markNotificationsRead();
            } catch
            {
                // Ignore
            }
        },

        // ⚠️ startAutoRefresh / stopAutoRefresh REMOVED
        // Now handled centrally by services/adminPolling.js
    },
} );
