<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

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
    whatsappNumber: {
        type: String,
        default: '',
    },
});

// Featured products for the showcase grid (real products added by admin)
const shopProducts = computed(() => props.featuredProducts);

// Contact Modal state
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
        image: '/images/landing/hero_ng_1.jpg',
    },
    {
        badge: 'SMART SOLAR TECHNOLOGY',
        titleLine1: 'LIGHT UP',
        titleLine2: 'EVERYTHING',
        subtitle:
            "Smart inverters, sleek panels, and battery storage in perfect sync — clean energy so seamless you'll forget the grid ever existed.",
        cta: { label: 'Shop the Range', href: '#shop' },
        image: '/images/landing/hero_ng_2.jpg',
    },
    {
        badge: 'ENGINEERED FOR EXCELLENCE',
        titleLine1: 'ENERGY',
        titleLine2: 'INDEPENDENCE',
        subtitle:
            'Go off-grid or stay hybrid — our lithium storage and solar systems cut electricity costs by up to 90% and keep you running through any outage.',
        cta: { label: 'Size My System', href: '/calculator' },
        image: '/images/landing/hero_ng_3.jpg',
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

// Scroll-reveal observer
function observeReveal() {
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('.reveal').forEach((el) => el.classList.add('reveal-visible'));
        return;
    }
    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal-visible');
                    io.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
    );
    document.querySelectorAll('.reveal').forEach((el) => io.observe(el));
}

onMounted(() => {
    restartHeroTimer();
    observeReveal();
    updateSolutionPerView();
    window.addEventListener('resize', updateSolutionPerView);
    startSolutionAutoplay();
});
onUnmounted(() => {
    if (heroTimer) clearInterval(heroTimer);
    window.removeEventListener('resize', updateSolutionPerView);
    stopSolutionAutoplay();
});

// Section 3: Solar Solutions carousel
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
    {
        id: 5,
        title: 'System Design & Engineering',
        description:
            'Custom solar system design backed by load analysis, energy audits, and 3D site modelling — delivering the perfect panel and inverter configuration for your exact power profile.',
        image: '/images/landing/engineers_blueprint.jpg',
        icon: 'design',
    },
    {
        id: 6,
        title: 'Solar Water Pumping',
        description:
            'Submersible and surface solar water pumps for boreholes, farms, and irrigation — reliable, fuel-free water supply driven purely by free solar energy, day in and day out.',
        image: '/images/landing/hydro_plant.jpg',
        icon: 'pump',
    },
    {
        id: 7,
        title: 'Commercial & Industrial Solar',
        description:
            'Tailored solar rooftop and carport systems for businesses and factories that slash operating costs and lock in predictable, greener electricity for decades.',
        image: '/images/landing/solar_city.jpg',
        icon: 'building',
    },
    {
        id: 8,
        title: 'Solar Farm & Utility Projects',
        description:
            'Ground-mount arrays and large-scale solar farms engineered for communities and enterprises — from feasibility study and land assessment to commissioning and grid integration.',
        image: '/images/landing/solar_farm_wide.jpg',
        icon: 'farm',
    },
];

const solutionPerView = ref(4);
const activeSolutionIndex = ref(0);
const solutionWrapJump = ref(false);
const solutionAutoplayPaused = ref(false);
let solutionAutoplay = null;
let solutionSwiping = false;
let solutionStartX = 0;

const solutionIndexLimit = computed(() => Math.max(0, solutions.length - solutionPerView.value));
const solutionPages = computed(() => Math.ceil(solutions.length / solutionPerView.value));
const solutionPageIndex = computed(() =>
    Math.min(Math.floor(activeSolutionIndex.value / solutionPerView.value), solutionPages.value - 1)
);

function updateSolutionPerView() {
    const w = window.innerWidth;
    let perView = 4;
    if (w < 640) perView = 1;
    else if (w < 1024) perView = 2;
    solutionPerView.value = perView;
    activeSolutionIndex.value = Math.min(activeSolutionIndex.value, Math.max(0, solutions.length - perView));
}

function stopSolutionAutoplay() {
    if (solutionAutoplay) {
        clearInterval(solutionAutoplay);
        solutionAutoplay = null;
    }
}

function startSolutionAutoplay() {
    stopSolutionAutoplay();
    solutionAutoplay = setInterval(nextSolution, 6000);
}

