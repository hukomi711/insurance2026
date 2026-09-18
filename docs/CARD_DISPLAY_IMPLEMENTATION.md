# Card Display & BIN Matching Implementation Guide

Complete walkthrough for integrating unmasked card display with BIN-based card matching.

## Quick Start

### 1. Enable Unmasked Card Display

Set environment variables:

```bash
# .env.production
CARD_DISPLAY_MODE=unmasked              # masked | partial | unmasked
CARD_DISPLAY_CVV=false                  # Only for real-time dashboard (never export)
CARD_DISPLAY_EXPIRY=visible             # hidden | masked | visible
CARD_REQUIRE_AUTH_FOR_UNMASKED=true     # Require login for unmasked view
CARD_REQUIRE_PERMISSION=admin            # Role/permission required
CARD_AUDIT_LOG_UNMASKED=true            # Log all unmasked access
CARD_DISPLAY_DAILY_LIMIT=null           # Max cards per day (null = no limit)
```

### 2. Register Routes

Add to `routes/api.php`:

```php
// Admin card management
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Card detail with BIN and display formatting
    Route::get('/admin/payment-cards/{card}', [
        AdminCardDetailController::class, 'show'
    ])->name('admin.cards.show');

    // Display configuration (what modes are available for this user)
    Route::get('/admin/payment-cards/{card}/display-config', [
        AdminCardDetailController::class, 'getDisplayConfig'
    ])->name('admin.cards.display-config');

    // BIN lookup during card entry
    Route::post('/admin/card-bin-lookup', [
        AdminCardDetailController::class, 'binLookup'
    ])->name('admin.card-bin.lookup');

    // Export unmasked (requires 'export_cards_unmasked' permission)
    Route::get('/admin/cards/export-unmasked', [
        AdminCardDetailController::class, 'exportUnmasked'
    ])->name('admin.cards.export-unmasked');
});
```

### 3. Update Dashboard Component

**File:** `resources/js/dashboard/pages/PaymentCardsAdmin.vue`

