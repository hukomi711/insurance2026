<?php

namespace App\Console\Commands;

use App\Models\CustomerProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypt existing plaintext PII and populate blind-index hash columns.
 *
 * Run ONCE after the 2026_04_12_000003 migration:
 *   php artisan customers:encrypt-pii
 *   php artisan customers:encrypt-pii --dry-run   # preview only
 */
class EncryptCustomerPii extends Command
{
    protected $signature = 'customers:encrypt-pii
                            {--dry-run : Preview changes without modifying the database}
                            {--chunk=200 : Number of records per batch}';

    protected $description = 'تشفير بيانات العملاء الحساسة (national_id, phone_number, email) وإنشاء أعمدة الهاش';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $encrypted = 0;
        $skipped = 0;

        if ($chunkSize < 1) {
            $this->error('--chunk must be at least 1.');

            return self::INVALID;
        }

        if ($dryRun) {
            $this->info('🔍 الوضع التجريبي — لن يتم تعديل أي بيانات');
        }

        $total = CustomerProfile::count();
        $this->info("Processing {$total} customer profiles...");

        $bar = $this->output->createProgressBar($total);

        CustomerProfile::query()
            ->select(['id', 'national_id', 'phone_number', 'email', 'national_id_hash', 'phone_number_hash'])
            ->orderBy('id')
            ->chunk($chunkSize, function ($profiles) use ($dryRun, &$encrypted, &$skipped, $bar) {
                foreach ($profiles as $profile) {
                    $needsUpdate = false;
                    $updates = [];

                    // ── national_id ──
                    $rawNationalId = $this->getPlaintext($profile->getRawOriginal('national_id'));
                    if ($rawNationalId !== null) {
                        $hash = CustomerProfile::hashPii($rawNationalId);

                        // Re-encrypt if still plaintext (not already encrypted)
                        if (! $this->isEncrypted($profile->getRawOriginal('national_id'))) {
                            $updates['national_id'] = Crypt::encryptString($rawNationalId);
                            $needsUpdate = true;
                        }

                        // Populate hash if missing
                        if ($profile->getRawOriginal('national_id_hash') !== $hash) {
                            $updates['national_id_hash'] = $hash;
                            $needsUpdate = true;
                        }
                    }

                    // ── phone_number ──
                    $rawPhone = $this->getPlaintext($profile->getRawOriginal('phone_number'));
                    if ($rawPhone !== null) {
                        $hash = CustomerProfile::hashPii($rawPhone);

                        if (! $this->isEncrypted($profile->getRawOriginal('phone_number'))) {
                            $updates['phone_number'] = Crypt::encryptString($rawPhone);
                            $needsUpdate = true;
                        }

                        if ($profile->getRawOriginal('phone_number_hash') !== $hash) {
                            $updates['phone_number_hash'] = $hash;
                            $needsUpdate = true;
                        }
                    }

                    // ── email ──
                    $rawEmail = $this->getPlaintext($profile->getRawOriginal('email'));
                    if ($rawEmail !== null) {
                        if (! $this->isEncrypted($profile->getRawOriginal('email'))) {
                            $updates['email'] = Crypt::encryptString($rawEmail);
                            $needsUpdate = true;
                        }
                    }

                    if ($needsUpdate) {
                        if (! $dryRun) {
                            // Use raw update to bypass model events (avoid double-encrypting)
                            CustomerProfile::where('id', $profile->id)->update($updates);
                        }
                        $encrypted++;
                    } else {
                        $skipped++;
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Encrypted: {$encrypted} | Already encrypted: {$skipped}");

        if ($dryRun) {
            $this->warn('⚠️  وضع تجريبي — لم يتم تعديل أي بيانات.');
        }

        return self::SUCCESS;
    }

    /**
     * Get plaintext value — decrypt if encrypted, return as-is if plaintext.
     */
    private function getPlaintext(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return $value; // already plaintext
        }
    }

    /**
     * Check if a value looks like Laravel encrypted ciphertext.
     */
    private function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        // Laravel encrypted strings are base64-encoded JSON with iv+value+mac
        $decoded = base64_decode($value, true);

        return $decoded !== false && str_contains($decoded, '"iv"') && str_contains($decoded, '"value"');
    }
}
