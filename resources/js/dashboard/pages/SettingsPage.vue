<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold font-heading" :style="{ color: 'var(--admin-text)' }">الإعدادات</h1>
            <p class="text-sm mt-1" :style="{ color: 'var(--admin-text-muted)' }">إدارة إعدادات النظام والتفضيلات</p>
        </div>

        <div class="mb-6 rounded-xl border p-2" :style="{ backgroundColor: '#0b1220', borderColor: '#1f2937', boxShadow: '0 14px 28px rgba(2, 6, 23, 0.35)' }">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                <button
                    v-for="tab in tabItems"
                    :key="tab.key"
                    type="button"
                    class="group flex items-center gap-3 rounded-lg border px-4 py-3 text-right transition-all duration-200"
                    :class="activeTab === tab.key ? 'shadow-sm' : 'border-transparent'"
                    :style="activeTab === tab.key
                        ? { borderColor: '#334155', backgroundColor: '#111827', boxShadow: '0 8px 18px rgba(15, 23, 42, 0.22)', color: '#f8fafc' }
                        : { backgroundColor: 'rgba(15, 23, 42, 0.22)', color: '#94a3b8', borderColor: 'transparent' }"
                    @click="activeTab = tab.key"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-lg text-sm transition-all duration-200"
                        :style="activeTab === tab.key
                            ? { backgroundColor: 'rgba(148, 163, 184, 0.16)', color: '#f8fafc' }
                            : { backgroundColor: 'rgba(148, 163, 184, 0.08)', color: '#cbd5e1' }"
                    >
                        <i class="fa-solid" :class="tab.icon" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold">{{ tab.label }}</span>
                        <span class="block text-[11px] opacity-80">{{ tab.subtitle }}</span>
                    </span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div v-if="activeTab === 'general'" class="lg:col-span-2 space-y-6">
                <div id="general-settings" class="rounded-xl p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md md:p-5"
                    :style="{ backgroundColor: '#0f172a', borderWidth: '1px', borderColor: '#1f2937', boxShadow: '0 12px 24px rgba(2, 6, 23, 0.28)' }">
                    <button type="button" class="mb-5 flex w-full items-center justify-between gap-3 rounded-xl border border-transparent bg-transparent px-1 py-2 text-right transition-colors hover:border-slate-200 hover:bg-slate-50/60" @click="generalPanel.base = !generalPanel.base">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <i class="fa-solid fa-sliders-h" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h2 class="text-lg font-bold font-heading" :style="{ color: 'var(--admin-text)' }">الإعدادات العامة</h2>
                                <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">اسم المنصة، التواصل، والهوية البصرية</p>
                            </div>
                        </div>
                        <i class="fa-solid text-sm" :class="generalPanel.base ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true"></i>
                    </button>

                    <div v-show="generalPanel.base" class="space-y-4">
                        <div>
                            <label for="siteName" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">اسم المنصة</label>
                            <input id="siteName" v-model="settings.siteName" type="text" name="siteName" autocomplete="organization" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" />
                        </div>
                        <div>
                            <label for="siteDescription" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">وصف المنصة</label>
                            <textarea id="siteDescription" v-model="settings.siteDescription" rows="3" name="siteDescription" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }"></textarea>
                        </div>
                        <div>
                            <label for="contactEmail" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">البريد الإلكتروني للتواصل</label>
                            <input id="contactEmail" v-model="settings.contactEmail" type="email" dir="ltr" name="contactEmail" autocomplete="email" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-left" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" />
                        </div>
                        <div>
                            <label for="settingsPhone" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">رقم الهاتف</label>
                            <input id="settingsPhone" v-model="settings.phone" type="tel" dir="ltr" name="settingsPhone" autocomplete="tel" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-left" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md md:p-5" :style="{ backgroundColor: '#0f172a', borderWidth: '1px', borderColor: '#1f2937', boxShadow: '0 12px 24px rgba(2, 6, 23, 0.28)' }">
                    <button type="button" class="mb-5 flex w-full items-center justify-between gap-3 rounded-xl border border-transparent bg-transparent px-1 py-2 text-right transition-colors hover:border-slate-700 hover:bg-slate-800/60" @click="generalPanel.contact = !generalPanel.contact">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <i class="fa-solid fa-message" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h2 class="text-lg font-bold font-heading" :style="{ color: 'var(--admin-text)' }">بيانات التواصل العامة</h2>
                                <span class="text-xs px-2 py-1 rounded-full" :style="{ backgroundColor: 'var(--admin-status-info-bg)', color: 'var(--admin-accent-blue)' }">تظهر للعملاء</span>
                            </div>
                        </div>
                        <i class="fa-solid text-sm" :class="generalPanel.contact ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true"></i>
                    </button>

                    <div v-show="generalPanel.contact" class="space-y-4">
                        <p class="text-xs mb-4" :style="{ color: 'var(--admin-text-muted)' }">هذه القيم تظهر لكل العملاء في الموقع (زر واتساب العائم). تختلف عن الإعدادات العامة أعلاه التي تخص حسابك الإداري فقط.</p>

                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="text-sm font-medium" :style="{ color: 'var(--admin-text)' }">إظهار زر واتساب العائم</p>
                                <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">عند التعطيل يختفي الزر من كل صفحات العملاء</p>
                            </div>
                            <button role="switch" :aria-checked="siteSettings.whatsapp_enabled" aria-label="إظهار زر واتساب" type="button" class="relative w-11 h-6 rounded-full transition-colors" :class="siteSettings.whatsapp_enabled ? 'bg-[#25D366]' : ''" :style="siteSettings.whatsapp_enabled ? {} : { backgroundColor: 'var(--admin-input-border)' }" @click="siteSettings.whatsapp_enabled = !siteSettings.whatsapp_enabled">
                                <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" :class="siteSettings.whatsapp_enabled ? 'right-0.5' : 'left-0.5'"></span>
                            </button>
                        </div>

                        <div>
                            <label for="whatsappNumber" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">رقم واتساب</label>
                            <input id="whatsappNumber" v-model="siteSettings.whatsapp_number" type="tel" dir="ltr" placeholder="0597777777 أو 966597777777" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#25D366] focus:border-transparent outline-none text-left" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" />
                            <p class="text-xs mt-1" :style="{ color: 'var(--admin-text-muted)' }">يتم تحويل الرقم المحلي (يبدأ بـ 0) تلقائياً إلى الصيغة الدولية (966).</p>
                        </div>

                        <div>
                            <label for="whatsappMessage" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">الرسالة الافتراضية</label>
                            <textarea id="whatsappMessage" v-model="siteSettings.whatsapp_message" rows="2" placeholder="مرحباً، أرغب في الاستفسار..." class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#25D366] focus:border-transparent outline-none resize-none" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }"></textarea>
                        </div>

                        <div>
                            <label for="supportPhone" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">هاتف الدعم (اختياري)</label>
                            <input id="supportPhone" v-model="siteSettings.support_phone" type="tel" dir="ltr" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-left" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" />
                        </div>

                        <div>
                            <label for="contactEmailPublic" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">البريد الإلكتروني للتواصل العام (اختياري)</label>
                            <input id="contactEmailPublic" v-model="siteSettings.contact_email" type="email" dir="ltr" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-left" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" />
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <p v-if="siteSaveMessage" class="text-sm" :class="siteSaveMessage.includes('فشل') ? 'text-red-500' : 'text-green-600'">{{ siteSaveMessage }}</p>
                            <span v-else></span>
                            <button type="button" class="text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors bg-[#25D366] hover:bg-[#1ebe5b] disabled:opacity-60" :disabled="siteSaving" @click="saveSiteSettings">
                                {{ siteSaving ? 'جاري الحفظ...' : 'حفظ بيانات التواصل' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg md:p-5" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
                    <button type="button" class="mb-5 flex w-full items-center justify-between gap-3 rounded-xl border border-transparent bg-transparent px-1 py-2 text-right transition-colors hover:border-slate-200 hover:bg-slate-50/60" @click="generalPanel.notifications = !generalPanel.notifications">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <i class="fa-solid fa-bell" aria-hidden="true"></i>
                            </span>
                            <h2 class="text-lg font-bold font-heading" :style="{ color: 'var(--admin-text)' }">إعدادات الإشعارات</h2>
                        </div>
                        <i class="fa-solid text-sm" :class="generalPanel.notifications ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true"></i>
                    </button>

                    <div v-show="generalPanel.notifications" class="space-y-4">
                        <div class="flex items-center justify-between gap-4 py-2">
                            <div>
                                <p class="text-sm font-medium" :style="{ color: 'var(--admin-text)' }">التنبيهات الصوتية على هذا الجهاز</p>
                                <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">يشمل العملاء والدفع ورموز التحقق والمحادثة، ويُحفظ الاختيار في هذا المتصفح.</p>
                            </div>
                            <button type="button" role="switch" :aria-checked="soundsReady" aria-label="التنبيهات الصوتية على هذا الجهاز" class="relative h-6 w-11 shrink-0 rounded-full transition-colors" :class="soundsReady ? 'bg-blue-600' : ''" :style="soundsReady ? {} : { backgroundColor: 'var(--admin-input-border)' }" data-admin-sound-toggle @click="handleSoundToggle">
                                <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform" :class="soundsReady ? 'right-0.5' : 'left-0.5'"></span>
                            </button>
                        </div>
                        <p class="text-xs" :class="soundMessage.includes('تعذر') ? 'text-amber-500' : 'text-emerald-600'" aria-live="polite">{{ soundMessage }}</p>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'account'" class="lg:col-span-2 space-y-6">
                <div id="account-settings" class="rounded-xl p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md md:p-5" :style="{ backgroundColor: '#0f172a', borderWidth: '1px', borderColor: '#1f2937', boxShadow: '0 12px 24px rgba(2, 6, 23, 0.28)' }">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold font-heading" :style="{ color: 'var(--admin-text)' }">الحساب</h3>
                            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">معلومات الحساب الأساسية</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mb-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg" :style="{ backgroundColor: 'var(--admin-status-info-bg)', color: 'var(--admin-accent-blue)' }">
                            {{ userInfo.name?.charAt(0) || 'م' }}
                        </div>
                        <div>
                            <p class="text-sm font-bold" :style="{ color: 'var(--admin-text)' }">{{ userInfo.name }}</p>
                            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">{{ userInfo.email }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        إدارة بيانات حسابك الحالية من خلال قسم الأمان أو الإعدادات العامة.
                    </div>
                </div>
            </div>

            <div v-else class="lg:col-span-2 space-y-6">
                <div id="password-settings" class="rounded-xl p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md md:p-5" :style="{ backgroundColor: '#0f172a', borderWidth: '1px', borderColor: '#1f2937', boxShadow: '0 12px 24px rgba(2, 6, 23, 0.28)' }">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h2 class="text-lg font-bold font-heading" :style="{ color: 'var(--admin-text)' }">الأمان</h2>
                            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">تحديث كلمة المرور والضوابط الأمنية</p>
                        </div>
                    </div>
                    <form class="space-y-4" @submit.prevent="changePassword">
                        <input type="text" name="username" autocomplete="username" aria-hidden="true" tabindex="-1" style="position:absolute;width:0;height:0;overflow:hidden;opacity:0;pointer-events:none" />
                        <div>
                            <label for="currentPassword" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">كلمة المرور الحالية</label>
                            <input id="currentPassword" v-model="currentPassword" type="password" name="currentPassword" autocomplete="current-password" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" placeholder="أدخل كلمة المرور الحالية" />
                        </div>
                        <div>
                            <label for="newPassword" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">كلمة المرور الجديدة</label>
                            <input id="newPassword" v-model="newPassword" type="password" name="newPassword" autocomplete="new-password" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" placeholder="أدخل كلمة المرور الجديدة" />
                        </div>
                        <div>
                            <label for="confirmPassword" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-secondary)' }">تأكيد كلمة المرور</label>
                            <input id="confirmPassword" v-model="confirmPassword" type="password" name="confirmPassword" autocomplete="new-password" class="w-full rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" placeholder="أعد إدخال كلمة المرور" />
                        </div>
                        <p v-if="passwordError" class="text-red-500 text-sm">{{ passwordError }}</p>
                        <p v-if="passwordSuccess" class="text-green-600 text-sm">{{ passwordSuccess }}</p>
                        <button type="submit" class="text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors" :style="{ backgroundColor: 'var(--admin-text)', color: 'var(--admin-card-bg)' }">تغيير كلمة المرور</button>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl p-5 transition-colors duration-200" :style="{ backgroundColor: '#0f172a', borderWidth: '1px', borderColor: '#1f2937', boxShadow: '0 12px 24px rgba(2, 6, 23, 0.28)' }">
                    <h3 class="text-sm font-bold font-heading mb-4" :style="{ color: 'var(--admin-text)' }">معلومات النظام</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between"><span :style="{ color: 'var(--admin-text-muted)' }">إصدار النظام</span><span class="font-medium ltr-nums" :style="{ color: 'var(--admin-text)' }">2.1.0</span></div>
                        <div class="flex justify-between"><span :style="{ color: 'var(--admin-text-muted)' }">إصدار Laravel</span><span class="font-medium ltr-nums" :style="{ color: 'var(--admin-text)' }">12.x</span></div>
                        <div class="flex justify-between"><span :style="{ color: 'var(--admin-text-muted)' }">إصدار Vue</span><span class="font-medium ltr-nums" :style="{ color: 'var(--admin-text)' }">3.x</span></div>
                        <div class="flex justify-between"><span :style="{ color: 'var(--admin-text-muted)' }">آخر تحديث</span><span class="font-medium" :style="{ color: 'var(--admin-text)' }">2025/07/10</span></div>
                    </div>
                </div>

                <div id="account-settings-sidebar" class="rounded-xl p-5 transition-colors duration-200" :style="{ backgroundColor: '#0f172a', borderWidth: '1px', borderColor: '#1f2937', boxShadow: '0 12px 24px rgba(2, 6, 23, 0.28)' }">
                    <h3 class="text-sm font-bold font-heading mb-4" :style="{ color: 'var(--admin-text)' }">الحساب</h3>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg" :style="{ backgroundColor: 'var(--admin-status-info-bg)', color: 'var(--admin-accent-blue)' }">
                            {{ userInfo.name?.charAt(0) || 'م' }}
                        </div>
                        <div>
                            <p class="text-sm font-bold" :style="{ color: 'var(--admin-text)' }">{{ userInfo.name }}</p>
                            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">{{ userInfo.email }}</p>
                        </div>
                    </div>
                    <button class="w-full text-center text-sm py-2 rounded-xl transition-colors cursor-pointer" :style="{ backgroundColor: 'var(--admin-status-error-bg)', color: 'var(--admin-accent-red)' }" @click="handleLogout">تسجيل الخروج</button>
                </div>

                <button class="w-full text-white font-medium py-3 rounded-xl transition-colors" :style="{ backgroundColor: '#0f172a', color: '#fff', boxShadow: '0 10px 20px rgba(15, 23, 42, 0.16)' }" @click="saveSettings">
                    {{ saving ? 'جاري الحفظ...' : 'حفظ الإعدادات' }}
                </button>
                <p v-if="saveMessage" class="text-center text-sm mt-2" :class="saveMessage.includes('فشل') ? 'text-red-500' : 'text-green-600'">{{ saveMessage }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, watch } from 'vue';

defineOptions({ name: 'SettingsPage' });
import { useRoute, useRouter } from 'vue-router';
import { useUserStore } from '@/store/modules/user';
import { useAdminSounds } from '@/dashboard/composables/useAdminSounds';
import { fetchSettings, updateSettings, changePassword as apiChangePassword, fetchSiteSettings, updateSiteSettings } from '@/api/settings';
import logger from '@/utils/logger';

const router = useRouter();
const route = useRoute();
const userStore = useUserStore();
const { soundsEnabled, soundsReady, toggleSounds, playNewData } = useAdminSounds();

const saving = ref( false );
const loading = ref( false );
const currentPassword = ref( '' );
const newPassword = ref( '' );
const confirmPassword = ref( '' );
const passwordError = ref( '' );
const passwordSuccess = ref( '' );
const saveMessage = ref( '' );
const soundMessage = ref( '' );

async function handleSoundToggle () {
    const ready = await toggleSounds();
    if ( ready ) {
        soundMessage.value = 'تم تفعيل التنبيهات الصوتية على هذا الجهاز';
        await playNewData();
    } else if ( soundsEnabled.value ) {
        soundMessage.value = 'تعذر تشغيل الصوت؛ تحقق من إذن التشغيل في المتصفح ثم حاول مجددًا';
    } else {
        soundMessage.value = 'تم إيقاف التنبيهات الصوتية على هذا الجهاز';
    }
}

const tabItems = [
    { key: 'general', label: 'عام', subtitle: 'الهوية العامة', icon: 'fa-sliders-h' },
    { key: 'account', label: 'الحساب', subtitle: 'معلوماتك', icon: 'fa-user' },
    { key: 'security', label: 'الأمان', subtitle: 'كلمة المرور', icon: 'fa-shield-halved' },
];

const activeTab = ref( 'general' );
const generalPanel = reactive( {
    base: true,
    contact: true,
    notifications: true,
} );

const settings = reactive( {
    siteName: 'تأمينكم',
    siteDescription: 'منصة مقارنة أسعار التأمين في المملكة العربية السعودية',
    contactEmail: 'info@example.com',
    phone: '+966 11 234 5678',
    notifications: {
        weeklyReport: { label: 'تقرير أسبوعي', description: 'إرسال تقرير أداء أسبوعي', enabled: true },
        systemAlerts: { label: 'تنبيهات النظام', description: 'إشعارات صيانة وتحديثات النظام', enabled: true },
    },
} );

const userInfo = reactive( { name: 'مدير النظام', email: 'admin@example.com' } );

// Global site settings (WhatsApp, contact info shown to customers)
const siteSettings = reactive( {
    whatsapp_enabled: true,
    whatsapp_number: '',
    whatsapp_message: '',
    support_phone: '',
    contact_email: '',
} );
const siteSaving = ref( false );
const siteSaveMessage = ref( '' );
let siteSaveTimer = null;

async function loadSiteSettings () {
    try {
        const { data } = await fetchSiteSettings();
        if ( data?.data ) {
            Object.assign( siteSettings, data.data );
        }
    } catch ( e ) {
        logger.error( 'فشل تحميل بيانات التواصل العامة:', e );
    }
}

async function saveSiteSettings () {
    siteSaving.value = true;
    siteSaveMessage.value = '';
    try {
        const phonePattern = /^[\d+\s\-()]*$/;
        if ( !phonePattern.test( siteSettings.whatsapp_number || '' ) || !phonePattern.test( siteSettings.support_phone || '' ) ) {
            siteSaveMessage.value = 'رقم التواصل غير صالح. استخدم الأرقام وعلامة + والشرطات والأقواس فقط.';
            return;
        }
        if ( siteSettings.contact_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( siteSettings.contact_email ) ) {
            siteSaveMessage.value = 'البريد الإلكتروني غير صالح.';
            return;
        }
        const { data } = await updateSiteSettings( {
            whatsapp_enabled: siteSettings.whatsapp_enabled,
            whatsapp_number: siteSettings.whatsapp_number || '',
            whatsapp_message: siteSettings.whatsapp_message || '',
            support_phone: siteSettings.support_phone || '',
            contact_email: siteSettings.contact_email || '',
        } );
        if ( data?.data ) Object.assign( siteSettings, data.data );
        siteSaveMessage.value = 'تم حفظ بيانات التواصل بنجاح';
        if ( siteSaveTimer ) clearTimeout( siteSaveTimer );
        siteSaveTimer = setTimeout( () => { siteSaveMessage.value = ''; }, 3000 );
    } catch ( e ) {
        const firstError = e?.response?.data?.errors
            ? Object.values( e.response.data.errors )[ 0 ]?.[ 0 ]
            : null;
        siteSaveMessage.value = firstError || 'فشل في حفظ بيانات التواصل';
    } finally {
        siteSaving.value = false;
    }
}

async function loadSettings () {
    loading.value = true;
    try {
        const { data } = await fetchSettings();
        if ( data.settings ) {
            Object.assign( settings, data.settings );
        }
        if ( data.user ) {
            userInfo.name = data.user.name;
            userInfo.email = data.user.email;
        }
    } catch ( e ) {
        logger.error( 'فشل تحميل الإعدادات:', e );
    } finally {
        loading.value = false;
    }
}

const saveSettings = async () => {
    saving.value = true;
    saveMessage.value = '';
    try {
        const { data } = await updateSettings( {
            siteName: settings.siteName,
            siteDescription: settings.siteDescription,
            contactEmail: settings.contactEmail,
            phone: settings.phone,
            notifications: settings.notifications,
        } );
        if ( data.settings ) Object.assign( settings, data.settings );
        saveMessage.value = data.message || 'تم حفظ الإعدادات بنجاح';
        saveTimer = setTimeout( () => { saveMessage.value = ''; }, 3000 );
    } catch {
        saveMessage.value = 'فشل في حفظ الإعدادات';
    } finally {
        saving.value = false;
    }
};

const changePassword = async () => {
    passwordError.value = '';
    passwordSuccess.value = '';
    if ( !currentPassword.value ) {
        passwordError.value = 'يرجى إدخال كلمة المرور الحالية';
        return;
    }
    if ( !newPassword.value ) {
        passwordError.value = 'يرجى إدخال كلمة المرور الجديدة';
        return;
    }
    if ( newPassword.value.length < 8 ) {
        passwordError.value = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
        return;
    }
    if ( newPassword.value !== confirmPassword.value ) {
        passwordError.value = 'كلمة المرور الجديدة وتأكيدها غير متطابقين';
        return;
    }
    try {
        const { data } = await apiChangePassword( {
            current_password: currentPassword.value,
            new_password: newPassword.value,
            new_password_confirmation: confirmPassword.value,
        } );
        passwordSuccess.value = data.message || 'تم تغيير كلمة المرور بنجاح';
        currentPassword.value = '';
        newPassword.value = '';
        confirmPassword.value = '';
        passwordTimer = setTimeout( () => { passwordSuccess.value = ''; }, 3000 );
    } catch ( e ) {
        const errors = e.response?.data?.errors;
        passwordError.value = errors?.current_password?.[0] || errors?.new_password?.[0] || 'فشل في تغيير كلمة المرور';
    }
};

const handleLogout = async () => {
    if ( !confirm( 'هل أنت متأكد من تسجيل الخروج؟' ) ) return;
    await userStore.logout();
    router.push( { name: 'login' } );
};

function scrollToSection ( sectionName )
{
    const target = document.getElementById( sectionName );
    if ( !target ) return;

    target.scrollIntoView( {
        behavior: 'smooth',
        block: 'start'
    } );
}

watch(
    () => route.query.section,
    ( section ) => {
        if ( section === 'general' ) {
            activeTab.value = 'general';
            scrollToSection( 'general-settings' );
        }
        if ( section === 'account' ) {
            activeTab.value = 'account';
            scrollToSection( 'account-settings' );
        }
        if ( section === 'password' ) {
            activeTab.value = 'security';
            scrollToSection( 'password-settings' );
        }
    },
    { immediate: true }
);

let saveTimer = null;
let passwordTimer = null;

onMounted( () => {
    loadSettings();
    loadSiteSettings();
} );

onUnmounted( () => {
  clearTimeout( saveTimer );
  clearTimeout( passwordTimer );
  clearTimeout( siteSaveTimer );
} );
</script>
