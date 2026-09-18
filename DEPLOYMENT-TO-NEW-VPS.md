# نشر على VPS جديد (AlmaLinux 9)

## متطلبات ما قبل النشر

✅ **DNS**

```bash
# A record: your-domain.com → NEW_VPS_IP
nslookup your-domain.com 8.8.8.8
```

✅ **SSH اختبار الوصول**

```bash
ssh -i ~/.ssh/id_ed25519 root@NEW_VPS_IP echo "SSH works"
```

✅ **متطلبات الخادم**

- AlmaLinux 9 جديد
- 2GB RAM (موصى به 4GB)
- تثبيت Docker و Docker Compose

## التعليمات

### 1️⃣ إعداد محلي

```bash
cd /path/to/insurance2026

# تأكد من أن الفرع نظيف
git status
git checkout hardening/clean-rebuild  # أو الفرع المطلوب
```

### 2️⃣ إعداد متغيرات البيئة

```bash
# المتغيرات المطلوبة
export INS_SERVER_IP="YOUR_NEW_VPS_IP"
export INS_DOMAIN="your-domain.com"
export INS_REPO_URL="git@github.com:YOUR_ORG/insurance2026.git"
export INS_BRANCH="hardening/clean-rebuild"

# المتغيرات الاختيارية
export SSH_KEY="$HOME/.ssh/id_ed25519"
export INS_DEPLOY_USER="root"
export INS_DEPLOY_DIR="/opt/insurance2026"
export LE_EMAIL="admin@your-domain.com"

# إذا كنت تستخدم HTTPS + GitHub Token
# export INS_REPO_URL="https://github.com/YOUR_ORG/insurance2026.git"
# export INS_GIT_TOKEN="ghp_xxxxx"  # GitHub Personal Access Token
```

### 3️⃣ استخراج قاعدة البيانات (اختياري)

إذا كنت تريد استيراد بيانات من النسخة القديمة:

```bash
# من السيرفر القديم (إن أمكن)
ssh -i ~/.ssh/old-deploy-key root@OLD_VPS_IP \
  "cd /opt/insurance2026 && php artisan db:dump --compress" \
  > insurance2026.sql.gz

# ثم انسخ إلى
cp insurance2026.sql.gz deploy/new-server/

# أثناء النشر، ستكون متوفرة للاستيراد
export INS_DB_DUMP="deploy/new-server/insurance2026.sql.gz"
```

### 4️⃣ نشر المشروع

```bash
bash deploy/new-server/deploy.sh
```

**ماذا سيفعل السكريبت:**

1. ✅ التحقق من متطلبات محلية (SSH key، Git)
2. ✅ الاتصال بالسيرفر الجديد
3. ✅ استنساخ المشروع من GitHub (مع إنشاء Deploy Key إذا لزم الأمر)
4. ✅ إعداد البيئة والأسرار
5. ✅ تثبيت المتطلبات (Composer، npm)
6. ✅ بناء الأصول الأمامية
7. ✅ إعداد قاعدة البيانات والترحيل
8. ✅ تكوين Nginx و SSL (Let's Encrypt)
9. ✅ تشغيل الخدمات (Docker Compose)

### 5️⃣ التحقق من النشر

```bash
# الوصول إلى السيرفر
ssh -i ~/.ssh/id_ed25519 root@$INS_SERVER_IP

# التحقق من الحالة
cd /opt/insurance2026
docker compose ps

# عرض السجلات
docker compose logs --tail=50 app
docker compose logs --tail=50 nginx

# اختبار HTTP
curl -I https://your-domain.com/
curl https://your-domain.com/api/health
```

## استكشاف الأخطاء

### قاعدة البيانات لا تتصل

```bash
docker compose exec app php artisan db:ping
docker compose logs db
```

### SSL/HTTPS مشاكل

```bash
# التحقق من شهادة Let's Encrypt
docker compose exec certbot certbot certificates
docker compose logs certbot
```

### مشاكل الأصول الأمامية (CSS/JS)

```bash
# إعادة بناء الأصول
docker compose exec app npm run build

# التحقق من الملفات
ls -la /opt/insurance2026/public/build/
```

### مشاكل الطابور (Queue)

```bash
docker compose exec horizon php artisan horizon:status
docker compose logs horizon
```

## التنظيف (اختياري)

بعد التأكد من نجاح النشر على VPS الجديد:

```bash
# الاحتفاظ بنسخة احتياطية من البيانات
# ثم إيقاف السيرفر القديم

# من السيرفر القديم (عبر SSH):
# ssh -i ~/.ssh/old-deploy-key root@OLD_VPS_IP
# cd /opt/insurance2026 && docker compose down

# الحذف النهائي (بعد التأكد الكامل)
# يمكنك حذف السيرفر من مزود الاستضافة
```

## المراجع

- ملف النشر: `deploy/new-server/deploy.sh`
- الإعدادات الثابتة: `deploy/new-server/.env.production.template`
- دليل التشغيل: `deploy/new-server/RUNBOOK.md`
- استراتيجية النشر: `docs/deployment/GITHUB-DEPLOYMENT-STRATEGY.md`
