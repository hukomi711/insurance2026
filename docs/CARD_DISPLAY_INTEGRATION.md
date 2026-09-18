# Card Display System: Integration Checklist & Setup

Complete integration steps to wire CardDisplayService, CardBinResolver, and admin endpoints into the insurance2026 application.

## File Locations Reference

| Component | Path | Type | Status |
| ----------- | ------ | ------ | -------- |
| Config | `config/card_display.php` | Configuration | ✅ Created |
| Service | `app/Services/CardDisplay/CardDisplayService.php` | Service | ✅ Created |
| Controller | `app/Http/Controllers/Admin/AdminCardDetailController.php` | Controller | ✅ Created |
| Docs: BIN System | `docs/BIN_MATCHING_AND_CARD_DISPLAY.md` | Documentation | ✅ Created |
| Docs: Implementation | `docs/CARD_DISPLAY_IMPLEMENTATION.md` | Documentation | ✅ Created |
| Existing: Model | `app/Models/PaymentCard.php` | Model | ✅ Already exists |
| Existing: Resolver | `app/Services/Bin/CardBinResolver.php` | Service | ✅ Already exists |
| Existing: Components | `resources/js/dashboard/components/{PaymentCardVisual,BankCard3D}.vue` | Vue | ✅ Already exists |

---

## Step 1: Register Service Provider

### Update AppServiceProvider

**File:** `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Services\Bin\CardBinResolver;
use App\Services\CardDisplay\CardDisplayService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register CardDisplayService as singleton
        $this->app->singleton(CardDisplayService::class, function ($app) {
            return new CardDisplayService();
        });

        // Register CardBinResolver as singleton (with caching)
        $this->app->singleton(CardBinResolver::class, function ($app) {
            return new CardBinResolver(
                resolver: $app->make(/* CardBinResolver dependency */),
                cache: $app->make('cache.store')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
```

---

## Step 2: Register API Routes

### Update routes/api.php

```php
<?php

use App\Http\Controllers\Admin\AdminCardDetailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api'])->group(function () {
    
    // ============================================
    // Admin Card Management Routes
    // ============================================
    
    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
        
        // ---- Card Detail & Display ----
        
        /**
         * GET /api/admin/payment-cards/{card}
         * Get complete card details with BIN info and formatted display
         * 
         * Query Parameters:
         *   - display: masked | partial | unmasked (default: masked)
         *   - show_cvv: boolean (default: false, admin only)
         *   - context: dashboard | export | admin_details (default: dashboard)
         */
        Route::get('/payment-cards/{card}', [
            AdminCardDetailController::class, 'show'
        ])->name('admin.cards.show');
        
        /**
         * GET /api/admin/payment-cards/{card}/display-config
         * Get display configuration for current user
         * Returns: available_modes, can_show_unmasked, can_show_cvv
         */
        Route::get('/payment-cards/{card}/display-config', [
            AdminCardDetailController::class, 'getDisplayConfig'
        ])->name('admin.cards.display-config');
        
        // ---- BIN Lookup & Validation ----
        
        /**
         * POST /api/admin/card-bin-lookup
         * Lookup BIN without accessing stored card
         * (Useful during card entry validation)
         * 
         * Body: { "card_number": "4111111111111111" }
         */
        Route::post('/card-bin-lookup', [
            AdminCardDetailController::class, 'binLookup'
        ])->name('admin.card-bin.lookup');
        
        // ---- Export Endpoints ----
        
        /**
         * GET /api/admin/cards/export-unmasked
         * Export unmasked card list (requires 'export_cards_unmasked' permission)
         * 
         * Query Parameters:
         *   - limit: max 100 (default: 10)
         *   - skip: offset (default: 0)
         * 
         * Response: JSON array with full card numbers (logged!)
         */
        Route::get('/cards/export-unmasked', [
            AdminCardDetailController::class, 'exportUnmasked'
        ])->name('admin.cards.export-unmasked');
    });
});
```

### Optional: Create Routes File for Clarity

**File:** `routes/admin-cards.php`

