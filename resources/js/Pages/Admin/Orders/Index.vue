<script setup>
import { ref } from 'vue';
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

const viewMode = ref('table');

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

function clearFilters() {
    form.search = '';
    form.status = '';
    form.delivery = '';
    window.location.search = '';
}

function statusBadge(status) {
    const map = {
        pending: 'border-amber-200 bg-amber-50 text-amber-800',
        paid: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        processing: 'border-sky-200 bg-sky-50 text-sky-800',
        completed: 'border-teal-200 bg-teal-50 text-teal-800',
        cancelled: 'border-slate-200 bg-slate-100 text-slate-600',
        refunded: 'border-rose-200 bg-rose-50 text-rose-700',
    };
    return badgeClass(map[status] ?? 'border-slate-200 bg-slate-100 text-slate-600');
}

function statusRail(status) {
    const map = {
        pending: 'from-[#FACC15] via-amber-400 to-[#FACC15]',
        paid: 'from-[#40e0d0] via-emerald-400 to-[#40e0d0]',
        processing: 'from-sky-400 via-cyan-400 to-sky-400',
        completed: 'from-emerald-500 via-teal-400 to-emerald-500',
        cancelled: 'from-slate-300 via-slate-400 to-slate-300',
        refunded: 'from-rose-300 via-rose-400 to-rose-300',
    };
    return map[status] ?? map.cancelled;
}

function statusLabel(status) {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
}

function fulfillmentLabel(fulfillment) {
    return fulfillment === 'delivered' ? 'Delivered' : 'Awaiting delivery';
}

function fulfillmentBadge(fulfillment) {
    return fulfillment === 'delivered'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
        : 'border-[#FACC15]/60 bg-[#FACC15]/15 text-amber-800';
}

function orderBalance(order) {
    return Math.max(Number(order.total ?? 0) - Number(order.paid_sum ?? 0), 0);
}

function paymentProgress(order) {
    const total = Number(order.total ?? 0);
    const paid = Number(order.paid_sum ?? 0);
    if (total <= 0) return paid > 0 ? 100 : 0;
    return Math.min(Math.max((paid / total) * 100, 0), 100);
}

function customerInitial(name) {
    return (name || 'Guest').trim().charAt(0).toUpperCase();
}
</script>

