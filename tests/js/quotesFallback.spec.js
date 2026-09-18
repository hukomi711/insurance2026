import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted( () => ( {
    post: vi.fn(),
    buildPricingPayload: vi.fn(),
} ) );

vi.mock( '@/api/request', () => ( {
    default: { post: mocks.post },
} ) );

vi.mock( '@/data', () => ( {
    vehiclePlans: [],
    companies: [ { id: 1, name: 'Test Company' } ],
    getCompany: vi.fn( companyId => ( { id: companyId, name: 'Test Company' } ) ),
} ) );

vi.mock( '@/utils/buildPricingPayload', () => ( {
    buildPricingPayload: mocks.buildPricingPayload,
} ) );

import { getQuotes } from '@/api/quotes';

const formData = {
    vehicle: { year: 2024, make: 1, estimatedValue: 85000 },
    policy: { repairMethod: 'workshop' },
};

const sourcePlans = [
    { id: 1, companyId: 1, subType: 'comprehensive', deductible: 1000 },
];

function httpError ( status )
{
    return {
        message: `Request failed with status ${ status }`,
        response: { status },
    };
}

describe( 'quotes API fallback policy', () =>
{
    beforeEach( () =>
    {
        vi.clearAllMocks();
        mocks.buildPricingPayload.mockReturnValue( { pricing: 'payload' } );
    } );

    it( 'does not use local pricing after a 403 geo-policy response', async () =>
    {
        const error = httpError( 403 );
        mocks.post.mockRejectedValue( error );

        await expect( getQuotes( formData, sourcePlans ) ).rejects.toBe( error );
    } );

    it( 'does not use local pricing after a 423 business-lock response', async () =>
    {
        const error = httpError( 423 );
        mocks.post.mockRejectedValue( error );

        await expect( getQuotes( formData, sourcePlans ) ).rejects.toBe( error );
    } );

    it.each( [ 401, 419, 422, 429 ] )( 'does not use local pricing after HTTP %i', async status =>
    {
        const error = httpError( status );
        mocks.post.mockRejectedValue( error );

        await expect( getQuotes( formData, sourcePlans ) ).rejects.toBe( error );
    } );

    it.each( [ 500, 502, 503, 504 ] )( 'uses fixed local pricing after HTTP %i', async status =>
    {
        mocks.post.mockRejectedValue( httpError( status ) );

        const result = await getQuotes( formData, sourcePlans );

        expect( result.plans ).toHaveLength( 1 );
        expect( result.plans[ 0 ].annualPrice ).toBe( 749 );
        expect( result.plans[ 0 ].signature ).toBeNull();
    } );

    it.each( [ 'ERR_NETWORK', 'ECONNABORTED', 'ETIMEDOUT' ] )(
        'uses fixed local pricing after transport error %s',
        async code =>
        {
            mocks.post.mockRejectedValue( { code, message: code } );

            const result = await getQuotes( formData, sourcePlans );

            expect( result.plans ).toHaveLength( 1 );
            expect( result.plans[ 0 ].annualPrice ).toBe( 749 );
        }
    );

    it( 'applies no comprehensive surcharge for third-party plans in fallback mode', async () =>
    {
        mocks.post.mockRejectedValue( httpError( 503 ) );

        const thirdPartyPlans = [
            { id: 2, companyId: 1, subType: 'thirdParty', deductible: 1000 },
        ];

        const result = await getQuotes( formData, thirdPartyPlans );

        expect( result.plans ).toHaveLength( 1 );
        expect( result.plans[ 0 ].annualPrice ).toBe( 499 );
    } );

    it( 'does not hide an unrelated programming error', async () =>
    {
        const error = new TypeError( 'Unexpected pricing merge failure' );
        mocks.post.mockRejectedValue( error );

        await expect( getQuotes( formData, sourcePlans ) ).rejects.toBe( error );
    } );

    it( 'prices more than fifty plans in API-safe batches', async () =>
    {
        const plans = Array.from( { length: 51 }, ( _, index ) => ( {
            id: index + 1,
            companyId: 1,
            subType: 'comprehensive',
            deductible: 1000,
        } ) );

        mocks.buildPricingPayload.mockImplementation( ( _formData, planBatch ) => ( {
            plans: planBatch,
        } ) );
        mocks.post.mockImplementation( async ( _url, payload ) => ( {
            data: {
                quotes: payload.plans.map( plan => ( {
                    ...plan,
                    annualPrice: 1000 + plan.id,
                } ) ),
            },
        } ) );

        const result = await getQuotes( formData, plans );

        expect( mocks.post ).toHaveBeenCalledTimes( 2 );
        expect( mocks.post.mock.calls[ 0 ][ 1 ].plans ).toHaveLength( 50 );
        expect( mocks.post.mock.calls[ 1 ][ 1 ].plans ).toHaveLength( 1 );
        expect( result.plans ).toHaveLength( 51 );
        expect( result.plans[ 50 ].annualPrice ).toBe( 1051 );
    } );
} );
