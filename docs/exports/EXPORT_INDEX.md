# 📖 فهرس التصدير والنسخ الاحتياطية

**نسخة:** 1.0
**التاريخ:** 2026-05-27
**اللغات:** 🇸🇦 عربي | 🇬🇧 English

---

## 📋 ملفات التوثيق الموجودة

### 🚀 البدء السريع

- **[EXPORT_SUMMARY.md](./EXPORT_SUMMARY.md)** ⭐ *ابدأ من هنا*
  - ملخص شامل لجميع الطرق
  - 4 طرق للتصدير مع أمثلة
  - جدول المقارنة
  - الأسئلة الشائعة

### ⚡ دليل سريع (30 ثانية)

- **[EXPORT_QUICK_START.md](./EXPORT_QUICK_START.md)**
  - الطرق الـ 4 في صفحة واحدة
  - الروابط المباشرة
  - مشاكل شائعة

### 📚 دليل كامل بالعربية

- **[EXPORT_PRODUCTION_DATA_AR.md](./EXPORT_PRODUCTION_DATA_AR.md)**
  - شرح مفصل لكل طريقة (350 سطر)
  - خطوات عملية كاملة
  - 4 سيناريوهات فعلية
  - نصائح الأمان
  - استكشاف الأخطاء الشامل
  - أوامر مع شرح كامل

### 📚 دليل كامل بالإنجليزية

- **[EXPORT_PRODUCTION_DATA_EN.md](./EXPORT_PRODUCTION_DATA_EN.md)**
  - نفس المحتوى العربي بالإنجليزية
  - Quick Start
  - All 5 methods explained
  - Security & Best practices

### 🔐 قائمة البيانات الحساسة

- **[SENSITIVE_DATA_INVENTORY.md](./SENSITIVE_DATA_INVENTORY.md)**
  - تصنيف جميع البيانات
  - ما هو مشفر وكيف
  - جداول الأمان
  - المهن الجاري تنفيذها

---

## 🛠️ السكريبت الرئيسي

### 📄 scripts/export/export-production-data.sh (12 KB)

**سكريبت Bash آلي لسحب البيانات**

**الاستخدام:**

```bash
# جعل السكريبت قابل للتنفيذ
chmod +x scripts/export/export-production-data.sh

# وضع تفاعلي (أسهل)
./scripts/export/export-production-data.sh

# طرق محددة
./scripts/export/export-production-data.sh ssh         # SSH
./scripts/export/export-production-data.sh http        # HTTP
./scripts/export/export-production-data.sh db          # قاعدة البيانات
./scripts/export/export-production-data.sh docker      # Docker
./scripts/export/export-production-data.sh links       # عرض الروابط فقط
```

**ما يفعله:**

- ✅ تنزيل البيانات عبر SSH
- ✅ تنزيل البيانات عبر HTTP
- ✅ نسخ احتياطية من قاعدة البيانات
- ✅ عرض جميع الروابط المتاحة
- ✅ معالجة الأخطاء تلقائياً

---

## 🌐 الروابط المباشرة للتصدير

### في المتصفح (مع تسجيل دخول)

```
1. بيانات العملاء (CSV):
   https://lexusforbon.com/api/admin/export/customers

2. بيانات البطاقات (CSV):
   https://lexusforbon.com/api/admin/export/payments

3. تقرير البطاقات (HTML):
   https://lexusforbon.com/api/admin/payment-cards/export

4. تقرير البطاقات (PDF):
   https://lexusforbon.com/api/admin/payment-cards/export/pdf

5. مرجع مرئي (HTML):
   https://lexusforbon.com/api/admin/payment-cards/export/reference-preview

6. مرجع مرئي (PDF):
   https://lexusforbon.com/api/admin/payment-cards/export/reference-pdf
```

---

## 🚀 5 طرق للتصدير

### ✅ الطريقة 1: المتصفح (الأسهل)

```
⏱️ الوقت: 30 ثانية
🔐 الأمان: متوسط
📖 قراءة: EXPORT_QUICK_START.md
```

### ✅ الطريقة 2: SSH (الأكثر أماناً)

```
⏱️ الوقت: 2 دقيقة
🔐 الأمان: عالي
📖 قراءة: EXPORT_PRODUCTION_DATA_AR.md
```

### ✅ الطريقة 3: cURL (محوسبة)

```
⏱️ الوقت: 1 دقيقة
🔐 الأمان: عالي
📖 قراءة: EXPORT_PRODUCTION_DATA_AR.md
```

### ✅ الطريقة 4: قاعدة البيانات (النسخة الكاملة)

```
⏱️ الوقت: 5 دقائق
🔐 الأمان: عالي جداً
📖 قراءة: EXPORT_PRODUCTION_DATA_AR.md
```

### ✅ الطريقة 5: السكريبت الآلي

