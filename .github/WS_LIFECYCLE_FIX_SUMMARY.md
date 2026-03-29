# WebSocket Lifecycle State Machine Fix — March 29, 2026

## 🎯 Problem Statement

Dashboard WebSocket connection exhibited:
- **WS flapping** (connected/disconnected cycling every few seconds)
- **Duplicate channel subscriptions** (.listen() registrations firing multiples times)
- **Polling not stopping** when WS was connected
- **Race conditions** between Pusher 'connected' event and channel subscription completion

### Root Cause

The system used a boolean (`wsConnected = true/false`) to track connection state, but this was insufficient to represent the complete lifecycle:

```
boolean: true/false
  ↓
needed: disconnected → connecting → connected → ready → reconnecting → failed
```

**The actual problem:** Multiple side effects (bindings, subscriptions, polling guards) were triggered without proper serialization, causing:
1. Old reconnect attempts to interfere with new ones (no generation tracking)
2. Duplicate Pusher connection event bindings (not guarded)
3. Duplicate channel listeners (subscribers called .listen() without prior .stopListening())
4. Polling stopping before subscriptions were stable (connected ≠ ready)

---

## ✅ Solution Implemented (PHASE 1)

### New State Machine

Introduced **5-state machine** instead of boolean:

```js
let wsState = 'disconnected' | 'connecting' | 'connected' | 'ready' | 'reconnecting' | 'failed'
```

| State | Meaning | Polling Runs? |
|-------|---------|---|
| `disconnected` | No connection attempt | ✅ YES (fallback) |
| `connecting` | Transport connecting, subscriptions pending | ✅ YES (safety) |
| `connected` | Transport up, but subscriptions not yet bound | ✅ YES (safety) |
| `ready` | Transport + subscriptions stable | ❌ NO (WS is primary) |
| `reconnecting` | Scheduled retry in progress | ✅ YES (fallback) |
| `failed` | Connection gave up, max backoff reached | ✅ YES (fallback) |

### Generation Tracking

Added **`wsGeneration` token** to prevent old reconnect attempts from interfering:

```js
let wsGeneration = 0

async function connectDashboardWebSocket() {
  const generation = ++wsGeneration
  
  if (generation !== wsGeneration) return  // Guard: abort if newer attempt started
}
```

### Channel Subscription Registry

Replaced scattered `_dashboardChannel`, `_adminOtpChannel`, etc. with single registry:

```js
let subscribedChannels = new Map()  // "dashboard" → channel object, "admin.otp" → channel, ...

function teardownChannelListeners() {
  for (const [name, channel] of subscribedChannels.entries()) {
    channel.stopListening(event)  // Explicit cleanup before reconnect
  }
  subscribedChannels.clear()
}
```

### New Helper Functions

```js
setWsState(next)                    // Update state + handle polling transitions
clearReconnectTimer()               // Safe timer cleanup
teardownChannelListeners()          // Idempotent listener removal
```

---

## 📁 Files Modified

### 1. `resources/js/dashboard/pages/DashboardHome.vue`

**Added:**
- Line ~207: `let wsGeneration = 0`
- Line ~208: `let wsState = 'disconnected'`
- Line ~209: `let connectionBindingsBound = false`
- Line ~210: `let subscribedChannels = new Map()`
- Line ~316–361: Three new functions:
  - `setWsState(next)` — State machine + polling trigger
  - `clearReconnectTimer()` — Safe cleanup of reconnect timer
  - `teardownChannelListeners()` — Explicit .stopListening() on all events
- Line ~407: Updated `onUnmounted()` to use `wsGeneration++` and new cleanup functions

**To Implement (PHASE 2):**
- Replace old `connectDashboardWebSocket()` to use generation guards, state machine, and subscription registry
- Update `scheduleReconnect()` and `disconnectDashboardWebSocket()`
- Error event now treated as failure (not just logged)

### 2. `resources/js/services/adminPolling.js`

