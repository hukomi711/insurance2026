#!/bin/bash
set -e

SERVER="root@209.74.64.215"
APP_PATH="/opt/insurance2026"
PASSWORD="${1:-}"

if [ -z "$PASSWORD" ]; then
  echo "❌ Usage: $0 <root_password>"
  exit 1
fi

echo "📦 Deploying Echo auth fix to production..."

# Step 1: Pull changes
echo "1️⃣ Pulling code from remote..."
export SSHPASS="$PASSWORD"
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$SERVER" \
  "cd $APP_PATH && git pull origin hardening/clean-rebuild"

# Step 2: Build frontend assets
echo "2️⃣ Building frontend assets..."
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$SERVER" \
  "cd $APP_PATH && npm run build 2>&1 | tail -n 5"

# Step 3: Rebuild Docker image
echo "3️⃣ Rebuilding app container..."
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$SERVER" \
  "cd $APP_PATH && docker compose build app"

# Step 4: Restart services
echo "4️⃣ Restarting services (app, horizon, scheduler, reverb)..."
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$SERVER" \
  "cd $APP_PATH && docker compose up -d app horizon scheduler reverb"

# Step 5: Verify deployment
echo "5️⃣ Verifying deployment..."
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$SERVER" \
  "cd $APP_PATH && docker compose ps | grep -E 'app|horizon|scheduler|reverb'"

echo ""
echo "✅ Deployment complete!"
echo "🔍 Testing connectivity..."
sleep 3
curl -I https://lexusforbon.com 2>&1 | grep -E "HTTP|cf-ray" || echo "⚠️ Warning: cf-ray header not detected"
