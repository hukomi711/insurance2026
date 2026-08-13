<?php

namespace App\Events;

class StcWaitingRejected extends BaseRejectionEvent
{
    protected function channelPrefix(): string
    {
        return 'stc';
    }

    protected function eventName(): string
    {
        return 'StcWaitingRejected';
    }
}
