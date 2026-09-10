<script setup>
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    rows: { type: Array, default: () => [] },
    by_staff: { type: Array, default: () => [] },
    by_method: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    can_view_profit: { type: Boolean, default: false },
});

const groupLink = computed(() => (group) => {
    const p = new URLSearchParams({ ...props.filters, group });
    return `/admin/reports/sales?${p.toString()}`;
});

const exportLink = computed(() => {
    const p = new URLSearchParams(props.filters);
    return `/admin/reports/export?type=sales&${p.toString()}`;
});

let maxTotal = 0;
props.rows.forEach((r) => (maxTotal = Math.max(maxTotal, Number(r.total) || 0)));

const totalLabel = computed(() => (props.rows.length ? `Sales of ${props.rows.length} period(s)` : 'No data for the selected range'));
</script>

<template>
        <FlashMessages />

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Sales Report</h1>
                <p class="mt-1 text-sm text-slate-500">Revenue, number of invoices and payment split over time.</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex overflow-hidden rounded-lg border border-slate-300 text-sm">
                    <a v-for="g in ['day', 'week', 'month']" :key="g" :href="groupLink(g)"
                       class="px-3 py-1.5 font-medium"
                       :class="props.filters.group === g ? 'bg-[#0D1527] text-white' : 'text-slate-600 hover:bg-slate-100'">
                        {{ { day: 'Day', week: 'Week', month: 'Month' }[g] }}
                    </a>
                </div>
                <a :href="exportLink" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Export CSV</a>
            </div>
        </div>

        <form class="mb-6 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-3">
            <input :default-value="props.filters.from" name="from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <input :default-value="props.filters.to" name="to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90">Apply Range</button>
        </form>

        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Revenue</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(totals.revenue) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Collected</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ naira(totals.collected) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Invoices</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ totals.count }}</p>
            </div>
        </div>

        <div v-if="props.rows.length" class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <h2 class="mb-4 text-sm font-semibold text-slate-900">{{ totalLabel }}</h2>
            <div class="flex h-56 items-end gap-px">
                <div v-for="r in props.rows" :key="r.period" class="group relative flex-1">
                    <div class="mx-0.5 rounded-t bg-[#0D1527] transition-colors group-hover:bg-emerald-600"
                         :style="{ height: Math.max(3, (Number(r.total) / maxTotal) * 180) + 'px' }" />
                    <div v-if="r.count" class="pointer-events-none absolute left-1/2 top-0 z-10 hidden -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-[#0D1527] px-2 py-1 text-xs text-white group-hover:block">
                        {{ r.period }} · {{ naira(r.total) }} · {{ r.count }} sale(s)
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">By Salesperson</h2>
                <div v-if="by_staff.length" class="space-y-3">
                    <div v-for="s in by_staff" :key="s.salesperson_id" class="flex items-center justify-between border-b border-slate-100 pb-2 last:border-0">
                        <p class="text-sm font-medium text-slate-700">{{ s.salesperson?.name ?? 'Unassigned' }}</p>
                        <p class="text-sm font-semibold text-slate-900">{{ naira(s.total) }}</p>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-slate-400">No sales data.</p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">By Payment Method</h2>
                <div v-if="by_method.length" class="space-y-3">
                    <div v-for="m in by_method" :key="m.method" class="flex items-center justify-between border-b border-slate-100 pb-2 last:border-0">
                        <p class="text-sm font-medium capitalize text-slate-700">{{ m.method }}</p>
                        <p class="text-sm font-semibold text-slate-900">{{ naira(m.total) }}</p>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-slate-400">No payment data.</p>
            </div>
        </div>
</template>