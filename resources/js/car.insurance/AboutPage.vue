<template>
  <div class="bg-bg min-h-screen" dir="rtl">
    <!-- Page Header -->
    <div class="bg-gradient-to-bl from-primary to-primary-dark text-white py-16">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <h1 class="typ-d2 mb-4">{{ t('about.title') }}</h1>
        <p class="typ-t1 text-blue-200 max-w-2xl mx-auto">{{ t('about.subtitle') }}</p>
      </div>
    </div>

    <!-- Mission -->
    <section class="py-16">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="typ-h1 text-foreground mb-4">{{ t('about.mission') }}</h2>
            <p class="text-muted mb-4 leading-relaxed">{{ t('about.missionP1') }}</p>
            <p class="text-muted leading-relaxed">{{ t('about.missionP2') }}</p>
          </div>
          <div class="bg-white rounded-2xl p-8 shadow-sm border border-border text-center">
            <div class="text-7xl mb-4">🎯</div>
            <h3 class="typ-h2 text-foreground mb-2">{{ t('about.vision') }}</h3>
            <p class="text-muted text-sm">{{ t('about.visionText') }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Values -->
    <section class="py-16 bg-white">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <h2 class="typ-h1 text-foreground mb-10 text-center">{{ t('about.values') }}</h2>
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
        <h2 class="typ-h1 text-foreground mb-10 text-center">{{ t('about.whyUs') }}</h2>
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
          <h3 class="typ-t1 text-foreground">{{ t('about.regulated') }}</h3>
        </div>
        <p class="text-muted text-sm max-w-xl mx-auto">{{ t('about.regulatedDesc') }}</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const values = [
  { icon: '🔍', title: t('about.valueTransparency'), description: t('about.valueTransparencyDesc') },
  { icon: '⚡', title: t('about.valueSpeed'), description: t('about.valueSpeedDesc') },
  { icon: '🛡️', title: t('about.valueSecurity'), description: t('about.valueSecurityDesc') },
  { icon: '🤝', title: t('about.valueTrust'), description: t('about.valueTrustDesc') },
];

const reasons = [
  { icon: '💰', title: t('about.reasonSave'), description: t('about.reasonSaveDesc') },
  { icon: '⏱️', title: t('about.reasonFast'), description: t('about.reasonFastDesc') },
  { icon: '📋', title: t('about.reasonCompare'), description: t('about.reasonCompareDesc') },
];
</script>
