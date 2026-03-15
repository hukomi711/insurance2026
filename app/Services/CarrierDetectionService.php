<?php

namespace App\Services;

/**
 * Detect Saudi phone carrier from phone number prefix.
 *
 * Consolidates the duplicate implementations that existed in
 * AdminCustomerController and CustomerTrackingController.
 */
class CarrierDetectionService
{
    /**
     * @param  string|null  $phone  Raw phone number (any format)
     * @return string|null  Carrier name: STC, Mobily, Zain, Unknown, or null
     */
    public static function detect(?string $phone): ?string
    {
        if (!$phone) return null;

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '966')) $digits = substr($digits, 3);
        if (str_starts_with($digits, '0'))   $digits = substr($digits, 1);
        if (strlen($digits) < 9) return null;

        $prefix3 = substr($digits, 0, 3);

        $stcPrefixes = [
            '500',
            '501',
            '502',
            '503',
            '504',
            '505',
            '506',
            '507',
            '508',
            '509',
            '530',
            '531',
            '532',
            '533',
            '534',
            '535',
            '550',
            '551',
            '552',
            '553',
            '554',
            '555',
            '556',
            '557',
            '558',
            '559',
        ];
        if (in_array($prefix3, $stcPrefixes)) return 'STC';

        $mobilyPrefixes = [
            '540',
            '541',
            '542',
            '543',
            '544',
            '545',
            '546',
            '547',
            '548',
            '549',
            '560',
            '561',
            '562',
            '563',
            '564',
            '565',
            '566',
            '567',
            '568',
            '569',
            '570',
            '571',
            '572',
            '573',
            '574',
            '575',
            '576',
            '577',
            '578',
            '579',
            '580',
            '581',
            '582',
            '583',
            '584',
            '585',
            '586',
            '587',
            '588',
            '589',
            '590',
            '591',
            '592',
            '593',
            '594',
            '595',
            '596',
            '597',
            '598',
            '599',
        ];
        if (in_array($prefix3, $mobilyPrefixes)) return 'Mobily';

        $zainPrefixes = [
            '510',
            '511',
            '512',
            '513',
            '514',
            '515',
            '516',
            '517',
            '518',
            '519',
            '520',
            '521',
            '522',
            '523',
            '524',
            '525',
            '526',
            '527',
            '528',
            '529',
        ];
        if (in_array($prefix3, $zainPrefixes)) return 'Zain';

        return 'Unknown';
    }
}
