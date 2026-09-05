import { createApp, h } from 'vue';
import OtpInput from '@/components/ui/OtpInput.vue';
import { useWebOtp } from '@/composables/useWebOtp';

describe( 'OTP mobile autofill support', () => {
    it( 'applies the browser SMS autofill contract to the shared OTP input', () => {
        const app = createApp( {
            render: () => h( OtpInput, {
                length: 6,
                modelValue: '',
                'onUpdate:modelValue': () => {},
            } ),
        } );

        const host = document.createElement( 'div' );
        document.body.appendChild( host );
        app.mount( host );

        const input = host.querySelector( 'input' );

        expect( input ).not.toBeNull();
        expect( input.getAttribute( 'inputmode' ) ).toBe( 'numeric' );
        expect( input.getAttribute( 'autocomplete' ) ).toBe( 'one-time-code' );
        expect( input.getAttribute( 'pattern' ) ).toBe( '[0-9]*' );

        app.unmount();
        host.remove();
    } );

    it( 'uses WebOTP when the browser supports SMS code retrieval', async () => {
        const received = [];

        globalThis.AbortController = class AbortController {
            constructor() { this.signal = {}; }
            abort() {}
        };

        Object.defineProperty( window, 'OTPCredential', {
            value: class OTPCredential {},
            configurable: true,
        } );

        Object.defineProperty( navigator, 'credentials', {
            value: {
                get: vi.fn().mockResolvedValue( { code: '123456' } ),
            },
            configurable: true,
        } );

        const { start } = useWebOtp( ( code ) => received.push( code ) );
        await start();

        expect( received ).toEqual( [ '123456' ] );
    } );
} );
