# 🚀 Quick Reference Card — Mobile HTTPS Troubleshooting
**Print this or pin in support channel**

---

## 30-Second Diagnosis

```
┌─ User: "I can't access tamincom.store on my phone"
│
├─ Q1: Error message?
│  ├─ ERR_SSL_PROTOCOL_ERROR → Step A
│  ├─ ERR_NAME_NOT_RESOLVED → Step A
│  └─ Other / timeout → Step A
│
├─ Q2: Works on desktop?
│  ├─ Yes → Step B (Network issue)
│  └─ No → Escalate (Server issue)
│
├─ Q3: iPhone?
│  ├─ Yes → Step B1 (Test Private Relay OFF)
│  └─ No → Step B2 (Test WARP)
│
└─ Done!
```

---

## Step A: Instant Verification (1 min)

**Run this from your machine RIGHT NOW:**

```bash
# Test 1: Server responding?
curl -sI https://tamincom.store | head -1

# Test 2: Certificate valid?
openssl s_client -connect tamincom.store:443 -servername tamincom.store </dev/null 2>/dev/null | grep -E "(subject=|notAfter=)"

# Test 3: Reverb working?
curl -s https://tamincom.store/api/customer/ip | head -c 50
```

**If all 3 pass:**
→ Server is 100% healthy. Problem is user's device/network. Continue to Step B.

**If any fail:**
→ Escalate to Engineering immediately.

---

## Step B: User-Side Diagnostic (5 min)

### **Sub-Step B1: iPhone Users**

**Send user this message:**

```
Hi! Let's try a quick test:

1. Go to Settings → [Your Name] → iCloud → Private Relay
2. Turn it OFF
3. Go to Safari and reload tamincom.store
4. Does it work now?

Let me know what happens! 👍
```

**User replies:**

| Reply | Action |
|-------|--------|
| "Yes, it works!" | → **Close ticket.** Reply: "Great! The issue is iOS privacy layer. Either keep Private Relay off for this site, or use Cloudflare WARP instead (free app)." |
| "Still doesn't work" | → Go to Sub-Step B2 (WARP test) |

---

### **Sub-Step B2: All Users (iOS/Android)**

**Send user this message:**

```
Let's test with Cloudflare WARP (free VPN):

1. Download "1.1.1.1: Faster Internet" from App Store / Play Store
2. Open the app and toggle the switch ON
3. Then try tamincom.store again
4. Does it work now?

(It's totally safe — just uses a different network path)
```

**User replies:**

| Reply | Action |
|-------|--------|
| "Yes, it works with WARP!" | → **Close ticket.** Reply: "Perfect! The issue is your carrier's DNS. Use WARP or try a different Wi-Fi. Our server is fine." |
| "Still doesn't work with WARP" | → **Escalate to Engineering.** Reply: "This is unusual. Sending your case to our tech team for deeper review." **Tag: #investigation** |

---

## Copy-Paste Responses

### ✅ **WARP Fixed It**
```
Awesome! Your issue was your carrier's DNS resolver, not our server. 
We confirmed our infrastructure is working perfectly from here.

**Going forward:**
- Keep using WARP (it's free + speeds up your whole phone)
- OR switch to a different Wi-Fi network
- OR wait 30 min and retry (DNS cache clears)

Thanks for reporting! 🎯
```

### ✅ **Private Relay Fixed It**
```
Got it! This is an iOS issue with iCloud Private Relay on .sbs domains.

**Two options:**
1. Keep Private Relay disabled for tamincom.store
2. Use Cloudflare WARP instead (free, protects and speeds up)

Either way, you're all set! Let us know if it happens again. 👍
```

### ⚠️ **Still Failing**
```
Hmm, we've tested standard network troubleshooting with no luck. 
I'm escalating this to our engineering team for deeper investigation.

**To help them investigate, can you:**
1. Open Chrome on your phone
2. Press F12 → Console
3. Paste: fetch('https://tamincom.store').catch(e => console.log(e))
4. Screenshot and reply with the error

We'll get back to you within 24 hours! 🔧
```

---

## Status Codes Cheat Sheet

| Code | Meaning | Common Cause | Your Response |
|------|---------|--------------|---|
| `ERR_SSL_PROTOCOL_ERROR` | TLS handshake failed | Carrier/DNS edge issue | WARP test |
| `ERR_NAME_NOT_RESOLVED` | DNS lookup failed | Carrier DNS resolver | WARP test |
| `ERR_CONNECTION_REFUSED` | Server not answering port 443 | Firewall / very rare | Escalate |
| `ERR_CONNECTION_TIMEOUT` | No response after 30s | Network latency | WARP test |
| Works fine | N/A | No issue! | No action |

---

## Red Flags (Escalate Immediately)

```
🚨 ESCALATE if:
   □ Desktop ALSO fails
   □ Multiple users report simultaneously (5+)
   □ Works with WARP but WARP also has TLS errors
   □ Nginx/Reverb container logs show errors
   □ curl fails from support machine
```

---

## Time Log (Track Efficiency)

```
Support Ticket #1234
├─ Start: 14:30
├─ Private Relay test: 2 min ✅
├─ Resolved: YES
└─ End: 14:32 — Speed: ⚡⚡⚡ Fast!

Support Ticket #1235
├─ Start: 14:35
├─ Desktop verification: 1 min
├─ WARP test: 3 min ✅
├─ Resolved: YES
└─ End: 14:39 — Speed: ⚡⚡ Good!

Support Ticket #1236
├─ Start: 14:40
├─ All tests pass: 5 min
├─ Still fails: ⚠️
├─ Escalated: YES
└─ End: 14:45 — Speed: ⏱️ Escalated
```

---

## Weekly Report Template

```
📊 Mobile HTTPS Troubleshooting — Week of March 24-30, 2026

Total tickets: 8
✅ Resolved by Support: 7 (87.5%)
   • Private Relay: 4
   • WARP test: 3

⚠️ Escalated: 1 (12.5%)
   • Reason: Unknown ISP block
   • Status: Under investigation

📈 Average resolution time: 6.3 min
⏰ Fastest: 2 min (Private Relay OFF)
🐢 Slowest: 12 min (multiple retests)

Pattern: Most issues are iOS + Private Relay (58.3%)
Recommendation: Consider .sa domain or notify Apple
```

---

**Last Updated:** March 29, 2026  
**Version:** 1.0  
**Next Review:** April 15, 2026
