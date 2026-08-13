<?php

namespace App\Events;

class StcOtpRejected extends BaseRejectionEvent
{
    protected function channelPrefix(): string
    {
        return 'stc';
    }

    protected function eventName(): string
    {
        return 'StcOtpRejected';
    }
}
