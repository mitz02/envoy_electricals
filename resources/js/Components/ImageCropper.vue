<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Cropper, CircleStencil, RectangleStencil } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

const props = defineProps({
    imageSrc: { type: String, required: true },
    aspectRatio: { type: Number, default: 1 },
    circle: { type: Boolean, default: true },
    outputWidth: { type: Number, default: 600 },
    outputQuality: { type: Number, default: 0.88 },
});

const emit = defineEmits(['crop', 'cancel']);

const cropperRef = ref(null);
const isCircle = ref(props.circle ?? true);
const isLoading = ref(true);
const hasError = ref(false);

const stencilComponent = computed(() => {
    return isCircle.value ? CircleStencil : RectangleStencil;
});

const stencilProps = computed(() => {
    return {
        aspectRatio: isCircle.value ? 1 : props.aspectRatio,
        movable: true,
        resizable: true,
    };
});

function onReady() {
    isLoading.value = false;
    hasError.value = false;
}

function onError(e) {
    isLoading.value = false;
    hasError.value = true;
    console.error('Cropper image error:', e);
}

function zoomIn() {
    cropperRef.value?.zoom?.(1.25);
}

function zoomOut() {
    cropperRef.value?.zoom?.(0.8);
}

function rotateLeft() {
    cropperRef.value?.rotate?.(-90);
}

function rotateRight() {
    cropperRef.value?.rotate?.(90);
}

function flipHorizontal() {
    cropperRef.value?.flip?.(true, false);
}

function flipVertical() {
    cropperRef.value?.flip?.(false, true);
}

function resetCrop() {
    cropperRef.value?.reset?.();
}

function toggleShape() {
    isCircle.value = !isCircle.value;
}

function confirmCrop() {
    if (!cropperRef.value) return;

    try {
        const result = cropperRef.value.getResult();
        if (!result || !result.canvas) {
            console.error('No canvas available from cropper');
            return;
        }

        const sourceCanvas = result.canvas;
        const maxDim = props.outputWidth || 600;
        let targetWidth = sourceCanvas.width;
        let targetHeight = sourceCanvas.height;

        if (targetWidth > maxDim || targetHeight > maxDim) {
            if (targetWidth >= targetHeight) {
                targetHeight = Math.max(1, Math.round((targetHeight * maxDim) / targetWidth));
                targetWidth = maxDim;
            } else {
                targetWidth = Math.max(1, Math.round((targetWidth * maxDim) / targetHeight));
                targetHeight = maxDim;
            }
        }

        const outputCanvas = document.createElement('canvas');
        outputCanvas.width = targetWidth;
        outputCanvas.height = targetHeight;
        const ctx = outputCanvas.getContext('2d');
        if (!ctx) return;

        // Background fill to prevent transparent PNGs turning black when saved as JPEG
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, targetWidth, targetHeight);
        ctx.drawImage(sourceCanvas, 0, 0, targetWidth, targetHeight);

        outputCanvas.toBlob(
            (blob) => {
                if (blob) {
                    emit('crop', blob);
                } else {
                    console.error('Failed to create blob from canvas');
                }
            },
            'image/jpeg',
            props.outputQuality || 0.88
        );
    } catch (err) {
        console.error('Error during crop confirmation:', err);
    }
}

function cancelCrop() {
    emit('cancel');
}

