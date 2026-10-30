<script setup>
import { ref, onMounted, onBeforeUnmount, computed, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ReceiptVoucher from '@/Components/ReceiptVoucher.vue';
import { trimCanvasToDataUrl } from '@/lib/trimCanvas';

const props = defineProps({
    receipt: { type: Object, required: true },
});

// Step state: 1 = Sign First, 2 = Preview Receipt with Signature
const step = ref(1);

// ---- Canvas drawing state ----
const canvas = ref(null);
const isDrawing = ref(false);
const hasSigned = ref(false);
const lastPos = ref({ x: 0, y: 0 });
const signatureDataUrl = ref(null);
const rawCanvasDataUrl = ref(null);

// ---- Mobile fit / scale calculation ----
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);
const zoomMode = ref('fit'); // 'fit' or 'actual'

function handleResize() {
    if (typeof window !== 'undefined') {
        windowWidth.value = window.innerWidth;
    }
}

const scaleFactor = computed(() => {
    if (zoomMode.value === 'actual') return 1;
    // Leave 32px for mobile padding
    const maxW = Math.max(300, windowWidth.value - 32);
    if (maxW >= 1012) return 1;
    return Number((maxW / 1012).toFixed(3));
});

function getPos(e, el) {
    const rect = el.getBoundingClientRect();
    const src = e.touches ? e.touches[0] : e;
    return {
        x: (src.clientX - rect.left) * (el.width / rect.width),
        y: (src.clientY - rect.top) * (el.height / rect.height),
    };
}

function startDraw(e) {
    if (e.cancelable) e.preventDefault();
    isDrawing.value = true;
    lastPos.value = getPos(e, canvas.value);
}

function draw(e) {
    if (!isDrawing.value || !canvas.value) return;
    if (e.cancelable) e.preventDefault();
    const ctx = canvas.value.getContext('2d');
    const pos = getPos(e, canvas.value);
    ctx.strokeStyle = '#0a2456';
    ctx.lineWidth = 3.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    ctx.moveTo(lastPos.value.x, lastPos.value.y);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    lastPos.value = pos;
    hasSigned.value = true;
    rawCanvasDataUrl.value = canvas.value.toDataURL('image/png');
}

function stopDraw() {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    if (hasSigned.value && canvas.value) {
        rawCanvasDataUrl.value = canvas.value.toDataURL('image/png');
        signatureDataUrl.value = trimCanvasToDataUrl(canvas.value);
    }
}

function clearSignature() {
    if (canvas.value) {
        const ctx = canvas.value.getContext('2d');
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    }
    hasSigned.value = false;
    signatureDataUrl.value = null;
    rawCanvasDataUrl.value = null;
}

