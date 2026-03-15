<?php

namespace App\Events;

class NafathRejected extends BaseRejectionEvent
{
    protected function channelPrefix(): string { return 'nafath'; }
    protected function eventName(): string { return 'NafathRejected'; }
}
