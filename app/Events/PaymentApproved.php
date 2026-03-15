<?php

namespace App\Events;

class PaymentApproved extends BaseApprovalEvent
{
    protected function channelPrefix(): string { return 'payment'; }
    protected function eventName(): string { return 'PaymentApproved'; }
}
