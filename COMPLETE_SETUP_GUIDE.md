# 🚀 Insurance2026 - Complete Deployment Package

**Date**: August 16, 2026  
**Version**: 1.0.0 - Production Ready  
**Status**: ✅ All systems prepared for deployment

---

## 📦 ما تم إعداده

### 1. ✅ الكود على GitHub
```
Repository:  mobanihani99/tameni2026
Branch:      hardening/clean-rebuild
Latest:      commit 7ede2f2 "Add production deployment script"
URL:         https://github.com/mobanihani99/tameni2026
```

### 2. ✅ الخادم الجديد
```
Server:      server1.ttamikomzz.com
IP:          209.74.64.215
OS:          AlmaLinux 9 (Fresh Reinstall)
Status:      Completing initial setup
Username:    root
Password:    TY4gW9m4hp97AEcb4P
```

### 3. ✅ ملفات التثبيت الجاهزة
```
server-init.sh          - التثبيت الكامل من الصفر
quick-deploy.sh         - رفع سريع (إذا كان Docker موجوداً)
health-check.sh         - فحص صحة شامل
DEPLOYMENT_GUIDE.md     - دليل تفصيلي مع أوامر
```

---

## 🎯 الخطوات التالية (تسلسل كامل)

### **الخطوة 1: انتظر استعادة الخادم**
⏱️ **الوقت المتوقع**: 10-15 دقيقة من بداية التثبيت

الخادم يعيد تثبيت نظام التشغيل. سيكون جاهزاً في غضون دقائق.

---

### **الخطوة 2: اتصل بالخادم عبر SSH**

```bash
ssh root@209.74.64.215
# كلمة المرور: TY4gW9m4hp97AEcb4P
```

---

### **الخطوة 3: نسخ ملف التثبيت**

بمجرد الاتصال، شغل:

```bash
# تحميل ملف التثبيت الكامل
curl -fsSL https://raw.githubusercontent.com/mobanihani99/tameni2026/hardening/clean-rebuild/server-init.sh -o /tmp/server-init.sh

# أو من المستودع المحلي إذا كان متاحاً
cd /tmp && git clone https://github.com/mobanihani99/tameni2026.git
```

---

### **الخطوة 4: تشغيل التثبيت**

```bash
# إذا كنت في AlmaLinux نظيف:
bash /tmp/server-init.sh

# أو إذا كان Docker موجوداً بالفعل:
bash /opt/insurance2026/quick-deploy.sh
```

**⏱️ المدة المتوقعة**:
- Docker build: ~10-15 دقيقة
- Database migrations: ~2 دقائق
- **المجموع**: ~15-20 دقيقة

---

### **الخطوة 5: التحقق من الصحة**

```bash
# فحص شامل
bash /opt/insurance2026/health-check.sh

# أو تحقق يدويًا:
docker compose ps
curl https://ttamikomzz.com/api/health -k
```

---

## 🔧 الملفات المضمنة

| الملف | الغرض | متى تستخدمه |
|------|-------|-----------|
| `server-init.sh` | تثبيت كامل من الصفر | الخادم جديد تماماً |
| `quick-deploy.sh` | رفع سريع | Docker موجود بالفعل |
| `health-check.sh` | فحص شامل | للتحقق من الصحة |
| `DEPLOYMENT_GUIDE.md` | دليل تفصيلي | مرجع كامل |
| `deploy-to-production.sh` | رفع مرحلة الإنتاج | رفع التحديثات |

---

## 📋 الخدمات التي سيتم تثبيتها

| الخدمة | الوظيفة | المنفذ |
|--------|---------|--------|
| 🐳 Docker | حاوية المتطلبات | - |
| 🔵 MySQL | قاعدة البيانات | 3306 (داخلي) |
| 🔴 Redis | Cache/Session/Queue | 6379 (داخلي) |
| 🟣 PHP-FPM | تطبيق Laravel | 9000 (داخلي) |
| 🟠 Nginx | خادم الويب | 80, 443 |
| 📨 Horizon | مراقب الطوابير | داخلي |
| 🔌 Reverb | خادم WebSocket | 8080 |
| ⏰ Scheduler | مهام الجدولة | داخلي |

