<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const origin = window.location.origin;

const props = defineProps({
    package: { type: Object, required: true },
    images: { type: Array, default: () => [] },
    paystackConfigured: { type: Boolean, default: false },
});

function parsePrice(value) {
    if (!value) return 0;
    // Handle strings with commas (e.g., "1,500,000") or numeric strings
    const cleaned = String(value).replace(/,/g, '');
    const parsed = Number(cleaned);
    return Number.isNaN(parsed) ? 0 : parsed;
}

const packageUrl = computed(() => `${origin}/packages/${props.package.id}`);

const packagePrice = computed(() => {
    const price = parsePrice(props.package.package_price);
    const installation = parsePrice(props.package.installation_cost);
    return price + installation;
});

const firstImage = computed(() => props.images[0]?.media?.path ?? null);

const packageSchema = computed(() => JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: `${props.package.name} Solar Package`,
    description: String(props.package.description || props.package.name || '').slice(0, 300),
    image: firstImage.value ? `${origin}${firstImage.value.startsWith('/storage') ? '' : '/images/'}${firstImage.value}` : `${origin}/images/landing/solar_panels_sky.jpg`,
    offers: {
        '@type': 'Offer',
        url: packageUrl.value,
        priceCurrency: 'NGN',
        price: String(packagePrice.value.toFixed(2)),
        availability: props.package.availability === 'available' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        itemCondition: 'https://schema.org/NewCondition',
        seller: { '@type': 'Organization', name: 'Envoy Electricals' },
    },
}));

const pageMetaDescription = computed(() =>
    String(props.package.description || props.package.name || '').slice(0, 160),
);

const hasUser = !!usePage().props.auth?.user;
const user = usePage().props.auth?.user;
const hasCustomer = hasUser && user?.customer;
const isProcessing = ref(false);
const paymentError = ref(null);
const showGuestForm = ref(false);
const guestForm = ref({
    customer_name: hasUser ? (user?.name ?? '') : '',
    customer_phone: hasCustomer ? (user?.customer?.phone ?? '') : '',
    customer_email: hasCustomer ? (user?.customer?.email ?? user?.email ?? '') : (user?.email ?? ''),
});

const totalAmount = ref(0);

const currentImageIndex = ref(0);

function currentImage() {
    return props.images[currentImageIndex.value]?.media?.path;
}

function imageSrc(path) {
    return path?.startsWith('/images/') ? path : `/storage/${path}`;
}

onMounted(() => {
    totalAmount.value = packagePrice.value;
});

function naira(v) {
    return '₦' + Number(v || 0).toLocaleString('en-NG');
}

function handlePaystackPayment() {
    if (!props.paystackConfigured) {
        paymentError.value = 'Paystack is not configured. Please use bank transfer.';
        return;
    }

    // If user is logged in but needs to provide details, show form
    if (hasUser && !hasCustomer && (!guestForm.value.customer_name || !guestForm.value.customer_phone || !guestForm.value.customer_email)) {
        showGuestForm.value = true;
        return;
    }

    // If not logged in, show form to collect details
    if (!hasUser) {
        showGuestForm.value = true;
        return;
    }

    // Proceed with payment
    processPayment();
}

function processPayment() {
    isProcessing.value = true;
    paymentError.value = null;

    const payload = hasUser ? {} : guestForm.value;

    axios.post(`/packages/${props.package.id}/paystack`, payload)
        .then(({ data }) => {
            if (data.authorization_url) {
                window.location.href = data.authorization_url;
            } else if (data.requires_email) {
                // Server says email is required - show form
                showGuestForm.value = true;
                paymentError.value = data.message;
            } else {
                paymentError.value = data.message || 'Failed to initialize payment';
            }
        })
        .catch((e) => {
            if (e.response?.status === 422 && e.response?.data?.requires_email) {
                showGuestForm.value = true;
            }
            paymentError.value = e.response?.data?.message || 'Payment initialization failed';
        })
        .finally(() => {
            isProcessing.value = false;
        });
}
</script>