// Navigation between steps
function goToPreview() {
    if (!hasSigned.value || !canvas.value) return;
    signatureDataUrl.value = trimCanvasToDataUrl(canvas.value);
    step.value = 2;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function backToSigning() {
    step.value = 1;
    // When returning to Step 1, ensure canvas retains previous untrimmed drawing
    nextTick(() => {
        if (canvas.value && (rawCanvasDataUrl.value || signatureDataUrl.value)) {
            const ctx = canvas.value.getContext('2d');
            const img = new Image();
            img.onload = () => {
                ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
                ctx.drawImage(img, 0, 0);
            };
            img.src = rawCanvasDataUrl.value || signatureDataUrl.value;
        }
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ---- Submission ----
const isSubmitting = ref(false);
const submitted = ref(false);

function submit() {
    if (!signatureDataUrl.value) return;
    isSubmitting.value = true;

    router.post(`/receipt/${props.receipt.token}/sign`, {
        signature: signatureDataUrl.value,
    }, {
        onSuccess: () => {
            submitted.value = true;
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', handleResize);
        handleResize();
    }
    if (canvas.value) {
        const ctx = canvas.value.getContext('2d');
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    }
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', handleResize);
    }
});

const alreadySigned = computed(() =>
    props.receipt.customer_signature &&
    typeof props.receipt.customer_signature === 'string' &&
    props.receipt.customer_signature.startsWith('data:image')
);

// Preview object with the customer's drawn signature stamped on it
const receiptPreviewObj = computed(() => {
    return {
        ...props.receipt,
        customer_signature: signatureDataUrl.value || props.receipt.customer_signature,
    };
});

function formatDate(val) {
    if (!val) return '';
    return new Date(val).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatCurrency(val) {
    return '₦' + Number(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head :title="`Sign Official Receipt ${receipt.ref_id} - Envoy Electricals`" />

    <div class="min-h-screen bg-slate-100 flex flex-col justify-between py-6 px-3 sm:px-6">
        <div class="max-w-3xl mx-auto w-full space-y-5">

            <!-- Top Brand Bar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-9 w-auto object-contain" />
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 tracking-tight leading-tight">ENVOY ELECTRICALS</h1>
                        <p class="text-[11px] text-slate-500">Official Receipt Signing Portal</p>
                    </div>
                </div>
                <div class="text-right hidden sm:block">
                    <div class="text-xs font-mono font-bold text-slate-700">{{ receipt.ref_id }}</div>
                    <div class="text-[11px] text-emerald-600 font-medium">Secured Portal</div>
                </div>
            </div>

            <!-- Case 1: Receipt Already Signed -->
            <div v-if="alreadySigned" class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-6 sm:p-8 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center text-3xl text-emerald-600">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <h2 class="text-lg font-bold text-slate-900">Receipt Already Signed</h2>
                <p class="text-sm text-slate-600 max-w-md mx-auto">
                    This official cash receipt has already been signed by <strong class="text-slate-800">{{ receipt.customer_name }}</strong>.
                    <span v-if="receipt.signed_at" class="block text-xs text-slate-400 mt-1">
                        Signed on {{ formatDate(receipt.signed_at) }}.
                    </span>
                </p>
                <div class="pt-2">
                    <a
                        :href="`/receipt/${receipt.token}`"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-bold transition shadow-sm"
                    >
                        <i class="bi bi-receipt"></i>
                        View Official E-Receipt
                    </a>
                </div>
            </div>

            <!-- Case 2: Just Submitted Successfully -->
            <div v-else-if="submitted" class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-6 sm:p-8 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center text-3xl text-emerald-600 animate-bounce">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-900">Signature Confirmed & Recorded!</h2>
                <p class="text-sm text-slate-600 max-w-md mx-auto leading-relaxed">
                    Thank you, <strong class="text-slate-800">{{ receipt.customer_name }}</strong>! Your signature has been officially placed on your cash receipt voucher.
                </p>
                <div class="pt-3">
                    <a
                        :href="`/receipt/${receipt.token}`"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-bold transition shadow-md"
                    >
                        <i class="bi bi-receipt"></i>
                        View & Download Your Official E-Receipt
                    </a>
                </div>
            </div>

            <!-- Case 3: Active Multi-Step Signing Flow -->
            <div v-else class="space-y-5">
                <!-- Stepper Tabs -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-3 sm:p-4">
                    <div class="flex items-center justify-between max-w-md mx-auto">
                        <!-- Step 1 Button -->
                        <button
                            type="button"
                            @click="backToSigning"
                            class="flex items-center gap-2 text-left focus:outline-none"
                        >
                            <span
                                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition"
                                :class="step === 1
                                    ? 'bg-sky-600 text-white shadow-md ring-4 ring-sky-100'
                                    : 'bg-emerald-500 text-white'"
                            >
                                <i v-if="step > 1" class="bi bi-check-lg text-sm"></i>
                                <span v-else>1</span>
                            </span>
                            <div>
                                <div class="text-xs font-bold" :class="step === 1 ? 'text-sky-700' : 'text-slate-700'">
                                    1. Draw Signature
                                </div>
                                <div class="text-[10px] text-slate-400">Sign with finger/mouse</div>
                            </div>
                        </button>

                        <div
                            class="h-0.5 flex-1 mx-3 transition-colors duration-200"
                            :class="step > 1 ? 'bg-emerald-500' : 'bg-slate-200'"
                        ></div>

                        <!-- Step 2 Button -->
                        <button
                            type="button"
                            @click="goToPreview"
                            :disabled="!hasSigned"
                            class="flex items-center gap-2 text-left focus:outline-none disabled:opacity-50"
                        >
                            <span
                                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition"
                                :class="step === 2
                                    ? 'bg-sky-600 text-white shadow-md ring-4 ring-sky-100'
                                    : 'bg-slate-100 text-slate-500'"
                            >
                                2
                            </span>
                            <div>
                                <div class="text-xs font-bold" :class="step === 2 ? 'text-sky-700' : 'text-slate-500'">
                                    2. Review & Confirm
                                </div>
                                <div class="text-[10px] text-slate-400">Inspect voucher</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Receipt Context Summary Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="bi bi-receipt text-sky-600"></i>
                            Receipt Details
                        </div>
                        <span class="font-mono text-xs font-bold text-sky-700">{{ receipt.ref_id }}</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-3 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Received From</span>
                            <span class="font-bold text-slate-800">{{ receipt.customer_name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Amount</span>
                            <span class="font-bold text-emerald-700 text-sm">{{ formatCurrency(receipt.amount) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Date</span>
                            <span class="font-semibold text-slate-700">{{ formatDate(receipt.receipt_date) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Method</span>
                            <span class="font-semibold text-slate-700">{{ receipt.payment_method }}</span>
                        </div>
                    </div>

                    <div class="pt-2 text-xs border-t border-slate-50 mt-2">
                        <span class="text-slate-400 text-[11px]">Payment For: </span>
                        <span class="font-medium text-slate-700">{{ receipt.payment_for }}</span>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- STEP 1: SIGN FIRST (TOUCH-FRIENDLY CANVAS)                   -->
                <!-- ============================================================ -->
                <div v-show="step === 1" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i class="bi bi-pen-fill text-sky-600"></i>
                                Step 1: Sign in the Box Below
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Use your finger (on phone/tablet) or mouse to draw your signature.
                            </p>
                        </div>

                        <button
                            v-if="hasSigned"
                            type="button"
                            @click="clearSignature"
                            class="px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition flex items-center gap-1 shrink-0"
                        >
                            <i class="bi bi-eraser-fill"></i>
                            Clear & Cancel
                        </button>
                    </div>

                    <!-- Signature Pad Box -->
                    <div
                        class="relative border-2 border-dashed rounded-2xl bg-slate-50/70 overflow-hidden transition-colors"
                        :class="hasSigned ? 'border-sky-400 bg-white shadow-inner' : 'border-slate-300'"
                    >
                        <canvas
                            ref="canvas"
                            width="700"
                            height="180"
                            class="w-full touch-none cursor-crosshair block select-none"
                            style="height: 180px;"
                            @mousedown="startDraw"
                            @mousemove="draw"
                            @mouseup="stopDraw"
                            @mouseleave="stopDraw"
                            @touchstart="startDraw"
                            @touchmove="draw"
                            @touchend="stopDraw"
                        ></canvas>

                        <!-- Empty Placeholder Prompt -->
                        <div
                            v-if="!hasSigned"
                            class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none select-none text-slate-400 p-4 text-center"
                        >
                            <i class="bi bi-pencil-fill text-3xl mb-1 text-slate-300"></i>
                            <span class="text-sm font-semibold text-slate-500">Sign your name here</span>
                            <span class="text-xs text-slate-400">Touch and draw on your screen</span>
                        </div>

                        <!-- Baseline guide -->
                        <div class="absolute bottom-9 left-6 right-6 border-b-2 border-slate-200 pointer-events-none"></div>
                    </div>

                    <!-- Help text -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <i class="bi bi-info-circle text-sky-600"></i>
                            You can clear and redo your signature as many times as you like.
                        </span>
                        <span v-if="hasSigned" class="text-emerald-600 font-semibold flex items-center gap-1">
                            <i class="bi bi-check-circle-fill"></i> Signature captured
                        </span>
                    </div>

                    <!-- Step 1 Actions -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            @click="clearSignature"
                            :disabled="!hasSigned"
                            class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 disabled:opacity-40 rounded-xl transition flex items-center gap-1.5"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Reset
                        </button>

                        <button
                            type="button"
                            @click="goToPreview"
                            :disabled="!hasSigned"
                            class="flex-1 sm:flex-initial px-6 py-3 bg-sky-600 hover:bg-sky-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2"
                        >
                            <span>Next: Preview On Receipt</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- STEP 2: PREVIEW RECEIPT WITH SIGNATURE                       -->
                <!-- ============================================================ -->
                <div v-show="step === 2" class="space-y-4">
                    <!-- Instruction Card -->
                    <div class="bg-white rounded-2xl border border-sky-200 p-4 sm:p-5 shadow-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i class="bi bi-file-earmark-check-fill text-sky-600"></i>
                                Step 2: Review Your Signed Receipt
                            </h2>
                            <!-- Zoom toggle on mobile -->
                            <div v-if="scaleFactor < 1" class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
                                <button
                                    type="button"
                                    @click="zoomMode = 'fit'"
                                    class="px-2 py-0.5 text-[11px] font-semibold rounded"
                                    :class="zoomMode === 'fit' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'"
                                >
                                    Fit Screen
                                </button>
                                <button
                                    type="button"
                                    @click="zoomMode = 'actual'"
                                    class="px-2 py-0.5 text-[11px] font-semibold rounded"
                                    :class="zoomMode === 'actual' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'"
                                >
                                    100% Zoom
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-slate-600">
                            Check that your signature appears properly on the <strong>Customer's Signature</strong> spot. If you want to change it, tap <strong>Back / Redo</strong>. If you are satisfied, tap <strong>Confirm & Submit</strong>.
                        </p>
                    </div>

                    <!-- Receipt Voucher Container (Mobile Scaled) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-3 sm:p-5 overflow-hidden">
                        <div
                            class="w-full flex justify-center overflow-x-auto rounded-xl bg-slate-100/70 p-2 sm:p-4 border border-slate-200"
                        >
                            <div
                                :style="{
                                    transform: `scale(${scaleFactor})`,
                                    transformOrigin: 'top center',
                                    height: `${Math.round(438 * scaleFactor)}px`,
                                    marginBottom: scaleFactor < 1 ? '8px' : '0'
                                }"
                                class="transition-all duration-200"
                            >
                                <ReceiptVoucher :receipt="receiptPreviewObj" />
                            </div>
                        </div>

                        <p v-if="scaleFactor < 1 && zoomMode === 'fit'" class="text-[11px] text-slate-400 text-center mt-2">
                            Scaled to fit your mobile screen. Tap "100% Zoom" above to scroll full details.
                        </p>
                    </div>

                    <!-- Step 2 Action Buttons -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <!-- Back / Redo Button -->
                        <button
                            type="button"
                            @click="backToSigning"
                            class="w-full sm:w-auto px-5 py-3 border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl transition flex items-center justify-center gap-2"
                        >
                            <i class="bi bi-arrow-left"></i>
                            <span>Back / Redo Signature</span>
                        </button>

                        <!-- Confirm & Submit Button -->
                        <button
                            type="button"
                            @click="submit"
                            :disabled="isSubmitting"
                            class="w-full sm:w-auto px-8 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2"
                        >
                            <i v-if="isSubmitting" class="bi bi-arrow-repeat animate-spin text-base"></i>
                            <i v-else class="bi bi-check-circle-fill text-base"></i>
                            <span>{{ isSubmitting ? 'Submitting Signature...' : 'Confirm & Submit Receipt' }}</span>
                        </button>
                    </div>

                    <p class="text-center text-xs text-slate-400">
                        By confirming, you verify that you have received the goods/services listed and agree to this official electronic cash receipt.
                    </p>
                </div>
            </div>

            <!-- Portal Footer -->
            <div class="text-center text-xs text-slate-400 space-y-1 pt-2 pb-6">
                <div>Envoy Electricals &bull; RC: 7464589</div>
                <div>Shop 1, Peace Avenue Junction, FUTA South Gate Road, Akure, Ondo State</div>
                <div>Tel: 0809 708 9259, 0706 057 2282</div>
            </div>

        </div>
    </div>
</template>