# تحليل تغيير الدومين إلى `tamnyfordr.online`

تاريخ الفحص: 2026-05-21

آخر إعادة فحص: 2026-05-21

## الخلاصة التنفيذية

تم تحديث ملفات التشغيل والوثائق وملفات البيئة المحلية/الإنتاجية إلى الدومين الجديد `tamnyfordr.online`، وتم تحويل هدف النشر إلى VPS الجديد `69.57.161.222`. تم كذلك إعادة بناء `public/build` بعد تحديث `.env.production` حتى لا تبقى assets موجهة للدومين القديم. DNS أصبح صحيحا الآن ويشير إلى VPS الجديد من أكثر من resolver. SSH daemon يرد، لكن الدخول غير التفاعلي فشل لأن مفتاح النشر غير مثبت بعد أو لأن السيرفر يتطلب كلمة المرور المؤقتة لأول دخول.

أخطر نقاط متبقية:

1. `ssh -o BatchMode=yes root@69.57.161.222` فشل برسالة `Permission denied`.
2. لا يمكن تنفيذ النشر الآلي قبل أول دخول بكلمة المرور المؤقتة وتثبيت مفتاح SSH.
3. بعد نجاح SSH يمكن تنفيذ النشر وإصدار SSL.

النتيجة: الكود والبيئة المحلية أصبحا متجهين إلى الدومين الجديد، لكن النشر يحتاج إصلاح DNS/SSH ثم إعادة build وإعادة إنشاء containers وإصدار SSL.

## حالة DNS الحالية

الفحص تم عبر resolver `8.8.8.8`.

| النطاق | الحالة الحالية | المتوقع |
| --- | --- | --- |
| `tamnyfordr.online` | A -> `69.57.161.222` | صحيح |
| `www.tamnyfordr.online` | A -> `69.57.161.222` | صحيح |

تم التحقق أيضا عبر `1.1.1.1` وكانت النتيجة نفسها لكلا النطاقين. DNS جاهز لإصدار Let's Encrypt من ناحية توجيه النطاق. إذا فشل إصدار SSL بعد ذلك، فالسبب التالي الذي يجب فحصه هو وصول HTTP/HTTPS إلى السيرفر وتشغيل Nginx/Certbot.

## حالة SSH الحالية

تم فحص SSH على VPS الجديد:

```text
ssh -o BatchMode=yes root@69.57.161.222 echo SSH_OK
Permission denied (publickey,gssapi-keyex,gssapi-with-mic,password).
```

هذا يعني أن خدمة SSH ترد، لكن المفتاح المحلي غير مثبت بعد أو أن الدخول الأول يحتاج كلمة المرور المؤقتة. فحص `Test-NetConnection` ما زال يرجع `TcpTestSucceeded=False` من PowerShell، لكن نتيجة OpenSSH أدق هنا لأنها وصلت إلى daemon وأعادت رفض مصادقة.

لا تحفظ كلمة مرور root داخل ملفات المشروع. بعد أول دخول يجب تغيير كلمة المرور المؤقتة، ثم تثبيت مفتاح SSH عام داخل `/root/.ssh/authorized_keys`.

## الملفات التي تم تحديثها إلى الدومين الجديد

### ملفات البيئة المحلية

- `.env`
  - تم تحديث `APP_URL`, `DOMAIN`, `SUPPORT_EMAIL_DOMAIN`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, `REVERB_HOST`, `CORS_ALLOWED_ORIGINS`, `VITE_REVERB_HOST`, و`MAIL_FROM_ADDRESS` إلى `tamnyfordr.online`.

- `.env.production`
  - تم تحديث نفس مفاتيح production حتى لا يحقن Vite الدومين القديم داخل ملفات JavaScript عند build.
  - تم توحيد `VITE_REVERB_APP_KEY` مع `REVERB_APP_KEY` حتى يستخدم المتصفح نفس مفتاح Reverb الذي يعرفه السيرفر.
  - تم قفل `REVERB_ALLOWED_ORIGINS` على الدومينات العامة بدل wildcard.

- `.env.production.example`
  - تم تحديث مثال production حتى لا يرجع أي إعداد جديد إلى الدومين القديم.

- `deploy/new-server/.env.production.template`
  - تم تحديث template النشر ليشمل `SANCTUM_STATEFUL_DOMAINS`.
  - تم تصحيح تعليق Reverb القديم إلى `__DOMAIN__`.
  - تم قفل `REVERB_ALLOWED_ORIGINS` على `https/http` للدومين و`www`.

- `public/build`
  - تمت إعادة بنائه بعد تحديث `.env.production`.
  - الفحص أكد عدم وجود أي دومينات إنتاج قديمة داخل build الحالي.
  - ملف Echo المبني أصبح يستخدم `tamnyfordr.online`.

### Nginx وSSL

