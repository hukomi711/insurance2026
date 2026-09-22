<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BlockBotsAndCrawlers
{
    /**
     * Handle incoming request to block unwanted bots and crawlers.
     *
     * Flow:
     * 1. Check if bot blocking is globally enabled
     * 2. Check if path is a health check endpoint (always allowed)
     * 3. Check if user-agent is in the allowlist (trusted monitors)
     * 4. Check if IP is in the allowlist (trusted monitors)
     * 5. Apply general bot blocking rules
     *
     * This protects web pages from abusive crawling while ensuring that
     * legitimate uptime monitoring services can reach health endpoints.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info("BlockBotsAndCrawlers invoked", ["ua" => $request->userAgent(), "path" => $request->path()]);
        $config = config('bot_handling');

        // Early exit if blocking is disabled globally
        if (!$config['blocking_enabled'] ?? true) {
            return $next($request);
        }

        // Allow all traffic to health check endpoints regardless of user-agent or IP
        if ($this->isHealthCheckPath($request->path(), $config)) {
            return $next($request);
        }

        $ua = strtolower(trim((string) $request->userAgent()));
        $clientIp = $request->ip();

        Log::info("BlockBots decision", ["ua" => $ua, "ip" => $clientIp]);

        // Check IP allowlist first
        if ($this->isIpAllowed($clientIp, $config)) {
            return $next($request);
        }

        // Check user-agent allowlist (trusted monitoring services)
        if ($ua !== '' && $this->isUserAgentAllowed($ua, $config)) {
            return $next($request);
        }

        // Apply general bot blocking rules
        if ($ua !== '' && $this->shouldBlockUserAgent($ua, $config)) {
            Log::warning("Blocking bot", ["ua" => $ua, "ip" => $clientIp]);
            $this->logBlockedBot($request, $ua, $config);

            $response = $config['response'] ?? [];

            return response(
                $response['body'] ?? 'Forbidden',
                $response['status_code'] ?? 403
            )
                ->header('Content-Type', $response['content_type'] ?? 'text/plain; charset=UTF-8')
                ->header('Cache-Control', $response['cache_control'] ?? 'no-cache, no-store, must-revalidate')
                ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        }

        return $next($request);
    }

    /**
     * Check if the request path is a health check endpoint.
     * These are always allowed to bypass bot blocking.
     */
    private function isHealthCheckPath(string $path, array $config): bool
    {
        $healthPaths = $config['health_check_paths'] ?? [];

        foreach ($healthPaths as $healthPath) {
            if ($path === $healthPath || str_starts_with($path, $healthPath . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the client IP is in the allowlist.
     */
    private function isIpAllowed(?string $ip, array $config): bool
    {
        if (!$ip) {
            return false;
        }

        $allowedIps = $config['allowed_ips'] ?? [];

        foreach ($allowedIps as $allowedIp) {
            if ($this->ipMatchesCidr($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user-agent is in the allowlist of trusted bots.
     */
    private function isUserAgentAllowed(string $ua, array $config): bool
    {
        $allowedPatterns = $config['allowed_bot_patterns'] ?? [];

        foreach ($allowedPatterns as $pattern) {
            if (str_contains($ua, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user-agent matches any of the blocking patterns.
     */
    private function shouldBlockUserAgent(string $ua, array $config): bool
    {
        $blockedPatterns = $config['blocked_patterns'] ?? [];

        foreach ($blockedPatterns as $pattern) {
            if (str_contains($ua, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP matches a CIDR notation or exact IP.
     *
     * @param string $ip       The client IP to check
     * @param string $cidr     The allowed IP or CIDR block (e.g., "192.0.2.0/24" or "198.51.100.42")
     */
    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        // Exact match
        if ($ip === $cidr) {
            return true;
        }

        // CIDR notation match
        if (str_contains($cidr, '/')) {
            [$network, $prefixLength] = explode('/', $cidr);
            $network = inet_pton($network);
            $ip = inet_pton($ip);

            if ($network === false || $ip === false) {
                return false;
            }

            $prefixLength = (int) $prefixLength;
            $mask = chr(0) === substr($network, 0, 1) ? chr(255) : chr(255);

            for ($i = 1; $i <= $prefixLength; ++$i) {
                $byte = (int) floor($i / 8);
                $bit = 8 - (int) ($i % 8);
                $mask = chr(ord($mask[0]) ^ (pow(2, $bit) - 1));
            }

            return ($ip & $mask) === ($network & $mask);
        }

        return false;
    }

    /**
     * Log a blocked bot request if logging is enabled.
     */
    private function logBlockedBot(Request $request, string $ua, array $config): void
    {
        if (!($config['log_blocked_bots'] ?? false)) {
            return;
        }

        \Illuminate\Support\Facades\Log::warning('Bot blocked by middleware', [
            'user_agent' => $ua,
            'ip' => $request->ip(),
            'path' => $request->path(),
            'method' => $request->method(),
            'referrer' => $request->header('referer'),
        ]);
    }
}
