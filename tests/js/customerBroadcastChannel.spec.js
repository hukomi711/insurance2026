import { describe, expect, it } from 'vitest';
import { customerBroadcastChannel } from '../../resources/js/utils/customerBroadcastChannel';

describe( 'customerBroadcastChannel', () =>
{
    it( 'matches the SHA-256 channel format produced by PHP', async () =>
    {
        const sessionId = '4b2686c3-92ea-49b4-b7fd-ea673263f27f';

        await expect( customerBroadcastChannel( 'payment', sessionId ) ).resolves.toBe(
            'payment.0f96bd9390abb58cf84a620f8c262c282c77b85fd2dc9c270322a4a925145d03',
        );
    } );

    it( 'rejects missing identity values', async () =>
    {
        await expect( customerBroadcastChannel( 'otp', '' ) ).rejects.toThrow();
    } );
} );
