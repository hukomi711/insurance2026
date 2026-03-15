import { defineStore } from 'pinia';

/**
 * @typedef {'desktop'|'mobile'} DeviceType
 */

export const useAppStore = defineStore( 'app', {
    state: () => ( {
        /** @type {boolean} */
        sidebarOpened: true,
        /** @type {DeviceType} */
        device: 'desktop',
        /** @type {boolean} */
        withoutAnimation: false,
    } ),

    getters: {
        /** @returns {boolean} */
        isMobile: ( state ) => state.device === 'mobile',
    },

    actions: {
        toggleSidebar() {
            this.sidebarOpened = !this.sidebarOpened;
        },

        closeSidebar( /** @type {boolean} */ withoutAnimation = false ) {
            this.withoutAnimation = withoutAnimation;
            this.sidebarOpened = false;
        },

        openSidebar() {
            this.withoutAnimation = false;
            this.sidebarOpened = true;
        },

        /**
         * @param {DeviceType} device
         */
        toggleDevice( device ) {
            this.device = device;
        },
    },
} );
