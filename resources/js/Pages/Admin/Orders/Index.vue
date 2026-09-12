<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDateTime, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    totals: { type: Object, default: () => ({ total_orders: 0, booking_value: 0, collected: 0 }) },
});

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    delivery: props.filters.delivery ?? '',
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

function statusBadge(status) {
    const map = {
        pending: 'bg-amber-100 text-amber-800',
        paid: 'bg-emerald-100 text-emerald-800',
        processing: 'bg-sky-100 text-sky-800',
        completed: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-slate-200 text-slate-600',
        refunded: 'bg-slate-200 text-slate-600',
    };
    return badgeClass(map[status] ?? 'bg-slate-200 text-slate-600');
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="Online Orders" subtitle="Storefront orders, payments and fulfilment." />

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total orders</p>
            <p class="mt-1 text-2xl font-black text-slate-900">{{ totals.total_orders }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Booking value</p>
            <p class="mt-1 text-2xl font-black text-slate-900">{{ naira(totals.booking_value) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Collected</p>
            <p class="mt-1 text-2xl font-black text-emerald-800">{{ naira(totals.collected) }}</p>
        </div>
    </div>

    <div class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-6">
        <input v-model="form.search" type="search" placeholder="Reference, name, phone…" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20 sm:col-span-3" @keyup.enter="applyFilters" />
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
        </select>
        <select v-model="form.delivery" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
            <option value="">Any Fulfilment</option>
            <option value="pending">Not Delivered</option>
            <option value="delivered">Delivered</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Order</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Items</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Total</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Paid</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="o in orders.data" :key="o.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="route('admin.orders.show', o.id)" class="font-mono text-xs font-medium text-slate-900 hover:text-yellow-600">
                                {{ o.ref_id }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-800">{{ o.customer_name ?? '—' }}</span>
                            <p class="text-xs text-slate-400">{{ o.customer_phone }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ formatDateTime(o.created_at) }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ o.items_count }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(o.total, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ naira(o.paid_sum ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span :class="statusBadge(o.status)">{{ o.status }}</span>
                            <p class="mt-1 text-[11px] text-slate-400">{{ o.fulfillment }}</p>
                        </td>
                    </tr>
                    <tr v-if="!orders.data.length">
                        <td colspan="7" class="px-4 py-10 text-center text-slate-400">No orders found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination v-if="orders.total > orders.per_page" :meta="orders" />
    </div>
</template>