<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { addItem } from '@/lib/cart';
import { naira, stockStatusLabel } from '@/lib/format';

defineOptions({ layout: PublicLayout });

const origin = window.location.origin;

const props = defineProps({
    product: { type: Object, required: true },
    related: { type: Array, default: () => [] },
});

const gallery = computed(() => props.product.images || []);
const activeImageIndex = ref(0);
const quantity = ref(1);

const images = computed(() => {
    if (gallery.value.length) return gallery.value;
    return [{ path: '/images/landing/solar_products.jpg' }];
});

function imageUrl(img) {
    return img.path?.startsWith('/images/') ? img.path : `/storage/${img.path}`;
}

function isOutOfStock() {
    return props.product.stock_status === 'out_of_stock';
}

function changeQuantity(delta) {
    quantity.value = Math.max(1, Math.min(99, quantity.value + delta));
}

function addToCart() {
    addItem(props.product, quantity.value);
}

function relatedImage(p) {
    const img = (p.images || []).find((i) => i.is_featured) || (p.images || [])[0];
    return img ? imageUrl(img) : '/images/landing/solar_products.jpg';
}

const productUrl = computed(() => `${origin}/shop/${props.product.id}`);

const pageMetaDescription = computed(() =>
    String(props.product.description || props.product.name || '').slice(0, 160),
);

const productImage = computed(() => {
    const img = props.product.images?.find((i) => i.is_featured) || props.product.images?.[0];
    return img ? imageUrl(img) : `${origin}/images/landing/solar_products.jpg`;
});

const productSchema = computed(() => {
    const schema = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: props.product.name,
        image: productImage.value,
        description: String(props.product.description || props.product.name || '').slice(0, 300),
        sku: props.product.sku || undefined,
        brand: props.product.brand?.name ? { '@type': 'Brand', name: props.product.brand.name } : undefined,
        category: props.product.category?.name,
        offers: {
            '@type': 'Offer',
            url: productUrl.value,
            priceCurrency: 'NGN',
            price: String(Number(props.product.selling_price || 0).toFixed(2)),
            availability: isOutOfStock() ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
            itemCondition: 'https://schema.org/NewCondition',
            seller: { '@type': 'Organization', name: 'Envoy Electricals' },
        },
    };
    return JSON.stringify(schema);
});

const specRows = computed(() => {
    const rows = [];
    const structured = Array.isArray(props.product.specifications_json)
        ? props.product.specifications_json
        : parseLegacySpecs(props.product.specifications);
    if (structured.length) {
        structured.forEach((s) => {
            if (s?.label && s?.value) {
                rows.push({ label: s.label, value: s.value });
            }
        });
    }
    if (props.product.brand?.name) rows.push({ label: 'Brand', value: props.product.brand.name });
    if (props.product.sku) rows.push({ label: 'SKU', value: props.product.sku });
    if (props.product.unit) rows.push({ label: 'Unit', value: props.product.unit });
    if (props.product.category?.name) rows.push({ label: 'Category', value: props.product.category.name });
    if (props.product.current_quantity != null) rows.push({ label: 'Available Units', value: `${props.product.current_quantity} pcs` });
    rows.push({
        label: 'Availability',
        value: isOutOfStock() ? 'Out of stock' : 'In stock',
    });
    return rows;
});

const features = computed(() => {
    const f = [];
    if (props.product.is_featured) f.push('Envoy best-seller');
    if (!isOutOfStock()) f.push('Ready for same-day dispatch');
    f.push('Quality-checked before delivery');
    if (props.product.brand?.name) f.push(`Brand: ${props.product.brand.name}`);
    if (props.product.unit) f.push(`Sold in: ${props.product.unit}`);
    return f;
});

function parseLegacySpecs(text) {
    if (typeof text !== 'string' || !text.trim()) return [];
    return text
        .split(/\r?\n/)
        .map((line) => {
            const [label, ...rest] = line.split(':');
            return { label: (label ?? '').trim(), value: rest.join(':').trim() };
        })
        .filter((s) => s.label && s.value);
}
</script>

