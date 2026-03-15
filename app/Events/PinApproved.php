<?php

namespace App\Events;

class PinApproved extends BaseApprovalEvent
{
    protected function channelPrefix(): string
    {
        return 'otp';
    }

    protected function eventName(): string
    {
        return 'PinApproved';
    }
}
