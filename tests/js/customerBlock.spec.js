// @vitest-environment happy-dom

import { beforeEach, describe, expect, it } from 'vitest';
import {
    clearCustomerBlocked,
    getCustomerBlockState,
    markCustomerBlocked,
} from '@/utils/customerBlock';

describe( 'customer block state', () => {
    beforeEach( () => {
        localStorage.clear();
    } );

    it( 'keeps a block only for the browser session that received it', () => {
        markCustomerBlocked();

        expect( getCustomerBlockState()?.blocked ).toBe( true );

        localStorage.setItem( 'customer_session_token', 'other-browser-session' );

        expect( getCustomerBlockState() ).toBeNull();
        expect( localStorage.getItem( 'customer_blocked' ) ).toBeNull();
    } );

    it( 'clears legacy block state that is not tied to a session', () => {
        localStorage.setItem( 'customer_blocked', JSON.stringify( { blocked: true } ) );

        expect( getCustomerBlockState() ).toBeNull();
        expect( localStorage.getItem( 'customer_blocked' ) ).toBeNull();
    } );

    it( 'clears stale geo block cache when the customer is unblocked', () => {
        markCustomerBlocked( { message: 'blocked' } );
        sessionStorage.setItem( 'geo_status', JSON.stringify( {
            customer_blocked: true,
            fetched_at: Date.now(),
        } ) );

        clearCustomerBlocked();

        expect( getCustomerBlockState() ).toBeNull();
        expect( localStorage.getItem( 'customer_blocked' ) ).toBeNull();
        expect( sessionStorage.getItem( 'geo_status' ) ).toBeNull();
    } );
} );
