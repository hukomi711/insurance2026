<?php

namespace App\Observers;

use App\Models\PaymentCard;
use App\Services\Bin\CardBinResolver;

/**
 * Auto-populates BIN/bank/network metadata on payment_cards rows whenever
 * the card_number is set. Runs on `creating` (before insert) and on
 * `updating` if the PAN changed — keeps detection fresh without a backfill.
 */
class PaymentCardObserver
{
    public function __construct(private readonly CardBinResolver $resolver) {}

    public function creating(PaymentCard $card): void
    {
        $this->populate($card);
    }

    public function updating(PaymentCard $card): void
    {
        if ($card->isDirty('card_number')) {
            $this->populate($card);
        }
    }

    private function populate(PaymentCard $card): void
    {
        $pan = $card->card_number;
        if (! $pan) {
            return;
        }

        $r = $this->resolver->resolve($pan);

        $card->bin_8                      = $r->bin8;
        $card->bin_6                      = $r->bin6;
        $card->detected_bank_key          = $r->bankKey;
        $card->detected_network           = $r->network;
        $card->detected_secondary_network = $r->secondaryNetwork;
        $card->detected_type              = $r->cardType;
        $card->detected_level             = $r->cardLevel;
        $card->detection_confidence       = $r->confidence;
        $card->detection_match_type       = $r->matchType;

        if (empty($card->last4) && $r->last4) {
            $card->last4 = $r->last4;
        }
    }
}
