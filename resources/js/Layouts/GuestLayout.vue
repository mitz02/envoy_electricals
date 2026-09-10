<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    eyebrow: { type: String, default: 'Envoy Customer Portal' },
    title: { type: String, default: 'Welcome' },
    titleAccent: { type: String, default: 'Back!' },
    subtitle: {
        type: String,
        default: 'Access your quotes, orders and account dashboard with ease.',
    },
    features: {
        type: Array,
        default: () => [
            { icon: 'M6 7h12l-1 13H7L6 7zM9 7a3 3 0 0 1 6 0', label: 'Track your orders &amp; quotes' },
            { icon: 'M14 3v5h5M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8l-5-5z', label: 'Download invoices &amp; quotations' },
            { icon: 'M13 2 3 14h8l-1 8 10-12h-8l1-8z', label: 'Quick checkout — zero fuss' },
            { icon: 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0', label: 'Payment &amp; update notifications' },
        ],
    },
    footerText: { type: String, default: '© 2026 Envoy Electricals Ltd. All rights reserved.' },
});
</script>

<template>
    <div class="relative min-h-screen bg-[#FAF8F2] font-sans antialiased overflow-hidden">
        <!-- ===== Animated background ===== -->
        <div class="fixed inset-0 pointer-events-none" aria-hidden="true">
            <div
                class="absolute inset-0"
                style="background-image:linear-gradient(rgba(13,21,39,0.035) 1px, transparent 1px), linear-gradient(90deg, rgba(13,21,39,0.035) 1px, transparent 1px), radial-gradient(rgba(13,21,39,0.06) 1.5px, transparent 1.5px); background-size:54px 54px, 54px 54px, 27px 27px; background-position:0 0, 0 0, 13.5px 13.5px;"
            ></div>
            <div class="auth-orb auth-orb-1"></div>
            <div class="auth-orb auth-orb-2"></div>
            <div class="auth-orb auth-orb-3"></div>
        </div>

        <!-- ===== Card ===== -->
        <div class="relative z-10 min-h-screen flex items-center justify-center px-4 sm:px-6 py-12">
            <div class="w-full max-w-5xl bg-white rounded-3xl overflow-hidden shadow-[0_24px_60px_rgba(13,21,39,0.14)] border border-slate-100 grid lg:grid-cols-2">
                <!-- Left brand panel -->
                <div class="relative hidden lg:flex flex-col justify-between bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] p-10 xl:p-12 overflow-hidden">
                    <div class="absolute inset-0" aria-hidden="true">
                        <div class="absolute inset-0" style="background-image:radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px); background-size:28px 28px;"></div>
                        <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-yellow-400/[0.10] blur-[100px]"></div>
                        <div class="absolute -bottom-24 -left-16 w-72 h-72 rounded-full bg-[#40e0d0]/[0.08] blur-[100px]"></div>
                        <div class="absolute top-1/3 left-6 w-40 h-40 rounded-full bg-white/[0.04] blur-[70px]"></div>
                    </div>

                    <!-- Logo -->
                    <Link href="/" class="relative z-10 flex items-center gap-3">
                        <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-10 w-auto object-contain bg-white rounded-lg p-1" />
                    </Link>

                    <!-- Brand message -->
                    <div class="relative z-10 py-12">
                        <span class="inline-flex items-center gap-2 text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em] mb-6">
                            <span class="w-8 h-px bg-[#40e0d0]"></span>
                            {{ eyebrow }}
                        </span>

                        <!-- Sunrise beam ornament (echoes home hero) -->
                        <div class="flex items-center gap-3 mb-6">
                            <span class="h-px w-10 bg-gradient-to-r from-transparent via-[#FACC15]/60 to-[#FACC15]"></span>
                            <span class="relative flex h-8 w-8 items-center justify-center">
                                <span class="absolute inset-0 rounded-full bg-[#FACC15]/20 blur-md"></span>
                                <svg class="relative h-6 w-6 text-[#FDE047] sun-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="4" fill="#FDE047" stroke="none" />
                                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" stroke-width="2.4" />
                                </svg>
                            </span>
                            <span class="h-px w-10 bg-gradient-to-l from-transparent via-[#40e0d0]/60 to-[#40e0d0]"></span>
                        </div>

                        <h2 class="text-[34px] xl:text-4xl font-black text-white leading-tight tracking-tight">
                            <span class="whitespace-pre-line">{{ title }}</span>
                            <span class="block mt-0.5 bg-gradient-to-r from-[#FACC15] via-[#40e0d0] to-[#FACC15] bg-clip-text text-transparent">{{ titleAccent }}</span>
                        </h2>

                        <!-- Gradient underline -->
                        <div class="flex items-center gap-2 mt-5">
                            <span class="h-1 w-16 rounded-full bg-gradient-to-r from-[#FACC15] to-[#40e0d0]"></span>
                            <span class="h-1 w-3 rounded-full bg-[#40e0d0]/70"></span>
                        </div>

                        <p class="mt-5 text-sm xl:text-[15px] text-slate-400 leading-relaxed max-w-sm">{{ subtitle }}</p>

                        <ul v-if="features.length" class="mt-8 space-y-3.5">
                            <li v-for="f in features" :key="f.label" class="flex items-center gap-3 text-sm text-white/85">
                                <span class="w-7 h-7 rounded-lg bg-[#40e0d0]/10 text-[#40e0d0] flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path :d="f.icon" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span v-html="f.label"></span>
                            </li>
                        </ul>
                    </div>

                    <p class="relative z-10 text-[11.5px] text-white/30">{{ footerText }}</p>
                </div>

                <!-- Right form panel -->
                <div class="relative bg-[#FAF8F2] p-7 sm:p-10 xl:p-12 flex flex-col justify-center">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes authOrbFloat {
    0% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(50px, 70px) scale(1.12); }
    100% { transform: translate(-30px, 35px) scale(0.95); }
}

@keyframes sunSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.sun-spin {
    animation: sunSpin 24s linear infinite;
}

.auth-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    opacity: 0.5;
    animation: authOrbFloat 20s ease-in-out infinite alternate;
}

.auth-orb-1 {
    width: 560px;
    height: 560px;
    background: radial-gradient(circle, rgba(250,204,21,0.28) 0%, transparent 70%);
    top: -160px;
    left: -120px;
    animation-duration: 22s;
}

.auth-orb-2 {
    width: 640px;
    height: 640px;
    background: radial-gradient(circle, rgba(64,224,208,0.22) 0%, transparent 70%);
    top: 30%;
    right: -200px;
    animation-duration: 26s;
    animation-delay: -5s;
}

.auth-orb-3 {
    width: 520px;
    height: 520px;
    background: radial-gradient(circle, rgba(13,21,39,0.22) 0%, transparent 70%);
    bottom: -120px;
    left: 15%;
    animation-duration: 24s;
    animation-delay: -10s;
}
</style>