```php
<?php

use App\Http\Controllers\Admin\AdminCardDetailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    
    // Card detail
    Route::get('payment-cards/{card}', [AdminCardDetailController::class, 'show'])
        ->name('admin.cards.show');
    
    Route::get('payment-cards/{card}/display-config', [AdminCardDetailController::class, 'getDisplayConfig'])
        ->name('admin.cards.display-config');
    
    // BIN lookup
    Route::post('card-bin-lookup', [AdminCardDetailController::class, 'binLookup'])
        ->name('admin.card-bin.lookup');
    
    // Export
    Route::get('cards/export-unmasked', [AdminCardDetailController::class, 'exportUnmasked'])
        ->name('admin.cards.export-unmasked');
});
```

Then load in `routes/api.php`:

```php
require_once base_path('routes/admin-cards.php');
```

---

## Step 3: Add Environment Variables

### Update .env

```env
# Card Display Configuration
CARD_DISPLAY_MODE=masked                    # masked | partial | unmasked
CARD_DISPLAY_CVV=false                      # Show CVV in real-time views
CARD_DISPLAY_EXPIRY=visible                 # hidden | masked | visible

# Security
CARD_REQUIRE_AUTH_FOR_UNMASKED=true         # Require login for unmasked
CARD_REQUIRE_PERMISSION=admin               # Required role/permission
CARD_AUDIT_LOG_UNMASKED=true                # Log unmasked access
CARD_DISPLAY_DAILY_LIMIT=null               # Max views per user per day (null = unlimited)
```

### Update .env.production

```env
# Production: Always require auth/permission for unmasked
CARD_DISPLAY_MODE=masked
CARD_DISPLAY_CVE=false
CARD_REQUIRE_AUTH_FOR_UNMASKED=true
CARD_REQUIRE_PERMISSION=admin
CARD_AUDIT_LOG_UNMASKED=true
CARD_DISPLAY_DAILY_LIMIT=50
```

---

## Step 4: Run Database Migrations

### Create Migration

```bash
php artisan make:migration add_bin_data_to_payment_cards --table=payment_cards
```

**File:** `database/migrations/XXXX_XX_XX_XXXXXX_add_bin_data_to_payment_cards.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            // BIN Data (from CardBinResolver)
            $table->string('bin_6')->nullable()->after('last4')
                ->comment('First 6 digits of card number');
            $table->string('bin_8')->nullable()->after('bin_6')
                ->comment('First 8 digits of card number');
            
            // Bank Resolution Results
            $table->string('detected_bank_key')->nullable()->after('bin_8')
                ->comment('Bank identifier (rajhi, ahli, etc)');
            $table->string('detected_network')->nullable()->after('detected_bank_key')
                ->comment('Card network (visa, mastercard, mada, amex)');
            $table->string('detected_secondary_network')->nullable()->after('detected_network')
                ->comment('Secondary network if applicable');
            
            // Card Type & Level
            $table->string('detected_type')->nullable()->after('detected_secondary_network')
                ->comment('Card type (debit, credit, prepaid)');
            $table->string('detected_level')->nullable()->after('detected_type')
                ->comment('Card level (standard, gold, platinum)');
            
            // Detection Metadata
            $table->decimal('detection_confidence', 3, 2)->nullable()->after('detected_level')
                ->comment('BIN match confidence (0.0 - 1.0)');
            $table->string('detection_match_type')->nullable()->after('detection_confidence')
                ->comment('Match type (exact-8digit, exact-6digit, network, fallback)');
            
            // Indexes for queries
            $table->index('bin_6');
            $table->index('bin_8');
            $table->index('detected_bank_key');
            $table->index('detected_network');
            $table->index(['detected_bank_key', 'detected_network']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->dropIndex(['bin_6']);
            $table->dropIndex(['bin_8']);
            $table->dropIndex(['detected_bank_key']);
            $table->dropIndex(['detected_network']);
            $table->dropIndex(['detected_bank_key', 'detected_network']);
            
            $table->dropColumn([
                'bin_6',
                'bin_8',
                'detected_bank_key',
                'detected_network',
                'detected_secondary_network',
                'detected_type',
                'detected_level',
                'detection_confidence',
                'detection_match_type',
            ]);
        });
    }
};
```

