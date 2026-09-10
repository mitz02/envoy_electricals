<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    categories: { type: Array, required: true },
    expense: { type: Object, default: null },
});

const isEdit = computed(() => !!props.expense);

const form = useForm({
    expense_date: props.expense?.expense_date ?? new Date().toISOString().split('T')[0],
    expense_category_id: props.expense?.expense_category_id ?? '',
    description: props.expense?.description ?? '',
    amount: props.expense?.amount ?? '',
    payment_method: props.expense?.payment_method ?? 'cash',
    paid_to: props.expense?.paid_to ?? '',
    reference_no: props.expense?.reference_no ?? '',
    remarks: props.expense?.remarks ?? '',
});

function submit() {
    if (isEdit.value) {
        form.put(`/admin/expenses/${props.expense.id}`);
    } else {
        form.post('/admin/expenses');
    }
}
</script>

<template>
        <FlashMessages />
        <PageHeader :title="isEdit ? 'Edit Expense' : 'Add Expense'" />

        <form class="max-w-2xl space-y-5" @submit.prevent="submit">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Date <span class="text-red-500">*</span></label>
                        <input v-model="form.expense_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Category <span class="text-red-500">*</span></label>
                        <select v-model="form.expense_category_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">Select category</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <div v-if="form.errors.expense_category_id" class="mt-1 text-xs text-red-600">{{ form.errors.expense_category_id }}</div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description <span class="text-red-500">*</span></label>
                        <input v-model="form.description" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Amount (₦) <span class="text-red-500">*</span></label>
                        <input v-model.number="form.amount" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
                        <select v-model="form.payment_method" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="cash">Cash</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                            <option value="pos">POS</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Paid to / Vendor</label>
                        <input v-model="form.paid_to" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Receipt / reference no</label>
                        <input v-model="form.reference_no" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Remarks</label>
                        <textarea v-model="form.remarks" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50" :disabled="form.processing">
                    {{ isEdit ? 'Save Changes' : 'Record Expense' }}
                </button>
            </div>
        </form>
</template>