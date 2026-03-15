<?php

namespace App\Events;

class PaymentRejected extends BaseRejectionEvent
{
    protected function channelPrefix(): string { return 'payment'; }
    protected function eventName(): string { return 'PaymentRejected'; }
}
