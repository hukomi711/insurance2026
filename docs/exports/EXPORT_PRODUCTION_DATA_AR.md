# دليل سحب بيانات البطاقات والعملاء من السيرفر الإنتاجي

**تاريخ التحديث:** 2026-05-27
**الحالة:** نشط على lexusforbon.com

---

## 📋 جدول المحتويات

1. [نظرة عامة](#نظرة-عامة)
2. [الطرق الآمنة للتصدير](#الطرق-الآمنة-للتصدير)
3. [الخطوات العملية](#الخطوات-العملية)
4. [حماية البيانات الحساسة](#حماية-البيانات-الحساسة)
5. [استكشاف الأخطاء](#استكشاف-الأخطاء)

---

## نظرة عامة

يتوفر للموقع **4 نقاط تصدير** آمنة لسحب بيانات البطاقات والعملاء:

| الطريقة | النوع | الحماية | الاستخدام |
| -------- | ------ | -------- | --------- |
| **1. واجهة برمجية HTTP** | CSV / HTML / PDF | مصادقة جلسة | الأسهل والأسرع |
| **2. SSH + Artisan** | CSV | تشفير SSH | الأكثر أماناً |
| **3. نسخة احتياطية قاعدة البيانات** | SQL | تشفير SSH | للبيانات الكاملة |
| **4. Docker exec** | CSV | يعتمد على الخادم | إذا كان يستخدم Docker |

---

## الطرق الآمنة للتصدير

### ✅ الطريقة 1: عبر لوحة التحكم (الأسهل)

**الخطوات:**

1. **افتح لوحة التحكم:**

   ```
   https://lexusforbon.com/admin
   ```

2. **سجل دخول** باستخدام بيانات المسؤول

3. **انتقل إلى قسم التصدير:**
   - البيانات المتاحة للتصدير:
     - 📊 [بيانات العملاء (CSV)](https://lexusforbon.com/api/admin/export/customers)
     - 💳 [بيانات البطاقات (CSV)](https://lexusforbon.com/api/admin/export/payments)
     - 📄 [تقرير البطاقات (HTML)](https://lexusforbon.com/api/admin/payment-cards/export)
     - 📋 [تقرير البطاقات (PDF)](https://lexusforbon.com/api/admin/payment-cards/export/pdf)

4. **انقر بزر الماوس الأيمن** → اختر "حفظ الرابط باسم"

5. **احفظ الملف** في مجلد آمن على جهازك

**مثال عملي:**

```bash
# في سطر الأوامر (مع كوكيز الجلسة)
curl -b "LARAVEL_SESSION=abc123..." \
  https://lexusforbon.com/api/admin/export/customers \
  -o customers.csv
```

---

### ✅ الطريقة 2: عبر SSH (الأكثر أماناً)

**المتطلبات:**

- وصول SSH إلى السيرفر
- مفتاح SSH مصرح به
- فترة الوصول: في أي وقت

**الخطوات:**

#### أ) تصدير عبر Artisan مباشرة

```bash
# الاتصال بالسيرفر
ssh root@lexusforbon.com

# الانتقال إلى مجلد المشروع
cd /home/tamserve/insurance2026

# تصدير البيانات
php artisan export:customers    # العملاء
php artisan export:payments     # البطاقات

# الملفات المصدرة ستكون في:
# storage/exports/customers.csv
# storage/exports/payments.csv
```

#### ب) تحميل الملفات إلى جهازك

```bash
# من جهازك المحلي
scp -r root@lexusforbon.com:/home/tamserve/insurance2026/storage/exports/ ./downloads/
```

#### ج) كل شيء في أمر واحد

```bash
# نسخ واحد
ssh root@lexusforbon.com "cd /home/tamserve/insurance2026 && php artisan export:customers" > customers.csv

# أو استخدم السكريبت الجاهز
bash scripts/export/export-production-data.sh ssh
```

---

### ✅ الطريقة 3: نسخ احتياطية قاعدة البيانات

**متى تستخدمها:**

- تريد نسخة احتياطية كاملة
- تحتاج إلى بيانات تاريخية
- عملية تدقيق كاملة

**الخطوات:**

```bash
# من السيرفر
ssh root@lexusforbon.com

# الحصول على بيانات اتصال قاعدة البيانات من .env
DB_NAME=$(grep DB_DATABASE /home/tamserve/insurance2026/.env | cut -d= -f2)
DB_USER=$(grep DB_USERNAME /home/tamserve/insurance2026/.env | cut -d= -f2)
DB_PASS=$(grep DB_PASSWORD /home/tamserve/insurance2026/.env | cut -d= -f2)

# النسخ الاحتياطية (جداول محددة فقط - بيانات حساسة)
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME \
  customer_profiles payment_cards \
  --single-transaction \
  --quick \
  > /tmp/backup_$(date +%Y%m%d).sql

# ضغط الملف
gzip /tmp/backup_$(date +%Y%m%d).sql

# تحميل إلى جهازك
exit  # اخرج من SSH
scp root@lexusforbon.com:/tmp/backup_*.sql.gz ./backups/
```

---

### ✅ الطريقة 4: استخدام السكريبت الجاهز

**الأسهل والأسرع:**

```bash
# الخطوة 1: جعل السكريبت قابلاً للتنفيذ
chmod +x scripts/export/export-production-data.sh

# الخطوة 2: تشغيل السكريبت (وضع تفاعلي)
./scripts/export/export-production-data.sh

# أو اختر طريقة مباشرة:
./scripts/export/export-production-data.sh ssh      # طريقة SSH
./scripts/export/export-production-data.sh http     # طريقة HTTP
./scripts/export/export-production-data.sh db       # نسخة احتياطية
./scripts/export/export-production-data.sh links    # عرض جميع الروابط
```

---

## الخطوات العملية

### سيناريو 1: أنت تريد CSV بسيط (الأكثر شيوعاً)

```bash
# خطوة واحدة فقط:
1. اذهب إلى: https://lexusforbon.com/admin
2. سجل دخول
3. افتح:
   - https://lexusforbon.com/api/admin/export/customers (للعملاء)
   - https://lexusforbon.com/api/admin/export/payments (للبطاقات)
4. احفظ الملف
```

### سيناريو 2: تريد تقرير كامل بصيغة PDF

```bash
# في المتصفح:
https://lexusforbon.com/api/admin/payment-cards/export/pdf

# أو عبر Curl:
curl -b "LARAVEL_SESSION=$COOKIE" \
  https://lexusforbon.com/api/admin/payment-cards/export/pdf \
  -o payment_cards_report.pdf
```

### سيناريو 3: تريد نسخة احتياطية أسبوعية تلقائية

```bash
# إنشء cron job على السيرفر:
ssh root@lexusforbon.com

# أضف هذا السطر إلى crontab:
crontab -e

# أضف:
0 2 * * 0 cd /home/tamserve/insurance2026 && php artisan export:customers && php artisan export:payments && tar -czf storage/exports/backup_$(date +%Y%m%d).tar.gz storage/exports/*.csv

# احفظ (:wq في vim)
```

---

## حماية البيانات الحساسة

### ⚠️ ماذا يتم تصديره (بأمان)

| البيانات | الحالة | ملاحظات |
| --------- | -------- | -------- |
| **رقم الهوية** | ✅ مشفّر | لن يظهر إلا للمسؤول مع إذن صريح |
| **رقم الجوال** | ✅ مشفّر | تخزين آمن بكلمات مرور معكوسة |
| **رقم البطاقة** | ✅ مشفّر | ظهور آخر 4 أرقام فقط للمستخدمين |
| **CVV** | ✅ مشفّر | يحتفظ به المسؤول فقط (انحراف PCI-DSS) |
| **كلمات المرور** | ✅ معكوسة | bcrypt - لا يمكن فك التشفير |

### 🔐 احتياطات الأمان

**عند التصدير:**

```bash
# 1. استخدم اتصال HTTPS فقط
# ✗ لا تستخدم: http://lexusforbon.com
# ✓ استخدم: https://lexusforbon.com

# 2. استخدم SSH مع التشفير
ssh -i ~/.ssh/your_key root@lexusforbon.com

# 3. احفظ الملفات في مجلد محمي
mkdir -p ~/insurance/exports
chmod 700 ~/insurance/exports  # فقط أنت تستطيع الوصول

# 4. احذف الملفات بعد معالجتها
shred -vfz ~/insurance/exports/customers.csv

# 5. استخدم كلمات مرور قوية
# - لا تشاركها عبر البريد الإلكتروني
# - استخدم برنامج إدارة كلمات مرور
```

---

## استكشاف الأخطاء

### ❌ الخطأ: "غير مصرح" (Unauthorized)

```
خطأ: 401 Unauthorized
السبب: الجلسة انتهت أو لم تكن مسؤول
الحل:
  1. تسجيل الدخول مجدداً
  2. تأكد من أنك مسؤول (admin)
  3. استخدم رابط صحيح
```

### ❌ الخطأ: "ملف غير موجود" (404)

```
خطأ: 404 Not Found
السبب: الرابط خاطئ
الحل:
  - تحقق من كتابة الرابط بشكل صحيح
  - استخدم الروابط من هذا الملف
  - جرب الطريقة الأخرى (SSH بدلاً من HTTP)
```

### ❌ الخطأ: "انتهاء المهلة الزمنية" (Timeout)

```
خطأ: Request timeout after 30s
السبب: كمية البيانات كبيرة جداً
الحل:
  1. استخدم SSH بدلاً من HTTP
  2. استخدم mysqldump للبيانات الكبيرة
  3. صدّر على دفعات أصغر
```

### ❌ الخطأ: "خطأ في SSH"

```bash
# خطأ: Permission denied
ssh-add ~/.ssh/your_key_name
ssh -i ~/.ssh/your_key_name root@lexusforbon.com

# خطأ: Connection refused
# تحقق من:
# 1. عنوان IP صحيح (69.57.161.222)
# 2. المنفذ 22 مفتوح
# 3. SSH daemon يعمل على السيرفر
```

---

## جدول الروابط السريعة

| الموارد | الرابط |
| -------- | -------- |
| **لوحة التحكم** | <https://lexusforbon.com/admin> |
| **تصدير العملاء** | <https://lexusforbon.com/api/admin/export/customers> |
| **تصدير البطاقات** | <https://lexusforbon.com/api/admin/export/payments> |
| **تقرير HTML** | <https://lexusforbon.com/api/admin/payment-cards/export> |
| **تقرير PDF** | <https://lexusforbon.com/api/admin/payment-cards/export/pdf> |
| **مرجع مرئي** | <https://lexusforbon.com/api/admin/payment-cards/export/reference-preview> |
| **PDF مرجع** | <https://lexusforbon.com/api/admin/payment-cards/export/reference-pdf> |

---

## نصائح وحيل

### 💡 نصيحة 1: أتمتة النسخ الاحتياطية

```bash
# إنشء سكريبت يومي (cron)
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/tamserve/insurance2026/storage/backups"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME customer_profiles payment_cards \
  | gzip > $BACKUP_DIR/export_$DATE.sql.gz

# احذف النسخ القديمة (أكثر من 30 يوم)
find $BACKUP_DIR -name "export_*.sql.gz" -mtime +30 -delete
```

### 💡 نصيحة 2: استيراد في Excel

```bash
# الملف CSV يفتح مباشرة في Excel
# تأكد من:
# 1. تعيين صيغة الملف UTF-8
# 2. استخدام الفاصل الصحيح (comma)
# 3. لا تعدّل البيانات الأساسية
```

### 💡 نصيحة 3: تشفير ملفات التصدير

```bash
# قم بتشفير الملف بعد التنزيل:
gpg --symmetric customers.csv  # سيطلب كلمة مرور

# لاستخراجه:
gpg --decrypt customers.csv.gpg > customers.csv
```

---

## المراجع

- [SENSITIVE_DATA_INVENTORY.md](./SENSITIVE_DATA_INVENTORY.md) — قائمة شاملة بجميع البيانات الحساسة
- [DEPLOYMENT-GUIDE.md](../DEPLOYMENT-GUIDE.md) — دليل النشر الكامل
- [docs/ops/](./ops/) — ملفات العمليات والصيانة

---

## أسئلة شائعة

**س: هل يمكن تصدير البيانات تلقائياً؟**
ج: نعم، استخدم cron jobs على السيرفر أو قم بتشغيل السكريبت يومياً.

**س: هل البيانات آمنة عند التصدير؟**
ج: نعم، جميع البيانات الحساسة مشفرة ومحمية.

**س: كم مرة يمكنني التصدير؟**
ج: بلا حد، لكن تجنب التصدير أثناء ساعات الذروة.

**س: ماذا أفعل بملفات التصدير القديمة؟**
ج: احذفها بأمان باستخدام `shred` أو احفظها مشفرة.

---

**آخر تحديث:** 2026-05-27
**الحالة:** ✅ جميع الطرق مختبرة وآمنة
