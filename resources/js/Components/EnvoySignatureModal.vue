<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { trimCanvasToDataUrl } from '@/lib/trimCanvas';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    currentSignature: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const canvas = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
const lastPos = ref({ x: 0, y: 0 });
const signatureData = ref(props.currentSignature || '');
const isSaving = ref(false);
const saveSuccess = ref(false);

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
    hasDrawn.value = true;
    signatureData.value = canvas.value.toDataURL('image/png');
}

function stopDraw() {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    if (hasDrawn.value && canvas.value) {
        signatureData.value = trimCanvasToDataUrl(canvas.value);
    }
}

function clearPad() {
    if (canvas.value) {
        const ctx = canvas.value.getContext('2d');
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    }
    hasDrawn.value = false;
    signatureData.value = '';
}

function initPad() {
    hasDrawn.value = false;
    signatureData.value = props.currentSignature || '';
    nextTick(() => {
        if (canvas.value) {
            const ctx = canvas.value.getContext('2d');
            ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
            if (props.currentSignature && props.currentSignature.startsWith('data:image')) {
                const img = new Image();
                img.onload = () => {
                    ctx.drawImage(img, 0, 0);
                    hasDrawn.value = true;
                };
                img.src = props.currentSignature;
            }
        }
    });
}

async function saveSignature() {
    isSaving.value = true;
    saveSuccess.value = false;

    if (hasDrawn.value && canvas.value) {
        signatureData.value = trimCanvasToDataUrl(canvas.value);
    }

    try {
        const res = await window.axios.post('/admin/receipts/default-envoy-signature', {
            signature: signatureData.value || null,
        });

        if (res.data && res.data.success) {
            saveSuccess.value = true;
            emit('saved', signatureData.value);
            setTimeout(() => {
                saveSuccess.value = false;
                emit('update:modelValue', false);
            }, 800);
        }
    } catch (e) {
        console.error('Failed to save default Envoy signature', e);
        alert('Failed to save signature. Please try again.');
    } finally {
        isSaving.value = false;
    }
}

function close() {
    emit('update:modelValue', false);
}
</script>

<template>
    <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto animate-in fade-in duration-150"
        @click.self="close"
    >
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-lg">
                        <i class="bi bi-pen-fill"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-tight">
                            Official Envoy Electricals Signature
                        </h3>
                        <p class="text-xs text-slate-500">Default company signature on all cash receipts</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="close"
                    class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Info callout -->
            <div class="p-3 bg-sky-50/70 border border-sky-100 rounded-xl text-xs text-sky-900 flex items-start gap-2">
                <i class="bi bi-info-circle-fill text-sky-600 text-sm shrink-0 mt-0.5"></i>
                <div>
                    Draw the official authorized signature for <strong>Envoy Electricals</strong> below. Once saved, it will automatically appear in the <em>"For Envoy Electricals"</em> spot by default whenever a new cash receipt is generated.
                </div>
            </div>

            <!-- Signature Pad Canvas -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Company Signature Pad
                    </label>
                    <button
                        type="button"
                        @click="clearPad"
                        class="text-xs text-red-600 hover:text-red-700 hover:underline flex items-center gap-1"
                    >
                        <i class="bi bi-eraser-fill"></i>
                        Clear Pad
                    </button>
                </div>

                <div
                    class="relative border-2 border-dashed rounded-xl bg-slate-50/80 overflow-hidden transition"
                    :class="hasDrawn ? 'border-sky-400 bg-white' : 'border-slate-300'"
                >
                    <canvas
                        ref="canvas"
                        width="600"
                        height="140"
                        class="w-full touch-none cursor-crosshair block select-none"
                        style="height: 140px;"
                        @mousedown="startDraw"
                        @mousemove="draw"
                        @mouseup="stopDraw"
                        @mouseleave="stopDraw"
                        @touchstart="startDraw"
                        @touchmove="draw"
                        @touchend="stopDraw"
                    ></canvas>

                    <!-- Guide overlay -->
                    <div
                        v-if="!hasDrawn"
                        class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none select-none text-slate-400 text-center"
                    >
                        <i class="bi bi-pencil-fill text-2xl text-slate-300 mb-1"></i>
                        <span class="text-xs font-semibold text-slate-500">Draw Envoy authorized signature here</span>
                        <span class="text-[10px] text-slate-400">Finger, stylus, or mouse drag</span>
                    </div>

                    <!-- Baseline -->
                    <div class="absolute bottom-6 left-6 right-6 border-b border-slate-200 pointer-events-none"></div>
                </div>
            </div>

            <!-- Success notification -->
            <div
                v-if="saveSuccess"
                class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-semibold flex items-center justify-center gap-2"
            >
                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                Saved as default company signature!
            </div>

            <!-- Footer Buttons -->
            <div class="pt-2 flex items-center justify-between border-t border-slate-100">
                <button
                    type="button"
                    @click="close"
                    class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition"
                >
                    Cancel
                </button>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="clearPad"
                        class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Clear
                    </button>

                    <button
                        type="button"
                        @click="saveSignature"
                        :disabled="isSaving || (!hasDrawn && !signatureData)"
                        class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
                    >
                        <i v-if="isSaving" class="bi bi-arrow-repeat animate-spin"></i>
                        <i v-else class="bi bi-check2-circle text-sm"></i>
                        {{ isSaving ? 'Saving...' : 'Save as Default Signature' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>