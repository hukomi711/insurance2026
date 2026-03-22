<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 font-heading">الإعدادات</h1>
            <p class="text-sm text-gray-500 mt-1">إدارة إعدادات النظام والتفضيلات</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- General Settings -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-800 font-heading mb-5">الإعدادات العامة</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="siteName" class="block text-sm font-medium text-gray-700 mb-1">اسم
                                المنصة</label>
                            <input id="siteName" v-model="settings.siteName" type="text" name="siteName"
                                autocomplete="organization" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                        </div>
                        <div>
                            <label for="siteDescription" class="block text-sm font-medium text-gray-700 mb-1">وصف
                                المنصة</label>
                            <textarea id="siteDescription" v-model="settings.siteDescription" rows="3"
                                name="siteDescription"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none"></textarea>
                        </div>
                        <div>
                            <label for="contactEmail" class="block text-sm font-medium text-gray-700 mb-1">البريد
                                الإلكتروني للتواصل</label>
                            <input id="contactEmail" v-model="settings.contactEmail" type="email" dir="ltr"
                                name="contactEmail" autocomplete="email"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-left" />
                        </div>
                        <div>
                            <label for="settingsPhone" class="block text-sm font-medium text-gray-700 mb-1">رقم
                                الهاتف</label>
                            <input id="settingsPhone" v-model="settings.phone" type="tel" dir="ltr" name="settingsPhone"
                                autocomplete="tel" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-left" />
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-800 font-heading mb-5">إعدادات الإشعارات</h2>
                    <div class="space-y-4">
                        <div v-for="(notif, key) in settings.notifications" :key="key"
                            class="flex items-center justify-between py-2">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ notif.label }}</p>
                                <p class="text-xs text-gray-500">{{ notif.description }}</p>
                            </div>
                            <button role="switch"
                                :aria-checked="notif.enabled" :aria-label="notif.label" class="relative w-11 h-6 rounded-full transition-colors"
                                :class="notif.enabled ? 'bg-blue-600' : 'bg-gray-200'"
                                @click="notif.enabled = !notif.enabled">
                                <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                    :class="notif.enabled ? 'right-0.5' : 'left-0.5'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Security -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-800 font-heading mb-5">الأمان</h2>
                    <form class="space-y-4" @submit.prevent="changePassword">
                        <input type="text" name="username" autocomplete="username" aria-hidden="true" tabindex="-1"
                            style="position:absolute;width:0;height:0;overflow:hidden;opacity:0;pointer-events:none" />
                        <div>
                            <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-1">كلمة
                                المرور الحالية</label>
                            <input id="currentPassword" v-model="currentPassword" type="password" name="currentPassword"
                                autocomplete="current-password"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                placeholder="••••••••" />
                        </div>
                        <div>
                            <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور
                                الجديدة</label>
                            <input id="newPassword" v-model="newPassword" type="password" name="newPassword" autocomplete="new-password"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                placeholder="••••••••" />
                        </div>
                        <div>
                            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-1">تأكيد كلمة
                                المرور</label>
                            <input id="confirmPassword" v-model="confirmPassword" type="password" name="confirmPassword"
                                autocomplete="new-password"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                placeholder="••••••••" />
                        </div>
                        <p v-if="passwordError" class="text-red-500 text-sm">{{ passwordError }}</p>
                        <p v-if="passwordSuccess" class="text-green-600 text-sm">{{ passwordSuccess }}</p>
                        <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-5 py-2.5 rounded-xl hover:bg-gray-700 transition-colors">تغيير
                            كلمة المرور</button>
                    </form>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-800 font-heading mb-4">معلومات النظام</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">إصدار النظام</span><span
                                class="font-medium ltr-nums">2.1.0</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">إصدار Laravel</span><span
                                class="font-medium ltr-nums">12.x</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">إصدار Vue</span><span
                                class="font-medium ltr-nums">3.x</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">آخر تحديث</span><span
                                class="font-medium">2025/07/10</span></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-800 font-heading mb-4">الحساب</h3>
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                            {{ userInfo.name?.charAt(0) || 'م' }}</div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">{{ userInfo.name }}</p>
                            <p class="text-xs text-gray-500">{{ userInfo.email }}</p>
                        </div>
                    </div>
                    <button class="w-full text-center text-sm text-red-600 bg-red-50 py-2 rounded-xl hover:bg-red-100 transition-colors cursor-pointer"
                        @click="handleLogout">تسجيل
                        الخروج</button>
                </div>

                <!-- Save Button -->
                <button class="w-full bg-blue-600 text-white font-medium py-3 rounded-xl hover:bg-blue-700 transition-colors"
                    @click="saveSettings">
                    {{ saving ? 'جاري الحفظ...' : 'حفظ الإعدادات' }}
                </button>
                <p v-if="saveMessage" class="text-center text-sm mt-2" :class="saveMessage.includes('فشل') ? 'text-red-500' : 'text-green-600'">{{ saveMessage }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';

defineOptions({ name: 'SettingsPage' });
import { useRouter } from 'vue-router';
import { useUserStore } from '@/store/modules/user';
import { fetchSettings, updateSettings, changePassword as apiChangePassword } from '@/api/settings';
import logger from '@/utils/logger';

const router = useRouter();
const userStore = useUserStore();

const saving = ref( false );
const loading = ref( false );
const currentPassword = ref( '' );
const newPassword = ref( '' );
const confirmPassword = ref( '' );
const passwordError = ref( '' );
const passwordSuccess = ref( '' );
const saveMessage = ref( '' );

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

let saveTimer = null;
let passwordTimer = null;

onMounted( loadSettings );

onUnmounted( () => {
  clearTimeout( saveTimer );
  clearTimeout( passwordTimer );
} );
</script>
