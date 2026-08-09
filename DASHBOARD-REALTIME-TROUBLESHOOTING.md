# Dashboard Real-Time Updates - Troubleshooting Guide

## Problem
لوحة التحكم لا تقوم باعادة التحميل لوحدها (Real-time updates not working)

## Quick Diagnosis Checklist

### 1. **Is Auto-Refresh Enabled?**
✓ Check the dashboard header for the "تحديث تلقائي" (Auto-Refresh) button
- Should show a **green pulsing dot** if enabled
- Shows **amber dot** if disabled
- **Fix**: Click the button to enable if disabled

### 2. **Check Browser Console for Errors**
```javascript
// Open browser DevTools (F12) → Console tab
// Look for these patterns:

// WebSocket connection errors:
"[Echo] Invalid production Reverb VITE_* config"
"[Echo] Auth failed"
"[Dashboard WS] error:"

// Polling errors:
"[AdminPolling] Failed to fetch customers"

// Network errors:
"Failed to fetch" (usually CORS or auth)
```

### 3. **Verify WebSocket Connection**
```javascript
// In browser console, run:
window.Echo && window.Echo.connector?.pusher?.connection?.state

// Expected output: 'connected' or 'connected'
// If 'unavailable', WebSocket connection failed
```

### 4. **Check Network Tab**
1. Open DevTools → Network tab
2. Filter by WebSocket (WS)
3. Should see connection to `wss://lybankss.com/app/...`
4. Connection should say "101 Web Socket Protocol Handshake" (not red/failed)

### 5. **Verify Polling is Running**
```javascript
// In browser console, run:
window.localStorage.getItem('admin_polling_enabled')

// Or check for these logs:
"[AdminPolling] tick #" (appears every 5 seconds)
```

---

## Common Issues & Solutions

### Issue A: Auto-Refresh Toggle Shows AMBER (Disabled)
**Symptom**: "تحديث متوقف" (Auto-Refresh Stopped) in header

**Solution**:
1. Click the amber dot/toggle button to enable
2. Button should turn green with pulsing animation
3. Dashboard should refresh every 10 seconds

**Why it happens**: 
- User accidentally clicked the toggle
- Browser localStorage persists disabled state across page reloads

---

### Issue B: WebSocket Connection Shows RED/UNAVAILABLE
**Symptom**: Browser console shows errors like:
```
[Echo] Invalid production Reverb VITE_* config
[Echo] Auth failed after 3 retries
```

**Solution - Check Auth Token**:
```javascript
// In console:
localStorage.getItem('auth_token')

// Should return a long JWT token
// If empty/null → user is not authenticated
// Fix: Log out → Log in again
```

**Solution - Check VITE Variables**:
```javascript
// In console:
import.meta.env.VITE_REVERB_APP_KEY
import.meta.env.VITE_REVERB_HOST
import.meta.env.VITE_REVERB_PORT
import.meta.env.VITE_REVERB_SCHEME

// Expected:
// VITE_REVERB_APP_KEY: "YGjMWUkuM8ruG6QPqGudw2J1Gx7XYDsrL5sfgCxU"
// VITE_REVERB_HOST: "lybankss.com"
// VITE_REVERB_PORT: "443" or 443
// VITE_REVERB_SCHEME: "https"

// If any are undefined/wrong → frontend build is stale
// Fix: Hard refresh (Ctrl+Shift+R / Cmd+Shift+R) or clear cache
```

---

### Issue C: Network Tab Shows RED WebSocket
**Symptom**: WebSocket connection attempt fails immediately

**Possible Causes**:
1. **Browser Privacy Mode** - WebSocket may be blocked
   - Fix: Use normal browsing mode

2. **VPN/Proxy blocking WebSocket**
   - Fix: Disable VPN temporarily or whitelist lybankss.com

3. **Corporate Firewall blocking port 443 WebSocket**
   - Fix: Contact IT / use fallback polling (still works)

