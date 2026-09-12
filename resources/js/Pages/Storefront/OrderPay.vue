<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira, badgeClass } from '@/lib/format';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    order: { type: Object, required: true },
    payments: { type: Array, default: () => [] },
    paid: { type: Number, default: 0 },
    balance: { type: Number, default: 0 },
    paystackConfigured: { type: Boolean, default: false },
    businessEmail: { type: String, default: '' },
    bank: { type: Object, default: () => ({}) },
});

const paying = ref(false);
const payloadError = ref('');
const paystackError = ref('');
const showBankModal = ref(false);
const offlineSubmitting = ref(false);

const isPaid = computed(() => props.balance <= 0 || ['cancelled', 'refunded'].includes(props.order.status));

function imageUrl(item) {
    const img = item?.product?.images?.[0]?.path ?? item?.product?.image ?? '';
    return img && !img.startsWith('/images/') ? `/storage/${img}` : '/images/landing/solar_products.jpg';
}

async function payWithPaystack() {
    if (!props.order.customer_email && !props.businessEmail) {
        payloadError.value = 'We need an email to create the Paystack payment.';
        return;
    }

    paying.value = true;
    paystackError.value = '';
    payloadError.value = '';

    try {
        const { data } = await axios.post(`/orders/${props.order.ref_id}/paystack`);
        window.open(data.authorization_url, '_blank');
    } catch (e) {
        paystackError.value = e.response?.data?.message ?? 'Something went wrong. Please try again.';
    } finally {
        paying.value = false;
    }
}