<template>
    <Head :title="`${product.name} — Solar & Electrical Supplies | Envoy Electricals`">
        <meta name="description" :content="pageMetaDescription" />
        <link rel="canonical" :href="productUrl" />
        <meta property="og:type" content="product" />
        <meta property="og:title" :content="`${product.name} — Envoy Electricals`" />
        <meta property="og:description" :content="pageMetaDescription" />
        <meta property="og:url" :href="productUrl" />
        <meta property="og:image" :content="productImage" />
        <component is="script" type="application/ld+json">{{ productSchema }}</component>
    </Head>
    <!-- ======================= TOP BAR / BREADCRUMB ======================= -->
    <div class="bg-[#0D1527]">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 py-3.5">
            <nav class="flex flex-wrap items-center gap-2 text-[12px] font-medium text-slate-400">
                <Link href="/" class="hover:text-yellow-400 transition-colors">Home</Link>
                <svg class="w-3 h-3 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <Link href="/shop" class="hover:text-yellow-400 transition-colors">Shop</Link>
                <svg class="w-3 h-3 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span v-if="product.category" class="hover:text-yellow-400 transition-colors">{{ product.category.name }}</span>
                <svg v-if="product.category" class="w-3 h-3 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="text-white font-semibold truncate">{{ product.name }}</span>
            </nav>
        </div>
    </div>

    <div class="bg-[#FAF8F2]">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 py-6 sm:py-8">

            <!-- ======================= TITLE BAR ======================= -->
            <div class="flex items-start justify-between gap-4 pb-5">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#0D1527] px-3 py-1 text-[10px] font-black uppercase tracking-widest text-yellow-400">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-1.4 14.2L6.8 12.4l1.4-1.4 2.6 2.6 4.4-4.4 1.4 1.4-6 6z"/></svg>
                            Envoy Official Store
                        </span>
                        <span v-if="product.is_featured" class="rounded-full bg-yellow-400 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-slate-950">Best Seller</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl lg:text-[28px] font-extrabold tracking-tight text-slate-950 leading-snug">
                        {{ product.name }}
                    </h1>
                    <p class="mt-1 text-[13px] text-slate-500">
                        Brand: <span class="font-bold text-slate-700">{{ product.brand?.name || 'Envoy' }}</span>
                        <span v-if="product.category" class="mx-1.5 text-slate-300">•</span>
                        <Link v-if="product.category" :href="`/shop?category=${product.category.id}`" class="text-[#40e0d0] hover:underline">
                            Similar products from {{ product.category.name }}
                        </Link>
                    </p>
                </div>
                <button
                    type="button"
                    class="hidden sm:inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 shadow-sm hover:border-yellow-400 hover:text-slate-900 transition-colors"
                    title="Share this product"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4" stroke-linecap="round"/></svg>
                    Share
                </button>
            </div>

            <!-- ======================= MAIN GRID ======================= -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- ---------------- CONTENT COLUMN (9) ---------------- -->
                <div class="lg:col-span-9 space-y-6">

                    <!-- ===== BUY CARD: Gallery + Info ===== -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_10px_30px_rgba(0,0,0,0.06)] p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                        <!-- Gallery -->
                        <div>
                            <div class="relative aspect-square overflow-hidden rounded-xl bg-[#FAF8F2] border border-slate-100">
                                <img
                                    :src="imageUrl(images[activeImageIndex])"
                                    :alt="product.name"
                                    class="w-full h-full object-contain"
                                />
                                <span
                                    v-if="isOutOfStock()"
                                    class="absolute top-3 right-3 rounded-full bg-red-500 px-3.5 py-1.5 text-[11px] font-black uppercase tracking-wider text-white shadow-lg"
                                >Sold Out</span>
                            </div>
                            <div v-if="images.length > 1" class="mt-3 grid grid-cols-5 gap-2.5">
                                <button
                                    v-for="(img, i) in images"
                                    :key="i"
                                    type="button"
                                    class="aspect-square overflow-hidden rounded-lg bg-[#FAF8F2] border transition-all duration-200"
                                    :class="i === activeImageIndex
                                        ? 'border-yellow-400 ring-2 ring-yellow-400/30'
                                        : 'border-slate-200 opacity-70 hover:opacity-100'"
                                    @click="activeImageIndex = i"
                                >
                                    <img :src="imageUrl(img)" :alt="product.name" class="w-full h-full object-contain p-1" />
                                </button>
                            </div>
                        </div>

                        <!-- Buy Info -->
                        <div class="flex flex-col">
                            <!-- rating-style row -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="flex items-center gap-0.5 text-yellow-400">
                                    <svg v-for="s in 5" :key="s" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                </span>
                                <span class="text-xs font-bold text-slate-500">5.0</span>
                                <span class="text-xs text-slate-400">·</span>
                                <span class="text-xs font-bold text-[#1a6b63]">Envoy Verified</span>
                                <span
                                    class="ml-auto inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold"
                                    :class="stockStatusLabel[product.stock_status]?.cls || 'bg-slate-100 text-slate-600'"
                                >
                                    {{ stockStatusLabel[product.stock_status]?.label || 'In Stock' }}
                                </span>
                            </div>

                            <!-- Price block -->
                            <div class="mt-4 rounded-2xl bg-gradient-to-r from-[#0D1527] to-[#16223F] p-5">
                                <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-slate-400">Selling Price</p>
                                <div class="mt-1 flex items-end gap-3">
                                    <p class="text-3xl sm:text-[34px] font-black tracking-tight text-[#40e0d0] leading-none">
                                        {{ naira(product.selling_price) }}
                                    </p>
                                </div>
                                <p class="mt-2.5 text-[11px] font-medium text-slate-400">
                                    Free delivery on orders over ₦100,000
                                </p>
                            </div>

                            <!-- Stock + delivery line -->
                            <div class="mt-4 flex items-center gap-2.5 text-xs text-slate-500">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span v-if="!isOutOfStock()"><span class="font-bold text-slate-700">{{ product.current_quantity ?? 'N/A' }}</span> items in stock · ready to ship today</span>
                                <span v-else>Currently out of stock</span>
                            </div>

                            <hr class="my-5 border-slate-100" />

                            <!-- Quantity -->
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2.5">Quantity</p>
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center rounded-xl border border-slate-200 bg-white">
                                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg font-bold text-slate-600 hover:text-slate-950 transition-colors" @click="changeQuantity(-1)">−</button>
                                    <span class="w-10 text-center text-sm font-bold text-slate-900">{{ quantity }}</span>
                                    <button type="button" class="w-11 h-11 flex items-center justify-center text-lg font-bold text-slate-600 hover:text-slate-950 transition-colors" @click="changeQuantity(1)">+</button>
                                </div>
                                <p class="text-xs text-slate-400">Total: <span class="font-black text-[#40e0d0] text-sm">{{ naira(product.selling_price * quantity) }}</span></p>
                            </div>

                            <!-- Add to cart -->
                            <button
                                type="button"
                                :disabled="isOutOfStock()"
                                class="mt-4 w-full rounded-xl bg-gradient-to-r from-yellow-400 to-amber-400 py-4 text-sm font-black uppercase tracking-widest text-slate-950 hover:brightness-110 transition-all shadow-lg shadow-yellow-400/25 disabled:opacity-40 disabled:cursor-not-allowed"
                                @click="addToCart"
                            >
                                <svg v-if="!isOutOfStock()" class="w-5 h-5 inline mr-2 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ isOutOfStock() ? 'Out of Stock' : 'Add to Cart' }}
                            </button>

                            <!-- Promo chips -->
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-yellow-400/40 bg-yellow-400/10 px-3 py-1.5 text-[11px] font-bold text-[#8a6d00]">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
                                    100% Genuine
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-bold text-slate-500">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
                                    Warranty Covered
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ===== DELIVERY & RETURNS ===== -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_10px_30px_rgba(0,0,0,0.06)] overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100">
                            <span class="w-10 h-[2px] bg-yellow-400"></span>
                            <h2 class="text-base font-black uppercase tracking-wide text-slate-900">Delivery &amp; Returns</h2>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div class="flex items-start gap-4 px-6 py-4">
                                <div class="w-10 h-10 shrink-0 rounded-xl bg-yellow-400/10 flex items-center justify-center mt-0.5">
                                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12l5-3v6l-5-3zm5 0h6m-6 0l5-3v6l-5-3zm6 0h6m-6 0l5-3v6l-5-3zm6 0h5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-800">Door Delivery — Nationwide</p>
                                    <p class="mt-0.5 text-xs text-slate-400 leading-relaxed">Same-day within Lagos · 2–4 working days across Nigeria. Free delivery on orders above ₦100,000.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4 px-6 py-4">
                                <div class="w-10 h-10 shrink-0 rounded-xl bg-yellow-400/10 flex items-center justify-center mt-0.5">
                                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-800">Warranty</p>
                                    <p class="mt-0.5 text-xs text-slate-400 leading-relaxed">All products come with a valid brand warranty and after-sale support from our engineers.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== PRODUCT DETAILS ===== -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_10px_30px_rgba(0,0,0,0.06)] p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-5">
                            <span class="w-10 h-[2px] bg-yellow-400"></span>
                            <h2 class="text-base font-black uppercase tracking-wide text-slate-900">Product Details</h2>
                        </div>
                        <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                            {{ product.description || 'No description provided.' }}
                        </p>
                    </div>

                    <!-- ===== SPECIFICATIONS ===== -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_10px_30px_rgba(0,0,0,0.06)] overflow-hidden">
                        <div class="flex items-center gap-3 px-6 sm:px-8 py-4 border-b border-slate-100">
                            <span class="w-10 h-[2px] bg-[#40e0d0]"></span>
                            <h2 class="text-base font-black uppercase tracking-wide text-slate-900">Specifications</h2>
                        </div>

                        <!-- Key features -->
                        <div v-if="features.length" class="px-6 sm:px-8 py-5 border-b border-slate-100">
                            <p class="text-[11px] font-black uppercase tracking-[0.15em] text-slate-400 mb-3">Key Features</p>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <li v-for="(f, i) in features" :key="i" class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                    <svg class="w-4 h-4 shrink-0 text-[#40e0d0]" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
                                    {{ f }}
                                </li>
                            </ul>
                        </div>

                        <!-- Spec table -->
                        <dl class="divide-y divide-slate-100">
                            <div v-for="(row, i) in specRows" :key="i" class="grid grid-cols-[140px_1fr] sm:grid-cols-[200px_1fr] gap-4 px-6 sm:px-8 py-3.5">
                                <dt class="text-xs font-semibold text-slate-400">{{ row.label }}</dt>
                                <dd class="text-sm font-bold text-slate-800">{{ row.value }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- ---------------- SIDEBAR (3) ---------------- -->
                <aside class="lg:col-span-3 space-y-6">
                    <!-- Seller information -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_10px_30px_rgba(0,0,0,0.06)] p-5">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-900 mb-4">Seller Information</h3>
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-[#0D1527] flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-1.4 14.2L6.8 12.4l1.4-1.4 2.6 2.6 4.4-4.4 1.4 1.4-6 6z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-extrabold text-slate-900">Envoy Electricals</p>
                                <p class="text-[11px] text-slate-400">Official Solar Store</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-slate-500">Seller Score</span>
                                <span class="font-black text-[#40e0d0]">100%</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full w-full rounded-full bg-gradient-to-r from-yellow-400 to-[#40e0d0]"></div>
                            </div>
                        </div>
                        <p class="mt-4 text-[11px] text-slate-400 leading-relaxed">Ships from Lagos · Trusted across Nigeria since day one.</p>
                    </div>

                    <!-- Questions / chat -->
                    <div class="rounded-2xl border border-slate-100 bg-white shadow-[0_10px_30px_rgba(0,0,0,0.06)] p-5">
                        <div class="w-10 h-10 rounded-xl bg-[#40e0d0]/10 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-[#40e0d0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-sm font-extrabold text-slate-900">Questions about this product?</p>
                        <p class="mt-1 text-xs text-slate-400 leading-relaxed">Chat with our engineers for sizing advice, installation and bulk pricing.</p>
                        <Link href="/contact" class="mt-3 inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-[#40e0d0] hover:text-[#2ba89a] transition-colors">
                            Chat with us
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </Link>
                    </div>

                    <!-- Calculator CTA -->
                    <div class="relative overflow-hidden rounded-2xl bg-[#0D1527] p-5 shadow-[0_10px_30px_rgba(13,21,39,0.25)]">
                        <div class="absolute -right-10 -top-10 w-32 h-32 rounded-full bg-yellow-400/10 blur-2xl"></div>
                        <div class="relative z-10">
                            <div class="w-10 h-10 rounded-xl bg-yellow-400/15 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <p class="text-sm font-extrabold text-white leading-snug">Not sure which system fits your home?</p>
                            <p class="mt-1.5 text-xs text-slate-400 leading-relaxed">Run our free solar load calculator and get a recommendation in seconds.</p>
                            <Link href="/calculator" class="mt-4 block w-full rounded-xl bg-gradient-to-r from-yellow-400 to-amber-400 py-2.5 text-center text-xs font-black uppercase tracking-widest text-slate-950 hover:brightness-110 transition-all">Calculate My Load</Link>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- ======================= RELATED ======================= -->
            <section v-if="related.length" class="mt-14">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-[2px] bg-yellow-400"></span>
                        <h2 class="text-xl font-black uppercase tracking-wide text-slate-900">Related Products</h2>
                    </div>
                    <Link href="/shop" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-[#40e0d0] hover:text-[#2ba89a] transition-colors">
                        View All
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </Link>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-6">
                    <Link
                        v-for="p in related"
                        :key="p.id"
                        :href="`/shop/${p.id}`"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-[0_10px_30px_rgba(0,0,0,0.06)] hover:shadow-[0_20px_40px_rgba(13,21,39,0.16)] hover:-translate-y-1.5 transition-all duration-300"
                    >
                        <div class="relative h-40 sm:h-44 overflow-hidden bg-slate-100">
                            <img :src="relatedImage(p)" :alt="p.name" loading="lazy" class="w-full h-full object-cover object-center transition-transform duration-500 group-hover:scale-105" />
                            <span class="absolute top-3 left-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-800">
                                {{ p.category?.name || p.brand?.name || 'Envoy' }}
                            </span>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="line-clamp-2 text-sm font-bold text-slate-900">{{ p.name }}</h3>
                            <div class="mt-auto flex items-end justify-between pt-4">
                                <span class="text-base font-black text-[#40e0d0]">{{ naira(p.selling_price) }}</span>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#0D1527] px-3 h-9 text-xs font-bold text-white transition-all duration-200 group-hover:bg-yellow-400 group-hover:text-slate-950"
                                >
                                    View
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>