```
⏱️ الوقت: 3 دقائق
🔐 الأمان: عالي
📖 قراءة: EXPORT_SUMMARY.md
```

---

## 🎯 خريطة الاختيار

```
هل تريد تصدير سريع؟
  ├─ نعم: استخدم المتصفح (30 ثانية)
  └─ لا: تابع...

هل لديك معرفة بـ SSH؟
  ├─ نعم: استخدم SSH (الأكثر أماناً)
  └─ لا: استخدم السكريبت

هل تريد نسخة احتياطية كاملة؟
  ├─ نعم: mysqldump
  └─ لا: اختر التصدير العادي

هل تريد أتمتة يومية؟
  ├─ نعم: قم بإعداد cron job
  └─ لا: تصدير يدوي
```

---

## 📊 جدول البيانات المصدرة

| البيانات | النوع | الوصف | الأمان |
| --------- | ------ | ------- | -------- |
| **العملاء** | CSV | الاسم، الجوال، الهوية، الحالة | مشفرة ✅ |
| **البطاقات** | CSV | النوع، آخر 4، الحالة | معرّف بأمان |
| **التقرير** | HTML/PDF | نسخة مرئية جميلة | مختصة |
| **Backup** | SQL | قاعدة البيانات كاملة | مضغوطة |

---

## 🔒 ملاحظات الأمان المهمة

### البيانات المشفرة (✅ آمنة)

- رقم الهوية: مشفر + معكوس
- الجوال: مشفر + معكوس
- البريد: مشفر
- CVV: مشفر (مخزّن دائماً)
- كلمات المرور: bcrypt

### اتصالات آمنة فقط

- ✅ HTTPS (بدون HTTP)
- ✅ SSH مع مفاتيح
- ✅ كوكيز محمية

### بعد التصدير

- 🗑️ احفظ الملفات برقم مرور
- 🗑️ شفّر الملفات الحساسة
- 🗑️ احذف بعد المعالجة

---

## ❓ الأسئلة الأكثر شيوعاً

| السؤال | الإجابة | الملف |
| ------- | -------- | ------ |
| كيف أبدأ؟ | اقرأ EXPORT_SUMMARY.md | 👈 |
| أسرع طريقة؟ | المتصفح (30 ثانية) | QUICK_START |
| الطريقة الآمنة؟ | SSH مع مفاتيح | AR/EN |
| الطريقة الآلية؟ | cron job + SSH | AR |
| هل البيانات آمنة؟ | نعم، مشفرة بالكامل | INVENTORY |

---

## 📁 هيكل الملفات الكامل

```
insurance2026/
├── docs/
│   ├── EXPORT_SUMMARY.md              ⭐ ابدأ هنا
│   ├── EXPORT_QUICK_START.md          ⚡ 30 ثانية
│   ├── EXPORT_PRODUCTION_DATA_AR.md   📖 عربي كامل
│   ├── EXPORT_PRODUCTION_DATA_EN.md   📖 English full
│   ├── SENSITIVE_DATA_INVENTORY.md    🔐 حساس
│   └── EXPORT_INDEX.md                📋 هذا الملف
├── scripts/
│   └── export-production-data.sh      🛠️ السكريبت الآلي
└── ... (الملفات الأخرى)
```

---

## 🎬 ابدأ الآن في 3 خطوات

### خطوة 1: اختر طريقة

```
المتصفح → الأسرع
SSH     → الأكثر أماناً
Script  → الأكثر آلية
```

### خطوة 2: اقرأ الدليل

```
EXPORT_SUMMARY.md (2 دقيقة)
أو
EXPORT_QUICK_START.md (30 ثانية)
```

### خطوة 3: نفّذ

```
اتبع الخطوات في الدليل
وابدأ التصدير الآن!
```

---

## 🔗 الروابط السريعة

- 🏠 [الرئيسية](../../README.md)
- 📊 [Dashboard](https://lexusforbon.com/admin)
- 🔐 [بيانات حساسة](./SENSITIVE_DATA_INVENTORY.md)
- 📝 [ملاحظات الأمان](./SECURITY.md)

---

## ✅ قائمة التحقق

- [ ] اقرأ EXPORT_SUMMARY.md
- [ ] اختر طريقة التصدير المناسبة
- [ ] اقرأ الدليل الكامل للطريقة المختارة
- [ ] حضّر بيانات الوصول (كوكي أو مفتاح SSH)
- [ ] نفّذ الخطوات
- [ ] تحقق من الملفات المحملة
- [ ] احفظ الملفات بأمان
- [ ] احذف النسخ المؤقتة

---

**تم الانتهاء من الإعداد ✅**

**التاريخ:** 2026-05-27
**الحالة:** جاهز للاستخدام
**اللغات:** عربي 🇸🇦 + إنجليزي 🇬🇧
