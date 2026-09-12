<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const hasUser = !!usePage().props.auth?.user;
const particles = ref([]);
const mousePosition = ref({ x: 0, y: 0 });

const gridPattern = `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E")`;

// Generate floating particles
onMounted(() => {
    for (let i = 0; i < 30; i++) {
        particles.value.push({
            id: i,
            x: Math.random() * 100,
            y: Math.random() * 100,
            size: Math.random() * 4 + 1,
            speedX: (Math.random() - 0.5) * 0.3,
            speedY: (Math.random() - 0.5) * 0.3,
            opacity: Math.random() * 0.5 + 0.1,
            color: Math.random() > 0.5 ? 'yellow-400' : 'teal-400',
        });
    }

    // Animation loop
    const animate = () => {
        particles.value.forEach(p => {
            p.x += p.speedX;
            p.y += p.speedY;

            // Wrap around
            if (p.x > 100) p.x = 0;
            if (p.x < 0) p.x = 100;
            if (p.y > 100) p.y = 100;
            if (p.y < 0) p.y = 100;
        });
        requestAnimationFrame(animate);
    };
    animate();

    // Mouse parallax
    window.addEventListener('mousemove', (e) => {
        mousePosition.value.x = (e.clientX / window.innerWidth - 0.5) * 20;
        mousePosition.value.y = (e.clientY / window.innerHeight - 0.5) * 20;
    });
});
</script>

