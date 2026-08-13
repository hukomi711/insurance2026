/* global process */
import fs from 'node:fs';
import path from 'node:path';
import { describe, it, expect } from 'vitest';
import { formatPaymentFailure } from '@/constants/rejectionReasons';

const fixtures = JSON.parse(
    fs.readFileSync(path.join(process.cwd(), 'tests/Fixtures/payment-failure-reasons.json'), 'utf8'),
);

describe('formatPaymentFailure', () => {
  it('shows rajhi rejection message when backend reason is rajhi_not_supported', () => {
    const alert = formatPaymentFailure('rajhi_not_supported');

    expect(alert.reason).toBe('rajhi_not_supported');
    expect(alert.type).toBe('warning');
    expect(alert.retryable).toBe(true);
    expect(alert.message.length).toBeGreaterThan(10);
  });

  it('treats rajhi same as any other bank — no special override', () => {
    const alert = formatPaymentFailure('card_declined', { detectedBank: 'rajhi' });

    expect(alert.reason).toBe('card_declined');
    expect(alert.type).toBe('error');
  });

  it('falls back safely for unknown reasons', () => {
    const alert = formatPaymentFailure('unknown_reason');

    expect(alert.reason).toBe('card_declined');
    expect(alert.title.length).toBeGreaterThan(3);
    expect(alert.message.length).toBeGreaterThan(3);
  });

  it.each(fixtures.cases)('matches the shared contract for $reason', ({ reason, expected }) => {
    expect(formatPaymentFailure(reason)).toEqual(expected);
  });
});
