<?php

namespace App\Events;

class PhoneOtpApproved extends BaseApprovalEvent
{
    protected function channelPrefix(): string { return 'phone'; }
    protected function eventName(): string { return 'PhoneOtpApproved'; }
}
