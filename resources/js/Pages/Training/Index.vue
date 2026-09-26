<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const origin = window.location.origin;

defineProps({
    trainings: { type: Array, default: () => [] },
});

const hasUser = !!usePage().props.auth?.user;

const levelStyles = {
    beginner: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    intermediate: 'bg-amber-100 text-amber-800 border-amber-200',
    advanced: 'bg-red-100 text-red-700 border-red-200',
};

const levelIcons = {
    beginner: 'bi-mortarboard',
    intermediate: 'bi-lightning-charge',
    advanced: 'bi-award',
};

const formatPrice = (price) => {
    if (!price || price === 0) return 'Free';
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 0 }).format(price);
};

const getCapacityStatus = (training) => {
    if (!training.capacity) return { text: 'Unlimited seats', class: 'text-emerald-600' };
    const remaining = training.capacity - training.enrolled_count;
    if (remaining <= 0) return { text: 'Full', class: 'text-red-600' };
    if (remaining <= 5) return { text: `Only ${remaining} seats left`, class: 'text-amber-600' };
    return { text: `${remaining} of ${training.capacity} seats left`, class: 'text-emerald-600' };
};

const curriculumPreview = (t) => {
    if (t.weeks && t.weeks.length) {
        const lessons = t.weeks.flatMap((w) => w.lessons || []);
        return lessons.map((l) => l.title);
    }
    return (t.curriculum || '').split('\n').filter((i) => i.trim()).map((i) => i.trim());
};
</script>

