# Load Test — `POST /api/customer/page`

Local/staging-only tooling to measure the Redis-first heartbeat
optimization shipped in PR #43 (`perf/tracking-write-optimization`).

The endpoint is hit by every active visitor every 10s. The optimization
serves "same-page" heartbeats from Redis without touching the database;
this script lets you confirm that with real numbers.

## Script

[scripts/load-test-customer-page.mjs](../../scripts/load-test-customer-page.mjs)

Or via npm:

```bash
npm run loadtest:customer-page -- --url=http://localhost --visitors=500
```

## Flags

| Flag | Default | Meaning |
|---|---|---|
| `--url` | `http://localhost` | Base URL (no trailing slash needed) |
| `--visitors` | `1500` | Distinct synthetic visitors (each gets a unique `X-Forwarded-For`) |
| `--requests-per-visitor` | `5` | Heartbeats sent per visitor |
| `--concurrency` | `100` | Parallel workers |
| `--page` | `/insurance/start` | Value sent as `current_page` |
| `--timeout-ms` | `10000` | Per-request timeout (`AbortController`); aborted requests count as `failed` with error `timeout after Xms` |

The script prints a JSON report on stdout and a one-line human summary
on stderr.

## Examples

### Local

Against a local `php artisan serve` or Docker stack on
`http://localhost`:

```bash
node scripts/load-test-customer-page.mjs \
  --url=http://localhost \
  --visitors=200 \
  --requests-per-visitor=5 \
  --concurrency=50 \
  --page=/insurance/start
```

### Staging

```bash
node scripts/load-test-customer-page.mjs \
  --url=https://staging.example.com \
  --visitors=1500 \
  --requests-per-visitor=5 \
  --concurrency=100 \
  --page=/insurance/start
```

To prove the Redis fast-path savings, watch the database **while** the
test runs:

```bash
# Inside the app container or DB host
mysqladmin -uroot -p extended-status -i 1 \
  | grep -E 'Com_(update|insert|begin)|Innodb_rows_updated'
```

The first heartbeat per IP takes the slow path (1 INSERT/UPDATE). All
subsequent same-page heartbeats from that IP should produce **zero**
writes — counters should flatten while the script keeps sending traffic.

## Interpreting the report

```json
{
  "config":   { ... },
  "totals":   { "requests": 7500, "success": 7500, "failed": 0,
                "elapsedSec": 12.4, "requestsPerSec": 604.8 },
  "latencyMs": { "p50": 9.1, "p95": 24.6, "p99": 41.2, "max": 187.3 },
  "statusCounts": { "200": 7500 },
  "errSamples": []
}
```

- **p50** — typical latency. For Redis-served heartbeats this should be
  in the single-digit / low-double-digit milliseconds.
- **p95 / p99** — tail latency. These are what real users feel during a
  spike. Watch for sudden jumps when concurrency is increased — that
  usually points at queue/connection saturation, not Redis.
- **failed** — anything that wasn't a 2xx. Common causes:
  - `429` — `throttle:customer-tracking` middleware (200 req/min/IP).
    Expected once a single synthetic IP exceeds the limit; spread load
    across more `--visitors`.
  - `403` — `geo.api` middleware blocked the request. Run from an IP
    inside the allow-list, or hit a staging endpoint with the same
    config as production.
  - `5xx` — backend regression. Open a ticket.
- **statusCounts** — full distribution; use it to tell throttle hits
  apart from real failures.

## Warnings

- **Do not run this against production** unless explicitly authorized
  and rate-limited. It generates real `customer_profiles` rows on the
  first heartbeat per synthetic IP and counts against any rate limiter
  in front of the endpoint.
- The script sends `User-Agent: load-test-local` so its traffic is
  trivially filterable in nginx/Laravel logs.
- IPs are synthesized in the private `10.0.0.0/8` range. If your stack
  trusts `X-Forwarded-For` only behind a known proxy, run the script
  from inside that proxy chain or temporarily expand `TRUSTED_PROXIES`
  in staging.
- The endpoint is gated by `geo.api`. Staging must allow the runner IP,
  otherwise every request returns `403`.
