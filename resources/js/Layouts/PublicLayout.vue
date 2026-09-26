<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { cartCount } from '../lib/cart';
import SiteFooter from '../Components/SiteFooter.vue';

const isMobileMenuOpen = ref(false);
const isScrolled = ref(false);

const whatsappNumber = '2348097089259';
const whatsapp = computed(() => whatsappNumber.replace(/\D/g, ''));
const whatsappUrl = computed(() => `https://wa.me/${whatsapp.value}`);

const navLinks = [
    { label: 'Home', href: '/' },
    { label: 'Buy Our Products', href: '/shop' },
    { label: 'Packages', href: '/packages' },
    { label: 'Free Calculator', href: '/calculator' },
    { label: 'About Us', href: '/about' },
    { label: 'Training', href: '/portal' },
];

// Track scroll for header shadow
if (typeof window !== 'undefined') {
    window.addEventListener('scroll', () => {
        isScrolled.value = window.scrollY > 10;
    });
}
function leaveImpersonation() {
    router.post(route('buyer.impersonation.leave'));
}
</script>

<template>
    <div class="min-h-screen bg-[#FAF8F2] flex flex-col">
        <!-- IMPERSONATION BANNER -->
        <div v-if="$page.props.impersonation?.active" class="bg-[#0D1527] text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="py-2.5 flex flex-wrap items-center justify-between gap-2">
                    <p class="flex items-center gap-2 text-xs sm:text-sm">
                        <i class="bi bi-incognito text-[#FACC15]"></i>
                        <span>Viewing as <strong>{{ $page.props.auth?.user?.name }}</strong><span class="text-slate-400"> · impersonated by {{ $page.props.impersonation.impersonator_name }}</span></span>
                    </p>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full bg-[#FACC15] px-3 py-1.5 text-xs font-bold text-[#0D1527] transition hover:bg-yellow-300" @click="leaveImpersonation">
                        <i class="bi bi-box-arrow-left"></i>
                        Return to admin
                    </button>
                </div>
            </div>
        </div>

        <!-- HEADER -->
        <header
            class="sticky top-0 left-0 right-0 z-40 border-b border-slate-200 bg-white transition-all duration-300"
            :class="isScrolled ? 'shadow-md' : 'shadow-sm'"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="h-16 sm:h-18 flex items-center justify-between gap-4">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center shrink-0" aria-label="Envoy Electricals Home">
                        <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-9 sm:h-10 w-auto object-contain" />
                    </Link>

                    <!-- Desktop Navigation -->
                    <nav class="hidden lg:flex items-center gap-1" aria-label="Main navigation">
                        <Link
                            v-for="l in navLinks"
                            :key="l.href"
                            :href="l.href"
                            class="relative px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-950 rounded-lg transition-colors duration-200 before:absolute before:bottom-0 before:left-1/2 before:w-0 before:h-[2px] before:bg-yellow-400 before:transition-all before:duration-300 hover:before:left-0 hover:before:w-full hover:before:-translate-x-1/2"
                        >
                            {{ l.label }}
                        </Link>
                        <Link
                            href="/contact"
                            class="relative px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-950 rounded-lg transition-colors duration-200 before:absolute before:bottom-0 before:left-1/2 before:w-0 before:h-[2px] before:bg-yellow-400 before:transition-all before:duration-300 hover:before:left-0 hover:before:w-full hover:before:-translate-x-1/2"
                        >
                            Contact
                        </Link>
                    </nav>

                    <!-- Desktop Actions -->
                    <div class="hidden lg:flex items-center gap-3 shrink-0">
                        <!-- "Let's talk" CTA Button -->
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-[#0D1527] text-white text-sm font-semibold transition-all duration-300 hover:bg-[#0D1527]/90 hover:shadow-lg hover:shadow-[#0D1527]/30 hover:-translate-y-0.5"
                        >
                            <i class="bi bi-whatsapp text-lg" />
                            Let's Talk
                        </a>

                        <!-- Cart -->
                        <Link
                            href="/cart"
                            title="Cart"
                            class="relative w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:border-yellow-400 hover:bg-yellow-50 hover:text-slate-950 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1" />
                                <circle cx="20" cy="21" r="1" />
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                            </svg>
                            <span v-if="cartCount > 0" class="absolute -top-1 -right-1 min-w-5 h-5 rounded-full bg-[#E4312B] text-white text-[10px] font-black flex items-center justify-center px-1">
                                {{ cartCount }}
                            </span>
                        </Link>

                        <!-- User/Dashboard or Login -->
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            title="Dashboard"
                            class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:border-yellow-400 hover:bg-yellow-50 hover:text-slate-950 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </Link>
                        <Link
                            v-else
                            :href="route('login')"
                            title="Sign In"
                            class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:border-yellow-400 hover:bg-yellow-50 hover:text-slate-950 transition-all duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </Link>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button
                        type="button"
                        class="lg:hidden w-10 h-10 flex flex-col justify-center items-end gap-1.5 p-2 group focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400 focus-visible:ring-offset-2 rounded-lg"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        aria-label="Toggle menu"
                        aria-expanded="false"
                    >
                        <span class="w-6 h-[2px] bg-slate-700 transition-all duration-300 group-hover:bg-yellow-400 origin-right"></span>
                        <span class="w-5 h-[2px] bg-slate-700 transition-all duration-300 group-hover:bg-yellow-400 origin-right"></span>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation (inline, slides down) -->
            <div v-show="isMobileMenuOpen" class="lg:hidden overflow-hidden transition-all duration-300 ease-out bg-white border-t border-slate-100 animate-slide-down">
                <div class="max-w-7xl mx-auto px-4 py-4 space-y-3">
                    <nav class="space-y-1" aria-label="Mobile navigation">
                        <Link
                            v-for="l in navLinks"
                            :key="l.href"
                            :href="l.href"
                            class="block px-3 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-950 rounded-lg transition-colors"
                            @click="isMobileMenuOpen = false"
                        >
                            {{ l.label }}
                        </Link>
                        <Link
                            href="/contact"
                            class="block px-3 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-950 rounded-lg transition-colors"
                            @click="isMobileMenuOpen = false"
                        >
                            Contact
                        </Link>
                    </nav>

                    <div class="pt-3 border-t border-slate-100 space-y-3">
                        <!-- WhatsApp CTA -->
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#0D1527] text-white font-semibold rounded-xl transition-all duration-300 hover:bg-[#0D1527]/90 hover:shadow-lg hover:shadow-[#0D1527]/30"
                        >
                            <i class="bi bi-whatsapp text-lg" />
                            Let's Talk on WhatsApp
                        </a>

                        <!-- Auth Links -->
                        <Link
                            v-if="!$page.props.auth?.user"
                            :href="route('login')"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 border border-slate-200 text-slate-700 font-semibold rounded-xl transition-all hover:bg-slate-50 hover:border-yellow-400"
                            @click="isMobileMenuOpen = false"
                        >
                            <i class="bi bi-box-arrow-in-right" />
                            Sign In / Staff Portal
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 border border-slate-200 text-slate-700 font-semibold rounded-xl transition-all hover:bg-slate-50 hover:border-yellow-400"
                            @click="isMobileMenuOpen = false"
                        >
                            <i class="bi bi-speedometer2" />
                            Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- FOOTER -->
        <SiteFooter estimate-href="/contact" />
    </div>
</template>

<style scoped>
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-down {
    animation: slideDown 0.3s ease-out forwards;
}
</style>