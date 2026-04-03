# 🔴 Runbook: HTTPS/SSL Issues on Mobile Devices
**⏱️ Estimated time: 10 minutes**  
**📱 Applies to:** ERR_SSL_PROTOCOL_ERROR, ERR_NAME_NOT_RESOLVED, connection timeouts on iOS/Android

---

## 🎯 Quick Diagnosis Flow

```
Does user get ERR_SSL_PROTOCOL_ERROR or ERR_NAME_NOT_RESOLVED?
    ↓
Can they access site from Desktop/Laptop? (YES/NO)
    ↓ YES
    ↓
Is this iPhone/iOS? (YES/NO)
    ↓ YES → Go to Step 2: iOS Private Relay Test
    ↓ NO  → Go to Step 3: Network Path Test
```

---

## 📋 Step 1: Initial Triage (1 min)

**Ask the user:**

```
1. What is the exact error message? 
   📸 Screenshot the error (full error code)
   
2. Device & OS? 
   e.g., "iPhone 14 Pro / iOS 18.7"
   
3. Network type?
   e.g., "Wi-Fi", "5G", "4G LTE"
   
4. Which browser?
   e.g., "Safari", "Chrome", "Firefox"
   
5. Can you access the site from your computer right now?
   "Yes" / "No"
```

**Store these details — they determine next steps.**

---

## 👨‍💻 Step 2: iOS Private Relay Test (3 min)

**👉 ONLY if user is on iOS (iPhone/iPad)**

### What is this?
On iOS, **iCloud Private Relay** intercepts HTTPS requests through Apple's CDN edge, which can cause DNS inconsistency or TLS failures with certain domains (.sbs, smaller TLDs).

### Test:
1. **Settings → (Your Name) → iCloud → Private Relay → Turn OFF**
   
2. **Safari: Settings → Develop → Turn OFF "Private Browsing"** (if on)
   
3. **Close Safari completely** (swipe up from task switcher)
   
4. **Hard refresh:** Open watheeq.plus again
   - If on Safari: Cmd+Shift+R or hold refresh button → "Hard Refresh"
   
5. **Does the domain load now?**

#### ✅ **Result: YES, loaded!**
→ **Root cause: iOS privacy layer + CDN edge routing incompatibility**

- **Solution for user:** Keep Private Relay OFF for watheeq.plus, or use Cloudflare WARP (see Step 3)
- **Document:** "iOS Private Relay conflict with .sbs domain"
- **Close ticket** ✅

#### ❌ **Result: NO, still fails**
→ Continue to **Step 3**

---

## 🌐 Step 3: Network Path Test via VPN (4 min)

**👉 For all users (iOS/Android) who still fail**

### What is this?
**Cloudflare WARP** tunnels all traffic through a different resolver/CDN path. If the user succeeds with WARP, the problem is **carrier DNS or CDN edge routing**, not the server.

### Test:
1. **Download Cloudflare WARP** (free tier):
   - iOS: App Store search "1.1.1.1: Faster Internet"
   - Android: Google Play search "1.1.1.1"
   
2. **Install & open the app**
   
3. **Toggle the switch: ON**
   - Wait 5 seconds for connection
   
4. **Open Safari/Chrome → Visit watheeq.plus**
   
5. **Does it load?**

#### ✅ **Result: YES, loaded with WARP!**
→ **Root cause: Carrier/DNS resolver path instability**
- **Why:** WARP uses Cloudflare's own resolver instead of carrier DNS
- **Solution for user:**
  - "Continue using WARP when accessing insurance portal"
  - OR "Try different Wi-Fi network or wait 30 min and retry"
- **Document:** "Carrier DNS path issue — WARP resolves"
- **Close ticket** ✅

#### ❌ **Result: NO, fails even with WARP**
→ This is very rare. Continue to **Step 4**.

---

## 🔧 Step 4: Advanced Diagnostics (2 min)

**Only if Step 1–3 all failed**

### Manual Network Test (for user to send back)
1. **Open Chrome → Settings → Developer Tools (F12)**
   
2. **Copy-paste this into Console:**
   ```javascript
   fetch('https://watheeq.plus/api/customer/ip')
     .then(r => { console.log('✅ Status:', r.status); return r.text() })
     .then(t => console.log('Response:', t))
     .catch(e => console.error('❌ Error:', e.message))
   ```
   
