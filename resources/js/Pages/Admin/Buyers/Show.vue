<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, badgeClass, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    buyer: { type: Object, required: true },
    customer: { type: Object, default: null },
    stats: { type: Object, required: true },
    orders: { type: Object, required: true },
    payments: { type: Array, default: () => [] },
});

const actions = useForm({});

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

function impersonate() {
    if (window.confirm(`Sign in as ${props.buyer.name} to see their account exactly as they do?`)) {
        actions.post(route('admin.buyers.impersonate', props.buyer.id));
    }
}

function removeBuyer() {
    if (window.confirm(`Delete the login account for ${props.buyer.name}? Their order history and linked customer record are kept for admin bookkeeping. This cannot be undone.`)) {
        actions.delete(route('admin.buyers.destroy', props.buyer.id));
    }
}
</script>

<template>
    <div class="w-full space-y-5">
        <FlashMessages />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <Link href="/admin/buyers" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#40e0d0] hover:text-[#0D1527]">
                    <i class="bi bi-arrow-left"></i>
                </Link>
                <div>
                    <p class="text-xs text-slate-400"><Link href="/admin/buyers" class="font-semibold text-teal-700 hover:underline">Buyers</Link> / Profile</p>
                    <h1 class="mt-0.5 text-xl font-black tracking-tight text-[#0D1527]">{{ buyer.name }}</h1>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Link :href="route('admin.buyers.edit', buyer.id)" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-[#FACC15] hover:text-slate-950">
                    <i class="bi bi-pencil-square"></i>
                    Edit
                </Link>
                <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0D1527] px-4 text-sm font-semibold text-white transition hover:bg-slate-800" @click="impersonate">
                    <i class="bi bi-incognito"></i>
                    View as buyer
                </button>
                <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-red-200 bg-white px-4 text-sm font-semibold text-red-600 transition hover:bg-red-50" @click="removeBuyer">
                    <i class="bi bi-trash3"></i>
                    Delete
                </button>
            </div>
        </div>

        <!-- Profile card -->
        <section class="relative overflow-hidden rounded-3xl bg-[#0D1527] p-6 text-white shadow-lg sm:p-8">
            <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#40e0d0]/20"></div>
            <div class="absolute -bottom-20 -left-10 h-48 w-48 rounded-full bg-[#FACC15]/15"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#FACC15] text-2xl font-black text-[#0D1527]">{{ (buyer.name || 'B').trim().charAt(0).toUpperCase() }}</span>
                    <div>
                        <h2 class="text-2xl font-black tracking-tight">{{ buyer.name }}</h2>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-300">
                            <span class="inline-flex items-center gap-1.5"><i class="bi bi-envelope"></i>{{ buyer.email }}</span>
                            <span v-if="buyer.phone" class="inline-flex items-center gap-1.5"><i class="bi bi-telephone"></i>{{ buyer.phone }}</span>
                            <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar2-check"></i>Joined {{ formatDate(buyer.created_at) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span v-if="customer" class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold">
                        Customer: {{ customer.ref_id }}
                    </span>
                    <Link v-if="customer" :href="route('admin.customers.show', customer.id)" class="inline-flex items-center gap-2 rounded-full bg-[#40e0d0] px-4 py-2 text-xs font-bold text-[#0D1527] transition hover:bg-[#35cab9]">
                        <i class="bi bi-people"></i>
                        View customer record
                    </Link>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#40e0d0]/15 text-[#159a8c]"><i class="bi bi-bag-check"></i></span>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Orders placed</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-[#0D1527]">{{ stats.orders_count }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><i class="bi bi-clock-history"></i></span>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Awaiting fulfilment</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-[#0D1527]">{{ stats.pending_count }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-3xl border border-[#40e0d0]/30 bg-gradient-to-br from-white to-[#40e0d0]/10 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#40e0d0] text-[#0D1527]"><i class="bi bi-wallet2"></i></span>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Total spent</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-[#0D1527]">{{ naira(stats.total_spent) }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-3xl border border-[#FACC15]/50 bg-[#FACC15]/20 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0D1527] text-[#FACC15]"><i class="bi bi-cash-coin"></i></span>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#0D1527]/60">Outstanding</p>
                        <p class="mt-1 text-2xl font-black tracking-tight" :class="stats.outstanding > 0 ? 'text-amber-800' : 'text-emerald-800'">{{ naira(stats.outstanding) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Orders -->
        <section class="w-full">
            <div class="mb-3 flex items-end justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-tight text-[#0D1527]">Orders</h2>
                    <p class="mt-1 text-xs text-slate-500">Every online order placed from this account.</p>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span><span class="rounded-full bg-sky-50 px-2.5 py-1 font-semibold text-sky-700">{{ orders.total }}</span> orders</span>
                </div>
            </div>

            <div v-if="orders.data.length" class="w-full overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="h-1.5 bg-gradient-to-r from-[#40e0d0] via-[#FACC15] to-[#40e0d0]"></div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[820px] divide-y divide-slate-200 text-sm">
                        <thead class="bg-[#FAF8F2]">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Order</th>
                                <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Date</th>
                                <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Items</th>
                                <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Status</th>
                                <th class="px-4 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Fulfilment</th>
                                <th class="px-4 py-3.5 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Total</th>
                                <th class="px-5 py-3.5 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="o in orders.data" :key="o.id" class="transition hover:bg-[#40e0d0]/[0.05]">
                                <td class="px-5 py-4"><Link :href="route('admin.orders.show', o.id)" class="font-mono text-xs font-bold text-teal-700 hover:underline">{{ o.ref_id }}</Link></td>
                                <td class="px-4 py-4 text-slate-600">{{ formatDate(o.created_at) }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ o.item_count }}</td>
                                <td class="px-4 py-4"><span :class="statusBadge[o.status] || statusBadge.pending" class="capitalize">{{ o.status }}</span></td>
                                <td class="px-4 py-4"><span :class="fulfillmentBadge[o.fulfillment] || fulfillmentBadge.pending" class="capitalize">{{ o.fulfillment }}</span></td>
                                <td class="px-4 py-4 text-right font-bold text-slate-900">{{ naira(o.total) }}</td>
                                <td class="px-5 py-4 text-right font-bold" :class="o.balance > 0 ? 'text-amber-700' : 'text-emerald-700'">{{ naira(o.balance) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-[#40e0d0]/30 bg-[#40e0d0] text-2xl text-[#0D1527]">
                    <i class="bi bi-bag"></i>
                </div>
                <h3 class="mt-4 text-base font-bold text-[#0D1527]">No orders yet</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">This buyer hasn't placed any online orders yet.</p>
            </div>

            <div v-if="orders.data.length && orders.last_page > 1" class="mt-4 w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
                <Pagination :meta="orders" />
            </div>
        </section>

        <!-- Payments -->
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Recent payments</h2>
            <ul v-if="payments.length" class="mt-4 space-y-3">
                <li v-for="p in payments" :key="p.id" class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-mono text-xs font-bold text-slate-900">{{ p.ref_id }}</p>
                        <p class="text-xs text-slate-400 capitalize">{{ p.payment_method }} · {{ formatDate(p.payment_date ?? p.created_at) }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="font-bold text-slate-900">{{ naira(p.amount, 2) }}</span>
                        <span :class="badgeClass(p.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800')">{{ p.status }}</span>
                    </div>
                </li>
            </ul>
            <p v-else class="mt-4 text-sm text-slate-400">No payments recorded yet.</p>
        </section>
    </div>
</template>