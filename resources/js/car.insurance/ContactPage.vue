<template>
    <div class="contact-page">
        <!-- Page Header -->
        <div class="contact-header">
            <div class="contact-header__inner">
                <h1 class="contact-header__title">تواصل معنا</h1>
                <p class="contact-header__subtitle">نسعد بتواصلك معنا ونحرص على الرد في أسرع وقت</p>
            </div>
        </div>

        <!-- Quick Contact Channels -->
        <section class="contact-channels">
            <div class="contact-channels__inner">
                <div class="contact-channels__grid">
                    <a href="tel:920000000" class="contact-channel">
                        <div class="contact-channel__icon contact-channel__icon--blue">
                            <svg style="width:24px;height:24px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="contact-channel__label">الهاتف الموحد</p>
                            <span class="contact-channel__value contact-channel__ltr">920000000</span>
                        </div>
                    </a>

                    <a href="mailto:info@example.com" class="contact-channel">
                        <div class="contact-channel__icon contact-channel__icon--green">
                            <svg style="width:24px;height:24px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="contact-channel__label">البريد الإلكتروني</p>
                            <span class="contact-channel__value">info@example.com</span>
                        </div>
                    </a>

                    <a href="#" class="contact-channel">
                        <div class="contact-channel__icon contact-channel__icon--slate">
                            <svg style="width:24px;height:24px" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                        </div>
                        <div>
                            <p class="contact-channel__label">تابعنا</p>
                            <span class="contact-channel__value">@taminkom</span>
                        </div>
                    </a>

                    <div class="contact-channel">
                        <div class="contact-channel__icon contact-channel__icon--orange">
                            <svg style="width:24px;height:24px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="contact-channel__label">ساعات العمل</p>
                            <span class="contact-channel__value">الأحد - الخميس</span>
                            <span class="contact-channel__sub contact-channel__ltr">8:00 ص - 5:00 م</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content: Form + Sidebar -->
        <section class="contact-main">
            <div class="contact-main__inner">
                <div class="contact-layout">
                    <!-- Contact Form -->
                    <div class="contact-form-wrap">
                        <div class="contact-card">
                            <h2 class="contact-card__title">أرسل لنا رسالة</h2>
                            <p class="contact-card__desc">املأ النموذج أدناه وسنعود إليك في أقرب وقت ممكن</p>

                            <!-- Success State -->
                            <div v-if="submitted" class="contact-success">
                                <div class="contact-success__icon">
                                    <svg style="width:40px;height:40px;color:#1db97d" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="contact-success__title">تم إرسال رسالتك بنجاح!</h3>
                                <p class="contact-success__desc">سنتواصل معك في أقرب وقت ممكن.</p>
                                <button class="contact-success__btn" @click="resetForm">إرسال رسالة أخرى</button>
                            </div>

                            <!-- Form -->
                            <form v-else class="contact-form" @submit.prevent="submitForm">
                                <div class="contact-form__row">
                                    <div class="contact-form__field">
                                        <label for="contact-name" class="contact-form__label">الاسم الكامل <span
                                                class="contact-form__required">*</span></label>
                                        <input id="contact-name" v-model="form.name" type="text" name="contact-name"
                                            autocomplete="name" placeholder="أدخل اسمك" class="contact-form__input"
                                            :class="{ 'contact-form__input--error': errors.name }" required />
                                        <p v-if="errors.name" class="contact-form__error">{{ errors.name }}</p>
                                    </div>
                                    <div class="contact-form__field">
                                        <label for="contact-email" class="contact-form__label">البريد الإلكتروني <span
                                                class="contact-form__required">*</span></label>
                                        <input id="contact-email" v-model="form.email" type="email" name="contact-email"
                                            autocomplete="email" placeholder="example@email.com"
                                            class="contact-form__input contact-form__input--ltr"
                                            :class="{ 'contact-form__input--error': errors.email }" required />
                                        <p v-if="errors.email" class="contact-form__error">{{ errors.email }}</p>
                                    </div>
                                </div>

                                <div class="contact-form__row">
                                    <div class="contact-form__field">
                                        <label for="contact-phone" class="contact-form__label">رقم الجوال</label>
                                        <input id="contact-phone" v-model="form.phone" type="tel" name="contact-phone"
                                            autocomplete="tel" placeholder="05XXXXXXXX" maxlength="10"
                                            class="contact-form__input contact-form__input--ltr" />
                                    </div>
                                    <div class="contact-form__field">
                                        <label for="contact-subject" class="contact-form__label">الموضوع <span
                                                class="contact-form__required">*</span></label>
                                        <select id="contact-subject" v-model="form.subject" name="contact-subject"
                                            class="contact-form__input contact-form__select" required>
                                            <option value="">اختر الموضوع</option>
                                            <option value="inquiry">استفسار عام</option>
                                            <option value="complaint">شكوى</option>
                                            <option value="suggestion">اقتراح</option>
                                            <option value="technical">مشكلة تقنية</option>
                                            <option value="partnership">شراكة تجارية</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="contact-form__field">
                                    <label for="contact-message" class="contact-form__label">الرسالة <span
                                            class="contact-form__required">*</span></label>
                                    <textarea id="contact-message" v-model="form.message" rows="5"
                                        name="contact-message" placeholder="اكتب رسالتك هنا..."
                                        class="contact-form__input contact-form__textarea"
                                        :class="{ 'contact-form__input--error': errors.message }" required></textarea>
                                    <p v-if="errors.message" class="contact-form__error">{{ errors.message }}</p>
                                </div>

                                <button type="submit" class="contact-form__submit">
                                    <svg style="width:18px;height:18px" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    إرسال الرسالة
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="contact-sidebar">
                        <!-- Location Card -->
                        <div class="contact-card">
                            <div class="contact-card__icon-header">
                                <div class="contact-card__icon-circle">
                                    <svg style="width:22px;height:22px;color:var(--color-primary)" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h3 class="contact-card__heading">العنوان</h3>
                            </div>
                            <p class="contact-card__text">الرياض، المملكة العربية السعودية</p>
                            <p class="contact-card__text">حي العليا، طريق الملك فهد</p>
                        </div>

                        <!-- FAQ Link Card -->
                        <div class="contact-faq-card">
                            <div class="contact-faq-card__icon">
                                <svg style="width:28px;height:28px;color:var(--color-primary)" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="contact-faq-card__title">لديك سؤال؟</h3>
                            <p class="contact-faq-card__desc">قد تجد إجابتك في صفحة الأسئلة الشائعة</p>
                            <router-link to="/faq" class="contact-faq-card__link">
                                زيارة الأسئلة الشائعة
                                <svg style="width:16px;height:16px" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                            </router-link>
                        </div>

                        <!-- Regulation Badge -->
                        <div class="contact-reg">
                            <svg style="width:20px;height:20px;color:var(--color-secondary);flex-shrink:0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <p class="contact-reg__text">منصة مقارنة أسعار التأمين في المملكة العربية السعودية</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { submitContact } from '@/api/contactApi';

