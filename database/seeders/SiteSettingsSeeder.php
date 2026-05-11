<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'whatsapp_number',  'value' => '059777777777',                                'group' => 'contact'],
            ['key' => 'whatsapp_message', 'value' => 'مرحباً، أرغب في الاستفسار عن تأمين السيارة', 'group' => 'contact'],
            ['key' => 'whatsapp_enabled', 'value' => '1', 'type' => 'boolean',                       'group' => 'contact'],
            ['key' => 'support_phone',    'value' => '',                                              'group' => 'contact'],
            ['key' => 'contact_email',    'value' => '',                                              'group' => 'contact'],
        ];

        foreach ($defaults as $row) {
            SiteSetting::firstOrCreate(
                ['key' => $row['key']],
                [
                    'value' => $row['value'],
                    'type'  => $row['type'] ?? 'string',
                    'group' => $row['group'] ?? 'general',
                ],
            );
        }

        SiteSetting::flushCache();
        $this->command?->info('✅ SiteSettingsSeeder: defaults ensured.');
    }
}
