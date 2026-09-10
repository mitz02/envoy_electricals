<script setup>
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    expenses_by_category: { type: Array, default: () => [] },
    product_profit: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const exportLink = computed(() => '/admin/reports/export?type=expenses');
const marginPct = computed(() => (props.summary.gross_margin ?? 0).toFixed(1) + '%');

function marginColor(value) {
    if (value >= 30) {
        return 'text-emerald-700';
    }
    if (value >= 15) {
        return 'text-amber-700';
    }
    return 'text-red-700';
}
</script>

<template>
        <FlashMessages />

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Profit &amp; Loss</h1>
                <p class="mt-1 text-sm text-slate-500">Revenue, cost of goods, expenses and net profit for the period.</p>
            </div>
            <a :href="exportLink" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Export Expenses CSV</a>
        </div>

        <form class="mb-6 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-3">
            <input :default-value="props.filters.from" name="from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <input :default-value="props.filters.to" name="to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90">Apply Range</button>
        </form>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Revenue</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">{{ naira(summary.revenue) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">COGS</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.cogs) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Expenses</p>
                <p class="mt-2 text-2xl font-bold text-red-700">{{ naira(summary.expenses) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Purchases outlay</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.purchases) }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-sm font-semibold text-emerald-900">Gross Profit</p>
                <p class="mt-2 text-3xl font-extrabold text-emerald-900">{{ naira(summary.gross_profit) }}</p>
                <p class="mt-1 text-sm font-semibold" :class="marginColor(summary.gross_margin)">Margin {{ marginPct }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <p class="text-sm font-semibold text-slate-900">Net Profit</p>
                <p class="mt-2 text-3xl font-extrabold" :class="summary.net_profit >= 0 ? 'text-emerald-700' : 'text-red-700'">{{ naira(summary.net_profit) }}</p>
                <p class="mt-1 text-sm text-slate-500">After expenses</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Expenses by Category</h2>
                <div v-if="expenses_by_category.length" class="space-y-3">
                    <div v-for="e in expenses_by_category" :key="e.expense_category_id" class="flex items-center justify-between border-b border-slate-100 pb-2 last:border-0">
                        <p class="text-sm font-medium text-slate-700">{{ e.category?.name ?? 'Uncategorized' }}</p>
                        <p class="text-sm font-semibold text-red-700">{{ naira(e.total) }}</p>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-slate-400">No expenses recorded in range.</p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Top Products by Profit</h2>
                <div v-if="product_profit.length" class="space-y-3">
                    <div v-for="p in product_profit" :key="p.product_id" class="flex items-center justify-between border-b border-slate-100 pb-2 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ p.product?.name ?? 'Unknown' }}</p>
                            <p class="text-xs text-slate-400">{{ p.qty }} sold</p>
                        </div>
                        <p class="text-sm font-semibold text-emerald-700">{{ naira(p.profit) }}</p>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-slate-400">No sales data.</p>
            </div>
        </div>
</template>