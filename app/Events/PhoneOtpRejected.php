<?php

namespace App\Events;

class PhoneOtpRejected extends BaseRejectionEvent
{
    protected function channelPrefix(): string { return 'phone'; }
    protected function eventName(): string { return 'PhoneOtpRejected'; }
}
