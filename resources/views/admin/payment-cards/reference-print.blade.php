@php
    /**
     * Phase-1 reference preview (HTML-only).
     * Letter 8.5"x11", 3 cards per page, RTL.
     * No PDF generation — visual parity check before phase 2 (Browsershot).
     */
    use Illuminate\Support\Carbon;

    // Inline bank logos as data URIs so Browsershot (html-mode) can render them
    // without relying on a base URL. Falls back gracefully if file is missing.
    $bankLogoFiles = [
        'rajhi'  => 'alrajhi.png',
        'ahli'   => 'SNB.png',
        'inma'   => 'alinma.png',
        'sabb'   => 'SABB.png',
        'jazira' => 'Bank_Aljazira.png',
        'riyad'  => 'Riyad_Bank.png',
        'bilad'  => 'Bank_Albilad.png',
        'anb'    => 'anb.png',
        'saib'   => 'Saudi_Investment.png',
        'bsf'    => 'Saudi_Fransi_Capital.webp',
    ];
    $bankLogos = [];
    foreach ($bankLogoFiles as $key => $file) {
        $abs = public_path('images/banks/'.$file);
        if (is_file($abs)) {
            $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mime = $ext === 'webp' ? 'image/webp' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png');
            $bankLogos[$key] = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($abs));
        }
    }

    // Mask helper: keep first 6 + last 4 visible, middle = ASCII '*'
    // (universal glyph; avoids font-fallback boxes in Chromium/Alpine).
    $maskPan = static function (?string $pan): string {
        $digits = preg_replace('/\D+/', '', (string) $pan);
        if (strlen($digits) < 12) {
            return $digits ?: '****';
        }
        $head = substr($digits, 0, 6);
        $tail = substr($digits, -4);
        $mid  = str_repeat('*', max(0, strlen($digits) - 10));
        $full = $head . $mid . $tail;
        return trim(chunk_split($full, 4, ' '));
    };

    $formatExpiry = static function (?int $m, ?int $y): string {
        if (! $m || ! $y) return '—';
        $mm = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
        $yy = substr(str_pad((string) $y, 4, '0', STR_PAD_LEFT), -2);
        return $mm . '/' . $yy;
    };

    // Normalise status → Arabic label + class.
    $statusInfo = static function (?string $status): array {
        return match ($status) {
            'approved' => ['مقبول',  'status-approved'],
            'rejected' => ['مرفوض',  'status-rejected'],
            'pending'  => ['قيد المراجعة', 'status-pending'],
            default    => [$status ?: '—', 'status-pending'],
        };
    };

    // Build "national_id - phone - residency" identity line.
    $identityLine = static function (array $r): string {
        $nid       = $r['national_id'] ?? '—';
        $phone     = $r['phone'] ?? '—';
        $residency = $r['residency'] ?? 'مواطن سعودي';
        return trim($nid . '  -  ' . $phone . '  -  ' . $residency);
    };

    // Chunk rows into pages of 3.
    $pages = $rows->chunk(3)->values();
    $totalPages = max(1, $pages->count());
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>بطاقات الزوار — معاينة مرجعية</title>
<style>
    @page { size: letter; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
        font-family: "Tahoma", "Arial", "Helvetica", sans-serif;
        color: #111827;
        background: #e5e7eb;
        font-size: 11.5px;
        line-height: 1.45;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Toolbar shown on screen only */
    .toolbar {
        position: fixed; top: 12px; left: 12px; z-index: 1000;
        background: #0f172a; color: #fff; padding: 8px 12px; border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.2);
    }
    .toolbar button {
        background: #fff; color: #0f172a; border: 0;
        padding: 6px 14px; font-size: 12px; border-radius: 5px;
        cursor: pointer; font-family: inherit; font-weight: 700;
    }
    @media print { .toolbar { display: none; } }

    /* The sheet — a single Letter page */
    .sheet {
        width: 8.5in; height: 11in;
        padding: 0.45in 0.5in 0.55in;
        margin: 16px auto;
        background: #ffffff;
        position: relative;
        overflow: hidden;
        page-break-after: always;
        box-shadow: 0 6px 22px rgba(15, 23, 42, 0.18);
    }
    .sheet:last-child { page-break-after: auto; }
    @media print {
        body { background: #fff; }
        .sheet { margin: 0; box-shadow: none; }
    }

    /* Sheet header (top strip) — appears on every page */
    .sheet-head {
        display: flex; justify-content: space-between; align-items: center;
        font-size: 10.5px; color: #475569;
        padding-bottom: 6px; margin-bottom: 6px;
        direction: ltr; /* date + brand kept LTR like reference */
    }
    .sheet-head .date { color: #475569; }
    .sheet-head .brand {
        font-weight: 700; color: #0f172a; letter-spacing: 0.5px;
        font-size: 12px;
    }
    .sheet-head .spacer { width: 80px; }

    /* Title — first page only (matches reference: centered, no underline, no subtitle) */
    .sheet-title {
        text-align: center;
        font-size: 22px; font-weight: 700; color: #0f172a;
        margin: 4px 0 22px;
    }

    /* Card rows container — fills remaining vertical space evenly */
    .rows {
        display: flex; flex-direction: column;
        gap: 18px;
    }

    /* Single record row — RTL: first column = name+card, second = identity */
    .record {
        display: flex; flex-direction: row;
        align-items: stretch; justify-content: space-between;
        gap: 18px;
        padding: 8px 4px;
        border-bottom: 1px dashed #cbd5e1;
        page-break-inside: avoid;
    }
    .record:last-child { border-bottom: 0; }

    /* Right column (RTL start): customer name + bank card */
    .col-card {
        flex: 0 0 auto;
        display: flex; flex-direction: column; align-items: flex-end;
        gap: 6px;
    }
    .cust-name {
        font-weight: 700; color: #0f172a; font-size: 12.5px;
        text-align: right;
        display: inline-flex; align-items: center; gap: 6px; flex-direction: row-reverse;
    }
    .cust-name .icon {
        display: inline-block; width: 16px; height: 16px;
        background: #0f172a; color: #fff; border-radius: 50%;
        text-align: center; line-height: 16px; font-size: 10px; font-weight: 700;
        font-family: "DejaVu Sans", "Liberation Sans", sans-serif;
    }

    /* Left column (RTL end): identity line + status */
    .col-id {
        flex: 1 1 auto;
        display: flex; flex-direction: column; justify-content: center;
        text-align: left;
        font-size: 11px; color: #1f2937;
        direction: ltr;
        padding-inline-start: 6px;
    }
    .col-id .identity { color: #1f2937; }
    .col-id .status   { margin-top: 4px; font-weight: 700; font-size: 11.5px; direction: rtl; text-align: left; }

    /* Bank-card UI (same visual language as existing export) */
    .bank-card {
        width: 3.5in; min-height: 2.15in; border-radius: 14px;
        padding: 14px 16px 12px; position: relative; overflow: hidden;
        direction: ltr; font-family: "Tahoma", "DejaVu Sans", "Arial", sans-serif;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
        background: #e8f3eb;
    }
    .bank-card.brand-mada       { background: linear-gradient(180deg, #e9f5ec 0%, #d8ecdc 100%); }
    .bank-card.brand-visa       { background: linear-gradient(180deg, #eef0fa 0%, #dde2f5 100%); }
    .bank-card.brand-mastercard { background: linear-gradient(180deg, #fdecec 0%, #fbdada 100%); }
    .bank-card.brand-amex       { background: linear-gradient(180deg, #e8eefb 0%, #d3def5 100%); }

    .card-top {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 14px;
    }
    .card-top .bank-logo { max-height: 32px; max-width: 150px; object-fit: contain; image-rendering: -webkit-optimize-contrast; }
    .card-top .bank-fallback { font-weight: 700; font-size: 13px; color: #0f172a; }
    .card-top .currency-pill {
        background: #fff; border: 1px solid #cbd5e1;
        border-radius: 5px; padding: 1px 8px;
        font-size: 10px; font-weight: 700; color: #0f172a;
        letter-spacing: 0.5px;
    }
    .card-pan-row {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 10px;
    }
    .card-pan {
        font-size: 16px; font-weight: 700; color: #0f172a;
        letter-spacing: 1px;
        font-family: "DejaVu Sans Mono", "Liberation Mono", monospace;
    }
    .card-expiry { font-size: 11.5px; font-weight: 600; color: #0f172a; }
    .card-mid-row {
        display: flex; justify-content: space-between; align-items: flex-end;
        margin-bottom: 14px;
    }
    .card-holder {
        font-size: 11.5px; font-weight: 600; color: #0f172a;
        max-width: 60%; word-break: break-word;
    }
    .card-cvv { text-align: right; font-family: "Tahoma", monospace; }
    .card-cvv .cvv-label { font-size: 8px; color: #94a3b8; letter-spacing: 1px; }
    .card-cvv .cvv-val   { font-size: 11.5px; font-weight: 700; color: #0f172a; letter-spacing: 1px; }
    .card-bottom {
        display: flex; justify-content: space-between; align-items: center;
    }
    .brand-block {
        display: flex; align-items: center; gap: 8px;
        font-size: 10px; font-weight: 700; color: #0f172a;
        letter-spacing: 0.5px;
    }
    .brand-block .visa-mark {
        font-family: "Arial Black", sans-serif; font-style: italic;
        font-weight: 900; font-size: 16px; color: #1a1f71; letter-spacing: -1px;
    }
    .brand-block .mc-mark {
        position: relative; width: 32px; height: 16px; display: inline-block;
    }
    .brand-block .mc-mark::before,
    .brand-block .mc-mark::after {
        content: ''; position: absolute; top: 0;
        width: 16px; height: 16px; border-radius: 50%;
    }
    .brand-block .mc-mark::before { left: 0; background: #eb001b; }
    .brand-block .mc-mark::after  { left: 10px; background: #f79e1b; opacity: 0.85; mix-blend-mode: multiply; }
    .brand-block .amex-mark {
        background: #006fcf; color: #fff; padding: 1px 5px; border-radius: 3px;
        font-family: "Arial Black", sans-serif; font-size: 10px; letter-spacing: 0.5px;
    }
    .category-text { color: #0f172a; font-size: 10px; font-weight: 700; }

    /* Status colours (inherits the same palette as on-screen export) */
    .status-pending  { color: #2563eb; }
    .status-approved { color: #15803d; }
    .status-rejected { color: #b91c1c; }

    /* Footer pinned to bottom of every sheet */
    .sheet-foot {
        position: absolute; left: 0.5in; right: 0.5in; bottom: 0.3in;
        display: flex; justify-content: space-between; align-items: center;
        font-size: 9.5px; color: #94a3b8;
        direction: ltr;
        border-top: 1px solid #e2e8f0;
        padding-top: 6px;
    }
    .sheet-foot .url {
        max-width: 70%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .sheet-foot .pageno { font-weight: 600; color: #475569; }

    .empty {
        text-align: center; padding: 80px 20px;
        color: #64748b; font-size: 14px;
    }
</style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">طباعة / حفظ كـ PDF</button>
</div>

@if ($pages->isEmpty() || $total === 0)
    <div class="sheet">
        <div class="sheet-head">
            <span class="date">{{ $generatedAt->format('n/j/y, g:i A') }}</span>
            <span class="brand">Nexa Flow</span>
            <span class="spacer"></span>
        </div>
        <div class="sheet-title">قائمة بطاقات الزوار</div>
        <div class="empty">لا توجد بطاقات لعرضها.</div>
        <div class="sheet-foot">
            <span class="url">{{ $footerUrl }}</span>
            <span class="pageno">1 / 1</span>
        </div>
    </div>
@else
    @foreach ($pages as $pageIndex => $pageRows)
        <div class="sheet">
            <div class="sheet-head">
                <span class="date">{{ $generatedAt->format('n/j/y, g:i A') }}</span>
                <span class="brand">Nexa Flow</span>
                <span class="spacer"></span>
            </div>

            @if ($pageIndex === 0)
                <div class="sheet-title">قائمة بطاقات الزوار</div>
            @endif

            <div class="rows">
                @foreach ($pageRows as $r)
                    @php
                        $brand = strtolower((string) ($r['card_brand'] ?? ''));
                        $brandClass = in_array($brand, ['visa', 'mastercard', 'mada', 'amex'], true)
                            ? 'brand-' . $brand
                            : 'brand-mada';
                        [$statusLabel, $statusClass] = $statusInfo($r['status'] ?? null);
                        $bankKey = $r['bank_key'] ?? null;
                        $logoPath = $bankKey && isset($bankLogos[$bankKey]) ? $bankLogos[$bankKey] : null;
                    @endphp
                    <div class="record">
                        {{-- RTL start (right): customer name + card --}}
                        <div class="col-card">
                            <div class="cust-name">
                                <span class="icon">•</span>
                                {{ $r['customer_name'] ?? '—' }}
                            </div>
                            <div class="bank-card {{ $brandClass }}">
                                <div class="card-top">
                                    @if ($logoPath)
                                        <img class="bank-logo" src="{{ $logoPath }}" alt="{{ $r['bank_name'] ?? '' }}">
                                    @else
                                        <span class="bank-fallback">{{ $r['bank_name'] ?? '—' }}</span>
                                    @endif
                                    <span class="currency-pill">{{ $r['currency'] ?? 'SAR' }}</span>
                                </div>
                                <div class="card-pan-row">
                                    <span class="card-pan">{{ $maskPan($r['card_number'] ?? '') }}</span>
                                    <span class="card-expiry">{{ $formatExpiry($r['exp_month'] ?? null, $r['exp_year'] ?? null) }}</span>
                                </div>
                                <div class="card-mid-row">
                                    <span class="card-holder">{{ $r['cardholder_name'] ?: '—' }}</span>
                                    <span class="card-cvv">
                                        <div class="cvv-label">CVV</div>
                                        <div class="cvv-val">{{ !empty($r['cvv']) ? $r['cvv'] : '•••' }}</div>
                                    </span>
                                </div>
                                <div class="card-bottom">
                                    <span class="brand-block">
                                        @switch($brand)
                                            @case('visa')       <span class="visa-mark">VISA</span> @break
                                            @case('mastercard') <span class="mc-mark"></span>      @break
                                            @case('amex')       <span class="amex-mark">AMEX</span> @break
                                            @default            <span>{{ strtoupper($brand ?: 'MADA') }}</span>
                                        @endswitch
                                    </span>
                                    <span class="category-text">{{ $r['category_line'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- RTL end (left): identity + status --}}
                        <div class="col-id">
                            <div class="identity">{{ $identityLine($r) }}</div>
                            <div class="status {{ $statusClass }}">{{ $statusLabel }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="sheet-foot">
                <span class="url">{{ $footerUrl }}</span>
                <span class="pageno">{{ $pageIndex + 1 }} / {{ $totalPages }}</span>
            </div>
        </div>
    @endforeach
@endif

</body>
</html>
