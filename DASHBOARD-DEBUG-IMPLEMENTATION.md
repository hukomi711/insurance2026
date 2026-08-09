# Dashboard Real-Time Updates - Implementation Guide

## What Was Added

### 1. **Debug Panel Component** (`resources/js/dashboard/components/DashboardDebugPanel.vue`)
- Shows real-time status of polling, WebSocket, and system health
- Automatically hidden in production (visible only with `?debug=1` URL param)
- Displays:
  - ✓ Polling status (active/paused)
  - ✓ WebSocket connection state
  - ✓ Time since last refresh
  - ✓ Error count and warnings
  - ✓ Echo/Pusher connection status
  - ✓ Auth token presence

### 2. **Troubleshooting Guide** (`DASHBOARD-REALTIME-TROUBLESHOOTING.md`)
- Comprehensive step-by-step diagnosis
- Common issues and solutions
- Server-side verification commands
- Debug console examples

## How to Use

### Enable Debug Panel

**Option 1: Via URL Query Parameter**
```
https://lybankss.com/admin/dashboard?debug=1
```

**Option 2: Via Browser Console**
```javascript
// In browser console
localStorage.setItem('dashboard_debug', 'true');
window.location.reload();

// To disable
localStorage.removeItem('dashboard_debug');
window.location.reload();
```

### What the Panel Shows

```
🔧 Dashboard Debug
Status: ✓ Polling • ✓ WS • Last: 5s ago
Pusher: connected
Auth: ✓ Token

[Verbose] [Close]
```

**Color Coding**:
- 🟢 Green background = All systems operational (Polling + WS both working)
- 🟡 Amber background = Polling disabled (auto-refresh OFF)
- 🔴 Red background = System issues detected

## Key Troubleshooting Flows

### Flow 1: Auto-Refresh Disabled
```
1. Check Header: "تحديث متوقف" instead of "تحديث تلقائي"
2. Click the amber dot to enable
3. Should turn green with pulsing animation
4. Debug panel shows "✓ Polling"
5. Last update timestamp should advance
```

### Flow 2: WebSocket Not Connected
```
1. Open browser DevTools (F12)
2. Go to Console tab
3. Type: window.Echo?.connector?.pusher?.connection?.state
4. Expected: 'connected'
5. If unavailable/failed → check auth token:
   localStorage.getItem('auth_token')
   
If empty:
   - Log out and log in again
   - Or try ?debug=1 to see detailed errors
```

### Flow 3: Polling Running But Data Not Updating
```
1. Check Header: "Last Updated" timestamp should change every ~10s
2. If not advancing:
   - WebSocket might be consuming events
   - Or no new data is available on backend
   
3. Test with manual action:
   - Approve a customer card
   - Should see updated status instantly
   - Or after 2-3s via fallback refresh
```

### Flow 4: Debug Console Output
```javascript
// Enable verbose logging in debug panel
// [Click "Verbose" button]

// Then in console, filter for dashboard logs:
// Ctrl+Shift+K → type "Dashboard" or "AdminPolling"

// Expected output:
[AdminPolling] tick #1
[AdminPolling] tick #2
[Dashboard] refreshCustomers success: 25 rows
[Dashboard WS] Activity update: 192.168.1.5 card_submitted
```

## For Developers

### Adding More Debug Info

The debug panel can be extended by:

1. Modifying `DashboardDebugPanel.vue`:
```vue
<div>Custom Status: {{ someValue }}</div>
```

2. Passing props from `DashboardLayout.vue`:
```vue
<DashboardDebugPanel 
    :customValue="customValue"
    ...
/>
```

3. Adding to `adminPolling.js`:
```javascript
export function getPollingStatus() {
    return {
        isRunning,
        tickCount,
        consecutiveFailures,
        wsState,
        _isTabVisible
    };
}
```

## Testing Procedures

### Test 1: WebSocket Connection
```bash
# From browser console
const echo = window.Echo;
echo.private('test').listen('.TestEvent', evt => console.log(evt));

# From server (trigger broadcast)
docker compose exec -T app php artisan tinker
>>> event(new \App\Events\CustomerActivityUpdated(1, '192.168.1.5', '/', true))
```

### Test 2: Polling Activation
```javascript
// Disable WebSocket to force polling
localStorage.setItem('ws_disabled', 'true');
window.location.reload();

// Now "Last Updated" should advance every 10s via polling alone
```

### Test 3: Error Recovery
```bash
# Simulate network error by adding throttling in DevTools
# Network → Add custom throttle profile → apply to dashboard page

# Should see:
# - Errors in console
# - Debug panel shows ⚠️ error count
# - Backoff kicks in (updates every 30+ seconds)
# - Recovery when network restored
```

## Metrics to Monitor

| Metric | Healthy | Warning | Critical |
|--------|---------|---------|----------|
| Time since last update | < 2s | 5-10s | > 60s |
| WebSocket state | connected | connecting | unavailable/failed |
| Polling status | ✓ Active | (amber) disabled | (stopped) |
| Error count | 0 | 1-3 | > 5 |
| Last event | < 30s ago | < 2min | No events |

## Expected Behavior After These Changes

✅ Users can instantly see debug status via `?debug=1` URL param
✅ Troubleshooting guide provides clear diagnostic steps
✅ Auto-refresh toggle is visible and works
✅ Polling/WebSocket status is transparent
✅ Error conditions are highlighted
✅ Timestamp shows when system last updated

## Deployment Notes

1. **No breaking changes** - all additions are backwards compatible
2. **Debug panel only visible with `?debug=1`** - no production overhead
3. **No new dependencies** - uses existing Vue + logger utilities
4. **No API changes** - purely frontend diagnostics

## Troubleshooting the Troubleshooter

If debug panel doesn't appear:
```javascript
// Check if debug mode is enabled
console.log(localStorage.getItem('dashboard_debug'));
console.log(new URL(window.location).searchParams.has('debug'));

// Force enable
localStorage.setItem('dashboard_debug', 'true');
window.location.reload();

// Check if component is imported in DashboardLayout
// (should see import line in source)
```

---

**Last Updated**: 2026-08-08
**Status**: Ready for testing and production deployment
