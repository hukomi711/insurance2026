<?php

namespace App\Events;

class StcCallRejected extends BaseRejectionEvent
{
    protected function channelPrefix(): string
    {
        return 'stc';
    }

    protected function eventName(): string
    {
        return 'StcCallRejected';
    }
}
