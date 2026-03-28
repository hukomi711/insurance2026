import { defineStore } from 'pinia';
import request, { initCsrf } from '@/api/request';
import { stopAdminPolling } from '@/services/adminPolling';
import { destroyEcho } from '@/services/echo';

/**
 * @typedef {'admin'|'editor'|'viewer'} UserRole
 * @typedef {{ name: string, email: string, role: UserRole, avatar: string }} UserInfo
 */

export const useUserStore = defineStore( 'user', {
    state: () => ( {
        /** @type {string} */
        name: '',
        /** @type {string} */
        email: '',
        /** @type {UserRole} */
        role: 'admin',
        /** @type {string} */
        avatar: '',
        /** @type {boolean} */
        isAuthenticated: false,
        /** @type {string|null} */
        token: localStorage.getItem( 'auth_token' ) || null,
        /** @type {number} — timestamp of last /admin/me fetch */
        _meLoadedAt: 0,
    } ),

    getters: {
        /** @returns {string} */
        initials: ( state ) =>
        {
            return state.name ? state.name.charAt( 0 ) : 'م';
        },
        /** @returns {UserInfo} */
        userInfo: ( state ) => ( {
            name: state.name,
            email: state.email,
            role: state.role,
            avatar: state.avatar,
        } ),
    },

    actions: {
        /**
         * Login via API — returns Sanctum token
         * @param {{ email: string, password: string }} credentials
         * @returns {Promise<boolean>}
         */
        async login ( credentials )
        {
            // Note: destroyEcho() was removed here — channelAuthorization.customHandler
            // reads auth_token from localStorage dynamically, so the existing Echo
            // singleton works with the new token. Destroying mid-connect caused
            // "WebSocket is closed before the connection is established" errors.

            await initCsrf();
            const { data } = await request.post( '/admin/login', credentials );

            // 2FA required — return indicator without setting auth state
            if ( data.requires_2fa )
            {
                return { requires_2fa: true, user_id: data.user_id };
            }

            this.token = data.token;
            this.name = data.user.name;
            this.email = data.user.email;
            this.role = data.user.role || 'admin';
            this.isAuthenticated = true;
            localStorage.setItem( 'auth_token', data.token );
            return { requires_2fa: false };
        },

        /**
         * Verify 2FA code and complete login
         */
        async verifyCode ( userId, code )
        {
            await initCsrf();
            const { data } = await request.post( '/admin/verify-code', {
                user_id: userId,
                code,
            } );
            this.token = data.token;
            this.name = data.user.name;
            this.email = data.user.email;
            this.role = data.user.role || 'admin';
            this.isAuthenticated = true;
            localStorage.setItem( 'auth_token', data.token );
            return true;
        },

        /**
         * Resend 2FA verification code
         */
        async resendCode ( userId )
        {
            await initCsrf();
            await request.post( '/admin/resend-code', { user_id: userId } );
        },

        /**
         * Logout and reset state
         */
        async logout ()
        {
            try
            {
                await request.post( '/admin/logout' );
            } catch
            {
                // Token may already be invalid
            }

            // Stop central polling and destroy WebSocket
            stopAdminPolling();
            destroyEcho();

            localStorage.removeItem( 'auth_token' );
            sessionStorage.clear();
            this.$reset();
            this.isAuthenticated = false;
            this.token = null;
        },

        /**
         * Fetch current user info from API (cached for 60 seconds)
         * @returns {Promise<UserInfo>}
         */
        async getInfo ()
        {
            // ✅ Cache — skip if loaded within last 60 seconds
            if ( this.name && this.isAuthenticated && Date.now() - this._meLoadedAt < 60_000 )
            {
                return this.userInfo;
            }

            try
            {
                const { data } = await request.get( '/admin/me' );
                this.name = data.user.name;
                this.email = data.user.email;
                this.role = data.user.role || 'admin';
                this.isAuthenticated = true;
                this._meLoadedAt = Date.now();
                return this.userInfo;
            } catch
            {
                this.isAuthenticated = false;
                this.token = null;
                this._meLoadedAt = 0;
                localStorage.removeItem( 'auth_token' );
                throw new Error( 'Unauthorized' );
            }
        },
    },
} );
