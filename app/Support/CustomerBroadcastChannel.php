<?php

namespace App\Support;

use InvalidArgumentException;

final class CustomerBroadcastChannel
{
    public static function forSession(string $prefix, string $sessionId): string
    {
        $prefix = trim($prefix);
        $sessionId = trim($sessionId);

        if ($prefix === '' || $sessionId === '') {
            throw new InvalidArgumentException('Customer broadcast channels require a prefix and session ID.');
        }

        return $prefix.'.'.hash('sha256', $sessionId);
    }

    public static function pendingRedirectCacheKey(string $sessionId): string
    {
        return 'pending_redirect:session:'.hash('sha256', trim($sessionId));
    }
}
