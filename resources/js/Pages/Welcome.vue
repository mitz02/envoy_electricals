<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { products, currencyFormat, discountPercent, discountAmount } from '../data/products';
import { cartCount, bumpCart } from '../lib/cart';

const props = defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
    featuredProducts: {
        type: Array,
        default: () => [],
    },
});

// Featured products for the showcase grid (real products from the store)
const shopProducts = computed(() =>
    props.featuredProducts.length ? props.featuredProducts : products.slice(0, 8).map((p) => ({
        id: p.id,
        name: p.name,
        price: p.price,
        image: p.image,
        category: p.category,
    }))
);

// Navigation / Drawer state
const isMobileMenuOpen = ref(false);
const isContactModalOpen = ref(false);
const contactSuccess = ref(false);
const contactForm = ref({
    name: '',
    email: '',
    phone: '',
    service: 'Solar Panel Installation',
    message: '',
});

function submitContact() {
    contactSuccess.value = true;
    setTimeout(() => {
        contactSuccess.value = false;
        isContactModalOpen.value = false;
        contactForm.value = {
            name: '',
            email: '',
            phone: '',
            service: 'Solar Panel Installation',
            message: '',
        };
    }, 2000);
}

// Hero slider state
const activeHeroIndex = ref(0);
const heroDuration = 8000;
let heroTimer = null;
const heroSlides = [
    {
        badge: 'SUSTAINABLE ENERGY SOLUTIONS',
        titleLine1: 'POWERED BY',
        titleLine2: 'THE SUN',
        subtitle:
            'We design, install, and maintain high-performance solar systems that turn sunlight into reliable, affordable power — built to last for decades.',
        cta: { label: 'Explore Solutions', href: '#solutions' },
        image: '/images/landing/hero_solar_panels.jpg',
    },
    {
        badge: 'SMART SOLAR TECHNOLOGY',
        titleLine1: 'LIGHT UP',
        titleLine2: 'EVERYTHING',
        subtitle:
            "Smart inverters, sleek panels, and battery storage in perfect sync — clean energy so seamless you'll forget the grid ever existed.",
        cta: { label: 'Shop the Range', href: '#shop' },
        image: '/images/landing/solar_panels_sky.jpg',
    },
    {
        badge: 'ENGINEERED FOR EXCELLENCE',
        titleLine1: 'ENERGY',
        titleLine2: 'INDEPENDENCE',
        subtitle:
            'Go off-grid or stay hybrid — our lithium storage and solar systems cut electricity costs by up to 90% and keep you running through any outage.',
        cta: { label: 'Size My System', href: '/calculator' },
        image: '/images/landing/solar_farm_wide.jpg',
    },
];

function restartHeroTimer() {
    if (heroTimer) clearInterval(heroTimer);
    heroTimer = setInterval(() => {
        activeHeroIndex.value = (activeHeroIndex.value + 1) % heroSlides.length;
    }, heroDuration);
}

function pauseHeroTimer() {
    if (heroTimer) clearInterval(heroTimer);
}

function nextHero() {
    activeHeroIndex.value = (activeHeroIndex.value + 1) % heroSlides.length;
    restartHeroTimer();
}

function prevHero() {
    activeHeroIndex.value =
        (activeHeroIndex.value - 1 + heroSlides.length) % heroSlides.length;
    restartHeroTimer();
}

function goToHero(index) {
    activeHeroIndex.value = index;
    restartHeroTimer();
}

onMounted(restartHeroTimer);
onUnmounted(() => {
    if (heroTimer) clearInterval(heroTimer);
});

// Section 3: Solar Solutions carousel state
const activeSolutionIndex = ref(0);
const solutions = [
    {
        id: 1,
        title: 'Battery Storage Solution',
        description:
            'Advanced lithium-ion and deep-cycle battery storage systems engineered for uninterrupted backup power, peak shaving, and complete off-grid energy independence.',
        image: '/envoy_images/solutions-battery.jpg',
        icon: 'battery',
    },
    {
        id: 2,
        title: 'Solar Panel Installation',
        description:
            'Precision rooftop and ground-mounted solar panel installations utilizing Tier-1 monocrystalline modules for maximum sunlight absorption and decade-spanning durability.',
        image: '/envoy_images/solutions-installation.jpg',
        icon: 'solar',
    },
    {
        id: 3,
        title: 'Solar Inverter Installation',
        description:
            'High-efficiency hybrid, on-grid, and off-grid inverter setups configured by certified technicians to seamlessly regulate and convert DC solar power to AC electricity.',
        image: '/envoy_images/solutions-technician.jpg',
        icon: 'inverter',
    },
    {
        id: 4,
        title: 'Installation & Maintenance',
        description:
            'Comprehensive lifecycle support, preventative thermal inspections, performance tuning, and 24/7 technical assistance to keep your solar investment operating at peak output.',
        image: '/envoy_images/engineers-rooftop.jpg',
        icon: 'service',
    },
];

function nextSolution() {
    activeSolutionIndex.value = (activeSolutionIndex.value + 1) % solutions.length;
}

function prevSolution() {
    activeSolutionIndex.value =
        (activeSolutionIndex.value - 1 + solutions.length) % solutions.length;
}

// Section 5: Gallery showcase state
const galleryFilters = ['All', 'Installation', 'Products', 'Projects', 'Training'];
const galleryImages = [
    { src: '/envoy_images/solutions-installation.jpg', caption: 'Rooftop Panel Installation', category: 'Installation', tall: true },
    { src: '/envoy_images/solar-panels-sky.jpg', caption: 'Residential Solar Array', category: 'Installation', tall: false },
    { src: '/envoy_images/solutions-technician.jpg', caption: 'Precision Mounting Works', category: 'Installation', tall: false },
    { src: '/envoy_images/engineers-rooftop.jpg', caption: 'Quality Inspection on Site', category: 'Installation', tall: true },
    { src: '/envoy_images/gallery-inverter.jpg', caption: 'Smart Inverter Lineup', category: 'Products', tall: false },
    { src: '/envoy_images/solutions-battery.jpg', caption: 'Battery Storage Systems', category: 'Products', tall: true },
    { src: '/envoy_images/solar-city-rooftop.jpg', caption: 'Commercial Rooftop Project', category: 'Projects', tall: false },
    { src: '/envoy_images/gallery-solar-farm.jpg', caption: 'Utility-Scale Solar Farm', category: 'Projects', tall: true },
    { src: '/envoy_images/gallery-solar-field.jpg', caption: 'Solar Meets Nature', category: 'Projects', tall: false },
    { src: '/envoy_images/workflow-team.jpg', caption: 'Hands-on Installation Training', category: 'Training', tall: false },
];
const galleryFilter = ref('All');
const filteredGallery = computed(() =>
    galleryFilter.value === 'All'
        ? galleryImages
        : galleryImages.filter((img) => img.category === galleryFilter.value)
);
const isGalleryOpen = ref(false);
const galleryIndex = ref(0);
function openGallery(index) {
    galleryIndex.value = index;
    isGalleryOpen.value = true;
}
function closeGallery() {
    isGalleryOpen.value = false;
}
function galleryNext() {
    galleryIndex.value = (galleryIndex.value + 1) % filteredGallery.value.length;
}
function galleryPrev() {
    galleryIndex.value =
        (galleryIndex.value - 1 + filteredGallery.value.length) % filteredGallery.value.length;
}

// Section: Solar Products catalog (shared mock data)
// `products`, `currencyFormat` imported from data/products.js
const cartToastVisible = ref(false);
const cartToastName = ref('');
let cartToastTimer = null;
function addToCart(product) {
    bumpCart();
    cartToastName.value = product.name;
    cartToastVisible.value = true;
    clearTimeout(cartToastTimer);
    cartToastTimer = setTimeout(() => {
        cartToastVisible.value = false;
    }, 2600);
}

const formatNaira = currencyFormat;
const productDiscountPercent = discountPercent;
const productDiscountAmount = discountAmount;

const activeProductCategory = ref('Services');
const filteredProducts = computed(() => {
    if (activeProductCategory.value === 'All') return products;
    return products.filter((p) => p.category === activeProductCategory.value);
});

// Newsletter state
const newsletterEmail = ref('');
const newsletterSubscribed = ref(false);
function subscribeNewsletter() {
    if (newsletterEmail.value) {
        newsletterSubscribed.value = true;
        setTimeout(() => {
            newsletterSubscribed.value = false;
            newsletterEmail.value = '';
        }, 3000);
    }
}

// Workflow Process Steps (Image 1: 02, 03, 04, 01)
const workflowSteps = [
    {
        badge: '02',
        title: 'Initial Installation',
        description: 'We offer professional solar installation services .Best experts',
        image: '/envoy_images/gallery-solar-field.jpg',
    },
    {
        badge: '03',
        title: 'Quality Control',
        description: 'We offer professional solar installation services .Best experts',
        image: '/envoy_images/workflow-laptop.jpg',
    },
    {
        badge: '04',
        title: 'Repair & monitoring',
        description: 'We offer professional solar installation services .Best experts',
        image: '/envoy_images/workflow-tablet.jpg',
    },
    {
        badge: '01',
        title: 'Project Planning',
        description: 'We offer professional solar installation services .Best experts',
        image: '/envoy_images/workflow-team.jpg',
    },
];

// Free Estimate CTA state (Image 2)
const estimateInput = ref('');
function handleEstimateSubmit() {
    if (estimateInput.value) {
        contactForm.value.message = `Free Solar Estimate request: ${estimateInput.value}`;
    }
    isContactModalOpen.value = true;
}

// FAQ section state
const openFaq = ref(0);
const faqs = [
    {
        q: 'How much does a solar system cost?',
        a: 'It depends on your usage, system size, and equipment choice. A basic home backup starts from a modest budget, while full off-grid systems scale higher. Use our Load Calculator, then request a free consultation for a precise, no-obligation quote.',
    },
    {
        q: 'How long does installation take?',
        a: 'Most residential systems are installed and commissioned within 1–3 days after the site survey. Commercial and industrial projects follow a clear timeline shared with you before work begins.',
    },
    {
        q: 'Do I need a generator if I go solar?',
        a: 'With the right inverter, battery bank, and panel array, most homes run fully on solar during the day and stored power at night. Many of our customers keep a small generator only as an emergency backup.',
    },
    {
        q: 'Are your products genuinely warrantied?',
        a: 'Yes. We supply genuine tier-one panels, inverters and batteries backed by manufacturer warranties, and our installations carry a workmanship warranty. Warranty terms are included with every quote.',
    },
    {
        q: 'Do you train technicians?',
        a: 'Yes — we run practical, hands-on training programmes covering system design, sizing, safe installation, and troubleshooting, with certification on completion.',
    },
    {
        q: 'Can you maintain an existing solar system?',
        a: 'Absolutely. We offer inspection, cleaning, diagnostics, repairs and battery health checks for systems installed by us or any other provider.',
    },
];
</script>