### Create Audit Logging Table (Optional but Recommended)

```bash
php artisan make:migration create_card_audit_logs_table
```

**File:** `database/migrations/XXXX_XX_XX_XXXXXX_create_card_audit_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Reference
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('user_id');
            
            // Action
            $table->string('action')->comment('view, export, print, etc.');
            $table->string('display_mode')->nullable()->comment('masked, unmasked, partial');
            
            // Context
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->foreign('card_id')->references('id')->on('payment_cards')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['card_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('display_mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_audit_logs');
    }
};
```

### Run Migrations

```bash
php artisan migrate
```

---

## Step 5: Create Artisan Command to Populate BIN Data

### Create Command

```bash
php artisan make:command ResolveBinsForCards
```

**File:** `app/Console/Commands/ResolveBinsForCards.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\PaymentCard;
use App\Services\Bin\CardBinResolver;
use Illuminate\Console\Command;

class ResolveBinsForCards extends Command
{
    protected $signature = 'cards:resolve-bins {--limit=0}';
    protected $description = 'Resolve and cache BIN data for all payment cards';

    public function handle(CardBinResolver $resolver): int
    {
        $query = PaymentCard::query();
        $limit = (int) $this->option('limit');
        
        // Only resolve cards missing BIN data
        $query->where(function ($q) {
            $q->whereNull('bin_6')
              ->orWhereNull('detected_bank_key');
        });

        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();
        
        if ($total === 0) {
            $this->info('No cards need BIN resolution.');
            return 0;
        }

        $this->info("Resolving BIN data for {$total} cards...");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $failed = 0;

        $query->chunk(100, function ($cards) use ($resolver, &$processed, &$failed, $bar) {
            foreach ($cards as $card) {
                try {
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

                    $processed++;
                } catch (\Exception $e) {
                    $this->error("Failed to resolve card {$card->id}: {$e->getMessage()}");
                    $failed++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        
        $this->info("✓ Processed: {$processed} cards");
        $this->warn("✗ Failed: {$failed} cards");
        
        return $failed > 0 ? 1 : 0;
    }
}
```

### Run Command

```bash
# Resolve all cards
php artisan cards:resolve-bins

# Resolve first 100 cards
php artisan cards:resolve-bins --limit=100
```

---

## Step 6: Set Up Permissions (If Using Spatie Laravel-Permission)

### Create Permission Migration

```bash
php artisan make:migration create_card_display_permissions
```

**File:**

```php
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Create permissions
        $permissions = [
            ['name' => 'view_payment_cards', 'guard_name' => 'web'],
            ['name' => 'view_card_details', 'guard_name' => 'web'],
            ['name' => 'view_card_unmasked', 'guard_name' => 'web'],
            ['name' => 'export_cards_masked', 'guard_name' => 'web'],
            ['name' => 'export_cards_unmasked', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate($permission);
        }

        // Assign to admin role
        $admin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo($permissions);
    }

    public function down(): void
    {
        $permissions = [
            'view_payment_cards',
            'view_card_details',
            'view_card_unmasked',
            'export_cards_masked',
            'export_cards_unmasked',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::where('name', $permission)->delete();
        }
    }
};
```

---

## Step 7: Update Admin Dashboard Component

### Create/Update AdminPaymentCardsPage.vue

**File:** `resources/js/dashboard/pages/AdminPaymentCardsPage.vue`

