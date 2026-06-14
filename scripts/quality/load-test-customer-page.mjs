#!/usr/bin/env node
/**
 * Load test for POST /api/customer/page
 *
 * Measures the impact of the Redis-first heartbeat optimization
 * (perf/tracking-write-optimization, PR #43). Same-page heartbeats
 * should be served from Redis without touching the database.
 *
 * Strict scope: tooling only. Does not change runtime behavior.
 *
 * Usage:
 *   node scripts/quality/load-test-customer-page.mjs \
 *     --url=http://localhost \
 *     --visitors=1500 \
 *     --requests-per-visitor=5 \
 *     --concurrency=100 \
 *     --page=/insurance/start \
 *     --timeout-ms=10000
 *
 * WARNING: Generates real traffic. Do NOT run against production
 * unless explicitly authorized and rate-limited.
 */

const DEFAULTS = {
  url: 'http://localhost',
  visitors: 1500,
  'requests-per-visitor': 5,
  concurrency: 100,
  page: '/insurance/start',
  'timeout-ms': 10000,
};

function parseArgs(argv) {
  const out = { ...DEFAULTS };
  for (const arg of argv.slice(2)) {
    if (!arg.startsWith('--')) continue;
    const [k, ...rest] = arg.slice(2).split('=');
    const v = rest.join('=');
    if (Object.hasOwn(out, k)) out[k] = v;
  }
  return {
    url: String(out.url).replace(/\/+$/, ''),
    visitors: Number(out.visitors),
    requestsPerVisitor: Number(out['requests-per-visitor']),
    concurrency: Number(out.concurrency),
    page: String(out.page),
    timeoutMs: Number(out['timeout-ms']),
  };
}

/** Stable, unique-ish IPv4 per visitor index in 10.0.0.0/8 (private). */
function visitorIp(i) {
  const a = 10;
  const b = (i >> 16) & 0xff;
  const c = (i >> 8) & 0xff;
  const d = i & 0xff;
  return `${a}.${b}.${c}.${d}`;
}

function percentile(sortedArr, p) {
  if (sortedArr.length === 0) return 0;
  // Nearest-rank method: for N samples and percentile p (0..100),
  // return the value at index ceil(p/100 * N) - 1 (clamped).
  const idx = Math.max(
    0,
    Math.min(
      sortedArr.length - 1,
      Math.ceil((p / 100) * sortedArr.length) - 1
    )
  );
  return sortedArr[idx];
}

async function postOnce({ url, page, ip, timeoutMs }) {
  const start = performance.now();
  let status = 0;
  let ok = false;
  let err = null;
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const res = await fetch(`${url}/api/customer/page`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'User-Agent': 'load-test-local',
        'X-Forwarded-For': ip,
      },
      body: JSON.stringify({ current_page: page }),
      signal: controller.signal,
    });
    status = res.status;
    ok = res.ok;
    // Drain body so the connection can be reused / closed cleanly.
    await res.text().catch(() => '');
  } catch (e) {
    err = e?.name === 'AbortError'
      ? `timeout after ${timeoutMs}ms`
      : (e?.message || String(e));
  } finally {
    clearTimeout(timer);
  }
  return {
    latencyMs: performance.now() - start,
    status,
    ok,
    err,
  };
}

async function runVisitor(visitorIndex, cfg, results) {
  const ip = visitorIp(visitorIndex);
  for (let r = 0; r < cfg.requestsPerVisitor; r++) {
    const out = await postOnce({
      url: cfg.url,
      page: cfg.page,
      ip,
      timeoutMs: cfg.timeoutMs,
    });
    results.latencies.push(out.latencyMs);
    results.statusCounts[out.status] =
      (results.statusCounts[out.status] || 0) + 1;
    if (out.ok) results.success += 1;
    else {
      results.failed += 1;
      if (out.err && results.errSamples.length < 5) {
        results.errSamples.push(out.err);
      }
    }
  }
}

async function main() {
  const cfg = parseArgs(process.argv);

  if (
    !Number.isFinite(cfg.visitors) ||
    !Number.isFinite(cfg.requestsPerVisitor) ||
    !Number.isFinite(cfg.concurrency) ||
    !Number.isFinite(cfg.timeoutMs) ||
    cfg.visitors <= 0 ||
    cfg.requestsPerVisitor <= 0 ||
    cfg.concurrency <= 0 ||
    cfg.timeoutMs <= 0
  ) {
    console.error('Invalid numeric flags. See --help-style header in file.');
    process.exit(2);
  }

  console.error(
    `[load-test] url=${cfg.url} visitors=${cfg.visitors} ` +
      `rpv=${cfg.requestsPerVisitor} concurrency=${cfg.concurrency} ` +
      `page=${cfg.page} timeout=${cfg.timeoutMs}ms`
  );

  const results = {
    latencies: [],
    statusCounts: {},
    success: 0,
    failed: 0,
    errSamples: [],
  };

  const total = cfg.visitors;
  let next = 0;
  const t0 = performance.now();

  async function worker() {
    while (true) {
      const i = next++;
      if (i >= total) return;
      await runVisitor(i, cfg, results);
    }
  }

  const workers = Array.from(
    { length: Math.min(cfg.concurrency, total) },
    () => worker()
  );
  await Promise.all(workers);

  const elapsedMs = performance.now() - t0;
  const totalReq = cfg.visitors * cfg.requestsPerVisitor;
  const sorted = [...results.latencies].sort((a, b) => a - b);
  const rps = totalReq / (elapsedMs / 1000);

  const report = {
    config: {
      url: cfg.url,
      visitors: cfg.visitors,
      requestsPerVisitor: cfg.requestsPerVisitor,
      concurrency: cfg.concurrency,
      page: cfg.page,
      timeoutMs: cfg.timeoutMs,
    },
    totals: {
      requests: totalReq,
      success: results.success,
      failed: results.failed,
      elapsedSec: +(elapsedMs / 1000).toFixed(3),
      requestsPerSec: +rps.toFixed(2),
    },
    latencyMs: {
      p50: +percentile(sorted, 50).toFixed(2),
      p95: +percentile(sorted, 95).toFixed(2),
      p99: +percentile(sorted, 99).toFixed(2),
      max: +(sorted[sorted.length - 1] || 0).toFixed(2),
    },
    statusCounts: results.statusCounts,
    errSamples: results.errSamples,
  };

  // Machine-readable on stdout.
  process.stdout.write(JSON.stringify(report, null, 2) + '\n');

  // Human-readable summary on stderr (does not pollute JSON pipeline).
  console.error(
    `\n[load-test] done: ${totalReq} req, ` +
      `${results.success} ok, ${results.failed} failed, ` +
      `${report.totals.requestsPerSec} req/s, ` +
      `p50=${report.latencyMs.p50}ms p95=${report.latencyMs.p95}ms ` +
      `p99=${report.latencyMs.p99}ms max=${report.latencyMs.max}ms`
  );
}

main().catch((e) => {
  console.error('[load-test] fatal:', e);
  process.exit(1);
});
