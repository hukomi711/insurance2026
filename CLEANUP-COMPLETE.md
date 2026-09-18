# ✅ تنظيف السيرفر القديم والتحضير للـ VPS الجديد

**التاريخ:** 2026-09-18  
**الحالة:** ✅ اكتمل التنظيف  
**الخطوة التالية:** البدء بنشر VPS جديد

---

## 📋 ما تم إنجازه

### 1. تنظيف مراجع السيرفر القديم

#### ✅ ملف النشر القديم

- **الملف:** `deploy/admin/deploy-local-to-vps-docker.sh`
- **الإجراء:** إضافة تحذير ⚠️ وتحديث الـ defaults إلى `DEPRECATED_CHANGE_ME`
- **السبب:** منع الاستخدام العرضي للسيرفر القديم (203.161.38.43)

#### ✅ بيانات الاختبار

- **الملف:** `tests/Feature/Admin/CustomerReactivationNotificationTest.php`
- **الإجراء:** استبدال IP الحقيقي `203.161.38.43` بـ `192.0.2.1` (IP اختبار من RFC 5737)
- **السبب:** عزل بيانات الاختبار عن السيرفرات الفعلية

### 2. إعداد النشر الجديد

#### ✅ وثائق شاملة

- **ملف جديد:** `DEPLOYMENT-TO-NEW-VPS.md`
- **المحتوى:**
  - ✅ متطلبات ما قبل النشر
  - ✅ خطوات الإعداد المحلي
  - ✅ إعدادات متغيرات البيئة
  - ✅ استخراج البيانات من السيرفر القديم (اختياري)
  - ✅ تنفيذ النشر
  - ✅ التحقق من النشر
  - ✅ استكشاف الأخطاء الشائعة

#### ✅ ملفات النشر الموجودة

```
✅ deploy/new-server/deploy.sh              — السكريبت الرئيسي (جاهز)
✅ deploy/new-server/.env.production.template — الإعدادات الثابتة (جاهز)
✅ deploy/new-server/RUNBOOK.md              — دليل التشغيل (موجود)
✅ deploy/new-server/README.md               — التعليمات (موجود)
✅ docker-compose.yml                        — الخدمات (جاهز)
✅ Dockerfile                                — صورة Docker (جاهز)
```

---

## 🚀 الخطوات التالية

### المرحلة الأولى: التحضير المحلي

```bash
# 1. التحقق من حالة المشروع
cd /path/to/insurance2026
git status
git log --oneline -5

# 2. التأكد من الفرع الصحيح
git checkout hardening/clean-rebuild  # أو الفرع المطلوب
```

### المرحلة الثانية: إعداد السيرفر الجديد

```bash
# متطلبات السيرفر الجديد:
# 1. AlmaLinux 9
# 2. Docker و Docker Compose مثبتة
# 3. SSH key-based auth مفعّل
# 4. DNS A record موجه للـ IP الجديد
```

### المرحلة الثالثة: النشر

```bash
# 1. تعيين متغيرات البيئة
export INS_SERVER_IP="YOUR_NEW_VPS_IP"
export INS_DOMAIN="your-domain.com"
export INS_REPO_URL="git@github.com:YOUR_ORG/insurance2026.git"
export INS_BRANCH="hardening/clean-rebuild"

# 2. تشغيل النشر
bash deploy/new-server/deploy.sh

# 3. اتباع التعليمات (إضافة Deploy Key إذا لزم)
```

---

## 📌 ملاحظات مهمة

### السيرفر القديم (203.161.38.43)

- ⚠️ **غير متاح حاليًا** (تم قطع الاتصال أثناء محاولة الإصلاح)
- 📦 إذا كنت تريد استخراج البيانات: جرب إعادة الاتصال يدويًا
- 🔧 يمكنك الاحتفاظ به كنسخة احتياطية أو حذفه بعد نجاح النشر الجديد

### البيانات الحساسة

- 🔐 كلمات المرور و secrets **لا تُحفظ** في Git
- 🔐 استخدم `docker/secrets/` للـ secrets في النشر
- 🔐 اتبع إرشادات `.env.production.template` لتعيين القيم

### نسخ احتياطية من البيانات

```bash
# إذا كنت تريد استيراد البيانات من السيرفر القديم:
1. استخراج قاعدة البيانات (إن أمكن)
2. حفظها في `deploy/new-server/insurance2026.sql.gz`
3. تعيين: export INS_DB_DUMP="deploy/new-server/insurance2026.sql.gz"
4. تشغيل النشر (سيستورد البيانات تلقائيًا)
```

---

## 🔍 التحقق السريع

بعد النشر الناجح:

```bash
# الوصول للسيرفر
ssh -i ~/.ssh/id_ed25519 root@YOUR_NEW_VPS_IP

# التحقق من الخدمات
cd /opt/insurance2026
docker compose ps

# اختبار الاتصال
curl -I https://your-domain.com/
```

---

## 📞 المساعدة والمراجع

- **الملف الرئيسي:** [DEPLOYMENT-TO-NEW-VPS.md](../DEPLOYMENT-TO-NEW-VPS.md)
- **سكريبت النشر:** [deploy/new-server/deploy.sh](../deploy/new-server/deploy.sh)
- **دليل التشغيل:** [deploy/new-server/RUNBOOK.md](../deploy/new-server/RUNBOOK.md)
- **استراتيجية النشر:** [docs/deployment/GITHUB-DEPLOYMENT-STRATEGY.md](../docs/deployment/GITHUB-DEPLOYMENT-STRATEGY.md)

---

**حالة المشروع:** ✅ جاهز للنشر على VPS جديد  
**آخر تحديث:** 2026-09-18
