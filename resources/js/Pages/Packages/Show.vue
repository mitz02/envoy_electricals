<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    package: { type: Object, required: true },
    image: { type: Object, default: null },
    paystackConfigured: { type: Boolean, default: false },
    bankDetails: { type: Object, default: () => ({}) },
});

const hasUser = !!usePage().props.auth?.user;
const isProcessing = ref(false);
const paymentError = ref(null);
const showOfflineDetails = ref(false);
const activeTab = ref('paystack');

const totalAmount = ref(0);

onMounted(() => {
    totalAmount.value = (props.package.package_price || 0) + (props.package.installation_cost || 0);
});

function naira(v) {
    return '₦' + Number(v || 0).toLocaleString('en-NG');
}

function handlePaystackPayment() {
    if (!hasUser) {
        router.visit('/portal/register');
        return;
    }

    if (!props.paystackConfigured) {
        paymentError.value = 'Paystack is not configured. Please use offline payment.';
        return;
    }

    isProcessing.value = true;
    paymentError.value = null;

    router.post(`/packages/${props.package.id}/paystack`, {}, {
        onSuccess: (page) => {
            if (page.props.authorization_url) {
                window.location.href = page.props.authorization_url;
            } else {
                paymentError.value = 'Failed to initialize payment';
            }
            isProcessing.value = false;
        },
        onError: (errors) => {
            paymentError.value = errors.payment || 'Payment initialization failed';
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
}

function handleOfflinePayment() {
    if (!hasUser) {
        router.visit('/portal/register');
        return;
    }

    isProcessing.value = true;
    paymentError.value = null;

    router.post(`/orders/${props.package.id}/offline`, {
        package_id: props.package.id,
    }, {
        onSuccess: () => {
            showOfflineDetails.value = true;
            isProcessing.value = false;
        },
        onError: (errors) => {
            paymentError.value = errors.payment || 'Failed to process offline payment';
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-6">
            <Link href="/packages" class="hover:text-amber-600">Solar Packages</Link>
            <span>/</span>
            <span class="text-slate-800 truncate">{{ package.name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
            <div>
                <div class="aspect-[4/3] overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    <img
                        :src="image ? `/storage/${image.path}` : '/images/landing/solar_installation.jpg'"
                        :alt="package.name"
                        class="w-full h-full object-cover"
                    />
                </div>
            </div>

            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-950 tracking-tight">{{ package.name }}</h1>
                    <span v-if="package.is_featured" class="rounded-full bg-amber-400 px-3 py-1 text-[10px] font-black uppercase text-slate-950">Popular</span>
                </div>
                <p class="mt-3 text-slate-600 leading-relaxed">{{ package.description }}</p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span v-if="package.estimated_load_capacity" class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Load: {{ package.estimated_load_capacity }}W</span>
                    <span v-if="package.inverter_capacity" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ package.inverter_capacity }} inverter</span>
                    <span v-if="package.warranty" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ package.warranty }} warranty</span>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-3">What's included</p>
                    <ul class="space-y-2.5">
                        <li v-for="item in package.items" :key="item.id" class="flex items-start gap-2 text-sm text-slate-600">
                            <i class="bi bi-check2 text-emerald-500 mt-0.5"></i>
                            <span>{{ item.quantity }}× {{ item.name }}<span v-if="item.specification" class="text-slate-400"> — {{ item.specification }}</span></span>
                        </li>
                        <li v-if="!package.items?.length" class="text-sm text-slate-400">Components configured on request.</li>
                    </ul>
                </div>

                <div class="mt-6 flex items-end justify-between bg-[#0D1527] rounded-2xl p-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Package price</p>
                        <p class="text-3xl font-extrabold text-white">{{ naira(package.package_price) }}</p>
                    </div>
                    <div v-if="package.installation_cost > 0" class="text-right">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Installation</p>
                        <p class="text-sm font-bold text-amber-400">{{ naira(package.installation_cost) }}</p>
                    </div>
                </div>

                <!-- Total Amount -->
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900">Total (Package + Installation)</span>
                        <span class="text-2xl font-extrabold text-amber-600">{{ naira(totalAmount) }}</span>
                    </div>
                </div>

                <!-- Payment Options -->
                <div class="mt-6">
                    <p class="text-sm font-semibold text-slate-900 mb-4">Choose Payment Method</p>

                    <!-- Tabs -->
                    <div class="flex gap-2 mb-4 border-b border-slate-200">
                        <button
                            @click="activeTab = 'paystack'"
                            :class="[
                                'px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 transition-colors',
                                activeTab === 'paystack'
                                    ? 'border-amber-500 text-amber-600'
                                    : 'border-transparent text-slate-400 hover:text-slate-600'
                            ]"
                            :disabled="!props.paystackConfigured"
                        >
                            <i class="bi bi-credit-card mr-1"></i> Paystack (Card/Bank/USSD)
                            <span v-if="!props.paystackConfigured" class="ml-1 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">Unavailable</span>
                        </button>
                        <button
                            @click="activeTab = 'offline'"
                            :class="[
                                'px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 transition-colors',
                                activeTab === 'offline'
                                    ? 'border-amber-500 text-amber-600'
                                    : 'border-transparent text-slate-400 hover:text-slate-600'
                            ]"
                        >
                            <i class="bi bi-bank mr-1"></i> Bank Transfer (Offline)
                        </button>
                    </div>

                    <!-- Error Message -->
                    <div v-if="paymentError" class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ paymentError }}
                    </div>

                    <!-- Paystack Tab -->
                    <div v-if="activeTab === 'paystack'" class="space-y-4">
                        <button
                            @click="handlePaystackPayment"
                            :disabled="isProcessing || !props.paystackConfigured || !hasUser"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-4 text-sm font-bold text-slate-950 hover:bg-amber-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-amber-400/30"
                        >
                            <i v-if="isProcessing" class="bi bi-arrow-clockwise animate-spin"></i>
                            <i v-else class="bi bi-credit-card"></i>
                            <span v-if="isProcessing">Processing...</span>
                            <span v-else>Pay {{ naira(totalAmount) }} with Paystack</span>
                        </button>

                        <p v-if="!hasUser" class="text-center text-sm text-slate-500">
                            You'll be redirected to register/login first, then complete payment.
                        </p>

                        <p v-if="!props.paystackConfigured" class="text-center text-sm text-red-600">
                            Paystack is not configured. Please use offline payment.
                        </p>
                    </div>

                    <!-- Offline/Bank Transfer Tab -->
                    <div v-if="activeTab === 'offline'" class="space-y-4">
                        <button
                            @click="handleOfflinePayment"
                            :disabled="isProcessing || !hasUser"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-200 px-6 py-4 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i v-if="isProcessing" class="bi bi-arrow-clockwise animate-spin"></i>
                            <i v-else class="bi bi-bank"></i>
                            <span v-if="isProcessing">Processing...</span>
                            <span v-else>Proceed to Offline Payment</span>
                        </button>

                        <p v-if="!hasUser" class="text-center text-sm text-slate-500">
                            You'll be redirected to register/login first.
                        </p>

                        <!-- Bank Details (shown after offline payment intent) -->
                        <div v-if="showOfflineDetails" class="rounded-xl border border-amber-200 bg-amber-50 p-5 animate-fade-in">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="bi bi-info-circle text-amber-600 text-xl"></i>
                                <h3 class="text-lg font-bold text-amber-800">Bank Transfer Details</h3>
                            </div>
                            <p class="text-sm text-amber-800 mb-4">
                                Transfer <span class="font-bold">{{ naira(totalAmount) }}</span> for <span class="font-bold">{{ package.name }}</span> to the account below.
                                Include the payment reference in the narration.
                            </p>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-amber-100">
                                    <span class="text-sm font-medium text-slate-500">Account Name</span>
                                    <div class="flex items-center gap-2">
                                        <code class="flex-1 text-sm font-mono text-slate-900 bg-amber-50 px-2 py-1 rounded">{{ bankDetails.account_name || 'Envoy Electricals' }}</code>
                                        <button @click="copyToClipboard(bankDetails.account_name || 'Envoy Electricals')" class="text-amber-600 hover:text-amber-800 text-sm">Copy</button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-amber-100">
                                    <span class="text-sm font-medium text-slate-500">Account Number</span>
                                    <div class="flex items-center gap-2">
                                        <code class="flex-1 text-sm font-mono text-slate-900 bg-amber-50 px-2 py-1 rounded">{{ bankDetails.account_number || '5168265608' }}</code>
                                        <button @click="copyToClipboard(bankDetails.account_number || '5168265608')" class="text-amber-600 hover:text-amber-800 text-sm">Copy</button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-amber-100">
                                    <span class="text-sm font-medium text-slate-500">Bank Name</span>
                                    <div class="flex items-center gap-2">
                                        <code class="flex-1 text-sm font-mono text-slate-900 bg-amber-50 px-2 py-1 rounded">{{ bankDetails.bank_name || 'Moniepoint MFB' }}</code>
                                        <button @click="copyToClipboard(bankDetails.bank_name || 'Moniepoint MFB')" class="text-amber-600 hover:text-amber-800 text-sm">Copy</button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-amber-100">
                                    <span class="text-sm font-medium text-slate-500">Narration / Reference</span>
                                    <div class="flex items-center gap-2">
                                        <code class="flex-1 text-sm font-mono text-slate-900 bg-amber-50 px-2 py-1 rounded">PAY-{{ package.ref_id || package.id }}</code>
                                        <button @click="copyToClipboard('PAY-' + (package.ref_id || package.id))" class="text-amber-600 hover:text-amber-800 text-sm">Copy</button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 p-3 rounded-lg bg-white border border-amber-100">
                                <p class="text-xs text-slate-600 mb-2 font-semibold">Important:</p>
                                <ul class="text-xs text-slate-600 space-y-1 list-disc list-inside">
                                    <li>Transfer the exact amount: <span class="font-bold">{{ naira(totalAmount) }}</span></li>
                                    <li>Use the reference <span class="font-bold">PAY-{{ package.ref_id || package.id }}</span> in the narration</li>
                                    <li>Payment will be verified manually within 1-2 business hours</li>
                                    <li>You'll receive a confirmation once verified</li>
                                </ul>
                            </div>

                            <button
                                @click="showOfflineDetails = false"
                                class="mt-4 w-full text-sm font-medium text-amber-700 hover:text-amber-900 underline"
                            >
                                Hide Details
                            </button>
                        </div>
                    </div>
                </div>

                <Link href="/packages" class="mt-6 block text-center text-sm font-semibold text-slate-500 hover:text-amber-600">← Back to all packages</Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>