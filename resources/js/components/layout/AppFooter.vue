<template>
  <footer class="bg-slate-50 p-6 md:p-8">
    <div v-if="isOrderReview" class="md:hidden text-center">
      <p class="text-xs text-slate-500">🔒 الدفع آمن ومشفر</p>
      <div class="mt-3 flex justify-center gap-4 text-xs text-slate-600">
        <router-link to="/privacy">سياسة الخصوصية</router-link>
        <router-link to="/terms">الشروط والأحكام</router-link>
      </div>
      <p class="mt-3 text-[11px] text-slate-500">© تأمينكم 2026</p>
    </div>

    <div class="max-w-7xl mx-auto" :class="isOrderReview ? 'hidden md:block' : ''">
      <section class="flex flex-col xl:flex-row xl:gap-16 border-b-0 xl:border-slate-300 xl:border-b pb-4 md:pb-8">
        <!-- Social & Copyright (Right Column on Desktop) -->
        <section class="w-full xl:w-60 flex flex-col gap-8 pb-4 border-b border-slate-300 xl:border-b-0">
          <div class="flex flex-row xl:flex-col gap-2">
            <h5 class="text-sm font-medium text-neutral-900">تابعنا</h5>
            <div class="flex gap-2">
              <!-- X (Twitter) -->
              <a href="#" aria-label="X" class="text-gray-500 hover:text-gray-800">
                <span class="h-6 w-6" aria-hidden="true">
                  <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6">
                    <g clip-path="url(#clip0_x)">
                      <rect width="20" height="20" fill="white" />
                      <path d="M10 0C4.48626 0 0 4.48626 0 10C0 15.5137 4.48626 20 10 20C15.5137 20 20 15.5137 20 10C20 4.48626 15.5137 0 10 0ZM18.7163 10C18.7163 14.8065 14.8065 18.7163 10 18.7163C5.19349 18.7163 1.28102 14.8065 1.28102 10C1.28102 5.19349 5.19349 1.28369 10 1.28369C14.8065 1.28369 18.7163 5.19349 18.7163 10Z" fill="#475569" />
                      <g clip-path="url(#clip1_x)">
                        <mask id="mask0_x" style="mask-type: luminance" maskUnits="userSpaceOnUse" x="5" y="5" width="10" height="10">
                          <path d="M14.78 5H5V15H14.78V5Z" fill="white" />
                        </mask>
                        <g mask="url(#mask0_x)">
                          <path d="M10.8204 9.23214L14.4612 5H13.5985L10.4371 8.67471L7.91221 5H5L8.81819 10.5568L5 14.9949H5.8628L9.20123 11.1142L11.8677 14.9949H14.78L10.8202 9.23214H10.8204ZM9.63867 10.6058L9.25181 10.0524L6.17368 5.64951H7.4989L9.98298 9.20281L10.3698 9.75614L13.5989 14.3749H12.2736L9.63867 10.606V10.6058Z" fill="#475569" />
                        </g>
                      </g>
                    </g>
                    <defs>
                      <clipPath id="clip0_x"><rect width="20" height="20" fill="white" /></clipPath>
                      <clipPath id="clip1_x"><rect width="9.77995" height="10" fill="white" transform="translate(5 5)" /></clipPath>
                    </defs>
                  </svg>
                </span>
              </a>
              <!-- Facebook -->
              <a href="#" aria-label="Facebook" class="text-gray-500 hover:text-gray-800">
                <span class="h-6 w-6" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10.4212 12.0481V17.9184C10.4212 18.0016 10.4884 18.0721 10.5749 18.0721H12.7559C12.8391 18.0721 12.9096 18.0048 12.9096 17.9184V11.952H14.4916C14.5717 11.952 14.6358 11.8911 14.6454 11.8111L14.7959 10.0144C14.8023 9.92478 14.7318 9.84791 14.6422 9.84791H12.9096V8.57329C12.9096 8.27546 13.153 8.03206 13.4508 8.03206H14.6678C14.751 8.03206 14.8215 7.96481 14.8215 7.87834V6.0817C14.8215 5.99844 14.7542 5.92798 14.6678 5.92798H12.6085C11.4012 5.92798 10.4212 6.90796 10.4212 8.11533V9.84791H9.33231C9.24905 9.84791 9.17859 9.91517 9.17859 10.0016V11.7983C9.17859 11.8815 9.24584 11.952 9.33231 11.952H10.4212V12.0481Z" fill="currentColor" />
                    <path d="M12 0C5.38351 0 0 5.38351 0 12C0 18.6165 5.38351 24 12 24C18.6165 24 24 18.6165 24 12C24 5.38351 18.6165 0 12 0ZM22.4596 12C22.4596 17.7678 17.7678 22.4596 12 22.4596C6.23219 22.4596 1.53723 17.7678 1.53723 12C1.53723 6.23219 6.23219 1.54043 12 1.54043C17.7678 1.54043 22.4596 6.23219 22.4596 12Z" fill="currentColor" />
                  </svg>
                </span>
              </a>
              <!-- Instagram -->
              <a href="#" aria-label="Instagram" class="text-gray-500 hover:text-gray-800">
                <span class="h-6 w-6" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6">
                    <path d="M16 8.68849C16 9.0856 15.6798 9.40586 15.2827 9.40586C14.8855 9.40586 14.5653 9.0824 14.5653 8.68849C14.5653 8.29457 14.8887 7.97111 15.2827 7.97111C15.6766 7.97111 16 8.29457 16 8.68849ZM15.1065 11.9103C15.1065 13.5756 13.7518 14.9303 12.0865 14.9303C10.4212 14.9303 9.06648 13.5756 9.06648 11.9103C9.06648 10.2449 10.4212 8.89025 12.0865 8.89025C13.7518 8.89025 15.1065 10.2449 15.1065 11.9103ZM14.0208 11.9103C14.0208 10.8438 13.1529 9.97592 12.0865 9.97592C11.02 9.97592 10.1522 10.8438 10.1522 11.9103C10.1522 12.9767 11.02 13.8446 12.0865 13.8446C13.1529 13.8446 14.0208 12.9767 14.0208 11.9103ZM18.197 9.48592C18.197 7.4523 16.5477 5.80298 14.514 5.80298H9.48922C7.45559 5.80298 5.80627 7.4523 5.80627 9.48592V14.5107C5.80627 16.5444 7.45559 18.1937 9.48922 18.1937H14.514C16.5477 18.1937 18.197 16.5444 18.197 14.5107V9.48592ZM16.9896 14.4563C16.9896 15.8526 15.8559 16.9863 14.4596 16.9863H9.54366C8.14734 16.9863 7.01364 15.8526 7.01364 14.4563V9.54037C7.01364 8.14405 8.14734 7.01034 9.54366 7.01034H14.4596C15.8559 7.01034 16.9896 8.14405 16.9896 9.54037V14.4563Z" fill="currentColor" />
                    <path d="M12 0C5.38351 0 0 5.38351 0 12C0 18.6165 5.38351 24 12 24C18.6165 24 24 18.6165 24 12C24 5.38351 18.6165 0 12 0ZM12 22.4596C6.23219 22.4596 1.54043 17.7678 1.54043 12C1.54043 6.23219 6.23219 1.54043 12 1.54043C17.7678 1.54043 22.4628 6.23219 22.4628 12C22.4628 17.7678 17.771 22.4596 12 22.4596Z" fill="currentColor" />
                  </svg>
                </span>
              </a>
              <!-- Snapchat -->
              <a href="#" aria-label="Snapchat" class="text-gray-500 hover:text-gray-800">
                <span class="h-6 w-6" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6">
                    <path d="M5.99841 15.1513V14.9623C6.07527 14.8727 6.15533 14.799 6.28023 14.7734C7.26342 14.578 7.92315 13.9503 8.41955 13.1273C8.83588 12.4355 8.77183 12.2754 8.00001 12.0032C7.95518 11.9871 7.91034 11.9743 7.86871 11.9583C7.70217 11.8911 7.53884 11.8174 7.40113 11.6989C7.05525 11.3979 7.26022 10.9687 7.66694 10.8695C7.96478 10.799 8.22419 10.9879 8.51242 11.0264C8.70457 11.0488 8.78144 10.9847 8.75902 10.7798C8.69817 10.2514 8.69817 9.71973 8.72379 9.18811C8.77182 8.1761 9.26822 7.40428 10.1137 6.90468C11.3467 6.1745 12.6309 6.16489 13.8735 6.89187C14.6485 7.34663 15.1481 8.03198 15.257 8.95432C15.3275 9.5532 15.2987 10.1553 15.2538 10.7542C15.2346 11.0136 15.2858 11.0616 15.5452 11.0008C15.8655 10.9271 16.1345 10.7606 16.4676 10.9079C16.6469 10.9847 16.8007 11.1545 16.7975 11.3498C16.7942 11.4843 16.7174 11.606 16.6149 11.6925C16.4612 11.827 16.2594 11.9007 16.0737 11.9775C15.9296 12.0352 15.779 12.08 15.6381 12.1473C15.3467 12.285 15.2634 12.4643 15.4011 12.7526C15.8719 13.7486 16.5637 14.5044 17.6846 14.767C17.8223 14.799 17.9312 14.8534 18.0144 14.9655V15.1545C17.9087 15.3723 17.7198 15.494 17.502 15.574C17.2842 15.6573 17.0633 15.7502 16.8295 15.7662C16.506 15.7854 16.3331 15.9295 16.285 16.2658C16.237 16.586 16.1409 16.6341 15.8143 16.6052C15.7374 16.5988 15.6605 16.586 15.5837 16.57C14.9432 16.4547 14.3379 16.522 13.8127 16.9479C13.7102 17.0312 13.5949 17.1016 13.4828 17.1721C12.5829 17.755 11.6637 17.8222 10.7222 17.2746C10.466 17.1241 10.2226 16.9543 9.97599 16.791C9.77103 16.6565 9.55005 16.5828 9.30345 16.5444C8.91274 16.4867 8.54124 16.5732 8.16334 16.6149C7.96798 16.6373 7.77904 16.5796 7.76943 16.3811C7.74381 15.8943 7.44597 15.7758 7.03604 15.7309C6.84709 15.7085 6.65814 15.6317 6.4788 15.558C6.28344 15.4747 6.10409 15.3594 6.01122 15.1545L5.99841 15.1513Z" fill="currentColor" />
                    <path d="M12 0C5.38351 0 0 5.38351 0 12C0 18.6165 5.38351 24 12 24C18.6165 24 24 18.6165 24 12C24 5.38351 18.6165 0 12 0ZM12 22.4596C6.23219 22.4596 1.54043 17.7678 1.54043 12C1.54043 6.23219 6.23219 1.54043 12 1.54043C17.7678 1.54043 22.4596 6.23219 22.4596 12C22.4596 17.7678 17.7678 22.4596 12 22.4596Z" fill="currentColor" />
                  </svg>
                </span>
              </a>
              <!-- YouTube -->
              <a href="#" aria-label="YouTube" class="text-gray-500 hover:text-gray-800">
                <span class="h-6 w-6" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6">
                    <path d="M18.4115 10.418C18.4115 8.87113 17.1561 7.61572 15.6093 7.61572H8.39392C6.84708 7.61572 5.59167 8.87113 5.59167 10.418V13.5821C5.59167 15.1289 6.84708 16.3843 8.39392 16.3843H15.6093C17.1561 16.3843 18.4115 15.1289 18.4115 13.5821V10.418ZM14.4436 12.0833L11.2186 13.675C11.0937 13.7422 10.6517 13.6525 10.6517 13.5084V10.245C10.6517 10.1009 11.0969 10.008 11.2218 10.0817L14.3058 11.7566C14.4339 11.8303 14.5749 12.016 14.4468 12.0865L14.4436 12.0833Z" fill="currentColor" />
                    <path d="M12 0C5.38351 0 0 5.38351 0 12C0 18.6165 5.38351 24 12 24C18.6165 24 24 18.6165 24 12C24 5.38351 18.6165 0 12 0ZM12 22.4628C6.23219 22.4628 1.53723 17.771 1.53723 12C1.53723 6.22898 6.22898 1.54043 12 1.54043C17.771 1.54043 22.4628 6.23219 22.4628 12C22.4628 17.7678 17.771 22.4628 12 22.4628Z" fill="currentColor" />
                  </svg>
                </span>
              </a>
            </div>
          </div>
          <div>
            <p class="text-xs text-slate-600 leading-snug">© تأمينكم 2026. جميع الحقوق محفوظة</p>
          </div>
        </section>

        <!-- Mobile Navigation (Accordion) -->
        <nav class="flex flex-col gap-4 xl:hidden border-b border-slate-300 py-2">
          <AccordionRoot type="single" collapsible>
            <AccordionItem v-for="section in footerSections" :key="section.title" :value="section.title" class="border-b-0">
              <AccordionHeader class="flex mb-0">
                <AccordionTrigger class="px-0 flex flex-1 items-center justify-between transition-all py-2 font-semibold text-md [&[data-state=open]>svg]:rotate-180">
                  {{ section.title }}
                  <svg viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-[10px] w-[10px] shrink-0 transition-transform duration-200">
                    <path d="M1.25 1.375L6.5 6.625L11.75 1.375" stroke="currentColor" stroke-width="1.3125" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </AccordionTrigger>
              </AccordionHeader>
              <AccordionContent class="overflow-hidden text-sm transition-all data-[state=closed]:animate-collapse-up data-[state=open]:animate-collapse-down">
                <ul class="pb-4 flex flex-col gap-3">
                  <li v-for="link in section.links" :key="link.label" class="ps-1 text-slate-600 text-sm font-normal hover:text-blue-600">
                    <a v-if="link.external" :href="link.href" target="_blank" rel="noreferrer noopener">{{ link.label }}</a>
                    <router-link v-else :to="link.href">{{ link.label }}</router-link>
                  </li>
                </ul>
              </AccordionContent>
            </AccordionItem>
          </AccordionRoot>
        </nav>

        <!-- Desktop Navigation -->
        <nav class="hidden xl:flex grow justify-between gap-16">
          <div v-for="section in footerSections" :key="section.title">
            <h3 class="mb-6 font-semibold text-md text-slate-900">{{ section.title }}</h3>
            <div class="flex flex-col gap-4 text-balance">
              <ul class="text-muted-foreground gap-y-4 gap-x-12 grid grid-rows-5 grid-flow-col">
                <li v-for="link in section.links" :key="link.label" class="ps-1 text-slate-600 text-sm font-normal hover:text-blue-600">
                  <a v-if="link.external" :href="link.href" target="_blank" rel="noreferrer noopener">{{ link.label }}</a>
                  <router-link v-else :to="link.href">{{ link.label }}</router-link>
                </li>
              </ul>
            </div>
          </div>
        </nav>
      </section>

      <!-- App Download Section removed -->
    </div>
  </footer>
