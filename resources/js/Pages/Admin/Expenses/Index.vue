<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    expenses: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    month_total: { type: Number, default: 0 },
});

const { has } = useCan();

const form = useForm({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function applyFilters() {
    const params = {};
    Object.entries(form.data()).forEach(([k, v]) => {
        if (v) {
            params[k] = v;
        }
    });
    window.location.search = new URLSearchParams(params).toString();
}
</script>

<template>
        <FlashMessages />
        <PageHeader
            title="Expenses"
            subtitle="Record and track business expenses."
            :action-href="has('expenses.create') ? '/admin/expenses/create' : ''"
            action-label="Add Expense"
        />

        <div class="mb-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Expenses this month</p>
            <p class="mt-1 text-2xl font-bold text-red-700">{{ naira(month_total) }}</p>
        </div>

        <div class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-5">
            <input v-model="form.search" type="search" placeholder="Search description, ref, paid toâ€¦" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            <select v-model="form.category_id" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All Categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <input v-model="form.from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters" />
            <input v-model="form.to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters" />
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Expense</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Ref</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="e in expenses.data" :key="e.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ e.description }}</p>
                                <p class="text-xs text-slate-400">{{ e.paid_to || '' }} {{ e.payment_method ? `Â· ${e.payment_method}` : '' }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ e.ref_id }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(e.expense_date) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ e.category?.name }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-red-700">-{{ naira(e.amount) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <Link v-if="has('expenses.edit')" :href="`/admin/expenses/${e.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!expenses.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No expenses found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="expenses" />
            </div>
        </div>
</template>