4. **Browser extensions blocking WebSocket**
   - Fix: Try incognito mode (doesn't load extensions)

---

### Issue D: Dashboard Updates Once Then Stops
**Symptom**: Initial load shows data, but doesn't refresh when data changes

**Most Likely Cause**: WebSocket connected but events aren't arriving

**Solution - Verify Events are Being Broadcast**:

From production server:
```bash
# SSH to server
ssh root@209.74.72.242

# Check if events are being published
docker compose logs -f reverb 2>&1 | grep -i "publish\|broadcast"

# You should see lines like:
# "Publishing CustomerActivityUpdated to dashboard"
```

**If no events shown**:
- Backend isn't triggering events
- Check Laravel logs: `docker compose logs app --tail 50`

---

### Issue E: Polling Logs Show "tick #" But No Data Updates
**Symptom**: `[AdminPolling] tick #1`, `tick #2`, etc appear but no refresh calls

**Cause**: Polling timer is running but `refreshCustomers` callback not registered

**Solution**:
1. Check if Dashboard Home page is actually mounted
2. Open browser console and look for:
   ```
   [Dashboard] refreshCustomers success: X rows
   ```
3. If missing, Dashboard component may not be rendering

---

## Advanced Debugging

### Enable Verbose Logging
```javascript
// In browser console:
import { default as logger } from '@/utils/logger';
logger.setVerbose(true);

// Now refresh page - will see detailed logs:
// [AdminPolling] tick #...
// [Dashboard] refreshCustomers...
// [Dashboard WS] Activity update...
```

### Check Last Update Timestamp
The header shows "آخر تحديث: ..." (Last Updated)
- Should update every 10 seconds if polling is working
- Should update more frequently if events are arriving

### Force Manual Refresh
Click the blue "تحديث" (Refresh) button to manually trigger an update
- If this works → backend and API are fine
- If this fails → check API/network errors in console

---

## Server-Side Verification

If client-side looks correct but still no updates:

```bash
ssh root@209.74.72.242

# 1. Check Reverb service
docker compose ps reverb
# Should show: Up 10 hours (healthy)

# 2. Check queue processing
docker compose logs -f horizon --tail 20
# Should show queue jobs being processed

# 3. Check for database activity
docker compose exec -T app php artisan tinker --execute "
  echo 'Recent activity: ' . DB::table('customer_activities')->latest()->limit(5)->count() . ' records\n';
  echo 'Recent updates: ' . DB::table('customer_profiles')->where('updated_at', '>', now()->subMinute())->count() . ' in last minute\n';
"

# 4. Check for broadcast errors
docker compose logs app 2>&1 | grep -i "broadcast\|event\|error" | tail -20
```

---

## Nuclear Option: Full Reset

If nothing works, try:

```bash
# Clear browser cache
# 1. Hard refresh: Ctrl+Shift+R (or Cmd+Shift+R on Mac)
# 2. Open DevTools → Application → Clear Site Data
# 3. Log out → Log in again

# Then:
1. Check console for any error messages
2. Monitor "Last Updated" timestamp
3. Trigger a test action (e.g., approve a card) to see if dashboard updates
```

---

## Reverb Health Check

From dashboard:
```javascript
// Check WebSocket transport
window.Echo?.connector?.pusher?.connection?.transport?.name
// Should return: 'ws' or 'wss'

// Check Pusher activity
window.Echo?.connector?.pusher?.channels
// Should show: {'dashboard': ..., 'admin.otp': ...}
```

---

## Still Not Working?

1. **Collect these logs**:
   ```bash
   # From your browser console (right-click → Save As)
   copy(localStorage.getItem('console_logs') || 'No logs')
   
   # From server
   ssh root@209.74.72.242
   docker compose logs --tail 100 reverb > /tmp/reverb.log
   docker compose logs --tail 100 app > /tmp/app.log
   docker compose logs --tail 100 horizon > /tmp/horizon.log
   
   # Download logs
   scp root@209.74.72.242:/tmp/*.log ~/
   ```

2. **Report with**:
   - Browser version
   - Operating system
   - Network type (WiFi/Ethernet/Mobile)
   - Steps to reproduce
   - Screenshot of error message or Network tab

---

## Quick Reference: Status Indicators

| Indicator | Meaning | Action |
|-----------|---------|--------|
| 🟢 Green pulsing dot | Auto-refresh ON, WS connected | Normal - working fine |
| 🟡 Amber dot | Auto-refresh OFF | Click to enable |
| 🔴 Red in console | WebSocket failed | Check auth token, hard refresh |
| ⏱️ "آخر تحديث: قبل X ثانية" | Last update time | Should advance every ~10s |
| ⚠️ "فشل تحميل البيانات" | API error | Check network, auth token |

---

Last Updated: 2026-08-08
