<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    package: { type: Object, required: true },
    image: { type: Object, default: null },
});

function naira(v) {
    return '₦' + Number(v || 0).toLocaleString('en-NG');
}

function warn() {
    if (!localStorage) return;
    // Remember selection so the calculator can prefill it.
    try {
        localStorage.setItem('envoy_package', JSON.stringify({ id: props.package.id, name: props.package.name }));
    } catch (e) {
        /* ignore */
    }
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-6">
            <Link href="/packages" class="hover:text-amber-600">Solar Packages</Link>
            <span>/</span>
            <span class="text-slate-800 truncate">{{ package.name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
            <div>
                <div class="aspect-[4/3] overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    <img
                        :src="image ? `/storage/${image.path}` : '/images/landing/solar_installation.jpg'"
                        :alt="package.name"
                        class="w-full h-full object-cover"
                    />
                </div>
            </div>

            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-950 tracking-tight">{{ package.name }}</h1>
                    <span v-if="package.is_featured" class="rounded-full bg-amber-400 px-3 py-1 text-[10px] font-black uppercase text-slate-950">Popular</span>
                </div>
                <p class="mt-3 text-slate-600 leading-relaxed">{{ package.description }}</p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span v-if="package.estimated_load_capacity" class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Load: {{ package.estimated_load_capacity }}</span>
                    <span v-if="package.inverter_capacity" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ package.inverter_capacity }} inverter</span>
                    <span v-if="package.warranty" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ package.warranty }} warranty</span>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-3">What's included</p>
                    <ul class="space-y-2.5">
                        <li v-for="item in package.items" :key="item.id" class="flex items-start gap-2 text-sm text-slate-600">
                            <i class="bi bi-check2 text-emerald-500 mt-0.5"></i>
                            <span>{{ item.quantity }}× {{ item.name }}<span v-if="item.specification" class="text-slate-400"> — {{ item.specification }}</span></span>
                        </li>
                        <li v-if="!package.items?.length" class="text-sm text-slate-400">Components configured on request.</li>
                    </ul>
                </div>

                <div class="mt-6 flex items-end justify-between bg-[#0D1527] rounded-2xl p-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Package price</p>
                        <p class="text-3xl font-extrabold text-white">{{ naira(package.package_price) }}</p>
                    </div>
                    <div v-if="package.installation_cost > 0" class="text-right">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Installation</p>
                        <p class="text-sm font-bold text-amber-400">{{ naira(package.installation_cost) }}</p>
                    </div>
                </div>

                <Link
                    :href="`/calculator?package=${package.id}`"
                    class="mt-6 block w-full rounded-xl bg-amber-400 py-4 text-center text-sm font-black text-slate-950 hover:bg-amber-300 transition-colors"
                    @click="warn"
                >Request a Free Quote for this Package</Link>
                <Link href="/packages" class="mt-3 block text-center text-sm font-semibold text-slate-500 hover:text-amber-600">← Back to all packages</Link>
            </div>
        </div>
    </div>
</template>