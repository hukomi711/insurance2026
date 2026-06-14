#!/bin/bash

# ═══════════════════════════════════════════════════════════════════════════
# Insurance2026 - Download Production Data Script
# Purpose: Safely export customer and payment card data from production server
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

# Configuration
SERVER_DOMAIN="${SERVER_DOMAIN:-tttaaammmin.xyz}"
SERVER_PATH="/home/tamserve/insurance2026"
EXPORT_DIR="./downloads"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create export directory
mkdir -p "$EXPORT_DIR"

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Insurance2026 - Production Data Export${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

# ─────────────────────────────────────────────────────────────────────────
# Method 1: Using SSH + artisan commands (RECOMMENDED for encryption)
# ─────────────────────────────────────────────────────────────────────────

download_via_ssh() {
    echo -e "${YELLOW}Method 1: Downloading via SSH (Recommended)${NC}"
    echo ""

    if ! command -v scp &> /dev/null; then
        echo -e "${RED}✗ scp not found. Please ensure SSH is installed.${NC}"
        return 1
    fi

    # Option 1a: Export CSV via artisan (on server, then download)
    echo -e "${GREEN}1a. Exporting customers to CSV...${NC}"
    ssh -q root@tttaaammmin.xyz "cd $SERVER_PATH && php artisan export:customers" 2>/dev/null || {
        echo -e "${YELLOW}   (artisan command may not exist, using HTTP method instead)${NC}"
    }

    # Option 1b: Using HTTP API (requires authentication)
    echo -e "${GREEN}1b. Downloading customer data via API...${NC}"

    # Get auth token from .env on server
    AUTH_TOKEN=$(ssh -q root@tttaaammmin.xyz "cd $SERVER_PATH && grep ADMIN_API_TOKEN .env | cut -d= -f2" 2>/dev/null || echo "")

    if [ -z "$AUTH_TOKEN" ]; then
        echo -e "${YELLOW}   Skipping authenticated API download (no token configured)${NC}"
    else
        curl -s -H "Authorization: Bearer $AUTH_TOKEN" \
            "https://$SERVER_DOMAIN/api/admin/export/customers" \
            -o "$EXPORT_DIR/customers_${TIMESTAMP}.csv"
        echo -e "${GREEN}   ✓ Saved to: $EXPORT_DIR/customers_${TIMESTAMP}.csv${NC}"
    fi
}

# ─────────────────────────────────────────────────────────────────────────
# Method 2: Using HTTP API (requires browser login or token)
# ─────────────────────────────────────────────────────────────────────────

download_via_http() {
    echo -e "${YELLOW}Method 2: Downloading via HTTP API${NC}"
    echo ""
    echo -e "${YELLOW}You must be logged in as admin in your browser.${NC}"
    echo -e "${BLUE}Endpoints:${NC}"
    echo ""

    # Customer data
    echo -e "${BLUE}📊 Customer Data (CSV):${NC}"
    CUSTOMER_URL="https://$SERVER_DOMAIN/api/admin/export/customers"
    echo -e "   ${GREEN}$CUSTOMER_URL${NC}"
    echo ""

    # Payment card data
    echo -e "${BLUE}💳 Payment Card Data (CSV):${NC}"
    PAYMENT_URL="https://$SERVER_DOMAIN/api/admin/export/payments"
    echo -e "   ${GREEN}$PAYMENT_URL${NC}"
    echo ""

    # Payment card report (HTML)
    echo -e "${BLUE}📄 Payment Card Report (HTML):${NC}"
    REPORT_HTML="https://$SERVER_DOMAIN/api/admin/payment-cards/export"
    echo -e "   ${GREEN}$REPORT_HTML${NC}"
    echo ""

    # Payment card report (PDF)
    echo -e "${BLUE}📋 Payment Card Report (PDF):${NC}"
    REPORT_PDF="https://$SERVER_DOMAIN/api/admin/payment-cards/export/pdf"
    echo -e "   ${GREEN}$REPORT_PDF${NC}"
    echo ""

    # Manual download instructions
    echo -e "${BLUE}📥 How to download:${NC}"
    echo ""
    echo "1. Open admin dashboard: https://$SERVER_DOMAIN/admin"
    echo "2. Log in with admin credentials"
    echo "3. Right-click the link above and 'Save link as...' OR"
    echo "4. Open the link in a new tab and use browser 'Save page as'"
    echo ""

    # Or use curl with session
    read -p "Do you have an admin session cookie? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "Enter your browser session cookie (LARAVEL_SESSION value): " COOKIE

        echo -e "${GREEN}Downloading customer data...${NC}"
        curl -s -b "LARAVEL_SESSION=$COOKIE" \
            "$CUSTOMER_URL" \
            -o "$EXPORT_DIR/customers_${TIMESTAMP}.csv"

        echo -e "${GREEN}Downloading payment data...${NC}"
        curl -s -b "LARAVEL_SESSION=$COOKIE" \
            "$PAYMENT_URL" \
            -o "$EXPORT_DIR/payments_${TIMESTAMP}.csv"

        echo -e "${GREEN}Downloading payment report PDF...${NC}"
        curl -s -b "LARAVEL_SESSION=$COOKIE" \
            "$REPORT_PDF" \
            -o "$EXPORT_DIR/payment_cards_report_${TIMESTAMP}.pdf"
    fi
}

# ─────────────────────────────────────────────────────────────────────────
# Method 3: Direct database backup
# ─────────────────────────────────────────────────────────────────────────

backup_database() {
    echo -e "${YELLOW}Method 3: Database Backup via SSH${NC}"
    echo ""

    # Get DB credentials from server
    DB_NAME=$(ssh -q root@tttaaammmin.xyz "cd $SERVER_PATH && grep DB_DATABASE .env | cut -d= -f2" 2>/dev/null || echo "insurance2026")
    DB_USER=$(ssh -q root@tttaaammmin.xyz "cd $SERVER_PATH && grep DB_USERNAME .env | cut -d= -f2" 2>/dev/null || echo "root")
    DB_PASS=$(ssh -q root@tttaaammmin.xyz "cd $SERVER_PATH && grep DB_PASSWORD .env | cut -d= -f2" 2>/dev/null || echo "")
    DB_HOST=$(ssh -q root@tttaaammmin.xyz "cd $SERVER_PATH && grep DB_HOST .env | cut -d= -f2" 2>/dev/null || echo "localhost")

    if [ -z "$DB_PASS" ]; then
        echo -e "${RED}✗ Could not retrieve database password${NC}"
        return 1
    fi

    echo -e "${GREEN}Backing up database (customers + payment_cards tables)...${NC}"

    BACKUP_FILE="$EXPORT_DIR/insurance2026_data_${TIMESTAMP}.sql"

    # Backup only sensitive tables
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        customer_profiles payment_cards \
        --single-transaction \
        --quick \
        --lock-tables=false \
        > "$BACKUP_FILE"

    echo -e "${GREEN}✓ Database backup saved to: $BACKUP_FILE${NC}"

    # Compress the backup
    gzip "$BACKUP_FILE"
    echo -e "${GREEN}✓ Compressed: ${BACKUP_FILE}.gz${NC}"
}

# ─────────────────────────────────────────────────────────────────────────
# Method 4: Export via Docker container
# ─────────────────────────────────────────────────────────────────────────

export_via_docker() {
    echo -e "${YELLOW}Method 4: Export via Docker (if server uses Docker)${NC}"
    echo ""

    echo -e "${BLUE}Running on production server:${NC}"
    cat << 'EOF'

docker compose exec -T app php artisan tinker <<'TINKER'
use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use Illuminate\Support\Collection;

// Export customers
$customers = CustomerProfile::orderByDesc('created_at')->get();
\League\Csv\Writer::createFromPath('storage/exports/customers.csv', 'w')
    ->insertOne(['ID', 'Name', 'Phone', 'National ID', 'Status', 'Created At'])
    ->insertAll($customers->map(fn($c) => [
        $c->id,
        $c->full_name,
        $c->phone_number,
        $c->national_id,
        $c->is_active ? 'Active' : 'Inactive',
        $c->created_at
    ]));

// Export payment cards
$cards = PaymentCard::orderByDesc('created_at')->get();
\League\Csv\Writer::createFromPath('storage/exports/payment_cards.csv', 'w')
    ->insertOne(['ID', 'Customer ID', 'Card Type', 'Last 4', 'Status', 'Created At'])
    ->insertAll($cards->map(fn($c) => [
        $c->id,
        $c->customer_profile_id,
        $c->card_type,
        $c->last4,
        $c->status,
        $c->created_at
    ]));

echo "✓ Exports ready in storage/exports/\n";
TINKER

# Then download:
scp root@tttaaammmin.xyz:/home/tamserve/insurance2026/storage/exports/*.csv ./downloads/
EOF
    echo ""
}

# ─────────────────────────────────────────────────────────────────────────
# Main menu
# ─────────────────────────────────────────────────────────────────────────

show_menu() {
    echo -e "${BLUE}Select export method:${NC}"
    echo ""
    echo "  1) SSH + API (Recommended - most secure)"
    echo "  2) HTTP API (Requires browser session)"
    echo "  3) Database backup (mysqldump)"
    echo "  4) Docker container export"
    echo "  5) View all download links"
    echo "  6) Exit"
    echo ""
    read -p "Enter choice [1-6]: " choice
}

# Process menu selection
case "${1:-}" in
    1|ssh)
        download_via_ssh
        ;;
    2|http)
        download_via_http
        ;;
    3|db)
        backup_database
        ;;
    4|docker)
        export_via_docker
        ;;
    5|links)
        echo -e "${BLUE}═ Download Links ═${NC}"
        echo ""
        echo "Customers (CSV):"
        echo "  https://$SERVER_DOMAIN/api/admin/export/customers"
        echo ""
        echo "Payment Cards (CSV):"
        echo "  https://$SERVER_DOMAIN/api/admin/export/payments"
        echo ""
        echo "Payment Cards Report (HTML):"
        echo "  https://$SERVER_DOMAIN/api/admin/payment-cards/export"
        echo ""
        echo "Payment Cards Report (PDF):"
        echo "  https://$SERVER_DOMAIN/api/admin/payment-cards/export/pdf"
        echo ""
        ;;
    *)
        # Interactive mode
        show_menu
        case $choice in
            1) download_via_ssh ;;
            2) download_via_http ;;
            3) backup_database ;;
            4) export_via_docker ;;
            5) show_menu ;;
            6) exit 0 ;;
            *) echo -e "${RED}Invalid choice${NC}" ;;
        esac
        ;;
esac

echo ""
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ Export complete!${NC}"
echo -e "${GREEN}Files saved to: ${EXPORT_DIR}/${NC}"
echo ""
ls -lh "$EXPORT_DIR" 2>/dev/null || true
echo ""
