<template>
    <section class="py-16 sm:py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="plan in insurancePlans" :key="plan.title"
                    class="bg-white rounded-2xl border overflow-hidden transition-all duration-300 hover:shadow-xl"
                    :class="[
                        plan.featured ? 'border-primary shadow-lg ring-2 ring-primary/20' : 'border-border',
                        plan.featured ? 'order-first md:order-none' : ''
                    ]">
                    <div class="px-6 pt-6 pb-4 text-center"
                        :class="plan.featured ? 'bg-primary text-white' : 'bg-slate-50'">
                        <span v-if="plan.badge" class="inline-block typ-c1 font-bold px-3 py-1 rounded-full mb-2"
                            :class="plan.featured ? 'bg-white/20 text-white' : 'bg-primary-light text-primary'">
                            {{ plan.badge }}
                        </span>
                        <h3 class="typ-h3 mb-1" :class="plan.featured ? 'text-white' : 'text-foreground'">
                            {{ plan.title }}
                        </h3>
                        <p class="text-sm leading-relaxed" :class="plan.featured ? 'text-white/80' : 'text-muted'">
                            {{ plan.description }}
                        </p>
                    </div>
                    <div class="p-6">
                        <router-link :to="{ name: 'motorapp' }"
                            class="block w-full text-center py-3 rounded-xl typ-s1 transition-all duration-200 mb-5"
                            :class="plan.featured
                                ? 'bg-primary text-white hover:bg-primary-dark'
                                : 'bg-white text-primary border-2 border-primary hover:bg-primary-light'">
                            اشتر الآن
                        </router-link>
                        <ul class="space-y-3">
                            <li v-for="(feature, fi) in plan.features" :key="fi" class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0 text-secondary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-muted leading-relaxed">{{ feature }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

const insurancePlans = [
    {
        title: 'ضد الغير (الطرف الثالث)',
        badge: 'التغطية الأساسية',
        featured: false,
        description: 'التغطية الأساسية المطلوبة لتجنب المخالفات المرورية',
        features: [
            'يغطي الأضرار التي قد تتسبب بها مركبتك لطرف ثالث (مثل إصلاح مركبة شخص آخر أو تعويض أضرار ممتلكاته)',
            'هذا النوع من التأمين إلزامي قانونًا في المملكة لجميع المركبات',
        ],
    },
    {
        title: 'التأمين الشامل',
        badge: 'أفضل تغطية',
        featured: true,
        description: 'توفر هذه التغطية حماية لسيارتك في حال وقوع الحوادث، حيث تغطي تكاليف الإصلاح أو الاستبدال',
        features: [
            'المسؤولية تجاه الغير',
            'إصلاح السيارة',
            'الكوارث الطبيعية',
            'سرقة السيارة',
            'عدم تحمل نسبة استهلاك قطع الغيار',
            'إمكانية التقسيط',
            'الحريق',
            'تعويض الحوادث الشخصية للسائق',
            'تغطية شاملة للسيارة المؤمن عليها',
        ],
    },
    {
        title: 'ضد الغير بلس',
        badge: 'التغطية المخصصة',
        featured: false,
        description: 'توفر تغطية التأمين ضد الغير مع تغطيات إضافية ومخصصة لحمايتك',
        features: [
            'تغطية سيارة مالك الوثيقة بشروط محددة',
            'تغطيات إضافية مقدمة من شركة التأمين',
            'التغطيات لهذا المنتج تكون منوعة ومخصصة بناء على شركة التأمين',
        ],
    },
];
