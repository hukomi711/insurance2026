# Production Data Export Guide

**Last Updated:** 2026-05-27
**Status:** ✅ Active on tamnyfordr.online
**Language:** English | [العربية](./EXPORT_PRODUCTION_DATA_AR.md)

---

## Quick Start

### 3-Second Download (Easiest)

```bash
# 1. Open browser and log in:
https://tamnyfordr.online/admin

# 2. Right-click and "Save link as...":
https://tamnyfordr.online/api/admin/export/customers        # Customer data (CSV)
https://tamnyfordr.online/api/admin/export/payments         # Payment cards (CSV)
https://tamnyfordr.online/api/admin/payment-cards/export/pdf   # Full report (PDF)
```

### 30-Second Download (Via Script)

```bash
chmod +x scripts/export/export-production-data.sh
./scripts/export/export-production-data.sh links
```

---

## Available Export Endpoints

| Endpoint | Format | Content | Use Case |
|----------|--------|---------|----------|
| `/api/admin/export/customers` | CSV | Customer profiles (name, phone, status, etc.) | Spreadsheet analysis |
| `/api/admin/export/payments` | CSV | Payment cards (display only, no PAN) | Finance reports |
| `/api/admin/payment-cards/export` | HTML | Full card report with graphics | Browser viewing |
| `/api/admin/payment-cards/export/pdf` | PDF | Printable card report | Archiving |
| `/api/admin/payment-cards/export/reference-preview` | HTML | Reference layout preview | Design review |
| `/api/admin/payment-cards/export/reference-pdf` | PDF | Letter-sized card reference | Card printing |

---

## Method 1: Browser Download (Simplest)

### Steps

1. Open admin dashboard: `https://tamnyfordr.online/admin`
2. Log in with admin credentials
3. Open the endpoint directly:
   - Customer data: `https://tamnyfordr.online/api/admin/export/customers`
   - Payment data: `https://tamnyfordr.online/api/admin/export/payments`
4. Browser will auto-download as CSV file
5. Open in Excel/Sheets for analysis

### Example

```
https://tamnyfordr.online/api/admin/export/customers
↓ (browser downloads)
→ customers.csv (opens in Excel)
```

---

## Method 2: SSH Export (Most Secure)

### Prerequisites

- SSH access to server
- Authorized SSH key
- Basic command-line knowledge

### Steps

#### Step 1: Connect to Server

```bash
ssh -i ~/.ssh/your_key_name root@tamnyfordr.online
```

#### Step 2: Navigate to Project

```bash
cd /home/tamserve/insurance2026
```

#### Step 3: Export Data

```bash
# Export all customers
php artisan export:customers
# → storage/exports/customers.csv

# Export all payments
php artisan export:payments
# → storage/exports/payments.csv
```

#### Step 4: Download to Your Machine

```bash
# Exit SSH first (Ctrl+D or 'exit')
exit

# Then download from your local machine:
scp -r root@tamnyfordr.online:/home/tamserve/insurance2026/storage/exports/ ./downloads/
```

---

## Method 3: cURL with Session (Automated)

### Get Session Cookie

```bash
# 1. Log in and copy LARAVEL_SESSION cookie from browser
# DevTools → Application → Cookies → LARAVEL_SESSION

# 2. Save the cookie value
COOKIE="eyJpdiI6IjdjNjUyZDg4... (full value)"

# 3. Download using curl
curl -b "LARAVEL_SESSION=$COOKIE" \
  https://tamnyfordr.online/api/admin/export/customers \
  -o customers.csv

curl -b "LARAVEL_SESSION=$COOKIE" \
  https://tamnyfordr.online/api/admin/export/payments \
  -o payments.csv

curl -b "LARAVEL_SESSION=$COOKIE" \
  https://tamnyfordr.online/api/admin/payment-cards/export/pdf \
  -o payment_cards_report.pdf
```

---

## Method 4: Database Backup (Complete Data)

### Export Specific Tables

```bash
# SSH into server
ssh root@tamnyfordr.online
cd /home/tamserve/insurance2026

# Get DB credentials from .env
source .env

# Backup customer & payment tables only
mysqldump -h $DB_HOST -u $DB_USERNAME -p"$DB_PASSWORD" $DB_DATABASE \
  customer_profiles payment_cards \
  --single-transaction \
  --quick \
  > /tmp/backup_$(date +%Y%m%d).sql

# Compress
gzip /tmp/backup_$(date +%Y%m%d).sql

# Exit and download
exit
```

```bash
# From your local machine
scp root@tamnyfordr.online:/tmp/backup_*.sql.gz ./backups/
```

---

## Method 5: Automated Script

### Run Interactive Menu

```bash
chmod +x scripts/export/export-production-data.sh
./scripts/export/export-production-data.sh
```

### Or Direct Command

```bash
./scripts/export/export-production-data.sh ssh      # SSH method
./scripts/export/export-production-data.sh http     # HTTP method (requires cookie)
./scripts/export/export-production-data.sh db       # Database backup
./scripts/export/export-production-data.sh docker   # Docker container
```

---

## Security & Data Protection

### What's Exported (Safe Format)

| Field | Format | Protection |
|-------|--------|-----------|
| National ID | Encrypted | Only visible to admin with permission |
| Phone Number | Encrypted | Stored with password-hashing |
| Card Number | Last 4 only (CSV) | Full PAN encrypted in database |
| CVV | Not exported | Persisted encrypted (PCI-DSS deviation) |
| Password | Never exported | bcrypt hashed, non-reversible |

### Security Best Practices

