<template>
  <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6">
    <h5 class="text-lg font-bold text-foreground mb-4">الشروط والأحكام</h5>

    <label for="accept-terms" class="flex items-start gap-3 cursor-pointer">
      <CheckboxRoot
        id="accept-terms"
        :checked="accepted"
        name="accept-terms"
        class="flex h-5 w-5 shrink-0 appearance-none items-center justify-center rounded-md border-2 mt-0.5 transition-colors cursor-pointer"
        :class="accepted ? 'bg-primary border-primary' : 'bg-white border-slate-300 hover:border-slate-400'"
        @update:checked="$emit('update:accepted', $event)"
      >
        <CheckboxIndicator>
          <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
          </svg>
        </CheckboxIndicator>
      </CheckboxRoot>
      <span class="typ-s2 text-foreground">أقر بأن جميع البيانات المدخلة صحيحة و أوافق على
        <router-link to="/terms" target="_blank" class="text-primary hover:underline">الشروط والأحكام</router-link>
        و
        <router-link to="/privacy" target="_blank" class="text-primary hover:underline">سياسة الخصوصية</router-link>
        الخاصة بتأمينكم
      </span>
    </label>

    <p v-if="error" class="text-destructive typ-c1 mt-3">{{ error }}</p>
  </div>
</template>

<script setup>
import { CheckboxRoot, CheckboxIndicator } from 'radix-vue';

defineProps({
  accepted: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

defineEmits(['update:accepted']);
</script>