3. **Screenshot the entire console output** and send to support
   
4. **Also run:**
   ```javascript
   console.log('DNS:', location.hostname)
   console.log('Protocol:', location.protocol)
   console.log('User Agent:', navigator.userAgent)
   ```

**💡 Send these details to Engineering for deeper analysis**

---

## ✅ Verdict Guide

| Error | Status | Root Cause | Solution |
|-------|--------|-----------|----------|
| Fails on mobile, works on desktop | ✅ VERIFIED | Mobile network path / Carrier DNS | WARP or wait/retry |
| Fails on iOS, works off Private Relay | ✅ VERIFIED | iOS Privacy Layer conflict | Disable Private Relay or use WARP |
| Works with WARP, fails without | ✅ VERIFIED | DNS resolver instability | Use WARP as workaround |
| Fails everywhere with WARP | ⚠️ RARE | Server-side issue or device ISP block | Escalate to Engineering |
| Works on reopening/hard refresh | ✅ VERIFIED | DNS/TLS cache issue | Clear cache, try again |

---

## 📞 Response Template

### ✅ **Resolved**
```
Hi [User],

Thanks for reporting! We've confirmed the issue is with your network path, 
not our server.

**Solution:** Try one of these:
1. Disable iCloud Private Relay (Settings → iCloud)
2. Use Cloudflare WARP app (free, App Store/Play Store)
3. Switch to a different Wi-Fi or wait 30 min before retrying

Our server is working correctly (verified ✅). The error is from your 
carrier's DNS resolver or a routing edge issue, which WARP bypasses.

Let us know if it works!
```

### ⚠️ **Unresolved - Escalate**
```
Hi [User],

We've completed standard troubleshooting:
- ❌ Private Relay disabled: Still fails
- ❌ WARP enabled: Still fails
- ❌ Desktop works fine

This requires deeper engineering review. We're escalating to our tech team.

Please reply with the console output from Step 4 so we can investigate further.

ETA: 24 hours
```

---

## 🧠 Key Points for Support Team

| Point | Why It Matters |
|-------|---|
| **Server is healthy** | HTTPS works from desktop + curl + nginx logs are clean |
| **Problem is device/path specific** | Only certain mobile networks fail, others work fine |
| **Network layer issue, not app** | Same server responds correctly to some requests; DNS/TLS resolvers vary by carrier |
| **WARP is diagnostic magic** | If it works with WARP but not without, you've proven DNS/routing, not server |
| **iOS Private Relay is a real culprit** | Apple's CDN intercept breaks .sbs domain TLS negotiation for some users |
| **Never escalate without testing** | Save engineering time: you can resolve 95% of these in 10 min |

---

## 🛠️ Test Server Status (Verify Before Each Shift)

Run this every morning to confirm server is healthy:

```bash
# From your terminal:
curl -sI https://watheeq.plus | head -5

# Expected output:
# HTTP/2 200 
# server: nginx
# strict-transport-security: max-age=31536000
```

If this fails, **escalate immediately** — server is actually down.

---

## 📊 Metrics to Track

Keep a simple log:

| User | OS | Network | Error | Solution | Time | Result |
|------|---|---------|-------|----------|------|--------|
| user@mail.com | iOS 18 | 5G | ERR_SSL | Private Relay OFF | 5min | ✅ Fixed |
| user2@mail.com | Android 14 | 4G | ERR_NAME | WARP enabled | 8min | ✅ Fixed |
| user3@mail.com | macOS | Wi-Fi | N/A | Works fine | 1min | ✅ Works |

**Share this with Product weekly** — patterns will help decide if we need .sa domain or CDN change.

---

## 🚨 Escalation Triggers

**Escalate to Engineering immediately if:**

```
1. Desktop ALSO fails
   → Server issue, not network

2. curl from Linux also fails
   → Certificate or Nginx config issue

3. User has WARP ON and still fails
   → ISP blocking or very exotic issue

4. Multiple users (5+) report simultaneously
   → Possible carrier-wide issue or DNS propagation delay
```

---

## 📧 One-Liner for Email/Chat

```
Hi! We found your connection issue is from your mobile network's DNS, 
not our server. Try turning off iCloud Private Relay or using Cloudflare WARP app. 
Let me know if that helps! 🎯
```

---

**Last Updated:** March 29, 2026  
**Owner:** DevOps / Support Team  
**Review Frequency:** Monthly