function confirmOffline() {
    offlineSubmitting.value = true;
    router.post(`/orders/${props.order.ref_id}/offline`, {}, { onFinish: () => { offlineSubmitting.value = false; } });
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
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Order {{ order.ref_id }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Placed {{ new Date(order.created_at).toLocaleString('en-GB') }} ·
                    <span :class="statusBadge[order.status]">{{ order.status }}</span>
                </p>
            </div>
            <Link href="/shop" class="text-sm font-medium text-slate-600 underline hover:text-slate-900">Continue shopping</Link>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-5">
            <div class="space-y-6 lg:col-span-3">
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Items</h2>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        <li v-for="item in order.items" :key="item.id" class="flex items-start gap-4 px-6 py-4">
                            <img :src="imageUrl(item)" alt="" class="h-14 w-14 rounded-xl object-cover" />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ item.product?.name ?? `Product #${item.product_id}` }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ item.quantity }} × {{ naira(item.unit_price, 2) }}
                                </p>
                            </div>
                            <p class="text-sm font-semibold text-slate-900">{{ naira(item.total, 2) }}</p>
                        </li>
                    </ul>
                    <div class="space-y-1.5 border-t border-slate-100 px-6 py-4 text-sm">
                        <div class="flex justify-between text-slate-600"><span>Subtotal</span><span>{{ naira(order.subtotal, 2) }}</span></div>
                        <div v-if="order.discount > 0" class="flex justify-between text-emerald-700"><span>Discount</span><span>−{{ naira(order.discount, 2) }}</span></div>
                        <div v-if="order.tax > 0" class="flex justify-between text-slate-600"><span>Tax</span><span>{{ naira(order.tax, 2) }}</span></div>
                        <div v-if="order.delivery_fee > 0" class="flex justify-between text-slate-600"><span>Delivery fee</span><span>{{ naira(order.delivery_fee, 2) }}</span></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-lg font-black text-slate-900">
                            <span>Total</span><span>{{ naira(order.total, 2) }}</span>
                        </div>
                        <div class="flex justify-between pt-1 font-semibold text-emerald-700">
                            <span>Paid</span><span>{{ naira(paid, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold" :class="balance > 0 ? 'text-red-600' : 'text-emerald-800'">
                            <span>Balance due</span><span>{{ naira(balance, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Delivery details</h2>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex gap-2"><dt class="w-24 font-medium text-slate-600">Name:</dt><dd class="text-slate-900">{{ order.customer_name }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 font-medium text-slate-600">Phone:</dt><dd class="text-slate-900">{{ order.customer_phone }}</dd></div>
                        <div v-if="order.customer_email" class="flex gap-2"><dt class="w-24 font-medium text-slate-600">Email:</dt><dd class="text-slate-900">{{ order.customer_email }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 font-medium text-slate-600">Address:</dt><dd class="text-slate-900">{{ order.delivery_address }}</dd></div>
                        <div class="flex gap-2">
                            <dt class="w-24 font-medium text-slate-600">Fulfilment:</dt>
                            <dd class="capitalize text-slate-900">{{ order.fulfillment }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="payments.length" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Payments</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="p in payments" :key="p.id" class="flex items-center justify-between">
                            <div>
                                <span class="font-mono text-xs font-medium text-slate-700">{{ p.ref_id }}</span>
                                <span class="ml-2 text-slate-600 capitalize">{{ p.payment_method }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-slate-900">{{ naira(p.amount, 2) }}</span>
                                <span :class="badgeClass(p.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800')">{{ p.status }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div v-if="isPaid" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                    <p class="text-lg font-black text-emerald-800">
                        {{ order.status === 'cancelled' || order.status === 'refunded' ? 'Order not payable' : 'Order paid — thank you!' }}
                    </p>
                    <p class="mt-2 text-sm text-emerald-700">
                        {{ order.status === 'cancelled' || order.status === 'refunded'
                            ? 'This order has been closed and can no longer be paid.'
                            : 'Your payment has been received. We will dispatch your order shortly.' }}
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Pay {{ naira(balance, 2) }}</h2>

                        <template v-if="paystackConfigured">
                            <button
                                type="button"
                                class="mt-4 w-full rounded-xl bg-[#0D1527] px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                :disabled="paying"
                                @click="payWithPaystack"
                            >
                                {{ paying ? 'Contacting Paystack…' : 'Pay Online with Paystack' }}
                            </button>
                            <p v-if="payloadError" class="mt-2 text-xs text-red-600">{{ payloadError }}</p>
                            <p v-if="paystackError" class="mt-2 text-xs text-red-600">{{ paystackError }}</p>
                            <div class="my-4 flex items-center gap-3 text-xs uppercase tracking-wide text-slate-400">
                                <span class="h-px flex-1 bg-slate-200" />or<span class="h-px flex-1 bg-slate-200" />
                            </div>
                        </template>
                        <p v-else class="mt-3 text-sm text-slate-500">Online card payment is currently unavailable.</p>

                        <button
                            type="button"
                            class="w-full rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                            @click="showBankModal = true"
                        >
                            Pay by Bank Transfer
                        </button>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                        <p class="font-semibold">How offline transfers work</p>
                        <p class="mt-1">Transfer the exact amount to the bank details shown, then click "I have transferred". Our team verifies and confirms your order within business hours.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="showBankModal"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center"
        @click.self="showBankModal = false"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between">
                <h3 class="text-lg font-black text-slate-900">Bank Transfer</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" @click="showBankModal = false">✕</button>
            </div>

            <dl class="mt-4 space-y-3 rounded-xl bg-slate-50 p-4 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-500">Account name</dt>
                    <dd class="font-bold text-slate-900">{{ bank['bank.account_name'] || 'Envoy Electricals' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-500">Account number</dt>
                    <dd class="font-mono text-lg font-black tracking-wide text-slate-900">{{ bank['bank.account_number'] || '5168265608' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="font-medium text-slate-500">Bank</dt>
                    <dd class="font-bold text-slate-900">{{ bank['bank.bank_name'] || 'Moniepoint MFB' }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-sm text-slate-600">
                Amount to pay: <span class="font-black text-slate-900">{{ naira(balance, 2) }}</span>
                <span class="ml-1 text-xs text-slate-400">({{ order.ref_id }})</span>
            </p>
            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                {{ bank['bank.instructions'] || 'Quote your order reference as the transfer narration so we can match it.' }}
            </p>

            <button
                type="button"
                class="mt-5 w-full rounded-xl bg-[#E4312B] px-6 py-3 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                :disabled="offlineSubmitting"
                @click="confirmOffline"
            >
                {{ offlineSubmitting ? 'Confirming…' : 'I have made the transfer' }}
            </button>
            <p class="mt-3 text-center text-xs text-slate-400">We verify transfers before confirming your order.</p>
        </div>
    </div>
</template>