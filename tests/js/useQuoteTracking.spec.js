// @vitest-environment happy-dom

import { describe, expect, it, vi, beforeEach } from 'vitest';

vi.mock( '@/api/request', () => ( {
    default: {
        get: vi.fn(),
        post: vi.fn(),
    },
} ) );

vi.mock( '@/utils/logger', () => ( {
    default: { error: vi.fn(), debug: vi.fn(), warn: vi.fn(), info: vi.fn() },
} ) );

vi.mock( '@/utils/customerBlock', () => ( {
    isCustomerBlocked: vi.fn( () => false ),
    isCustomerBlockedError: vi.fn( () => false ),
} ) );

vi.mock( 'vue-router', () => ( {
    useRoute: () => ( { query: {} } ),
} ) );

import { useQuoteTracking } from '@/composables/useQuoteTracking';
import request from '@/api/request';

describe( 'useQuoteTracking startSession', () => {
    beforeEach( () => {
        sessionStorage.clear();
        request.get.mockReset();
        request.post.mockReset();
    } );

    // The composable holds sessionUUID in a module-scope singleton, so this
    // exercises the full lifecycle in one sequential flow rather than
    // relying on fresh module state per case.
    it( 'preserves the session on network/5xx errors but discards it on 404', async () => {
        request.post.mockResolvedValueOnce( { data: { uuid: 'first-uuid', session_id: 1 } } );
        const { startSession } = useQuoteTracking();

        const firstUuid = await startSession();
        expect( firstUuid ).toBe( 'first-uuid' );
        expect( request.post ).toHaveBeenCalledTimes( 1 );

        // Network error: existing session must be kept, no new session created.
        request.get.mockRejectedValueOnce( new Error( 'Network Error' ) );
        const uuidAfterNetworkError = await startSession();
        expect( uuidAfterNetworkError ).toBe( 'first-uuid' );
        expect( request.post ).toHaveBeenCalledTimes( 1 );

        // 5xx error: existing session must also be kept.
        request.get.mockRejectedValueOnce( { response: { status: 503 } } );
        const uuidAfter5xx = await startSession();
        expect( uuidAfter5xx ).toBe( 'first-uuid' );
        expect( request.post ).toHaveBeenCalledTimes( 1 );

        // 404: stale session must be discarded and a new one created.
        request.get.mockRejectedValueOnce( { response: { status: 404 } } );
        request.post.mockResolvedValueOnce( { data: { uuid: 'second-uuid', session_id: 2 } } );
        const uuidAfter404 = await startSession();
        expect( uuidAfter404 ).toBe( 'second-uuid' );
        expect( request.post ).toHaveBeenCalledTimes( 2 );
    } );
} );
