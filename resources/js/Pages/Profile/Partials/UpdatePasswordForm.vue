<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

/**
 * Mirrors the server rules in PasswordController: Password::defaults() (8
 * characters) plus `confirmed`. Anything weaker is rejected on save.
 */
const requirements = [
    { label: 'At least 8 characters', met: computed(() => form.password.length >= 8) },
    { label: 'Matches the confirmation', met: computed(() => form.password.length > 0 && form.password === form.password_confirmation) },
];

const metCount = computed(() => requirements.filter((r) => r.met.value).length);

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#FACC15]/20 text-[#0D1527]">
                    <i class="bi bi-shield-lock"></i>
                </span>
                <div>
                    <h2 class="text-sm font-black text-[#0D1527]">Password &amp; security</h2>
                    <p class="text-xs text-slate-400">Choose something long and random that you do not use elsewhere.</p>
                </div>
            </div>
            <span
                class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                :class="metCount === requirements.length ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
            >
                {{ metCount }}/{{ requirements.length }} checks
            </span>
        </div>

        <form class="p-5" @submit.prevent="updatePassword">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="current_password" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Current password</label>
                    <input
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-[#FACC15] focus:ring-[#FACC15]/40"
                    />
                    <p v-if="form.errors.current_password" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <i class="bi bi-exclamation-circle mt-px shrink-0"></i>
                        {{ form.errors.current_password }}
                    </p>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">New password</label>
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-[#FACC15] focus:ring-[#FACC15]/40"
                    />
                    <p v-if="form.errors.password" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <i class="bi bi-exclamation-circle mt-px shrink-0"></i>
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Confirm new password</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-[#FACC15] focus:ring-[#FACC15]/40"
                    />
                    <p v-if="form.errors.password_confirmation" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <i class="bi bi-exclamation-circle mt-px shrink-0"></i>
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>
            </div>

            <ul class="mt-4 space-y-1.5">
                <li
                    v-for="requirement in requirements"
                    :key="requirement.label"
                    class="flex items-center gap-2 text-xs"
                    :class="requirement.met.value ? 'text-emerald-700' : 'text-slate-400'"
                >
                    <i
                        class="bi text-xs"
                        :class="requirement.met.value ? 'bi-check-circle-fill' : 'bi-circle'"
                    ></i>
                    {{ requirement.label }}
                </li>
            </ul>

            <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0D1527]/90 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <i v-if="form.processing" class="bi bi-arrow-clockwise animate-spin"></i>
                    <i v-else class="bi bi-key"></i>
                    {{ form.processing ? 'Updating…' : 'Update password' }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700"
                    >
                        <i class="bi bi-check-circle"></i>
                        Password changed.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
