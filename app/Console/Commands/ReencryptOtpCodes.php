<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;

class ReencryptOtpCodes extends Command
{
    protected $signature = 'otp:reencrypt
        {--batch=500 : Number of rows per chunk}
        {--dry-run : Show counts without modifying data}';

    protected $description = 'Re-encrypt legacy plaintext OTP codes using Laravel Crypt';

    public function handle(): int
    {
        $batchSize = (int) $this->option('batch');
        $dryRun    = (bool) $this->option('dry-run');

        if ($batchSize < 1) {
            $this->error('--batch must be at least 1.');

            return self::INVALID;
        }

        $total     = OtpCode::count();
        $encrypted = 0;
        $skipped   = 0;
        $errors    = 0;

        $this->info("Scanning {$total} OTP rows (batch={$batchSize}, dry-run=" . ($dryRun ? 'yes' : 'no') . ')');

        OtpCode::query()->chunkById($batchSize, function ($rows) use ($dryRun, &$encrypted, &$skipped, &$errors) {
            foreach ($rows as $otp) {
                /** @var OtpCode $otp */
                if (! $otp->isLegacyPlaintext()) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $encrypted++;
                    continue;
                }

                try {
                    if ($otp->reencryptIfNeeded()) {
                        $encrypted++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("Row #{$otp->id}: {$e->getMessage()}");
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total rows',        $total],
                ['Re-encrypted',      $encrypted],
                ['Already encrypted', $skipped],
                ['Errors',            $errors],
            ]
        );

        if ($dryRun) {
            $this->warn('Dry-run mode — no rows were modified.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
