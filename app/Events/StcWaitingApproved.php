<?php

namespace App\Events;

class StcWaitingApproved extends BaseApprovalEvent
{
    protected function channelPrefix(): string { return 'stc'; }
    protected function eventName(): string { return 'StcWaitingApproved'; }
}
