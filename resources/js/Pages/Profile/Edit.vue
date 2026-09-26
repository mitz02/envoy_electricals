<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    isActive: { type: Boolean, default: true },
    createdAt: { type: String, default: null },
});

const page = usePage();

const user = computed(() => page.props.auth.user);
const stores = computed(() => page.props.stores ?? []);

/** Resolves the branch allow-list from shared props so the summary never leaks an id. */
const branchNames = computed(() => {
    if (!user.value?.is_store_restricted) {
        return [];
    }

    return (user.value.store_access ?? [])
        .map((id) => stores.value.find((store) => store.id === id)?.name)
        .filter(Boolean);
});

const branchSummary = computed(() => {
    if (!user.value?.is_store_restricted) {
        return 'All branches';
    }

    const names = branchNames.value;

    if (names.length === 0) {
        return 'No branches granted';
    }

    if (names.length <= 2) {
        return names.join(' · ');
    }

    return `${names.slice(0, 2).join(' · ')} +${names.length - 2} more`;
});

const initials = computed(() => (user.value?.name || 'A').trim().charAt(0).toUpperCase());

const facts = computed(() => [
    { label: 'Role', value: user.value?.role || 'No role assigned', icon: 'bi-person-badge' },
    { label: 'Branch access', value: branchSummary.value, icon: 'bi-shop' },
    { label: 'Account status', value: props.isActive ? 'Active' : 'Suspended', icon: 'bi-activity' },
    { label: 'Member since', value: props.createdAt || 'Unknown', icon: 'bi-calendar3' },
]);
</script>

<template>
    <Head title="My Profile" />

    <FlashMessages />

    <!-- Identity hero -->
    <div class="relative z-20 mb-6 overflow-visible rounded-3xl bg-gradient-to-r from-[#0D1527] via-[#12203C] to-[#1A365D] p-6 shadow-xl sm:p-7">
        <!-- Glows are clipped in their own layer so nothing else is cut off. -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl" aria-hidden="true">
            <div class="absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[#40e0d0]/15 blur-3xl"></div>
            <div class="absolute -bottom-24 right-32 h-56 w-56 rounded-full bg-[#FACC15]/10 blur-3xl"></div>
        </div>

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#FACC15] to-[#40e0d0] text-xl font-black text-[#0D1527] shadow-lg">
                    {{ initials }}
                </div>
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-black text-white sm:text-3xl">{{ user?.name }}</h1>
                    <p class="mt-0.5 truncate text-sm text-slate-300/90">{{ user?.email }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-[#FACC15] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#0D1527]">
                            {{ user?.role || 'No role' }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider"
                            :class="props.isActive ? 'bg-[#40e0d0]/20 text-[#40e0d0]' : 'bg-red-500/20 text-red-300'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="props.isActive ? 'bg-[#40e0d0]' : 'bg-red-400'"></span>
                            {{ props.isActive ? 'Active' : 'Suspended' }}
                        </span>
                    </div>
                </div>
            </div>

            <p class="max-w-xs text-xs leading-relaxed text-slate-300/80">
                Your name, contact details and password are managed here. Role and branch access are set by a super
                admin from the Roles &amp; Access screen.
            </p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Editable sections -->
        <div class="space-y-6 lg:col-span-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <UpdateProfileInformationForm />
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <UpdatePasswordForm />
            </div>
        </div>

        <!-- Read-only context + danger zone -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="mb-3 flex items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#40e0d0]/10 text-[#0D1527]">
                        <i class="bi bi-info-circle"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-[#0D1527]">Your access</h2>
                        <p class="text-xs text-slate-400">Set by a super admin</p>
                    </div>
                </div>

                <dl class="space-y-2.5">
                    <div
                        v-for="fact in facts"
                        :key="fact.label"
                        class="flex items-center justify-between gap-3 rounded-xl bg-[#FAF8F2] px-3.5 py-2.5"
                    >
                        <dt class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <i class="bi text-slate-400" :class="fact.icon"></i>
                            {{ fact.label }}
                        </dt>
                        <dd class="min-w-0 truncate text-right text-xs font-bold text-[#0D1527]">{{ fact.value }}</dd>
                    </div>
                </dl>

                <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
                    Need different permissions or branches? Ask a super admin to update the
                    <span class="font-semibold text-slate-500">Roles &amp; Access</span> screen.
                </p>
            </div>

            <DeleteUserForm />
        </div>
    </div>
</template>
