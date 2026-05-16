import { defineStore } from 'pinia';
import request from '@/api/request';

export const useBadgeStore = defineStore( 'badges', {
    state: () => ( {
        /** @type {Record<string, number>} — new-since-last-seen counts per section */
        counts: {},
        /** @type {Record<string, number>} — absolute totals */
        totals: {},
        loading: false,

    } ),

    getters: {
        /**
         * Get badge count for a section key
         * @returns {(key: string) => number}
         */
        getBadge: ( state ) => ( key ) => state.counts[ key ] || 0,

        /** Total across all sections */
        totalBadges: ( state ) => Object.values( state.counts ).reduce( ( sum, n ) => sum + n, 0 ),
    },

    actions: {
        async fetch ()
        {
            if ( this.loading ) return true;
            this.loading = true;
            try
            {
                const { data } = await request.get( '/admin/badge-counts', { silent: true, timeout: 4000 } );
                if ( data.success )
                {
                    this.counts = data.badges || {};
                    this.totals = data.totals || {};
                }
                return true;
            } catch
            {
                // Silent — badge counts are non-critical
                return false;
            } finally
            {
                this.loading = false;
            }
        },

        /**
         * Mark a section as seen (resets its badge to 0)
         * @param {string} section
         */
        async markSeen ( section )
        {
            // Optimistic: clear immediately
            this.counts[ section ] = 0;
            try
            {
                await request.post( `/admin/badge-seen/${ section }` );
            } catch
            {
                // Will self-correct on next fetch
            }
        },

        // ⚠️ startAutoRefresh / stopAutoRefresh REMOVED
        // Now handled centrally by services/adminPolling.js
    },
} );
