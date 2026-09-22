<template>
  <ModalShell
    :open="open"
    title="إدارة المستخدمين"
    subtitle="التحكم بحسابات لوحة التحكم وصلاحياتها"
    size="lg"
    accent="#60a5fa"
    icon="fa-solid fa-users-gear"
    dir="rtl"
    @close="handleClose"
  >
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-400" aria-hidden="true"></i>
    </div>

    <!-- Load error -->
    <div v-else-if="loadError" class="py-12 text-center">
      <i class="fa-solid fa-circle-exclamation mb-3 text-3xl text-red-400" aria-hidden="true"></i>
      <p class="text-sm text-gray-400">{{ loadError }}</p>
      <AdminButton class="mt-4" variant="secondary" size="sm" label="إعادة المحاولة" @click="fetchUsersList" />
    </div>

    <template v-else>
      <!-- Add user — super_admin only -->
      <SectionCard v-if="userStore.isSuperAdmin" title="إضافة مستخدم جديد" emoji="➕" color="emerald">
        <form class="grid grid-cols-1 gap-3 sm:grid-cols-2" @submit.prevent="submitCreateUser">
          <div>
            <label class="mb-1 block text-xs text-gray-400" for="new-user-name">الاسم</label>
            <input
              id="new-user-name"
              v-model.trim="createForm.name"
              type="text"
              required
              class="admin-field"
              placeholder="اسم المستخدم"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-400" for="new-user-email">البريد الإلكتروني</label>
            <input
              id="new-user-email"
              v-model.trim="createForm.email"
              type="email"
              required
              dir="ltr"
              class="admin-field"
              placeholder="name@example.com"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-400" for="new-user-password">كلمة المرور</label>
            <input
              id="new-user-password"
              v-model="createForm.password"
              type="password"
              required
              autocomplete="new-password"
              class="admin-field"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-400" for="new-user-password-confirm">تأكيد كلمة المرور</label>
            <input
              id="new-user-password-confirm"
              v-model="createForm.password_confirmation"
              type="password"
              required
              autocomplete="new-password"
              class="admin-field"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-400" for="new-user-role">الدور</label>
            <select id="new-user-role" v-model="createForm.role" class="admin-field">
              <option value="viewer">مشاهد (Viewer)</option>
              <option value="admin">مشرف (Admin)</option>
              <option value="super_admin">مشرف عام (Super Admin)</option>
            </select>
          </div>
          <div class="flex items-end">
            <AdminButton
              type="submit"
              variant="accept"
              class="w-full"
              :loading="creating"
              :disabled="creating"
              label="إضافة المستخدم"
            />
          </div>
          <p v-if="createError" class="text-sm text-red-400 sm:col-span-2">{{ createError }}</p>
        </form>
      </SectionCard>

      <!-- Users list -->
      <SectionCard title="المستخدمون" emoji="👥" color="blue">
        <p v-if="users.length === 0" class="py-6 text-center text-sm text-gray-400">لا يوجد مستخدمون</p>

        <div v-else class="space-y-3">
          <div v-for="u in users" :key="u.id" class="admin-glass">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="truncate text-sm font-semibold text-white">{{ u.name }}</p>
                  <span class="admin-status-badge" :class="`admin-status-badge--${roleVariant(u.role)}`">{{ roleLabel(u.role) }}</span>
                  <span v-if="u.id === userStore.id" class="text-[10px] text-gray-500">(أنت)</span>
                </div>
                <p class="truncate text-xs text-gray-400" dir="ltr">{{ u.email }}</p>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <select
                  class="admin-field admin-field--sm"
                  :value="u.role"
                  :disabled="u.id === userStore.id || roleSaving === u.id"
                  aria-label="تغيير دور المستخدم"
                  @change="onRoleChange(u, $event.target.value)"
                >
                  <option value="viewer">مشاهد</option>
                  <option value="admin">مشرف</option>
                  <option value="super_admin">مشرف عام</option>
                </select>

                <AdminButton
                  variant="secondary"
                  size="sm"
                  label="كلمة المرور"
                  @click="togglePasswordEditor(u.id)"
                />

                <AdminButton
                  v-if="userStore.isSuperAdmin"
                  variant="reject"
                  size="sm"
                  label="حذف"
                  :disabled="u.id === userStore.id"
                  :loading="deleting === u.id"
                  @click="onDelete(u)"
                />
              </div>
            </div>

            <!-- Inline password editor -->
            <form
              v-if="passwordEditId === u.id"
              class="mt-3 grid grid-cols-1 gap-2 border-t border-white/10 pt-3 sm:grid-cols-3"
              @submit.prevent="submitPasswordChange(u)"
            >
              <input
                v-model="passwordDraft.password"
                type="password"
                required
                autocomplete="new-password"
                class="admin-field admin-field--sm"
                placeholder="كلمة المرور الجديدة"
              />
              <input
                v-model="passwordDraft.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="admin-field admin-field--sm"
                placeholder="تأكيد كلمة المرور"
              />
              <div class="flex items-center gap-2">
                <AdminButton
                  type="submit"
                  variant="accept"
                  size="sm"
                  label="حفظ"
                  :loading="passwordSaving"
                  :disabled="passwordSaving"
                />
                <AdminButton
                  type="button"
                  variant="ghost"
                  size="sm"
                  label="إلغاء"
                  :disabled="passwordSaving"
                  @click="passwordEditId = null"
                />
              </div>
              <p v-if="passwordError" class="text-sm text-red-400 sm:col-span-3">{{ passwordError }}</p>
            </form>
          </div>
        </div>
      </SectionCard>

      <p v-if="actionSuccess" class="text-center text-sm text-emerald-400">{{ actionSuccess }}</p>
      <p v-if="actionError" class="text-center text-sm text-red-400">{{ actionError }}</p>
    </template>

    <template #footer>
      <AdminButton variant="secondary" label="إغلاق" class="w-full" @click="handleClose" />
    </template>
  </ModalShell>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { ModalShell, AdminButton, SectionCard, StatusPill } from '../ui';
