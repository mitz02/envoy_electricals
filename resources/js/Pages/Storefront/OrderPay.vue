<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
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
    whatsappNumber: { type: String, default: '' },
    bankAccountName: { type: String, default: '' },
    bankAccountNumber: { type: String, default: '' },
    bankName: { type: String, default: '' },
    bankInstructions: { type: String, default: '' },
});

const page = usePage();

const whatsapp = computed(() => (props.whatsappNumber || '2348097089259').replace(/\D/g, ''));

const paying = ref(false);
const payloadError = ref('');
const paystackError = ref('');
const verifying = ref(false);
const paymentSuccess = ref(false);

const isPaid = computed(() => props.balance <= 0 || ['cancelled', 'refunded'].includes(props.order.status) || paymentSuccess.value);

// Check for Paystack callback on page load
onMounted(() => {
    const reference = page.props.url.query.reference ?? page.props.url.query.trxref;
    if (reference) {
        verifyPayment(reference);
    }
});

async function verifyPayment(reference) {
    verifying.value = true;
    try {
        const { data } = await axios.get(`/orders/${props.order.ref_id}/paystack/verify`, {
            params: { reference },
            timeout: 30000
        });
        if (data.success) {
            paymentSuccess.value = true;
            // Refresh page data to show updated balance
            window.location.reload();
        } else {
            paystackError.value = data.message ?? 'Payment verification failed.';
        }
    } catch (e) {
        console.error('Payment verification failed:', e);
        paystackError.value = e.response?.data?.message ?? 'Could not verify payment. Please try again.';
    } finally {
        verifying.value = false;
    }
}

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
    <Head>
        <meta name="robots" content="noindex, nofollow" />
    </Head>
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
                <div v-if="verifying" class="rounded-2xl border border-sky-200 bg-sky-50 p-6 text-center">
                    <div class="flex items-center justify-center gap-2 text-sky-700">
                        <svg class="animate-spin h-6 w-6" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        <span class="font-medium">Verifying your payment...</span>
                    </div>
                    <p v-if="paystackError" class="mt-3 text-sm text-red-600">{{ paystackError }}</p>
                </div>

                <div v-else-if="paymentSuccess" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                    <p class="text-lg font-black text-emerald-800">Payment Successful — Thank You!</p>
                    <p class="mt-2 text-sm text-emerald-700">Your payment has been verified. We will dispatch your order shortly.</p>
                </div>

                <div v-else-if="isPaid" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
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
                        </template>
                        <p v-else class="mt-3 text-sm text-slate-500">Online card payment is currently unavailable. Please contact us for alternative payment options.</p>

                        <!-- Offline / Bank Transfer Payment -->
                        <template v-if="bankAccountName && bankAccountNumber && bankName">
                            <div class="mt-6 pt-6 border-t border-slate-100">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Or Pay via Bank Transfer</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ bankInstructions }}</p>

                                <div class="mt-4 rounded-xl bg-amber-50 border border-amber-100 p-4">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Bank</p>
                                            <p class="text-sm font-semibold text-slate-900">{{ bankName }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Account Name</p>
                                            <p class="text-sm font-semibold text-slate-900">{{ bankAccountName }}</p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Account Number</p>
                                            <p class="text-lg font-mono font-bold text-slate-900 break-all">{{ bankAccountNumber }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">Important</p>
                                    <ul class="text-xs text-slate-600 space-y-1 list-disc list-inside">
                                        <li>Transfer the exact amount: <strong>{{ naira(balance, 2) }}</strong></li>
                                        <li>Use your Order Reference <strong>{{ order.ref_id }}</strong> as the narration</li>
                                        <li>Take a screenshot or photo of the transfer receipt</li>
                                    </ul>
                                </div>

                                <a
                                    :href="`https://wa.me/${whatsapp}?text=${encodeURIComponent('Payment Proof for Order ' + order.ref_id + '\nAmount: ' + naira(balance, 2) + '\nBank: ' + bankName + '\nAccount: ' + bankAccountName + ' (' + bankAccountNumber + ')')}`"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#25D366] px-5 py-3 text-sm font-bold text-white hover:bg-[#1fb858] transition-colors"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm4.5 13c-.2.2-1.4.7-1.7.8s-.4.1-.7 0-.9-.3-1.4-.7a5.6 5.6 0 0 1-1.9-2.5c-.1-.2.2-.4.4-.6l.4-.6a.8.8 0 0 0 .1-.2.5.5 0 0 0 0-.4c-.1-.2-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a.8.8 0 0 0-.6.3 2.6 2.6 0 0 0-.8 1.9 4.5 4.5 0 0 0 .9 2.4 10 10 0 0 0 3.9 3.4c1.2.6 1.7.6 2.1.6a2.6 2.6 0 0 0 1.7-1.2 2 2 0 0 0 .1-1.2z"/></svg>
                                    <span>Send Receipt via WhatsApp</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== WHATSAPP FLOAT ===================== -->
    <a
        :href="`https://wa.me/${whatsapp}`"
        target="_blank"
        rel="noopener"
        title="Chat on WhatsApp"
        class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-[0_6px_18px_rgba(37,211,102,0.4)] transition-all duration-200 hover:-translate-y-1 hover:bg-[#1fb858] active:translate-y-0 sm:h-16 sm:w-16"
    >
        <svg class="h-7 w-7 sm:h-8 sm:w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm4.5 13c-.2.2-1.4.7-1.7.8s-.4.1-.7 0-.9-.3-1.4-.7a5.6 5.6 0 0 1-1.9-2.5c-.1-.2.2-.4.4-.6l.4-.6a.8.8 0 0 0 .1-.2.5.5 0 0 0 0-.4c-.1-.2-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a.8.8 0 0 0-.6.3 2.6 2.6 0 0 0-.8 1.9 4.5 4.5 0 0 0 .9 2.4 10 10 0 0 0 3.9 3.4c1.2.6 1.7.6 2.1.6a2.6 2.6 0 0 0 1.7-1.2 2 2 0 0 0 .1-1.2z"/></svg>
        <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-[#E4312B]"></span>
    </a>
</template>