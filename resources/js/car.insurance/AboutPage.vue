<template>
  <div class="bg-bg">
    <!-- Page Header -->
    <div class="bg-gradient-to-bl from-primary to-primary-dark text-white py-16">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <h1 class="typ-d2 mb-4">من نحن</h1>
        <p class="typ-t1 text-blue-200 max-w-2xl mx-auto">نساعدك في العثور على أفضل تأمين بأنسب سعر من خلال المقارنة بين
          أفضل شركات التأمين في المملكة</p>
      </div>
    </div>

    <!-- Mission -->
    <section class="py-16">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="typ-h1 text-foreground mb-4">مهمتنا</h2>
            <p class="text-muted mb-4 leading-relaxed">
              في تأمينكم، نؤمن بأن كل شخص يستحق الحصول على أفضل تغطية تأمينية بأنسب سعر. لذلك أنشأنا منصة إلكترونية
              متكاملة تتيح لك مقارنة عروض التأمين من أفضل شركات التأمين المرخصة في المملكة العربية السعودية.
            </p>
            <p class="text-muted leading-relaxed">
              نحن نوفر الوقت والجهد ونضمن الشفافية الكاملة في عرض الأسعار والتغطيات، حتى تتمكن من اتخاذ قرار مستنير
              يناسب احتياجاتك وميزانيتك.
            </p>
          </div>
          <div class="bg-white rounded-2xl p-8 shadow-sm border border-border text-center">
            <div class="text-7xl mb-4">🎯</div>
            <h3 class="typ-h2 text-foreground mb-2">رؤيتنا</h3>
            <p class="text-muted text-sm">أن نكون المنصة الأولى والأكثر ثقة لمقارنة وشراء التأمين في المملكة العربية
              السعودية</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Values -->
    <section class="py-16 bg-white">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <h2 class="typ-h1 text-foreground mb-10 text-center">قيمنا</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="(value, i) in values" :key="i" class="text-center p-6 rounded-2xl bg-bg border border-border">
            <div class="text-4xl mb-4">{{ value.icon }}</div>
            <h3 class="typ-t2 text-foreground mb-2">{{ value.title }}</h3>
            <p class="text-sm text-muted">{{ value.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Trust Indicators -->
    <section class="py-16">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <h2 class="typ-h1 text-foreground mb-10 text-center">لماذا تأمينكم؟</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="(reason, i) in reasons" :key="i" class="bg-white rounded-2xl p-6 shadow-sm border border-border">
            <div class="w-12 h-12 bg-primary-light rounded-xl flex items-center justify-center mb-4">
              <span class="text-2xl">{{ reason.icon }}</span>
            </div>
            <h3 class="typ-t2 text-foreground mb-2">{{ reason.title }}</h3>
            <p class="text-sm text-muted leading-relaxed">{{ reason.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Regulation Notice -->
    <section class="py-12 bg-blue-50">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center">
        <div class="flex items-center justify-center gap-3 mb-4">
          <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          <h3 class="typ-t1 text-foreground">مرخصون ومنظمون</h3>
        </div>
        <p class="text-muted text-sm max-w-xl mx-auto">
          تأمينكم منصة إلكترونية لمقارنة أسعار التأمين في المملكة العربية السعودية. نلتزم بجميع الأنظمة واللوائح التنظيمية لحماية
          حقوق العملاء وضمان جودة الخدمة.
        </p>
      </div>
    </section>
  </div>
</template>

<script setup>
const values = [
  { icon: '🔍', title: 'الشفافية', description: 'نعرض جميع المعلومات بوضوح دون رسوم خفية' },
  { icon: '⚡', title: 'السرعة', description: 'احصل على عروض مقارنة في أقل من 3 دقائق' },
  { icon: '🛡️', title: 'الأمان', description: 'بياناتك محمية بأعلى معايير أمان المعلومات' },
  { icon: '🤝', title: 'الموثوقية', description: 'نتعامل فقط مع شركات تأمين مرخصة ومعتمدة' },
];

const reasons = [
  { icon: '💰', title: 'وفّر حتى 40%', description: 'قارن بين أكثر من 8 شركات تأمين واختر الأنسب لميزانيتك. عملاؤنا وفروا في المتوسط 35% على أقساطهم.' },
  { icon: '⏱️', title: 'سريع وسهل', description: 'أكمل العملية بالكامل إلكترونياً في دقائق. لا حاجة لزيارة فروع أو تقديم أوراق ورقية.' },
  { icon: '📋', title: 'مقارنة شاملة', description: 'قارن التغطيات والأسعار والمزايا جنباً إلى جنب لاتخاذ القرار الأفضل المبني على معلومات دقيقة.' },
];
</script>
