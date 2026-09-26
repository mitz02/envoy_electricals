<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    customer: { type: Object, default: null },
    stores: { type: Array, default: () => [] },
    currentStoreId: { type: [Number, String, null], default: null },
});

const isEdit = computed(() => !!props.customer);

const typeOptions = [
    { value: 'walk_in', label: 'Walk-in', hint: 'One-off retail buyer at the counter.' },
    { value: 'regular', label: 'Regular', hint: 'Repeat customer, may buy on invoice.' },
    { value: 'corporate', label: 'Corporate', hint: 'Company / business account.' },
];

const form = useForm({
    name: props.customer?.name ?? '',
    phone: props.customer?.phone ?? '',
    email: props.customer?.email ?? '',
    customer_type: props.customer?.customer_type ?? 'walk_in',
    location: props.customer?.location ?? '',
    address: props.customer?.address ?? '',
    notes: props.customer?.notes ?? '',
    store_id: props.customer?.store_id ?? props.currentStoreId ?? '',
});

const typeHint = computed(() => typeOptions.find((t) => t.value === form.customer_type)?.hint ?? '');

function submit() {
    if (isEdit.value) {
        form.put(`/admin/customers/${props.customer.id}`);
    } else {
        form.post('/admin/customers');
    }
}

function fieldClass(hasError) {
    return hasError
        ? 'w-full rounded-xl border border-red-300 bg-white text-sm focus:border-red-400 focus:ring-red-400/20'
        : 'w-full rounded-xl border border-slate-300 bg-white text-sm focus:border-[#40e0d0] focus:ring-[#40e0d0]/20';
}

const labelClass = 'mb-1.5 block text-sm font-semibold text-[#0D1527]';
</script>

<template>
    <FlashMessages />
    <PageHeader
        :title="isEdit ? `Edit ${customer.name}` : 'Add Customer'"
        subtitle="Save customer details, their type and branch assignment."
    />

    <form class="max-w-4xl space-y-5" @submit.prevent="submit">
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#40e0d0]/15 text-lg text-[#0D1527]">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-[#0D1527]">Customer Information</h2>
                    <p class="text-xs text-slate-500">Who this customer is and how to reach them.</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#0D1527]">Full name <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" placeholder="e.g. Aisha Bello" :class="fieldClass(!!form.errors.name)" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#0D1527]">Phone</label>
                    <input v-model="form.phone" type="tel" placeholder="e.g. 0801 234 5678" :class="fieldClass(!!form.errors.phone)" />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-[#0D1527]">Email</label>
                    <input v-model="form.email" type="email" placeholder="name@example.com" :class="fieldClass(!!form.errors.email)" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label :class="labelClass">Customer type <span class="text-red-500">*</span></label>
                    <select v-model="form.customer_type" :class="fieldClass(!!form.errors.customer_type)">
                        <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-400">{{ typeHint }}</p>
                    <p v-if="form.errors.customer_type" class="mt-1 text-xs text-red-600">{{ form.errors.customer_type }}</p>
                </div>

                <div>
                    <label :class="labelClass">Location</label>
                    <input v-model="form.location" type="text" placeholder="e.g. Ikeja, Lagos" :class="fieldClass(!!form.errors.location)" />
                    <p v-if="form.errors.location" class="mt-1 text-xs text-red-600">{{ form.errors.location }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label :class="labelClass">Address</label>
                    <textarea v-model="form.address" rows="2" placeholder="Delivery / billing address" :class="fieldClass(!!form.errors.address)" />
                    <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">{{ form.errors.address }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label :class="labelClass">Notes</label>
                    <textarea v-model="form.notes" rows="3" placeholder="Anything worth remembering about this customer…" :class="fieldClass(!!form.errors.notes)" />
                    <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#FACC15]/20 text-lg text-[#0D1527]">
                    <i class="bi bi-shop"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-[#0D1527]">Branch assignment</h2>
                    <p class="text-xs text-slate-500">Which branch this customer belongs to.</p>
                </div>
            </div>

            <div class="max-w-xl">
                <label :class="labelClass">Store</label>
                <select v-model="form.store_id" :class="fieldClass(!!form.errors.store_id)">
                    <option value="">No branch yet — shows under All Stores</option>
                    <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }} ({{ s.code }})</option>
                </select>
                <p v-if="form.errors.store_id" class="mt-1 text-xs text-red-600">{{ form.errors.store_id }}</p>
                <p class="mt-2 flex items-start gap-1.5 rounded-xl bg-[#FAF8F2] px-3 py-2 text-xs leading-relaxed text-slate-500">
                    <i class="bi bi-info-circle mt-0.5 shrink-0 text-[#40e0d0]"></i>
                    Buyers who register online start unassigned and only appear once you pick a branch. Leave this untouched to keep them under "All Stores".
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <Link href="/admin/customers" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                Cancel
            </Link>
            <button
                type="submit"
                class="rounded-xl bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                :disabled="form.processing"
            >
                {{ isEdit ? 'Save Changes' : 'Create Customer' }}
            </button>
        </div>
    </form>
</template>