<template>
    <div class="w-full space-y-5">
        <FlashMessages />
        <PageHeader title="Online Orders" subtitle="Storefront orders, payments and fulfilment." />

        <div class="grid w-full gap-4 md:grid-cols-3">
            <div class="group relative overflow-hidden rounded-3xl border border-[#40e0d0]/30 bg-gradient-to-br from-white via-[#FAF8F2] to-[#40e0d0]/20 p-5 shadow-[0_12px_34px_rgba(13,21,39,0.07)] transition duration-300 hover:-translate-y-0.5 hover:border-[#40e0d0]/60 hover:shadow-lg">
                <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-[#40e0d0]/20 transition duration-500 group-hover:scale-125"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Total orders</p>
                        <p class="mt-3 text-3xl font-black tracking-tight text-[#0D1527]">{{ totals.total_orders }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl border border-[#40e0d0]/20 bg-white/80 text-teal-700 shadow-sm">
                        <i class="bi bi-box-seam-fill text-lg"></i>
                    </span>
                </div>
                <div class="relative mt-5 flex items-center gap-2 border-t border-[#40e0d0]/20 pt-3 text-[11px] text-slate-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#40e0d0]"></span>
                    Across all storefront orders
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-3xl border border-[#FACC15]/40 bg-[#FACC15] p-5 text-[#0D1527] shadow-[0_12px_30px_rgba(250,204,21,0.16)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_18px_38px_rgba(250,204,21,0.22)]">
                <div class="absolute -bottom-8 -right-5 h-24 w-24 rounded-full border-[14px] border-white/20 transition duration-500 group-hover:scale-110"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#0D1527]/60">Booking value</p>
                        <p class="mt-3 truncate text-2xl font-black tracking-tight">{{ naira(totals.booking_value) }}</p>
                    </div>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-[#0D1527]/10 bg-white/30 text-[#0D1527] shadow-sm">
                        <i class="bi bi-receipt-cutoff text-lg"></i>
                    </span>
                </div>
                <p class="relative mt-5 flex items-center gap-2 border-t border-[#0D1527]/10 pt-3 text-[11px] font-medium text-[#0D1527]/60">
                    <i class="bi bi-graph-up-arrow"></i>
                    Non-cancelled order value
                </p>
            </div>

            <div class="group relative overflow-hidden rounded-3xl border border-emerald-200 bg-gradient-to-br from-white via-emerald-50 to-[#40e0d0]/15 p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)] transition duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-emerald-200/40 blur-xl transition duration-500 group-hover:scale-125"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700">Collected</p>
                        <p class="mt-3 truncate text-2xl font-black tracking-tight text-emerald-900">{{ naira(totals.collected) }}</p>
                    </div>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-emerald-200 bg-white/80 text-emerald-700 shadow-sm">
                        <i class="bi bi-check2-circle text-lg"></i>
                    </span>
                </div>
                <p class="relative mt-5 flex items-center gap-2 border-t border-emerald-200/70 pt-3 text-[11px] font-medium text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Value from paid orders
                </p>
            </div>
        </div>

        <div class="flex w-full flex-col gap-3 rounded-3xl border border-slate-200/80 bg-white p-3 shadow-[0_10px_30px_rgba(13,21,39,0.05)] xl:flex-row xl:items-center">
            <div class="relative min-w-0 flex-1">
                <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input
                    v-model="form.search"
                    type="search"
                    placeholder="Search reference, customer or phone…"
                    aria-label="Search orders"
                    class="h-11 w-full rounded-2xl border-slate-200 bg-slate-50/80 pl-10 pr-4 text-sm placeholder:text-slate-400 focus:border-[#40e0d0] focus:bg-white focus:ring-2 focus:ring-[#40e0d0]/20"
                    @keyup.enter="applyFilters"
                />
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex">
                <div class="relative min-w-0 sm:w-40">
                    <i class="bi bi-stars pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <select
                        v-model="form.status"
                        aria-label="Filter by order status"
                        class="h-11 w-full appearance-none rounded-2xl border-slate-200 bg-white pl-9 pr-8 text-sm font-medium text-slate-700 focus:border-[#40e0d0] focus:ring-2 focus:ring-[#40e0d0]/20"
                        @change="applyFilters"
                    >
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="refunded">Refunded</option>
                    </select>
                    <i class="bi bi-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"></i>
                </div>
                <div class="relative min-w-0 sm:w-44">
                    <i class="bi bi-truck pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <select
                        v-model="form.delivery"
                        aria-label="Filter by fulfilment"
                        class="h-11 w-full appearance-none rounded-2xl border-slate-200 bg-white pl-9 pr-8 text-sm font-medium text-slate-700 focus:border-[#40e0d0] focus:ring-2 focus:ring-[#40e0d0]/20"
                        @change="applyFilters"
                    >
                        <option value="">Any fulfilment</option>
                        <option value="pending">Not delivered</option>
                        <option value="delivered">Delivered</option>
                    </select>
                    <i class="bi bi-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"></i>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex">
                <button
                    v-if="form.search || form.status || form.delivery"
                    type="button"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                    @click="clearFilters"
                >
                    <i class="bi bi-x-lg"></i>
                    Clear
                </button>
                <button type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-[#40e0d0] px-5 text-sm font-bold text-[#0D1527] shadow-sm transition hover:-translate-y-0.5 hover:bg-[#35cab9] hover:shadow-md" @click="applyFilters">
                    <i class="bi bi-search"></i>
                    Search
                </button>
            </div>
            <div class="flex items-center rounded-2xl bg-slate-100 p-1" role="group" aria-label="Order view">
                <button
                    type="button"
                    class="flex h-9 flex-1 items-center justify-center gap-2 rounded-xl px-3 text-xs font-semibold transition xl:flex-none"
                    :class="viewMode === 'cards' ? 'bg-white text-[#0D1527] shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    :aria-pressed="viewMode === 'cards'"
                    @click="viewMode = 'cards'"
                >
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    Cards
                </button>
                <button
                    type="button"
                    class="flex h-9 flex-1 items-center justify-center gap-2 rounded-xl px-3 text-xs font-semibold transition xl:flex-none"
                    :class="viewMode === 'table' ? 'bg-white text-[#0D1527] shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    :aria-pressed="viewMode === 'table'"
                    @click="viewMode = 'table'"
                >
                    <i class="bi bi-list-ul"></i>
                    Table
                </button>
            </div>
        </div>

        <section class="w-full">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-tight text-[#0D1527]">{{ form.search || form.status || form.delivery ? 'Matching orders' : 'Recent orders' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">Track payments, fulfilment and every customer order in one place.</p>
                </div>
                <span class="w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-xs">
                    {{ orders.total }} {{ orders.total === 1 ? 'order' : 'orders' }}
                </span>
            </div>

            <div v-if="!orders.data.length" class="relative overflow-hidden rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-14 text-center">
                <div class="absolute -left-10 -top-10 h-32 w-32 rounded-full bg-[#40e0d0]/10"></div>
                <div class="absolute -bottom-12 -right-8 h-36 w-36 rounded-full bg-[#FACC15]/20"></div>
                <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-3xl border border-[#40e0d0]/30 bg-[#40e0d0] text-2xl text-[#0D1527] shadow-lg shadow-[#40e0d0]/20">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h3 class="relative mt-5 text-lg font-bold text-[#0D1527]">No orders found</h3>
                <p class="relative mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Try adjusting the filters to find the order you are looking for.</p>
                <button v-if="form.search || form.status || form.delivery" type="button" class="relative mt-6 rounded-xl bg-[#40e0d0] px-4 py-2.5 text-sm font-bold text-[#0D1527] transition hover:bg-[#35cab9]" @click="clearFilters">
                    Clear filters
                </button>
            </div>

            <div v-else-if="viewMode === 'cards'" class="grid w-full gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="o in orders.data"
                    :key="o.id"
                    class="group relative flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(13,21,39,0.05)] transition duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-[0_18px_40px_rgba(13,21,39,0.10)]"
                >
                    <div class="h-1.5 bg-gradient-to-r" :class="statusRail(o.status)"></div>
                    <div class="absolute right-0 top-5 h-24 w-24 translate-x-10 -translate-y-1/2 rounded-full bg-[#40e0d0]/10 transition duration-500 group-hover:scale-125"></div>
                    <div class="relative flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <Link :href="route('admin.orders.show', o.id)" class="font-mono text-sm font-black tracking-tight text-[#0D1527] transition hover:text-teal-700">
                                    {{ o.ref_id }}
                                </Link>
                                <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-slate-500">
                                    <i class="bi bi-clock"></i>
                                    {{ formatDateTime(o.created_at) }}
                                </p>
                            </div>
                            <span :class="statusBadge(o.status)">{{ statusLabel(o.status) }}</span>
                        </div>

                        <div class="mt-5 flex items-center gap-3 rounded-2xl border border-[#40e0d0]/15 bg-gradient-to-r from-[#FAF8F2] to-[#40e0d0]/10 p-3.5">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-[#40e0d0]/25 bg-white text-sm font-black text-[#0D1527] shadow-sm">
                                {{ customerInitial(o.customer_name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-[#0D1527]">{{ o.customer_name ?? 'Guest customer' }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ o.customer_phone || 'No phone supplied' }}</p>
                            </div>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/80 text-teal-700 shadow-xs">
                                <i class="bi bi-person"></i>
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2.5">
                            <div class="rounded-2xl border border-slate-100 bg-[#FAF8F2] p-3.5">
                                <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-500">Order items</p>
                                <p class="mt-1.5 flex items-center gap-1.5 text-sm font-black text-[#0D1527]">
                                    <i class="bi bi-bag text-teal-600"></i>
                                    {{ o.items_count }} {{ o.items_count === 1 ? 'item' : 'items' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border p-3.5" :class="o.fulfillment === 'delivered' ? 'border-emerald-200 bg-emerald-50' : 'border-[#FACC15]/50 bg-[#FACC15]/15'">
                                <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-500">Fulfilment</p>
                                <p class="mt-1.5 flex items-center gap-1.5 truncate text-xs font-bold" :class="o.fulfillment === 'delivered' ? 'text-emerald-800' : 'text-amber-800'">
                                    <i :class="o.fulfillment === 'delivered' ? 'bi bi-check-circle-fill' : 'bi bi-clock-fill'"></i>
                                    {{ fulfillmentLabel(o.fulfillment) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-500">Order total</p>
                                    <p class="mt-1 text-xl font-black tracking-tight text-[#0D1527]">{{ naira(o.total, 2) }}</p>
                                </div>
                                <span :class="fulfillmentBadge(o.fulfillment)" class="rounded-full border px-2.5 py-1 text-[10px] font-bold">
                                    {{ fulfillmentLabel(o.fulfillment) }}
                                </span>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="orderBalance(o) === 0 ? 'bg-emerald-500' : 'bg-gradient-to-r from-[#40e0d0] to-[#FACC15]'"
                                    :style="{ width: paymentProgress(o) + '%' }"
                                ></div>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <p class="text-slate-400">Paid</p>
                                    <p class="mt-0.5 font-bold text-emerald-700">{{ naira(o.paid_sum ?? 0, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-slate-400">Balance</p>
                                    <p class="mt-0.5 font-bold" :class="orderBalance(o) > 0 ? 'text-amber-700' : 'text-emerald-700'">{{ naira(orderBalance(o), 2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                <span class="h-2 w-2 rounded-full" :class="orderBalance(o) === 0 ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                                {{ orderBalance(o) === 0 ? 'Paid in full' : 'Payment in progress' }}
                            </div>
                            <Link :href="route('admin.orders.show', o.id)" class="inline-flex h-9 items-center gap-2 rounded-xl bg-[#40e0d0] px-3.5 text-xs font-bold text-[#0D1527] transition hover:-translate-y-0.5 hover:bg-[#35cab9]">
                                View order
                                <i class="bi bi-arrow-right text-[10px]"></i>
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="w-full overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(13,21,39,0.05)]">
                <div class="h-1.5 bg-gradient-to-r from-[#40e0d0] via-[#FACC15] to-[#40e0d0]"></div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[1100px] divide-y divide-slate-200 text-sm">
                        <thead class="bg-[#FAF8F2]">
                            <tr>
                                <th class="px-5 py-4 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Order</th>
                                <th class="px-4 py-4 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Customer</th>
                                <th class="px-4 py-4 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Date</th>
                                <th class="px-4 py-4 text-center text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Items</th>
                                <th class="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Total</th>
                                <th class="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Paid</th>
                                <th class="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Balance</th>
                                <th class="px-4 py-4 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Status</th>
                                <th class="px-4 py-4 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Fulfilment</th>
                                <th class="px-5 py-4 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="o in orders.data" :key="o.id" class="transition hover:bg-[#40e0d0]/[0.05]">
                                <td class="px-5 py-4">
                                    <Link :href="route('admin.orders.show', o.id)" class="font-mono text-xs font-black text-[#0D1527] transition hover:text-teal-700">
                                        {{ o.ref_id }}
                                    </Link>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[#40e0d0]/25 bg-[#40e0d0]/15 text-xs font-black text-[#0D1527]">{{ customerInitial(o.customer_name) }}</span>
                                        <div class="min-w-0">
                                            <p class="max-w-40 truncate font-bold text-slate-900">{{ o.customer_name ?? 'Guest customer' }}</p>
                                            <p class="mt-0.5 text-xs text-slate-400">{{ o.customer_phone || '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-600">{{ formatDateTime(o.created_at) }}</td>
                                <td class="px-4 py-4 text-center font-semibold text-slate-600">{{ o.items_count }}</td>
                                <td class="px-4 py-4 text-right font-black text-slate-900">{{ naira(o.total, 2) }}</td>
                                <td class="px-4 py-4 text-right font-bold text-emerald-700">{{ naira(o.paid_sum ?? 0, 2) }}</td>
                                <td class="px-4 py-4 text-right font-bold" :class="orderBalance(o) > 0 ? 'text-amber-700' : 'text-emerald-700'">{{ naira(orderBalance(o), 2) }}</td>
                                <td class="px-4 py-4"><span :class="statusBadge(o.status)">{{ statusLabel(o.status) }}</span></td>
                                <td class="px-4 py-4"><span :class="fulfillmentBadge(o.fulfillment)" class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold">{{ fulfillmentLabel(o.fulfillment) }}</span></td>
                                <td class="px-5 py-4 text-right">
                                    <Link :href="route('admin.orders.show', o.id)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#40e0d0] font-bold text-[#0D1527] transition hover:bg-[#35cab9]" :aria-label="`View order ${o.ref_id}`" title="View order">
                                        <i class="bi bi-eye"></i>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="orders.data.length && orders.last_page > 1" class="mt-4 w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
                <Pagination :meta="orders" />
            </div>
        </section>
    </div>
</template>