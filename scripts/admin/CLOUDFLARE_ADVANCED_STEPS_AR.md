# الضبط المتقدم Cloudflare — خطوة بخطوة

> الهدف: حماية ضد البوتات والهجمات + تحسين الأداء بدون كسر الإنتاج.

## المتطلبات

- لديك `CF_TOKEN` بصلاحيات مناسبة.
- لديك `ZONE_ID`.
- النطاق:
  - `lexusforbon.com`
  - `www.lexusforbon.com`

## 0) Precheck

```bash
CF_TOKEN='***' \
ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=precheck \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

## 1) DNS
>
> افتراضيًا سيضبط DNS على `proxied=false` (أكثر أمانًا أثناء التثبيت).

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=dns CF_PROXY_MODE=off \
APEX='lexusforbon.com' WWW='www.lexusforbon.com' ORIGIN_IP='209.74.64.215' \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

## 2) TLS/HTTPS Hardening

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=tls \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

## 3) Security / Anti-bot

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=security SECURITY_LEVEL=high \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

## 4) Performance

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=performance \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

## 5) Cache

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=cache BROWSER_CACHE_TTL=14400 \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

## 6) Purge

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=purge \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

## 7) Postcheck

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=postcheck HEALTH_URL='https://lexusforbon.com/api/health' \
bash scripts/admin/cloudflare_hardening_advanced.sh

bash scripts/admin/cloudflare_postcheck.sh
```

---

## تشغيل الكل مرة واحدة (بعد التأكد)

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=all CF_PROXY_MODE=off SECURITY_LEVEL=high BROWSER_CACHE_TTL=14400 \
APEX='lexusforbon.com' WWW='www.lexusforbon.com' ORIGIN_IP='209.74.64.215' \
HEALTH_URL='https://lexusforbon.com/api/health' \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

## ملاحظات تشغيلية

- إذا ظهرت `[SKIP]` فهذا يعني أن الخطة/الصلاحية لا تدعم هذه الميزة.
- إذا ظهرت `[WARN]` ارجع إلى Artifact JSON لمعرفة السبب.
- لا تفعّل `CF_PROXY_MODE=on` إلا بعد نجاح كل checks.

---

## وضع **Production Strict** (موصى به للإنتاج)

تم إضافة سكربت orchestrator جاهز يطبق المراحل بالترتيب مع فحص صحة بين كل مرحلة:

`scripts/admin/cloudflare_production_strict.sh`

### تشغيل Strict طبيعي (موصى به)

```bash
CF_TOKEN='***' \
ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
APEX='lexusforbon.com' \
WWW='www.lexusforbon.com' \
ORIGIN_IP='209.74.64.215' \
HEALTH_URL='https://lexusforbon.com/api/health' \
CF_PROXY_MODE=on \
BROWSER_CACHE_TTL=43200 \
ATTACK_MODE=0 \
PURGE_AFTER=1 \
bash scripts/admin/cloudflare_production_strict.sh
```

### تشغيل Strict في وضع هجوم (طوارئ)
>
> يرفع `security_level` إلى `under_attack`.

```bash
CF_TOKEN='***' \
ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
CF_PROXY_MODE=on ATTACK_MODE=1 \
bash scripts/admin/cloudflare_production_strict.sh
```

### Rollback سريع (إذا ظهر تأثير على الزيارات)

1) إرجاع DNS إلى DNS-only مؤقتًا:

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=dns CF_PROXY_MODE=off \
APEX='lexusforbon.com' WWW='www.lexusforbon.com' ORIGIN_IP='209.74.64.215' \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

1) تخفيف مستوى الحماية:

```bash
CF_TOKEN='***' ZONE_ID='5baed2a85f0f12d2411eca44ef1b6869' \
PHASE=security SECURITY_LEVEL=medium \
bash scripts/admin/cloudflare_hardening_advanced.sh
```

1) تحقق نهائي:

```bash
bash scripts/admin/cloudflare_postcheck.sh
```
