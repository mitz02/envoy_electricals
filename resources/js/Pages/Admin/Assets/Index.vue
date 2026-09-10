<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    assets: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('assets.manage');

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    category: props.filters.category ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.status) p.set('status', form.status);
    if (form.category) p.set('category', form.category);
    window.location.search = p.toString();
}

const statusBadge = {
    active: 'bg-emerald-100 text-emerald-800',
    inactive: 'bg-slate-100 text-slate-500',
    disposed: 'bg-red-100 text-red-700',
};

const conditionBadge = {
    excellent: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    good: 'bg-blue-50 text-blue-700 border-blue-200',
    fair: 'bg-amber-50 text-amber-700 border-amber-200',
    poor: 'bg-red-50 text-red-700 border-red-200',
};
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Assets Register"
        subtitle="Track company equipment, vehicles and fixed assets."
        :action-href="canManage ? '/admin/assets/create' : ''"
        action-label="Add Asset"
    />

    <!-- Summary Cards -->
    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Assets</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.total }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ summary.active }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Disposed</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ summary.disposed }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Value</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.value) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input v-model="form.search" type="search" placeholder="Search name, serial number, location, refâ€¦" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All statuses</option>
            <option v-for="s in statuses" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
        </select>
        <select v-model="form.category" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All categories</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <!-- Assets Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Asset</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Location</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Purchase Cost</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Current Value</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Maintenance</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="a in assets.data" :key="a.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="`/admin/assets/${a.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ a.name }}</Link>
                            <p class="text-xs text-slate-400">{{ a.ref_id }}<span v-if="a.serial_number"> Â· {{ a.serial_number }}</span></p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ a.category || 'â€”' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ a.location || 'â€”' }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ naira(a.purchase_cost) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(a.current_value) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="badgeClass(statusBadge[a.status] || 'bg-slate-100 text-slate-500')">{{ a.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-500">{{ a.maintenances_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Link :href="`/admin/assets/${a.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                <Link v-if="canManage" :href="`/admin/assets/${a.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!assets.data.length">
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">No assets found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="assets" />
        </div>
    </div>
</template>