<template>
    <Head :title="`${package.name} Solar Package — Price & Specs | Envoy Electricals`">
        <meta name="description" :content="pageMetaDescription" />
        <link rel="canonical" :href="packageUrl" />
        <meta property="og:type" content="product" />
        <meta property="og:title" :content="`${package.name} Solar Package — Envoy Electricals`" />
        <meta property="og:description" :content="pageMetaDescription" />
        <meta property="og:url" :href="packageUrl" />
        <component is="script" type="application/ld+json">{{ packageSchema }}</component>
    </Head>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-6">
            <Link href="/packages" class="hover:text-amber-600">Solar Packages</Link>
            <span>/</span>
            <span class="text-slate-800 truncate">{{ package.name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
            <div>
                <div class="aspect-[4/3] overflow-hidden rounded-3xl border border-slate-200 bg-white relative">
                    <img
                        v-if="currentImage()"
                        :src="imageSrc(currentImage())"
                        :alt="package.name"
                        class="w-full h-full object-cover"
                    />
                    <img
                        v-else
                        src="/images/landing/solar_installation.jpg"
                        :alt="package.name"
                        class="w-full h-full object-cover"
                    />

                    <!-- Thumbnail navigation -->
                    <div v-if="props.images.length > 1" class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                        <button
                            v-for="(img, index) in props.images"
                            :key="img.id"
                            @click="currentImageIndex = index"
                            :class="[
                                'w-2 h-2 rounded-full transition-all',
                                index === currentImageIndex
                                    ? 'bg-white scale-125'
                                    : 'bg-white/50 hover:bg-white/75'
                            ]"
                            class="focus:outline-none focus:ring-2 focus:ring-white"
                        />
                    </div>
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

                    <!-- Error Message -->
                    <div v-if="paymentError" class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ paymentError }}
                    </div>

                    <!-- Guest/Profile Form - shows when email/details needed -->
                    <div v-if="showGuestForm" class="mb-4 p-4 rounded-xl border border-slate-200 bg-white">
                        <h3 class="text-sm font-semibold text-slate-900 mb-3">
                            {{ hasUser ? 'Complete your details for payment' : 'Enter your details to proceed' }}
                        </h3>
                        <p class="text-xs text-slate-500 mb-3">Your email is required for Paystack payment receipt.</p>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Full Name</label>
                                <input v-model="guestForm.customer_name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="John Doe" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Phone Number</label>
                                <input v-model="guestForm.customer_phone" type="tel" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="08012345678" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Email Address <span class="text-red-500">*</span></label>
                                <input v-model="guestForm.customer_email" type="email" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="john@example.com" />
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button @click="processPayment" :disabled="isProcessing" class="rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                                {{ isProcessing ? 'Processing...' : 'Continue to Paystack' }}
                            </button>
                            <button @click="showGuestForm = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                        </div>
                    </div>

                    <div v-else-if="props.paystackConfigured">
                        <button
                            @click="handlePaystackPayment"
                            :disabled="isProcessing"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-4 text-sm font-bold text-slate-950 hover:bg-amber-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-amber-400/30"
                        >
                            <i v-if="isProcessing" class="bi bi-arrow-clockwise animate-spin"></i>
                            <i v-else class="bi bi-credit-card"></i>
                            <span v-if="isProcessing">Processing...</span>
                            <span v-else>Pay {{ naira(totalAmount) }} with Paystack</span>
                        </button>
                    </div>

                    <p v-else class="rounded-lg bg-slate-100 p-3 text-center text-sm text-slate-500">
                        Online card payment is not available right now — please use bank transfer.
                    </p>

                    <div class="my-5 flex items-center gap-3 text-xs uppercase tracking-wide text-slate-400">
                        <span class="h-px flex-1 bg-slate-200" />or<span class="h-px flex-1 bg-slate-200" />
                    </div>

                    <Link
                        :href="`/packages/${props.package.id}/pay/bank-transfer`"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-300 bg-white px-6 py-4 text-sm font-bold text-slate-800 hover:border-slate-400 hover:bg-slate-50 transition-all"
                    >
                        <i class="bi bi-bank"></i>
                        Pay by Bank Transfer
                    </Link>
                    <p class="mt-2 text-center text-xs text-slate-400">Transfer to our account and send your proof on WhatsApp.</p>
                </div>

                <Link href="/packages" class="mt-6 block text-center text-sm font-semibold text-slate-500 hover:text-amber-600">← Back to all packages</Link>
            </div>
        </div>
    </div>
</template>