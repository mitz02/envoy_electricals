<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { cartCount } from '../lib/cart';

const isMobileMenuOpen = ref(false);
const isContactModalOpen = ref(false);
const contactForm = ref({ name: '', email: '', phone: '', message: '' });
const contactSubmitted = ref(false);
const contactSending = ref(false);

function submitContact() {
    if (!contactForm.value.name || !contactForm.value.email) return;
    contactSending.value = true;
    setTimeout(() => {
        contactSending.value = false;
        contactSubmitted.value = true;
        setTimeout(() => {
            contactSubmitted.value = false;
            isContactModalOpen.value = false;
            contactForm.value = { name: '', email: '', phone: '', message: '' };
        }, 2500);
    }, 900);
}

const values = [
    {
        icon: 'bi-shield-check',
        title: 'Integrity',
        text: 'We give straightforward advice, honest prices, and only recommend what you actually need.',
    },
    {
        icon: 'bi-patch-check',
        title: 'Quality First',
        text: 'Genuine tier-one equipment with full warranty — nothing less leaves our warehouse.',
    },
    {
        icon: 'bi-lightbulb',
        title: 'Innovation',
        text: 'We design modern, future-ready solar systems that grow with your energy needs.',
    },
    {
        icon: 'bi-recycle',
        title: 'Sustainability',
        text: 'Clean energy that protects the planet and lowers running costs for the long haul.',
    },
];

const processSteps = [
    { step: '01', icon: 'bi-person-raised-hand', title: 'Consultation & Survey', text: 'We listen to your power needs, inspect the site, and measure your exact usage.' },
    { step: '02', icon: 'bi-rulers', title: 'Custom Design', text: 'Our engineers size the right panels, inverters and batteries for your budget.' },
    { step: '03', icon: 'bi-tools', title: 'Professional Installation', text: 'Certified technicians install safely and neatly, on schedule and on budget.' },
    { step: '04', icon: 'bi-headset', title: 'Support & Monitoring', text: 'We stay on hand with warranty cover, maintenance, and system monitoring.' },
];

const team = [
    {
        photo: '/images/landing/engineers_rooftop.jpg',
        name: 'Engineers & Technicians',
        role: 'Certified installation crew',
        bio: 'Trained, safety-first crews handling rooftop, commercial and industrial installs.',
    },
    {
        photo: '/images/landing/engineer_solar.jpg',
        name: 'Design Team',
        role: 'System design & sizing',
        bio: 'Precision sizing of panels, inverters and batteries for every energy goal.',
    },
    {
        photo: '/images/landing/workflow_team.jpg',
        name: 'Projects & After-Sales',
        role: 'Delivery, support & training',
        bio: 'Smooth handovers, response-ready support, and hands-on technician training.',
    },
];

const stats = [
    { value: '500+', label: 'Projects Delivered' },
    { value: '25+', label: 'Engineers & Technicians' },
    { value: '10+', label: 'Years of Experience' },
    { value: '100%', label: 'Warranty-Backed Work' },
];

function observeReveal() {
    const els = document.querySelectorAll('.reveal');
    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal-visible');
                    io.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12 }
    );
    els.forEach((el) => io.observe(el));
}

onMounted(() => {
    observeReveal();
    document.addEventListener('keydown', onKeydown);
});
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
});

function onKeydown(e) {
    if (e.key === 'Escape') {
        isMobileMenuOpen.value = false;
        isContactModalOpen.value = false;
    }
}
</script>

