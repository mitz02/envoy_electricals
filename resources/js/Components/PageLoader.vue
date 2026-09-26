<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

const active = ref(false);
const longLoad = ref(false);
let longTimer = null;
let minTimer = null;

function start() {
    clearTimeout(longTimer);
    clearTimeout(minTimer);
    minTimer = null;
    active.value = true;
    longTimer = setTimeout(() => {
        longLoad.value = true;
    }, 350);
}

function finish() {
    clearTimeout(longTimer);
    clearTimeout(minTimer);
    minTimer = setTimeout(() => {
        active.value = false;
        longLoad.value = false;
    }, 160);
}

onMounted(() => {
    router.on('start', start);
    router.on('finish', finish);
});

onBeforeUnmount(() => {
    router.off('start', start);
    router.off('finish', finish);
    clearTimeout(longTimer);
    clearTimeout(minTimer);
});
</script>

<template>
    <!-- Top shimmer progress bar -->
    <transition
        enter-active-class="transition-opacity duration-150"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200"
        leave-to-class="opacity-0"
    >
        <div v-if="active" class="pointer-events-none fixed inset-x-0 top-0 z-[100] h-[3px] overflow-hidden loader-shell">
            <div class="loader-bar h-full w-full" />
        </div>
    </transition>

    <!-- Branded overlay for slower navigations -->
    <transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200"
        leave-to-class="opacity-0"
    >
        <div
            v-if="active && longLoad"
            class="fixed inset-0 z-[99] flex flex-col items-center justify-center gap-7 bg-[#FAF8F2]/85 backdrop-blur-sm"
        >
            <!-- Aurora comet loader -->
            <div class="relative h-28 w-28">
                <!-- Soft centre glow -->
                <span class="orb-glow absolute inset-4 rounded-full"></span>

                <!-- Outer teal comet -->
                <span class="orb-comet absolute inset-0" style="--c: #40e0d0; --t: 6px"></span>

                <!-- Middle yellow comet (counter-rotating) -->
                <span class="orb-comet spin-reverse absolute inset-2" style="--c: #FACC15; --t: 4px; animation-duration: 1.9s"></span>

                <!-- Inner navy arc -->
                <span class="orb-arc spin-slow absolute inset-[30px]" style="--c: #0D1527; --t: 3px; animation-duration: 4.5s; animation-direction: reverse"></span>

                <!-- Dashed accent ring -->
                <span class="absolute inset-[38px] rounded-full border-2 border-dashed border-[#0D1527]/10 spin-slow" style="animation-duration: 9s"></span>
            </div>

            <div class="flex flex-col items-center gap-3">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.4em] text-[#0D1527]/70">
                    Loading<span class="ellipsis"><span>.</span><span>.</span><span>.</span></span>
                </p>
                <div class="h-[2px] w-24 overflow-hidden rounded-full bg-[#0D1527]/10">
                    <div class="load-underline h-full w-1/2 rounded-full bg-gradient-to-r from-[#0D1527] via-[#40e0d0] to-[#FACC15]"></div>
                </div>
            </div>
        </div>
    </transition>
</template>

<style>
.loader-shell {
    background: rgba(13, 21, 39, 0.04);
    box-shadow: 0 1px 8px rgba(64, 224, 208, 0.45);
}

.loader-bar {
    background: linear-gradient(90deg, #FACC15 0%, #40e0d0 45%, #0D1527 80%, #FACC15 100%);
    background-size: 300% 100%;
    animation: envoy-loader-slide 1s linear infinite;
}

.loader-bar::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.75) 50%, transparent 100%);
    transform: translateX(-100%);
    animation: envoy-loader-shimmer 1.1s ease-in-out infinite;
}

@keyframes envoy-loader-slide {
    0% {
        background-position: 0% 0;
    }
    100% {
        background-position: 300% 0;
    }
}

@keyframes envoy-loader-shimmer {
    0% {
        transform: translateX(-100%);
    }
    55%,
    100% {
        transform: translateX(100%);
    }
}

/* ---- Orb loader animations ---- */
.spin-slow {
    animation-name: envoy-spin;
    animation-duration: 2.6s;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
}

.spin-reverse {
    animation-direction: reverse;
}

.orb-comet,
.orb-arc {
    border-radius: 50%;
    -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - var(--t)), #000 calc(100% - var(--t) + 2px));
    mask: radial-gradient(farthest-side, transparent calc(100% - var(--t)), #000 calc(100% - var(--t) + 2px));
}

.orb-comet {
    background: conic-gradient(
        from 0deg,
        transparent 0deg 150deg,
        color-mix(in srgb, var(--c) 8%, transparent) 185deg,
        color-mix(in srgb, var(--c) 45%, transparent) 205deg,
        var(--c) 215deg,
        #ffffff 219deg,
        transparent 226deg 360deg
    );
    animation-name: envoy-spin;
    animation-duration: 1.4s;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
    filter: drop-shadow(0 0 6px color-mix(in srgb, var(--c) 45%, transparent));
}

.orb-arc {
    background: conic-gradient(
        from 0deg,
        transparent 0deg 285deg,
        color-mix(in srgb, var(--c) 65%, transparent) 320deg,
        var(--c) 335deg,
        transparent 348deg 360deg
    );
    animation-name: envoy-spin;
    animation-duration: 1.4s;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
}

.orb-glow {
    background: radial-gradient(circle, color-mix(in srgb, #40e0d0 60%, #FACC15 40%) 0%, color-mix(in srgb, #40e0d0 22%, transparent) 55%, transparent 72%);
    filter: blur(8px);
    animation: envoy-glow 2.2s ease-in-out infinite;
}

@keyframes envoy-spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes envoy-glow {
    0%,
    100% {
        opacity: 0.5;
        transform: scale(0.82);
    }
    50% {
        opacity: 0.95;
        transform: scale(1.08);
    }
}

.load-underline {
    animation: envoy-sweep 1.6s ease-in-out infinite;
}

@keyframes envoy-sweep {
    0% {
        transform: translateX(-110%);
    }
    100% {
        transform: translateX(220%);
    }
}

.ellipsis span {
    display: inline-block;
    animation: envoy-blink 1.2s infinite;
}

.ellipsis span:nth-child(2) {
    animation-delay: 0.2s;
}

.ellipsis span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes envoy-blink {
    0%,
    100% {
        opacity: 0.2;
    }
    50% {
        opacity: 1;
    }
}
</style>