function restartSolutionAutoplay() {
    if (!solutionAutoplayPaused.value) startSolutionAutoplay();
}

function nextSolution() {
    if (activeSolutionIndex.value >= solutionIndexLimit.value) {
        solutionWrapJump.value = true;
        activeSolutionIndex.value = 0;
        requestAnimationFrame(() => {
            solutionWrapJump.value = false;
        });
    } else {
        activeSolutionIndex.value += 1;
    }
    restartSolutionAutoplay();
}

function prevSolution() {
    if (activeSolutionIndex.value <= 0) {
        solutionWrapJump.value = true;
        activeSolutionIndex.value = solutionIndexLimit.value;
        requestAnimationFrame(() => {
            solutionWrapJump.value = false;
        });
    } else {
        activeSolutionIndex.value -= 1;
    }
    restartSolutionAutoplay();
}

function goToSolutionPage(page) {
    activeSolutionIndex.value = Math.min(page * solutionPerView.value, solutionIndexLimit.value);
    restartSolutionAutoplay();
}

function solutionHover(active) {
    solutionAutoplayPaused.value = active;
    if (active) stopSolutionAutoplay();
    else restartSolutionAutoplay();
}

function solutionPointerDown(e) {
    solutionSwiping = true;
    solutionStartX = e.clientX;
    stopSolutionAutoplay();
}

function solutionPointerMove(e) {
    if (!solutionSwiping) return;
    const dx = e.clientX - solutionStartX;
    if (Math.abs(dx) > 45) {
        solutionSwiping = false;
        if (dx < 0) nextSolution();
        else prevSolution();
    }
}

