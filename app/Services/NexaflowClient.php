<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Nexaflow API client — thin wrapper around Laravel's HTTP client.
 *
 * Docs: https://nexaflow.readme.io/reference
 * Base: https://api.nexaflow.xyz/api
 *
 * Auth: API key is sent via the `x-api-key` request header. The exact
 * header name is configurable in case Nexaflow changes it; override
 * with NEXAFLOW_AUTH_HEADER if needed.
 *
 * Usage:
 *   app(NexaflowClient::class)->websites();
 *   app(NexaflowClient::class)->website($id);
 *   app(NexaflowClient::class)->page($id);
 *   app(NexaflowClient::class)->submitForm($formId, ['name' => '...']);
 */
class NexaflowClient
{
    public function __construct(
        protected ?string $key = null,
        protected ?string $base = null,
        protected int $timeout = 10,
        protected string $authHeader = 'x-api-key',
    ) {
        $this->key        = $this->key ?: (string) config('services.nexaflow.key', '');
        $this->base       = rtrim($this->base ?: (string) config('services.nexaflow.base', 'https://api.nexaflow.xyz/api'), '/');
        $this->timeout    = $this->timeout ?: (int) config('services.nexaflow.timeout', 10);
        $this->authHeader = (string) config('services.nexaflow.auth_header', $this->authHeader);
    }

    /** GET /websites — all websites for the authenticated key */
    public function websites(): array
    {
        return $this->json($this->request(retry: true)->get($this->path('/websites')));
    }

    /** GET /websites/{id} */
    public function website(string $id): array
    {
        return $this->json($this->request(retry: true)->get($this->path("/websites/{$id}")));
    }

    /** GET /page/{id}?websiteId=... */
    public function page(string $id, ?string $websiteId = null): array
    {
        $websiteId = $websiteId ?: (string) config('services.nexaflow.website_id', '');
        $query     = $websiteId !== '' ? ['websiteId' => $websiteId] : [];

        return $this->json($this->request(retry: true)->get($this->path("/page/{$id}"), $query));
    }

    /**
     * POST /form/{formId}
     *
     * Retry is disabled to avoid duplicate form submissions if Nexaflow
     * received the payload but the response was lost in transit.
     */
    public function submitForm(string $formId, array $payload): array
    {
        return $this->json(
            $this->request(retry: false)->post($this->path("/form/{$formId}"), $payload)
        );
    }

    // ── Generic escape hatch ───────────────────────────────────────
    public function get(string $path, array $query = []): array
    {
        return $this->json($this->request(retry: true)->get($this->path($path), $query));
    }

    public function post(string $path, array $payload = [], bool $retry = false): array
    {
        return $this->json($this->request(retry: $retry)->post($this->path($path), $payload));
    }

    // ── Internal ───────────────────────────────────────────────────
    protected function request(bool $retry = true): PendingRequest
    {
        if ($this->key === '') {
            throw new RuntimeException('NEXAFLOW_API_KEY is not configured.');
        }

        $request = Http::baseUrl($this->base)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout(5)
            ->withHeaders([$this->authHeader => $this->key]);

        return $retry
            ? $request->retry(2, 250, throw: false)
            : $request;
    }

    protected function path(string $path): string
    {
        return ltrim($path, '/');
    }

    protected function json(Response $response): array
    {
        if ($response->failed()) {
            Log::warning('Nexaflow API error', [
                'status'       => $response->status(),
                'url'          => method_exists($response, 'effectiveUri')
                    ? (string) $response->effectiveUri()
                    : null,
                'body_excerpt' => app()->environment('local')
                    ? mb_substr((string) $response->body(), 0, 500)
                    : null,
            ]);
            throw new RuntimeException("Nexaflow API request failed with status {$response->status()}");
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