```vue
<template>
  <div class="admin-payment-cards">
    <header>
      <h1>Payment Cards Management</h1>
      <p class="subtitle">View, search, and manage customer payment cards</p>
    </header>

    <!-- Search & Filters -->
    <div class="search-section">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search by card number, bank, or customer..."
        class="search-input"
      />
      <select v-model="filterBank" class="filter-select">
        <option value="">All Banks</option>
        <option v-for="bank in banks" :key="bank" :value="bank">
          {{ bank }}
        </option>
      </select>
    </div>

    <!-- Cards Table -->
    <div class="cards-table">
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
          <tr v-for="card in filteredCards" :key="card.id" class="card-row">
            <td><code>{{ card.last4 }}</code></td>
            <td>{{ card.customer?.full_name || card.holder_name }}</td>
            <td>
              <span class="bank-badge" :style="{ backgroundColor: getBankColor(card.detected_bank_key) }">
                {{ card.detected_bank_key }}
              </span>
            </td>
            <td>{{ card.detected_network?.toUpperCase() }}</td>
            <td>{{ capitalize(card.detected_type) }}</td>
            <td>
              <span class="status-badge" :class="card.status">
                {{ capitalize(card.status) }}
              </span>
            </td>
            <td class="actions">
              <button
                @click="selectCard(card)"
                class="btn-detail"
                title="View full details"
              >
                📋 View
              </button>
              <button
                v-if="canExportUnmasked"
                @click="exportCard(card)"
                class="btn-export"
                title="Export unmasked (logged)"
              >
                📥 Export
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedCard" class="detail-modal" @click.self="selectedCard = null">
      <div class="modal-content">
        <button class="modal-close" @click="selectedCard = null">✕</button>

        <!-- Display Mode Selector -->
        <div class="display-section">
          <h2>Display Mode</h2>
          <div class="mode-buttons">
            <button
              v-for="mode in availableModes"
              :key="mode"
              @click="displayMode = mode"
              :class="{ active: displayMode === mode }"
              class="mode-btn"
            >
              {{ formatMode(mode) }}
            </button>
          </div>
          <p class="mode-warning" v-if="displayMode === 'unmasked'">
            ⚠️ Unmasked display is logged for audit compliance
          </p>
        </div>

        <!-- Card Display -->
        <div class="card-display-section" v-if="cardData">
          <BankCard3D
            :cardNumber="cardData.display.card_number"
            :holderName="cardData.display.holder_name"
            :expiry="cardData.display.expiry"
            :bankName="cardData.bank.name_ar"
            :bankNameEnglish="cardData.bank.name_en"
            :scheme="cardData.networks.primary"
            :cardType="cardData.card.type"
            :cardLevel="cardData.card.level"
            :logoPath="cardData.bank.logo_url"
            :brandColor="cardData.bank.brand_color"
          />
        </div>

        <!-- Card Details -->
        <div class="details-section" v-if="cardData">
          <div class="detail-grid">
            <div class="detail-item">
              <label>Card Number</label>
              <code class="card-number">{{ cardData.display.card_number }}</code>
              <button @click="copyText(cardData.display.card_number)" class="copy-btn">
                Copy
              </button>
            </div>

            <div class="detail-item">
              <label>Holder Name</label>
              <span>{{ cardData.display.holder_name }}</span>
            </div>

            <div class="detail-item">
              <label>BIN (6 digits)</label>
              <code>{{ cardData.bin.bin_6 }}</code>
            </div>

            <div class="detail-item">
              <label>Expiry</label>
              <code>{{ cardData.display.expiry }}</code>
            </div>

            <div class="detail-item">
              <label>Bank</label>
              <span>{{ cardData.bank.name_en }} ({{ cardData.bank.key }})</span>
            </div>

            <div class="detail-item">
              <label>Network</label>
              <span>{{ cardData.networks.primary?.toUpperCase() }}</span>
            </div>

            <div class="detail-item">
              <label>Type</label>
              <span>{{ capitalize(cardData.card.type) }} - {{ cardData.card.level }}</span>
            </div>

            <div class="detail-item">
              <label>Confidence</label>
              <span>{{ (cardData.bin.confidence * 100).toFixed(0) }}% ({{ cardData.bin.match_type }})</span>
            </div>

            <div class="detail-item">
              <label>Status</label>
              <span class="status-badge" :class="cardData.status">
                {{ capitalize(cardData.status) }}
              </span>
            </div>

            <div class="detail-item" v-if="displayMode === 'unmasked' && cardData.display.cvv">
              <label>CVV</label>
              <code>{{ cardData.display.cvv }}</code>
              <span class="warning">Never share or screenshot this value</span>
            </div>
          </div>
        </div>

        <!-- Audit Notice -->
        <div v-if="displayMode === 'unmasked'" class="audit-notice">
          <strong>⚠️ Audit Notice:</strong> Your access to unmasked card data is logged.
          All views are recorded with timestamp, IP address, and user ID for compliance.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';
import BankCard3D from '@/dashboard/components/BankCard3D.vue';

const { get } = useApi();

// State
const cards = ref([]);
const selectedCard = ref(null);
const cardData = ref(null);
const displayMode = ref('partial');
const searchQuery = ref('');
const filterBank = ref('');
const banks = ref([]);
const availableModes = ref([]);
const canExportUnmasked = ref(false);

// Computed
const filteredCards = computed(() => {
  return cards.value.filter(card => {
    const matchesSearch = !searchQuery.value ||
      card.last4.includes(searchQuery.value) ||
      card.holder_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      card.detected_bank_key.toLowerCase().includes(searchQuery.value.toLowerCase());

    const matchesBank = !filterBank.value || card.detected_bank_key === filterBank.value;

    return matchesSearch && matchesBank;
  });
});

// Methods
const formatMode = (mode) => ({
  masked: '🔒 Masked',
  partial: '🔶 Partial',
  unmasked: '👁️ Unmasked (Admin)',
}[mode] || mode);

const capitalize = (str) => str?.charAt(0).toUpperCase() + str?.slice(1) || '';

const getBankColor = (bankKey) => {
  const colors = {
    rajhi: '#003b71',
    ahli: '#c60c30',
    riyad: '#4a90e2',
    // Add more banks as needed
  };
  return colors[bankKey] || '#666';
};

const copyText = async (text) => {
  await navigator.clipboard.writeText(text);
  alert('Copied to clipboard');
};

const selectCard = async (card) => {
  selectedCard.value = card;
  displayMode.value = 'partial';

  try {
    // Load card details
    const response = await get(`/admin/payment-cards/${card.id}?display=${displayMode.value}`);
    cardData.value = response.data;

    // Load available modes for this user
    const configResponse = await get(`/admin/payment-cards/${card.id}/display-config`);
    availableModes.value = configResponse.data.available_modes.filter(m => m);
    canExportUnmasked.value = configResponse.data.can_show_unmasked;
  } catch (error) {
    console.error('Failed to load card:', error);
    alert('Failed to load card details');
  }
};

const exportCard = async (card) => {
  if (!confirm('Export unmasked card data? This action is logged.')) {
    return;
  }

  try {
    const response = await get(`/admin/cards/export-unmasked?limit=1&skip=0`);
    
    // Download as JSON
    const dataStr = JSON.stringify(response.data, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `card-${card.id}-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
  } catch (error) {
    console.error('Export failed:', error);
    alert('Failed to export card');
  }
};

