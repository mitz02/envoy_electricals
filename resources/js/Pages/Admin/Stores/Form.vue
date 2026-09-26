<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    store: { type: Object, default: null },
});

const isEditing = ref(!!props.store);

const form = useForm({
    name: props.store?.name ?? '',
    code: props.store?.code ?? '',
    address: props.store?.address ?? '',
    phone: props.store?.phone ?? '',
    email: props.store?.email ?? '',
    is_active: props.store?.is_active ?? true,
    is_default: props.store?.is_default ?? false,
    settings: props.store?.settings ?? {},
});

function submit() {
    if (isEditing.value) {
        form.put(`/admin/stores/${props.store.code}`, {
            preserveScroll: true,
            onSuccess: () => router.visit('/admin/stores'),
        });
    } else {
        form.post('/admin/stores', {
            preserveScroll: true,
            onSuccess: () => router.visit('/admin/stores'),
        });
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <FlashMessages />

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#0D1527]">{{ isEditing ? 'Edit Store' : 'Create Store' }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ isEditing ? 'Update store details' : 'Add a new store location' }}</p>
            </div>
            <Link href="/admin/stores" class="text-sm text-[#40e0d0] hover:underline">← Back to Stores</Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-[#0D1527] mb-4">Basic Information</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Store Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-[#40e0d0] focus:outline-none focus:ring-2 focus:ring-[#40e0d0]/20"
                            placeholder="e.g., Lagos Main Branch"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Store Code *</label>
                        <input
                            v-model="form.code"
                            type="text"
                            :disabled="isEditing"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-[#40e0d0] focus:outline-none focus:ring-2 focus:ring-[#40e0d0]/20"
                            placeholder="e.g., LAG"
                            required
                            maxlength="10"
                        />
                        <p v-if="form.errors.code" class="mt-1 text-sm text-rose-600">{{ form.errors.code }}</p>
                        <p class="mt-1 text-xs text-slate-500">Short code (max 10 chars). Cannot be changed after creation.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                    <textarea
                        v-model="form.address"
                        rows="2"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-[#40e0d0] focus:outline-none focus:ring-2 focus:ring-[#40e0d0]/20"
                        placeholder="Full physical address"
                    ></textarea>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-[#40e0d0] focus:outline-none focus:ring-2 focus:ring-[#40e0d0]/20"
                            placeholder="+234 800 123 4567"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-[#40e0d0] focus:outline-none focus:ring-2 focus:ring-[#40e0d0]/20"
                            placeholder="store@example.com"
                        />
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-[#40e0d0] focus:ring-[#40e0d0]"
                        />
                        <span class="text-sm font-medium text-slate-700">Active</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-[#40e0d0] focus:ring-[#40e0d0]"
                        />
                        <span class="text-sm font-medium text-slate-700">Default Store</span>
                    </label>
                </div>

                <p class="mt-2 text-xs text-slate-500">Only one store can be default. Setting this will unset the current default.</p>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link href="/admin/stores" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-xl bg-[#0D1527] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ isEditing ? 'Update Store' : 'Create Store' }}
                </button>
            </div>
        </form>
    </div>
</template>