import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock( '@/utils/logger', () => ( {
    default: {
        debug: vi.fn(),
        warn: vi.fn(),
    },
} ) );

import {
    registerPollingCallback,
    setTabVisible,
    startAdminPolling,
    stopAdminPolling,
} from '@/services/adminPolling';

function createPollingTargets () {
    return {
        badgeStore: { fetch: vi.fn().mockResolvedValue( true ) },
        notificationsStore: { fetchNotifications: vi.fn().mockResolvedValue( true ) },
        refreshCustomers: vi.fn().mockResolvedValue( true ),
    };
}

describe( 'admin polling visibility lifecycle', () => {
    beforeEach( () => {
        vi.useFakeTimers();
        stopAdminPolling();
    } );

    afterEach( () => {
        stopAdminPolling();
        vi.clearAllTimers();
        vi.useRealTimers();
    } );

    it( 'stops all polling work and timer wakeups while the tab is hidden', async () => {
        const targets = createPollingTargets();
        registerPollingCallback( 'refreshCustomers', targets.refreshCustomers );
        startAdminPolling( targets );
        await vi.advanceTimersByTimeAsync( 0 );

        expect( targets.badgeStore.fetch ).toHaveBeenCalledTimes( 1 );
        expect( targets.notificationsStore.fetchNotifications ).toHaveBeenCalledTimes( 1 );

        setTabVisible( false );
        await vi.advanceTimersByTimeAsync( 120_000 );

        expect( targets.badgeStore.fetch ).toHaveBeenCalledTimes( 1 );
        expect( targets.notificationsStore.fetchNotifications ).toHaveBeenCalledTimes( 1 );
        expect( targets.refreshCustomers ).not.toHaveBeenCalled();
        expect( vi.getTimerCount() ).toBe( 0 );
    } );

    it( 'does not fetch or schedule a timer when polling starts hidden', async () => {
        const targets = createPollingTargets();
        registerPollingCallback( 'refreshCustomers', targets.refreshCustomers );
        setTabVisible( false );

        startAdminPolling( targets );
        await vi.advanceTimersByTimeAsync( 120_000 );

        expect( targets.badgeStore.fetch ).not.toHaveBeenCalled();
        expect( targets.notificationsStore.fetchNotifications ).not.toHaveBeenCalled();
        expect( targets.refreshCustomers ).not.toHaveBeenCalled();
        expect( vi.getTimerCount() ).toBe( 0 );
    } );

    it( 'refreshes all active targets once and restarts one timer when visible again', async () => {
        const targets = createPollingTargets();
        registerPollingCallback( 'refreshCustomers', targets.refreshCustomers );
        setTabVisible( false );
        startAdminPolling( targets );

        setTabVisible( true );
        await vi.advanceTimersByTimeAsync( 0 );

        expect( targets.badgeStore.fetch ).toHaveBeenCalledTimes( 1 );
        expect( targets.notificationsStore.fetchNotifications ).toHaveBeenCalledTimes( 1 );
        expect( targets.refreshCustomers ).toHaveBeenCalledTimes( 1 );
        expect( vi.getTimerCount() ).toBe( 1 );
    } );
} );
