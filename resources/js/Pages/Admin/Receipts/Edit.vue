<script setup>
import { ref, computed, nextTick } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import ReceiptVoucher from '@/Components/ReceiptVoucher.vue';
import EnvoySignatureModal from '@/Components/EnvoySignatureModal.vue';
import { numberToNairaWords } from '@/lib/numberToWords';
import { trimCanvasToDataUrl } from '@/lib/trimCanvas';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    receipt: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    defaultEnvoySignature: { type: String, default: '' },
});

const form = useForm({
    receipt_date: props.receipt.receipt_date ? props.receipt.receipt_date.split('T')[0] : '',
    customer_id: props.receipt.customer_id,
    customer_name: props.receipt.customer_name,
    customer_phone: props.receipt.customer_phone || '',
    customer_email: props.receipt.customer_email || '',
    amount: props.receipt.amount,
    amount_in_words: props.receipt.amount_in_words,
    payment_for: props.receipt.payment_for,
    payment_method: props.receipt.payment_method,
    customer_signature: props.receipt.customer_signature || '',
    envoy_signature: props.receipt.envoy_signature || props.defaultEnvoySignature || '',
    signatory_title: props.receipt.signatory_title || 'Authorized Signatory',
    notes: props.receipt.notes || '',
});

// Envoy signature modal
const envoySigModalOpen = ref(false);

function openEnvoySigModal() {
    envoySigModalOpen.value = true;
}

function onEnvoySigSaved(sig) {
    form.envoy_signature = sig;
}

function regenerateWords() {
    form.amount_in_words = numberToNairaWords(form.amount);
}

// ---- Customer Signature Editing ----
const isImageSig = computed(() => {
    return form.customer_signature &&
        typeof form.customer_signature === 'string' &&
        form.customer_signature.startsWith('data:image');
});

const isDrawingMode = ref(false);
const canvas = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
const lastPos = ref({ x: 0, y: 0 });

function getCanvasPos(e, el) {
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
    lastPos.value = getCanvasPos(e, canvas.value);
}

function draw(e) {
    if (!isDrawing.value || !canvas.value) return;
    if (e.cancelable) e.preventDefault();
    const ctx = canvas.value.getContext('2d');
    const pos = getCanvasPos(e, canvas.value);
    ctx.strokeStyle = '#0a2456';
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    ctx.moveTo(lastPos.value.x, lastPos.value.y);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    lastPos.value = pos;
    hasDrawn.value = true;
    form.customer_signature = canvas.value.toDataURL('image/png');
}

function stopDraw() {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    if (hasDrawn.value && canvas.value) {
        form.customer_signature = trimCanvasToDataUrl(canvas.value);
    }
}

function clearSignature() {
    if (canvas.value) {
        const ctx = canvas.value.getContext('2d');
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    }
    hasDrawn.value = false;
    form.customer_signature = '';
}

function openDrawPad() {
    isDrawingMode.value = true;
    nextTick(() => {
        if (canvas.value) {
            const ctx = canvas.value.getContext('2d');
            ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
            hasDrawn.value = false;
        }
    });
}

function closeDrawPad() {
    isDrawingMode.value = false;
}