</template>

<script setup>
import {
  AccordionRoot,
  AccordionItem,
  AccordionHeader,
  AccordionTrigger,
  AccordionContent,
} from 'radix-vue'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const isOrderReview = computed(() => route.name === 'orderReview')

const footerSections = [
  {
    title: 'المنتجات',
    links: [
      { label: 'تأمين السيارات', href: '/' },
      { label: 'تأمين الصحي للمنشآت', href: '/' },
      { label: 'تأمين ضد الأخطاء الطبية', href: '/' },
      { label: 'تأمين العمالة المنزلية', href: '/' },
      { label: 'تأمين السفر', href: '/' },
      { label: 'تأمين المنزل', href: '/' },
      { label: 'تأمين الإقامة المميزة', href: '/' },
      { label: 'الحماية والادخار', href: '/' },
      { label: 'نقل البضائع', href: '/' },
    ],
  },
  {
    title: 'الدعم',
    links: [
      { label: 'تواصل معنا', href: '/contact' },
      { label: 'سياسة الخصوصية', href: '/privacy' },
      { label: 'الشروط والأحكام', href: '/terms' },
      { label: 'سياسة الاستخدام المقبول', href: '/acceptable-use' },
      { label: 'حقوق الملكية الفكرية', href: '/dmca' },
    ],
  },
  {
    title: 'الموارد',
    links: [
      { label: 'الأسئلة الشائعة', href: '/faq' },
      { label: 'مدونة تأمينكم', href: '/blog' },
    ],
  },
]
</script>

<style scoped>
[data-state='open'] > svg {
  transform: rotate(180deg);
}
</style>