const submitted = ref( false );
const submitting = ref( false );
const submitError = ref( '' );
const form = reactive( { name: '', email: '', phone: '', subject: '', message: '' } );
const errors = reactive( {} );

async function submitForm() {
    Object.keys( errors ).forEach( key => delete errors[ key ] );
    submitError.value = '';

    let valid = true;
    if ( !form.name.trim() ) { errors.name = 'يرجى إدخال الاسم'; valid = false; }
    if ( !form.email.trim() ) { errors.email = 'يرجى إدخال البريد الإلكتروني'; valid = false; }
    else if ( !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( form.email ) ) { errors.email = 'البريد الإلكتروني غير صحيح'; valid = false; }
    if ( !form.message.trim() ) { errors.message = 'يرجى كتابة الرسالة'; valid = false; }

    if ( valid ) {
        try {
            submitting.value = true;
            await submitContact( { ...form } );
            submitted.value = true;
        } catch ( error ) {
            submitError.value = error?.response?.data?.message || 'حدث خطأ أثناء الإرسال. يرجى المحاولة مرة أخرى.';
        } finally {
            submitting.value = false;
        }
    }
}

function resetForm() {
    form.name = '';
    form.email = '';
    form.phone = '';
    form.subject = '';
    form.message = '';
    submitted.value = false;
}
</script>

