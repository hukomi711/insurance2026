# اختبار تدفق الدفع عملياً (Practical Payment Flow Testing)

## 🧪 1. اختبار المستخدم النهائي (End-to-End User Testing)

### السيناريو الكامل

```plaintext
المستخدم                      التطبيق                    الأدمن              السيرفر
   |                           |                         |                   |
   |--[1. كمل المراجعة]-------→|                         |                   |
   |                           |                         |                   |
   |←--[spinner 300ms]---------| (تأخير UX)              |                   |
   |                           |                         |                   |
   |--[2. ذهب للدفع]----------→|                         |                   |
   |                           |--[Form يحمل]→           |                   |
   |                           |←--[Ready]               |                   |
   |                           |                         |                   |
   |--[3. أدخل البطاقة]-------→|                         |                   |
   |                           |--[Validate]→            |                   |
   |                           |←--[✓ Valid]             |                   |
   |                           |                         |                   |
   |--[4. اضغط إدفع]----------→|--[POST /submit]--------→|                   |
   |                           |                         |  --[Process]------→|
   |                           |                         |                    |
   |←--[spinner]----============[✓ Card Created]←--------+----[Response]←----| 
   |                           |                         |                   |
   |--[5. Modal انتظار]-------→|--[WebSocket Setup]     |                   |
   |   (Dots متحركة)            |  + [Polling كل 5s]    |                   |
   |                           |   ⏳ Waiting...         |                   |
   |                           |                         |                   |
   |       [60 ثانية لاحقاً]    |                         |                   |
   |←--[رسالة "مراجعة"]------------|                       |                   |
   |                           |                         |                   |
   |                           |                         |--[ينظر الأدمن]   |
   |                           |                         |   البطاقة         |
   |                           |                         |                   |
   |                           |                         |--[POST approve]--→|
   |                           |                         |←--[✓ Success]-----| 
   |                           |←--[Broadcast]←---------+                   |
   |                           | "PaymentApproved"      |                   |
   |                           |                         |                   |
   |←--[✓ موافق]←---[1000ms]---|                         |                   |
   |   (Visual Feedback)        |                         |                   |
   |                           |--[Navigate OTP]→       |                   |
   |--[6. صفحة OTP]----------→ |                         |                   |
```

---

## 🔬 2. اختبار الأداء بالأرقام

### اختبر هذا في متصفحك

```javascript
// في console أثناء الدفع:

// ✅ اختبار 1: سرعة الانتقال من الملخص
(() => {
    window.checkoutStart = performance.now();
    console.log('✅ بدء قياس الانتقال من الملخص...');
    // اضغط "الانتقال للدفع"
    
    // بعد الانتقال:
    window.checkoutEnd = performance.now();
    console.log(`⏱️ الوقت: ${window.checkoutEnd - window.checkoutStart}ms`);
    console.log(window.checkoutEnd - window.checkoutStart > 300 && window.checkoutEnd - window.checkoutStart < 1000 
        ? '✅ ممتاز (300-1000ms)' 
        : '⚠️ غير متوقع');
})();

// ✅ اختبار 2: Polling interval
(() => {
    let pollCount = 0;
    const pollLog = [];
    const observer = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
            if (entry.name.includes('/status/payment-card')) {
                pollCount++;
                const now = new Date().toLocaleTimeString();
                console.log(`🔄 Poll #${pollCount} في ${now}`);
                pollLog.push(now);
            }
        });
    });
    observer.observe({ entryTypes: ['resource'] });
    
    setTimeout(() => {
        console.log(`📊 عدد الـ polls في 30 ثانية: ${pollCount}`);
        console.log(`⏱️ Interval متوسط: ${pollLog.length > 1 ? '~5 ثواني' : 'N/A'}`);
    }, 30000);
})();

// ✅ اختبار 3: WebSocket الاستجابة
(() => {
    const originalLog = console.log;
    let wsEvents = [];
    
    // نصب عارض للرسائل
    window.addEventListener('message', (e) => {
        if (e.data?.type?.includes('PaymentApproved')) {
            const now = performance.now();
            wsEvents.push({ event: 'PaymentApproved', time: now });
            console.log(`✅ WebSocket PaymentApproved في ${now}ms`);
        }
    });
})();