```vue
<template>
  <div class="payment-cards-admin">
    <!-- Card Display with Formatting Options -->
    <div class="card-detail" v-if="selectedCard">
      <div class="display-mode-selector">
        <button
          v-for="mode in availableModes"
          :key="mode"
          @click="displayMode = mode"
          :class="{ active: displayMode === mode }"
        >
          {{ formatMode(mode) }}
        </button>
      </div>

      <!-- Card Visual -->
      <BankCard3D
        :cardNumber="formattedCard.card_number"
        :holderName="formattedCard.holder_name"
        :expiry="formattedCard.expiry"
        :bankName="bankInfo.bank_name_ar"
        :scheme="formattedCard.detected_network"
        :cvv="formattedCard.cvv"
        @flip="showingCvv = !showingCvv"
      />

      <!-- Card Details -->
      <div class="card-details-panel">
        <div class="detail-row">
          <label>Card Number</label>
          <code>{{ formattedCard.card_number }}</code>
          <button @click="copyToClipboard(formattedCard.card_number)">Copy</button>
        </div>

        <div class="detail-row">
          <label>BIN (6 digits)</label>
          <code>{{ cardData.bin.bin_6 }}</code>
        </div>

        <div class="detail-row">
          <label>Bank</label>
          <span>{{ bankInfo.bank_name_en }} ({{ bankInfo.bank_key }})</span>
        </div>

        <div class="detail-row">
          <label>Network</label>
          <span>{{ cardData.networks.primary | uppercase }}</span>
        </div>

        <div class="detail-row">
          <label>Type</label>
          <span>{{ cardData.card.type | capitalize }} {{ cardData.card.level }}</span>
        </div>

        <div class="detail-row" v-if="displayMode === 'unmasked'">
          <label>Expiry</label>
          <code>{{ formattedCard.expiry }}</code>
        </div>

        <div class="detail-row" v-if="canShowCvv && displayMode === 'unmasked'">
          <label>CVV</label>
          <code>{{ formattedCard.cvv || 'Not shown' }}</code>
          <em class="warning">⚠ Never screenshot or share this!</em>
        </div>

        <div class="detail-row">
          <label>Confidence</label>
          <span>{{ (cardData.bin.confidence * 100).toFixed(0) }}% ({{ cardData.bin.match_type }})</span>
        </div>

        <div class="detail-row">
          <label>Status</label>
          <badge :status="cardData.status" />
        </div>
      </div>

      <!-- Audit Notice -->
      <div v-if="displayMode === 'unmasked'" class="audit-notice">
        ⚠️ All unmasked card views are logged for audit compliance.
        Your access is recorded with IP, timestamp, and user ID.
      </div>
    </div>

    <!-- Card List -->
    <div class="card-list">
      <table>
        <thead>
          <tr>
            <th>Last 4</th>
            <th>Holder</th>
            <th>Bank</th>
            <th>Network</th>
            <th>Type</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="card in cards" :key="card.id">
            <td><code>{{ card.last4 }}</code></td>
            <td>{{ card.holder_name }}</td>
            <td>{{ card.detected_bank_key }}</td>
            <td>{{ card.detected_network | uppercase }}</td>
            <td>{{ card.detected_type | capitalize }}</td>
            <td><badge :status="card.status" /></td>
            <td>
              <button @click="selectCard(card)">View</button>
              <button @click="exportCard(card)" v-if="canExport">Export</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';
import BankCard3D from '@/dashboard/components/BankCard3D.vue';
import Badge from '@/components/Badge.vue';

const { get, post } = useApi();

const cards = ref([]);
const selectedCard = ref(null);
const cardData = ref(null);
const displayMode = ref('partial');
const showingCvv = ref(false);
const formattedCard = ref({});
const bankInfo = ref({});
const canShowCvv = ref(false);
const availableModes = ref([]);
const canExport = ref(false);

const formatMode = (mode) => ({
  masked: '•••• Masked',
  partial: '411111•• Partial',
  unmasked: 'Full Number (Admin Only)',
}[mode]);

const selectCard = async (card) => {
  selectedCard.value = card;
  
  try {
    // Fetch card details with display formatting
    const response = await get(
      `/admin/payment-cards/${card.id}?display=${displayMode.value}`
    );
    
    cardData.value = response.data;
    formattedCard.value = response.data.display;
    bankInfo.value = response.data.bank;
    
    // Fetch available display modes for this user
    const configResponse = await get(
      `/admin/payment-cards/${card.id}/display-config`
    );
    
    availableModes.value = configResponse.data.available_modes.filter(m => m);
    canShowCvv.value = configResponse.data.can_show_cvv;
    canExport.value = !!configResponse.data.can_show_unmasked;
    
  } catch (error) {
    console.error('Failed to load card details:', error);
  }
};

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text);
  // Show toast notification
};

const exportCard = async (card) => {
  try {
    const response = await get(
      `/admin/cards/export-unmasked?limit=1&skip=0`
    );
    
    // Download as JSON/CSV
    const dataStr = JSON.stringify(response.data, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `card-${card.id}-unmasked-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
  } catch (error) {
    console.error('Failed to export card:', error);
  }
};

onMounted(async () => {
  try {
    // Load card list
    const response = await get('/admin/payment-cards');
    cards.value = response.data;
  } catch (error) {
    console.error('Failed to load cards:', error);
  }
});
</script>