<style scoped>
/* ===== PAGE ===== */
.contact-page {
    background-color: var(--color-bg);
    min-height: 100vh;
}

/* ===== HEADER ===== */
.contact-header {
    background: linear-gradient(to bottom left, var(--color-primary), var(--color-primary-dark));
    color: white;
    padding: 56px 16px;
}

@media (min-width: 640px) {
    .contact-header {
        padding: 72px 24px;
    }
}

.contact-header__inner {
    max-width: 768px;
    margin: 0 auto;
    text-align: center;
}

.contact-header__title {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: var(--d2-font-size);
    line-height: var(--d2-line-height);
    letter-spacing: var(--d2-letter-spacing);
    font-weight: 800;
    margin-bottom: 12px;
    color: white;
}

.contact-header__subtitle {
    font-size: var(--t1-font-size);
    line-height: var(--t1-line-height);
    color: #bfdbfe;
}

/* ===== QUICK CHANNELS ===== */
.contact-channels {
    padding: 0 16px;
    margin-top: -32px;
    position: relative;
    z-index: 10;
}

@media (min-width: 640px) {
    .contact-channels {
        padding: 0 24px;
    }
}

.contact-channels__inner {
    max-width: 960px;
    margin: 0 auto;
}

.contact-channels__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

@media (min-width: 768px) {
    .contact-channels__grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
}

.contact-channel {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
    padding: 20px 12px;
    background: white;
    border-radius: 16px;
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    text-decoration: none;
    color: inherit;
    transition: box-shadow 0.2s, transform 0.2s;
}

.contact-channel:hover {
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1);
    transform: translateY(-2px);
}

@media (min-width: 768px) {
    .contact-channel {
        padding: 24px 16px;
    }
}

.contact-channel__icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.contact-channel__icon--blue {
    background: rgba(0, 136, 235, 0.1);
    color: var(--color-primary);
}

.contact-channel__icon--green {
    background: rgba(29, 185, 125, 0.1);
    color: var(--color-secondary);
}

.contact-channel__icon--slate {
    background: #f1f5f9;
    color: #334155;
}

.contact-channel__icon--orange {
    background: rgba(253, 181, 65, 0.12);
    color: #b4812e;
}

.contact-channel__label {
    font-size: var(--s2-font-size);
    line-height: var(--s2-line-height);
    font-weight: 600;
    color: var(--color-foreground);
    margin-bottom: 2px;
}

.contact-channel__value {
    font-size: 13px;
    color: var(--color-primary);
    font-weight: 500;
}

.contact-channel__sub {
    display: block;
    font-size: 12px;
    color: var(--color-muted);
    margin-top: 2px;
}

.contact-channel__ltr {
    direction: ltr;
    unicode-bidi: embed;
}

/* ===== MAIN SECTION ===== */
.contact-main {
    padding: 40px 16px 64px;
}

@media (min-width: 640px) {
    .contact-main {
        padding: 48px 24px 80px;
    }
}

.contact-main__inner {
    max-width: 960px;
    margin: 0 auto;
}

/* ===== LAYOUT ===== */
.contact-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

@media (min-width: 992px) {
    .contact-layout {
        grid-template-columns: 1fr 320px;
        gap: 32px;
    }
}

/* ===== CARD (shared) ===== */
.contact-card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--color-border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    padding: 24px;
}

@media (min-width: 640px) {
    .contact-card {
        padding: 32px;
    }
}

