<template>
    <div class="min-h-screen bg-slate-50" dir="rtl">
        <FunnelProgress :current="3" />
        <!-- Success Header -->
        <div class="bg-gradient-to-bl from-secondary to-green-600 text-white">
            <div class="box py-10 text-center">
                <div
                    class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-5 animate-scale-in">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="typ-d2 text-white mb-2">تم الشراء بنجاح! 🎉</h1>
                <p class="typ-s1 text-white/80">تم إصدار وثيقة التأمين الخاصة بك</p>
                <p v-if="order" class="typ-c1 text-white/60 mt-2 ltr-nums">رقم الطلب: {{ order.orderNumber }}</p>
            </div>
        </div>

        <!-- Content -->
        <div v-if="order" class="box py-8">
            <div class="max-w-3xl mx-auto space-y-6">

                <!-- Policy Document Card -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-in">
                    <div class="bg-primary/5 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="typ-h3 text-foreground flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            بيانات الوثيقة
                        </h2>
                        <span
                            class="inline-flex items-center gap-1 typ-c1 font-medium px-3 py-1 rounded-full bg-green-100 text-green-700">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            سارية
                        </span>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <p class="typ-c1 text-muted mb-0.5">شركة التأمين</p>
                                    <p class="typ-s1 font-medium text-foreground">{{ order.plan.companyName }}</p>
                                </div>
                                <div>
                                    <p class="typ-c1 text-muted mb-0.5">نوع التأمين</p>
                                    <span class="inline-block typ-c1 font-medium px-2.5 py-0.5 rounded-full"
                                        :class="order.plan.type === 'comprehensive' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">
                                        {{ order.plan.typeAr }}
                                    </span>
                                </div>
                                <div>
                                    <p class="typ-c1 text-muted mb-0.5">اسم الوثيقة</p>
                                    <p class="typ-s1 font-medium text-foreground">{{ order.plan.name }}</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <p class="typ-c1 text-muted mb-0.5">رقم الوثيقة</p>
                                    <p class="typ-s1 font-medium text-foreground ltr-nums">{{ policyNumber }}</p>
                                </div>
                                <div>
                                    <p class="typ-c1 text-muted mb-0.5">تاريخ البداية</p>
                                    <p class="typ-s1 font-medium text-foreground ltr-nums">{{ formattedStartDate }}</p>
                                </div>
                                <div>
                                    <p class="typ-c1 text-muted mb-0.5">تاريخ الانتهاء</p>
                                    <p class="typ-s1 font-medium text-foreground ltr-nums">{{ formattedEndDate }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applicant Info Card -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-in"
                    style="animation-delay: 0.1s">
                    <div class="bg-primary/5 px-6 py-4 border-b border-slate-200">
                        <h2 class="typ-h3 text-foreground flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            بيانات المؤمن له
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="typ-c1 text-muted mb-0.5">الاسم الكامل</p>
                                <p class="typ-s1 font-medium text-foreground">{{ order.applicant.fullName }}</p>
                            </div>
                            <div>
                                <p class="typ-c1 text-muted mb-0.5">رقم الهوية / الإقامة</p>
                                <p class="typ-s1 font-medium text-foreground ltr-nums">{{
                                    maskedIdentity }}</p>
                            </div>
                            <div>
                                <p class="typ-c1 text-muted mb-0.5">رقم الجوال</p>
                                <p class="typ-s1 font-medium text-foreground ltr-nums" dir="ltr">+966{{
                                    order.applicant.phone }}</p>
                            </div>
                            <div>
                                <p class="typ-c1 text-muted mb-0.5">البريد الإلكتروني</p>
                                <p class="typ-s1 font-medium text-foreground" dir="ltr">{{ order.applicant.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary Card -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-in"
                    style="animation-delay: 0.2s">
                    <div class="bg-primary/5 px-6 py-4 border-b border-slate-200">
                        <h2 class="typ-h3 text-foreground flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            ملخص الدفع
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted">القسط الأساسي</span>
                                <span class="font-medium ltr-nums">{{ formatPrice(order.pricing.subtotal -
                                    addonsTotal) }}</span>
                            </div>
                            <div v-for="addon in order.pricing.addons" :key="addon.name"
                                class="flex justify-between text-secondary">
                                <span>{{ addon.name }}</span>
                                <span class="font-medium ltr-nums">+{{ formatPrice(addon.price) }}</span>
                            </div>
                            <hr class="border-slate-200" />
                            <div class="flex justify-between">
                                <span class="text-muted">المجموع قبل الضريبة</span>
                                <span class="font-medium ltr-nums">{{ formatPrice(order.pricing.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted">ضريبة القيمة المضافة (15%)</span>
                                <span class="font-medium ltr-nums">{{ formatPrice(order.pricing.vat) }}</span>
                            </div>
                            <hr class="border-slate-200" />
                            <div class="flex justify-between text-base pt-1">
                                <span class="font-bold text-foreground">المبلغ المدفوع</span>
                                <span class="font-extrabold text-primary ltr-nums text-lg">{{
                                    formatPrice(order.pricing.total) }}</span>
                            </div>
                            <div class="flex justify-between text-sm pt-2">
                                <span class="text-muted">طريقة الدفع</span>
                                <span class="font-medium">{{ paymentMethodLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps Card -->
                <div class="bg-blue-50 rounded-2xl border border-blue-100 p-6 animate-fade-in"
                    style="animation-delay: 0.3s">
                    <h3 class="typ-t2 text-foreground mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        الخطوات التالية
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div
                                class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center typ-c1 font-bold shrink-0">
                                1</div>
                            <div>
                                <p class="typ-s2 font-medium text-foreground">تم إرسال الوثيقة</p>
                                <p class="typ-c1 text-muted">تم إرسال نسخة من الوثيقة على بريدك الإلكتروني
                                    ورقم الجوال</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center typ-c1 font-bold shrink-0">
                                2</div>
                            <div>
                                <p class="typ-s2 font-medium text-foreground">تسجيل الوثيقة في نجم</p>
                                <p class="typ-c1 text-muted">سيتم تسجيل وثيقتك تلقائياً في نظام نجم خلال 24 ساعة</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center typ-c1 font-bold shrink-0">
                                3</div>
                            <div>
                                <p class="typ-s2 font-medium text-foreground">التحديث التلقائي في أبشر</p>
                                <p class="typ-c1 text-muted">سيتم تحديث حالة التأمين في منصة أبشر تلقائياً</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 animate-fade-in" style="animation-delay: 0.4s">
                    <button class="flex-1 flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white py-3.5 rounded-xl font-bold transition-colors cursor-pointer"
                        @click="downloadPolicy">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        تحميل الوثيقة PDF
                    </button>
                    <router-link to="/"
                        class="flex-1 flex items-center justify-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-foreground py-3.5 rounded-xl font-bold transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        العودة للرئيسية
                    </router-link>
                </div>

                <!-- Support Notice -->
                <div class="text-center typ-c1 text-muted pb-8">
                    <p>لأي استفسارات يرجى التواصل مع خدمة العملاء على الرقم
                        <a href="tel:920000XXX" class="text-primary font-medium ltr-nums" dir="ltr">920000XXX</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- No Order State -->
        <div v-else class="max-w-md mx-auto px-4 py-20 text-center">
            <div class="text-5xl mb-4">📋</div>
            <h2 class="typ-h2 text-foreground mb-3">لا يوجد طلب</h2>
            <p class="typ-s1 text-muted mb-6">لم يتم العثور على بيانات الطلب</p>
            <router-link to="/"
                class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-medium hover:bg-primary-dark transition-colors">
                العودة للرئيسية
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { formatPrice } from '@/data';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { trackStepViewed, trackOrderConfirmed } from '@/composables/useFunnelTracking';
import FunnelProgress from '@/car.insurance/components/FunnelProgress.vue';

const { trackStep } = useQuoteTracking();

const order = ref( null );

onMounted( () => {
    const raw = sessionStorage.getItem( 'orderData' );
    if ( raw ) {
        try { order.value = JSON.parse( raw ); } catch { /* ignore */ }
    }
    if ( order.value ) {
        trackStep( 'confirmation', 7, { order_number: order.value.orderNumber }, 'complete' );
        trackStepViewed( 'confirmation', { order_number: order.value.orderNumber } );
        trackOrderConfirmed( { order_number: order.value.orderNumber } );
    }
} );

//
const policyNumber = computed( () => {
    if ( !order.value ) return '';
    // Use server-generated policy number; fall back to derived one
    if ( order.value.policyNumber ) return order.value.policyNumber;
    return 'POL-' + order.value.orderNumber?.replace( 'ORD-', '' ) + '-SA';
} );

const formattedStartDate = computed( () => {
    if ( !order.value ) return '';
    // استخدم policyStartDate الحقيقي أولاً ثم orderDate كبديل
    const raw = order.value.policyStartDate || order.value.orderDate;
    if ( !raw ) return '';
    let d;
    if ( raw.includes( '/' ) ) {
        const parts = raw.split( '/' );
        d = new Date( parts[ 2 ], parts[ 1 ] - 1, parts[ 0 ] );
    } else {
        d = new Date( raw );
    }
    if ( isNaN( d.getTime() ) ) return '';
    return d.toLocaleDateString( 'ar-SA-u-nu-latn', { year: 'numeric', month: 'long', day: 'numeric' } );
} );

const formattedEndDate = computed( () => {
    if ( !order.value ) return '';
    const raw = order.value.policyStartDate || order.value.orderDate;
    if ( !raw ) return '';
    let d;
    if ( raw.includes( '/' ) ) {
        const parts = raw.split( '/' );
        d = new Date( parts[ 2 ], parts[ 1 ] - 1, parts[ 0 ] );
    } else {
        d = new Date( raw );
    }
    if ( isNaN( d.getTime() ) ) return '';
    d.setFullYear( d.getFullYear() + 1 );
    d.setDate( d.getDate() - 1 );
    return d.toLocaleDateString( 'ar-SA-u-nu-latn', { year: 'numeric', month: 'long', day: 'numeric' } );
} );

const maskedIdentity = computed( () => {
    if ( !order.value?.applicant?.identityNumber ) return '';
    const id = String( order.value.applicant.identityNumber );
    if ( id.length < 4 ) return id;
    return id.slice( 0, 2 ) + '••••••' + id.slice( -2 );
} );

const addonsTotal = computed( () => {
    return ( order.value?.pricing?.addons || [] ).reduce( ( sum, a ) => sum + a.price, 0 );
} );

const paymentMethodLabel = computed( () => {
    const map = { card: 'بطاقة (فيزا / ماستركارد / مدى)', sadad: 'سداد', mada: 'مدى', visa: 'فيزا / ماستركارد', applepay: 'Apple Pay' };
    return map[ order.value?.paymentMethod ] || order.value?.paymentMethod || '';
} );

function downloadPolicy() {
    if ( !order.value ) return;

    /** Escape a value so it's safe inside HTML text / attribute context */
    const esc = ( v ) => String( v ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );

    // Build a printable summary and trigger PDF-style download via browser print
    const content = `
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <title>وثيقة التأمين - ${ esc( order.value.orderId || '' ) }</title>
            <style>
                body { font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif; padding: 40px; direction: rtl; color: #1a1a2e; }
                h1 { color: #16a34a; font-size: 24px; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
                h2 { font-size: 18px; margin-top: 28px; color: #374151; }
                table { width: 100%; border-collapse: collapse; margin: 12px 0; }
                td { padding: 8px 12px; border: 1px solid #e5e7eb; }
                td:first-child { background: #f9fafb; font-weight: 600; width: 40%; }
                .total { font-size: 20px; font-weight: 700; color: #16a34a; margin-top: 20px; }
                @media print { body { padding: 20px; } }
            </style>
        </head>
        <body>
            <h1>وثيقة تأمين المركبات</h1>
            <table>
                <tr><td>رقم الطلب</td><td>${ esc( order.value.orderId || '—' ) }</td></tr>
                <tr><td>تاريخ الطلب</td><td>${ esc( formattedStartDate.value ) }</td></tr>
                <tr><td>شركة التأمين</td><td>${ esc( order.value.companyName || '—' ) }</td></tr>
                <tr><td>نوع التغطية</td><td>${ esc( order.value.policyType || '—' ) }</td></tr>
            </table>
            <h2>بيانات المؤمّن له</h2>
            <table>
                <tr><td>الاسم</td><td>${ esc( order.value.applicant?.name || '—' ) }</td></tr>
                <tr><td>رقم الهوية</td><td>${ esc( maskedIdentity.value ) }</td></tr>
            </table>
            <h2>بيانات المركبة</h2>
            <table>
                <tr><td>المركبة</td><td>${ esc( order.value.vehicleName || '—' ) }</td></tr>
                <tr><td>الموديل</td><td>${ esc( order.value.vehicleYear || '—' ) }</td></tr>
                <tr><td>الرقم التسلسلي</td><td>${ esc( order.value.sequenceNumber || '—' ) }</td></tr>
            </table>
            <p class="total">الإجمالي: ${ esc( order.value.pricing?.total?.toLocaleString( 'ar-SA' ) || '—' ) } ر.س</p>
        </body>
        </html>
    `;

    const blob = new Blob( [ content ], { type: 'text/html' } );
    const blobUrl = URL.createObjectURL( blob );
    const printWindow = window.open( blobUrl, '_blank' );
    if ( printWindow ) {
        printWindow.addEventListener( 'load', () => {
            printWindow.focus();
            printWindow.print();
            URL.revokeObjectURL( blobUrl );
        } );
    } else {
        URL.revokeObjectURL( blobUrl );
    }
}
</script>
