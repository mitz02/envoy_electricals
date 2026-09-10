<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    customer: { type: Object, default: null },
});

const isEdit = computed(() => !!props.customer);

const form = useForm({
    name: props.customer?.name ?? '',
    phone: props.customer?.phone ?? '',
    email: props.customer?.email ?? '',
    address: props.customer?.address ?? '',
    location: props.customer?.location ?? '',
    customer_type: props.customer?.customer_type ?? 'walk_in',
    notes: props.customer?.notes ?? '',
});

function submit() {
    if (isEdit.value) {
        form.put(`/admin/customers/${props.customer.id}`);
    } else {
        form.post('/admin/customers');
    }
}
</script>

<template>
        <FlashMessages />
        <PageHeader :title="isEdit ? `Edit ${customer.name}` : 'Add Customer'" />

        <form class="max-w-2xl space-y-5" @submit.prevent="submit">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Customer Information</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input v-model="form.phone" type="tel" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input v-model="form.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Customer type</label>
                        <select v-model="form.customer_type" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="walk_in">Walk-in</option>
                            <option value="regular">Regular</option>
                            <option value="corporate">Corporate</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Location</label>
                        <input v-model="form.location" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                        <textarea v-model="form.address" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50" :disabled="form.processing">
                    {{ isEdit ? 'Save Changes' : 'Create Customer' }}
                </button>
            </div>
        </form>
</template>