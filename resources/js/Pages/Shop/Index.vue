<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { bumpCart } from '@/lib/cart';
import { naira, stockStatusLabel } from '@/lib/format';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    priceBounds: { type: Object, default: () => ({ min: 0, max: 1000000 }) },
    totalProducts: { type: Number, default: 0 },
});

const state = reactive({
    search: props.filters.search ?? '',
    category: Number(props.filters.category ?? '') || '',
    minPrice: props.filters.min_price ?? '',
    maxPrice: props.filters.max_price ?? '',
    availability: props.filters.availability ?? '',
    sort: props.filters.sort ?? '',
});

const hasActiveFilters = computed(() =>
    state.category
    || state.minPrice
    || state.maxPrice
    || state.availability
    || state.search
);

function buildParams(overrides = {}) {
    const p = {};
    if (state.search) p.search = state.search;
    if (state.category) p.category = state.category;
    if (state.minPrice) p.min_price = state.minPrice;
    if (state.maxPrice) p.max_price = state.maxPrice;
    if (state.availability) p.availability = state.availability;
    if (state.sort) p.sort = state.sort;
    return { ...p, ...overrides };
}

function apply(overrides = {}) {
    router.get('/shop', buildParams(overrides), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function applySearch() {
    apply({ page: 1 });
}

function selectCategory(id) {
    state.category = state.category === id ? '' : id;
    apply({ page: 1 });
}

function setAvailability(v) {
    state.availability = state.availability === v ? '' : v;
    apply({ page: 1 });
}

function setSort(v) {
    state.sort = v;
    apply({ page: 1 });
}

function applyPrice() {
    apply({ page: 1 });
}

function clearFilters() {
    Object.assign(state, {
        search: '', category: '', minPrice: '', maxPrice: '', availability: '', sort: '',
    });
    router.get('/shop', {}, { preserveState: true, preserveScroll: true, replace: true });
}

const chips = computed(() => {
    const c = [];
    if (state.search) c.push({ key: 'search', label: `"${state.search}"` });
    const cat = props.categories.find((x) => x.id === state.category);
    if (cat) c.push({ key: 'category', label: cat.name });
    if (state.minPrice) c.push({ key: 'min_price', label: `From ${naira(state.minPrice)}` });
    if (state.maxPrice) c.push({ key: 'max_price', label: `Up to ${naira(state.maxPrice)}` });
    if (state.availability) c.push({ key: 'availability', label: state.availability === 'in_stock' ? 'In stock' : 'Out of stock' });
    return c;
});

function removeChip(key) {
    if (key === 'search') state.search = '';
    if (key === 'category') state.category = '';
    if (key === 'min_price') state.minPrice = '';
    if (key === 'max_price') state.maxPrice = '';
    if (key === 'availability') state.availability = '';
    apply();
}

const activeFilterCount = computed(() => chips.value.length);

const isFilterDrawerOpen = ref(false);
const priceExpanded = ref(true);
const catExpanded = ref(true);

function imageFor(product) {
    const img = (product.images || []).find((i) => i.is_featured) || (product.images || [])[0];
    return img ? `/storage/${img.path}` : '/images/landing/solar_panels_sky.jpg';
}

function addToCart(product) {
    bumpCart();
}

const pricePresets = computed(() => {
    const m = props.priceBounds.max || 1000000;
    return [
        { label: `Under ${naira(m / 4)}`, from: '', to: Math.round(m / 4) },
        { label: `${naira(m / 4)} – ${naira(m / 2)}`, from: Math.round(m / 4), to: Math.round(m / 2) },
        { label: `${naira(m / 2)} – ${naira(m)}`, from: Math.round(m / 2), to: '' },
        { label: `${naira(m)}+`, from: m, to: '' },
    ];
});

function presetActive(p) {
    return String(state.minPrice ?? '') === String(p.from ?? '') && String(state.maxPrice ?? '') === String(p.to ?? '');
}

function applyPreset(p) {
    state.minPrice = p.from;
    state.maxPrice = p.to;
    apply({ page: 1 });
}

const heroFeatured = computed(() => props.products.data.slice(0, 2));
</script>

<template>
    <!-- ======================= HERO ======================= -->
    <section class="relative bg-[#0D1527] overflow-hidden">
        <!-- Background accents -->
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute inset-0" style="background-image:linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px); background-size:56px 56px;"></div>
            <div class="absolute -top-40 -right-32 w-[560px] h-[560px] rounded-full bg-yellow-400/[0.07] blur-[110px]"></div>
            <div class="absolute top-1/3 -left-40 w-[480px] h-[480px] rounded-full bg-[#40e0d0]/[0.06] blur-[110px]"></div>
            <div class="absolute -bottom-32 left-1/3 w-[420px] h-[420px] rounded-full bg-yellow-400/[0.05] blur-[110px]"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 pt-16 sm:pt-24 pb-24 sm:pb-36">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2.5 mb-6">
                        <span class="w-10 h-[2px] bg-[#40e0d0]"></span>
                        <span class="text-[#40e0d0] text-[11px] font-bold uppercase tracking-[0.25em]">Solar Shop</span>
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-[56px] font-extrabold tracking-tight text-white leading-[1.05]">
                        Power Your World<br />
                        With <span class="bg-gradient-to-r from-yellow-400 to-amber-300 bg-clip-text text-transparent">Trusted Solar Gear</span>
                    </h1>
                    <p class="mt-5 max-w-lg text-slate-400 text-base sm:text-lg leading-relaxed">
                        Genuine panels, inverters &amp; batteries, delivered fast.
                    </p>

                    <!-- Search bar -->
                    <form class="mt-8 flex max-w-xl items-center gap-3" @submit.prevent="applySearch">
                        <div class="flex-1 flex items-center gap-3 bg-white/[0.06] border border-white/10 rounded-xl px-4 py-3 backdrop-blur">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/></svg>
                            <input
                                v-model="state.search"
                                type="search"
                                placeholder="Search panels, inverters, batteries…"
                                class="w-full bg-transparent border-0 text-sm text-white placeholder:text-slate-500 focus:outline-none"
                            />
                        </div>
                        <button type="submit" class="shrink-0 rounded-xl bg-gradient-to-r from-yellow-400 to-amber-400 px-6 py-3 text-sm font-bold text-slate-950 hover:brightness-110 transition-all shadow-lg shadow-yellow-400/20">Search</button>
                    </form>

                    <!-- Trust row -->
                    <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-2.5">
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <svg class="w-4 h-4 text-[#40e0d0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12l14 0M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Fast Delivery
                        </span>
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Genuine &amp; Warranty
                        </span>
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <svg class="w-4 h-4 text-[#40e0d0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7h16M9 7V5h6v2M5 7l1 12h12l1-12M9 11v5M15 11v5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            7-Day Returns
                        </span>
                    </div>
                </div>

                <!-- Decorative showcase -->
                <div class="hidden lg:flex relative items-center justify-center h-[480px]" aria-hidden="true">
                    <!-- Rotating sun rays -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="shop-rays w-[560px] h-[560px] opacity-50" viewBox="0 0 200 200">
                            <g stroke="#FACC15" stroke-width="2.5" stroke-linecap="round">
                                <line x1="100" y1="4" x2="100" y2="30" />
                                <line x1="100" y1="170" x2="100" y2="196" />
                                <line x1="4" y1="100" x2="30" y2="100" />
                                <line x1="170" y1="100" x2="196" y2="100" />
                                <line x1="32" y1="32" x2="51" y2="51" />
                                <line x1="149" y1="149" x2="168" y2="168" />
                                <line x1="32" y1="168" x2="51" y2="149" />
                                <line x1="168" y1="32" x2="149" y2="51" />
                            </g>
                            <circle cx="100" cy="100" r="34" fill="#FACC15" opacity="0.14" />
                        </svg>
                    </div>
                    <div class="absolute w-80 h-80 rounded-full border border-white/10"></div>
                    <div class="absolute w-52 h-52 rounded-full border border-[#40e0d0]/20"></div>

                    <!-- Main product card -->
                    <div v-if="heroFeatured[0]" class="hero-float relative bg-white rounded-3xl border border-slate-100 shadow-2xl p-4 w-64">
                        <div class="rounded-2xl overflow-hidden bg-[#FAF8F2] border border-slate-100">
                            <img :src="imageFor(heroFeatured[0])" :alt="heroFeatured[0].name" class="w-full h-40 object-cover" />
                        </div>
                        <div class="flex items-center justify-between pt-3.5">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-[#40e0d0]">{{ heroFeatured[0].brand || 'Envoy' }}</p>
                                <p class="text-sm font-extrabold text-slate-900 line-clamp-1">{{ heroFeatured[0].name }}</p>
                            </div>
                            <span class="rounded-lg bg-yellow-400 px-2 py-1 text-[10px] font-black text-slate-950">HOT</span>
                        </div>
                        <p class="mt-2 text-lg font-black text-[#40e0d0]">{{ naira(heroFeatured[0].selling_price) }}</p>
                    </div>

                    <!-- Floating mini card -->
                    <div v-if="heroFeatured[1]" class="hero-float-delay absolute -right-2 top-8 bg-white rounded-2xl shadow-2xl border border-slate-100 p-3 flex items-center gap-3 w-56">
                        <div class="w-16 h-16 rounded-xl overflow-hidden bg-[#FAF8F2] border border-slate-100 shrink-0">
                            <img :src="imageFor(heroFeatured[1])" :alt="heroFeatured[1].name" class="w-full h-full object-cover" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-extrabold text-slate-900 line-clamp-1">{{ heroFeatured[1].name }}</p>
                            <p class="text-sm font-black text-[#40e0d0] mt-0.5">{{ naira(heroFeatured[1].selling_price) }}</p>
                        </div>
                    </div>

                    <!-- Floating chip -->
                    <div class="hero-float-delay absolute bottom-8 -left-6 bg-[#0D1527] text-white rounded-2xl px-5 py-3.5 shadow-2xl flex items-center gap-3 border border-white/10">
                        <span class="w-10 h-10 rounded-xl bg-[#40e0d0]/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#40e0d0]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 7h12l-1 13H7L6 7zM9 7a3 3 0 0 1 6 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <p class="text-[10px] uppercase text-slate-400 font-bold tracking-widest">In store</p>
                            <p class="text-base font-black text-yellow-400">{{ products.total }}+ Products</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave transition into content -->
        <div class="absolute bottom-0 left-0 w-full pointer-events-none" aria-hidden="true">
            <svg class="block w-full h-[70px] sm:h-[100px]" viewBox="0 0 1440 100" preserveAspectRatio="none">
                <path d="M0,55 C160,25 320,70 500,55 C700,38 840,5 1040,25 C1240,45 1340,70 1440,50 L1440,100 L0,100 Z" fill="#FAF8F2" opacity="0.9" />
                <path d="M0,78 C180,60 360,92 560,80 C760,68 940,52 1140,68 C1280,80 1380,78 1440,70 L1440,100 L0,100 Z" fill="#FAF8F2" />
            </svg>
        </div>
    </section>

    <!-- ======================= CONTENT ======================= -->
    <section class="bg-[#FAF8F2]">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 py-10 lg:py-14">

            <!-- Mobile filter toggle -->
            <div class="lg:hidden sticky top-16 z-30 -mx-6 sm:-mx-12 px-6 sm:px-12 py-3 bg-[#FAF8F2]/95 backdrop-blur border-b border-slate-200/60 flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:border-yellow-400"
                    @click="isFilterDrawerOpen = true"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M7 12h10M10 18h4" stroke-linecap="round"/></svg>
                    Filters
                    <span v-if="activeFilterCount" class="rounded-full bg-yellow-400 text-slate-950 text-[10px] font-black min-w-[20px] h-5 flex items-center justify-center px-1">{{ activeFilterCount }}</span>
                </button>
                <select v-model="state.sort" class="flex-1 rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm focus:border-yellow-400" @change="setSort(state.sort)">
                    <option value="">Sort: Featured</option>
                    <option value="price_asc">Price: Low → High</option>
                    <option value="price_desc">Price: High → Low</option>
                    <option value="newest">Newest First</option>
                </select>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-8">

                <!-- ======================= SIDEBAR ======================= -->
                <aside class="hidden lg:block">
                    <div class="sticky top-24 space-y-6">
                        <!-- Filters panel -->
                        <div class="rounded-2xl border border-slate-100 bg-white shadow-[0_10px_30px_rgba(0,0,0,0.06)] p-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-black uppercase tracking-widest text-slate-900">Filters</h3>
                                <button
                                    v-if="hasActiveFilters"
                                    type="button"
                                    class="text-xs font-bold text-[#40e0d0] hover:underline"
                                    @click="clearFilters"
                                >Clear all</button>
                            </div>

                            <!-- Categories -->
                            <div class="mt-6">
                                <button type="button" class="flex w-full items-center justify-between text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400" @click="catExpanded = !catExpanded">
                                    <span>Categories</span>
                                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="catExpanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <div v-show="catExpanded" class="mt-3 space-y-1">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition-all duration-150"
                                        :class="!state.category ? 'bg-yellow-400/10 text-yellow-700' : 'text-slate-600 hover:bg-slate-50'"
                                        @click="selectCategory('')"
                                    >
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
                                            All Products
                                        </span>
                                        <span class="text-xs text-slate-400">{{ totalProducts }}</span>
                                    </button>
                                    <button
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition-all duration-150"
                                        :class="state.category === cat.id ? 'bg-yellow-400/10 text-yellow-700' : 'text-slate-600 hover:bg-slate-50'"
                                        @click="selectCategory(cat.id)"
                                    >
                                        <span>{{ cat.name }}</span>
                                        <span class="text-xs text-slate-400">{{ cat.count }}</span>
                                    </button>
                                </div>
                            </div>

                            <hr class="my-5 border-slate-100" />

                            <!-- Price Range -->
                            <div>
                                <button type="button" class="flex w-full items-center justify-between text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400" @click="priceExpanded = !priceExpanded">
                                    <span>Price Range</span>
                                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="priceExpanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <div v-show="priceExpanded" class="mt-3 space-y-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">From</label>
                                            <input v-model="state.minPrice" type="number" min="0" placeholder="0" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-yellow-400 focus:ring-yellow-400/20" />
                                        </div>
                                        <span class="pt-4 text-slate-300">–</span>
                                        <div class="flex-1">
                                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">To</label>
                                            <input v-model="state.maxPrice" type="number" min="0" placeholder="1M+" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-yellow-400 focus:ring-yellow-400/20" />
                                        </div>
                                    </div>
                                    <button type="button" class="w-full rounded-xl bg-[#0D1527] py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition-colors" @click="applyPrice">Apply</button>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button
                                            v-for="p in pricePresets"
                                            :key="p.label"
                                            type="button"
                                            class="rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-all duration-150"
                                            :class="presetActive(p) ? 'border-yellow-400 bg-yellow-400/10 text-yellow-700' : 'border-slate-200 text-slate-500 hover:border-yellow-300'"
                                            @click="applyPreset(p)"
                                        >{{ p.label }}</button>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-5 border-slate-100" />

                            <!-- Availability -->
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Availability</p>
                                <div class="mt-3 space-y-1">
                                    <button
                                        type="button"
                                        class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-150"
                                        :class="state.availability === 'in_stock' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50'"
                                        @click="setAvailability('in_stock')"
                                    >
                                        <span class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                                              :class="state.availability === 'in_stock' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'"
                                        >
                                            <svg v-if="state.availability === 'in_stock'" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                        In stock only
                                    </button>
                                    <button
                                        type="button"
                                        class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-150"
                                        :class="state.availability === 'out_of_stock' ? 'bg-red-50 text-red-600' : 'text-slate-600 hover:bg-slate-50'"
                                        @click="setAvailability('out_of_stock')"
                                    >
                                        <span class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                                              :class="state.availability === 'out_of_stock' ? 'border-red-500 bg-red-500' : 'border-slate-300'"
                                        >
                                            <svg v-if="state.availability === 'out_of_stock'" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                        Out of stock
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar CTA -->
                        <div class="relative overflow-hidden rounded-2xl bg-[#0D1527] p-6 shadow-[0_10px_30px_rgba(13,21,39,0.2)]">
                            <div class="absolute -right-10 -top-10 w-32 h-32 rounded-full bg-yellow-400/10 blur-2xl"></div>
                            <div class="relative z-10">
                                <div class="w-10 h-10 rounded-xl bg-yellow-400/15 flex items-center justify-center mb-4">
                                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <p class="font-extrabold text-white text-[15px] leading-snug">Not sure which system fits?</p>
                                <p class="mt-2 text-sm text-slate-400 leading-relaxed">Run our free solar calculator and get a recommendation in seconds.</p>
                                <Link href="/calculator" class="mt-5 block w-full rounded-xl bg-gradient-to-r from-yellow-400 to-amber-400 py-2.5 text-center text-sm font-bold text-slate-950 hover:brightness-110 transition-all shadow-lg shadow-yellow-400/20">Calculate My Load</Link>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- ======================= RESULTS ======================= -->
                <section>
                    <!-- Toolbar -->
                    <div class="hidden lg:flex items-center justify-between gap-4">
                        <p class="text-sm text-slate-500">
                            Showing <span class="font-bold text-slate-900">{{ products.data.length }}</span> of
                            <span class="font-bold text-slate-900">{{ products.total }}</span> products
                        </p>
                        <select v-model="state.sort" class="rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm focus:border-yellow-400" @change="setSort(state.sort)">
                            <option value="">Sort: Featured</option>
                            <option value="price_asc">Price: Low → High</option>
                            <option value="price_desc">Price: High → Low</option>
                            <option value="newest">Newest First</option>
                        </select>
                    </div>

                    <!-- Active chips -->
                    <div v-if="chips.length" class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active:</span>
                        <button
                            v-for="chip in chips"
                            :key="chip.key"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border border-[#40e0d0]/30 bg-[#40e0d0]/10 py-1 pl-3 pr-2 text-xs font-semibold text-[#1a6b63] hover:border-[#40e0d0]/50 transition-colors"
                            @click="removeChip(chip.key)"
                        >
                            {{ chip.label }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" class="text-xs font-bold text-slate-500 hover:text-[#40e0d0] hover:underline" @click="clearFilters">Clear all</button>
                    </div>

                    <!-- Product grid — matching landing page card style -->
                    <div class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-5 sm:gap-6">
                        <Link
                            v-for="product in products.data"
                            :key="product.id"
                            :href="`/shop/${product.id}`"
                            class="group flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-[0_10px_30px_rgba(0,0,0,0.06)] hover:shadow-[0_20px_40px_rgba(13,21,39,0.16)] hover:-translate-y-1.5 transition-all duration-300"
                        >
                            <!-- Image -->
                            <div class="relative h-44 sm:h-52 overflow-hidden bg-slate-100">
                                <img
                                    :src="imageFor(product)"
                                    :alt="product.name"
                                    loading="lazy"
                                    class="w-full h-full object-cover object-center transition-transform duration-500 group-hover:scale-105"
                                />
                                <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                                    <span
                                        class="rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-800 backdrop-blur-sm"
                                    >{{ product.brand || (product.category?.name || 'Envoy') }}</span>
                                    <span
                                        v-if="product.is_featured"
                                        class="rounded-full bg-yellow-400 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-950"
                                    >Popular</span>
                                    <span
                                        v-if="product.stock_status === 'out_of_stock'"
                                        class="rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white"
                                    >Sold Out</span>
                                </div>
                                <!-- Hover add-to-cart -->
                                <div
                                    v-if="product.stock_status !== 'out_of_stock'"
                                    class="absolute bottom-3 right-3 translate-y-12 rounded-xl bg-[#0D1527] px-4 py-2.5 text-xs font-bold text-white opacity-0 shadow-lg transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100 whitespace-nowrap"
                                    @click.prevent="addToCart(product)"
                                >
                                    <svg class="w-3.5 h-3.5 inline mr-1 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Add to cart
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="flex flex-1 flex-col p-4 sm:p-5">
                                <h3 class="line-clamp-2 text-sm font-bold text-slate-900">{{ product.name }}</h3>
                                <div class="mt-auto flex items-end justify-between pt-4">
                                    <span class="text-lg font-black text-[#40e0d0]">{{ naira(product.selling_price) }}</span>
                                    <Link
                                        :href="`/shop/${product.id}`"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-[#0D1527] px-3 h-9 text-xs font-bold text-white transition-all duration-200 hover:bg-yellow-400 hover:text-slate-950"
                                        title="View Product"
                                    >
                                        View
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </Link>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Empty state -->
                    <div v-if="!products.data.length" class="mt-12 flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white px-6 py-20 text-center">
                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 21l-5.2-5.2m0 0A7.5 7.5 0 1 0 5.8 5.8a7.5 7.5 0 0 0 10 10z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-extrabold text-slate-900">No products found</h3>
                        <p class="mt-1 max-w-sm text-sm text-slate-500">We couldn't find anything matching your filters. Try removing a few, or browse the full catalogue.</p>
                        <button type="button" class="mt-5 rounded-xl bg-[#0D1527] px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 transition-colors" @click="clearFilters">Clear all filters</button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="products.last_page > 1" class="mt-10 flex flex-wrap items-center justify-center gap-2">
                        <template v-for="(link, i) in products.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="inline-flex min-w-[40px] h-10 items-center justify-center rounded-xl border px-3 text-sm font-bold transition-all"
                                :class="link.active
                                    ? 'border-yellow-400 bg-yellow-400 text-slate-950'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-yellow-300 hover:text-yellow-700 shadow-sm'"
                                v-html="link.label"
                            />
                            <span v-else class="inline-flex h-10 items-center justify-center px-2 text-sm text-slate-400" v-html="link.label" />
                        </template>
                    </div>
                </section>
            </div>

            <!-- Trust strip -->
            <div class="mt-16 grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                    <div class="w-11 h-11 shrink-0 rounded-xl bg-yellow-400/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12l5-3v6l-5-3zm5 0h6m-6 0l5-3v6l-5-3zm6 0h6m-6 0l5-3v6l-5-3zm6 0h5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Fast Delivery</p>
                        <p class="text-xs text-slate-400">Nationwide, Lagos same-day</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                    <div class="w-11 h-11 shrink-0 rounded-xl bg-[#40e0d0]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#40e0d0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Genuine & Warranty</p>
                        <p class="text-xs text-slate-400">Tier-one brands only</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                    <div class="w-11 h-11 shrink-0 rounded-xl bg-yellow-400/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Easy Payments</p>
                        <p class="text-xs text-slate-400">Paystack, transfer, cash</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_10px_30px_rgba(0,0,0,0.06)]">
                    <div class="w-11 h-11 shrink-0 rounded-xl bg-[#40e0d0]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#40e0d0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 18v-6a9 9 0 0 1 18 0v6M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Real Support</p>
                        <p class="text-xs text-slate-400">Engineers on call 7 days</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================= MOBILE FILTER DRAWER ======================= -->
    <Teleport to="body">
        <div v-if="isFilterDrawerOpen" class="fixed inset-0 z-50 lg:hidden">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="isFilterDrawerOpen = false" />
            <div class="absolute right-0 top-0 h-full w-[85%] max-w-sm overflow-y-auto bg-white p-6 shadow-2xl animate-[slideIn_0.25s_ease-out]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-black uppercase tracking-widest text-slate-900">Filters</p>
                    <button type="button" class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors" @click="isFilterDrawerOpen = false">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <div class="mt-6 space-y-6">
                    <!-- Category -->
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Category</p>
                        <div class="mt-2.5 flex flex-col gap-1">
                            <button type="button" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition-all" :class="!state.category ? 'bg-yellow-400/10 text-yellow-700' : 'text-slate-600'" @click="selectCategory(''); isFilterDrawerOpen = false">
                                <span>All Products</span><span class="text-xs text-slate-400">{{ totalProducts }}</span>
                            </button>
                            <button v-for="cat in categories" :key="cat.id" type="button" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition-all" :class="state.category === cat.id ? 'bg-yellow-400/10 text-yellow-700' : 'text-slate-600'" @click="selectCategory(cat.id); isFilterDrawerOpen = false">
                                <span>{{ cat.name }}</span><span class="text-xs text-slate-400">{{ cat.count }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Price Range</p>
                        <div class="mt-2.5 flex items-center gap-2">
                            <input v-model="state.minPrice" type="number" min="0" placeholder="From" class="w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm" />
                            <span class="text-slate-300">–</span>
                            <input v-model="state.maxPrice" type="number" min="0" placeholder="To" class="w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm" />
                        </div>
                        <button type="button" class="mt-2.5 w-full rounded-xl bg-[#0D1527] py-2.5 text-sm font-bold text-white" @click="apply(); isFilterDrawerOpen = false">Apply price</button>
                    </div>

                    <!-- Availability -->
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Availability</p>
                        <div class="mt-2.5 flex flex-col gap-1">
                            <button type="button" class="rounded-xl px-3 py-2.5 text-sm font-medium transition-all" :class="state.availability === 'in_stock' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600'" @click="setAvailability('in_stock'); isFilterDrawerOpen = false">In stock only</button>
                            <button type="button" class="rounded-xl px-3 py-2.5 text-sm font-medium transition-all" :class="state.availability === 'out_of_stock' ? 'bg-red-50 text-red-600' : 'text-slate-600'" @click="setAvailability('out_of_stock'); isFilterDrawerOpen = false">Out of stock</button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" class="flex-1 rounded-xl border border-slate-200 bg-white py-3 text-sm font-bold text-slate-700 hover:border-yellow-400 transition-colors" @click="clearFilters">Clear all</button>
                    <button type="button" class="flex-1 rounded-xl bg-[#0D1527] py-3 text-sm font-bold text-white hover:bg-slate-800 transition-colors" @click="isFilterDrawerOpen = false">Show Results</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes slideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

@keyframes shopSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes shopFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-14px); }
}

@keyframes shopFloatDelayed {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(12px); }
}

.shop-rays {
    animation: shopSpin 45s linear infinite;
    transform-origin: center center;
}

.hero-float {
    animation: shopFloat 6s ease-in-out infinite;
}

.hero-float-delay {
    animation: shopFloatDelayed 7.5s ease-in-out 1.2s infinite;
}
</style>
