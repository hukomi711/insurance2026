<?php

namespace App\Support;

use RuntimeException;

final class MailFailoverMailers
{
    /**
     * @return list<string>
     */
    public static function normalize(?string $value, bool $production): array
    {
        $mailers = array_values(array_unique(array_filter(
            array_map(
                static fn (string $mailer): string => strtolower(trim($mailer)),
                explode(',', (string) $value),
            ),
            static fn (string $mailer): bool => $mailer !== '',
        )));

        if ($mailers === []) {
            $mailers = ['smtp'];
        }

        $containsLogMailer = in_array('log', array_map('strtolower', $mailers), true);

        if ($production && $containsLogMailer) {
            throw new RuntimeException('MAIL_FAILOVER_MAILERS must not contain "log" in production.');
        }

        return $mailers;
    }
}