<template>
    <Head title="Factorex Manufacturing — Clean & Sustainable Solar Energy Solutions" />

    <div class="min-h-screen bg-white text-slate-900 font-sans antialiased selection:bg-yellow-400 selection:text-slate-900">
        <!-- ========================================================= -->
        <!-- HEADER / NAVIGATION BAR (Exact Match to Screenshot) -->
        <!-- ========================================================= -->
        <header class="sticky top-0 left-0 right-0 z-40 border-b border-slate-200 bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
                <!-- Logo -->
                <a href="#hero" class="flex items-center gap-3 group">
                    <img src="/envoy_images/logo.png" alt="Envoy Electric" class="h-9 sm:h-10 w-auto object-contain drop-shadow" />
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-8">
                    <Link
                        :href="route('home')"
                        class="group relative py-1 text-sm font-semibold transition-colors duration-200"
                        :class="route().current('home') ? 'text-[#016cbb]' : 'text-slate-700 hover:text-slate-950'"
                    >
                        Home
                        <span
                            class="absolute left-0 -bottom-0.5 h-0.5 rounded-full bg-gradient-to-r from-[#FACC15] to-[#016cbb] transition-all duration-300"
                            :class="route().current('home') ? 'w-full' : 'w-0 group-hover:w-full'"
                        ></span>
                    </Link>
                    <Link
                        :href="route('about')"
                        class="group relative py-1 text-sm font-semibold transition-colors duration-200"
                        :class="route().current('about') ? 'text-[#016cbb]' : 'text-slate-700 hover:text-slate-950'"
                    >
                        About
                        <span
                            class="absolute left-0 -bottom-0.5 h-0.5 rounded-full bg-gradient-to-r from-[#FACC15] to-[#016cbb] transition-all duration-300"
                            :class="route().current('about') ? 'w-full' : 'w-0 group-hover:w-full'"
                        ></span>
                    </Link>
                    <Link
                        :href="route('contact')"
                        class="group relative py-1 text-sm font-semibold transition-colors duration-200"
                        :class="route().current('contact') ? 'text-[#016cbb]' : 'text-slate-700 hover:text-slate-950'"
                    >
                        Contact Us
                        <span
                            class="absolute left-0 -bottom-0.5 h-0.5 rounded-full bg-gradient-to-r from-[#FACC15] to-[#016cbb] transition-all duration-300"
                            :class="route().current('contact') ? 'w-full' : 'w-0 group-hover:w-full'"
                        ></span>
                    </Link>
                    <Link
                        :href="route('calculator')"
                        class="group relative py-1 text-sm font-semibold transition-colors duration-200"
                        :class="route().current('calculator') ? 'text-[#016cbb]' : 'text-slate-700 hover:text-slate-950'"
                    >
                        Load Calculator
                        <span
                            class="absolute left-0 -bottom-0.5 h-0.5 rounded-full bg-gradient-to-r from-[#FACC15] to-[#016cbb] transition-all duration-300"
                            :class="route().current('calculator') ? 'w-full' : 'w-0 group-hover:w-full'"
                        ></span>
                    </Link>
                    <Link
                        href="/training"
                        class="group relative py-1 text-sm font-semibold transition-colors duration-200 text-slate-700 hover:text-slate-950"
                    >
                        Training
                        <span
                            class="absolute left-0 -bottom-0.5 h-0.5 rounded-full bg-gradient-to-r from-[#FACC15] to-[#016cbb] transition-all duration-300 w-0 group-hover:w-full"
                        ></span>
                    </Link>
                    <Link
                        :href="route('shop')"
                        class="group relative py-1 text-sm font-semibold transition-colors duration-200"
                        :class="route().current('shop') ? 'text-[#016cbb]' : 'text-slate-700 hover:text-slate-950'"
                    >
                        Shop
                        <span
                            class="absolute left-0 -bottom-0.5 h-0.5 rounded-full bg-gradient-to-r from-[#FACC15] to-[#016cbb] transition-all duration-300"
                            :class="route().current('shop') ? 'w-full' : 'w-0 group-hover:w-full'"
                        ></span>
                    </Link>
                </nav>

                <!-- Desktop Navigation Actions -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- "Let's talk" Pill Button -->
                    <button
                        type="button"
                        @click="isContactModalOpen = true"
                        class="inline-flex items-center justify-center px-5 sm:px-6 py-2 rounded-full border border-slate-300 bg-white hover:bg-slate-950 hover:text-white hover:border-slate-950 text-slate-900 text-xs sm:text-sm font-medium transition-all duration-300"
                    >
                        Let's talk
                    </button>

                    <!-- Profile / Login Icon Button -->
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        title="Dashboard"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-full border border-slate-300 bg-white hover:border-yellow-400 hover:bg-yellow-400 hover:text-slate-950 flex items-center justify-center text-slate-900 transition-all duration-200"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        title="Sign In"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-full border border-slate-300 bg-white hover:border-yellow-400 hover:bg-yellow-400 hover:text-slate-950 flex items-center justify-center text-slate-900 transition-all duration-200"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </Link>

                    <!-- Hamburger Menu Toggle Button (2 dark lines) -->
                    <button
                        type="button"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="w-8 sm:w-9 flex flex-col justify-center items-end gap-1.5 p-1 group focus:outline-none"
                        aria-label="Toggle menu"
                    >
                        <span class="w-6 sm:w-7 h-[2px] bg-slate-900 transition-all duration-300 group-hover:bg-yellow-400"></span>
                        <span class="w-6 sm:w-7 h-[2px] bg-slate-900 transition-all duration-300 group-hover:bg-yellow-400"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Slide-out Menu Drawer -->
        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex justify-end transition-opacity duration-300"
            @click.self="isMobileMenuOpen = false"
        >
            <div class="w-full max-w-sm bg-[#0B132B] h-full p-8 flex flex-col justify-between shadow-2xl border-l border-white/10">
                <div>
                    <div class="flex items-center justify-between pb-6 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <img src="/envoy_images/logo.png" alt="Envoy Electric" class="h-10 w-auto object-contain bg-white rounded-lg p-1" />
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
                        <a
                            href="#hero"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            Home
                        </a>
                        <a
                            href="#solutions"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            Solar Solutions
                        </a>
                        <a
                            href="#whatwedo"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            What We Do
                        </a>
                        <a
                            href="#gallery"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            Gallery
                        </a>
                        <a
                            href="#services"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            Renewable Offers
                        </a>
                        <Link
                            href="/training"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            Training
                        </Link>
                        <a
                            href="#shop"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center justify-between text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
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
                        </a>
                        <a
                            :href="route('about')"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            About Us
                        </a>
                        <a
                            :href="route('contact')"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            Contact
                        </a>
                        <a
                            :href="route('calculator')"
                            @click="isMobileMenuOpen = false"
                            class="block text-lg font-semibold text-white/90 hover:text-yellow-400 transition"
                        >
                            Load Calculator
                        </a>
                        <button
                            type="button"
                            @click="isMobileMenuOpen = false; isContactModalOpen = true"
                            class="block w-full text-left text-lg font-semibold text-yellow-400 hover:text-yellow-300 transition pt-2"
                        >
                            Consultation Request
                        </button>
                    </nav>
                </div>

                <div class="pt-6 border-t border-white/10 space-y-3">
                    <button
                        type="button"
                        @click="isMobileMenuOpen = false; isContactModalOpen = true"
                        class="w-full py-3 bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-bold rounded-full text-center transition"
                    >
                        Let's Talk
                    </button>
                    <Link
                        v-if="!$page.props.auth?.user"
                        :href="route('login')"
                        class="w-full py-3 border border-white/30 text-white font-semibold rounded-full text-center block hover:bg-white/10 transition"
                    >
                        Sign In / Staff Portal
                    </Link>
                    <Link
                        v-else
                        :href="route('dashboard')"
                        class="w-full py-3 border border-white/30 text-white font-semibold rounded-full text-center block hover:bg-white/10 transition"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- 1. HERO SECTION — CINEMATIC SLIDER (Exact Match to Design) -->
        <!-- ========================================================= -->
        <section
            id="hero"
            class="relative min-h-[65vh] flex flex-col justify-between overflow-hidden bg-slate-950 select-none"
            @mouseenter="pauseHeroTimer"
            @mouseleave="restartHeroTimer"
        >
            <!-- Animated Slide Background Layer -->
            <div class="absolute inset-0 z-0">
                <div
                    v-for="(slide, index) in heroSlides"
                    :key="index"
                    class="absolute inset-0 transition-opacity duration-[1200ms] ease-in-out"
                    :class="index === activeHeroIndex ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                >
                    <img
                        :src="slide.image"
                        :alt="slide.titleLine1 + ' ' + slide.titleLine2"
                        :class="['w-full h-full object-cover object-center', index === activeHeroIndex && 'hero-kenburns']"
                    />
                    <!-- Subtle Dark Vignette & Gradient Overlays (exact match to screenshot tone) -->
                    <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/20 to-black/60"></div>
                    <div class="absolute inset-0 bg-black/15"></div>
                </div>
            </div>

            <!-- Floating Side Slide Controls (Prev / Next) for Slider function -->
            <button
                type="button"
                @click="prevHero"
                aria-label="Previous slide"
                class="hidden md:flex absolute left-4 lg:left-8 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full border border-white/25 bg-black/20 hover:bg-[#FACC15] hover:border-[#FACC15] hover:text-slate-950 text-white backdrop-blur-md items-center justify-center transition-all duration-300 shadow-xl group"
            >
                <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button
                type="button"
                @click="nextHero"
                aria-label="Next slide"
                class="hidden md:flex absolute right-4 lg:right-8 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full border border-white/25 bg-black/20 hover:bg-[#FACC15] hover:border-[#FACC15] hover:text-slate-950 text-white backdrop-blur-md items-center justify-center transition-all duration-300 shadow-xl group"
            >
                <svg class="w-5 h-5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Central Hero Content -->
            <div class="relative z-10 flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 pt-8 sm:pt-12 pb-16">
                <div :key="activeHeroIndex" class="max-w-4xl mx-auto text-center flex flex-col items-center">
                    <!-- Angled Parallelogram Badge -->
                    <!-- Decorative Sunrise Beam Ornament -->
                    <div class="hero-reveal relative inline-flex items-center justify-center">
                        <span class="beam-pulse h-px w-10 sm:w-16 bg-gradient-to-r from-transparent via-[#FACC15]/60 to-[#FACC15]"></span>
                        <span class="relative mx-3 sm:mx-4 flex h-9 w-9 sm:h-11 sm:w-11 items-center justify-center">
                            <span class="absolute inset-0 rounded-full bg-[#FACC15]/20 blur-md beam-halo"></span>
                            <svg class="sun-spin relative h-7 w-7 sm:h-9 sm:w-9 text-[#FDE047] drop-shadow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="4" fill="#FDE047" stroke="none" />
                                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" stroke-width="2.4" />
                            </svg>
                        </span>
                        <span class="beam-pulse h-px w-10 sm:w-16 bg-gradient-to-l from-transparent via-[#40e0d0]/60 to-[#40e0d0]"></span>
                    </div>

                    <!-- Hero Headline -->
                    <h1 class="mt-5 text-4xl sm:text-6xl md:text-7xl lg:text-[84px] font-black uppercase tracking-tight text-white leading-none drop-shadow-xl">
                        <span class="hero-title-line block">
                            {{ heroSlides[activeHeroIndex].titleLine1 }}
                        </span>
                        <span class="hero-title-delay-1 block relative mt-1 sm:mt-2">
                            <span class="hero-title-grad bg-gradient-to-r from-[#FACC15] via-[#40e0d0] to-[#FACC15] bg-clip-text text-transparent">
                                {{ heroSlides[activeHeroIndex].titleLine2 }}
                            </span>
                        </span>
                    </h1>

                    <!-- Animated Gradient Underline -->
                    <div class="hero-reveal hero-title-delay-2 mt-5 inline-flex items-center gap-2">
                        <span class="h-1 w-16 sm:w-24 rounded-full bg-gradient-to-r from-[#FACC15] to-[#40e0d0]"></span>
                        <span class="h-1 w-3 rounded-full bg-[#40e0d0]/70"></span>
                    </div>

                    <!-- Subtitle -->
                    <p class="hero-reveal hero-delay-1 mt-5 sm:mt-6 text-sm sm:text-base md:text-lg text-slate-100/90 max-w-2xl font-normal leading-relaxed text-center drop-shadow">
                        {{ heroSlides[activeHeroIndex].subtitle }}
                    </p>

                    <!-- Center Single CTA Button (Exact match: Discover More ↗) -->
                    <div class="hero-reveal hero-delay-2 mt-7 sm:mt-9 flex items-center justify-center">
                        <a
                            :href="heroSlides[activeHeroIndex].cta.href"
                            class="group inline-flex items-center gap-3.5 bg-white hover:bg-[#FACC15] text-slate-950 font-bold px-7 sm:px-8 py-3 sm:py-3.5 rounded-full shadow-2xl transition-all duration-300 hover:-translate-y-0.5"
                        >
                            <span class="text-sm sm:text-base font-bold">{{ heroSlides[activeHeroIndex].cta.label }}</span>
                            <span class="w-7 h-7 rounded-full bg-slate-950 text-white flex items-center justify-center text-xs group-hover:scale-110 transition-transform">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="7" y1="17" x2="17" y2="7"></line>
                                    <polyline points="7 7 17 7 17 17"></polyline>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Ghost Slide Number Watermark -->
            <div class="pointer-events-none absolute left-6 lg:left-12 bottom-28 z-10 hidden lg:block select-none">
                <span class="hero-reveal text-[100px] xl:text-[130px] font-black leading-none text-white/10 tracking-tighter tabular-nums">
                    {{ String(activeHeroIndex + 1).padStart(2, '0') }}
                </span>
            </div>

            <!-- Creative Slide Pagination: Numbered Live Progress Bars -->
            <div class="relative z-20 flex items-center justify-center pb-4">
                <div class="flex items-center gap-3 sm:gap-4 rounded-full border border-white/20 bg-black/30 backdrop-blur-md px-4 sm:px-6 py-3">
                    <span class="text-[#FACC15] font-black text-xs sm:text-sm tracking-widest tabular-nums">{{ String(activeHeroIndex + 1).padStart(2, '0') }}</span>
                    <div class="flex items-center gap-2 sm:gap-2.5">
                        <button
                            v-for="(slide, i) in heroSlides"
                            :key="i"
                            type="button"
                            @click="goToHero(i)"
                            :aria-label="'Go to slide ' + (i + 1)"
                            class="group relative h-1.5 w-10 sm:w-14 overflow-hidden rounded-full bg-white/25 cursor-pointer"
                        >
                            <span
                                v-if="i === activeHeroIndex"
                                class="hero-progress-fill absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-[#FACC15] to-[#40e0d0]"
                            ></span>
                            <span
                                v-else
                                class="absolute inset-y-0 left-0 w-0 rounded-full bg-white/50 transition-all duration-300 group-hover:w-1/2"
                            ></span>
                        </button>
                    </div>
                    <span class="text-white/60 font-black text-xs sm:text-sm tracking-widest tabular-nums">{{ String(heroSlides.length).padStart(2, '0') }}</span>
                </div>
            </div>

            <!-- Bottom Hero Features Strip (Exact match to screenshot) -->
            <div class="relative z-20 w-full flex flex-col lg:flex-row items-stretch shadow-2xl">
                <!-- Left Yellow Angled Block -->
                <div class="relative bg-[#FACC15] text-slate-950 font-black px-6 sm:px-10 py-4 lg:py-5 flex items-center justify-center text-base sm:text-lg lg:text-xl tracking-tight uppercase shrink-0 lg:[clip-path:polygon(0_0,calc(100%-24px)_0,100%_100%,0_100%)]">
                    <span>Eco-Friendly Energy</span>
                </div>

                <!-- Dark Navy Strip with 4 Feature Icons -->
                <div class="flex-1 bg-[#0A101E] px-4 sm:px-8 py-4 flex flex-wrap items-center justify-around gap-4 sm:gap-6 text-white">
                    <!-- Feature 1: Smart Solar Technology -->
                    <div class="flex items-center gap-3 sm:gap-3.5 min-w-[150px]">
                        <div class="w-10 h-10 rounded-full border border-white/40 flex items-center justify-center shrink-0 text-white">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold text-slate-100 leading-tight">
                            Smart Solar<br />Technology
                        </span>
                    </div>

                    <!-- Feature 2: Advanced Solar Solutions -->
                    <div class="flex items-center gap-3 sm:gap-3.5 min-w-[150px]">
                        <div class="w-10 h-10 rounded-full border border-white/40 flex items-center justify-center shrink-0 text-white">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="4" width="18" height="12" rx="2" />
                                <path d="M3 10h18M12 4v12M9 20h6M12 16v4" />
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold text-slate-100 leading-tight">
                            Advanced<br />Solar Solutions
                        </span>
                    </div>

                    <!-- Feature 3: Unlimited Solar Power -->
                    <div class="flex items-center gap-3 sm:gap-3.5 min-w-[150px]">
                        <div class="w-10 h-10 rounded-full border border-white/40 flex items-center justify-center shrink-0 text-white">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 1v3M12 20v3M3 12H0M24 12h-3M19.07 4.93l-2.12 2.12M7.05 16.95l-2.12 2.12M19.07 19.07l-2.12-2.12M7.05 7.05L4.93 4.93" />
                                <path d="M13 8l-3 4h4l-2 4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold text-slate-100 leading-tight">
                            Unlimited<br />Solar Power
                        </span>
                    </div>

                    <!-- Feature 4: Solar Installation Service -->
                    <div class="flex items-center gap-3 sm:gap-3.5 min-w-[150px]">
                        <div class="w-10 h-10 rounded-full border border-white/40 flex items-center justify-center shrink-0 text-white">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold text-slate-100 leading-tight">
                            Solar Installation<br />Service
                        </span>
                    </div>
                </div>

                <!-- Far Right Yellow Accent Cut -->
                <div class="hidden lg:block w-32 xl:w-44 bg-[#FACC15] relative [clip-path:polygon(32px_0,100%_0,100%_100%,0_100%)]"></div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- 2. SECTION 2: SMART ENERGY SOLUTIONS & SLANTED MOSAIC (Image 2) -->
        <!-- ========================================================= -->
        <section class="py-20 sm:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <!-- Giant Headline -->
            <div class="text-center max-w-5xl mx-auto mb-16">
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-[40px] font-black uppercase tracking-tight text-slate-950 leading-tight sm:leading-snug">
                    SMART ENERGY SOLUTIONS, QUALITY SOLAR GOODS<br />& SKILLED SERVICE FOR A SUSTAINABLE FUTURE
                </h2>
            </div>

            <!-- 2-Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-stretch">
                <!-- Left / Center Column: Slanted Mosaic Photographic Collage (lg:col-span-8) -->
                <div class="lg:col-span-8 relative min-h-[380px] sm:min-h-[500px] flex flex-col justify-center">
                    <!-- Slanted Grid with sharp diagonal cuts & white borders -->
                    <div class="relative w-full h-[400px] sm:h-[480px] md:h-[540px] overflow-hidden rounded-xl bg-white">
                        <!-- Panel 1: Top Left Polygon (Engineer in yellow safety gear looking right) -->
                        <div
                            class="absolute top-0 left-0 w-[58%] h-[72%] overflow-hidden z-20 bg-slate-900 border-[6px] border-white shadow-md [clip-path:polygon(0_0,78%_0,100%_100%,0_100%)]"
                        >
                            <img
                                src="/envoy_images/solar-engineer.jpg"
                                alt="Solar Field Engineer"
                                class="w-full h-full object-cover object-center scale-110 filter brightness-95"
                            />
                        </div>

                        <!-- Panel 2: Center Wide Polygon (Rooftop solar overlooking skyline) -->
                        <div
                            class="absolute top-0 right-0 w-[74%] h-full overflow-hidden z-10 bg-slate-800 border-[6px] border-white shadow-lg [clip-path:polygon(32%_0,100%_0,100%_100%,0_100%)]"
                        >
                            <img
                                src="/envoy_images/solar-city-rooftop.jpg"
                                alt="Solar Rooftop and City Skyline"
                                class="w-full h-full object-cover object-center scale-105"
                            />
                        </div>

                        <!-- Panel 3: Bottom Left Diagonal Polygon (Engineers with toolbox) -->
                        <div
                            class="absolute bottom-0 left-0 w-[54%] h-[46%] overflow-hidden z-30 bg-slate-900 border-[6px] border-white shadow-2xl [clip-path:polygon(0_0,100%_0,62%_100%,0_100%)]"
                        >
                            <img
                                src="/envoy_images/engineers-rooftop.jpg"
                                alt="Engineers Reviewing Installation"
                                class="w-full h-full object-cover object-center scale-110"
                            />
                        </div>
                    </div>
                </div>

                <!-- Right Column: Stacked Card with Image & Cream Content Box (lg:col-span-4) -->
                <div class="lg:col-span-4 flex flex-col rounded-xl overflow-hidden shadow-sm border border-slate-200/80">
                    <!-- Top Image: Clean Ground Solar Panels -->
                    <div class="h-56 sm:h-64 overflow-hidden bg-slate-100">
                        <img
                            src="/envoy_images/solar-panels-sky.jpg"
                            alt="Clean Solar Panel Array"
                            class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                        />
                    </div>

                    <!-- Bottom Cream/Ivory Card -->
                    <div class="flex-1 bg-[#FAF7EE] p-6 sm:p-8 flex flex-col justify-between">
                        <p class="text-slate-700 text-sm sm:text-base leading-relaxed font-normal">
                            Factorex brings expert guidance and reliable technology to every project. Whether you're looking to lower your energy bills, increase property value, or achieve complete energy independence, we offer customized solutions to meet your energy goals.
                        </p>

                        <div class="mt-8">
                            <button
                                type="button"
                                @click="isContactModalOpen = true"
                                class="group inline-flex items-center justify-between gap-4 bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-6 py-3.5 rounded-full transition-all duration-300 shadow-md"
                            >
                                <span class="text-sm font-semibold tracking-wide">Get Started</span>
                                <span class="w-7 h-7 rounded-full bg-yellow-400 text-slate-950 flex items-center justify-center text-xs font-black group-hover:translate-x-0.5 transition-transform">
                                    ↗
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- 3. SECTION 3: SOLAR SOLUTIONS (Images 3 & 4) -->
        <!-- ========================================================= -->
        <section id="solutions" class="relative bg-[#0A1024] text-white pt-20 pb-28 sm:pb-32 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Section Header with Title & Arrow Navigation -->
                <div class="flex items-center justify-between mb-12 sm:mb-14">
                    <h2 class="text-2xl sm:text-4xl md:text-5xl font-black uppercase tracking-wider text-white">
                        SOLAR SOLUTIONS
                    </h2>

                    <!-- Navigation Arrows — matching mockup: ← outlined, → yellow filled -->
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="prevSolution"
                            class="w-11 h-11 border border-white/30 text-white flex items-center justify-center text-lg font-semibold hover:border-white/60 hover:bg-white/10 transition-all duration-200"
                            aria-label="Previous solution"
                        >
                            ←
                        </button>
                        <button
                            type="button"
                            @click="nextSolution"
                            class="w-11 h-11 bg-yellow-400 hover:bg-yellow-300 text-slate-950 flex items-center justify-center text-lg font-bold transition-all duration-200"
                            aria-label="Next solution"
                        >
                            →
                        </button>
                    </div>
                </div>

                <!-- 4 Solution Cards Grid — each card has icon top-left + diagonal triangle photo top-right -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div
                        v-for="sol in solutions"
                        :key="sol.id"
                        class="bg-white text-slate-900 rounded-lg flex flex-col overflow-hidden shadow-lg transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl group"
                    >
                        <!-- ======== CARD TOP: icon badge left + triangular photo cutout right ======== -->
                        <div class="relative h-36 shrink-0 bg-white">
                            <!-- Dark navy circular icon badge — top left -->
                            <div class="absolute top-4 left-4 z-20 w-[52px] h-[52px] rounded-full bg-[#0D1527] text-yellow-400 flex items-center justify-center shadow-md">
                                <svg v-if="sol.icon === 'battery'" class="w-[26px] h-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="1" y="6" width="18" height="13" rx="2" />
                                    <path d="M23 11v4M7 12h8" />
                                </svg>
                                <svg v-else-if="sol.icon === 'solar'" class="w-[26px] h-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M3 9.5 12 3l9 6.5V21H3V9.5z" />
                                    <path d="M9 21V13h6v8" />
                                    <path d="M10 6h4M9 9h6" stroke-width="1.3" />
                                </svg>
                                <svg v-else-if="sol.icon === 'inverter'" class="w-[26px] h-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="2" y="2" width="20" height="20" rx="3" />
                                    <path d="M6 12h2.5l2-4 2.5 8L15.5 12H18" stroke-width="1.8" />
                                </svg>
                                <svg v-else class="w-[26px] h-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94L14.7 6.3z" />
                                </svg>
                            </div>

                            <!-- Triangular diagonal photo cutout — top right corner, matching mockup shape -->
                            <div class="absolute top-0 right-0 w-44 h-36 overflow-hidden [clip-path:polygon(42%_0%,100%_0%,100%_100%,0%_100%)]">
                                <img
                                    :src="sol.image"
                                    :alt="sol.title"
                                    class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
                                />
                            </div>
                        </div>

                        <!-- ======== CARD BODY: Title + Description ======== -->
                        <div class="px-5 pt-4 pb-3 flex-1">
                            <h3 class="text-[15px] font-bold text-slate-900 leading-snug mb-2.5">
                                {{ sol.title }}
                            </h3>
                            <p class="text-[12.5px] text-slate-500 leading-relaxed">
                                {{ sol.description }}
                            </p>
                        </div>

                        <!-- ======== CARD FOOTER: Learn More pill button ======== -->
                        <div class="px-5 pt-2 pb-5">
                            <button
                                type="button"
                                @click="isContactModalOpen = true"
                                class="inline-flex items-center px-5 py-1.5 rounded-full border border-slate-800 text-[12.5px] font-semibold text-slate-900 hover:bg-slate-900 hover:text-white transition-colors duration-200"
                            >
                                Learn More
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Creative bottom transition: sun rising over flowing waves -->
            <div class="absolute bottom-0 left-0 right-0 h-28 pointer-events-none" aria-hidden="true">
                <!-- Soft sun glow -->
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-96 h-96 rounded-full bg-[radial-gradient(circle,rgba(250,204,21,0.5)_0%,rgba(250,204,21,0.14)_45%,transparent_72%)]"></div>

                <!-- Slowly rotating sun rays fanning out from the centre -->
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 origin-bottom w-[860px] h-48 sol-rays">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 860 192" fill="none">
                        <g stroke="url(#solRayFade)" stroke-width="2.5" stroke-linecap="round">
                            <line x1="430" y1="192" x2="70" y2="34" />
                            <line x1="430" y1="192" x2="150" y2="8" />
                            <line x1="430" y1="192" x2="270" y2="0" />
                            <line x1="430" y1="192" x2="590" y2="0" />
                            <line x1="430" y1="192" x2="710" y2="8" />
                            <line x1="430" y1="192" x2="790" y2="34" />
                            <line x1="430" y1="192" x2="0" y2="74" />
                            <line x1="430" y1="192" x2="860" y2="74" />
                        </g>
                        <defs>
                            <linearGradient id="solRayFade" x1="430" y1="192" x2="430" y2="0" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="#FACC15" stop-opacity="0.9" />
                                <stop offset="1" stop-color="#FACC15" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>

                <!-- Sun disc peeking over the horizon -->
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 w-28 h-28 rounded-full bg-gradient-to-t from-yellow-500 via-yellow-400 to-amber-300 shadow-[0_0_60px_14px_rgba(250,204,21,0.4)]"></div>

                <!-- Pulsing halo rings -->
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 w-44 h-44 rounded-full border border-yellow-400/50 sol-pulse"></div>
                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-72 h-72 rounded-full border border-yellow-400/30 sol-pulse-slow"></div>

                <!-- Turquoise horizon tick -->
                <div class="absolute bottom-12 left-1/2 -translate-x-1/2 w-2/3 h-px bg-gradient-to-r from-transparent via-[#40e0d0]/70 to-transparent"></div>

                <!-- Flowing layered waves transitioning into the next section -->
                <svg class="absolute bottom-0 left-0 w-full h-14" viewBox="0 0 1440 56" preserveAspectRatio="none" version="1.1" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0,28 C240,6 420,30 720,22 C1020,14 1230,30 1440,16 L1440,56 L0,56 Z" fill="#16223F" opacity="0.45" />
                    <path d="M0,40 C220,26 460,46 720,38 C980,30 1200,46 1440,34 L1440,56 L0,56 Z" fill="#FAF8F2" />
                </svg>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- 4. SECTION 4: WHAT WE DO — ENVOY ELECTRICALS SERVICES -->
        <!-- ========================================================= -->
        <section id="whatwedo" class="relative py-20 sm:py-28 px-4 sm:px-6 lg:px-8 overflow-hidden bg-[#FAF8F2]">
            <!-- Decorative glows -->
            <div class="absolute -top-24 -right-20 w-80 h-80 rounded-full bg-yellow-300/30 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-20 w-72 h-72 rounded-full bg-[#016cbb]/15 blur-3xl pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative">
                <!-- Section Header -->
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-14 sm:mb-16">
                    <div class="max-w-xl">
                        <span class="inline-flex items-center gap-2 text-[#016cbb] text-xs font-bold uppercase tracking-[0.3em] mb-4">
                            <span class="w-8 h-px bg-[#016cbb]"></span>
                            What We Do
                        </span>
                        <h2 class="text-3xl sm:text-5xl font-black uppercase tracking-tight text-slate-950 leading-tight">
                            Solar & Electrical<br />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-[#016cbb]">Services</span>
                        </h2>
                    </div>
                    <p class="max-w-md text-slate-500 text-sm sm:text-base leading-relaxed">
                        Envoy Electricals is your one-stop partner for solar installations, trusted solar &
                        electrical products, and professional hands-on training.
                    </p>
                </div>

                <!-- 3 Service Pillars -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6 lg:gap-8">
                    <!-- 01 Solar Installation -->
                    <div class="group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-[#111C36] to-[#0A1020] px-7 sm:px-8 pt-7 sm:pt-8 pb-9 sm:pb-10 flex flex-col min-h-[440px] transition-all duration-500 hover:-translate-y-2 hover:border-yellow-400/40 hover:shadow-2xl hover:shadow-yellow-400/10">
                        <!-- Top accent line -->
                        <span class="absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-transparent via-yellow-400 to-transparent opacity-80"></span>
                        <!-- Ghost number -->
                        <span class="absolute top-7 right-8 text-7xl font-black text-white/[0.06] transition-transform duration-500 group-hover:rotate-6 group-hover:scale-110 select-none">01</span>
                        <!-- Ambient glow -->
                        <span class="absolute -top-16 -right-16 w-44 h-44 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></span>

                        <!-- Icon medallion with rotating dashed ring -->
                        <div class="relative mt-1 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-yellow-400 to-amber-500 text-slate-950 shadow-lg shadow-yellow-400/30">
                            <span class="wdw-spin absolute -inset-2 rounded-3xl border border-dashed border-yellow-400/40"></span>
                            <i class="bi bi-sun text-2xl"></i>
                        </div>

                        <h3 class="mt-7 text-2xl font-black tracking-tight text-white">Solar Installation</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-400">
                            End-to-end design, installation, and after-sales support for homes and businesses across the country.
                        </p>

                        <ul class="mt-5 space-y-3">
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-yellow-400"></i>
                                Residential & commercial setups
                            </li>
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-yellow-400"></i>
                                Grid-tie, off-grid & hybrid systems
                            </li>
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-yellow-400"></i>
                                Maintenance & after-sales support
                            </li>
                        </ul>

                        <div class="mt-auto pt-7">
                            <button
                                type="button"
                                @click="isContactModalOpen = true"
                                class="group/btn inline-flex items-center gap-2.5 rounded-full border border-yellow-400/40 px-5 py-2.5 text-sm font-bold text-yellow-400 transition-all duration-300 hover:bg-yellow-400 hover:text-slate-950"
                            >
                                Request Installation
                                <i class="bi bi-arrow-right transition-transform duration-300 group-hover/btn:translate-x-1"></i>
                            </button>
                        </div>

                        <span class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-yellow-400/10 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
                    </div>

                    <!-- 02 Solar & Electrical Products -->
                    <div class="group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-[#111C36] to-[#0A1020] px-7 sm:px-8 pt-7 sm:pt-8 pb-9 sm:pb-10 flex flex-col min-h-[440px] transition-all duration-500 hover:-translate-y-2 hover:border-[#40e0d0]/40 hover:shadow-2xl hover:shadow-[#40e0d0]/10">
                        <!-- Top accent line -->
                        <span class="absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-transparent via-[#40e0d0] to-transparent opacity-80"></span>
                        <!-- Ghost number -->
                        <span class="absolute top-7 right-8 text-7xl font-black text-white/[0.06] transition-transform duration-500 group-hover:rotate-6 group-hover:scale-110 select-none">02</span>
                        <!-- Ambient glow -->
                        <span class="absolute -top-16 -right-16 w-44 h-44 rounded-full bg-[#40e0d0]/10 blur-3xl pointer-events-none"></span>

                        <!-- Icon medallion with rotating dashed ring -->
                        <div class="relative mt-1 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-[#40e0d0] to-[#40e0d0] text-slate-950 shadow-lg shadow-[#40e0d0]/30">
                            <span class="wdw-spin absolute -inset-2 rounded-3xl border border-dashed border-[#40e0d0]/40"></span>
                            <i class="bi bi-plug text-2xl"></i>
                        </div>

                        <h3 class="mt-7 text-2xl font-black tracking-tight text-white">Solar & Electrical Products</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-400">
                            Genuine solar and electrical equipment sourced from trusted brands — at the best prices with full warranty.
                        </p>

                        <ul class="mt-5 space-y-3">
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-[#40e0d0]"></i>
                                Tier-1 panels, inverters & batteries
                            </li>
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-[#40e0d0]"></i>
                                Electrical fittings & accessories
                            </li>
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-[#40e0d0]"></i>
                                Warranty-backed, fair pricing
                            </li>
                        </ul>

                        <div class="mt-auto pt-7">
                            <a
                                href="#shop"
                                class="group/btn inline-flex items-center gap-2.5 rounded-full border border-[#40e0d0]/40 px-5 py-2.5 text-sm font-bold text-[#40e0d0] transition-all duration-300 hover:bg-[#40e0d0] hover:text-slate-950"
                            >
                                Shop Products
                                <i class="bi bi-arrow-right transition-transform duration-300 group-hover/btn:translate-x-1"></i>
                            </a>
                        </div>

                        <span class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#40e0d0]/10 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
                    </div>

                    <!-- 03 Professional Trainings -->
                    <div id="training" class="group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-[#111C36] to-[#0A1020] px-7 sm:px-8 pt-7 sm:pt-8 pb-9 sm:pb-10 flex flex-col min-h-[440px] transition-all duration-500 hover:-translate-y-2 hover:border-emerald-400/40 hover:shadow-2xl hover:shadow-emerald-400/10">
                        <!-- Top accent line -->
                        <span class="absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-transparent via-emerald-400 to-transparent opacity-80"></span>
                        <!-- Ghost number -->
                        <span class="absolute top-7 right-8 text-7xl font-black text-white/[0.06] transition-transform duration-500 group-hover:rotate-6 group-hover:scale-110 select-none">03</span>
                        <!-- Ambient glow -->
                        <span class="absolute -top-16 -right-16 w-44 h-44 rounded-full bg-emerald-400/10 blur-3xl pointer-events-none"></span>

                        <!-- Icon medallion with rotating dashed ring -->
                        <div class="relative mt-1 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-500 text-slate-950 shadow-lg shadow-emerald-400/30">
                            <span class="wdw-spin absolute -inset-2 rounded-3xl border border-dashed border-emerald-400/40"></span>
                            <i class="bi bi-mortarboard text-2xl"></i>
                        </div>

                        <h3 class="mt-7 text-2xl font-black tracking-tight text-white">Professional Trainings</h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-400">
                            Practical, career-focused training that turns beginners into confident solar and electrical technicians.
                        </p>

                        <ul class="mt-5 space-y-3">
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-emerald-400"></i>
                                Hands-on solar installation practice
                            </li>
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-emerald-400"></i>
                                Inverter & battery wiring fundamentals
                            </li>
                            <li class="flex items-center gap-2.5 text-[13px] text-slate-300">
                                <i class="bi bi-patch-check-fill text-emerald-400"></i>
                                Certification for career growth
                            </li>
                        </ul>

                        <div class="mt-auto pt-7">
                            <button
                                type="button"
                                @click="isContactModalOpen = true"
                                class="group/btn inline-flex items-center gap-2.5 rounded-full border border-emerald-400/40 px-5 py-2.5 text-sm font-bold text-emerald-400 transition-all duration-300 hover:bg-emerald-400 hover:text-slate-950"
                            >
                                Enroll in a Class
                                <i class="bi bi-arrow-right transition-transform duration-300 group-hover/btn:translate-x-1"></i>
                            </button>
                        </div>

                        <span class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-emerald-400/10 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
                    </div>
                </div>

                <!-- Bottom CTA Banner -->
                <div class="mt-12 sm:mt-16 rounded-3xl bg-[#0D1527] px-6 sm:px-10 py-8 sm:py-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative overflow-hidden">
                    <div class="absolute -top-16 -right-10 w-48 h-48 rounded-full bg-yellow-400/20 blur-3xl pointer-events-none"></div>
                    <div>
                        <h3 class="text-xl sm:text-2xl font-black uppercase text-white tracking-tight">
                            Ready to Switch to Solar?
                        </h3>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed max-w-xl">
                            Book a free site assessment with our engineers and get a customised quote within 24 hours.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="isContactModalOpen = true"
                        class="shrink-0 inline-flex items-center gap-3 bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-bold px-7 py-3.5 rounded-full transition-all duration-300 shadow-lg shadow-yellow-400/30 group"
                    >
                        Get a Free Estimate
                        <i class="bi bi-arrow-right transition-transform duration-300 group-hover:translate-x-1"></i>
                    </button>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- 5. SECTION 5: OUR WORK GALLERY -->
        <!-- ========================================================= -->
        <section id="gallery" class="relative bg-[#0B1220] py-16 sm:py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
            <!-- Ambient Glow Orbs -->
            <div class="absolute -top-32 -right-24 w-96 h-96 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -left-24 w-80 h-80 rounded-full bg-[#40e0d0]/10 blur-3xl pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative">
                <!-- Header + Filters -->
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-9">
                    <div class="max-w-xl">
                        <span class="inline-flex items-center gap-2 text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em] mb-3">
                            <span class="w-8 h-px bg-[#40e0d0]"></span>
                            Our Work
                            <span class="w-8 h-px bg-[#40e0d0]"></span>
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black uppercase tracking-tight text-white leading-tight">
                            Project<br />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-[#40e0d0]">Gallery</span>
                        </h2>
                    </div>
                    <div class="hidden md:flex items-center gap-2.5 text-white/40 text-xs font-bold uppercase tracking-[0.25em]">
                            <i class="bi bi-images text-base"></i>
                            A Look At What We Build
                        </div>
                </div>

                <!-- Masonry Grid -->
                <div class="columns-1 sm:columns-2 lg:columns-3 gap-4">
                    <button
                        v-for="(image, index) in filteredGallery"
                        :key="image.src"
                        type="button"
                        @click="openGallery(index)"
                        :class="image.tall ? 'aspect-[4/3]' : 'aspect-[3/2]'"
                        class="relative group mb-4 w-full block overflow-hidden rounded-2xl cursor-pointer break-inside-avoid"
                    >
                        <img
                            :src="image.src"
                            :alt="image.caption"
                            class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 group-hover:scale-110"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent opacity-70 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <span class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white/15 backdrop-blur border border-white/25 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-300">
                            <i class="bi bi-zoom-in text-base"></i>
                        </span>
                        <div class="absolute bottom-0 left-0 right-0 p-3.5 sm:p-4 text-left">
                            <span class="text-[10px] font-bold uppercase tracking-[0.25em] text-yellow-400">{{ image.category }}</span>
                            <p class="mt-1 text-xs sm:text-sm font-bold text-white leading-snug">{{ image.caption }}</p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Lightbox Modal -->
            <div
                v-if="isGalleryOpen"
                class="fixed inset-0 z-[60] bg-slate-950/95 backdrop-blur-sm flex items-center justify-center p-4 sm:p-8"
                @click.self="closeGallery"
            >
                <button
                    type="button"
                    @click="closeGallery"
                    class="absolute top-5 right-5 sm:top-7 sm:right-7 w-11 h-11 rounded-full border border-white/30 text-white hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition flex items-center justify-center text-xl"
                    aria-label="Close gallery"
                >
                    ✕
                </button>
                <button
                    type="button"
                    @click="galleryPrev"
                    class="absolute left-3 sm:left-8 top-1/2 -translate-y-1/2 w-11 h-11 sm:w-14 sm:h-14 rounded-full border border-white/30 text-white hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition flex items-center justify-center text-xl"
                    aria-label="Previous image"
                >
                    ←
                </button>
                <button
                    type="button"
                    @click="galleryNext"
                    class="absolute right-3 sm:right-8 top-1/2 -translate-y-1/2 w-11 h-11 sm:w-14 sm:h-14 rounded-full bg-yellow-400 text-slate-950 hover:bg-yellow-300 transition flex items-center justify-center text-xl shadow-lg"
                    aria-label="Next image"
                >
                    →
                </button>

                <div class="max-w-4xl w-full">
                    <figure class="relative">
                        <div class="relative rounded-2xl overflow-hidden bg-black/40 h-[45vh] sm:h-[55vh]">
                            <img
                                :src="filteredGallery[galleryIndex].src"
                                :alt="filteredGallery[galleryIndex].caption"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        <figcaption class="mt-4 flex items-end justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-[0.25em] text-yellow-400">{{ filteredGallery[galleryIndex].category }}</span>
                                <p class="mt-1 text-base sm:text-lg font-bold text-white">{{ filteredGallery[galleryIndex].caption }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-black text-white/60">{{ String(galleryIndex + 1).padStart(2, '0') }} / {{ String(filteredGallery.length).padStart(2, '0') }}</span>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- 5a. SECTION: ABOUT US -->
        <!-- ========================================================= -->
        <section id="about" class="relative py-16 sm:py-20 px-4 sm:px-6 lg:px-8 overflow-hidden bg-[#FAF8F2]">
            <!-- Ambient glows -->
            <div class="absolute -bottom-24 -right-16 w-72 h-72 rounded-full bg-yellow-300/30 blur-3xl pointer-events-none"></div>
            <div class="absolute -top-24 -left-20 w-72 h-72 rounded-full bg-[#016cbb]/10 blur-3xl pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center">
                    <!-- Left: Imagery -->
                    <div class="lg:col-span-6">
                        <div class="relative pr-6 sm:pr-10 lg:pr-0">
                            <div class="relative rounded-[2rem] overflow-hidden shadow-2xl h-[300px] sm:h-[380px] lg:h-[430px] bg-slate-900">
                                <img
                                    src="/envoy_images/about-site-engineers.jpg"
                                    alt="Envoy Electricals Engineers at Work"
                                    class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-[#0D1527]/85 via-[#0D1527]/10 to-transparent"></div>
                                <!-- Top-left chip -->
                                <div class="absolute top-5 left-5 inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur border border-white/25 px-4 py-2 text-white text-xs font-bold">
                                    <i class="bi bi-lightning-charge-fill text-yellow-400"></i>
                                    Envoy Electricals
                                </div>
                                <!-- Bottom-left quote -->
                                <p class="absolute bottom-6 left-5 right-5 text-white/90 text-sm leading-relaxed">
                                    "Reliable energy should be within everyone's reach."
                                </p>
                            </div>
                            <!-- Floating experience badge -->
                            <div class="absolute -bottom-6 -left-2 sm:-left-6 rounded-2xl bg-[#0D1527] px-6 py-5 shadow-2xl border-l-4 border-yellow-400">
                                <p class="text-3xl font-black text-yellow-400 leading-none">10<span class="text-[#40e0d0]">+</span></p>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-white/80 mt-2">Years Experience</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Story -->
                    <div class="lg:col-span-6 mt-10 lg:mt-0">
                        <span class="inline-flex items-center gap-2 text-[#016cbb] text-xs font-bold uppercase tracking-[0.3em] mb-3">
                            <span class="w-8 h-px bg-[#016cbb]"></span>
                            About Us
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black uppercase tracking-tight text-slate-950 leading-tight">
                            Powering Homes &<br />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-[#016cbb]">Businesses</span>
                        </h2>
                        <p class="mt-4 text-slate-600 text-sm leading-relaxed max-w-xl">
                            Envoy Electricals is a dependable solar & electrical company built on one simple belief —
                            reliable energy should be within everyone's reach. From rooftop installations to complete
                            solar systems, retail supply, and technical training, we deliver clean power with genuine
                            products, skilled hands, and honest prices.
                        </p>

                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 w-6 h-6 rounded-lg bg-[#016cbb]/10 text-[#016cbb] flex items-center justify-center shrink-0">
                                    <i class="bi bi-patch-check-fill text-sm"></i>
                                </span>
                                <p class="text-[13px] text-slate-600 leading-relaxed">
                                    <span class="font-bold text-slate-900">Certified solar installation</span> — residential, commercial and industrial systems designed to last.
                                </p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 w-6 h-6 rounded-lg bg-[#016cbb]/10 text-[#016cbb] flex items-center justify-center shrink-0">
                                    <i class="bi bi-patch-check-fill text-sm"></i>
                                </span>
                                <p class="text-[13px] text-slate-600 leading-relaxed">
                                    <span class="font-bold text-slate-900">Genuine products</span> — panels, inverters and batteries supplied with warranty and after-sales support.
                                </p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 w-6 h-6 rounded-lg bg-[#016cbb]/10 text-[#016cbb] flex items-center justify-center shrink-0">
                                    <i class="bi bi-patch-check-fill text-sm"></i>
                                </span>
                                <p class="text-[13px] text-slate-600 leading-relaxed">
                                    <span class="font-bold text-slate-900">Hands-on training</span> — practical programmes that build skilled, certified solar technicians.
                                </p>
                            </li>
                        </ul>

                        <!-- Stats row -->
                        <div class="grid grid-cols-3 gap-4 mt-8 pt-7 border-t border-slate-200">
                            <div class="relative">
                                <p class="text-2xl font-black text-slate-950">500<span class="text-[#016cbb]">+</span></p>
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 mt-1">Projects Done</p>
                            </div>
                            <div class="relative">
                                <p class="text-2xl font-black text-slate-950">25<span class="text-[#016cbb]">+</span></p>
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 mt-1">Engineers & Techs</p>
                            </div>
                            <div class="relative">
                                <p class="text-2xl font-black text-slate-950">100<span class="text-[#016cbb]">%</span></p>
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 mt-1">Warranty-Backed</p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="isContactModalOpen = true"
                            class="mt-8 inline-flex items-center gap-2.5 bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-7 py-3.5 rounded-full transition-all duration-300 shadow-md group"
                        >
                            Work With Us
                            <span class="w-7 h-7 rounded-full bg-yellow-400 text-slate-950 flex items-center justify-center group-hover:translate-x-0.5 transition-transform">
                                <i class="bi bi-arrow-right text-sm"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- 5b. SECTION: POPULAR SERVICES & PRODUCTS (Mockup Style)     -->
        <!-- ========================================================= -->
        <section id="shop" class="relative overflow-hidden bg-white">
            <!-- Top Navy Banner -->
            <div class="relative overflow-hidden bg-[#0D1527] pt-16 pb-36 sm:pt-20 sm:pb-44 px-4 sm:px-6 lg:px-8">
                <!-- Ambient glows -->
                <span class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-yellow-400/15 blur-3xl pointer-events-none"></span>
                <span class="absolute bottom-0 -left-24 w-72 h-72 rounded-full bg-[#40e0d0]/10 blur-3xl pointer-events-none"></span>
                <div class="relative max-w-7xl mx-auto flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-2 text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em] mb-3">
                            <span class="w-8 h-px bg-[#40e0d0]"></span>
                            For Your Specific Business, Making It Easy For You.
                        </span>
                        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight text-white leading-tight">
                            Popular <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-[#40e0d0]">Products</span>
                        </h2>
                    </div>
                    <Link
                        :href="route('shop')"
                        class="inline-flex items-center gap-2 justify-center px-6 py-3 rounded-full bg-white hover:bg-yellow-400 text-[#0D1527] text-sm font-bold shadow-md hover:shadow-lg transition-all duration-200"
                    >
                        View All Products
                        <i class="bi bi-arrow-right"></i>
                    </Link>
                </div>
            </div>

            <!-- Overlapping Cards & Content Container -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24 sm:-mt-28 relative z-10 pb-20 sm:pb-28">

                <!-- POPULAR PRODUCTS GRID -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="product in shopProducts"
                        :key="product.id"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-[0_10px_30px_rgba(0,0,0,0.06)] hover:shadow-[0_20px_40px_rgba(13,21,39,0.16)] hover:-translate-y-1.5 transition-all duration-300"
                    >
                        <!-- Product Image -->
                        <div class="relative h-40 sm:h-44 overflow-hidden bg-slate-100">
                            <img
                                :src="product.image.startsWith('/images/') ? product.image : `/storage/${product.image}`"
                                :alt="product.name"
                                loading="lazy"
                                class="w-full h-full object-cover object-center transition-transform duration-500 group-hover:scale-105"
                            />
                            <span class="absolute top-3 left-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-800">
                                {{ product.category }}
                            </span>
                        </div>

                        <!-- Product Body -->
                        <div class="flex flex-1 flex-col p-4 sm:p-5">
                            <h3 class="line-clamp-2 text-sm font-bold text-slate-900">{{ product.name }}</h3>
                            <div class="mt-auto flex items-end justify-between pt-4">
                                <span class="text-lg font-black text-[#016cbb]">{{ formatNaira(product.price) }}</span>
                                <Link
                                    :href="route('products.show', product.id)"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#0D1527] text-white transition-colors duration-200 hover:bg-yellow-400 hover:text-slate-950"
                                    title="View Product"
                                >
                                    <i class="bi bi-arrow-right"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                </div>
        </section>


        <!-- ========================================================= -->
        <!-- 6. SECTION 6: BEST OFFER FOR RENEWABLE ENERGY (Image 5 bottom half) -->
        <!-- ========================================================= -->
        <section id="services" class="border-t border-slate-200 bg-white relative">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-200 items-stretch">
                    <!-- Column 1: Blue Card with Background Photo & Yellow Arrow Pointer -->
                    <div class="relative bg-[#1A365D] text-white p-8 flex flex-col justify-between overflow-hidden min-h-[320px]">
                        <!-- Background Image -->
                        <div class="absolute inset-0 z-0">
                            <img
                                src="/envoy_images/solar-panels-sky.jpg"
                                alt="Solar Array"
                                class="w-full h-full object-cover opacity-35 filter brightness-90"
                            />
                            <div class="absolute inset-0 bg-gradient-to-b from-[#1A365D]/90 via-[#1A365D]/80 to-[#1A365D]/95"></div>
                        </div>

                        <!-- Card Content -->
                        <div class="relative z-10">
                            <h3 class="text-2xl sm:text-3xl font-black uppercase tracking-wide leading-tight text-white">
                                BEST OFFER FOR<br />RENEWABLE<br />ENERGY
                            </h3>
                        </div>

                        <div class="relative z-10 pt-8">
                            <button
                                type="button"
                                @click="isContactModalOpen = true"
                                class="text-xs font-bold uppercase tracking-widest text-yellow-400 hover:text-yellow-300 flex items-center gap-2"
                            >
                                Inquire Today <span>→</span>
                            </button>
                        </div>

                        <!-- Yellow Triangular Pointer Arrow on Right Border -->
                        <div
                            class="hidden lg:block absolute -right-3 top-1/2 -translate-y-1/2 w-0 h-0 border-t-[12px] border-t-transparent border-b-[12px] border-b-transparent border-l-[14px] border-l-yellow-400 z-20"
                        ></div>
                    </div>

                    <!-- Column 2: Turbine Services -->
                    <div class="p-8 flex flex-col justify-between min-h-[320px] bg-white hover:bg-slate-50 transition-colors">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Turbine Services</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                                High-output commercial wind turbine supply, structural foundation engineering, and integrated solar-wind hybrid grid balancing.
                            </p>
                        </div>
                        <div class="pt-6">
                            <button
                                type="button"
                                @click="isContactModalOpen = true"
                                class="px-5 py-2 rounded-full border border-slate-900 text-xs sm:text-sm font-semibold text-slate-900 hover:bg-slate-900 hover:text-white transition duration-200"
                            >
                                Learn More
                            </button>
                        </div>
                    </div>

                    <!-- Column 3: Hydropower Plants (with yellow accent pointer) -->
                    <div class="p-8 flex flex-col justify-between min-h-[320px] bg-white hover:bg-slate-50 transition-colors relative">
                        <!-- Yellow Triangle Pointer at top-right -->
                        <div
                            class="hidden lg:block absolute right-0 top-12 w-0 h-0 border-t-[10px] border-t-transparent border-b-[10px] border-b-transparent border-r-[12px] border-r-yellow-400"
                        ></div>

                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Hydropower Plants</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                                Sustainable run-of-river mini-hydro installations generating uninterrupted 24-hour clean baseload electricity for rural and industrial zones.
                            </p>
                        </div>
                        <div class="pt-6">
                            <button
                                type="button"
                                @click="isContactModalOpen = true"
                                class="px-5 py-2 rounded-full border border-slate-900 text-xs sm:text-sm font-semibold text-slate-900 hover:bg-slate-900 hover:text-white transition duration-200"
                            >
                                Learn More
                            </button>
                        </div>
                    </div>

                    <!-- Column 4: Solar Panel Services -->
                    <div class="p-8 flex flex-col justify-between min-h-[320px] bg-white hover:bg-slate-50 transition-colors relative">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Solar Panel Services</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                                Full-scope turnkey solar engineering, procurement, construction (EPC), energy audits, and continuous utility monitoring services.
                            </p>
                        </div>
                        <div class="pt-6">
                            <button
                                type="button"
                                @click="isContactModalOpen = true"
                                class="px-5 py-2 rounded-full border border-slate-900 text-xs sm:text-sm font-semibold text-slate-900 hover:bg-slate-900 hover:text-white transition duration-200"
                            >
                                Learn More
                            </button>
                        </div>

                        <!-- Bottom Right Yellow Angled Block -->
                        <div class="hidden lg:block absolute bottom-0 right-0 w-32 h-6 bg-yellow-400 [clip-path:polygon(20%_0,100%_0,100%_100%,0_100%)]"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- 7. SECTION 7: PROCESS WORKFLOW (Image 1 from user) -->
        <!-- ========================================================= -->
        <section class="py-20 sm:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative bg-white overflow-hidden">
            <!-- Continuous Curved Dashed Line connecting circles (desktop/tablet) -->
            <svg
                class="absolute top-[38%] left-0 w-full h-32 -translate-y-1/2 pointer-events-none hidden md:block"
                viewBox="0 0 1200 120"
                fill="none"
                preserveAspectRatio="none"
            >
                <path
                    d="M 150,65 C 280,115 360,15 450,65 C 570,115 670,15 750,65 C 870,115 970,25 1050,65"
                    stroke="#94A3B8"
                    stroke-width="1.8"
                    stroke-dasharray="6 6"
                    fill="none"
                />
            </svg>

            <!-- 4 Circles Grid matching 02, 03, 04, 01 in mockup -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8 text-center relative z-10">
                <div
                    v-for="step in workflowSteps"
                    :key="step.title"
                    class="flex flex-col items-center group"
                >
                    <!-- Circular Node with Green Number Badge -->
                    <div class="relative w-44 h-44 sm:w-52 sm:h-52 rounded-full p-2 bg-white shadow-xl border border-slate-100 mb-6">
                        <!-- Numbered Green Badge -->
                        <div
                            class="absolute top-1 left-2 sm:top-2 sm:left-3 w-10 h-10 rounded-full bg-[#43A047] text-white font-extrabold text-sm flex items-center justify-center shadow-md border-2 border-white z-20"
                        >
                            {{ step.badge }}
                        </div>

                        <!-- Circular Photo -->
                        <div class="w-full h-full rounded-full overflow-hidden">
                            <img
                                :src="step.image"
                                :alt="step.title"
                                class="w-full h-full object-cover object-center group-hover:scale-110 transition-transform duration-500"
                            />
                        </div>
                    </div>

                    <!-- Step Title -->
                    <h3 class="text-xl sm:text-2xl font-bold text-[#0D1527] mb-2.5 tracking-tight">
                        {{ step.title }}
                    </h3>

                    <!-- Step Description -->
                    <p class="text-xs sm:text-sm text-slate-500 max-w-[230px] mx-auto leading-relaxed font-normal">
                        {{ step.description }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- FAQ (Frequently Asked Questions) -->
        <!-- ========================================================= -->
        <section id="faq" class="relative py-20 sm:py-28 px-4 sm:px-6 lg:px-8 bg-[#FAF8F2] overflow-hidden">
            <div class="absolute -bottom-24 -left-16 w-72 h-72 rounded-full bg-yellow-300/20 blur-3xl pointer-events-none"></div>
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-5 gap-12 lg:gap-16 relative">
                <!-- Left: Intro + Support card -->
                <div class="lg:col-span-2">
                    <span class="inline-flex items-center gap-2 text-[#016cbb] text-xs font-bold uppercase tracking-[0.3em] mb-4">
                        <span class="w-8 h-px bg-[#016cbb]"></span>
                        Need Answers?
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black uppercase tracking-tight text-slate-950 leading-tight">
                        Frequently Asked<br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-[#016cbb]">Questions</span>
                    </h2>
                    <p class="mt-5 text-slate-600 text-sm sm:text-base leading-relaxed max-w-md">
                        Quick answers to the questions we hear most. Can't find yours?
                        Our engineers are one message away.
                    </p>

                    <div class="mt-8 bg-[#0D1527] rounded-3xl p-7 relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-yellow-400/20 blur-2xl pointer-events-none"></div>
                        <span class="relative inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-yellow-400 text-slate-950 mb-4">
                            <i class="bi bi-chat-quote text-2xl"></i>
                        </span>
                        <h3 class="relative text-white font-black text-lg uppercase tracking-wide">Still Have Questions?</h3>
                        <p class="relative mt-2 text-slate-400 text-sm leading-relaxed">
                            Get honest, jargon-free answers from our team — usually within 24 hours.
                        </p>
                        <button
                            type="button"
                            @click="isContactModalOpen = true"
                            class="relative mt-5 inline-flex items-center gap-2.5 bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-bold px-6 py-3 rounded-full transition-all duration-300"
                        >
                            Ask A Question
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Right: Accordion -->
                <div class="lg:col-span-3">
                    <div class="space-y-4">
                        <div
                            v-for="(f, i) in faqs"
                            :key="f.q"
                            class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300"
                        >
                            <button
                                type="button"
                                @click="openFaq = openFaq === i ? -1 : i"
                                class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left"
                            >
                                <span class="font-bold text-slate-900 text-sm sm:text-base leading-snug">
                                    <span class="text-yellow-500 font-black mr-2">{{ String(i + 1).padStart(2, '0') }}</span>
                                    {{ f.q }}
                                </span>
                                <span
                                    class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300"
                                    :class="openFaq === i ? 'bg-yellow-400 text-slate-950 rotate-45' : 'bg-[#0D1527] text-white'"
                                >
                                    <i class="bi bi-plus text-lg"></i>
                                </span>
                            </button>
                            <div v-if="openFaq === i" class="px-6 pb-6">
                                <p class="text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4">{{ f.a }}</p>
                            </div>
                        </div>
                    </div>

                    <p class="mt-6 text-sm text-slate-500">
                        Looking for something specific?
                        <Link :href="route('contact')" class="font-bold text-slate-900 underline decoration-yellow-400 decoration-2 underline-offset-4 hover:text-yellow-500 transition">
                            Visit our contact page
                        </Link>
                    </p>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- 8. FOOTER                                                  -->
        <!-- ========================================================= -->
        <footer id="contact" class="relative bg-[#111827] text-white overflow-hidden">
            <!-- Subtle background: gradient mesh + fine cross-hatch -->
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="absolute inset-0 bg-gradient-to-br from-[#0D1527] via-[#111827] to-[#0F1A2E]"></div>
                <div class="absolute inset-0 opacity-[0.035]" style="background-image: repeating-linear-gradient(0deg,transparent,transparent 39px,rgba(255,255,255,.08) 39px,rgba(255,255,255,.08) 40px), repeating-linear-gradient(90deg,transparent,transparent 39px,rgba(255,255,255,.08) 39px,rgba(255,255,255,.08) 40px);"></div>
                <!-- Corner glow accents -->
                <div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full bg-yellow-400/[0.04] blur-[100px]"></div>
                <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] rounded-full bg-[#40e0d0]/[0.04] blur-[100px]"></div>
            </div>

            <!-- CTA Section -->
            <div class="relative max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 pt-14 pb-14 z-10">
                <div class="flex flex-col lg:flex-row items-center lg:items-center justify-between gap-10">
                    <div class="lg:max-w-lg text-center lg:text-left">
                        <div class="inline-flex items-center gap-2.5 mb-5">
                            <span class="w-10 h-[2px] bg-[#40e0d0]"></span>
                            <span class="text-[#40e0d0] text-[11px] font-bold uppercase tracking-[0.25em]">Free &amp; No-Obligation</span>
                        </div>
                        <h2 class="text-[26px] sm:text-[32px] lg:text-[38px] font-black uppercase leading-[1.1] tracking-tight">
                            Get Your Free<br class="hidden sm:block" /> Solar Estimate
                        </h2>
                        <p class="mt-3 text-slate-400 text-sm leading-relaxed max-w-md mx-auto lg:mx-0">Tell us about your energy needs and our engineers will design the perfect system for you — completely free.</p>
                    </div>

                    <div class="w-full max-w-md">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/10 rounded-2xl p-3 flex items-center gap-3">
                            <div class="flex-1 pl-3">
                                <input
                                    type="text"
                                    v-model="estimateInput"
                                    placeholder="Enter your phone or email"
                                    class="w-full bg-transparent border-0 text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-0 py-1"
                                    @keyup.enter="handleEstimateSubmit"
                                />
                            </div>
                            <button
                                type="button"
                                @click="handleEstimateSubmit"
                                class="shrink-0 px-6 py-3 rounded-xl bg-gradient-to-r from-yellow-400 to-amber-400 text-slate-950 text-sm font-bold hover:brightness-110 transition-all duration-200 shadow-lg shadow-yellow-400/20"
                            >
                                Get Started
                            </button>
                        </div>
                        <p class="mt-3 text-center text-[11px] text-slate-500">Our engineers typically reply within 24 hours.</p>
                    </div>
                </div>
            </div>

            <!-- Thin accent line -->
            <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
                <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
            </div>

            <!-- Main Footer Grid -->
            <div class="relative max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 pt-12 pb-12 z-10">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8">

                    <!-- Column 1: Brand (spans 4 cols) -->
                    <div class="lg:col-span-4">
                        <p class="text-slate-400 text-[13px] leading-relaxed mb-7 max-w-xs">
                            Clean, affordable, and sustainable solar energy. We design, supply, install, and maintain quality solar power systems across Nigeria.
                        </p>
                        <!-- Newsletter -->
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-2">Newsletter</label>
                        <div class="flex items-center gap-2 max-w-xs">
                            <input
                                v-model="newsletterEmail"
                                type="email"
                                placeholder="Your email"
                                class="flex-1 min-w-0 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white placeholder:text-slate-600 focus:outline-none focus:border-yellow-400/50 transition"
                                @keyup.enter="subscribeNewsletter"
                            />
                            <button
                                type="button"
                                @click="subscribeNewsletter"
                                class="shrink-0 h-[38px] px-4 rounded-lg bg-yellow-400 text-slate-950 text-xs font-bold hover:bg-yellow-300 transition"
                                title="Subscribe"
                            >
                                Join
                            </button>
                        </div>
                        <p v-if="newsletterSubscribed" class="mt-2 text-xs font-semibold text-[#40e0d0]">You're subscribed — welcome aboard!</p>
                    </div>

                    <!-- Column 2: Explore (spans 3 cols) -->
                    <div class="lg:col-span-3">
                        <h4 class="text-xs font-bold text-white mb-5 uppercase tracking-[0.15em]">Explore</h4>
                        <ul class="space-y-3">
                            <li><Link :href="route('shop')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Shop Solar Products</Link></li>
                            <li><Link :href="route('packages')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Solar Packages</Link></li>
                            <li><Link :href="route('projects')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Our Projects</Link></li>
                            <li><Link :href="route('calculator')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Load Calculator</Link></li>
                            <li><Link :href="route('about')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">About Us</Link></li>
                            <li><Link :href="route('contact')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Contact Us</Link></li>
                        </ul>
                    </div>

                    <!-- Column 3: Contact (spans 3 cols) -->
                    <div class="lg:col-span-3">
                        <h4 class="text-xs font-bold text-white mb-5 uppercase tracking-[0.15em]">Contact</h4>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6 19.79 19.79 0 0 1 1.62 5.07 2 2 0 0 1 3.59 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.6a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 18z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Phone</p>
                                    <p class="text-[13px] text-slate-300">+234 809 708 9259</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-[#40e0d0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                </span>
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Email</p>
                                    <p class="text-[13px] text-slate-300">hello@envoyelectric.com.ng</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                </span>
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Address</p>
                                    <p class="text-[13px] text-slate-300 leading-relaxed">Shop 1, Peace Avenue Junction,<br>opp Goddy Royal Hotel, Futa Southgate Road, Akure</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 4: Follow Us + Hours (spans 2 cols) -->
                    <div class="lg:col-span-2">
                        <h4 class="text-xs font-bold text-white mb-5 uppercase tracking-[0.15em]">Follow Us</h4>
                        <div class="flex items-center gap-2 mb-8">
                            <a href="#" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all duration-200" title="Instagram">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="#" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all duration-200" title="X / Twitter">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                            <a href="#" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all duration-200" title="Facebook">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all duration-200" title="LinkedIn">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            </a>
                        </div>
                        <h4 class="text-xs font-bold text-white mb-3 uppercase tracking-[0.15em]">Open Hours</h4>
                        <div class="space-y-1">
                            <p class="text-[13px] text-slate-300">Mon – Fri: <span class="text-white font-medium">8 AM – 6 PM</span></p>
                            <p class="text-[13px] text-slate-300">Saturday: <span class="text-white font-medium">9 AM – 4 PM</span></p>
                            <p class="text-[13px] text-slate-500">Sunday: Closed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="relative z-10 border-t border-white/[0.06]">
                <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-[11px] text-slate-600">&copy; 2026 Envoy Electric. All rights reserved.</p>
                    <nav class="flex items-center gap-5 text-[11px] text-slate-500">
                        <a href="#hero" class="hover:text-white transition">Back to top</a>
                        <Link :href="route('about')" class="hover:text-white transition">Privacy</Link>
                        <Link :href="route('about')" class="hover:text-white transition">Terms</Link>
                    </nav>
                </div>
            </div>
        </footer>

        <!-- ========================================================= -->
        <!-- CONSULTATION / "LET'S TALK" MODAL DIALOG -->
        <!-- ========================================================= -->
        <div
            v-if="isContactModalOpen"
            class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="isContactModalOpen = false"
        >
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 relative">
                <!-- Close Button -->
                <button
                    type="button"
                    @click="isContactModalOpen = false"
                    class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-xl font-bold w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100"
                >
                    ✕
                </button>

                <div class="mb-6">
                    <div class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold uppercase rounded-full mb-2">
                        Get In Touch
                    </div>
                    <h3 class="text-2xl font-black text-slate-900">Request Solar Consultation</h3>
                    <p class="text-sm text-slate-600 mt-1">
                        Connect directly with our senior solar engineers for customized project planning and pricing.
                    </p>
                </div>

                <div v-if="contactSuccess" class="py-8 text-center space-y-3">
                    <div class="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto text-2xl">
                        ✓
                    </div>
                    <h4 class="text-lg font-bold text-slate-900">Consultation Request Received!</h4>
                    <p class="text-sm text-slate-600">A Factorex solar specialist will reach out within 24 business hours.</p>
                </div>

                <form v-else @submit.prevent="submitContact" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Full Name</label>
                        <input
                            v-model="contactForm.name"
                            required
                            type="text"
                            placeholder="Your full name"
                            class="w-full px-4 py-2.5 text-sm rounded-lg border border-slate-300 focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Email Address</label>
                            <input
                                v-model="contactForm.email"
                                required
                                type="email"
                                placeholder="name@company.com"
                                class="w-full px-4 py-2.5 text-sm rounded-lg border border-slate-300 focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Phone Number</label>
                            <input
                                v-model="contactForm.phone"
                                required
                                type="tel"
                                placeholder="+234..."
                                class="w-full px-4 py-2.5 text-sm rounded-lg border border-slate-300 focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Service Required</label>
                        <select
                            v-model="contactForm.service"
                            class="w-full px-4 py-2.5 text-sm rounded-lg border border-slate-300 focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 bg-white"
                        >
                            <option value="Solar Panel Installation">Solar Panel Installation</option>
                            <option value="Battery Storage Solution">Battery Storage Solution</option>
                            <option value="Solar Inverter Installation">Solar Inverter Installation</option>
                            <option value="Turbine Services">Turbine Services</option>
                            <option value="Hydropower Plants">Hydropower Plants</option>
                            <option value="Maintenance & Energy Audit">Maintenance & Energy Audit</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Project Details / Message</label>
                        <textarea
                            v-model="contactForm.message"
                            rows="3"
                            placeholder="Tell us about your property, estimated power load, or requirements..."
                            class="w-full px-4 py-2.5 text-sm rounded-lg border border-slate-300 focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500"
                        ></textarea>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full bg-[#0D1527] hover:bg-slate-800 text-white font-bold py-3 rounded-lg text-sm transition shadow-lg flex items-center justify-center gap-2"
                        >
                            <span>Submit Request</span>
                            <span class="text-yellow-400">→</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cart Toast -->
        <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div
                v-if="cartToastVisible"
                class="fixed bottom-6 right-6 z-[60] flex items-center gap-3 bg-white shadow-2xl border border-slate-100 rounded-xl py-3 px-4 max-w-xs"
            >
                <span class="w-9 h-9 shrink-0 rounded-full bg-[#47B247] text-white flex items-center justify-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-900">Added to Cart</p>
                    <p class="text-xs text-slate-500 truncate">{{ cartToastName }}</p>
                </div>
            </div>
        </transition>
    </div>
</template>

<style>
@keyframes hero-kenburns {
    from {
        transform: scale(1.05);
    }
    to {
        transform: scale(1.18);
    }
}

.hero-kenburns {
    animation: hero-kenburns 9s ease-out forwards;
}

@keyframes hero-progress {
    from {
        width: 0%;
    }
    to {
        width: 100%;
    }
}

.hero-progress-fill {
    animation: hero-progress 8s linear forwards;
}

@keyframes hero-reveal {
    from {
        opacity: 0;
        transform: translateY(28px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-reveal {
    animation: hero-reveal 0.9s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.hero-delay-1 {
    animation-delay: 0.12s;
}

.hero-delay-2 {
    animation-delay: 0.28s;
}

.hero-delay-3 {
    animation-delay: 0.44s;
}

@keyframes hero-title-line {
    0% {
        opacity: 0;
        transform: translateY(46px) rotate(2deg) scale(1.04);
    }
    60% {
        opacity: 1;
    }
    100% {
        opacity: 1;
        transform: translateY(0) rotate(0) scale(1);
    }
}

.hero-title-line {
    animation: hero-title-line 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes hero-grad-pan {
    0% {
        background-position: 0% 50%;
    }
    100% {
        background-position: 200% 50%;
    }
}

.hero-title-grad {
    background-size: 200% auto;
    animation: hero-title-line 0.85s cubic-bezier(0.22, 1, 0.36, 1) both, hero-grad-pan 7s linear infinite 2s;
}

.hero-title-delay-1 {
    animation-delay: 0.16s;
}

.hero-title-delay-2 {
    animation-delay: 0.32s;
}

@keyframes sun-spin {
    to {
        transform: rotate(360deg);
    }
}

.sun-spin {
    animation: sun-spin 14s linear infinite;
    transform-origin: center;
}

@keyframes beam-pulse {
    0%,
    100% {
        opacity: 0.45;
    }
    50% {
        opacity: 1;
    }
}

.beam-pulse {
    animation: beam-pulse 3s ease-in-out infinite;
}

@keyframes beam-halo {
    0%,
    100% {
        opacity: 0.35;
        transform: scale(0.9);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.15);
    }
}

.beam-halo {
    animation: beam-halo 4s ease-in-out infinite;
}

@keyframes wdw-spin {
    to {
        transform: rotate(360deg);
    }
}

.wdw-spin {
    animation: wdw-spin 14s linear infinite;
    transform-origin: center;
}

@keyframes sol-rays-spin {
    from {
        transform: translateX(-50%) rotate(0deg);
    }
    to {
        transform: translateX(-50%) rotate(360deg);
    }
}

.sol-rays {
    animation: sol-rays-spin 40s linear infinite;
    transform-origin: bottom center;
}

@keyframes sol-pulse {
    0%,
    100% {
        transform: translateX(-50%) scale(0.9);
        opacity: 0.6;
    }
    50% {
        transform: translateX(-50%) scale(1.06);
        opacity: 0;
    }
}

.sol-pulse {
    animation: sol-pulse 2.6s ease-in-out infinite;
}

.sol-pulse-slow {
    animation: sol-pulse 4.2s ease-out infinite;
}
</style>
