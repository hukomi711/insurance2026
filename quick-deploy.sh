#!/bin/bash

# Insurance2026 Quick Deployment
# Run this if server and Docker are already set up
# Execute: bash quick-deploy.sh

set -e

PROJECT_ROOT="/opt/insurance2026"
BRANCH="hardening/clean-rebuild"

echo "╔════════════════════════════════════════════════╗"
echo "║  🚀 INSURANCE2026 - QUICK DEPLOYMENT           ║"
echo "╚════════════════════════════════════════════════╝"
echo ""

# Verify Docker is running
echo "✓ Checking Docker..."
docker ps > /dev/null 2>&1 || (echo "❌ Docker not running; try: dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin && systemctl start docker"; exit 1)

# Navigate to project
echo "✓ Navigating to project directory..."
cd "$PROJECT_ROOT" || exit 1

# Pull latest code
echo ""
echo "📥 Pulling latest code from GitHub..."
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"
echo "✅ Code updated"

# Show recent commits
echo ""
echo "📝 Recent commits:"
git log --oneline -5 | head -3
echo ""

# Build Docker image
echo "🔨 Building Docker image (this may take 5-10 minutes)..."
docker compose build app --no-cache 2>&1 | grep -E "Step|Successfully|error" || true
echo "✅ Docker image built"

# Stop current services
echo ""
echo "🛑 Stopping services..."
docker compose down || true
sleep 5

# Start services
echo "🚀 Starting services..."
docker compose up -d
echo "✅ Services started"

# Wait for services
echo ""
echo "⏳ Waiting for services to stabilize (20 seconds)..."
sleep 20

# Check status
echo ""
echo "📊 Service Status:"
docker compose ps
echo ""

# Health check
echo "🏥 Application Health Check:"
HEALTH=$(curl -sk https://localhost/api/health -H "Host: lexusforbon.com" 2>&1)
if echo "$HEALTH" | grep -q "ok.*true"; then
  echo "✅ $HEALTH"
else
  echo "⚠️  Response: $HEALTH"
  echo ""
  echo "Showing last 20 log lines:"
  docker logs ins2026-app 2>&1 | tail -20
fi

echo ""
echo "╔════════════════════════════════════════════════╗"
echo "║  ✅ DEPLOYMENT COMPLETE                        ║"
echo "╚════════════════════════════════════════════════╝"
echo ""
echo "🌐 Check production:"
echo "   https://lexusforbon.com/"
echo ""
echo "📋 View logs:"
echo "   docker logs ins2026-app -f --tail=50"
echo ""
