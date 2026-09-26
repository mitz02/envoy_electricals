<script setup>
import { Link, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, badgeClass, formatDate } from '@/lib/format';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    orders: { type: Object, required: true },
});

const statusBadge = {
    pending: badgeClass('bg-amber-100 text-amber-800'),
    paid: badgeClass('bg-emerald-100 text-emerald-800'),
    processing: badgeClass('bg-sky-100 text-sky-800'),
    completed: badgeClass('bg-emerald-100 text-emerald-800'),
    cancelled: badgeClass('bg-slate-200 text-slate-600'),
    refunded: badgeClass('bg-slate-200 text-slate-600'),
};

const fulfillmentBadge = {
    pending: badgeClass('bg-amber-100 text-amber-800'),
    delivered: badgeClass('bg-emerald-100 text-emerald-800'),
};
</script>

<template>
    <Head title="My Orders" />
    <FlashMessages />

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">My Orders</h1>
                <p class="mt-1 text-sm text-slate-500">Every order you've placed with Envoy.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="hidden rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-xs sm:inline-flex">
                    {{ orders.total }} {{ orders.total === 1 ? 'order' : 'orders' }}
                </span>
                <Link href="/account" class="text-sm font-semibold text-teal-700 transition hover:text-[#0D1527]">← Back to account</Link>
            </div>
        </div>

        <div v-if="orders.data.length" class="mt-8 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
            <div class="h-1.5 bg-gradient-to-r from-[#40e0d0] via-[#FACC15] to-[#40e0d0]"></div>
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[760px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-[#FAF8F2]">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Order</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Date</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Items</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Status</th>
                            <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Fulfilment</th>
                            <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Total</th>
                            <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Balance</th>
                            <th class="px-5 py-3.5 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="o in orders.data" :key="o.id" class="transition hover:bg-[#40e0d0]/[0.05]">
                            <td class="px-5 py-4 font-mono text-xs font-bold text-slate-900">{{ o.ref_id }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ formatDate(o.created_at) }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ o.item_count }}</td>
                            <td class="px-4 py-4"><span :class="statusBadge[o.status] || statusBadge.pending" class="capitalize">{{ o.status }}</span></td>
                            <td class="px-4 py-4"><span :class="fulfillmentBadge[o.fulfillment] || fulfillmentBadge.pending" class="capitalize">{{ o.fulfillment }}</span></td>
                            <td class="px-4 py-4 text-right font-bold text-slate-900">{{ naira(o.total) }}</td>
                            <td class="px-4 py-4 text-right font-bold" :class="o.balance > 0 ? 'text-amber-700' : 'text-emerald-700'">{{ naira(o.balance) }}</td>
                            <td class="px-5 py-4 text-right">
                                <Link :href="route('orders.pay', o.ref_id)" class="inline-flex h-8 items-center gap-1.5 rounded-xl bg-[#40e0d0] px-3 text-xs font-bold text-[#0D1527] transition hover:bg-[#35cab9]">
                                    View <i class="bi bi-arrow-right text-[10px]"></i>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-else class="mt-8 rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-12 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-[#40e0d0]/30 bg-[#40e0d0] text-2xl text-[#0D1527]">
                <i class="bi bi-bag"></i>
            </div>
            <h3 class="mt-4 text-base font-bold text-[#0D1527]">No orders yet</h3>
            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Browse the shop and your orders will appear here.</p>
            <Link href="/shop" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                Browse products <i class="bi bi-arrow-right text-xs"></i>
            </Link>
        </div>

        <div v-if="orders.data.length && orders.last_page > 1" class="mt-4 w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
            <Pagination :meta="orders" />
        </div>
    </div>
</template>