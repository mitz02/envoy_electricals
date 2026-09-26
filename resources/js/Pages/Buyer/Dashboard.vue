<script setup>
import { computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira, badgeClass, formatDate } from '@/lib/format';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    user: { type: Object, required: true },
    stats: { type: Object, default: () => ({ orders_count: 0, pending_count: 0, total_spent: 0, outstanding: 0 }) },
    recent_orders: { type: Array, default: () => [] },
    payments: { type: Array, default: () => [] },
});

const firstName = computed(() => (props.user.name || 'there').trim().split(/\s+/)[0]);

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

const quickLinks = [
    { label: 'My orders', sub: 'Track & pay', href: '/account/orders', icon: 'bi-bag-check' },
    { label: 'Browse shop', sub: 'Solar products', href: '/shop', icon: 'bi-sun' },
    { label: 'Packages', sub: 'Ready-made setups', href: '/packages', icon: 'bi-box-seam' },
    { label: 'Free calculator', sub: 'Size your system', href: '/calculator', icon: 'bi-calculator' },
];
</script>

<template>
    <Head title="My Account" />
    <FlashMessages />

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <!-- Hero -->
        <section class="relative overflow-hidden rounded-[28px] bg-[#0D1527] p-6 text-white shadow-xl shadow-[#0D1527]/20 sm:p-8">
            <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-[#40e0d0]/20"></div>
            <div class="absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-[#FACC15]/10"></div>
            <div class="relative">
                <div class="flex flex-wrap items-center justify-between gap-5">
                    <div class="flex items-center gap-4">
                        <div class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#FACC15] to-[#fde047] text-3xl font-black text-[#0D1527] shadow-lg shadow-[#FACC15]/30">
                            {{ (user.name || 'B').trim().charAt(0).toUpperCase() }}
                            <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-[#40e0d0] ring-4 ring-[#0D1527]">
                                <i class="bi bi-patch-check text-[11px] text-[#0D1527]"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#40e0d0]">Envoy buyer account</p>
                            <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">Welcome back, {{ firstName }} 👋</h1>
                            <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-300">
                                <span class="inline-flex items-center gap-1.5"><i class="bi bi-envelope text-xs"></i>{{ user.email }}</span>
                                <span v-if="user.phone" class="inline-flex items-center gap-1.5"><i class="bi bi-telephone text-xs"></i>{{ user.phone }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2.5">
                        <Link href="/account/orders" class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#40e0d0] px-4 text-sm font-bold text-[#0D1527] transition hover:-translate-y-0.5 hover:bg-[#35cab9] hover:shadow-lg hover:shadow-[#40e0d0]/25">
                            <i class="bi bi-bag-check"></i>
                            My orders
                        </Link>
                        <Link href="/shop" class="inline-flex h-11 items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                            <i class="bi bi-sun"></i>
                            Shop solar
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Outstanding alert -->
        <section v-if="stats.outstanding > 0" class="mt-5 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-400/30 text-amber-700"><i class="bi bi-exclamation-triangle"></i></span>
                    <div>
                        <p class="text-sm font-bold text-amber-900">You have an outstanding balance</p>
                        <p class="text-xs text-amber-700">Settle {{ naira(stats.outstanding) }} online so your orders stay on track.</p>
                    </div>
                </div>
                <Link href="/account/orders" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0D1527] px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Pay now <i class="bi bi-arrow-right text-xs"></i>
                </Link>
            </div>
        </section>

        <!-- Stats -->
        <section class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="group rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Orders placed</p>
                        <p class="mt-2 text-3xl font-black tracking-tight text-[#0D1527]">{{ stats.orders_count }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#40e0d0]/15 text-[#159a8c] transition group-hover:scale-110"><i class="bi bi-bag-check text-xl"></i></span>
                </div>
            </div>
            <div class="group rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Awaiting fulfilment</p>
                        <p class="mt-2 text-3xl font-black tracking-tight text-[#0D1527]">{{ stats.pending_count }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 transition group-hover:scale-110"><i class="bi bi-clock-history text-xl"></i></span>
                </div>
            </div>
            <div class="group rounded-3xl border border-[#40e0d0]/30 bg-gradient-to-br from-white to-[#40e0d0]/10 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Total spent</p>
                        <p class="mt-2 text-3xl font-black tracking-tight text-[#0D1527]">{{ naira(stats.total_spent) }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#40e0d0] text-[#0D1527] transition group-hover:scale-110"><i class="bi bi-wallet2 text-xl"></i></span>
                </div>
            </div>
            <div class="group rounded-3xl border border-[#FACC15]/50 bg-[#FACC15]/20 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#0D1527]/60">Outstanding balance</p>
                        <p class="mt-2 text-3xl font-black tracking-tight" :class="stats.outstanding > 0 ? 'text-amber-800' : 'text-emerald-800'">{{ naira(stats.outstanding) }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#0D1527] text-[#FACC15] transition group-hover:scale-110"><i class="bi bi-cash-coin text-xl"></i></span>
                </div>
            </div>
        </section>

        <!-- Quick actions -->
        <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="q in quickLinks"
                :key="q.href"
                :href="q.href"
                class="group flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#40e0d0]/60 hover:shadow-md"
            >
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#40e0d0]/25 bg-[#40e0d0]/10 text-lg text-[#159a8c] transition group-hover:bg-[#40e0d0] group-hover:text-[#0D1527]">
                    <i :class="q.icon"></i>
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold text-[#0D1527]">{{ q.label }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ q.sub }}</span>
                </span>
                <i class="bi bi-chevron-right ml-auto text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-[#159a8c]"></i>
            </Link>
        </section>

        <!-- Recent orders -->
        <section class="mt-8">
            <div class="flex items-end justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-tight text-[#0D1527]">Recent orders</h2>
                    <p class="mt-1 text-xs text-slate-500">Track everything from checkout to delivery.</p>
                </div>
                <Link href="/account/orders" class="text-xs font-bold text-teal-700 transition hover:text-[#0D1527]">
                    View all →
                </Link>
            </div>

            <div v-if="recent_orders.length" class="mt-4 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="h-1.5 bg-gradient-to-r from-[#40e0d0] via-[#FACC15] to-[#40e0d0]"></div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[720px] divide-y divide-slate-200 text-sm">
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
                            <tr v-for="o in recent_orders" :key="o.id" class="transition hover:bg-[#40e0d0]/[0.05]">
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
            <div v-else class="mt-4 rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-[#40e0d0]/30 bg-[#40e0d0] text-2xl text-[#0D1527]">
                    <i class="bi bi-bag"></i>
                </div>
                <h3 class="mt-4 text-base font-bold text-[#0D1527]">No orders yet</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">When you place an order it will show up here so you can track it and pay online.</p>
                <Link href="/shop" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Browse products <i class="bi bi-arrow-right text-xs"></i>
                </Link>
            </div>
        </section>

        <!-- Payments + help -->
        <section class="mt-8 grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Recent payments</h2>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><i class="bi bi-credit-card"></i></span>
                </div>
                <ul v-if="payments.length" class="mt-4 space-y-3">
                    <li v-for="p in payments" :key="p.id" class="flex items-center justify-between gap-3 rounded-xl bg-[#FAF8F2] px-4 py-3">
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
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Need help?</h2>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#40e0d0]/15 text-[#159a8c]"><i class="bi bi-headset"></i></span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Questions about an order, delivery or a product? Our team is one message away.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="https://wa.me/2348097089259" target="_blank" rel="noopener" class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#25D366]/40 bg-[#25D366]/10 px-4 text-sm font-semibold text-[#1fb858] transition hover:bg-[#25D366]/20">
                        <i class="bi bi-whatsapp"></i>
                        WhatsApp us
                    </a>
                    <Link href="/contact" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-[#40e0d0]">
                        Contact page
                    </Link>
                </div>
            </div>
        </section>

        <!-- Profile strip -->
        <section class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white px-5 py-4 shadow-sm">
            <p class="text-sm text-slate-600">
                Manage your details and password from your profile.
            </p>
            <Link href="/profile" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0D1527] px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                <i class="bi bi-gear"></i>
                Edit profile
            </Link>
        </section>
    </div>
</template>