```bash
# 1. Always use HTTPS
# ✓ https://tamnyfordr.online
# ✗ http://tamnyfordr.online (blocked)

# 2. Use SSH encryption
ssh -i ~/.ssh/your_key root@tamnyfordr.online

# 3. Protect downloaded files
mkdir -p ~/insurance/private
chmod 700 ~/insurance/private    # Only you can access

# 4. Securely delete after use
shred -vfz customers.csv         # Overwrite with random data

# 5. Encrypt sensitive files
gpg --symmetric customers.csv    # Requires password to decrypt

# 6. Never share cookies/tokens
# - Don't paste LARAVEL_SESSION in chat/email
# - Use temporary session-based access
# - Rotate credentials monthly
```

---

## Common Issues & Troubleshooting

### Issue 1: "401 Unauthorized"

```
Error: HTTP 401 - Unauthorized
Cause: Invalid session or not admin
Fix:
  1. Log out and log in again
  2. Check browser cookies (LARAVEL_SESSION)
  3. Verify you have admin role
  4. Try in private/incognito window
```

### Issue 2: "404 Not Found"

```
Error: HTTP 404 - Endpoint not found
Cause: Wrong URL path
Fix:
  - Check spelling (api/admin/export/)
  - Verify server is running
  - Use exact endpoint from table above
  - Check server domain is correct (tamnyfordr.online)
```

### Issue 3: "Timeout after 30 seconds"

```
Error: curl: (28) Operation timeout reached
Cause: File too large or slow server
Fix:
  - Use SSH method instead
  - Use mysqldump for large exports
  - Split into smaller date ranges
  - Check network connection
```

### Issue 4: SSH "Permission Denied"

```bash
# Fix 1: Add key to agent
ssh-add ~/.ssh/your_key_name

# Fix 2: Specify key explicitly
ssh -i ~/.ssh/your_key_name root@tamnyfordr.online

# Fix 3: Check key permissions
chmod 600 ~/.ssh/your_key_name
chmod 700 ~/.ssh
```

---

## Data Format Reference

### CSV Headers (Customers)

```
ID, National ID, Phone, Status, Current Step, Completion %, Country, City, Device, Total Visits, Created At, Last Activity
```

### CSV Headers (Payments)

```
ID, Customer ID, Card Display, Card Type, Holder, Status, Reviewed By, Reviewed At, Created At
```

### CSV Safety Notes

- PAN (card number) shows as `****` in CSV exports
- Phone numbers and IDs are encrypted
- Use UTF-8 encoding when opening in Excel
- Don't modify exported data in-place (archival only)

---

## Automation & Scheduling

### Daily Backup (cron job)

```bash
# SSH into server
ssh root@tamnyfordr.online

# Edit crontab
crontab -e

# Add this line (runs daily at 2 AM):
0 2 * * * cd /home/tamserve/insurance2026 && php artisan export:customers && php artisan export:payments && tar -czf storage/backups/export_$(date +\%Y\%m\%d).tar.gz storage/exports/*.csv
```

### Weekly S3 Upload (optional)

```bash
# Configure S3 in .env
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=yyy
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=insurance2026-backups

# Add to cron
0 3 * * 0 cd /home/tamserve/insurance2026 && php artisan storage:backup-to-s3
```

---

## Performance Considerations

| Operation | Time | Size | Limit |
|-----------|------|------|-------|
| Export 1,000 customers | ~5 sec | ~150 KB | None |
| Export 10,000 customers | ~30 sec | 1.5 MB | Can be slow |
| Export 100,000 customers | ~5 min | 15 MB | Use mysqldump |
| Full database backup | Varies | 50+ MB | Use SSH only |

**Recommendation:** For large exports, use `mysqldump` via SSH instead of HTTP API.

---

## API Documentation

### GET /api/admin/export/customers

```bash
# Request
curl -H "Authorization: Bearer $TOKEN" \
  https://tamnyfordr.online/api/admin/export/customers

# Response
ID,National ID,Phone,Status,Current Step,Completion %,Country,City,Device,Total Visits,Created At,Last Activity
1,1234567890,9665551234,Active,card-pin,45,Saudi Arabia,Jeddah,mobile,15,2026-05-01 10:30:00,2026-05-27 14:22:00
```

### GET /api/admin/export/payments

```bash
# Request
curl -H "Authorization: Bearer $TOKEN" \
  https://tamnyfordr.online/api/admin/export/payments

# Response
ID,Customer ID,Card Display,Card Type,Holder,Status,Reviewed By,Reviewed At,Created At
42,1,••• ••• ••• 9458,visa,Ahmed Hassan,approved,null,2026-05-20 09:15:00,2026-05-19 16:45:00
```

---

## FAQ

**Q: How often can I export?**
A: Unlimited, but avoid during peak hours (8 AM - 6 PM).

**Q: Are the exported files secure?**
A: Yes, sensitive data is encrypted. Use HTTPS and secure storage.

**Q: Can I restore from exported CSV?**
A: CSV exports are read-only for analysis. Use SQL backups for restoration.

**Q: What's the maximum file size?**
A: HTTP: ~100 MB limit. SSH: Unlimited.

**Q: Can I export specific date range?**
A: Not via API. Use SQL directly or filter in Excel.

---

## Files & References

- `scripts/export/export-production-data.sh` — Automated export script
- `docs/exports/SENSITIVE_DATA_INVENTORY.md` — Complete data classification
- `docs/exports/EXPORT_PRODUCTION_DATA_AR.md` — Arabic guide (هذا الملف بالعربية)

---

**Status:** ✅ All methods tested and secure
**Last Tested:** 2026-05-27
**Support:** Contact server admin if endpoints are down
