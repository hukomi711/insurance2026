<?php

namespace App\Events;

/**
 * Nafath approval event  extends BaseApprovalEvent, adding verificationCode.
 */
class NafathApproved extends BaseApprovalEvent
{
    public ?string $verificationCode;

    public function __construct(?string $sessionId, ?string $verificationCode = null, ?string $redirectTo = null, ?int $customerId = null)
    {
        parent::__construct($sessionId, $redirectTo, $customerId);
        $this->verificationCode = $verificationCode;
    }

    protected function channelPrefix(): string
    {
        return 'nafath';
    }

    protected function eventName(): string
    {
        return 'NafathApproved';
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'verification_code' => $this->verificationCode,
        ]);
    }
}
