<template>
    <div class="min-h-[70vh] flex flex-col items-center justify-center text-center px-6 py-20">
        <div class="text-8xl font-black text-primary/20 mb-4">404</div>

        <h1 class="text-2xl font-bold text-foreground mb-3">الصفحة غير موجودة</h1>

        <p class="text-slate-500 text-base max-w-xl mb-8 leading-relaxed">
            عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.
            تأكد من صحة الرابط أو ارجع للصفحة الرئيسية.
        </p>

        <div class="flex flex-col sm:flex-row gap-3">
            <router-link to="/"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-primary/90 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                الصفحة الرئيسية
            </router-link>

            <button class="inline-flex items-center justify-center gap-2 px-6 py-3 border border-slate-200 text-foreground rounded-xl font-semibold hover:bg-slate-50 transition-colors"
                @click="$router.back()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                العودة للخلف
            </button>
        </div>
    </div>
</template>

<script setup>
/**
 * 404 — Not Found Page
 *
 * يُعرض عند زيارة مسار غير موجود.
 * يقدم خيارين: العودة للرئيسية أو الرجوع للخلف.
 */
</script>