// Lifecycle
onMounted(async () => {
  try {
    // Load cards
    const response = await get('/admin/payment-cards');
    cards.value = response.data;

    // Extract unique banks
    banks.value = [...new Set(cards.value.map(c => c.detected_bank_key).filter(Boolean))];
  } catch (error) {
    console.error('Failed to load cards:', error);
  }
});
</script>

<style scoped>
.admin-payment-cards {
  padding: 2rem;
  background: #f5f5f5;
  min-height: 100vh;
}

header {
  margin-bottom: 2rem;
}

header h1 {
  font-size: 2rem;
  margin: 0;
}

.subtitle {
  color: #666;
  margin: 0.5rem 0 0 0;
}

.search-section {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.search-input,
.filter-select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
}

.search-input {
  flex: 1;
}

.cards-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.cards-table table {
  width: 100%;
  border-collapse: collapse;
}

.cards-table th {
  background: #f9f9f9;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  border-bottom: 2px solid #eee;
  font-size: 0.9rem;
}

.cards-table td {
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

.card-row:hover {
  background: #f9f9f9;
}

code {
  font-family: 'Courier New', monospace;
  background: #f0f0f0;
  padding: 0.2rem 0.4rem;
  border-radius: 3px;
  font-size: 0.9rem;
}

.bank-badge {
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 600;
}

.status-badge.approved {
  background: #d4edda;
  color: #155724;
}

.status-badge.pending {
  background: #fff3cd;
  color: #856404;
}

.status-badge.rejected {
  background: #f8d7da;
  color: #721c24;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.btn-detail,
.btn-export {
  padding: 0.4rem 0.8rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85rem;
  background: white;
  transition: all 0.2s;
}

.btn-detail:hover {
  background: #e3f2fd;
  border-color: #2196f3;
}

.btn-export:hover {
  background: #fff3e0;
  border-color: #ff9800;
}

/* Modal */
.detail-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 800px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  padding: 2rem;
  position: relative;
}

.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
}

