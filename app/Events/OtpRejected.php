<?php

namespace App\Events;

class OtpRejected extends BaseRejectionEvent
{
    protected function channelPrefix(): string { return 'otp'; }
    protected function eventName(): string { return 'OtpRejected'; }
}
