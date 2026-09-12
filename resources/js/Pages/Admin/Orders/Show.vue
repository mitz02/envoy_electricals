<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, formatDateTime, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    order: { type: Object, required: true },
    paid: { type: Number, default: 0 },
    balance: { type: Number, default: 0 },
});

const pendingConfirmations = props.order.payments
    ? props.order.payments.filter((p) => p.status === 'pending' && p.gateway === 'local')
    : [];

function confirmPayment(payment) {
    if (!confirm(`Confirm bank-transfer payment ${payment.ref_id} of ${naira(payment.amount, 2)}?`)) return;
    router.post(`/admin/orders/${props.order.id}/pay/${payment.id}`, {}, { preserveScroll: true });
}

function deliver() {
    if (!confirm(`Mark order ${props.order.ref_id} as delivered?`)) return;
    router.post(`/admin/orders/${props.order.id}/deliver`, {}, { preserveScroll: true });
}

const statusBadge = {
    pending: badgeClass('bg-amber-100 text-amber-800'),
    paid: badgeClass('bg-emerald-100 text-emerald-800'),
    processing: badgeClass('bg-sky-100 text-sky-800'),
    completed: badgeClass('bg-emerald-100 text-emerald-800'),
    cancelled: badgeClass('bg-slate-200 text-slate-600'),
    refunded: badgeClass('bg-slate-200 text-slate-600'),
};
</script>

<template>
    <FlashMessages />
    <PageHeader
        :title="order.ref_id"
        :subtitle="`Placed ${formatDateTime(order.created_at)}`"
    />

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span :class="statusBadge[order.status]">{{ order.status }}</span>
        <span :class="order.fulfillment === 'delivered' ? badgeClass('bg-emerald-100 text-emerald-800') : badgeClass('bg-amber-100 text-amber-800')">
            {{ order.fulfillment === 'delivered' ? 'Delivered' : 'Not Delivered' }}
        </span>

        <div class="ml-auto flex items-center gap-2">
            <Link href="/admin/orders" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back
            </Link>
            <button
                v-if="['paid', 'processing'].includes(order.status)"
                type="button"
                class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                @click="deliver"
            >
                Mark Delivered
            </button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Items</h2>
                </div>
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-2.5 text-left font-semibold text-slate-600">Product</th>
                            <th class="px-6 py-2.5 text-center font-semibold text-slate-600">Qty</th>
                            <th class="px-6 py-2.5 text-right font-semibold text-slate-600">Unit Price</th>
                            <th class="px-6 py-2.5 text-right font-semibold text-slate-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-6 py-3">
                                <span class="font-medium text-slate-800">{{ item.product?.name }}</span>
                                <p class="text-xs text-slate-400">{{ item.product?.sku }}</p>
                            </td>
                            <td class="px-6 py-3 text-center text-slate-600">{{ item.quantity }}</td>
                            <td class="px-6 py-3 text-right text-slate-600">{{ naira(item.unit_price, 2) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-slate-900">{{ naira(item.total, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Payments</h2>
                <ul v-if="order.payments?.length" class="mt-3 space-y-2 text-sm">
                    <li v-for="p in order.payments" :key="p.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-4 py-2.5">
                        <div>
                            <span class="font-mono text-xs font-medium text-slate-700">{{ p.ref_id }}</span>
                            <span class="ml-2 text-slate-600 capitalize">{{ p.payment_method }}</span>
                            <span v-if="p.gateway === 'paystack'" class="ml-1 text-xs text-slate-400">(online)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-900">{{ naira(p.amount, 2) }}</span>
                            <span :class="badgeClass(p.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800')">{{ p.status }}</span>
                            <button
                                v-if="p.status === 'pending' && p.gateway === 'local'"
                                type="button"
                                class="rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                                @click="confirmPayment(p)"
                            >
                                Confirm
                            </button>
                        </div>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-slate-400">No payments recorded yet.</p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Payment Summary</h2>
                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-600"><dt>Subtotal</dt><dd>{{ naira(order.subtotal, 2) }}</dd></div>
                    <div v-if="order.discount > 0" class="flex justify-between text-emerald-700"><dt>Discount</dt><dd>−{{ naira(order.discount, 2) }}</dd></div>
                    <div v-if="order.tax > 0" class="flex justify-between text-slate-600"><dt>Tax</dt><dd>{{ naira(order.tax, 2) }}</dd></div>
                    <div v-if="order.delivery_fee > 0" class="flex justify-between text-slate-600"><dt>Delivery</dt><dd>{{ naira(order.delivery_fee, 2) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-black text-slate-900"><dt>Total</dt><dd>{{ naira(order.total, 2) }}</dd></div>
                    <div class="flex justify-between font-semibold text-emerald-700"><dt>Paid</dt><dd>{{ naira(paid, 2) }}</dd></div>
                    <div class="flex justify-between font-bold" :class="balance > 0 ? 'text-red-600' : 'text-emerald-800'"><dt>Balance</dt><dd>{{ naira(balance, 2) }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Customer</h2>
                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex gap-2"><dt class="w-20 font-medium text-slate-600">Name:</dt><dd class="text-slate-900">{{ order.customer_name ?? '—' }}</dd></div>
                    <div class="flex gap-2"><dt class="w-20 font-medium text-slate-600">Phone:</dt><dd class="text-slate-900">{{ order.customer_phone ?? '—' }}</dd></div>
                    <div v-if="order.customer_email" class="flex gap-2"><dt class="w-20 font-medium text-slate-600">Email:</dt><dd class="text-slate-900">{{ order.customer_email }}</dd></div>
                    <div class="flex gap-2"><dt class="w-20 font-medium text-slate-600">Address:</dt><dd class="text-slate-900">{{ order.delivery_address ?? '—' }}</dd></div>
                </dl>
            </div>

            <div v-if="pendingConfirmations.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <p class="text-sm font-bold text-amber-800">Awaiting offline payment confirmation</p>
                <p class="mt-1 text-xs text-amber-700">
                    {{ pendingConfirmations.length }} customer(s) say they have transferred. Verify the payment in your bank app, then click Confirm.
                </p>
            </div>
        </div>
    </div>
</template>