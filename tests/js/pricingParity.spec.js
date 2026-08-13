/* global process */
import fs from 'node:fs';
import path from 'node:path';
import { afterAll, beforeAll, describe, expect, it, vi } from 'vitest';
import { usePricingEngine } from '@/utils/pricingEngine';

const fixtures = JSON.parse(
    fs.readFileSync(path.join(process.cwd(), 'tests/Fixtures/pricing-parity.json'), 'utf8'),
);

describe('PHP and JavaScript pricing parity', () => {
    beforeAll(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date(fixtures.referenceTime));
    });

    afterAll(() => {
        vi.useRealTimers();
    });

    it.each(fixtures.cases)('$name', ({ plan, vehicle, driver, policy, expected }) => {
        const result = usePricingEngine().calculatePremium(plan, { vehicle, driver, policy });

        expect({
            annualPrice: result.annualPrice,
            originalPrice: result.originalPrice,
            monthlyPrice: result.monthlyPrice,
            vatAmount: result.vatAmount,
            totalWithVAT: result.totalWithVAT,
            basePrice: result.basePrice,
            factors: result.factors,
        }).toEqual(expected);
    });
});