import { useUserStore } from '@/store/modules/user';
import { getUsers, createUser, updateUserRole, updateUserPassword, deleteUser } from '@/api/userManagement';

const props = defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const userStore = useUserStore();

const users = ref([]);
const loading = ref(false);
const loadError = ref('');

const creating = ref(false);
const createError = ref('');
const createForm = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'viewer',
});

const roleSaving = ref(null);
const deleting = ref(null);

const passwordEditId = ref(null);
const passwordDraft = reactive({ password: '', password_confirmation: '' });
const passwordSaving = ref(false);
const passwordError = ref('');

const actionSuccess = ref('');
const actionError = ref('');

const roleLabels = { viewer: 'مشاهد', admin: 'مشرف', super_admin: 'مشرف عام' };
const roleVariants = { viewer: 'neutral', admin: 'info', super_admin: 'purple' };

function roleLabel(role) {
  return roleLabels[role] || role;
}

function roleVariant(role) {
  return roleVariants[role] || 'neutral';
}

function firstValidationError(err) {
  const errors = err?.response?.data?.errors;
  if (errors) {
    const firstKey = Object.keys(errors)[0];
    if (firstKey) return errors[firstKey][0];
  }
  return err?.response?.data?.message || '';
}

async function fetchUsersList() {
  loading.value = true;
  loadError.value = '';
  try {
    const { data } = await getUsers();
    users.value = data?.data || [];
  } catch {
    loadError.value = 'تعذر تحميل قائمة المستخدمين';
  } finally {
    loading.value = false;
  }
}

watch(
  () => props.open,
  (isOpen) => {
    actionSuccess.value = '';
    actionError.value = '';
    if (isOpen) {
      fetchUsersList();
    } else {
      passwordEditId.value = null;
    }
  },
);

watch(
  () => passwordEditId.value,
  () => {
    passwordError.value = '';
    passwordDraft.password = '';
    passwordDraft.password_confirmation = '';
  },
);

function resetCreateForm() {
  createForm.name = '';
  createForm.email = '';
  createForm.password = '';
  createForm.password_confirmation = '';
  createForm.role = 'viewer';
}

async function submitCreateUser() {
  createError.value = '';
  creating.value = true;
  try {
    const { data } = await createUser({ ...createForm });
    if (data?.data) {
      users.value = [data.data, ...users.value];
    } else {
      await fetchUsersList();
    }
    resetCreateForm();
    actionSuccess.value = 'تم إنشاء المستخدم بنجاح';
  } catch (err) {
    createError.value = firstValidationError(err) || 'تعذر إنشاء المستخدم';
  } finally {
    creating.value = false;
  }
}

async function onRoleChange(user, newRole) {
  if (newRole === user.role) return;
  if (!confirm(`تغيير دور ${user.name} إلى "${roleLabel(newRole)}"؟`)) return;

  roleSaving.value = user.id;
  actionError.value = '';
  try {
    await updateUserRole(user.id, newRole);
    user.role = newRole;
    actionSuccess.value = 'تم تحديث الدور بنجاح';
  } catch (err) {
    actionError.value = firstValidationError(err) || 'تعذر تحديث الدور';
  } finally {
    roleSaving.value = null;
  }
}

function togglePasswordEditor(userId) {
  passwordEditId.value = passwordEditId.value === userId ? null : userId;
}

async function submitPasswordChange(user) {
  passwordError.value = '';
  passwordSaving.value = true;
  try {
    await updateUserPassword(user.id, { ...passwordDraft });
    actionSuccess.value = `تم تحديث كلمة مرور ${user.name}`;
    passwordEditId.value = null;
  } catch (err) {
    passwordError.value = firstValidationError(err) || 'تعذر تحديث كلمة المرور';
  } finally {
    passwordSaving.value = false;
  }
}

async function onDelete(user) {
  if (!confirm(`هل أنت متأكد من حذف ${user.name}؟ لا يمكن التراجع عن هذا الإجراء.`)) return;

  deleting.value = user.id;
  actionError.value = '';
  try {
    await deleteUser(user.id);
    users.value = users.value.filter((existing) => existing.id !== user.id);
    actionSuccess.value = 'تم حذف المستخدم بنجاح';
  } catch (err) {
    actionError.value = firstValidationError(err) || 'تعذر حذف المستخدم';
  } finally {
    deleting.value = null;
  }
}

function handleClose() {
  emit('close');
}
</script>

<style scoped>
@reference "../../../../css/app.css";

.admin-field {
  @apply w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none transition-colors placeholder:text-gray-500 focus:border-blue-400/50 focus:ring-2 focus:ring-blue-500/30;
}

.admin-field--sm {
  @apply w-auto px-2.5 py-1.5 text-xs;
}

.admin-field:disabled {
  @apply cursor-not-allowed opacity-50;
}
</style>
