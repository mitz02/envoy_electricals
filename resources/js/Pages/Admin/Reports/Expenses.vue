<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    expenses: { type: Object, required: true },
    total: { type: Number, default: 0 },
    filters: { type: Object, default: () => ({}) },
});

const exportLink = computed(() => {
    const p = new URLSearchParams(props.filters);
    return `/admin/reports/export?type=expenses&${p.toString()}`;
});
</script>

<template>
        <FlashMessages />

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Expense Report</h1>
                <p class="mt-1 text-sm text-slate-500">All recorded expenses in the selected period.</p>
            </div>
            <a :href="exportLink" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Export CSV</a>
        </div>

        <form class="mb-6 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-3">
            <input :default-value="props.filters.from" name="from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <input :default-value="props.filters.to" name="to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90">Apply Range</button>
        </form>

        <div class="rounded-xl border border-red-200 bg-red-50 p-4 mb-6">
            <p class="text-xs font-medium uppercase tracking-wide text-red-700">Total Expenses</p>
            <p class="mt-1 text-2xl font-bold text-red-800">{{ naira(total) }}</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Description</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="e in expenses.data" :key="e.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(e.expense_date) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ e.category?.name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ e.description }}</p>
                                <p v-if="e.paid_to" class="text-xs text-slate-400">{{ e.paid_to }}</p>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-red-700">{{ naira(e.amount) }}</td>
                        </tr>
                        <tr v-if="!expenses.data.length">
                            <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-400">No expenses in range.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="expenses" />
            </div>
        </div>
</template>