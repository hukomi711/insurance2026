<?php

namespace App\Console\Commands;

use App\Models\PaymentCard;
use App\Services\Bin\CardBinResolver;
use Illuminate\Console\Command;

/**
 * Backfills bin_8/bin_6/detected_* columns on payment_cards.
 *
 * Runs the {@see CardBinResolver} against every existing card and stores
 * the result so reports + admin UI can read without resolving on each
 * render. Idempotent — re-runs overwrite previous detection.
 *
 * Usage:
 *   php artisan cards:backfill-bin
 *   php artisan cards:backfill-bin --force   # overwrite even if filled
 */
class BackfillCardBin extends Command
{
    protected $signature = 'cards:backfill-bin {--force : Overwrite already-filled rows}';

    protected $description = 'Backfill BIN/network/bank/type columns on payment_cards using CardBinResolver';

    public function handle(CardBinResolver $resolver): int
    {
        $query = PaymentCard::query();
        if (! $this->option('force')) {
            $query->whereNull('detected_bank_key');
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $this->info("Backfilling {$total} payment cards...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $unknown = 0;

        $query->chunkById(200, function ($cards) use ($resolver, &$updated, &$unknown, $bar) {
            foreach ($cards as $card) {
                $pan = $card->card_number; // decrypted via cast
                $r = $resolver->resolve($pan);

                $card->forceFill([
                    'bin_8'                       => $r->bin8,
                    'bin_6'                       => $r->bin6,
                    'detected_bank_key'           => $r->bankKey,
                    'detected_network'            => $r->network,
                    'detected_secondary_network'  => $r->secondaryNetwork,
                    'detected_type'               => $r->cardType,
                    'detected_level'              => $r->cardLevel,
                    'detection_confidence'        => $r->confidence,
                    'detection_match_type'        => $r->matchType,
                ])->saveQuietly();

                $r->bankKey ? $updated++ : $unknown++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done. matched={$updated} unknown={$unknown}");

        return self::SUCCESS;
    }
}