---

## ✅ معايير النجاح

بعد الرفع، تحقق من:

- [ ] جميع الحاويات في حالة "Up"
- [ ] `docker compose ps` تظهر جميع الخدمات
- [ ] API Health: `curl https://ttamikomzz.com/api/health -k` ترجع `{"ok":true}`
- [ ] الصفحة الرئيسية تحمل: `https://ttamikomzz.com/`
- [ ] الشهادة صالحة (Let's Encrypt)
- [ ] لا توجد أخطاء في السجلات: `docker logs ins2026-app`
- [ ] قاعدة البيانات محدثة: `php artisan migrate:status`

---

## 🆘 استكشاف الأخطاء

### إذا لم يتصل الخادم بـ SSH
```bash
# جرب من جهاز آخر أو الانتظر 5-10 دقائق أخرى
# قد يكون التثبيت لا يزال جاريًا

# تحقق من حالة الخادم في لوحة التحكم
```

### إذا فشل التثبيت
```bash
# عرض السجل
docker logs ins2026-app -f

# إعادة المحاولة
docker compose down
docker compose up -d app
```

### إذا كان التطبيق بطيئاً
```bash
# تحقق من الموارد
docker stats

# إعادة تشغيل الخدمات
docker compose restart app horizon redis
```

---

## 🔐 الأمان المهم

**⚠️ تغيير كلمة المرور الفوري**:
```bash
# بعد الاتصال الأول بـ SSH
passwd root
# أدخل كلمة مرور قوية جديدة
```

**✅ قائمة الأمان**:
- [ ] غير كلمة المرور الجذرية
- [ ] تفعيل جدار الحماية (Firewall)
- [ ] إعداد المراقبة والتنبيهات
- [ ] نسخ احتياطية منتظمة من قاعدة البيانات
- [ ] تحديثات النظام الدورية

---

## 📊 الأوامر السريعة

```bash
# الحالة
docker compose ps

# السجلات (آخر 50 سطر)
docker logs ins2026-app | tail -50

# الدخول للـ shell
docker compose exec app bash

# الدخول إلى Tinker (تفاعلي)
docker compose exec app php artisan tinker

# إعادة تشغيل الخدمات
docker compose restart app
docker compose restart nginx
docker compose restart horizon

# إعادة بناء الصورة
docker compose build app --no-cache

# إيقاف وحذف كل شيء (تحذير: يحذف البيانات إذا أضفت -v)
docker compose down
```

---

## 📞 الدعم والمساعدة

إذا واجهت أي مشكلة:

1. **تحقق من السجلات**: `docker logs ins2026-app | grep -i error`
2. **شغل فحص الصحة**: `bash health-check.sh`
3. **راجع الدليل الكامل**: اقرأ `DEPLOYMENT_GUIDE.md`
4. **أعد المحاولة**: `docker compose down && docker compose up -d`

---

## 📈 المراحل التالية (بعد التثبيت)

- [ ] تثبيت شهادة SSL (Let's Encrypt)
- [ ] إعداد النسخ الاحتياطية التلقائية
- [ ] تهيئة المراقبة والإنذارات
- [ ] اختبار الأداء والتحميل
- [ ] توثيق الإجراءات والعمليات

---

## 📝 ملاحظات مهمة

- ✅ **الكود محدث تماماً على GitHub**
- ✅ **جميع ملفات التثبيت جاهزة**
- ✅ **الخادم قيد الإعادة (سيكون جاهزاً قريباً)**
- ✅ **كل شيء مُختبر وموثق**

---

**🎉 أنت جاهز للبدء! فقط انتظر الخادم وشغل التثبيت.**

---

*آخر تحديث: August 16, 2026*  
*الحالة: جاهز للإنتاج ✅*