- `docker/nginx/conf.d/default.conf`
  - `server_name` أصبح `tamnyfordr.online` و`www.tamnyfordr.online`.
  - التحويلات أصبحت إلى `https://tamnyfordr.online`.
  - مسارات الشهادات أصبحت:
    - `/etc/nginx/ssl/live/tamnyfordr.online/fullchain.pem`
    - `/etc/nginx/ssl/live/tamnyfordr.online/privkey.pem`
    - `/etc/nginx/ssl/live/tamnyfordr.online/chain.pem`

- `docker/nginx/snippets/security-headers.conf`
  - `connect-src` يسمح الآن بـ:
    - `wss://tamnyfordr.online`
    - `wss://www.tamnyfordr.online`

ملاحظة: `docker/nginx/conf.d/default.conf.template` يستخدم `${DOMAIN}` وليس دومينا hardcoded، وهذا صحيح بشرط أن تكون قيمة `DOMAIN` في `.env` هي `tamnyfordr.online`.

### سكربتات النشر والإصلاح

- `deploy-local-direct.sh`
  - الدومين الافتراضي أصبح `tamnyfordr.online`.
  - يتم ضبط مفاتيح الدومين الأساسية مباشرة على الدومين الجديد داخل `.env` و`.env.production` على السيرفر.

- `deploy/new-server/repair-current-server.sh`
  - الدومين الافتراضي أصبح `tamnyfordr.online`.
  - يتم ضبط مفاتيح الدومين الأساسية مباشرة على الدومين الجديد داخل ملفات البيئة على السيرفر.

- `deploy/new-server/issue-ssl-current-server.sh`
  - الدومين الافتراضي أصبح `tamnyfordr.online`.
  - البريد الافتراضي أصبح `admin@tamnyfordr.online`.

- `deploy-prod.sh`
  - `INS_DOMAIN` الافتراضي أصبح `tamnyfordr.online`.

- `deploy-extract-and-build.sh`
  - `DOMAIN` أصبح `tamnyfordr.online`.

- `deploy/new-server/deploy.sh`
  - مثال التشغيل أصبح يستخدم `INS_DOMAIN=tamnyfordr.online`.
  - السكربت نفسه يعتمد على `INS_DOMAIN` وملف `deploy/new-server/.env.production.template`، وهذا المسار أفضل من الاعتماد على `.env.production.example`.

### أدوات admin وdiagnostics

- `deploy/admin/create-admin.sh`
- `deploy/admin/fix-admin.sh`
- `deploy/admin/diagnose-login.sh`
- `deploy/admin/production-setup.sh`
- `scripts/health/watch-taminat-dns.sh`

تم تحديث الروابط والبريد الإداري إلى `tamnyfordr.online`.

### الوثائق التشغيلية

- `DEPLOYMENT-GUIDE.md`
- `docs/deployment/DEPLOYMENT-READY.md`
- `docs/ops/PRE-DEPLOYMENT-REVIEW.md`
- `docs/ops/PRODUCTION-FIX-GUIDE.md`
- `deploy/new-server/RUNBOOK.md`
- `docs/ops/laravel-docker-pitfalls.md`

تم تحديث مراجع الدومين في هذه الملفات، لكنها لا تؤثر وحدها على التشغيل.

## القيم المطلوبة بعد التحديث

القيم العامة المطلوبة في `.env` و`.env.production`:

```env
APP_URL=https://tamnyfordr.online
DOMAIN=tamnyfordr.online
SESSION_DOMAIN=.tamnyfordr.online
REVERB_HOST=tamnyfordr.online
VITE_REVERB_HOST=tamnyfordr.online
MAIL_FROM_ADDRESS=no-reply@tamnyfordr.online
CORS_ALLOWED_ORIGINS=https://tamnyfordr.online,https://www.tamnyfordr.online
SANCTUM_STATEFUL_DOMAINS=tamnyfordr.online,www.tamnyfordr.online
SUPPORT_EMAIL_DOMAIN=tamnyfordr.online
```

الأثر إذا تغيرت أو رجعت لقيم قديمة:

- Laravel سيولد روابط Storage وURL على الدومين القديم.
- Cookies قد تبقى مرتبطة بدومين قديم.
- CORS سيرفض origin الجديد.
- Reverb backend سيستخدم host قديم.
- Docker Compose يمرر `DOMAIN` إلى Nginx template، وإذا بقيت قديمة سينتج Nginx runtime خاطئ.

### `.env.production` وVite

هذه نقطة حساسة جدا لأن `Dockerfile` يعمل:

```dockerfile
COPY .env.production .env.production
RUN set -a && . ./.env.production && set +a \
    && env | grep '^VITE_' > .env \
    && npm run build
```

هذا يعني أن Vite يقرأ `VITE_REVERB_HOST` من `.env.production` وقت build. لذلك يجب إعادة build بعد أي تغيير في هذه القيم، حتى لو كان Nginx صحيحا.

## مسارات الكود المتأثرة بالدومين

### Laravel URL وStorage

- `config/app.php`
  - يعتمد على `APP_URL`.
