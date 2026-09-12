<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { cartCount } from '../lib/cart';

const isMobileMenuOpen = ref(false);

const navLinks = [
    { label: 'Home', href: '/' },
    { label: 'Shop', href: '/shop' },
    { label: 'Solar Packages', href: '/packages' },
    { label: 'Free Calculator', href: '/calculator' },
    { label: 'About Us', href: '/about' },
    { label: 'Training', href: '/portal' },
];
</script>

<template>
    <div class="min-h-screen bg-[#FAF8F2]">
        <!-- HEADER -->
        <header class="sticky top-0 left-0 right-0 z-40 border-b border-slate-200 bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-18 flex items-center justify-between gap-4">
                <Link href="/" class="flex items-center shrink-0">
                    <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-9 sm:h-10 w-auto object-contain" />
                </Link>

                <nav class="hidden lg:flex items-center gap-7">
                    <Link
                        v-for="l in navLinks"
                        :key="l.href"
                        :href="l.href"
                        class="text-sm font-semibold text-slate-700 hover:text-slate-950 relative after:absolute after:left-0 after:-bottom-1.5 after:h-[2px] after:w-0 after:bg-yellow-400 hover:after:w-full after:transition-all after:duration-300 transition-colors"
                    >
                        {{ l.label }}
                    </Link>
                    <Link href="/contact" class="text-sm font-semibold text-slate-700 hover:text-slate-950 relative after:absolute after:left-0 after:-bottom-1.5 after:h-[2px] after:w-0 after:bg-yellow-400 hover:after:w-full after:transition-all after:duration-300 transition-colors">
                        Contact
                    </Link>
                </nav>

                <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                    <Link
                        href="/cart"
                        title="Cart"
                        class="relative w-10 h-10 rounded-full border border-slate-300 flex items-center justify-center text-slate-900 hover:border-yellow-400 hover:bg-yellow-400 hover:text-slate-950 transition-all duration-200"
                    >
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1" />
                            <circle cx="20" cy="21" r="1" />
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                        </svg>
                        <span v-if="cartCount > 0" class="absolute -top-1.5 -right-1.5 min-w-4 h-4 rounded-full bg-[#E4312B] text-white text-[10px] font-black flex items-center justify-center px-1">
                            {{ cartCount }}
                        </span>
                    </Link>

                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        title="Dashboard"
                        class="w-10 h-10 rounded-full border border-slate-300 flex items-center justify-center text-slate-900 hover:border-yellow-400 hover:bg-yellow-400 hover:text-slate-950 transition-all duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        title="Sign In"
                        class="w-10 h-10 rounded-full border border-slate-300 flex items-center justify-center text-slate-900 hover:border-yellow-400 hover:bg-yellow-400 hover:text-slate-950 transition-all duration-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </Link>

                    <button
                        type="button"
                        class="lg:hidden flex flex-col justify-between items-center gap-1.5 w-9 h-9 py-1.5 focus:outline-none"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                    >
                        <span class="w-7 h-[2.5px] bg-slate-900 rounded"></span>
                        <span class="w-7 h-[2.5px] bg-slate-900 rounded"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- MOBILE DRAWER -->
        <div v-if="isMobileMenuOpen" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex justify-end" @click.self="isMobileMenuOpen = false">
            <div class="w-full max-w-sm bg-[#0B132B] h-full p-8 flex flex-col justify-between shadow-2xl border-l border-white/10 overflow-y-auto">
                <div>
                    <div class="flex items-center justify-between pb-6 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-10 w-auto object-contain bg-white rounded-lg p-1" />
                        </div>
                        <button type="button" class="text-white/70 hover:text-white text-2xl font-bold p-1" @click="isMobileMenuOpen = false">✕</button>
                    </div>
                    <nav class="mt-8 space-y-4">
                        <Link v-for="l in navLinks" :key="l.href" :href="l.href" @click="isMobileMenuOpen = false" class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition">
                            {{ l.label }}
                        </Link>
                        <Link href="/about" @click="isMobileMenuOpen = false" class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition">About Us</Link>
                        <Link href="/contact" @click="isMobileMenuOpen = false" class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition">Contact</Link>
                    </nav>
                </div>
                <div class="text-xs text-white/50 leading-relaxed pt-6 border-t border-white/10">
                    <p class="font-semibold text-white/70">Let's build your green future.</p>
                    <p class="mt-1">Sales: +234 809 708 9259<br />heil@solar</p>
                </div>
            </div>
        </div>

        <!-- PAGE CONTENT -->
        <main>
            <slot />
        </main>

        <!-- FOOTER -->
        <footer class="relative bg-[#0D1527] text-white overflow-hidden">
            <div class="absolute -top-24 right-0 w-96 h-96 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-6 sm:px-12 pt-10 pb-14 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8">
                    <div>
                        <div class="flex items-center gap-3">
                            <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-10 w-auto object-contain bg-white rounded-lg p-1" />
                            <span class="font-extrabold text-xl tracking-tight text-white">Envoy Electricals</span>
                        </div>
                        <p class="text-sm text-slate-400 leading-relaxed mt-4 text-left">
                            Clean, dependable solar & electrical energy for homes, businesses, and the technicians of tomorrow.
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-yellow-400 mb-5">Quick Links</p>
                        <ul class="space-y-3.5 text-sm text-slate-400">
                            <li><Link href="/" class="hover:text-white transition">Home</Link></li>
                            <li><Link href="/shop" class="hover:text-white transition">Shop Solar Products</Link></li>
                            <li><Link href="/packages" class="hover:text-white transition">Solar Packages</Link></li>
                            <li><Link href="/calculator" class="hover:text-white transition">Savings Calculator</Link></li>
                            <li><Link href="/projects" class="hover:text-white transition">Our Projects</Link></li>
                            <li><Link href="/portal" class="hover:text-white transition">Training Portal</Link></li>
                            <li><Link href="/contact" class="hover:text-white transition">Contact</Link></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-yellow-400 mb-5">Our Services</p>
                        <ul class="space-y-3.5 text-sm text-slate-400">
                            <li>Solar Installation</li>
                            <li>Inverter & Battery Systems</li>
                            <li>Electrical Products Sales</li>
                            <li><Link href="/portal" class="hover:text-white transition">Technician Training</Link></li>
                            <li>Maintenance & Support</li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-yellow-400 mb-5">Contact</p>
                        <ul class="space-y-4 text-sm text-slate-400">
                            <li class="flex items-start gap-3">
                                <i class="bi bi-geo-alt text-yellow-400 mt-0.5"></i>
                                <span>Shop 1, Peace Avenue Junction,<br />opp Goddy Royal Hotel, Futa Southgate Road, Akure</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="bi bi-telephone text-yellow-400"></i>
                                <a href="tel:+2348097089259" class="hover:text-white transition">+234 809 708 9259</a>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="bi bi-envelope text-yellow-400"></i>
                                <a href="mailto:hello@envoyelectric.com" class="hover:text-white transition">hello@envoyelectric.com</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="mt-10 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                    <p>© {{ new Date().getFullYear() }} Envoy Electricals Ltd. All rights reserved.</p>
                    <p>Powered by the sun, built for reliability.</p>
                </div>
            </div>
        </footer>
    </div>
</template>