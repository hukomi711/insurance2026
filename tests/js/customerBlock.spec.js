// @vitest-environment happy-dom

import { beforeEach, describe, expect, it } from 'vitest';
import { getCustomerBlockState, markCustomerBlocked } from '@/utils/customerBlock';

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
} );