.contact-card__title {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: var(--h2-font-size);
    line-height: var(--h2-line-height);
    font-weight: 700;
    color: var(--color-foreground);
    margin-bottom: 6px;
}

.contact-card__desc {
    font-size: var(--s2-font-size);
    line-height: var(--s2-line-height);
    color: var(--color-muted);
    margin-bottom: 28px;
}

.contact-card__icon-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.contact-card__icon-circle {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 12px;
    background: rgba(0, 136, 235, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
}

.contact-card__heading {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: var(--t1-font-size);
    line-height: var(--t1-line-height);
    font-weight: 700;
    color: var(--color-foreground);
}

.contact-card__text {
    font-size: var(--s2-font-size);
    line-height: 1.7;
    color: var(--color-muted);
}

/* ===== FORM ===== */
.contact-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.contact-form__row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 640px) {
    .contact-form__row {
        grid-template-columns: 1fr 1fr;
    }
}

.contact-form__field {
    display: flex;
    flex-direction: column;
}

.contact-form__label {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-foreground);
    margin-bottom: 8px;
}

.contact-form__required {
    color: var(--color-destructive);
}

.contact-form__input {
    width: 100%;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 14px;
    color: var(--color-foreground);
    background: white;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    font-family: inherit;
}

.contact-form__input::placeholder {
    color: #94a3b8;
}

.contact-form__input:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(0, 136, 235, 0.12);
}

.contact-form__input--error {
    border-color: var(--color-destructive) !important;
}

.contact-form__input--ltr {
    direction: ltr;
    text-align: left;
}

.contact-form__select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: left 14px center;
    background-size: 16px;
    padding-left: 36px;
}

.contact-form__textarea {
    resize: none;
    min-height: 120px;
}

.contact-form__error {
    font-size: 12px;
    color: var(--color-destructive);
    margin-top: 6px;
}

.contact-form__submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px 32px;
    border-radius: 12px;
    border: none;
    background: var(--color-primary);
    color: white;
    font-family: inherit;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

@media (min-width: 640px) {
    .contact-form__submit {
        width: auto;
    }
}

.contact-form__submit:hover {
    background: var(--color-primary-dark);
}

/* ===== SUCCESS ===== */
.contact-success {
    text-align: center;
    padding: 48px 0;
}

.contact-success__icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(29, 185, 125, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.contact-success__title {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: var(--h2-font-size);
    line-height: var(--h2-line-height);
    font-weight: 700;
    color: var(--color-foreground);
    margin-bottom: 8px;
}

.contact-success__desc {
    font-size: 14px;
    color: var(--color-muted);
    margin-bottom: 24px;
}

.contact-success__btn {
    background: none;
    border: none;
    color: var(--color-primary);
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 4px;
}

.contact-success__btn:hover {
    color: var(--color-primary-dark);
}

/* ===== SIDEBAR ===== */
.contact-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* FAQ Card */
.contact-faq-card {
    background: rgba(0, 136, 235, 0.04);
    border: 1px solid rgba(0, 136, 235, 0.12);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
}

.contact-faq-card__icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(0, 136, 235, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}

.contact-faq-card__title {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: var(--t1-font-size);
    line-height: var(--t1-line-height);
    font-weight: 700;
    color: var(--color-foreground);
    margin-bottom: 6px;
}

.contact-faq-card__desc {
    font-size: 13px;
    color: var(--color-muted);
    margin-bottom: 16px;
}

.contact-faq-card__link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 600;
    color: var(--color-primary);
    text-decoration: none;
    transition: color 0.15s;
}

.contact-faq-card__link:hover {
    color: var(--color-primary-dark);
    text-decoration: underline;
    text-underline-offset: 4px;
}

/* Regulation */
.contact-reg {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: rgba(29, 185, 125, 0.06);
    border: 1px solid rgba(29, 185, 125, 0.15);
    border-radius: 12px;
    padding: 16px;
}

.contact-reg__text {
    font-size: 12px;
    line-height: 1.7;
    color: var(--color-muted);
}
</style>