<template>
    <div class="min-h-screen bg-[#0D1527] font-sans antialiased relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0" aria-hidden="true">
            <!-- Large gradient orbs -->
            <div class="absolute -top-64 -right-64 h-128 w-128 rounded-full bg-yellow-400/10 blur-[150px] animate-pulse"></div>
            <div class="absolute -bottom-64 -left-64 h-128 w-128 rounded-full bg-[#40e0d0]/10 blur-[150px] animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-96 w-96 rounded-full bg-yellow-400/5 blur-[120px]"></div>

            <!-- Floating particles -->
            <div
                v-for="p in particles"
                :key="p.id"
                class="absolute rounded-full pointer-events-none transition-all duration-1000"
                :style="{
                    left: p.x + '%',
                    top: p.y + '%',
                    width: p.size + 'px',
                    height: p.size + 'px',
                    opacity: p.opacity,
                    transform: 'translate(' + mousePosition.x * (p.size / 4) + 'px, ' + mousePosition.y * (p.size / 4) + 'px)',
                }"
                :class="['bg-' + p.color]"
            ></div>

            <!-- Grid pattern overlay -->
            <div class="absolute inset-0 opacity-5" :style="{ backgroundImage: gridPattern }"></div>
        </div>

        <!-- Main Content -->
        <main class="relative min-h-screen flex items-center justify-center px-4 py-20">
            <div class="max-w-4xl w-full text-center">
                <!-- Error Code -->
                <div class="relative mb-8">
                    <!-- Glow rings -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="absolute h-72 w-72 rounded-full bg-yellow-400/20 blur-[100px] animate-pulse"></div>
                        <div class="absolute h-96 w-96 rounded-full bg-[#40e0d0]/10 blur-[120px] animate-pulse" style="animation-delay: 0.5s;"></div>
                    </div>

                    <div class="relative inline-flex items-center justify-center">
                        <!-- 404 Numbers -->
                        <div class="flex items-center gap-2 sm:gap-4 relative z-10">
                            <span class="text-8xl sm:text-9xl md:text-[12rem] font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-[#40e0d0] leading-none select-none">
                                4
                            </span>

                            <!-- Animated search icon in place of 0 -->
                            <div class="relative flex items-center justify-center">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="h-8xl w-8xl sm:h-9xl sm:w-9xl md:h-[12rem] md:w-[12rem] rounded-full border-4 border-yellow-400/30 animate-spin-slow"></div>
                                    <div class="h-8xl w-8xl sm:h-9xl sm:w-9xl md:h-[12rem] md:w-[12rem] rounded-full border-4 border-[#40e0d0]/30 animate-spin-slow-reverse"></div>
                                </div>
                                <div class="relative flex items-center justify-center h-8xl w-8xl sm:h-9xl sm:w-9xl md:h-[12rem] md:w-[12rem]">
                                    <svg class="w-1/2 h-1/2 text-yellow-400 drop-shadow-[0_0_20px_rgba(255,204,0,0.5)] animate-bounce-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                </div>
                            </div>

                            <span class="text-8xl sm:text-9xl md:text-[12rem] font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-[#40e0d0] leading-none select-none">
                                4
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <div class="mb-10">
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mb-4 leading-tight">
                        Page Not Found
                    </h1>
                    <p class="text-lg sm:text-xl text-slate-300 max-w-xl mx-auto leading-relaxed">
                        Looks like you've ventured into uncharted territory. The page you're looking for doesn't exist or has been moved to a different dimension.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                    <Link
                        href="/"
                        class="group inline-flex items-center gap-3 rounded-xl bg-yellow-400 px-8 py-4 text-base font-bold text-[#0D1527] hover:bg-yellow-300 transition-all shadow-xl hover:shadow-yellow-400/30"
                    >
                        <i class="bi bi-house-door-fill text-lg"></i>
                        <span>Back to Home</span>
                        <i class="bi bi-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </Link>

                    <Link
                        href="/training"
                        class="inline-flex items-center gap-3 rounded-xl border-2 border-white/20 px-8 py-4 text-base font-semibold text-white hover:bg-white/10 transition-all"
                    >
                        <i class="bi bi-mortarboard"></i>
                        <span>Browse Training</span>
                    </Link>
                </div>

                <!-- Helpful Links -->
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 backdrop-blur p-6 sm:p-8">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-6 flex items-center justify-center gap-2">
                        <i class="bi bi-compass text-yellow-400"></i>
                        Quick Navigation
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <Link href="/" class="group flex flex-col items-center gap-2 rounded-xl p-4 bg-slate-900/50 hover:bg-slate-800/50 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-yellow-400/10 flex items-center justify-center group-hover:bg-yellow-400/20 transition-colors">
                                <i class="bi bi-lightning-charge text-yellow-400 text-xl group-hover:scale-110 transition-transform"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors">Solar Packages</span>
                        </Link>

                        <Link href="/shop" class="group flex flex-col items-center gap-2 rounded-xl p-4 bg-slate-900/50 hover:bg-slate-800/50 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-teal-400/10 flex items-center justify-center group-hover:bg-teal-400/20 transition-colors">
                                <i class="bi bi-shop text-teal-400 text-xl group-hover:scale-110 transition-transform"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors">Shop Products</span>
                        </Link>

                        <Link href="/calculator" class="group flex flex-col items-center gap-2 rounded-xl p-4 bg-slate-900/50 hover:bg-slate-800/50 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-purple-400/10 flex items-center justify-center group-hover:bg-purple-400/20 transition-colors">
                                <i class="bi bi-calculator text-purple-400 text-xl group-hover:scale-110 transition-transform"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors">Calculator</span>
                        </Link>

                        <Link href="/contact" class="group flex flex-col items-center gap-2 rounded-xl p-4 bg-slate-900/50 hover:bg-slate-800/50 transition-colors">
                            <div class="w-12 h-12 rounded-xl bg-pink-400/10 flex items-center justify-center group-hover:bg-pink-400/20 transition-colors">
                                <i class="bi bi-envelope text-pink-400 text-xl group-hover:scale-110 transition-transform"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors">Contact Us</span>
                        </Link>
                    </div>
                </div>

                <!-- Fun fact / Easter egg -->
                <div class="mt-12 text-center">
                    <p class="text-xs text-slate-500">
                        <i class="bi bi-emoji-sunglasses text-yellow-400"></i>
                        Fun fact: 404 was the room number at CERN where the first web server was located.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="absolute bottom-0 left-0 right-0 border-t border-slate-800 px-4 py-6">
            <div class="mx-auto max-w-7xl flex flex-col items-center justify-between gap-3 text-xs text-slate-500 sm:flex-row">
                <p>© 2026 Envoy Electricals Ltd. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <Link href="/" class="hover:text-yellow-400 transition-colors">Website</Link>
                    <Link href="/portal" class="hover:text-yellow-400 transition-colors">Portal</Link>
                    <Link href="/training" class="hover:text-yellow-400 transition-colors">Training</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes spin-slow-reverse {
    from { transform: rotate(360deg); }
    to { transform: rotate(0deg); }
}

.animate-spin-slow {
    animation: spin-slow 20s linear infinite;
}

.animate-spin-slow-reverse {
    animation: spin-slow-reverse 25s linear infinite;
}

@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.animate-bounce-subtle {
    animation: bounce-subtle 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.05); }
}
</style>