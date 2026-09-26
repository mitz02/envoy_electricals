<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    buyer: { type: Object, required: true },
});

const form = useForm({
    name: props.buyer.name ?? '',
    phone: props.buyer.phone ?? '',
    email: props.buyer.email ?? '',
    is_active: props.buyer.is_active ?? true,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(route('admin.buyers.update', props.buyer.id), {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <div class="w-full space-y-5">
        <FlashMessages />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.buyers.show', buyer.id)" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#40e0d0] hover:text-[#0D1527]">
                    <i class="bi bi-arrow-left"></i>
                </Link>
                <PageHeader :title="'Edit '.concat(buyer.name)" subtitle="Update the buyer's account details and login access." />
            </div>
        </div>

        <form class="max-w-3xl space-y-5" @submit.prevent="submit">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="text-sm font-semibold text-slate-900">Account information</h2>
                <p class="mt-1 text-xs text-slate-500">Changes are mirrored to the buyer's linked customer record.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#40e0d0] focus:ring-[#40e0d0]/20" />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email <span class="text-red-500">*</span></label>
                        <input v-model="form.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#40e0d0] focus:ring-[#40e0d0]/20" />
                        <div v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input v-model="form.phone" type="tel" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#40e0d0] focus:ring-[#40e0d0]/20" />
                    </div>
                    <div class="flex items-end pb-1.5">
                        <label class="flex w-full cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Account active</span>
                                <span class="block text-xs text-slate-500">Inactive buyers can't sign in.</span>
                            </span>
                            <input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-[#40e0d0] focus:ring-[#40e0d0]/30" />
                        </label>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="text-sm font-semibold text-slate-900">Reset password</h2>
                <p class="mt-1 text-xs text-slate-500">Leave blank to keep the current password.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">New password</label>
                        <input v-model="form.password" type="password" autocomplete="new-password" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#40e0d0] focus:ring-[#40e0d0]/20" />
                        <div v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Confirm new password</label>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#40e0d0] focus:ring-[#40e0d0]/20" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <Link :href="route('admin.buyers.show', buyer.id)" class="text-sm font-semibold text-slate-500 transition hover:text-slate-800">Cancel</Link>
                <button type="submit" class="rounded-xl bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50" :disabled="form.processing">
                    <span v-if="form.processing"><i class="bi bi-arrow-repeat mr-1.5 animate-spin"></i>Saving…</span>
                    <span v-else>Save changes</span>
                </button>
            </div>
        </form>
    </div>
</template>