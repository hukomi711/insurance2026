import { ref, computed } from 'vue';

/**
 * Composable for managing a 6-digit OTP input with keyboard navigation and paste support.
 * @param {number} [length=6] - Number of OTP digits
 * @returns {object} OTP state and handlers
 */
export function useOtpInput ( length = 6 )
{
    const otp = ref( Array.from( { length }, () => '' ) );
    const otpInputs = ref( [] );

    const isComplete = computed( () => otp.value.every( ( digit ) => digit !== '' ) );

    const code = computed( () => otp.value.join( '' ) );

    const displayCode = computed( () => otp.value.map( ( d ) => d || '‒' ).join( ' ' ) );

    /**
     * Handle single digit input — auto-advance to next field.
     */
    const handleInput = ( index, event ) =>
    {
        const value = event.target.value;
        if ( !/^\d*$/.test( value ) )
        {
            otp.value[ index ] = '';
            return;
        }
        otp.value[ index ] = value;
        if ( value && index < length - 1 )
        {
            otpInputs.value[ index + 1 ]?.focus();
        }
    };

    /**
     * Handle keyboard navigation — Backspace moves back, arrows navigate.
     */
    const handleKeydown = ( index, event ) =>
    {
        if ( event.key === 'Backspace' && !otp.value[ index ] && index > 0 )
        {
            otpInputs.value[ index - 1 ]?.focus();
        }
        if ( event.key === 'ArrowLeft' && index < length - 1 )
        {
            otpInputs.value[ index + 1 ]?.focus();
        }
        if ( event.key === 'ArrowRight' && index > 0 )
        {
            otpInputs.value[ index - 1 ]?.focus();
        }
    };

    /**
     * Handle paste — fills all digits if pasted text matches expected length.
     */
    const handlePaste = ( event ) =>
    {
        event.preventDefault();
        const pastedData = event.clipboardData.getData( 'text' ).trim();
        const regex = new RegExp( `^\\d{${ length }}$` );
        if ( regex.test( pastedData ) )
        {
            otp.value = pastedData.split( '' );
            otpInputs.value[ length - 1 ]?.focus();
        }
    };

    /**
     * Reset OTP to empty and focus first input.
     */
    const reset = () =>
    {
        otp.value = Array.from( { length }, () => '' );
        otpInputs.value[ 0 ]?.focus();
    };

    /**
     * Focus the first input field.
     */
    const focusFirst = () =>
    {
        otpInputs.value[ 0 ]?.focus();
    };

    return {
        otp,
        otpInputs,
        isComplete,
        code,
        displayCode,
        handleInput,
        handleKeydown,
        handlePaste,
        reset,
        focusFirst,
    };
}