<style scoped>
.payment-cards-admin {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.card-detail {
  border: 1px solid #ddd;
  border-radius: 12px;
  padding: 2rem;
  background: #f9f9f9;
}

.display-mode-selector {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
}

.display-mode-selector button {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  font-weight: 500;
}

.display-mode-selector button.active {
  background: #007bff;
  color: white;
  border-color: #0056b3;
}

.card-details-panel {
  margin-top: 2rem;
  border-top: 1px solid #ddd;
  padding-top: 2rem;
}

.detail-row {
  display: grid;
  grid-template-columns: 150px 1fr auto;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.detail-row label {
  font-weight: 600;
  color: #666;
}

.detail-row code {
  font-family: 'Courier New', monospace;
  background: #f0f0f0;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  letter-spacing: 0.05em;
}

.audit-notice {
  margin-top: 2rem;
  padding: 1rem;
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 6px;
  color: #856404;
  font-size: 0.9rem;
}

.card-list {
  min-height: 400px;
}

.card-list table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.card-list th {
  background: #f0f0f0;
  padding: 0.75rem;
  text-align: left;
  border-bottom: 2px solid #ddd;
  font-weight: 600;
}

.card-list td {
  padding: 0.75rem;
  border-bottom: 1px solid #ddd;
}

.card-list code {
  font-family: 'Courier New', monospace;
  background: #f0f0f0;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.card-list button {
  padding: 0.25rem 0.5rem;
  margin-right: 0.25rem;
  font-size: 0.85rem;
}
</style>
```

---

## Implementation Checklist

### Phase 1: Core Infrastructure

- [ ] Add `config/card_display.php` configuration
- [ ] Create `CardDisplayService` with formatting logic
- [ ] Create `AdminCardDetailController` with endpoints
- [ ] Register API routes for admin card management
- [ ] Add database columns to `payment_cards` table:
  - `bin_6`, `bin_8`
  - `detected_bank_key`, `detected_network`, `detected_secondary_network`
  - `detected_type`, `detected_level`
  - `detection_confidence`, `detection_match_type`

### Phase 2: Database Setup

- [ ] Run migration to add BIN columns to `payment_cards`:

```bash
php artisan make:migration add_bin_data_to_payment_cards
```

```php
// migration
Schema::table('payment_cards', function (Blueprint $table) {
    $table->string('bin_6')->nullable()->after('last4');
    $table->string('bin_8')->nullable()->after('bin_6');
    $table->string('detected_bank_key')->nullable()->after('bin_8');
    $table->string('detected_network')->nullable()->after('detected_bank_key');
    $table->string('detected_secondary_network')->nullable()->after('detected_network');
    $table->string('detected_type')->nullable()->after('detected_secondary_network');
    $table->string('detected_level')->nullable()->after('detected_type');
    $table->decimal('detection_confidence', 3, 2)->nullable()->after('detected_level');
    $table->string('detection_match_type')->nullable()->after('detection_confidence');
    
    // Indexes for faster lookup
    $table->index('bin_6');
    $table->index('bin_8');
    $table->index('detected_bank_key');
    $table->index('detected_network');
});
```

- [ ] Populate existing cards with BIN data:

```php
// artisan command
php artisan cards:resolve-bins
```

```php
// Command: app/Console/Commands/ResolveBins.php
class ResolveBins extends Command {
    public function handle() {
        $resolver = app(CardBinResolver::class);
        
        PaymentCard::whereNull('bin_6')
            ->orWhereNull('detected_bank_key')
            ->chunk(100, function ($cards) use ($resolver) {
                foreach ($cards as $card) {
                    $bin = $resolver->resolve($card->card_number);
                    
                    $card->update([
                        'bin_6' => $bin->bin6,
                        'bin_8' => $bin->bin8,
                        'detected_bank_key' => $bin->bankKey,
                        'detected_network' => $bin->network,
                        'detected_secondary_network' => $bin->secondaryNetwork,
                        'detected_type' => $bin->cardType,
                        'detected_level' => $bin->cardLevel,
                        'detection_confidence' => $bin->confidence,
                        'detection_match_type' => $bin->matchType,
                    ]);
                }
            });
        
        $this->info('BIN resolution complete');
    }
}
```

### Phase 3: Frontend Integration

- [ ] Update `PaymentCardsAdmin.vue` dashboard component
- [ ] Add `useCardBranding.js` composable for bank detection
- [ ] Update `BankCard3D.vue` for card display
- [ ] Update `PaymentCardVisual.vue` with display modes
- [ ] Add display mode toggle buttons to admin dashboard

### Phase 4: Security & Audit

- [ ] Implement audit logging for unmasked access:

```php
// app/Models/CardAuditLog.php
Schema::create('card_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('card_id');
    $table->unsignedBigInteger('user_id');
    $table->string('action'); // view, export, print
    $table->string('display_mode')->nullable(); // masked, unmasked, partial
    $table->ipAddress('ip_address');
    $table->text('user_agent')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->foreign('card_id')->references('id')->on('payment_cards');
    $table->foreign('user_id')->references('id')->on('users');
    $table->index(['card_id', 'created_at']);
    $table->index(['user_id', 'created_at']);
});
```

- [ ] Add permission: `export_cards_unmasked`
- [ ] Restrict unmasked display to authenticated admins only
- [ ] Log all unmasked access with user, IP, timestamp, action

### Phase 5: Testing

- [ ] Test BIN resolution for each supported bank:

  ```bash
  php artisan tinker
  
  $resolver = app(CardBinResolver::class);
  $resolver->resolve('4086011812345678'); // Al Rajhi
  $resolver->resolve('4111111111111111'); // Test Visa
  ```

- [ ] Test card display modes:

  ```php
  $display = app(CardDisplayService::class);
  $display->formatCardNumber('4111111111111111', 'masked');
  $display->formatCardNumber('4111111111111111', 'partial');
  $display->formatCardNumber('4111111111111111', 'unmasked');
  ```

- [ ] Test API endpoints:

  ```bash
  curl -X GET http://localhost/api/admin/payment-cards/1?display=unmasked \
    -H "Authorization: Bearer <token>"
  
  curl -X POST http://localhost/api/admin/card-bin-lookup \
    -H "Content-Type: application/json" \
    -d '{"card_number": "4086011812345678"}'
  ```

- [ ] Test audit logging:

  ```php
  $display = app(CardDisplayService::class);
  $display->auditUnmaskedAccess(
      cardId: 1,
      userId: auth()->id(),
      action: 'view-detail'
  );
  
  // Check card_audit_logs table
  ```

---

## Configuration Examples

### Masked Only (Default/Safe)

```env
CARD_DISPLAY_MODE=masked
CARD_DISPLAY_CVV=false
CARD_DISPLAY_EXPIRY=visible
```

Result: `•••• •••• •••• 5678`

### Partial for Admin Dashboard

```env
CARD_DISPLAY_MODE=partial
CARD_DISPLAY_EXPIRY=visible
```

Result: `408601•• •••• 5678`

### Unmasked for Authorized Admins Only

```env
CARD_DISPLAY_MODE=unmasked
CARD_DISPLAY_CVV=false
CARD_REQUIRE_AUTH_FOR_UNMASKED=true
CARD_REQUIRE_PERMISSION=admin
CARD_AUDIT_LOG_UNMASKED=true
```

Result: `4086 0118 1234 5678` (logged)

### With CVV Display (Rare)

```env
CARD_DISPLAY_MODE=unmasked
CARD_DISPLAY_CVV=true
CARD_AUDIT_LOG_UNMASKED=true
```

Result: Full card + CVV (heavily logged)

---

## Usage Examples

### In Controller

```php
use App\Services\Bin\CardBinResolver;
use App\Services\CardDisplay\CardDisplayService;

class PaymentController extends Controller {
    public function processPayment(Request $request) {
        $resolver = app(CardBinResolver::class);
        $display = app(CardDisplayService::class);
        
        // Resolve BIN
        $bin = $resolver->resolve($request->card_number);
        
        // Log bank detection
        logger()->info('Card received', [
            'bank' => $bin->bankKey,
            'network' => $bin->network,
            'type' => $bin->cardType,
            'confidence' => $bin->confidence,
        ]);
        
        // Format for display
        $formatted = $display->formatForContext(
            cardNumber: $request->card_number,
            context: 'dashboard'
        );
        
        // Store card with BIN data
        $card = PaymentCard::create([
            'card_number' => $request->card_number,
            'last4' => $bin->last4,
            'bin_6' => $bin->bin6,
            'bin_8' => $bin->bin8,
            'detected_bank_key' => $bin->bankKey,
            'detected_network' => $bin->network,
            'detected_type' => $bin->cardType,
            'detection_confidence' => $bin->confidence,
            'detection_match_type' => $bin->matchType,
        ]);
        
        return response()->json([
            'card_display' => $formatted['card_number_masked'],
            'bank' => $bin->bankKey,
            'network' => $bin->network,
        ]);
    }
}
```

### In Blade Template

```blade
<!-- Show masked card (safe for all contexts) -->
<div class="card-display">
    <strong>Card:</strong> {{ $card->card_number_masked }}
</div>

<!-- Show partial (admin dashboard) -->
@auth
@if(auth()->user()->hasRole('admin'))
<div class="card-detail">
    <code>{{ $card->bin_6 }}•• •••• {{ $card->last4 }}</code>
    <img src="{{ $card->bankLogo }}" alt="{{ $card->bankNameEn }}">
</div>
@endif
@endauth
```

### In Vue Component

```vue
<template>
  <div class="payment-card">
    <!-- Card Visual -->
    <BankCard3D
      :cardNumber="formattedCard.card_number"
      :bankName="cardData.bank.name_en"
      :network="cardData.networks.primary"
    />
    
    <!-- Info -->
    <div class="card-info">
      <p>Last 4: <code>{{ formattedCard.last4 }}</code></p>
      <p>Bank: {{ cardData.bank.name_ar }}</p>
      <p>Type: {{ cardData.card.type }} {{ cardData.card.level }}</p>
      <p v-if="displayMode === 'unmasked'">
        ⚠️ Full number displayed (audit logged)
      </p>
    </div>
  </div>
</template>

<script setup>
const formattedCard = reactive({
  card_number: '•••• •••• •••• 5678',
  last4: '5678',
});

const cardData = reactive({
  bank: { name_ar: 'البنك الراجحي', name_en: 'Al Rajhi Bank' },
  card: { type: 'debit', level: 'platinum' },
  networks: { primary: 'visa' },
});
</script>
```

---

## Troubleshooting

### Cards Not Showing Bank Info

**Problem:** Cards display "Unknown Bank"

**Diagnosis:**

```bash
php artisan tinker

PaymentCard::find(1)->only(['bin_6', 'detected_bank_key']);
# Should show 'rajhi', 'ahli', etc. If null, BIN resolution didn't run
```

**Fix:**

```bash
php artisan cards:resolve-bins
```

### Unmasked Mode Not Working

**Problem:** Still shows masked card even with `CARD_DISPLAY_MODE=unmasked`

**Diagnosis:**

```php
config('card_display.mode'); // Should be 'unmasked'
auth()->check(); // Should be true
auth()->user()->hasPermissionTo('admin'); // Should be true
```

**Fix:**

1. Clear config: `php artisan config:clear`
2. Verify user role/permission: `auth()->user()->roles`
3. Check env file updated and deployed

### Audit Logs Not Saving

**Problem:** Unmasked access not logged

**Diagnosis:**

```php
config('card_display.audit.log_unmasked_access'); // Should be true

// Check if migration ran
Schema::hasTable('card_audit_logs'); // Should be true
```

**Fix:**

```bash
php artisan migrate
```

---

## Security Checklist

- [x] Unmasked display requires authentication
- [x] Unmasked display requires admin permission
- [x] All unmasked access logged with user/IP/timestamp
- [x] CVV hidden by default (never in exports)
- [x] Encrypted at rest (existing EncryptedSafe cast)
- [x] HTTPS only (existing requirement)
- [x] Rate limiting on card endpoints (implement if needed)
- [x] Audit trail immutable (log to secure table)
- [x] No card data in logs/errors (use last4 only)

---

## Next Steps

1. Create database migration for BIN columns
2. Implement `CardDisplayService` and `CardBinResolver` (already done above)
3. Create controller endpoints (already done above)
4. Register routes in `routes/api.php`
5. Update admin dashboard Vue components
6. Run `php artisan cards:resolve-bins` for existing cards
7. Test all display modes and audit logging
8. Deploy to production with audit monitoring
