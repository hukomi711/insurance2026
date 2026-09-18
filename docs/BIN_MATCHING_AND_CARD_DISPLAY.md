# BIN-Based Card Matching & Display System

Complete guide to the Bank Identification Number (BIN) matching system in insurance2026, including bank detection, card branding, and unified display logic.

## Table of Contents

- [1. BIN Matching Overview](#1-bin-matching-overview)
- [2. Card Data Flow](#2-card-data-flow)
- [3. Bank Detection System](#3-bank-detection-system)
- [4. Card Display Modes](#4-card-display-modes)
- [5. Implementation Details](#5-implementation-details)
- [6. Database Schema](#6-database-schema)
- [7. Frontend Components](#7-frontend-components)
- [8. API Endpoints](#8-api-endpoints)
- [9. Configuration](#9-configuration)
- [10. Troubleshooting](#10-troubleshooting)

---

## 1. BIN Matching Overview

### What is a BIN?

A **Bank Identification Number (BIN)** is the first 6-8 digits of a credit/debit card that identifies:

- **Issuing Bank** (e.g., Al Rajhi, Ahli, Riyad)
- **Card Network** (Visa, Mastercard, Mada, AMEX)
- **Card Type** (Debit, Credit, Prepaid)
- **Card Level** (Standard, Platinum, Gold, etc.)
- **Product Name** (e.g., "Tahweel", "Rewards Plus")

### Why BIN Matching Matters

1. **Branding**: Display correct bank logo and colors
2. **Customer Experience**: Show relevant card tier/benefits
3. **Risk Assessment**: Identify card types for fraud detection
4. **Compliance**: Track card networks for regulatory reporting
5. **Payment Processing**: Route to correct payment gateway

### Example BIN Lookup

```
Card Number: 4086 0118 1234 5678
              │││└─ Start of card-specific data
              ││└──ency
              │└───ary Network (usually first digit)
              └────ary Issuer Code
BIN-6: 408601 ← Identifies Al Rajhi bank (Visa)
BIN-8: 40860118 ← More specific; may identify exact product
```

---

## 2. Card Data Flow

### Full Transaction Flow

```
1. Customer enters card data in payment form
   ↓
2. Frontend validates Luhn checksum
   ↓
3. Backend receives encrypted card data
   ↓
4. CardBinResolver extracts BIN (first 6-8 digits)
   ↓
5. Query card_bin_ranges table → find matching bank/network
   ↓
6. Query issuer_banks table → fetch bank metadata (logo, colors, etc.)
   ↓
7. Store in PaymentCard with resolver result (bin_6, detected_bank_key, etc.)
   ↓
8. Admin views card in dashboard
   ├→ CardDisplayService formats card number (masked/unmasked)
   ├→ CardBinResolver provides branding data
   └→ PaymentCardVisual/BankCard3D component renders card
```

### Data At Rest

```sql
-- payment_cards table
┌─────────────────────┬──────────────────────────────────┐
│ Column              │ Value                            │
├─────────────────────┼──────────────────────────────────┤
│ card_number         │ (encrypted) "4086...5678"        │
│ last4               │ "5678"                           │
│ holder_name         │ "Ahmed Al Rajhi"                 │
│ expiry_month        │ "12" (encrypted)                 │
│ expiry_year         │ "25" (encrypted)                 │
│ cvv_encrypted       │ (encrypted) "123"                │
│                     │                                  │
│ bin_6               │ "408601"                         │
│ bin_8               │ "40860118"                       │
│ detected_bank_key   │ "rajhi"                          │
│ detected_network    │ "visa"                           │
│ detected_type       │ "debit"                          │
│ detected_level      │ "platinum"                       │
│ detection_confidence│ 0.95                             │
│ detection_match_type│ "exact-6digit"                   │
└─────────────────────┴──────────────────────────────────┘
```

---

## 3. Bank Detection System

### BIN Lookup Tables

#### card_bin_ranges table

Stores exact BIN-to-bank mappings:

```sql
SELECT * FROM card_bin_ranges LIMIT 5;

┌─────┬──────────┬──────────┬────────┬─────────┬──────────┬──────┬─────────┐
│ id  │ bin_start│ bin_end  │ bank   │ network │ type     │level │ product │
├─────┼──────────┼──────────┼────────┼─────────┼──────────┼──────┼─────────┤
│ 1   │ 408601   │ 408601   │ rajhi  │ visa    │ debit    │ std  │ tahweel │
│ 2   │ 458618   │ 458618   │ rajhi  │ visa    │ credit   │ plat │ rewards │
│ 3   │ 358925   │ 358925   │ ahli   │ master  │ credit   │ gold │ ahlitax │
│ ... │          │          │        │         │          │      │         │
└─────┴──────────┴──────────┴────────┴─────────┴──────────┴──────┴─────────┘
```

**Population:**

- Seeded from `config/bank_bins.php` via migrations
- Updated via artisan commands or manual database edits
- Supports multiple entries per bank (different BINs, card types, levels)

#### issuer_banks table

Stores bank metadata and branding:

```sql
SELECT * FROM issuer_banks WHERE key = 'rajhi';

┌─────┬─────────┬──────────────┬──────────────┬─────────────┬────────────┐
│ id  │ key     │ name_ar      │ name_en      │ logo_path   │ brand_color│
├─────┼─────────┼──────────────┼──────────────┼─────────────┼────────────┤
│ 1   │ rajhi   │ البنك الراجحي │ Al Rajhi Bank│ /bank_rajhi │ #003b71    │
│ ... │         │              │              │             │            │
└─────┴─────────┴──────────────┴──────────────┴─────────────┴────────────┘
```

**Metadata:**

- Arabic/English names
- Logo file paths (served from `/public/images/banks/`)
- Brand colors (hex) for card design
- Additional properties (support URL, phone, etc.)

### BIN Resolution Priority

The `CardBinResolver` uses a priority-based matching strategy:

```php
/**
 * Priority order for BIN matching:
 * 
 * 1. Exact 8-digit match (bin_8)
 *    → Most specific, product-level identification
 * 
 * 2. Exact 6-digit match (bin_6)
 *    → Standard BIN, bank-level identification
 * 
 * 3. First 4-digit match (bin_4)
 *    → Fallback for unusual card lengths
 * 
 * 4. Network prefix match
 *    → Last resort: assume Visa/MC/Mada from first digit
 * 
 * 5. Generic fallback
 *    → Unknown card (still Luhn-validated)
 */
```

**Example Resolution:**

```
Card: 4086 0118 1234 5678

✓ Check BIN-8: 40860118
  → Found in card_bin_ranges
  → Result: Al Rajhi, Visa, Debit, Platinum, "Tahweel"
  → Confidence: 95% (exact match)
  
✗ If not found, check BIN-6: 408601
  → Found
  → Result: Al Rajhi, Visa (any product)
  → Confidence: 85% (general match)
  
✗ If not found, check BIN-4: 4086
  → Check card_bin_ranges for 4-digit range
  
✗ If not found, network fallback: "4" = Visa
  → Result: Unknown bank, Visa
  → Confidence: 60% (network only)
```

---

## 4. Card Display Modes

### Display Configuration

Card numbers are formatted based on context and user role:

#### Mode: Masked (Default)

```
•••• •••• •••• 5678
```

**Use Cases:**

- Customer-facing receipts
- Email notifications
- Export reports
- Default admin dashboard

**Security:**

- Reveals only last 4 digits
- Safe for logs, screenshots, exports

#### Mode: Partial

```
408601•• •••• 5678
```

**Use Cases:**

- Advanced admin dashboard (optional)
- Card summary cards
- Quick identification

**Security:**

- Reveals first 6 (BIN) + last 4
- First 6 is publicly available (determines bank)
- Safe for most contexts

#### Mode: Unmasked

```
4086 0118 1234 5678
```

**Use Cases:**

- Authorized admin detail view only
- Real-time dashboard (not exported)
- Specific admin operations

**Security:**

- ONLY for authenticated admins
- Must log access to audit trail
- Never export or send via email
- Requires explicit permission

### Expiry Display

#### Mode: Hidden

```
(null)
```

#### Mode: Masked

```
12/**
```

#### Mode: Visible (Default)

```
12/25
```

### CVV Display

**Default:** Hidden/null

- CVV should NEVER appear in exports, PDFs, or emails
- Only show in real-time admin dashboard if toggled on
- Requires explicit configuration
- Always logged to audit trail

---

## 5. Implementation Details

### CardBinResolver Service

**File:** `app/Services/Bin/CardBinResolver.php`

Performs BIN resolution and caching:

```php
use App\Services\Bin\CardBinResolver;

$resolver = app(CardBinResolver::class);

// Resolve card
$result = $resolver->resolve('4086011812345678');

// Result properties
$result->bin6             // '408601'
$result->bin8             // '40860118'
$result->last4            // '5678'
$result->bankKey          // 'rajhi'
$result->bankNameAr       // 'البنك الراجحي'
$result->bankNameEn       // 'Al Rajhi Bank'
$result->logoPath         // '/images/banks/bank_rajhi.png'
$result->network          // 'visa'
$result->secondaryNetwork // null
$result->cardType         // 'debit'
$result->cardLevel        // 'platinum'
$result->productName      // 'Tahweel'
$result->currency         // 'SAR'
$result->isValidLuhn      // true
$result->confidence       // 0.95
$result->matchType        // 'exact-8digit'
```

### CardDisplayService Service

**File:** `app/Services/CardDisplay/CardDisplayService.php`

Formats card data for display:

```php
use App\Services\CardDisplay\CardDisplayService;

$display = app(CardDisplayService::class);

// Format for context
$formatted = $display->formatForContext(
    cardNumber: '4086011812345678',
    expiryMonth: '12',
    expiryYear: '25',
    cvv: '123',
    context: 'admin_details'  // or: dashboard, export, email, customer
);

/*
Result:
[
    'card_number' => '4086 0118 1234 5678',  // Based on mode
    'card_number_masked' => '•••• •••• •••• 5678',
    'card_number_unmasked' => '4086 0118 1234 5678',
    'card_number_partial' => '408601•• •••• 5678',
    'card_number_last4' => '5678',
    'expiry' => '12/25',
    'cvv' => null,  // Hidden by default
    'context' => 'admin_details',
    'mode' => 'unmasked'
]
*/
```

### PaymentCard Model

**File:** `app/Models/PaymentCard.php`

Stores card data with resolver results:

```php
$card = PaymentCard::find(123);

// Encrypted data
$card->card_number      // Decrypted via EncryptedSafe cast
$card->expiry_month     // Decrypted
$card->expiry_year      // Decrypted
$card->cvv_encrypted    // Decrypted
$card->last4            // Plaintext

// BIN resolver results (cached from resolution)
$card->bin_6            // '408601'
$card->bin_8            // '40860118'
$card->detected_bank_key       // 'rajhi'
$card->detected_network        // 'visa'
$card->detected_type           // 'debit'
$card->detected_level          // 'platinum'
$card->detection_confidence    // 0.95
$card->detection_match_type    // 'exact-8digit'

// Accessor
$card->card_display    // Returns card_number (computed attribute)
```

---

## 6. Database Schema

### card_bin_ranges

```sql
CREATE TABLE card_bin_ranges (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bin_start VARCHAR(8) NOT NULL,       -- '408601' or '40860118'
    bin_end VARCHAR(8) NOT NULL,         -- '408601' or '40860118'
    issuer_bank_id BIGINT NOT NULL,
    card_network VARCHAR(20) NOT NULL,   -- 'visa', 'mastercard', 'mada', 'amex'
    card_type VARCHAR(20) NULL,          -- 'debit', 'credit', 'prepaid'
    card_level VARCHAR(50) NULL,         -- 'standard', 'gold', 'platinum'
    product_name VARCHAR(100) NULL,      -- 'Tahweel', 'Rewards Plus'
    currency VARCHAR(3) DEFAULT 'SAR',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_bin_start (bin_start),
    INDEX idx_bin_end (bin_end),
    INDEX idx_issuer (issuer_bank_id),
    INDEX idx_network (card_network),
    UNIQUE KEY unique_bin_range (bin_start, bin_end)
);
```

### issuer_banks

```sql
CREATE TABLE issuer_banks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(50) NOT NULL UNIQUE,     -- 'rajhi', 'ahli', etc.
    name_ar VARCHAR(100) NOT NULL,       -- 'البنك الراجحي'
    name_en VARCHAR(100) NOT NULL,       -- 'Al Rajhi Bank'
    logo_path VARCHAR(255) NULL,         -- '/images/banks/bank_rajhi.png'
    brand_color VARCHAR(7) NULL,         -- '#003b71'
    support_email VARCHAR(100) NULL,
    support_phone VARCHAR(20) NULL,
    country VARCHAR(2) DEFAULT 'SA',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### payment_cards (BIN-related columns)

```sql
ALTER TABLE payment_cards ADD COLUMN (
    bin_6 VARCHAR(6) NULL AFTER last4,
    bin_8 VARCHAR(8) NULL AFTER bin_6,
    detected_bank_key VARCHAR(50) NULL AFTER bin_8,
    detected_network VARCHAR(20) NULL AFTER detected_bank_key,
    detected_secondary_network VARCHAR(20) NULL AFTER detected_network,
    detected_type VARCHAR(20) NULL AFTER detected_secondary_network,
    detected_level VARCHAR(50) NULL AFTER detected_type,
    detection_confidence DECIMAL(3,2) NULL AFTER detected_level,
    detection_match_type VARCHAR(20) NULL AFTER detection_confidence,
    
    INDEX idx_bin_6 (bin_6),
    INDEX idx_bin_8 (bin_8),
    INDEX idx_detected_bank (detected_bank_key),
    INDEX idx_detected_network (detected_network)
);
```

---

## 7. Frontend Components

### PaymentCardVisual.vue

**File:** `resources/js/dashboard/components/PaymentCardVisual.vue`

Basic card visual (static/reference):

```vue
<PaymentCardVisual
  :panDisplay="displayData.card_number"
  :cvvDisplay="displayData.cvv"
  :holder="card.holder_name"
  :expiry="displayData.expiry"
  :bankKey="card.detected_bank_key"
  :bankName="bankInfo.bank_name"
  :bankLogo="bankInfo.bank_logo"
  :primaryNetwork="card.detected_network"
  :cardType="card.detected_type"
  :cardLevel="card.detected_level"
  currency="SAR"
/>
```

Props:

- `panDisplay`: Formatted card number (pre-masked/unmasked)
- `cvvDisplay`: CVV (only if explicitly shown)
- `holder`: Cardholder name
- `expiry`: MM/YY format
- `bankKey`: Uniquely identifies the bank (for styling)
- `bankName`: Arabic bank name
- `bankLogo`: URL to bank logo image
- `primaryNetwork`: Card network (visa, mastercard, mada, amex)
- `cardType`: debit, credit, prepaid
- `cardLevel`: standard, gold, platinum, etc.

### BankCard3D.vue

**File:** `resources/js/dashboard/components/BankCard3D.vue`

Interactive 3D card display with flip animation:

```vue
<BankCard3D
  :cardNumber="displayData.card_number"
  :holderName="card.holder_name"
  :expiry="displayData.expiry"
  :bankName="bankInfo.bank_name_ar"
  :bankNameArabic="bankInfo.bank_name"
  :scheme="card.detected_network"
  :cardType="card.detected_type"
  :cardLevel="card.detected_level"
  :status="card.status"
  :cvv="displayData.cvv"
/>
```

Features:

- Flip on CVV focus
- Show/hide animation
- Bank-specific branding
- Responsive sizing
- Accessible keyboard navigation

### useCardBranding.js

**File:** `resources/js/dashboard/composables/useCardBranding.js`

Vue composable for bank detection and logo resolution:

```js
import { useCardBranding } from '@/dashboard/composables/useCardBranding';

const { 
  bankInfo,        // Resolved bank data
  brandClass,      // CSS class for styling
  getBankLogo,     // Function to get bank logo
  getBrandColor,   // Function to get brand color
  getCardTypeIcon, // Function to get card type icon
  networks         // Detected networks
} = useCardBranding(cardNumber);

// Returns:
{
  bankInfo: {
    key: 'rajhi',
    name_ar: 'البنك الراجحي',
    name_en: 'Al Rajhi Bank',
    logo: '/images/banks/bank_rajhi.png',
    color: '#003b71'
  },
  brandClass: 'brand-rajhi',
  ...
}
```

---

## 8. API Endpoints

### Admin: Card Detail with BIN Info

**Endpoint:** `GET /api/admin/payment-cards/{id}`

Returns:

```json
{
  "id": 123,
  "card_number": "4086 0118 1234 5678",
  "card_number_masked": "•••• •••• •••• 5678",
  "card_number_partial": "408601•• •••• 5678",
  "last4": "5678",
  "holder_name": "Ahmed Al Rajhi",
  "expiry": "12/25",
  "cvv": null,
  
  "bin_6": "408601",
  "bin_8": "40860118",
  
  "detected_bank_key": "rajhi",
  "detected_bank_name_ar": "البنك الراجحي",
  "detected_bank_name_en": "Al Rajhi Bank",
  "detected_bank_logo": "/images/banks/bank_rajhi.png",
  
  "detected_network": "visa",
  "detected_secondary_network": null,
  "detected_type": "debit",
  "detected_level": "platinum",
  "detected_product": "Tahweel",
  
  "detection_confidence": 0.95,
  "detection_match_type": "exact-8digit",
  
  "currency": "SAR",
  "status": "approved",
  "created_at": "2026-01-15T10:30:00Z"
}
```

### Admin: BIN Lookup (for validation/verification)

**Endpoint:** `POST /api/admin/bin/lookup`

Request:

```json
{
  "card_number": "4086011812345678"
}
```

Response:

```json
{
  "success": true,
  "data": {
    "bin_6": "408601",
    "bin_8": "40860118",
    "last4": "5678",
    "bank_key": "rajhi",
    "bank_name_ar": "البنك الراجحي",
    "bank_name_en": "Al Rajhi Bank",
    "bank_logo": "/images/banks/bank_rajhi.png",
    "network": "visa",
    "secondary_network": null,
    "card_type": "debit",
    "card_level": "platinum",
    "product_name": "Tahweel",
    "currency": "SAR",
    "is_valid_luhn": true,
    "confidence": 0.95,
    "match_type": "exact-8digit"
  }
}
```

### Admin: Export with Card Details

**Endpoint:** `GET /api/admin/payment-cards/export`

**Query Parameters:**

- `format`: html | pdf
- `display_mode`: masked | partial | unmasked (admin only)
- `include_cvv`: false | true (admin only, logged)

Response:

- HTML table with cards
- PDF report (via Browsershot)
- All cards masked by default unless unmasked mode requested

---

## 9. Configuration

### config/card_display.php

**Display Mode:**

```php
'mode' => env('CARD_DISPLAY_MODE', 'masked'),  // masked | partial | unmasked
```

**CVV Visibility:**

```php
'show_cvv' => env('CARD_DISPLAY_CVV', false),  // Only in real-time admin views
```

**Expiry Display:**

```php
'show_expiry' => env('CARD_DISPLAY_EXPIRY', 'visible'),  // hidden | masked | visible
```

**Context-Specific Settings:**

```php
'formats' => [
    'dashboard' => ['mode' => 'partial', 'show_cvv' => false],
    'export' => ['mode' => 'masked', 'show_cvv' => false],
    'admin_details' => ['mode' => 'unmasked', 'show_cvv' => false],  // CVV separate
]
```

**Audit Logging:**

```php
'audit' => [
    'log_unmasked_access' => true,
    'require_auth' => true,
    'require_permission' => 'admin',
    'daily_limit' => null,  // Cards per day
]
```

### config/bank_bins.php

**BIN Mappings:**

```php
'bins' => [
    'rajhi' => [
        'name_ar' => 'البنك الراجحي',
        'name_en' => 'Al Rajhi Bank',
        'logo_path' => '/images/banks/bank_rajhi.png',
        'brand_color' => '#003b71',
        'ranges' => [
            ['408601', '408601', 'visa', 'debit', 'standard', 'Tahweel'],
            ['458618', '458618', 'visa', 'credit', 'platinum', 'Rewards Plus'],
            // ...
        ]
    ],
    // ... other banks
]
```

---

## 10. Troubleshooting

### BIN Not Recognized

**Problem:** Card resolves to "Unknown Bank"

**Diagnosis:**

```php
$resolver = app(CardBinResolver::class);
$result = $resolver->resolve('4086011812345678');

logger()->debug('BIN Resolution', [
    'bin_6' => $result->bin6,
    'bin_8' => $result->bin8,
    'bank' => $result->bankKey,
    'confidence' => $result->confidence,
    'match_type' => $result->matchType,
]);
```

**Solutions:**

1. Add BIN range to `card_bin_ranges` table
2. Verify BIN range has corresponding `issuer_bank_id`
3. Check `issuer_banks` table has the bank record
4. Clear BIN cache: `php artisan cache:forget bin:*`

### Card Shows Wrong Bank

**Problem:** Card is Al Rajhi but showing as Ahli

**Diagnosis:**

```sql
-- Check what BIN range matched
SELECT * FROM card_bin_ranges WHERE bin_start = '408601';

-- Verify the issuer_bank
SELECT * FROM issuer_banks WHERE id = <issuer_bank_id>;
```

**Solutions:**

1. Verify BIN entry has correct `issuer_bank_id`
2. Check for duplicate/conflicting BIN entries
3. Ensure BIN range is active (not soft-deleted)
4. Refresh cache: `php artisan db:seed CardBinSeeder`

### Display Mode Not Working

**Problem:** Card always shows masked, unmasked mode not working

**Diagnosis:**

```php
echo config('card_display.mode');  // Should show current mode
echo config('card_display.audit.require_permission');  // Should show required permission
auth()->user()->can('admin');  // Check permission
```

**Solutions:**

1. Verify `CARD_DISPLAY_MODE` env var is set
2. Check user has 'admin' permission/role
3. Verify `require_auth` is true and user is logged in
4. Clear config cache: `php artisan config:clear`

### CVV Not Showing

**Problem:** CVV should show but displays null

**Diagnosis:**

```php
echo config('card_display.show_cvv');  // Should be true
echo config('card_display.audit.log_unmasked_access');  // Should be true

// Check if card has CVV in database
$card = PaymentCard::find(123);
echo $card->cvv_encrypted;  // Should have value
```

**Solutions:**

1. Set `CARD_DISPLAY_CVV=true` in .env (real-time views only)
2. Ensure card has CVV stored (`cvv_encrypted` not null)
3. Verify context is 'admin_details' or similar
4. Check audit logging is enabled
5. Restart/reload application to clear config cache

### Export Always Masked

**Problem:** Export shows masked cards even when requesting unmasked

**Diagnosis:**

```php
// Check export endpoint
GET /api/admin/payment-cards/export?display_mode=unmasked

// Verify request permission
dd(auth()->user()->can('admin'));
```

**Solutions:**

1. Ensure user is admin
2. Export always uses 'export' context (which defaults to masked)
3. To export unmasked: use dedicated endpoint or change context
4. Alternative: Admin downloads unmasked in dashboard, then saves/prints

---

## Summary Table: BIN Data at Each Stage

| Stage | BIN-6 | Bank Key | Network | Confidence | Source |
| ------- | ------- | ---------- | --------- | ------------ | -------- |
| **Card Entry** | N/A | N/A | N/A | — | User input |
| **Luhn Check** | Extracted | N/A | N/A | 0% | Format validation |
| **BIN Lookup** | 408601 | rajhi | visa | 95% | card_bin_ranges |
| **Bank Data** | 408601 | rajhi | visa | 95% | issuer_banks join |
| **Storage** | 408601 | rajhi | visa | 0.95 | payment_cards |
| **Display** | 408601 | rajhi | visa | 0.95 | Front-end component |

---

## References

- **Luhn Algorithm:** ISO/IEC 7064
- **Card Format:** ISO/IEC 7810 ID-1
- **BIN Database:** IBAN Registry, Payment Cards Data
- **Card Networks:** Visa, Mastercard, Mada, AMEX specifications
- **PCI-DSS:** Payment Card Industry Data Security Standard
