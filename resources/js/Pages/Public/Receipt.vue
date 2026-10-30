<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import ReceiptVoucher from '@/Components/ReceiptVoucher.vue';

const props = defineProps({
    receipt: { type: Object, required: true },
});

function printReceipt() {
    window.print();
}

const shareUrl = computed(() => {
    return window.location.href;
});

const whatsappShareUrl = computed(() => {
    const text = `Official Cash Receipt from Envoy Electricals\nReceipt No: ${props.receipt.ref_id}\nAmount: ₦${Number(props.receipt.amount).toLocaleString('en-NG')}\nView: ${shareUrl.value}`;
    return `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
});

const isSigned = computed(() =>
    props.receipt.customer_signature &&
    props.receipt.customer_signature.startsWith('data:image')
);

const signUrl = computed(() => `/receipt/${props.receipt.token}/sign`);
</script>

<template>
    <Head :title="`Official Receipt ${receipt.ref_id} - Envoy Electricals`" />

    <div class="min-h-screen bg-slate-100 flex flex-col justify-between py-8 px-4 sm:px-6">
        <!-- Top Branded Bar -->
        <div class="max-w-5xl mx-auto w-full mb-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-10 w-auto object-contain" />
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">ENVOY ELECTRICALS</h2>
                        <div class="flex items-center gap-2 text-xs text-emerald-700 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Verified Official Cash Receipt
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 flex-wrap justify-center print:hidden">
                    <button
                        @click="printReceipt"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition"
                    >
                        <i class="bi bi-printer"></i>
                        Print
                    </button>

                    <a
                        :href="`/receipt/${receipt.token}/pdf`"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition shadow-xs"
                    >
                        <i class="bi bi-file-earmark-pdf"></i>
                        Download PDF
                    </a>

                    <a
                        :href="`/receipt/${receipt.token}/image`"
                        class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition shadow-xs"
                    >
                        <i class="bi bi-image"></i>
                        Download Image
                    </a>

                    <a
                        :href="whatsappShareUrl"
                        target="_blank"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition shadow-xs"
                    >
                        <i class="bi bi-whatsapp"></i>
                        Share
                    </a>
                </div>
            </div>
        </div>

        <!-- Receipt Voucher Container -->
        <div class="max-w-5xl mx-auto w-full my-auto flex flex-col items-center">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-4 sm:p-6 w-full flex justify-center overflow-x-auto">
                <ReceiptVoucher :receipt="receipt" />
            </div>

            <!-- Sign CTA — shown when not yet signed -->
            <div
                v-if="!isSigned"
                class="mt-4 w-full max-w-2xl bg-amber-50 border border-amber-200 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 print:hidden"
            >
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                        <i class="bi bi-pen-fill"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-amber-900">Signature Required</div>
                        <div class="text-xs text-amber-700">This receipt has not been signed yet. Tap below to sign electronically.</div>
                    </div>
                </div>
                <a
                    :href="signUrl"
                    class="shrink-0 inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold shadow transition"
                >
                    <i class="bi bi-pen-fill"></i>
                    Sign This Receipt
                </a>
            </div>

            <!-- Signed badge -->
            <div
                v-else
                class="mt-4 w-full max-w-2xl bg-emerald-50 border border-emerald-200 rounded-2xl p-3 flex items-center gap-3 print:hidden"
            >
                <i class="bi bi-patch-check-fill text-emerald-600 text-xl"></i>
                <div class="text-sm text-emerald-800 font-medium">
                    This receipt has been electronically signed by <strong>{{ receipt.customer_name }}</strong>.
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 mt-4 print:hidden">
                Official document issued by Envoy Electricals &bull; RC: 7464589 &bull; Powering your World with Innovative Solutions
            </p>
        </div>

        <!-- Public Footer -->
        <div class="max-w-5xl mx-auto w-full mt-8 text-center text-xs text-slate-500 space-y-1 print:hidden">
            <div>
                <strong>Envoy Electricals</strong> &bull; Shop 1, Peace Avenue Junction, FUTA South Gate Road, Akure, Ondo State.
            </div>
            <div>
                Tel: 0809 708 9259, 0706 057 2282 &bull; Support: envoyelectricals@gmail.com
            </div>
        </div>
    </div>
</template>