function handleKeydown(e) {
    if (e.key === 'Escape') {
        cancelCrop();
    } else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        confirmCrop();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <div
        class="fixed inset-0 z-50 bg-slate-950/95 backdrop-blur-md flex flex-col h-screen select-none animate-fadeIn"
        role="dialog"
        aria-modal="true"
        aria-label="Crop Photo"
    >
        <!-- Top Toolbar -->
        <header class="flex items-center justify-between px-3 sm:px-6 py-2.5 sm:py-3 bg-slate-900/90 border-b border-slate-800 text-white flex-shrink-0 z-10">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse hidden sm:inline-block"></span>
                <h3 class="text-xs sm:text-sm font-semibold tracking-wide truncate">Crop & Position Photo</h3>
                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-slate-800 text-amber-300 border border-amber-400/20 hidden md:inline-block">
                    {{ isCircle ? 'Circle Avatar' : 'Square Crop' }}
                </span>
            </div>

            <!-- Toolbar buttons -->
            <div class="flex items-center gap-1 sm:gap-1.5">
                <!-- Shape Toggle -->
                <button
                    type="button"
                    @click="toggleShape"
                    class="p-1.5 sm:p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white transition-colors"
                    :title="isCircle ? 'Switch to square crop' : 'Switch to circular crop'"
                    aria-label="Toggle crop shape"
                >
                    <svg v-if="isCircle" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                    </svg>
                </button>

                <div class="w-px h-5 bg-slate-700/60 mx-0.5 sm:mx-1"></div>

                <!-- Zoom Out -->
                <button
                    type="button"
                    @click="zoomOut"
                    class="p-1.5 sm:p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white transition-colors"
                    title="Zoom Out"
                    aria-label="Zoom out"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                </button>

                <!-- Zoom In -->
                <button
                    type="button"
                    @click="zoomIn"
                    class="p-1.5 sm:p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white transition-colors"
                    title="Zoom In"
                    aria-label="Zoom in"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                </button>

                <!-- Rotate Counter-Clockwise -->
                <button
                    type="button"
                    @click="rotateLeft"
                    class="p-1.5 sm:p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white transition-colors"
                    title="Rotate 90° Left"
                    aria-label="Rotate left"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                        <path d="M3 3v5h5" />
                    </svg>
                </button>

                <!-- Rotate Clockwise -->
                <button
                    type="button"
                    @click="rotateRight"
                    class="p-1.5 sm:p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white transition-colors"
                    title="Rotate 90° Right"
                    aria-label="Rotate right"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                        <path d="M21 3v5h-5" />
                    </svg>
                </button>

                <!-- Flip Horizontal -->
                <button
                    type="button"
                    @click="flipHorizontal"
                    class="p-1.5 sm:p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white transition-colors hidden sm:inline-flex"
                    title="Flip Horizontal"
                    aria-label="Flip horizontal"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m3 7 5 5-5 5V7z" />
                        <path d="m21 7-5 5 5 5V7z" />
                        <line x1="12" y1="20" x2="12" y2="4" stroke-dasharray="2 2" />
                    </svg>
                </button>

                <!-- Reset -->
                <button
                    type="button"
                    @click="resetCrop"
                    class="p-1.5 sm:p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white transition-colors"
                    title="Reset to default"
                    aria-label="Reset"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>

                <div class="w-px h-5 bg-slate-700/60 mx-0.5 sm:mx-1"></div>

                <!-- Close / Cancel -->
                <button
                    type="button"
                    @click="cancelCrop"
                    class="p-1.5 sm:p-2 rounded-lg bg-slate-800/80 hover:bg-red-950 text-slate-300 hover:text-red-400 transition-colors"
                    title="Cancel (Esc)"
                    aria-label="Close"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Main Cropper Viewport -->
        <main class="flex-1 relative flex items-center justify-center overflow-hidden bg-slate-950 min-h-0">
            <!-- Loading Indicator -->
            <div
                v-if="isLoading && !hasError"
                class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/80 z-20"
            >
                <div class="w-10 h-10 border-4 border-amber-400 border-t-transparent rounded-full animate-spin mb-3"></div>
                <p class="text-sm font-medium text-slate-300">Loading photo...</p>
            </div>

            <!-- Error State -->
            <div
                v-if="hasError"
                class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950 p-6 z-20 text-center"
            >
                <div class="w-16 h-16 rounded-full bg-red-950/60 border border-red-800 text-red-400 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                </div>
                <h4 class="text-base font-semibold text-white mb-1">Failed to load photo</h4>
                <p class="text-sm text-slate-400 max-w-sm mb-6">The selected image file couldn't be opened. It may be corrupt or in an unsupported format.</p>
                <button
                    type="button"
                    @click="cancelCrop"
                    class="px-5 py-2 text-sm font-medium text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors"
                >
                    Choose Another Photo
                </button>
            </div>

            <!-- Cropper Component -->
            <Cropper
                ref="cropperRef"
                :src="imageSrc"
                :stencil-component="stencilComponent"
                :stencil-props="stencilProps"
                :image-restriction="'fit-area'"
                class="cropper-container"
                @ready="onReady"
                @error="onError"
            />
        </main>

        <!-- Footer -->
        <footer class="flex items-center justify-between gap-3 px-3 sm:px-6 py-3 bg-slate-900/90 border-t border-slate-800 flex-shrink-0 z-10">
            <div class="text-xs text-slate-400 hidden sm:flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9" />
                    <line x1="12" y1="16" x2="12" y2="12" />
                    <line x1="12" y1="8" x2="12.01" y2="8" />
                </svg>
                <span>Drag photo to position · Scroll or pinch to zoom</span>
            </div>

            <div class="flex items-center gap-2.5 ml-auto">
                <button
                    type="button"
                    @click="cancelCrop"
                    class="px-4 py-2 text-xs sm:text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg transition-colors"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="confirmCrop"
                    class="inline-flex items-center gap-1.5 px-5 py-2 text-xs sm:text-sm font-bold text-slate-950 bg-amber-400 hover:bg-amber-300 active:scale-95 rounded-lg shadow-lg shadow-amber-400/20 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    Apply & Use Photo
                </button>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.cropper-container {
    width: 100%;
    height: 100%;
    max-height: 100%;
    background: #020617;
}

:deep(.vue-advanced-cropper) {
    background: #020617;
    touch-action: none;
}

:deep(.vue-advanced-cropper__background),
:deep(.vue-advanced-cropper__foreground) {
    background: #020617;
}

:deep(.vue-simple-handler) {
    background: #FACC15;
    border-radius: 2px;
    box-shadow: 0 0 4px rgba(0, 0, 0, 0.6);
}

:deep(.vue-circle-stencil__preview) {
    border: 2px solid #FACC15;
    box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0.75);
}

:deep(.vue-rectangle-stencil) {
    border: 2px solid #FACC15;
    box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0.75);
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-fadeIn {
    animation: fadeIn 0.15s ease-out;
}
</style>
