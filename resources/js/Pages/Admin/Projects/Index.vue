<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    projects: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    customers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    customer_id: props.filters.customer_id ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.status) p.set('status', form.status);
    if (form.customer_id) p.set('customer_id', form.customer_id);
    window.location.search = p.toString();
}

const statusColors = {
    draft: 'bg-slate-100 text-slate-700',
    quotation: 'bg-sky-100 text-sky-700',
    approved: 'bg-indigo-100 text-indigo-700',
    in_progress: 'bg-amber-100 text-amber-800',
    installation: 'bg-amber-100 text-amber-700',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-700',
};

function statusLabel(s) {
    return s ? s.replace('_', ' ') : '-';
}
</script>

<template>
        <FlashMessages />
        <PageHeader title="Projects" subtitle="Solar and electrical installation projects with profitability tracking." action-href="/admin/projects/create" action-label="New Project" />

        <!-- Summary -->
        <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active Projects</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.active_projects }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Project Revenue</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ naira(summary.projects_revenue) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Project Cost</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.projects_cost) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Project Profit</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.projects_profit) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Outstanding</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ naira(summary.outstanding) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
            <input v-model="form.search" type="search" placeholder="Search name, ref, location, customerâ€¦" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
            </select>
            <select v-model="form.customer_id" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                <option value="">All customers</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Project</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Contract</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Received</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Balance</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Profit</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Dates</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in projects.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/projects/${p.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ p.name }}</Link>
                                <p class="text-xs text-slate-400">{{ p.ref_id }} Â· {{ p.customer?.name || 'No customer' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="badgeClass(statusColors[p.status])">{{ statusLabel(p.status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(p.contract_value) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ naira(p.amount_received) }}</td>
                            <td class="px-4 py-3 text-right" :class="p.balance > 0 ? 'font-medium text-amber-700' : 'text-slate-700'">{{ naira(p.balance) }}</td>
                            <td class="px-4 py-3 text-right font-semibold" :class="p.gross_profit >= 0 ? 'text-emerald-700' : 'text-red-700'">{{ naira(p.gross_profit) }}</td>
                            <td class="px-4 py-3 text-right text-xs text-slate-500">
                                {{ p.start_date ? formatDate(p.start_date) : 'â€”' }}
                                <span v-if="p.expected_completion_date">â†’ {{ formatDate(p.expected_completion_date) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="`/admin/projects/${p.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                <Link :href="`/admin/projects/${p.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!projects.data.length">
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">No projects found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="projects" />
            </div>
        </div>
</template>