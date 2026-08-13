<?php

namespace App\Events;

class StcOtpApproved extends BaseApprovalEvent
{
    protected function channelPrefix(): string
    {
        return 'stc';
    }

    protected function eventName(): string
    {
        return 'StcOtpApproved';
    }
}
