<?php

namespace App\Events;

class OtpApproved extends BaseApprovalEvent
{
    protected function channelPrefix(): string
    {
        return 'otp';
    }

    protected function eventName(): string
    {
        return 'OtpApproved';
    }
}
