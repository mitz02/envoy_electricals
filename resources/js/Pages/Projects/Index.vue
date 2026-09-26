<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const origin = window.location.origin;

const props = defineProps({
    projects: { type: Array, required: true },
});

const activeIndex = ref(null);
const activePhoto = ref(0);

const statusLabels = {
    completed: 'Completed',
    in_progress: 'In Progress',
    installation: 'Installation',
    approved: 'Approved',
};

const statusColors = {
    completed: 'bg-emerald-100 text-emerald-700',
    in_progress: 'bg-amber-100 text-amber-700',
    installation: 'bg-blue-100 text-blue-700',
    approved: 'bg-slate-100 text-slate-600',
};

function coverImage(project) {
    const published = (project.media || []).filter((m) => m.published && m.type === 'image');
    const img = published[0];
    return img ? `/storage/${img.path}` : '/images/landing/solar_installation.jpg';
}

function galleryImages(project) {
    return (project.media || []).filter((m) => m.published).map((m) => `/storage/${m.path}`);
}

function naira(v) {
    return '₦' + Number(v || 0).toLocaleString('en-NG');
}
</script>

<template>
    <Head title="Solar Installation Projects & Case Studies in Nigeria | Envoy Electricals">
        <meta name="description" content="Real solar and electrical projects delivered by Envoy Electricals across Nigeria — residential, commercial and industrial installations with photos and results." />
        <link rel="canonical" :href="origin + '/projects'" />
    </Head>
    <header class="bg-[#0D1527] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight">Our Solar Projects</h1>
            <p class="mt-3 text-slate-300 max-w-2xl mx-auto">Real installations across homes, offices and businesses — proof that clean energy works, everyday.</p>
        </div>
    </header>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div v-if="projects.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <button
                v-for="(p, idx) in projects"
                :key="p.id"
                type="button"
                class="group bg-white rounded-3xl overflow-hidden border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 text-left"
                @click="activeIndex = idx; activePhoto = 0"
            >
                <div class="aspect-[4/3] overflow-hidden bg-slate-50 relative">
                    <img :src="coverImage(p)" :alt="p.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                    <span class="absolute top-3 left-3 rounded-full px-3 py-1 text-[10px] font-black uppercase" :class="statusColors[p.status] || 'bg-slate-100 text-slate-600'">{{ statusLabels[p.status] || p.status }}</span>
                </div>
                <div class="p-5">
                    <h3 class="text-base font-extrabold text-slate-950">{{ p.name }}</h3>
                    <p class="mt-1 text-sm text-slate-500 line-clamp-2">{{ p.description || p.location || 'Residential/commercial solar installation.' }}</p>
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                        <span v-if="p.location" class="flex items-center gap-1"><i class="bi bi-geo-alt text-amber-500"></i> {{ p.location }}</span>
                        <span v-if="p.contract_value > 0" class="font-bold text-slate-700">{{ naira(p.contract_value) }}</span>
                    </div>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-amber-600 group-hover:text-amber-700">View project <i class="bi bi-arrow-right"></i></span>
                </div>
            </button>
        </div>

        <p v-else class="py-20 text-center text-slate-400">Our project gallery is coming soon — check back shortly.</p>

        <div class="mt-12 rounded-3xl bg-[#0D1527] p-8 sm:p-12 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div>
                <h2 class="text-2xl font-extrabold text-white">Want a system like these?</h2>
                <p class="mt-2 text-slate-300 max-w-xl">We design, supply and install across Lagos and Nigeria. Start with a free calculator run.</p>
            </div>
            <a href="/calculator" class="shrink-0 rounded-full bg-amber-400 px-8 py-3.5 font-bold text-slate-950 hover:bg-amber-300 transition-colors">Get Free Quote</a>
        </div>
    </section>

    <!-- Lightbox -->
    <div v-if="activeIndex !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4" @click="activeIndex = null">
        <div class="relative max-w-4xl w-full" @click.stop>
            <div class="mb-3 flex items-center justify-between text-white">
                <div>
                    <h3 class="text-xl font-extrabold">{{ projects[activeIndex].name }}</h3>
                    <p v-if="projects[activeIndex].description" class="text-sm text-slate-400 mt-1">{{ projects[activeIndex].description }}</p>
                </div>
                <button type="button" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20" @click="activeIndex = null">✕</button>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white aspect-[16/9]">
                <img :src="galleryImages(projects[activeIndex])[activePhoto] || coverImage(projects[activeIndex])" :alt="projects[activeIndex].name" class="w-full h-full object-cover" />
            </div>
            <div v-if="galleryImages(projects[activeIndex]).length > 1" class="mt-3 flex gap-2 overflow-x-auto">
                <img v-for="(img, gi) in galleryImages(projects[activeIndex])" :key="gi" :src="img" class="h-20 w-28 shrink-0 rounded-lg object-cover cursor-pointer border-2 transition-all" :class="gi === activePhoto ? 'border-amber-400' : 'border-transparent opacity-70 hover:opacity-100'" :alt="projects[activeIndex].name" @click="activePhoto = gi" />
            </div>
        </div>
    </div>
</template>