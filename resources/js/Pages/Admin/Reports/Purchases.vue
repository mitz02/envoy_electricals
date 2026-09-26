<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    purchases: { type: Object, required: true },
    totals: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const exportLink = computed(() => {
    const p = new URLSearchParams(props.filters);
    return `/admin/reports/export?type=purchases&${p.toString()}`;
});
</script>

<template>
        <FlashMessages />

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Purchase Report</h1>
                <p class="mt-1 text-sm text-slate-500">Stock bought from suppliers over the selected period.</p>
            </div>
            <a :href="exportLink" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Export CSV</a>
        </div>

        <form class="mb-6 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-3">
            <input :default-value="props.filters.from" name="from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <input :default-value="props.filters.to" name="to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90">Apply Range</button>
        </form>

        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Purchases</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(totals.total) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Paid</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ naira(totals.paid) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Aging Payable</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ naira(totals.payable) }}</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Ref</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Supplier</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Total</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Paid</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in purchases.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3"><Link :href="`/admin/purchases/${p.id}`" class="font-mono text-xs font-medium text-slate-700 hover:text-slate-900">{{ p.ref_id }}</Link></td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(p.purchase_date) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ p.supplier?.name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(p.total) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">{{ naira(p.amount_paid) }}</td>
                            <td class="px-4 py-3 text-right text-amber-700">{{ naira(p.balance) }}</td>
                        </tr>
                        <tr v-if="!purchases.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No purchases in range.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="purchases" />
            </div>
        </div>
</template>