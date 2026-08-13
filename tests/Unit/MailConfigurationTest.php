<?php

namespace Tests\Unit;

use App\Support\MailFailoverMailers;
use RuntimeException;
use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    public function test_failover_defaults_to_smtp_only(): void
    {
        $this->assertSame(['smtp'], MailFailoverMailers::normalize(null, true));
        $this->assertSame(['smtp'], MailFailoverMailers::normalize('', false));
    }

    public function test_failover_mailers_are_trimmed_and_deduplicated(): void
    {
        $this->assertSame(
            ['smtp', 'ses'],
            MailFailoverMailers::normalize(' SMTP, ses, smtp, ', true),
        );
    }

    public function test_production_rejects_log_as_a_failover_mailer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not contain "log"');

        MailFailoverMailers::normalize('smtp, LOG', true);
    }

    public function test_non_production_may_explicitly_use_log_as_a_failover_mailer(): void
    {
        $this->assertSame(
            ['smtp', 'log'],
            MailFailoverMailers::normalize('smtp,log', false),
        );
    }

    public function test_mail_config_uses_the_canonical_scheme_and_safe_failover_defaults(): void
    {
        $this->assertArrayHasKey('scheme', config('mail.mailers.smtp'));
        $this->assertArrayHasKey('require_tls', config('mail.mailers.smtp'));
        $this->assertArrayNotHasKey('encryption', config('mail.mailers.smtp'));
        $this->assertNotContains('log', config('mail.mailers.failover.mailers'));
    }

    public function test_tracked_environment_templates_use_mail_scheme_only(): void
    {
        foreach (['.env.example', '.env.production.example'] as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertIsString($contents);
            $this->assertStringContainsString('MAIL_SCHEME=smtp', $contents);
            $this->assertStringContainsString('MAIL_FAILOVER_MAILERS=smtp', $contents);
            $this->assertStringNotContainsString('MAIL_ENCRYPTION=', $contents);
        }

        $production = file_get_contents(base_path('.env.production.example'));

        $this->assertIsString($production);
        $this->assertStringContainsString('MAIL_REQUIRE_TLS=true', $production);
    }
}
