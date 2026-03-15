<?php

namespace App\Events;

class StcCallApproved extends BaseApprovalEvent
{
    protected function channelPrefix(): string { return 'stc'; }
    protected function eventName(): string { return 'StcCallApproved'; }
}