- `config/filesystems.php`
  - يستخدم `APP_URL` لبناء روابط `/storage`.
- `config/mail.php`
  - يستخدم `APP_URL` لاشتقاق `MAIL_EHLO_DOMAIN` إذا لم تضبط صراحة.
- `routes/api.php`
  - يستخدم `SUPPORT_EMAIL_DOMAIN` أو host من `config('app.url')`.

### Session وSanctum وCORS

- `config/session.php`
  - يعتمد على `SESSION_DOMAIN`.
- `config/cors.php`
  - يستخدم `CORS_ALLOWED_ORIGINS` أو `APP_URL`.
- `.env.production.example`
  - يحتوي `SANCTUM_STATEFUL_DOMAINS` ويجب تحديثه رغم أن البحث في config لم يظهر استخدامه المباشر في الملفات المفتوحة.

### Reverb وWebSocket

- `config/reverb.php`
  - يعتمد على `REVERB_HOST`.
- `config/broadcasting.php`
  - يعتمد على `REVERB_PUBLISH_HOST` ثم `REVERB_HOST`.
- `resources/js/services/echo.js`
  - يستخدم `VITE_REVERB_HOST` وقت build.
- `docker/nginx/snippets/security-headers.conf`
  - يسمح باتصالات `wss` للدومين الجديد.

الخطر الأكبر هنا هو build stale: إذا بنيت frontend قبل تعديل `.env.production` أو لم ترفع `public/build` الجديد، فقد يبقى WebSocket يحاول الاتصال بدومين قديم.

### Nginx runtime

- `docker-compose.yml`
  - خدمة `nginx` تحتاج:

```yaml
DOMAIN: "${DOMAIN:?set DOMAIN env var (primary public domain)}"
```

- `docker/nginx/conf.d/default.conf.template`
  - يستخدم `${DOMAIN}` لكل server_name ومسارات الشهادات.

لذلك يجب أن يحتوي `.env` على:

```env
DOMAIN=tamnyfordr.online
```

وإلا سيفشل `docker compose` أو ينتج config بدومين خاطئ.

## خطة الإكمال الصحيحة

### قبل النشر

1. DNS:
   - `tamnyfordr.online` -> `69.57.161.222` تم التحقق منه.
   - `www.tamnyfordr.online` -> `69.57.161.222` تم التحقق منه.

2. تسجيل الدخول لأول مرة بكلمة مرور root المؤقتة، تغييرها، ثم تثبيت مفتاح SSH عام في `/root/.ssh/authorized_keys`.

3. إعادة build بدون الاعتماد على assets قديمة:
   - لأن Vite يحقن `VITE_*` داخل build.
   - تم تنفيذ `npm run build` محليا بنجاح بعد التعديل.

4. التأكد من خلو build من الدومينات القديمة:

```bash
grep -R "<old-production-domain>" public/build || true
```

### على السيرفر `/opt/insurance2026`

القيم المطلوبة في `.env` على السيرفر:

```env
APP_URL=https://tamnyfordr.online
DOMAIN=tamnyfordr.online
SESSION_DOMAIN=.tamnyfordr.online
SANCTUM_STATEFUL_DOMAINS=tamnyfordr.online,www.tamnyfordr.online
CORS_ALLOWED_ORIGINS=https://tamnyfordr.online,https://www.tamnyfordr.online
REVERB_HOST=tamnyfordr.online
VITE_REVERB_HOST=tamnyfordr.online
SUPPORT_EMAIL_DOMAIN=tamnyfordr.online
MAIL_FROM_ADDRESS=no-reply@tamnyfordr.online
```

بعد تعديل DNS والبيئة:

```bash
cd /opt/insurance2026
docker compose up -d --build --force-recreate
docker exec ins2026-app php artisan config:cache
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan view:cache
docker exec ins2026-app php artisan event:cache
```

ثم إصدار SSL:

```bash
DOMAIN=tamnyfordr.online bash deploy/new-server/issue-ssl-current-server.sh
```

ثم التحقق:

```bash
curl -Ik https://tamnyfordr.online/api/health
curl -Ik https://tamnyfordr.online/api/health/realtime
docker exec ins2026-nginx nginx -T | grep -E "server_name|tamnyfordr"
docker exec ins2026-app printenv | grep -E "APP_URL|DOMAIN|SESSION_DOMAIN|REVERB_HOST|VITE_REVERB_HOST|CORS_ALLOWED_ORIGINS"
```

## حكم الجاهزية

الوضع الحالي غير جاهز للنشر الآلي الكامل على `tamnyfordr.online` عبر SSH.

السبب ليس الكود الأساسي ولا DNS، بل نقطة تشغيلية واحدة:

1. مفتاح SSH غير مثبت/الدخول غير التفاعلي مرفوض حتى يتم أول دخول بكلمة المرور المؤقتة.

بعد تثبيت SSH key ثم تنفيذ recreate وإصدار SSL، يصبح تغيير الدومين متسقا مع Laravel وNginx وReverb وCORS.
