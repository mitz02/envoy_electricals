<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    packages: { type: Array, required: true },
});

function naira(v) {
    return '₦' + Number(v || 0).toLocaleString('en-NG');
}
</script>

<template>
    <header class="bg-[#0D1527] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight">Complete Solar Packages</h1>
            <p class="mt-3 text-slate-300 max-w-2xl mx-auto">Fully kitted, engineered packages for homes and offices — installation available nationwide.</p>
        </div>
    </header>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="pkg in packages"
                :key="pkg.id"
                class="relative bg-white rounded-3xl border overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col"
                :class="pkg.is_featured ? 'border-amber-400 ring-2 ring-amber-400/20' : 'border-slate-200/80'"
            >
                <div v-if="pkg.is_featured" class="absolute top-4 right-4 rounded-full bg-amber-400 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-slate-950">Popular</div>

                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-extrabold text-slate-950">{{ pkg.name }}</h2>
                    <p class="mt-2 text-sm text-slate-500 min-h-10">{{ pkg.description }}</p>

                    <div class="mt-4 space-y-1.5 text-sm text-slate-700">
                        <p v-if="pkg.estimated_load_capacity" class="flex items-center gap-2"><i class="bi bi-lightning-charge text-amber-500"></i> Covers up to {{ pkg.estimated_load_capacity }}</p>
                        <p v-if="pkg.inverter_capacity" class="flex items-center gap-2"><i class="bi bi-battery-charging text-amber-500"></i> {{ pkg.inverter_capacity }} inverter</p>
                        <p v-if="pkg.warranty" class="flex items-center gap-2"><i class="bi bi-shield-check text-amber-500"></i> {{ pkg.warranty }} warranty</p>
                    </div>

                    <div class="mt-6 rounded-2xl bg-slate-50 border border-slate-100 p-4">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2.5">What's included</p>
                        <ul class="space-y-2">
                            <li v-for="item in pkg.items" :key="item.id" class="flex items-start gap-2 text-sm text-slate-600">
                                <i class="bi bi-check2 text-emerald-500 mt-0.5"></i>
                                <span>{{ item.quantity }}× {{ item.name }}<span v-if="item.specification" class="text-slate-400"> — {{ item.specification }}</span></span>
                            </li>
                            <li v-if="!pkg.items?.length" class="text-sm text-slate-400">Components configured on request.</li>
                        </ul>
                    </div>

                    <div class="mt-6 flex items-end justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Package price</p>
                            <p class="text-2xl font-extrabold text-slate-950">{{ naira(pkg.package_price) }}</p>
                        </div>
                        <div v-if="pkg.installation_cost > 0" class="text-right">
                            <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Installation</p>
                            <p class="text-sm font-bold text-slate-600">{{ naira(pkg.installation_cost) }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8 pt-0 mt-auto">
                    <Link
                        :href="`/calculator?package=${pkg.id}`"
                        class="block w-full rounded-xl bg-[#0D1527] py-3.5 text-center text-sm font-bold text-white hover:bg-amber-400 hover:text-slate-950 transition-colors"
                    >Get Free Quote</Link>
                    <p v-if="pkg.availability === 'unavailable'" class="mt-2 text-center text-xs font-semibold text-red-500">Currently unavailable — reserved for approved leads</p>
                </div>
            </div>
        </div>

        <p v-if="!packages.length" class="py-20 text-center text-slate-400">Packages are being configured — check back soon or use the calculator.</p>

        <div class="mt-12 rounded-3xl bg-[#0D1527] p-8 sm:p-12 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div>
                <h2 class="text-2xl font-extrabold text-white">Every system is custom-designed for your load</h2>
                <p class="mt-2 text-slate-300 max-w-xl">Tell us what you run and we'll recommend the exact package — no guesswork, no overselling.</p>
            </div>
            <Link href="/calculator" class="shrink-0 rounded-full bg-amber-400 px-8 py-3.5 font-bold text-slate-950 hover:bg-amber-300 transition-colors">Run the Calculator</Link>
        </div>
    </section>
</template>