<template>
    <Head title="About Us — Envoy Electricals" />

    <div class="min-h-screen bg-[#FAF8F2] text-slate-900 font-sans antialiased selection:bg-yellow-400 selection:text-slate-900">
        <!-- ===================== HEADER ===================== -->
        <header class="sticky top-0 left-0 right-0 z-40 border-b border-slate-200 bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-3 group">
                    <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-9 sm:h-10 w-auto object-contain" />
                </Link>

                <!-- Desktop Nav Links -->
                <nav class="hidden lg:flex items-center gap-8 absolute left-1/2 -translate-x-1/2">
                    <Link
                        href="/"
                        class="text-sm font-semibold text-slate-700 hover:text-slate-950 relative after:absolute after:left-0 after:-bottom-1.5 after:h-[2px] after:w-0 after:bg-yellow-400 hover:after:w-full after:transition-all after:duration-300 transition-colors"
                    >
                        Home
                    </Link>
                    <Link
                        href="/#shop"
                        class="text-sm font-semibold text-slate-700 hover:text-slate-950 relative after:absolute after:left-0 after:-bottom-1.5 after:h-[2px] after:w-0 after:bg-yellow-400 hover:after:w-full after:transition-all after:duration-300 transition-colors"
                    >
                        Shop
                    </Link>
                    <Link
                        href="/about"
                        class="text-sm font-bold text-slate-950 relative after:absolute after:left-0 after:-bottom-1.5 after:h-[2px] after:w-full after:bg-yellow-400"
                    >
                        About Us
                    </Link>
                    <Link
                        href="/contact"
                        class="text-sm font-semibold text-slate-700 hover:text-slate-950 relative after:absolute after:left-0 after:-bottom-1.5 after:h-[2px] after:w-0 after:bg-yellow-400 hover:after:w-full after:transition-all after:duration-300 transition-colors"
                    >
                        Contact
                    </Link>
                </nav>

                <!-- Desktop Actions -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <Link
                        href="/#shop"
                        title="View Cart"
                        class="relative w-10 h-10 rounded-full border border-slate-300 flex items-center justify-center text-slate-900 hover:border-yellow-400 hover:bg-yellow-400 hover:text-slate-950 transition-all duration-200"
                    >
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1" />
                            <circle cx="20" cy="21" r="1" />
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                        </svg>
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] rounded-full bg-[#E4312B] text-white text-[10px] font-black flex items-center justify-center px-1"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>

                    <button
                        type="button"
                        @click="isContactModalOpen = true"
                        class="hidden sm:inline-flex items-center justify-center px-6 py-2.5 rounded-full border border-slate-300 text-slate-900 font-semibold text-sm hover:bg-slate-950 hover:text-white hover:border-slate-950 transition-all duration-300"
                    >
                        Let's talk
                    </button>

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
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="lg:hidden w-9 h-7 flex flex-col justify-between items-center py-1 group focus:outline-none"
                        aria-label="Toggle menu"
                    >
                        <span class="w-7 h-[2.5px] bg-slate-900 transition-all duration-300 group-hover:bg-yellow-400"></span>
                        <span class="w-7 h-[2.5px] bg-slate-900 transition-all duration-300 group-hover:bg-yellow-400"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- ===================== MOBILE DRAWER ===================== -->
        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex justify-end transition-opacity duration-300"
            @click.self="isMobileMenuOpen = false"
        >
            <div class="w-full max-w-sm bg-[#0B132B] h-full p-8 flex flex-col justify-between shadow-2xl border-l border-white/10">
                <div>
                    <div class="flex items-center justify-between pb-6 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-10 w-auto object-contain bg-white rounded-lg p-1" />
                        </div>
                        <button
                            type="button"
                            @click="isMobileMenuOpen = false"
                            class="text-white/70 hover:text-white text-2xl font-bold p-1"
                        >
                            ✕
                        </button>
                    </div>

                    <nav class="mt-8 space-y-4">
                        <Link href="/" @click="isMobileMenuOpen = false" class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition">
                            Home
                        </Link>
                        <Link href="/#shop" @click="isMobileMenuOpen = false" class="flex items-center justify-between text-lg font-semibold text-white/90 hover:text-yellow-400 transition">
                            <span>Shop Products</span>
                            <span class="relative flex items-center justify-center">
                                <svg class="w-5 h-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1" />
                                    <circle cx="20" cy="21" r="1" />
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                                </svg>
                                <span
                                    v-if="cartCount > 0"
                                    class="absolute -top-2 -right-2 min-w-[18px] h-[18px] rounded-full bg-[#E4312B] text-white text-[10px] font-black flex items-center justify-center px-1"
                                >
                                    {{ cartCount }}
                                </span>
                            </span>
                        </Link>
                        <Link href="/about" @click="isMobileMenuOpen = false" class="block text-lg font-bold text-yellow-400 transition">
                            About Us
                        </Link>
                        <Link href="/contact" @click="isMobileMenuOpen = false" class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition">
                            Contact
                        </Link>
                        <button
                            type="button"
                            @click="isMobileMenuOpen = false; isContactModalOpen = true"
                            class="block w-full text-left text-lg font-semibold text-white/90 hover:text-yellow-400 transition pt-4 border-t border-white/10"
                        >
                            Consultation Request
                        </button>
                    </nav>
                </div>

                <div class="text-xs text-white/50 leading-relaxed">
                    <p class="font-semibold text-white/70 uppercase tracking-widest mb-2">Get in touch</p>
                    <p>+234 800 000 0000</p>
                    <p>hello@envoyelectricals.com</p>
                    <p class="mt-2">Lagos, Nigeria</p>
                </div>
            </div>
        </div>

        <!-- ===================== PAGE HERO ===================== -->
        <section class="relative overflow-hidden bg-[#0D1527]">
            <img
                src="/images/landing/solar_field_nature.jpg"
                alt="Solar site at Envoy Electricals"
                class="absolute inset-0 w-full h-full object-cover opacity-25"
            />
            <div class="absolute inset-0 bg-gradient-to-b from-[#0D1527]/80 via-[#0D1527]/60 to-[#0D1527]"></div>
            <div class="absolute -top-24 -right-20 w-80 h-80 rounded-full bg-yellow-400/20 blur-3xl pointer-events-none"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
                <nav class="flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-400 mb-6">
                    <Link href="/" class="hover:text-yellow-400 transition">Home</Link>
                    <span class="text-slate-600">/</span>
                    <span class="text-yellow-400">About Us</span>
                </nav>

                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 text-yellow-400 text-xs font-bold uppercase tracking-[0.3em] mb-5">
                        <span class="w-10 h-px bg-yellow-400"></span>
                        Who We Are
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black uppercase tracking-tight text-white leading-[1.05]">
                        Powering Progress,<br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-yellow-300">One Home At A Time</span>
                    </h1>
                    <p class="mt-6 text-slate-300 text-base sm:text-lg leading-relaxed max-w-2xl">
                        Envoy Electricals is a solar energy and electrical company built on dependable service —
                        from roof-sit installations and retail supply to practical technician training across Nigeria.
                    </p>
                    <div class="mt-9 flex flex-wrap items-center gap-4">
                        <button
                            type="button"
                            @click="isContactModalOpen = true"
                            class="inline-flex items-center gap-2.5 bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-bold px-8 py-4 rounded-full transition-all duration-300 shadow-lg shadow-yellow-400/20"
                        >
                            Work With Us
                            <i class="bi bi-arrow-right"></i>
                        </button>
                        <Link
                            href="/contact"
                            class="inline-flex items-center gap-2.5 border border-white/25 text-white font-semibold px-8 py-4 rounded-full hover:bg-white hover:text-slate-950 transition-all duration-300"
                        >
                            Contact Us
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Bottom stats strip -->
            <div class="relative border-t border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-2 lg:grid-cols-4 gap-y-6 py-8 sm:py-10">
                    <div v-for="s in stats" :key="s.label" class="text-center lg:text-left px-4">
                        <p class="text-3xl sm:text-4xl font-black text-yellow-400">{{ s.value }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mt-1">{{ s.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== STORY ===================== -->
        <section class="relative py-20 sm:py-28 px-4 sm:px-6 lg:px-8 overflow-hidden">
            <div class="absolute -bottom-24 -left-16 w-72 h-72 rounded-full bg-yellow-300/20 blur-3xl pointer-events-none"></div>
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center relative">
                <div class="relative reveal">
                    <img
                        src="/images/landing/engineers_blueprint.jpg"
                        alt="Envoy Electricals engineers planning a solar project"
                        class="w-full h-[420px] sm:h-[520px] object-cover rounded-3xl shadow-2xl"
                    />
                    <div class="absolute -bottom-6 -right-4 sm:-right-6 bg-[#0D1527] text-white rounded-2xl shadow-2xl px-7 py-6 border-l-4 border-yellow-400">
                        <p class="text-4xl sm:text-5xl font-black text-yellow-400">10+</p>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-300 mt-1">Years of<br />Experience</p>
                    </div>
                    <div class="absolute top-6 left-6 flex items-center gap-2 bg-white/90 backdrop-blur rounded-full pl-2 pr-4 py-2 shadow-lg">
                        <span class="w-7 h-7 rounded-full bg-yellow-400 flex items-center justify-center">
                            <i class="bi bi-lightning-charge-fill text-slate-950 text-sm"></i>
                        </span>
                        <span class="text-xs font-bold text-slate-900">100% Clean Solar</span>
                    </div>
                </div>

                <div class="reveal">
                    <span class="inline-flex items-center gap-2 text-yellow-500 text-xs font-bold uppercase tracking-[0.3em] mb-4">
                        <span class="w-8 h-px bg-yellow-500"></span>
                        Our Story
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black uppercase tracking-tight text-slate-950 leading-tight">
                        Solar Built On<br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-yellow-400">Trust & Craftsmanship</span>
                    </h2>
                    <p class="mt-6 text-slate-600 text-sm sm:text-base leading-relaxed">
                        Envoy Electricals started with a simple conviction: reliable energy should be within everyone's
                        reach. Today we design, install and maintain solar systems for homes, businesses and institutions —
                        pairing genuine equipment with skilled hands and honest prices.
                    </p>
                    <p class="mt-4 text-slate-600 text-sm sm:text-base leading-relaxed">
                        Every project follows the same disciplined process — a thorough energy audit, precise system
                        design, certified installation and real after-sales care. That is how we keep growing: one
                        satisfied customer at a time.
                    </p>
                    <ul class="mt-7 space-y-3.5">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 w-6 h-6 rounded-full bg-yellow-400/20 text-yellow-600 flex items-center justify-center shrink-0">
                                <i class="bi bi-check-lg text-sm"></i>
                            </span>
                            <p class="text-sm text-slate-700">
                                <span class="font-bold text-slate-900">Residential, commercial & industrial</span> solar systems designed to last.
                            </p>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 w-6 h-6 rounded-full bg-yellow-400/20 text-yellow-600 flex items-center justify-center shrink-0">
                                <i class="bi bi-check-lg text-sm"></i>
                            </span>
                            <p class="text-sm text-slate-700">
                                <span class="font-bold text-slate-900">Genuine tier-one products</span> supplied with warranty and after-sales support.
                            </p>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 w-6 h-6 rounded-full bg-yellow-400/20 text-yellow-600 flex items-center justify-center shrink-0">
                                <i class="bi bi-check-lg text-sm"></i>
                            </span>
                            <p class="text-sm text-slate-700">
                                <span class="font-bold text-slate-900">Hands-on technician training</span> building the next generation of solar professionals.
                            </p>
                        </li>
                    </ul>

                    <div class="mt-8 flex flex-col sm:flex-row sm:items-center gap-4">
                        <Link
                            href="/contact"
                            class="inline-flex items-center gap-2.5 bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-7 py-3.5 rounded-full transition-all duration-300 shadow-md"
                        >
                            Learn More With Us
                            <span class="w-7 h-7 rounded-full bg-yellow-400 text-slate-950 flex items-center justify-center group-hover:translate-x-0.5 transition-transform">
                                <i class="bi bi-arrow-right text-sm"></i>
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== VALUES ===================== -->
        <section class="relative py-20 sm:py-28 px-4 sm:px-6 lg:px-8 bg-white overflow-hidden">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto reveal">
                    <span class="inline-flex items-center gap-2 text-yellow-500 text-xs font-bold uppercase tracking-[0.3em] mb-4">
                        <span class="w-8 h-px bg-yellow-500"></span>
                        What We Stand For
                        <span class="w-8 h-px bg-yellow-500"></span>
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black uppercase tracking-tight text-slate-950">Our Core Values</h2>
                    <p class="mt-4 text-slate-600 text-sm sm:text-base">
                        The principles that guide every panel we install and every promise we make.
                    </p>
                </div>

                <div class="mt-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="(v, i) in values"
                        :key="v.title"
                        class="reveal group relative bg-[#FAF8F2] rounded-3xl p-8 border border-slate-100 hover:border-yellow-400 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300"
                        :style="{ transitionDelay: `${i * 80}ms` }"
                    >
                        <span class="absolute top-0 right-6 text-6xl font-black text-slate-100 group-hover:text-yellow-400/20 transition-colors duration-300 leading-none">
                            {{ String(i + 1).padStart(2, '0') }}
                        </span>
                        <span class="relative w-14 h-14 rounded-2xl bg-[#0D1527] text-yellow-400 flex items-center justify-center mb-6 group-hover:bg-yellow-400 group-hover:text-slate-950 transition-all duration-300">
                            <i class="bi text-2xl" :class="v.icon"></i>
                        </span>
                        <h3 class="text-lg font-black text-slate-950 uppercase tracking-wide">{{ v.title }}</h3>
                        <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ v.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== PROCESS ===================== -->
        <section class="relative py-20 sm:py-28 px-4 sm:px-6 lg:px-8 bg-[#0D1527] overflow-hidden">
            <div class="absolute -top-24 -left-20 w-96 h-96 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>
            <div class="max-w-7xl mx-auto relative">
                <div class="text-center max-w-2xl mx-auto reveal">
                    <span class="inline-flex items-center gap-2 text-yellow-400 text-xs font-bold uppercase tracking-[0.3em] mb-4">
                        <span class="w-8 h-px bg-yellow-400"></span>
                        How We Work
                        <span class="w-8 h-px bg-yellow-400"></span>
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black uppercase tracking-tight text-white">A Proven 4-Step Process</h2>
                    <p class="mt-4 text-slate-400 text-sm sm:text-base">
                        From first call to final handover — clear, structured, and fully accountable.
                    </p>
                </div>

                <div class="mt-14 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="(p, i) in processSteps"
                        :key="p.step"
                        class="reveal group relative bg-white/5 backdrop-blur rounded-3xl p-8 border border-white/10 hover:bg-white/10 hover:border-yellow-400 transition-all duration-300"
                        :style="{ transitionDelay: `${i * 80}ms` }"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <span class="w-14 h-14 rounded-2xl bg-yellow-400 text-slate-950 flex items-center justify-center">
                                <i class="bi text-2xl" :class="p.icon"></i>
                            </span>
                            <span class="text-5xl font-black text-white/10 group-hover:text-yellow-400/30 transition-colors">{{ p.step }}</span>
                        </div>
                        <h3 class="text-lg font-extrabold text-white">{{ p.title }}</h3>
                        <p class="mt-3 text-sm text-slate-400 leading-relaxed">{{ p.text }}</p>
                        <span class="absolute bottom-0 left-8 right-8 h-[3px] bg-yellow-400 scale-x-0 group-hover:scale-x-100 origin-left transition-transform duration-300 rounded-t-full"></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== TEAM ===================== -->
        <section class="relative py-20 sm:py-28 px-4 sm:px-6 lg:px-8 overflow-hidden">
            <div class="absolute -bottom-24 -right-16 w-72 h-72 rounded-full bg-yellow-300/20 blur-3xl pointer-events-none"></div>
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 reveal">
                    <div class="max-w-xl">
                        <span class="inline-flex items-center gap-2 text-yellow-500 text-xs font-bold uppercase tracking-[0.3em] mb-4">
                            <span class="w-8 h-px bg-yellow-500"></span>
                            The Team Behind The Panels
                        </span>
                        <h2 class="text-3xl sm:text-5xl font-black uppercase tracking-tight text-slate-950 leading-tight">
                            The People Who Make It Happen
                        </h2>
                    </div>
                    <Link
                        href="/contact"
                        class="shrink-0 inline-flex items-center gap-2 text-slate-900 font-bold text-sm bg-yellow-400 hover:bg-yellow-300 px-6 py-3 rounded-full transition-colors"
                    >
                        Join Our Team
                        <i class="bi bi-arrow-up-right"></i>
                    </Link>
                </div>

                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="(m, i) in team"
                        :key="m.name"
                        class="reveal group relative rounded-3xl overflow-hidden bg-[#0D1527]"
                        :style="{ transitionDelay: `${i * 80}ms` }"
                    >
                        <div class="relative h-80 overflow-hidden">
                            <img
                                :src="m.photo"
                                :alt="m.name"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-[#0D1527] via-transparent to-transparent"></div>
                        </div>
                        <div class="relative p-6 -mt-16">
                            <h3 class="text-xl font-black text-white">{{ m.name }}</h3>
                            <p class="text-xs font-bold uppercase tracking-widest text-yellow-400 mt-1">{{ m.role }}</p>
                            <p class="mt-3 text-sm text-slate-400 leading-relaxed">{{ m.bio }}</p>
                            <span class="block mt-5 h-[3px] w-12 bg-yellow-400 rounded-full group-hover:w-full transition-all duration-500"></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== CTA BAND ===================== -->
        <section class="relative py-20 sm:py-24 px-4 sm:px-6 lg:px-8 bg-white overflow-hidden">
            <div class="max-w-5xl mx-auto">
                <div class="reveal relative bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] rounded-[2.5rem] px-8 sm:px-14 py-14 sm:py-20 text-center overflow-hidden shadow-2xl">
                    <div class="absolute -top-20 -right-16 w-64 h-64 rounded-full bg-yellow-400/20 blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-24 -left-16 w-64 h-64 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[120%] h-[2px] bg-yellow-400/10 rotate-[-2deg]"></div>

                    <span class="relative inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-400 mb-8">
                        <i class="bi bi-lightning-charge-fill text-slate-950 text-3xl"></i>
                    </span>
                    <h2 class="relative text-3xl sm:text-5xl font-black uppercase tracking-tight text-white leading-tight">
                        Ready To Cut Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-yellow-300">Energy Bills?</span>
                    </h2>
                    <p class="relative mt-4 text-slate-400 max-w-xl mx-auto text-sm sm:text-base">
                        Get a free solar assessment and honest advice from our engineers. No pressure — just straight answers.
                    </p>
                    <div class="relative mt-9 flex flex-wrap items-center justify-center gap-4">
                        <button
                            type="button"
                            @click="isContactModalOpen = true"
                            class="inline-flex items-center gap-2.5 bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-bold px-8 py-4 rounded-full transition-all duration-300 shadow-lg shadow-yellow-400/20"
                        >
                            Request Free Consultation
                            <i class="bi bi-arrow-right"></i>
                        </button>
                        <Link
                            href="/contact"
                            class="inline-flex items-center gap-2.5 border border-white/25 text-white font-semibold px-8 py-4 rounded-full hover:bg-white hover:text-slate-950 transition-all duration-300"
                        >
                            <i class="bi bi-telephone"></i>
                            Contact Sales
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== FOOTER ===================== -->
        <footer id="contact" class="relative bg-[#0D1527] text-white overflow-hidden">
            <div class="absolute -top-24 right-0 w-96 h-96 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-6 sm:px-12 pt-10 pb-14 relative z-10">
                <div class="flex justify-end pb-8">
                    <nav class="flex flex-wrap items-center gap-6 sm:gap-10 text-xs sm:text-sm font-medium text-slate-300">
                        <Link href="/" class="hover:text-white transition">Home</Link>
                        <Link href="/about" class="text-yellow-400">About</Link>
                        <Link href="/contact" class="hover:text-white transition">Contact</Link>
                        <Link href="/#shop" class="hover:text-white transition">Shop</Link>
                    </nav>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8 relative">
                    <div class="relative z-20">
                        <div class="flex items-center gap-3">
                            <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-10 w-auto object-contain bg-white rounded-lg p-1" />
                            <span class="font-extrabold text-xl tracking-tight">Envoy Electricals</span>
                        </div>
                        <p class="text-sm text-slate-400 leading-relaxed mt-4 text-left">
                            Clean, dependable solar & electrical energy for homes, businesses, and the technicians of tomorrow.
                        </p>
                        <div class="flex items-center gap-3 mt-5">
                            <a href="#" class="w-9 h-9 rounded-full border border-white/15 flex items-center justify-center text-slate-300 hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="w-9 h-9 rounded-full border border-white/15 flex items-center justify-center text-slate-300 hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="w-9 h-9 rounded-full border border-white/15 flex items-center justify-center text-slate-300 hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all"><i class="bi bi-twitter-x"></i></a>
                            <a href="#" class="w-9 h-9 rounded-full border border-white/15 flex items-center justify-center text-slate-300 hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all"><i class="bi bi-whatsapp"></i></a>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-yellow-400 mb-5">Quick Links</p>
                        <ul class="space-y-3.5 text-sm text-slate-400">
                            <li><Link href="/" class="hover:text-white transition">Home</Link></li>
                            <li><Link href="/#shop" class="hover:text-white transition">Shop Solar Products</Link></li>
                            <li><Link href="/about" class="hover:text-yellow-400 transition">About Us</Link></li>
                            <li><Link href="/contact" class="hover:text-white transition">Contact</Link></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-yellow-400 mb-5">Our Services</p>
                        <ul class="space-y-3.5 text-sm text-slate-400">
                            <li>Solar Installation</li>
                            <li>Inverter & Battery Systems</li>
                            <li>Electrical Products Sales</li>
                            <li>Technician Training</li>
                            <li>Maintenance & Support</li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-yellow-400 mb-5">Contact</p>
                        <ul class="space-y-4 text-sm text-slate-400">
                            <li class="flex items-start gap-3">
                                <i class="bi bi-geo-alt text-yellow-400 mt-0.5"></i>
                                <span>Victoria Island,<br />Lagos, Nigeria</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="bi bi-telephone text-yellow-400"></i>
                                <a href="tel:+2348000000000" class="hover:text-white transition">+234 800 000 0000</a>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="bi bi-envelope text-yellow-400"></i>
                                <a href="mailto:hello@envoyelectricals.com" class="hover:text-white transition">hello@envoyelectricals.com</a>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="bi bi-clock text-yellow-400"></i>
                                <span>Mon – Sat, 8am – 6pm</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10">
                <div class="max-w-7xl mx-auto px-6 sm:px-12 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                    <p>© 2026 Envoy Electricals. All rights reserved.</p>
                    <p class="flex items-center gap-1.5">
                        Powering homes with <i class="bi bi-sun text-yellow-400"></i> clean solar energy
                    </p>
                </div>
            </div>
        </footer>

        <!-- ===================== CONTACT MODAL ===================== -->
        <div
            v-if="isContactModalOpen"
            class="fixed inset-0 z-[60] bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4"
            @click.self="isContactModalOpen = false"
        >
            <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="bg-[#0D1527] px-7 py-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-white font-black text-xl uppercase">Free Consultation</h3>
                        <p class="text-slate-400 text-xs mt-0.5">We reply within 24 hours.</p>
                    </div>
                    <button
                        type="button"
                        @click="isContactModalOpen = false"
                        class="text-white/70 hover:text-white text-2xl font-bold p-1"
                    >
                        ✕
                    </button>
                </div>

                <form v-if="!contactSubmitted" @submit.prevent="submitContact" class="px-7 py-7 space-y-4">
                    <div>
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Full Name *</label>
                        <input
                            v-model="contactForm.name"
                            type="text"
                            required
                            placeholder="Your name"
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email *</label>
                        <input
                            v-model="contactForm.email"
                            type="email"
                            required
                            placeholder="you@email.com"
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Phone</label>
                        <input
                            v-model="contactForm.phone"
                            type="tel"
                            placeholder="+234 ..."
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                        />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Message</label>
                        <textarea
                            v-model="contactForm.message"
                            rows="3"
                            placeholder="Tell us about your project or power needs..."
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 resize-none"
                        ></textarea>
                    </div>
                    <button
                        type="submit"
                        :disabled="contactSending"
                        class="w-full inline-flex items-center justify-center gap-2.5 bg-yellow-400 hover:bg-yellow-300 disabled:opacity-60 text-slate-950 font-bold px-7 py-3.5 rounded-full transition-all duration-300"
                    >
                        <i v-if="contactSending" class="bi bi-arrow-repeat animate-spin"></i>
                        <span>{{ contactSending ? 'Sending...' : 'Request Consultation' }}</span>
                    </button>
                </form>

                <div v-else class="px-7 py-14 text-center">
                    <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-5">
                        <i class="bi bi-check-lg text-3xl"></i>
                    </span>
                    <h3 class="text-xl font-black text-slate-950 uppercase">Request Received!</h3>
                    <p class="mt-2 text-sm text-slate-500">Thank you — our team will get back to you within 24 hours.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    .reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .reveal-visible {
        opacity: 1;
        transform: none;
    }
</style>