<template>
    <Head title="Solar Training Programs & Electrical Courses in Nigeria | Envoy Solar Academy">
        <meta name="description" content="Practical solar installation, inverter and electrical training programs in Akure, Nigeria. Hands-on curriculum, certification and mentorship from Envoy Electricals." />
        <link rel="canonical" :href="origin + '/training'" />
    </Head>
    <div class="min-h-screen bg-[#FAF8F2] font-sans text-slate-800 antialiased">
        <!-- Programs Grid -->
        <section id="programs" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                    <span class="h-px w-8 bg-[#40e0d0]"></span> Our Programs
                </span>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                    Find the Right Program for You
                </h2>
                <p class="mt-4 text-lg text-slate-500">
                    Each program combines classroom theory with extensive hands-on practice in our fully equipped workshops.
                </p>
            </div>

            <div v-if="trainings.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="t in trainings"
                    :key="t.id"
                    class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                >
                    <!-- Card Top Accent -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-yellow-400 to-[#40e0d0] opacity-0 group-hover:opacity-100 transition-opacity"></div>

                    <!-- Image/Icon Placeholder -->
                    <div class="relative aspect-[4/3] bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center overflow-hidden">
                        <img
                            v-if="t.image_path"
                            :src="t.image_path"
                            :alt="t.title"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                        <div v-else-if="!t.image_path" class="absolute inset-0 bg-gradient-to-tr from-yellow-400/10 to-[#40e0d0]/10 group-hover:from-yellow-400/20 group-hover:to-[#40e0d0]/20 transition-all duration-500"></div>
                        <i v-else :class="['bi', levelIcons[t.level] || 'bi-mortarboard', 'text-6xl', 'text-yellow-400/50', 'group-hover:text-yellow-400', 'transition-all', 'duration-500', 'relative', 'z-10']"></i>

                        <!-- Level Badge -->
                        <div class="absolute top-4 left-4 z-10">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold" :class="levelStyles[t.level] || 'bg-slate-100 text-slate-600 border-slate-200'">
                                <i :class="['bi', levelIcons[t.level] || 'bi-mortarboard']"></i>
                                {{ t.level.charAt(0).toUpperCase() + t.level.slice(1) }}
                            </span>
                        </div>

                        <!-- Price Tag -->
                        <div v-if="t.price && t.price > 0" class="absolute top-4 right-4 z-10">
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/95 backdrop-blur px-3 py-1.5 text-sm font-bold text-slate-900 shadow-lg">
                                {{ formatPrice(t.price) }}
                            </span>
                        </div>
                        <div v-else class="absolute top-4 right-4 z-10">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-800">
                                <i class="bi bi-gift"></i> Free
                            </span>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-medium uppercase tracking-wider text-slate-400">{{ t.ref_id }}</span>
                        </div>

                        <h3 class="text-xl font-bold text-slate-950 group-hover:text-[#0D1527] transition-colors mb-2">{{ t.title }}</h3>

                        <p class="flex-1 text-sm leading-relaxed text-slate-500 line-clamp-3 mb-4">{{ t.description || 'Comprehensive hands-on training program at Envoy Academy.' }}</p>

                        <!-- Meta Info -->
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 border-t border-slate-100 pt-4 mb-4">
                            <span class="inline-flex items-center gap-1.5">
                                <i class="bi bi-calendar3"></i>
                                {{ t.duration_weeks }} {{ t.duration_weeks === 1 ? 'week' : 'weeks' }}
                            </span>
                            <span v-if="t.start_date" class="inline-flex items-center gap-1.5">
                                <i class="bi bi-calendar-event"></i>
                                {{ new Date(t.start_date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}
                            </span>
                            <span v-if="t.capacity" class="inline-flex items-center gap-1.5" :class="getCapacityStatus(t).class">
                                <i class="bi bi-people"></i>
                                {{ getCapacityStatus(t).text }}
                            </span>
                        </div>

                        <!-- Curriculum Preview -->
                        <div v-if="curriculumPreview(t).length" class="mb-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">What You'll Learn</h4>
                            <ul class="space-y-1 max-h-24 overflow-hidden">
                                <li v-for="(item, idx) in curriculumPreview(t).slice(0, 4)" :key="idx" class="flex items-start gap-2 text-xs text-slate-500">
                                    <i class="bi bi-check text-emerald-400 mt-0.5 flex-shrink-0"></i>
                                    <span class="line-clamp-1">{{ item }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Action Button -->
                        <Link
                            :href="`/training/${t.id}`"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0D1527] px-6 py-3 text-sm font-bold text-white hover:bg-[#0D1527]/90 transition-all hover:shadow-lg hover:shadow-[#0D1527]/30 group-hover:scale-[1.02]"
                        >
                            View Details <i class="bi bi-arrow-right"></i>
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-20">
                <div class="inline-flex flex-col items-center gap-4 rounded-2xl border-2 border-dashed border-slate-300 bg-white p-12">
                    <i class="bi bi-collection text-5xl text-slate-300"></i>
                    <h3 class="text-lg font-semibold text-slate-600">No Programs Available</h3>
                    <p class="text-slate-400">Check back soon for new training programs.</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-gradient-to-r from-[#0D1527] to-[#1a233a] p-8 sm:p-12 lg:p-16 text-center relative overflow-hidden">
                <div class="absolute inset-0" aria-hidden="true">
                    <div class="absolute -top-20 right-20 h-64 w-64 rounded-full bg-yellow-400/10 blur-[100px]"></div>
                    <div class="absolute bottom-20 left-20 h-64 w-64 rounded-full bg-[#40e0d0]/10 blur-[100px]"></div>
                </div>

                <div class="relative max-w-3xl mx-auto">
                    <h2 class="text-3xl font-black text-white sm:text-4xl">
                        Ready to Start Your Career?
                    </h2>
                    <p class="mt-4 text-lg text-slate-300">
                        Join hundreds of trainees who have launched successful careers in solar and electrical installation.
                        Register today and secure your spot in the next cohort.
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Link href="/portal/register" class="inline-flex items-center gap-2 rounded-xl bg-yellow-400 px-8 py-4 text-base font-bold text-[#0D1527] hover:bg-yellow-300 transition-all shadow-lg hover:shadow-yellow-400/30">
                            <i class="bi bi-person-plus"></i> Register Now
                        </Link>
                        <Link href="/contact" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/20 px-8 py-4 text-base font-semibold text-white hover:bg-white/10 transition-all">
                            <i class="bi bi-question-circle"></i> Have Questions?
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>