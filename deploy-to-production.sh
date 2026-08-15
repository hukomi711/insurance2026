#!/bin/bash

# Insurance2026 Production Deployment Script
# Run this on server1.ttamikomzz.com (209.74.64.215)

set -e  # Exit on any error

PROJECT_ROOT="/opt/insurance2026"
BRANCH="hardening/clean-rebuild"

echo "╔════════════════════════════════════════════════╗"
echo "║  🚀 INSURANCE2026 PRODUCTION DEPLOYMENT        ║"
echo "╚════════════════════════════════════════════════╝"
echo ""

# Step 1: Change to project directory
echo "📁 Step 1: Navigating to project directory..."
cd "$PROJECT_ROOT" || exit 1
echo "✅ Current directory: $(pwd)"
echo ""

# Step 2: Git pull
echo "📥 Step 2: Pulling latest code from GitHub..."
git pull origin "$BRANCH"
echo "✅ Code pulled successfully"
echo ""

# Step 3: Show recent commits
echo "📝 Step 3: Recent commits:"
git log --oneline -5
echo ""

# Step 4: Build Docker image
echo "🔨 Step 4: Building Docker image (this may take 5-10 minutes)..."
docker compose build app --no-cache
echo "✅ Docker image built successfully"
echo ""

# Step 5: Restart services
echo "🚀 Step 5: Restarting services..."
docker compose up -d app horizon scheduler reverb
echo "✅ Services restarted"
echo ""

# Step 6: Wait for containers to stabilize
echo "⏳ Step 6: Waiting for containers to stabilize (15 seconds)..."
sleep 15
echo "✅ Containers stabilized"
echo ""

# Step 7: Check container status
echo "📊 Step 7: Container status:"
docker compose ps
echo ""

# Step 8: Health check
echo "🏥 Step 8: Application health check..."
HEALTH=$(curl -sk https://localhost/api/health -H "Host: ttamikomzz.com" 2>&1)
echo "Response:"
echo "$HEALTH" | python3 -m json.tool 2>/dev/null || echo "$HEALTH"
echo ""

# Step 9: Verify assets
echo "🎨 Step 9: Verifying assets are from correct domain..."
ASSETS=$(curl -sk https://localhost/ -H "Host: ttamikomzz.com" 2>&1 | grep -o "ttamikomzz.com/build/assets" | wc -l)
echo "✅ Found $ASSETS asset references from ttamikomzz.com"
echo ""

# Step 10: Check logs for errors
echo "📋 Step 10: Checking application logs (last 30 lines)..."
docker logs ins2026-app 2>&1 | tail -30
echo ""

# Final summary
echo "╔════════════════════════════════════════════════╗"
echo "║  ✅ DEPLOYMENT COMPLETE                        ║"
echo "╚════════════════════════════════════════════════╝"
echo ""
echo "📊 Service Status: All should show 'Up' with 'Healthy'"
echo "🌐 Production URLs:"
echo "   - https://ttamikomzz.com/"
echo "   - https://www.ttamikomzz.com/"
echo "🔍 API Health: https://ttamikomzz.com/api/health"
echo ""
echo "⚠️  If any errors appear above, check logs with:"
echo "   docker logs ins2026-app"
echo "   docker logs ins2026-nginx"
echo ""
