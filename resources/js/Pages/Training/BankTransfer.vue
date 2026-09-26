<script setup>
import { ref, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    training: { type: Object, required: true },
    bankDetails: { type: Object, default: () => ({}) },
    whatsappNumber: { type: String, default: '' },
});

const whatsapp = computed(() => (props.whatsappNumber || '2348097089259').replace(/\D/g, ''));

const bankName = computed(() => props.bankDetails.bank_name || 'Moniepoint MFB');
const accountName = computed(() => props.bankDetails.account_name || 'Envoy Electricals');
const accountNumber = computed(() => props.bankDetails.account_number || '5168265608');

const waProofLink = computed(() => {
    const lines = [
        `Hello Envoy Academy! I just made a bank transfer for "${props.training.title}".`,
        `Program reference: ${props.training.ref_id}`,
        `Amount paid: ${formatPrice(props.training.price)}`,
        'Here is my proof of payment.',
    ];
    return `https://wa.me/${whatsapp.value}?text=${encodeURIComponent(lines.filter(Boolean).join('\n'))}`;
});

const offlineSubmitting = ref(false);
const copied = ref('');

const formatPrice = (price) => {
    if (!price || price === 0) return 'Free';
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 0 }).format(price);
};

async function copyValue(label, value) {
    try {
        await navigator.clipboard.writeText(value);
        copied.value = label;
        setTimeout(() => {
            if (copied.value === label) copied.value = '';
        }, 2000);
    } catch {
        // Clipboard unavailable; no-op.
    }
}

function confirmOffline() {
    offlineSubmitting.value = true;
    router.post(`/portal/enroll/${props.training.id}`, { mode: 'offline' }, {
        onFinish: () => { offlineSubmitting.value = false; },
    });
}
</script>

<template>
    <Head>
        <meta name="robots" content="noindex, nofollow" />
    </Head>
    <FlashMessages />
    <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
        <Link
            :href="`/training/${training.id}`"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to {{ training.title }}
        </Link>

        <div class="mt-4">
            <h1 class="text-2xl font-black text-slate-900">Pay by Bank Transfer</h1>
            <p class="mt-1 text-sm text-slate-500">Envoy Academy · {{ training.title }}</p>
        </div>

        <!-- Program summary -->
        <div class="mt-6 flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <img
                :src="training.image_path || '/images/landing/solar_installation.jpg'"
                :alt="training.title"
                class="h-20 w-20 shrink-0 rounded-xl object-cover"
            />
            <div class="min-w-0">
                <p class="font-bold text-slate-900">{{ training.title }}</p>
                <p class="mt-0.5 text-xs text-slate-500">
                    {{ training.ref_id }}
                    <template v-if="training.duration_weeks"> · {{ training.duration_weeks }} {{ training.duration_weeks === 1 ? 'week' : 'weeks' }}</template>
                    <template v-if="training.start_date"> · starts {{ new Date(training.start_date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}</template>
                </p>
            </div>
        </div>

        <!-- Amount to pay -->
        <div class="mt-6 flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-[#FAF8F2] px-6 py-5">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Program fee</p>
                <p class="mt-0.5 text-3xl font-black text-slate-900">{{ formatPrice(training.price) }}</p>
            </div>
            <span class="rounded-lg bg-[#0D1527] px-3 py-1.5 font-mono text-xs font-bold text-yellow-400">{{ training.ref_id }}</span>
        </div>

        <!-- Bank details -->
        <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Bank account details</h2>
            <div class="mt-4 space-y-4">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-xs text-slate-500">Account name</p>
                        <p class="mt-0.5 font-bold text-slate-900">{{ accountName }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        @click="copyValue('name', accountName)"
                    >
                        {{ copied === 'name' ? 'Copied ✓' : 'Copy' }}
                    </button>
                </div>

                <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-xs text-slate-500">Account number</p>
                        <p class="mt-0.5 font-mono text-xl font-black tracking-widest text-slate-900">{{ accountNumber }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        @click="copyValue('number', accountNumber)"
                    >
                        {{ copied === 'number' ? 'Copied ✓' : 'Copy' }}
                    </button>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-slate-500">Bank</p>
                        <p class="mt-0.5 font-bold text-slate-900">{{ bankName }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        @click="copyValue('bank', bankName)"
                    >
                        {{ copied === 'bank' ? 'Copied ✓' : 'Copy' }}
                    </button>
                </div>
            </div>

            <p class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
                Use <strong>{{ training.ref_id }}</strong> as the transfer narration so we can match your payment quickly.
            </p>
        </div>

        <!-- WhatsApp proof CTA -->
        <div class="mt-6 rounded-2xl bg-[#0D1527] p-6 text-white">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-300">After you transfer</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-300">
                Send us your proof of payment on WhatsApp so we can verify your enrollment and confirm your seat within business hours.
            </p>
            <a
                :href="waProofLink"
                target="_blank"
                rel="noopener"
                class="mt-5 flex items-center justify-center gap-2.5 rounded-xl bg-[#25D366] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-green-500/20 transition hover:bg-[#1fb858]"
            >
                <i class="bi bi-whatsapp text-lg leading-none"></i>
                Send proof of payment on WhatsApp
            </a>
            <p class="mt-2 text-center text-xs text-slate-400">Attach your transfer screenshot and tap send.</p>
        </div>

        <div class="my-5 flex items-center gap-3 text-xs uppercase tracking-wide text-slate-400">
            <span class="h-px flex-1 bg-slate-200" />or<span class="h-px flex-1 bg-slate-200" />
        </div>

        <!-- I have transferred -->
        <button
            type="button"
            class="w-full rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 disabled:opacity-50"
            :disabled="offlineSubmitting"
            @click="confirmOffline"
        >
            {{ offlineSubmitting ? 'Confirming…' : 'I have already transferred' }}
        </button>
        <p class="mt-2 text-center text-xs text-slate-400">Your seat is reserved once you confirm — we verify the transfer before finalising enrollment.</p>
    </div>
</template>