function formatDate(val) {
    if (!val) return '';
    return new Date(val).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

// Quick presets for "payment for"
const commonPayments = [
    'Supply & Installation of Solar Inverter System',
    'Procurement of Deep Cycle Solar Batteries',
    'Supply of Monocrystalline Solar Panels',
    'CCTV Security Camera Installation & Cabling',
    'Electrical Installation & Maintenance Services',
    'Solar Generator Setup & Maintenance',
];

const previewReceipt = computed(() => {
    return {
        ref_id: props.receipt.ref_id,
        receipt_date: form.receipt_date,
        customer_name: form.customer_name,
        amount: form.amount,
        amount_in_words: form.amount_in_words,
        payment_for: form.payment_for,
        payment_method: form.payment_method,
        customer_signature: form.customer_signature,
        envoy_signature: form.envoy_signature,
        signatory_title: form.signatory_title,
    };
});

function submit() {
    if (isDrawingMode.value && hasDrawn.value && canvas.value) {
        form.customer_signature = trimCanvasToDataUrl(canvas.value);
    }
    form.put(`/admin/receipts/${props.receipt.id}`);
}
</script>

<template>
    <div class="space-y-6">
        <FlashMessages />

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                    <Link href="/admin/receipts" class="hover:text-slate-600 transition">Receipts</Link>
                    <span>/</span>
                    <span class="text-slate-700">Edit {{ receipt.ref_id }}</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="bi bi-pencil-square text-sky-600"></i>
                    Edit Receipt &mdash; {{ receipt.ref_id }}
                </h1>
            </div>
            <Link
                href="/admin/receipts"
                class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl transition flex items-center gap-1.5"
            >
                <i class="bi bi-arrow-left"></i>
                Back to List
            </Link>
        </div>

        <!-- Form and Live Preview Layout -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
            <!-- Left Form Column -->
            <div class="xl:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Date & Payment Method -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Receipt Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.receipt_date"
                                type="date"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.payment_method"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                            >
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="POS">POS / Card</option>
                                <option value="Online">Online / Web</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>

                    <!-- Customer -->
                    <div class="space-y-3 pt-2 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Customer Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.customer_name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Customer Phone
                                </label>
                                <input
                                    v-model="form.customer_phone"
                                    type="text"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Customer Email
                                </label>
                                <input
                                    v-model="form.customer_email"
                                    type="email"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Amount & In Words -->
                    <div class="space-y-3 pt-2 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Amount in Figures (₦) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400">₦</span>
                                <input
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    class="w-full pl-8 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-base font-bold focus:outline-none focus:ring-2 focus:ring-sky-500"
                                />
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-600">
                                    The Sum of (In Words) <span class="text-red-500">*</span>
                                </label>
                                <button
                                    type="button"
                                    @click="regenerateWords"
                                    class="text-[11px] text-sky-600 hover:underline flex items-center gap-1"
                                >
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Re-compute
                                </button>
                            </div>
                            <textarea
                                v-model="form.amount_in_words"
                                rows="2"
                                required
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Being Payment For -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Being Payment For <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.payment_for"
                            rows="2"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                        ></textarea>

                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <button
                                v-for="(item, idx) in commonPayments"
                                :key="idx"
                                type="button"
                                @click="form.payment_for = item"
                                class="px-2.5 py-1 text-[11px] bg-slate-100 hover:bg-sky-50 hover:text-sky-700 text-slate-600 rounded-md border border-slate-200/60 transition"
                            >
                                {{ item }}
                            </button>
                        </div>
                    </div>

                    <!-- Customer's Signature Editor -->
                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Customer's Signature
                            </label>
                            <span v-if="receipt.signed_at" class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                                <i class="bi bi-patch-check-fill"></i>
                                Signed {{ formatDate(receipt.signed_at) }}
                            </span>
                        </div>

                        <!-- Active Drawing Pad -->
                        <div v-if="isDrawingMode" class="space-y-2 p-3 bg-sky-50/60 rounded-xl border border-sky-200">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-sky-900 flex items-center gap-1">
                                    <i class="bi bi-pen-fill text-sky-600"></i>
                                    Draw New Signature
                                </span>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="clearSignature"
                                        class="text-xs text-red-600 hover:text-red-700 hover:underline flex items-center gap-1"
                                    >
                                        <i class="bi bi-eraser-fill"></i>
                                        Clear
                                    </button>
                                    <button
                                        type="button"
                                        @click="closeDrawPad"
                                        class="text-xs font-semibold text-slate-700 bg-white px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 transition"
                                    >
                                        Done
                                    </button>
                                </div>
                            </div>

                            <div class="relative border-2 border-dashed border-slate-300 rounded-xl bg-white overflow-hidden">
                                <canvas
                                    ref="canvas"
                                    width="600"
                                    height="120"
                                    class="w-full touch-none cursor-crosshair block"
                                    style="height: 120px;"
                                    @mousedown="startDraw"
                                    @mousemove="draw"
                                    @mouseup="stopDraw"
                                    @mouseleave="stopDraw"
                                    @touchstart="startDraw"
                                    @touchmove="draw"
                                    @touchend="stopDraw"
                                ></canvas>
                                <div
                                    v-if="!hasDrawn"
                                    class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none select-none text-slate-400"
                                >
                                    <i class="bi bi-pencil-fill text-xl mb-1 text-slate-300"></i>
                                    <span class="text-xs">Draw signature here</span>
                                </div>
                                <div class="absolute bottom-5 left-6 right-6 border-b border-slate-200 pointer-events-none"></div>
                            </div>
                        </div>

                        <!-- Display existing drawn signature image -->
                        <div v-else-if="isImageSig" class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-32 bg-white rounded-lg border border-slate-200 flex items-center justify-center p-1 overflow-hidden">
                                    <img :src="form.customer_signature" alt="Signature" class="max-h-full max-w-full object-contain" />
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Electronic Drawn Signature</div>
                                    <div class="text-[10px] text-slate-400">Captured on canvas</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="openDrawPad"
                                    class="px-2.5 py-1.5 text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 rounded-lg transition flex items-center gap-1"
                                    title="Redraw signature"
                                >
                                    <i class="bi bi-pen"></i>
                                    Redraw
                                </button>
                                <button
                                    type="button"
                                    @click="form.customer_signature = ''"
                                    class="px-2.5 py-1.5 text-xs font-semibold bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition flex items-center gap-1"
                                    title="Remove signature"
                                >
                                    <i class="bi bi-trash"></i>
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Text or empty signature input with Draw button -->
                        <div v-else class="space-y-2">
                            <div class="flex gap-2">
                                <input
                                    v-model="form.customer_signature"
                                    type="text"
                                    placeholder="Customer name/note or draw on pad ->"
                                    class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                                />
                                <button
                                    type="button"
                                    @click="openDrawPad"
                                    class="px-3 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold flex items-center gap-1 shrink-0 shadow-xs transition"
                                >
                                    <i class="bi bi-pen-fill"></i>
                                    Draw Pad
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Envoy Authorized Signatory -->
                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                For Envoy Electricals &mdash; Official Signature
                            </label>
                            <button
                                type="button"
                                @click="openEnvoySigModal"
                                class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline flex items-center gap-1"
                            >
                                <i class="bi bi-pen-fill"></i>
                                {{ form.envoy_signature ? 'Edit Default Signature' : 'Create Default Signature' }}
                            </button>
                        </div>

                        <!-- Clickable signature card -->
                        <div
                            @click="openEnvoySigModal"
                            class="p-3 bg-slate-50 hover:bg-sky-50/50 rounded-xl border border-slate-200 hover:border-sky-300 transition cursor-pointer flex items-center justify-between gap-3 group"
                            title="Click to create or edit the general Envoy signature"
                        >
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-32 bg-white rounded-lg border border-slate-200 flex items-center justify-center p-1 overflow-hidden group-hover:border-sky-300">
                                    <img
                                        v-if="form.envoy_signature && form.envoy_signature.startsWith('data:image')"
                                        :src="form.envoy_signature"
                                        alt="Envoy Signature"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                    <span v-else-if="form.envoy_signature" class="font-bold italic text-sky-900 text-xs truncate">
                                        {{ form.envoy_signature }}
                                    </span>
                                    <span v-else class="text-[10px] text-slate-400 italic">No signature set</span>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <span>Official Envoy Signature</span>
                                        <span v-if="form.envoy_signature" class="text-[10px] font-semibold text-emerald-700 bg-emerald-100 px-1.5 py-0.2 rounded">Active</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400">Click to change or draw new signature</div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="px-2.5 py-1.5 bg-white group-hover:bg-sky-600 text-slate-700 group-hover:text-white rounded-lg border border-slate-200 group-hover:border-sky-600 text-xs font-semibold transition flex items-center gap-1 shadow-xs"
                            >
                                <i class="bi bi-pencil"></i>
                                {{ form.envoy_signature ? 'Edit' : 'Create' }}
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Signatory Title
                            </label>
                            <input
                                v-model="form.signatory_title"
                                type="text"
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                            />
                        </div>
                    </div>

                    <div class="pt-2 flex gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 px-5 py-3 bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold rounded-xl shadow-sm transition"
                        >
                            {{ form.processing ? 'Saving Changes...' : 'Update Receipt' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Live Preview -->
            <div class="xl:col-span-7 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4 sticky top-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
                            Live Receipt Preview
                        </h3>
                        <span class="text-xs text-slate-400">Updates as you edit</span>
                    </div>

                    <div class="bg-slate-100/80 p-3 rounded-xl border border-slate-200 flex justify-center overflow-x-auto">
                        <ReceiptVoucher 
                            :receipt="previewReceipt" 
                            :can-edit-envoy-sig="true"
                            @edit-envoy-signature="openEnvoySigModal"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Envoy Signature Modal -->
        <EnvoySignatureModal
            v-model="envoySigModalOpen"
            :current-signature="form.envoy_signature"
            @saved="onEnvoySigSaved"
        />
    </div>
</template>