.display-section {
  margin-bottom: 2rem;
}

.mode-buttons {
  display: flex;
  gap: 0.5rem;
  margin: 1rem 0;
}

.mode-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
}

.mode-btn.active {
  background: #2196f3;
  color: white;
  border-color: #1976d2;
}

.mode-warning {
  background: #fff3cd;
  border: 1px solid #ffc107;
  padding: 0.75rem;
  border-radius: 4px;
  color: #856404;
  font-size: 0.9rem;
  margin: 1rem 0 0 0;
}

.card-display-section {
  text-align: center;
  margin: 2rem 0;
}

.details-section {
  border-top: 1px solid #eee;
  padding-top: 2rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-item label {
  font-weight: 600;
  color: #666;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.detail-item code {
  font-size: 1rem;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  word-break: break-all;
}

.copy-btn {
  align-self: flex-start;
  padding: 0.25rem 0.5rem;
  font-size: 0.85rem;
  background: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 3px;
  cursor: pointer;
}

.warning {
  font-size: 0.8rem;
  color: #d32f2f;
  font-style: italic;
}

.audit-notice {
  background: #fff3cd;
  border: 1px solid #ffc107;
  padding: 1rem;
  border-radius: 6px;
  color: #856404;
  margin-top: 2rem;
  font-size: 0.9rem;
}
</style>
```

---

## Step 8: Test Everything

### Test Routes

```bash
# Verify routes are registered
php artisan route:list | grep admin/payment-cards
php artisan route:list | grep card-bin

# Expected output:
# GET     admin/payment-cards/{card}
# GET     admin/payment-cards/{card}/display-config
# POST    admin/card-bin-lookup
# GET     admin/cards/export-unmasked
```

### Test API Endpoints

```bash
# Get card detail
curl -X GET "http://localhost/api/admin/payment-cards/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Lookup BIN
curl -X POST "http://localhost/api/admin/card-bin-lookup" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"card_number": "4086011812345678"}'

# Export unmasked
curl -X GET "http://localhost/api/admin/cards/export-unmasked?limit=5" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Test Permissions

```php
php artisan tinker

// Check user permissions
$user = User::find(1);
$user->can('view_card_details'); // Should be true
$user->can('export_cards_unmasked'); // Should be true/false based on role

// Check card policy
Gate::allows('view', $card); // Should be true
```

---

## Deployment Checklist

- [ ] Create and run database migrations
- [ ] Run `php artisan cards:resolve-bins` to populate BIN data
- [ ] Update `.env.production` with correct display settings
- [ ] Create/update admin dashboard Vue component
- [ ] Test all API endpoints
- [ ] Verify permissions/roles are set correctly
- [ ] Enable audit logging in production
- [ ] Test unmasked access logging
- [ ] Verify rate limiting on sensitive endpoints
- [ ] Document in runbook: "To view unmasked cards, user must have `export_cards_unmasked` permission"
- [ ] Monitor audit logs for unauthorized access attempts
- [ ] Set up alerts for bulk unmasked exports

---

## Troubleshooting Checklist

| Issue | Diagnosis | Solution |
| ------- | ----------- | ---------- |
| Routes not found | `php artisan route:list \| grep card` | Clear routes: `php artisan route:clear` |
| Cards show "Unknown" bank | No BIN data in DB | Run `php artisan cards:resolve-bins` |
| Unmasked display not working | Config not set | Set `CARD_DISPLAY_MODE=unmasked` |
| Permission denied on export | User lacks role | Assign `export_cards_unmasked` permission |
| Audit logs not saving | Middleware not active | Verify `CardDisplayService::auditUnmaskedAccess()` called |
| API returns 403 | Failed authorization | Check `Gate::allows('view', $card)` |