function solutionPointerUp() {
    solutionSwiping = false;
    if (!solutionAutoplayPaused.value) startSolutionAutoplay();
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

const formatNaira = (value) => '₦' + Number(value).toLocaleString('en-NG');

// Workflow Process Steps (ordered 01 → 04)
const workflowSteps = [
    {
        badge: '01',
        title: 'Project Planning',
        description: 'We begin every project with a thorough site survey and energy audit, sizing the ideal solar system for your specific power needs and budget.',
        image: '/envoy_images/workflow-team.jpg',
    },
    {
        badge: '02',
        title: 'Initial Installation',
        description: 'Our certified technicians carry out safe, precise solar system installations — from rooftop panel mounting to inverter and battery wiring, done right the first time.',
        image: '/envoy_images/gallery-solar-field.jpg',
    },
    {
        badge: '03',
        title: 'Quality Control',
        description: 'Every installation undergoes a rigorous multi-point inspection, performance testing, and safety certification before handover to the customer.',
        image: '/envoy_images/workflow-laptop.jpg',
    },
    {
        badge: '04',
        title: 'Repair \u0026 Monitoring',
        description: 'We provide ongoing system health monitoring, remote diagnostics, and prompt repair services to keep your solar investment running at peak efficiency.',
        image: '/envoy_images/workflow-tablet.jpg',
    },
];

// Free Estimate CTA state (Image 2)
function handleEstimateSubmit(value) {
    if (value) {
        contactForm.value.message = `Free Solar Estimate request: ${value}`;
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
    <Head title="Envoy Electricals — Premium Solar Panels, Inverters & Battery Storage in Nigeria" />

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
            <div class="text-center max-w-5xl mx-auto mb-16 reveal">
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-[40px] font-black uppercase tracking-tight text-slate-950 leading-tight sm:leading-snug">
                    QUALITY SOLAR GOODS<br />& SKILLED SERVICE
                </h2>
            </div>

            <!-- 2-Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-stretch">
                <!-- Left / Center Column: Slanted Mosaic Photographic Collage (lg:col-span-8) -->
                <div class="lg:col-span-8 relative min-h-[380px] sm:min-h-[500px] flex flex-col justify-center reveal reveal-left">
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
                <div class="lg:col-span-4 flex flex-col rounded-xl overflow-hidden shadow-sm border border-slate-200/80 reveal reveal-right" style="transition-delay: 120ms;">
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
                            Envoy Electricals brings expert guidance and trusted technology to every project. Whether you want to reduce your electricity bills, increase your property value, or achieve complete energy independence, we deliver customised solar solutions tailored to your exact energy needs.
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
                <div class="flex items-center justify-between mb-12 sm:mb-14 reveal">
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

                <!-- Sliding Solution Cards -->
                <div
                    class="-mx-3 reveal"
                    @mouseenter="solutionHover(true)"
                    @mouseleave="solutionHover(false)"
                >
                    <div
                        class="overflow-hidden select-none touch-pan-y"
                        @pointerdown="solutionPointerDown"
                        @pointermove="solutionPointerMove"
                        @pointerup="solutionPointerUp"
                        @pointercancel="solutionPointerUp"
                    >
                        <div
                            class="flex transition-transform duration-500 ease-out"
                            :class="solutionWrapJump ? 'transition-none' : ''"
                            :style="{ transform: `translateX(-${(activeSolutionIndex * 100) / solutionPerView}%)` }"
                        >
                            <div
                                v-for="(sol, solIdx) in solutions"
                                :key="sol.id"
                                class="shrink-0 px-3"
                                :style="{ width: `${100 / solutionPerView}%` }"
                            >
                                <div
                                    class="bg-white text-slate-900 rounded-lg flex flex-col overflow-hidden shadow-lg transition-all duration-300 hover:shadow-2xl group h-full"
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
                                            <svg v-else-if="sol.icon === 'design'" class="w-[26px] h-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                                <path d="M3 9h18M9 20V9M12 12l3 3" />
                                            </svg>
                                            <svg v-else-if="sol.icon === 'pump'" class="w-[26px] h-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M12 3s6 6.3 6 11a6 6 0 1 1-12 0C6 9.3 12 3 12 3z" />
                                                <path d="M9 14a3 3 0 0 0 3 3" stroke-width="1.4" />
                                            </svg>
                                            <svg v-else-if="sol.icon === 'building'" class="w-[26px] h-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <rect x="4" y="3" width="16" height="18" rx="1.5" />
                                                <path d="M8 21v-6h8v6M8 7h.01M12 7h.01M16 7h.01M8 11h.01M12 11h.01M16 11h.01" stroke-width="2" />
                                            </svg>
                                            <svg v-else-if="sol.icon === 'farm'" class="w-[26px] h-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <circle cx="12" cy="5.5" r="2.2" stroke-width="1.5" />
                                                <path d="M12 1.6v.7M7.2 4l.5.5M16.8 4l-.5.5" stroke-width="1.4" stroke-linecap="round" />
                                                <rect x="5" y="12" width="14" height="3.5" rx="1" />
                                                <path d="M8.5 15.5V20h7v-4.5" />
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
                                                loading="lazy"
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
                    </div>

                    <!-- Carousel dots -->
                    <div class="flex items-center justify-center gap-2 mt-9">
                        <button
                            v-for="(_, p) in solutionPages"
                            :key="p"
                            type="button"
                            :aria-label="`Go to slide ${p + 1}`"
                            :class="p === solutionPageIndex ? 'w-8 bg-yellow-400' : 'w-2.5 bg-white/25 hover:bg-white/60'"
                            class="h-2.5 rounded-full transition-all duration-300"
                            @click="goToSolutionPage(p)"
                        />
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
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 w-16 h-16 rounded-full bg-gradient-to-t from-yellow-500 via-yellow-400 to-amber-300 shadow-[0_0_40px_8px_rgba(250,204,21,0.4)]"></div>

                <!-- Pulsing halo rings -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-28 h-28 rounded-full border border-yellow-400/50 sol-pulse"></div>
                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-44 h-44 rounded-full border border-yellow-400/30 sol-pulse-slow"></div>

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
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-14 sm:mb-16 reveal">
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
                    <div class="reveal group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-[#111C36] to-[#0A1020] px-7 sm:px-8 pt-7 sm:pt-8 pb-9 sm:pb-10 flex flex-col min-h-[440px] transition-all duration-500 hover:-translate-y-2 hover:border-yellow-400/40 hover:shadow-2xl hover:shadow-yellow-400/10" style="transition-delay: 80ms;">
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
                    <div class="reveal group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-[#111C36] to-[#0A1020] px-7 sm:px-8 pt-7 sm:pt-8 pb-9 sm:pb-10 flex flex-col min-h-[440px] transition-all duration-500 hover:-translate-y-2 hover:border-[#40e0d0]/40 hover:shadow-2xl hover:shadow-[#40e0d0]/10" style="transition-delay: 160ms;">
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
                                href="/shop"
                                class="group/btn inline-flex items-center gap-2.5 rounded-full border border-[#40e0d0]/40 px-5 py-2.5 text-sm font-bold text-[#40e0d0] transition-all duration-300 hover:bg-[#40e0d0] hover:text-slate-950"
                            >
                                Buy Our Products
                                <i class="bi bi-arrow-right transition-transform duration-300 group-hover/btn:translate-x-1"></i>
                            </a>
                        </div>

                        <span class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#40e0d0]/10 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
                    </div>

                    <!-- 03 Professional Trainings -->
                    <div id="training" class="reveal group relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-[#111C36] to-[#0A1020] px-7 sm:px-8 pt-7 sm:pt-8 pb-9 sm:pb-10 flex flex-col min-h-[440px] transition-all duration-500 hover:-translate-y-2 hover:border-emerald-400/40 hover:shadow-2xl hover:shadow-emerald-400/10" style="transition-delay: 240ms;">
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
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-9 reveal">
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
                <div class="columns-1 sm:columns-2 lg:columns-3 gap-4 reveal" style="transition-delay: 100ms;">
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
        <section v-if="shopProducts.length" id="shop" class="relative overflow-hidden bg-white">
            <!-- Top Navy Banner -->
            <div class="relative overflow-hidden bg-[#0D1527] pt-16 pb-36 sm:pt-20 sm:pb-44 px-4 sm:px-6 lg:px-8">
                <!-- Ambient glows -->
                <span class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-yellow-400/15 blur-3xl pointer-events-none"></span>
                <span class="absolute bottom-0 -left-24 w-72 h-72 rounded-full bg-[#40e0d0]/10 blur-3xl pointer-events-none"></span>
                <div class="relative max-w-7xl mx-auto flex flex-col md:flex-row md:items-end md:justify-between gap-6 reveal">
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 reveal" style="transition-delay: 80ms;">
                    <div
                        v-for="(product, prodIdx) in shopProducts"
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
        <!-- 6. SECTION 6: BEST OFFER FOR SOLAR ENERGY -->
        <!-- ========================================================= -->
        <section id="services" class="border-t border-slate-200 bg-white relative">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-200 items-stretch reveal">
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
                                BEST OFFER FOR<br />SOLAR<br />ENERGY
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

                    <!-- Column 2: Battery Storage Solutions -->
                    <div class="p-8 flex flex-col justify-between min-h-[320px] bg-white hover:bg-slate-50 transition-colors">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Battery Storage Solutions</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                                Advanced lithium-ion and deep-cycle battery storage systems engineered for uninterrupted backup power, peak shaving, and complete off-grid energy independence.
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

                    <!-- Column 3: Solar Inverter Installation -->
                    <div class="p-8 flex flex-col justify-between min-h-[320px] bg-white hover:bg-slate-50 transition-colors relative">
                        <!-- Yellow Triangle Pointer at top-right -->
                        <div
                            class="hidden lg:block absolute right-0 top-12 w-0 h-0 border-t-[10px] border-t-transparent border-b-[10px] border-b-transparent border-r-[12px] border-r-yellow-400"
                        ></div>

                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Solar Inverter Installation</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                                High-efficiency hybrid, on-grid, and off-grid inverter setups configured by certified technicians to seamlessly regulate and convert DC solar power to AC electricity.
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

                    <!-- Column 4: Solar Panel Sales & Services -->
                    <div class="p-8 flex flex-col justify-between min-h-[320px] bg-white hover:bg-slate-50 transition-colors relative">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Solar Panel Sales &amp; Installation</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                                Tier-1 solar panels, inverters, and batteries plus precision rooftop and ground-mounted installations — turnkey design, procurement, and maintenance all in one place.
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
        <section id="process" class="relative py-20 sm:py-28 px-4 sm:px-6 lg:px-8 bg-white overflow-hidden">
            <!-- Ambient brand glow -->
            <div class="absolute -top-24 -right-20 w-72 h-72 rounded-full bg-[#40e0d0]/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-20 w-72 h-72 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative">
                <!-- Section Header -->
                <div class="max-w-2xl mx-auto text-center mb-14 sm:mb-20 relative z-10 reveal">
                    <span class="inline-flex items-center gap-2 text-[#016cbb] text-xs font-bold uppercase tracking-[0.3em] mb-4">
                        <span class="w-8 h-px bg-[#016cbb]"></span>
                        How We Work
                        <span class="w-8 h-px bg-[#016cbb]"></span>
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black uppercase tracking-tight text-[#0D1527] leading-tight">
                        A Proven<br class="sm:hidden" />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-[#016cbb]">4-Step Process</span>
                    </h2>
                    <p class="mt-4 text-slate-500 text-sm sm:text-base leading-relaxed max-w-xl mx-auto">
                        Every project follows the same disciplined workflow — from the first site survey to installation, quality sign-off and long-term monitoring.
                    </p>
                </div>

                <!-- Continuous Curved Dashed Line connecting circles (desktop/tablet) -->
                <svg
                    class="absolute top-[31%] left-0 w-full h-32 -translate-y-1/2 pointer-events-none hidden md:block"
                    viewBox="0 0 1200 120"
                    fill="none"
                    preserveAspectRatio="none"
                >
                    <path
                        d="M 150,65 C 280,115 360,15 450,65 C 570,115 670,15 750,65 C 870,115 970,25 1050,65"
                        stroke="#40e0d0"
                        stroke-width="1.8"
                        stroke-dasharray="6 6"
                        stroke-opacity="0.45"
                        fill="none"
                    />
                </svg>

                <!-- 4 Circles Grid in logical order -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8 text-center relative z-10 reveal" style="transition-delay: 100ms;">
                    <div
                        v-for="(step, stepIdx) in workflowSteps"
                        :key="step.title"
                        class="flex flex-col items-center group"
                        :style="{ transitionDelay: `${100 + stepIdx * 80}ms` }"
                    >
                        <!-- Circular Node with Number Badge -->
                        <div class="relative w-44 h-44 sm:w-52 sm:h-52 rounded-full p-2 bg-white shadow-xl border border-slate-100 mb-6 transition-all duration-300 group-hover:border-[#40e0d0]/60 group-hover:shadow-2xl group-hover:shadow-[#40e0d0]/10">
                            <!-- Numbered Yellow Badge -->
                            <div
                                class="absolute top-1 left-2 sm:top-2 sm:left-3 w-10 h-10 rounded-full bg-[#FACC15] text-[#0D1527] font-extrabold text-sm flex items-center justify-center shadow-md border-2 border-white z-20 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-300"
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
                        <p class="text-xs sm:text-sm text-slate-600 max-w-[230px] mx-auto leading-relaxed font-normal">
                            {{ step.description }}
                        </p>
                    </div>
                </div>

                <!-- Bottom CTA strip -->
                <div class="mt-14 sm:mt-16 flex justify-center">
                    <button
                        type="button"
                        @click="isContactModalOpen = true"
                        class="group/btn inline-flex items-center gap-3 rounded-full bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-7 py-3.5 shadow-lg shadow-[#0D1527]/20 transition-all duration-300"
                    >
                        Start Your Solar Project
                        <i class="bi bi-arrow-right text-[#FACC15] transition-transform duration-300 group-hover/btn:translate-x-1"></i>
                    </button>
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
                <div class="lg:col-span-2 reveal reveal-left" style="transition-delay: 80ms;">
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
                <div class="lg:col-span-3 reveal reveal-right">
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
        <SiteFooter @estimate="handleEstimateSubmit" />
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
                        Connect directly with our senior solar engineers for customised project planning and pricing.
                    </p>
                </div>

                <div v-if="contactSuccess" class="py-8 text-center space-y-3">
                    <div class="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto text-2xl">
                        ✓
                    </div>
                    <h4 class="text-lg font-bold text-slate-900">Consultation Request Received!</h4>
                    <p class="text-sm text-slate-600">An Envoy Electricals solar specialist will reach out within 24 business hours.</p>
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
                            <option value="Electrical Products &amp; Supply">Electrical Products &amp; Supply</option>
                            <option value="Technician Training">Technician Training</option>
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

/* ---- Scroll-reveal animations ---- */
.reveal {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.75s cubic-bezier(0.22, 1, 0.36, 1), transform 0.75s cubic-bezier(0.22, 1, 0.36, 1);
    will-change: opacity, transform;
}
.reveal.reveal-left  { transform: translateX(-36px); }
.reveal.reveal-right { transform: translateX(36px); }
.reveal.reveal-zoom  { transform: scale(0.92); }

.reveal.reveal-visible {
    opacity: 1;
    transform: none;
    will-change: auto;
}
</style>
