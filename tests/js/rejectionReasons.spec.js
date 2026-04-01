import { describe, it, expect } from 'vitest';
import { formatPaymentFailure } from '@/constants/rejectionReasons';

describe('formatPaymentFailure', () => {
  it('shows rajhi rejection message when backend reason is rajhi_not_supported', () => {
    const alert = formatPaymentFailure('rajhi_not_supported');

    expect(alert.reason).toBe('rajhi_not_supported');
    expect(alert.type).toBe('warning');
    expect(alert.retryable).toBe(true);
    expect(alert.message.length).toBeGreaterThan(10);
  });

  it('uses rajhi message when BIN detection says rajhi even if backend reason differs', () => {
    const alert = formatPaymentFailure('card_declined', { detectedBank: 'rajhi' });

    expect(alert.reason).toBe('rajhi_not_supported');
    expect(alert.type).toBe('warning');
  });

  it('falls back safely for unknown reasons', () => {
    const alert = formatPaymentFailure('unknown_reason');

    expect(alert.reason).toBe('unknown_reason');
    expect(alert.title.length).toBeGreaterThan(3);
    expect(alert.message.length).toBeGreaterThan(3);
  });
});
