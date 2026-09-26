<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    programs: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

defineOptions({ layout: PublicLayout });

const hasUser = !!usePage().props.auth?.user;

const levelStyles = {
    beginner: 'bg-emerald-100 text-emerald-800',
    intermediate: 'bg-amber-100 text-amber-800',
    advanced: 'bg-red-100 text-red-700',
};
</script>

<template>
    <Head>
        <title>Envoy Academy — Solar, Electrical & Installation Training in Nigeria</title>
        <meta name="description" content="Join Envoy Academy as a trainee or apprentice. Practical solar, electrical & installation training with hands-on workshops, online progress tracking, and industry-recognized certificates." />
        <link rel="canonical" href="https://envoyelectricals.com/portal" />
    </Head>

    <div class="min-h-screen bg-[#FAF8F2] font-sans text-slate-800 antialiased">
        <!-- Hero -->
        <section class="relative overflow-hidden bg-[#0D1527]">
            <div class="absolute inset-0" aria-hidden="true">
                <div class="absolute -top-24 right-20 h-80 w-80 rounded-full bg-yellow-400/10 blur-[100px]"></div>
                <div class="absolute bottom-0 left-10 h-72 w-72 rounded-full bg-[#40e0d0]/10 blur-[100px]"></div>
            </div>
            <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                <span class="inline-flex items-center gap-2 rounded-full border border-yellow-400/30 bg-yellow-400/10 px-4 py-1.5 text-xs font-semibold text-yellow-400">
                    <i class="bi bi-mortarboard-fill"></i> Practical skills. Certified careers.
                </span>
                <h1 class="mt-6 max-w-2xl text-3xl font-black leading-tight tracking-tight text-white sm:text-5xl">
                    Learn solar, electrical & install skills with
                    <span class="bg-gradient-to-r from-yellow-400 to-[#40e0d0] bg-clip-text text-transparent">Envoy Electricals</span>
                </h1>
                <p class="mt-5 max-w-xl text-sm leading-relaxed text-slate-400 sm:text-base">
                    Join our academy as a trainee or apprentice, train alongside working professionals,
                    track your progress online and earn a certificate when you complete a program.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <template v-if="hasUser">
                        <Link href="/portal/dashboard" class="inline-flex items-center gap-2 rounded-xl bg-yellow-400 px-6 py-3 text-sm font-bold text-[#0D1527] hover:bg-yellow-300 transition-colors">
                            Go to your dashboard <i class="bi bi-arrow-right"></i>
                        </Link>
                    </template>
                    <template v-else>
                        <Link href="/portal/register" class="inline-flex items-center gap-2 rounded-xl bg-yellow-400 px-6 py-3 text-sm font-bold text-[#0D1527] hover:bg-yellow-300 transition-colors">
                            Register as a trainee <i class="bi bi-arrow-right"></i>
                        </Link>
                        <Link href="/portal/login" class="inline-flex items-center gap-2 rounded-xl border border-white/15 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                            I already have an account
                        </Link>
                    </template>
                </div>

                <!-- Stats -->
                <div class="mt-12 grid max-w-lg grid-cols-3 gap-4">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-black text-yellow-400">{{ stats.programs }}</p>
                        <p class="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-400">Programs</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-black text-[#40e0d0]">{{ stats.graduates }}</p>
                        <p class="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-400">Certificates issued</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-black text-white">100%</p>
                        <p class="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-400">Hands-on</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Programs -->
        <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                        <span class="h-px w-6 bg-[#40e0d0]"></span> Training Programs
                    </span>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Programs on offer</h2>
                </div>
            </div>

            <div v-if="programs.length" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="p in programs" :key="p.id" class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs transition-shadow hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="levelStyles[p.level] || 'bg-slate-100 text-slate-600'">
                            {{ p.level.charAt(0).toUpperCase() + p.level.slice(1) }}
                        </span>
                        <span class="text-[11px] font-medium text-slate-400">{{ p.ref_id }}</span>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-slate-950">{{ p.title }}</h3>
                    <p class="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-500">{{ p.description || 'Practical, instructor-led training at Envoy Electricals.' }}</p>
                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                        <div class="flex items-center gap-4 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1.5"><i class="bi bi-clock"></i>{{ p.duration_weeks }} {{ p.duration_weeks === 1 ? 'week' : 'weeks' }}</span>
                            <span v-if="p.capacity" class="inline-flex items-center gap-1.5"><i class="bi bi-people"></i>{{ p.enrolled_count }}/{{ p.capacity }}</span>
                        </div>
                        <Link
                            :href="hasUser ? route('portal.enroll', p.id) : route('portal.register')"
                            :method="hasUser ? 'post' : 'get'"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-[#0D1527] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#0D1527]/90 transition-colors"
                        >
                            {{ hasUser ? 'Enrol' : 'Join program' }} <i class="bi bi-arrow-right"></i>
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-4">
                    <i class="bi bi-mortarboard text-3xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No training programs yet</h3>
                <p class="text-slate-500 mb-6 max-w-md mx-auto">
                    We're currently preparing new training programs. Check back soon or contact us to express your interest.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <Link href="/contact" class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0D1527]/90 transition-colors">
                        <i class="bi bi-envelope"></i> Contact Us
                    </Link>
                    <Link href="/portal/register" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="bi bi-bell"></i> Notify Me
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>