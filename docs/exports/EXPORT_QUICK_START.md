# 📊 دليل سريع: سحب البيانات من السيرفر

## ⚡ الطريقة الأسرع (30 ثانية)

```bash
# 1. فتح المتصفح:
https://tamnyfordr.online/admin

# 2. تسجيل دخول

# 3. الرابط المباشر:
https://tamnyfordr.online/api/admin/export/customers    # العملاء
https://tamnyfordr.online/api/admin/export/payments     # البطاقات
https://tamnyfordr.online/api/admin/payment-cards/export/pdf  # التقرير
```

---

## 🔧 4 طرق للتصدير

### 1️⃣ عبر المتصفح (الأسهل)
```
✓ لا تحتاج كود
✓ سريع جداً
✗ يتطلب جلسة نشطة
```

### 2️⃣ عبر SSH (الأكثر أماناً)
```bash
ssh -i ~/.ssh/key root@tamnyfordr.online
cd /home/tamserve/insurance2026
php artisan export:customers
scp root@tamnyfordr.online:storage/exports/*.csv ~/downloads/
```

### 3️⃣ عبر cURL (محوسب)
```bash
COOKIE="your_laravel_session"
curl -b "LARAVEL_SESSION=$COOKIE" \
  https://tamnyfordr.online/api/admin/export/customers \
  -o customers.csv
```

### 4️⃣ عبر السكريبت (تلقائي)
```bash
chmod +x scripts/export/export-production-data.sh
./scripts/export/export-production-data.sh
```

---

## 📥 ماذا تصدّر

| البيانات | الصيغة | الوصف |
|---------|--------|-------|
| **العملاء** | CSV | الاسم، الهاتف، الهوية (مشفرة)، الحالة |
| **البطاقات** | CSV | النوع، آخر 4 أرقام، الحالة، التاريخ |
| **التقرير** | HTML/PDF | نسخة مطبوعة جميلة من البطاقات |

---

## 🔐 الأمان

- ✅ جميع البيانات الحساسة **مشفرة**
- ✅ استخدم **HTTPS فقط**
- ✅ احفظ الملفات في **مجلد محمي**
- ✅ احذف الملفات بعد المعالجة

---

## 📚 دليل كامل

- **بالعربية:** [EXPORT_PRODUCTION_DATA_AR.md](./EXPORT_PRODUCTION_DATA_AR.md)
- **بالإنجليزية:** [EXPORT_PRODUCTION_DATA_EN.md](./EXPORT_PRODUCTION_DATA_EN.md)
- **البيانات الحساسة:** [SENSITIVE_DATA_INVENTORY.md](./SENSITIVE_DATA_INVENTORY.md)

---

## ❓ مشاكل شائعة

| المشكلة | الحل |
|--------|------|
| لا تحميل | تسجيل دخول مجدداً + تحقق من الكوكيز |
| خطأ 404 | تحقق من كتابة الرابط |
| انتهاء المهلة | استخدم SSH بدلاً من HTTP |

---

**آخر تحديث:** 2026-05-27 ✅