**Changed:**
- Line ~14: `let _wsState = 'disconnected'` (was: `let _isWsConnected = false`)
- Line ~74: Polling skip condition: `_wsState === 'ready'` (was: `_isWsConnected`)
- Line ~253–273: New `setWsState(state)` function with state machine logic
- Line ~275–284: Backward-compatible `setWsConnected(connected)` wrapper

---

## 🧪 Testing & Verification

### Build Status
✅ `npm run build` — **Success** (no errors, DashboardHome chunk 64.47 kB)

### Lint Status
✅ `npm run lint` — **Minor warnings only** (unused vars for new functions, expected until PHASE 2)

### Test Suite
- Verify with: `npm run test` (should pass existing tests)

---

## 🚀 Deployment Strategy

### Pre-deployment
1. Complete PHASE 2 (connectDashboardWebSocket rewrite)
2. Run full test suite: `npm run test`
3. Local browser test: Open admin dashboard, check DevTools console for:
   - No duplicate `[Dashboard WS] Subscribed...` messages
   - No duplicate listeners
   - `[Dashboard WS] State -> ready` appearing once after connect
   - No WS flapping logs within 60 seconds

### Deployment
```bash
npm run build
scp -r public/build/ prod-server:live_volume_path/
php artisan optimize:clear
```

### Post-deployment Monitoring
Monitor for 30 minutes:
```bash
ssh prod-server "docker logs reverb-container --tail 100 -f | grep -E '(connected|disconnected|error|4001)'"
curl -v https://domain.com/api/customer/ip
```

---

## 📊 Expected Improvements

| Metric | Before | After |
|--------|--------|-------|
| **WS Flapping** | Every 2–5s | None (stable connection or graceful failure) |
| **Duplicate Events** | 2–3× per event | 1× per event (exactly once) |
| **Polling While WS Active** | Overlapping | Completely stopped (WS-first) |
| **Reconnect Races** | Common | Prevented via generation token |
| **Channel Listener Count** | Growing over time | Bounded (cleanup explicit) |

---

## 🔍 Architecture Insights

### Why This Works

1. **Generation Token**: Each reconnect gets a unique ID; old operations from stale generations abort early via `if (generation !== wsGeneration) return`

2. **State Machine**: Instead of asking "is WS connected?" we ask "is WS READY?" — connected transport + subscriptions + listeners all bound exactly once

3. **Registry**: Centralized `subscribedChannels` Map prevents accidental duplicate listener registrations. `.stopListening()` before `.listen()` ensures idempotency.

4. **Explicit Cleanup**: `teardownChannelListeners()` calls `.stopListening()` for every event on every channel, preventing stale handlers from continuing after reconnect.

5. **Polling Transition Logic**: In `adminPolling.js`, `setWsState()` detects the moment WS transitions from ready → not-ready and immediately requests a poll.

---

## 📝 Code Comments for Future Developers

All new code includes clear inline comments explaining:
- Why generation tracking is needed
- Why `ready` state matters differently from `connected`
- Why registry-based subscriptions prevent duplicates
- Why explicit cleanup is necessary in each phase

---

## 🎓 Lessons for Similar Issues

If you encounter:
- **Duplicate listeners/bindings** → Use registry + explicit cleanup
- **Race conditions with async setup** → Generation/epoch tokens
- **Complex state transitions** → State machine + clear state diagram
- **Boolean flags insufficient** → Upgrade to string state/enum

---

## 📞 Contact / Questions

For questions about this fix:
1. Check inline code comments (heavily documented)
2. Review `/memories/session/websocket-echo-lifecycle-analysis-march2026.md`
3. Reference this summary

---

**Status:** ✅ PHASE 1 Complete, 🚀 Ready for PHASE 2 (connectDashboardWebSocket rewrite)  
**Estimated PHASE 2 Time:** 30 minutes  
**Risk Level:** Low (additive changes, backward compatible, testable in isolation)  
**Last Updated:** March 29, 2026, 22:00 UTC
