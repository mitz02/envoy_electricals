<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    customers: { type: Object, required: true },
    summary: { type: Object, default: () => ({ total_outstanding: 0, owing_customers: 0 }) },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search ?? '',
    owing: props.filters.owing === '1',
});

function applyFilters() {
    const params = {};
    if (form.search) params.search = form.search;
    if (form.owing) params.owing = '1';
    window.location.search = new URLSearchParams(params).toString();
}

function toggleOwing() {
    form.owing = !form.owing;
    applyFilters();
}

const typeBadge = {
    walk_in: 'bg-slate-100 text-slate-700',
    regular: 'bg-emerald-100 text-emerald-800',
    corporate: 'bg-amber-100 text-amber-800',
};
</script>

<template>
    <FlashMessages />
    <PageHeader title="Customers" subtitle="Customer records and their purchasing history." action-href="/admin/customers/create" action-label="Add Customer" />

    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Customers</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ customers.total }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Outstanding</p>
            <p class="mt-2 text-2xl font-bold text-amber-700">{{ naira(summary.total_outstanding) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Customers Owing</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ summary.owing_customers }}</p>
        </div>
    </div>

    <div class="mb-4 flex gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <input v-model="form.search" type="search" placeholder="Search name, phone, email, ref…" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <button
            type="button"
            class="shrink-0 rounded-lg border px-4 py-2 text-sm font-semibold transition disabled:opacity-50"
            :class="form.owing ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'"
            @click="toggleOwing"
        >
            <i class="bi bi-exclamation-triangle mr-1.5"></i>
            Owing only
        </button>
        <button class="shrink-0 rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Search</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Ref</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Phone</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Lifetime Purchases</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Outstanding</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Type</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Branch</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="c in customers.data" :key="c.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="`/admin/customers/${c.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ c.name }}</Link>
                            <p v-if="c.email" class="text-xs text-slate-400">{{ c.email }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ c.ref_id }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ c.phone || '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(c.lifetime_purchases) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span v-if="Number(c.outstanding) > 0" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                <i class="bi bi-exclamation-triangle text-[10px]"></i>
                                {{ naira(c.outstanding) }}
                            </span>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="typeBadge[c.customer_type] || typeBadge.walk_in">{{ c.customer_type }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="c.store" class="inline-flex items-center gap-1.5 rounded-full bg-[#40e0d0]/10 px-2.5 py-0.5 text-xs font-semibold text-[#0D1527]">
                                <i class="bi bi-shop text-[10px]"></i>
                                {{ c.store.name }}
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                                <i class="bi bi-globe2 text-[10px]"></i>
                                Unassigned
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/admin/customers/${c.id}`" class="mr-2 rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                            <Link :href="`/admin/customers/${c.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                        </td>
                    </tr>
                    <tr v-if="!customers.data.length">
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">No customers found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="customers" />
        </div>
    </div>
</template>