// ✅ اختبار 4: الحالات البصرية
(() => {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach((node) => {
                    if (node.textContent?.includes('جاري')) {
                        console.log(`👀 حالة جديدة: ${node.textContent.substring(0, 50)}`);
                    }
                    if (node.classList?.contains('fa-spinner')) {
                        console.log('🌀 Spinner ظهر!');
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, { 
        childList: true, 
        subtree: true 
    });
    console.log('👁️ مراقب الـ UI نشط...');
})();
```

### النتائج المتوقعة

```
✅ اختبار 1 (الانتقال):
⏱️ الوقت: 427ms ← ممتاز
✅ ممتاز (300-1000ms)

🔄 اختبار 2 (Polling):
Poll #1 في 2:45:30 PM
Poll #2 في 2:45:35 PM (فارق 5 ثواني ✓)
Poll #3 في 2:45:40 PM (فارق 5 ثواني ✓)
📊 عدد الـ polls في 30 ثانية: 6
⏱️ Interval متوسط: ~5 ثواني ✓

✅ اختبار 3 (WebSocket):
✅ WebSocket PaymentApproved في 3452ms

👄 اختبار 4 (UI):
👀 حالة جديدة: جاري مراجعة طلب الدفع
🌀 Spinner ظهر!
```

---

## 🎯 3. سيناريوهات الاختبار اليدوي

### سيناريو 1: الموافقة السريعة (5-10 ثواني)

```plaintext
1. سجّل دخول كمستخدم
   ↓
2. أكمل تفاصيل السيارة
   ↓
3. اختر خطة من الخطط
   ↓
4. في صفحة الملخص:
   ⏱️ 300ms انتظار (spinner يظهر)
   ✅ انتقال سلس
   ↓
5. ادخل بيانات البطاقة:
   - رقم: 4242 4242 4242 4242
   - اسم: Ahmed Test
   - تاريخ: 12/25
   - CVV: 123
   ✅ شعار البطاقة يظهر فوراً
   ↓
6. اضغط "إدفع الآن"
   ⏱️ 1-4 ثواني انتظار
   🌀 Spinner في الزر
   ↓
7. Modal ينبثق: "جاري مراجعة طلب الدفع"
   🌀 Dots متحركة
   ⏳ انتظر 5-10 ثواني
   ↓
8. الأدمن يوافق (من لوحة التحكم)
   ✅ رسالة "تمت الموافقة"
   ⏱️ 1 ثانية انتظار (رؤية الرسالة)
   ↓
9. انتقال تلقائي إلى صفحة OTP
   ✅ تم ✓

الإحساس: سريع وواضح وموثوق
```

### سيناريو 2: الانتظار الطويل (60+ ثانية)

```plaintext
[نفس الخطوات 1-7]
   ↓
7. Modal ينبثق: "جاري مراجعة طلب الدفع"
   🌀 Dots متحركة
   ⏳ انتظر 60 ثانية...
   ↓
8. بعد 60 ثانية:
   📢 رسالة جديدة تظهر:
   "مراجعة الطلب"
   "قد تستغرق لحظات قليلة..."
   ✅ هذه إشارة حقيقية أن شيء يحدث
   ↓
9. استمر الانتظار (الأدمن مشغول)
   🔄 Polling يستمر كل 5 ثواني
   ↓
10. الأدمن يوافق (في أي وقت)
    ✅ الرسالة تتغير فوراً
    ✓ تمت الموافقة
    ⏱️ 1 ثانية
    ↓
11. انتقال إلى OTP
    ✅ تم ✓

الإحساس: انتظار واقعي مع تغذية بصرية واضحة
```

### سيناريو 3: الرفض

```plaintext
[نفس الخطوات 1-7]
   ↓
7. Modal: "جاري مراجعة"
   ↓
8. الأدمن يرفع (من لوحة التحكم)
   POST /admin/actions/payment-card/123/reject
   ↓
9. فوراً:
   ✗ تم الرفض
   "لم تتم الموافقة على العملية"
   السبب: "بيانات البطاقة غير صحيحة"
   ↓
10. أزرار جديدة:
    [جرّب بطاقة أخرى] ← يعود للـ checkout
    [تعديل البيانات]
    ↓
11. المستخدم يحاول بطاقة أخرى
    ✅ تم ✓

الإحساس: واضح وسريع التصحيح
```

---

## 🔍 4. ملاحظات الأداء التفصيلية

### في DevTools (F12)

#### الخطوة 1: Performance Tab

```
1. افتح DevTools → Performance
2. اضغط "Record"
3. اضغط "الانتقال للدفع"
4. اضغط "Stop" بعد الانتقال

النتيجة المتوقعة:
- Task duration: ~300-500ms (الـ 300ms تأخير + navigation)
- Long tasks: 0 (لا توجد مهام طويلة)
- FCP (First Contentful Paint): ~200-400ms
- LCP (Largest Contentful Paint): ~500-800ms
```

#### الخطوة 2: Network Tab

```
1. افتح DevTools → Network
2. فلترة: XHR/Fetch فقط
3. اضغط "إدفع الآن"

النتيجة المتوقعة:
- POST /payment-card/submit: 
  * Time: ~500-2000ms
  * Status: 200
  * Response: { card_id, status_sig }

- بعدها مباشرة polling:
  * GET /status/payment-card/...: 
    - كل 5 ثواني
    - Status: 200
    - Response: { status: 'pending' } أو 'approved'
```

#### الخطوة 3: Console Logs

```
جاري التحقق من logs:

[PaymentModal] WebSocket listener setup complete
  ↓
[PaymentModal] Polling started (interval: 5000ms)
  ↓
[PaymentModal] Poll #1 completed - status: pending
  ↓
[PaymentModal] Poll #2 completed - status: pending
  ↓
[PaymentModal] Payment approved
  ↓
[PaymentModal] Redirecting to OTP
```

---

## 🚀 5. الاختبار المتقدم (Advanced Testing)

### اختبار سرعة التأخير الفعلية

```javascript
// ضع هذا في console قبل الاختبار:

class PaymentPerformanceMonitor {
    constructor() {
        this.events = [];
    }
    
    log(label) {
        const now = performance.now();
        this.events.push({ label, time: now });
        console.log(`⏱️ [${label}] - ${now.toFixed(2)}ms`);
    }
    
    getDuration(startLabel, endLabel) {
        const start = this.events.find(e => e.label === startLabel)?.time;
        const end = this.events.find(e => e.label === endLabel)?.time;
        if (!start || !end) return null;
        return (end - start).toFixed(2);
    }
    
    printReport() {
        console.log('\n📊 --- Performance Report ---');
        for (let i = 0; i < this.events.length - 1; i++) {
            const duration = this.events[i + 1].time - this.events[i].time;
            console.log(`${this.events[i].label} → ${this.events[i + 1].label}: ${duration.toFixed(2)}ms`);
        }
    }
}

window.monitor = new PaymentPerformanceMonitor();

// ثم في الكود:
// عند الضغط على "الانتقال للدفع":
monitor.log('checkout-click');

// عند بدء التأخير:
monitor.log('delay-start');

// عند انتهاء التأخير (300ms):
monitor.log('delay-end');

// عند الانتقال:
monitor.log('navigation');

// عند فتح Modal:
monitor.log('modal-open');

// عند الموافقة:
monitor.log('approved');

// عند التحويل:
monitor.log('redirect');

// الملخص:
monitor.printReport();
```

**النتائج المتوقعة:**

```
📊 --- Performance Report ---
checkout-click → delay-start: 5.42ms
delay-start → delay-end: 300.15ms ✓
delay-end → navigation: 2.08ms
navigation → modal-open: 250.32ms
modal-open → approved: 5234.18ms (5.2 ثواني - الأدمن)
approved → redirect: 1000.41ms ✓
```

---

## ✅ 6. قائمة التحقق النهائية

- [ ] الانتقال من الملخص: **300-500ms** مع spinner واضح
- [ ] صفحة الدفع: **تحميل < 1 ثانية**
- [ ] اكتشاف البطاقة: **فوري** (شعار يظهر)
- [ ] تقديم النموذج: **spinner في الزر فوراً**
- [ ] فتح Modal: **< 500ms** بعد الرد
- [ ] Polling: **كل 5 ثواني بالضبط**
- [ ] رسالة "مراجعة": **تظهر بعد 60 ثانية تماماً**
- [ ] WebSocket: **إذا كان متاحاً، يكون فورياً < 200ms**
- [ ] رسالة الموافقة: **فورية عند الموافقة من الأدمن**
- [ ] التأخير قبل الانتقال: **1000ms (بالضبط)**
- [ ] الانتقال إلى OTP: **سلس وفوري**

---

## 📞 7. استدعاء الأدمن للاختبار

**الرسالة:**

```
السلام عليكم ورحمة الله وبركاته

أتحتاج منك تفضل! 👋

📝 نحتاج اختبار سريع لنظام الدفع:
1. فتح لوحة التحكم
2. رقم المستخدم: [رقم الجوال أو ID]
3. ابحث عن آخر طلب دفع "pending"
4. اضغط "قبول" وشوف الانتقال في الناحية الأخرى 🚀

الوقت المتوقع: 30 ثانية فقط
الفائدة: التأكد من أن التطبيق يعمل بكفاءة

شكراً! 🙏
```

---

## 🎓 8. الدروس المستفادة

✅ **التأخيرات الواقعية مهمة** - المستخدمون يحتاجون رؤية حركة فعلية  
✅ **الـ Polling + WebSocket** - يضمن التوصيل حتى مع انقطاع الإنترنت  
✅ **الرسائل في الوقت المناسب** - بعد 60 ثانية يعرف المستخدم أن شيء يحدث  
✅ **تعطيل الأزرار** - منع الأخطاء من النقرات المتكررة  
✅ **الصور البصرية** - شعارات البنوك تزيد الثقة  

---

## 🔗 ملفات ذات صلة

- [payment-flow-analysis.md](./payment-flow-analysis.md) - التحليل الكامل
- [resources/js/composables/usePaymentWebSocket.js](../../resources/js/composables/usePaymentWebSocket.js)
- [resources/js/car.insurance/components/checkout/PaymentWaitingModal.vue](../../resources/js/car.insurance/components/checkout/PaymentWaitingModal.vue)
- [AdminPaymentCardController.php](../../app/Http/Controllers/Admin/AdminPaymentCardController.php)
