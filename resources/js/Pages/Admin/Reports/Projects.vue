<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, maskNaira, formatDate, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    projects: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    by_status: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const exportLink = () => {
    const p = new URLSearchParams();
    if (props.filters.from) p.set('from', props.filters.from);
    if (props.filters.to) p.set('to', props.filters.to);
    return `/admin/reports/export?type=projects&${p.toString()}`;
};

const statusColors = {
    draft: 'bg-slate-100 text-slate-700',
    quotation: 'bg-sky-100 text-sky-700',
    approved: 'bg-indigo-100 text-indigo-700',
    in_progress: 'bg-amber-100 text-amber-800',
    installation: 'bg-amber-100 text-amber-700',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-700',
};
</script>

<template>
        <FlashMessages />

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Projects Report</h1>
                <p class="mt-1 text-sm text-slate-500">Contract value, project cost, gross profit and outstanding balances.</p>
            </div>
            <a :href="exportLink()" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Export CSV</a>
        </div>

        <form class="mb-6 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-4" method="get">
            <input :default-value="props.filters.from" name="from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <input :default-value="props.filters.to" name="to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <select :default-value="props.filters.status ?? ''" name="status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s.replace('_', ' ') }}</option>
            </select>
            <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90">Apply Filters</button>
        </form>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active / Completed</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.active }} <span class="text-sm font-normal text-slate-400">/ {{ summary.completed }}</span></p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contract Value</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.contract_value) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Received / Outstanding</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ naira(summary.received) }}</p>
                <p class="text-xs font-medium text-amber-700">{{ naira(summary.outstanding) }} outstanding</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Cost / Gross Profit</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ maskNaira('project_cost', summary.project_cost) }}</p>
                <p class="text-xs font-semibold text-emerald-700">{{ maskNaira('gross_profit', summary.gross_profit) }} profit</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-1">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">By Status</h2>
                <div v-if="by_status.length" class="space-y-3">
                    <div v-for="s in by_status" :key="s.status" class="flex items-center justify-between border-b border-slate-100 pb-2 last:border-0">
                        <span :class="badgeClass(statusColors[s.status])">{{ s.status.replace('_', ' ') }}</span>
                        <span class="text-sm font-semibold text-slate-900">{{ s.count }} · {{ maskNaira('gross_profit', s.profit) }}</span>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-slate-400">No project data.</p>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Project</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Contract</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Received</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Cost</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Profit</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in projects.data" :key="p.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <Link :href="`/admin/projects/${p.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ p.name }}</Link>
                                    <p class="text-xs text-slate-400">{{ p.ref_id }} · {{ p.customer?.name || 'No customer' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="badgeClass(statusColors[p.status])">{{ p.status.replace('_', ' ') }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-900">{{ naira(p.contract_value) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700">{{ naira(p.amount_received) }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ maskNaira('project_cost', p.project_cost) }}</td>
                                <td class="px-4 py-3 text-right font-semibold" :class="p.gross_profit >= 0 ? 'text-emerald-700' : 'text-red-700'">{{ maskNaira('gross_profit', p.gross_profit) }}</td>
                                <td class="px-4 py-3 text-right" :class="p.balance > 0 ? 'font-medium text-amber-700' : 'text-slate-500'">{{ naira(p.balance) }}</td>
                            </tr>
                            <tr v-if="!projects.data.length">
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">No projects found for the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :meta="projects" />
                </div>
            </div>
        </div>
</template>