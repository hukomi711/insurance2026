<?php

namespace App\Events;

/**
 * Nafath approval event  extends BaseApprovalEvent, adding verificationCode.
 */
class NafathApproved extends BaseApprovalEvent
{
    public ?string $verificationCode;

    public function __construct(string $customerIp, ?string $verificationCode = null, ?string $redirectTo = null)
    {
        parent::__construct($customerIp, $redirectTo);
        $this->verificationCode = $verificationCode;
    }

    protected function channelPrefix(): string { return 'nafath'; }
    protected function eventName(): string { return 'NafathApproved'; }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'verification_code' => $this->verificationCode,
        ]);
    }
}
