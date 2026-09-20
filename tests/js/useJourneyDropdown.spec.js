// @vitest-environment happy-dom

import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

vi.mock( '@/api/request', () => ( {
    default: {
        post: vi.fn( () => Promise.resolve( { data: { success: true } } ) ),
    },
} ) );

vi.mock( '@/utils/logger', () => ( {
    default: { error: vi.fn(), debug: vi.fn(), warn: vi.fn() },
} ) );

import { useJourneyDropdown } from '@/dashboard/composables/useJourneyDropdown';
import request from '@/api/request';

describe( 'useJourneyDropdown redirectCustomerToPage', () => {
    it( 'optimistically updates both current_page and journey.current_page on success', async () => {
        const customers = ref( [
            { id: 1, ip: '1.2.3.4', current_page: '/motorapp', journey: { current_page: '/motorapp' } },
        ] );
        const emit = vi.fn();
        const { redirectCustomerToPage } = useJourneyDropdown( customers, emit );

        await redirectCustomerToPage( 1, 'basicDetails' );

        expect( request.post ).toHaveBeenCalledWith( '/admin/actions/redirect-customer', {
            customer_id: 1,
            customer_ip: '1.2.3.4',
            redirect_url: '/motorapp/basicDetails/new-insurance',
        } );
        expect( customers.value[ 0 ].current_page ).toBe( '/motorapp/basicDetails/new-insurance' );
        expect( customers.value[ 0 ].journey.current_page ).toBe( '/motorapp/basicDetails/new-insurance' );
        expect( emit ).toHaveBeenCalledWith( 'redirect', expect.objectContaining( { customer_id: 1, url: '/motorapp/basicDetails/new-insurance' } ) );
    } );

    it( 'does not mutate customers when the backend reports failure', async () => {
        request.post.mockResolvedValueOnce( { data: { success: false } } );

        const customers = ref( [
            { id: 2, ip: '5.6.7.8', current_page: '/motorapp', journey: { current_page: '/motorapp' } },
        ] );
        const emit = vi.fn();
        const { redirectCustomerToPage } = useJourneyDropdown( customers, emit );

        await redirectCustomerToPage( 2, 'basicDetails' );

        expect( customers.value[ 0 ].current_page ).toBe( '/motorapp' );
        expect( emit ).not.toHaveBeenCalled();
    } );
} );
