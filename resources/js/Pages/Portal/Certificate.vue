<script setup>
import { computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    certificate: { type: Object, required: true },
});

const canBack = !!usePage().props.auth?.user;
const isIssued = computed(() => props.certificate.status === 'issued');

const url = new URL(window.location.href);
const autoDownload = url.searchParams.get('download') === '1' || url.searchParams.get('download') === 'true';

function printCert() {
    window.print();
}

onMounted(() => {
    if (isIssued.value && autoDownload) {
        setTimeout(() => window.print(), 400);
    }
});

const formatDate = (d) => new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });

const levelLabel = computed(() => props.certificate.training.level.charAt(0).toUpperCase() + props.certificate.training.level.slice(1));

const durationLabel = computed(() => `${props.certificate.training.duration_weeks} ${props.certificate.training.duration_weeks === 1 ? 'Week' : 'Weeks'}`);
</script>

<template>
    <Head title="Certificate of Completion" />

    <div class="cert-print-root min-h-screen bg-slate-800 print:bg-white">
        <!-- Toolbar (hidden when printing) -->
        <div
            v-if="isIssued"
            class="sticky top-0 z-40 flex flex-wrap items-center justify-between gap-3 border-b border-white/10 bg-slate-900 px-4 py-3 print:hidden"
        >
            <Link :href="canBack ? route('portal.certificates') : '/'" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-300 hover:text-white">
                <i class="bi bi-arrow-left"></i> {{ canBack ? 'My certificates' : 'Portal home' }}
            </Link>
            <div class="flex items-center gap-2.5">
                <a
                    :href="route('portal.certificates.show', props.certificate.id)"
                    class="inline-flex items-center gap-2 rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-300 transition-colors hover:bg-white/10 hover:text-white"
                >
                    <i class="bi bi-eye"></i> Preview
                </a>
                <button
                    @click="printCert"
                    class="inline-flex items-center gap-2 rounded-lg bg-yellow-400 px-4 py-2 text-sm font-bold text-[#0D1527] transition-colors hover:bg-yellow-300"
                >
                    <i class="bi bi-download"></i> Download PDF
                </button>
                <button
                    @click="printCert"
                    class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-white/20"
                >
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <!-- Voided banner (hidden when printing) -->
        <div v-else class="flex items-center justify-center bg-slate-900 px-4 py-4 print:hidden">
            <div class="flex w-full max-w-[900px] items-start gap-3 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 text-lg shrink-0"></i>
                <div>
                    <p class="font-bold">This certificate has been voided.</p>
                    <p class="mt-0.5 text-red-300/80">Downloading has been disabled. Please contact the academy to resolve this.</p>
                </div>
                <Link :href="route('portal.certificates')" class="ml-auto shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-bold text-white hover:bg-white/20">
                    <i class="bi bi-arrow-left"></i> Back
                </Link>
            </div>
        </div>

        <div class="cert-print-wrap flex items-center justify-center p-4 sm:p-10 print:p-0">
            <div
                class="cert-print-box w-full max-w-[900px] overflow-hidden rounded-md bg-white shadow-2xl ring-1 ring-slate-200"
                style="aspect-ratio: 900 / 636;"
            >
                <!-- Certificate body -->
                <div class="flex h-full flex-col border-[10px] border-[#0D1527] p-4 sm:p-6">
                    <div class="relative flex h-full flex-col overflow-hidden border-2 border-[#FACC15]/70 bg-gradient-to-br from-white via-[#FFFDF8] to-[#FAF8F2] px-6 py-4 sm:px-12 sm:py-6">
                        <!-- Texture + watermarks -->
                        <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(rgba(250,204,21,0.5) 1px, transparent 1px); background-size: 26px 26px;" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute -right-10 -bottom-10 h-56 w-56 rounded-full bg-[#FACC15]/15 blur-2xl" aria-hidden="true"></div>
                        <img src="/envoy_images/logo.png" alt="" class="pointer-events-none absolute -bottom-8 right-0 h-[70%] w-auto object-contain opacity-[0.05]" aria-hidden="true" />

                        <!-- Corner accents -->
                        <span class="pointer-events-none absolute left-3 top-3 h-5 w-5 border-l-2 border-t-2 border-[#40e0d0]"></span>
                        <span class="pointer-events-none absolute right-3 top-3 h-5 w-5 border-r-2 border-t-2 border-[#40e0d0]"></span>
                        <span class="pointer-events-none absolute bottom-3 left-3 h-5 w-5 border-b-2 border-l-2 border-[#40e0d0]"></span>
                        <span class="pointer-events-none absolute bottom-3 right-3 h-5 w-5 border-b-2 border-r-2 border-[#40e0d0]"></span>

                        <!-- Header -->
                        <div class="relative flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-[#FACC15]/50 bg-white p-1 shadow-sm sm:h-14 sm:w-14">
                                    <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-full w-full object-contain" />
                                </div>
                                <div class="leading-tight">
                                    <p class="text-base font-black tracking-tight text-[#0D1527] sm:text-xl">Envoy Electricals Ltd.</p>
                                    <p class="text-[9px] font-bold uppercase tracking-[0.3em] text-[#40e0d0] sm:text-[11px]">Envoy Academy</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <svg class="ml-auto h-8 w-8 text-[#FACC15] sm:h-10 sm:w-10" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0l2.83 6.59L22 8.27l-5 4.87L18.56 21 12 17.27 5.44 21 7 13.14 2 8.27l7.17-1.68z" />
                                </svg>
                                <p class="mt-1 text-[10px] font-black uppercase tracking-[0.3em] text-[#0D1527] sm:text-xs">Certificate of Completion</p>
                            </div>
                        </div>

                        <!-- Ornament divider -->
                        <div class="relative mt-3 mb-1.5 flex items-center gap-3">
                            <span class="h-px flex-1 bg-gradient-to-r from-transparent to-[#FACC15]/70"></span>
                            <i class="bi bi-award-fill text-sm text-[#40e0d0]"></i>
                            <span class="h-px flex-1 bg-gradient-to-l from-transparent to-[#FACC15]/70"></span>
                        </div>

                        <!-- Center body -->
                        <div class="relative flex flex-1 flex-col items-center justify-center text-center">
                            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-[#0D1527] sm:text-xs">This is to certify that</p>
                            <p class="mt-2 font-serif text-3xl font-bold italic tracking-wide text-[#0D1527] sm:text-5xl">{{ certificate.trainee.name }}</p>
                            <p class="mt-3 max-w-xl text-[11px] leading-snug text-slate-600 sm:text-sm">
                                has successfully completed the prescribed training program of
                            </p>
                            <p class="mt-1.5 text-lg font-black uppercase tracking-wide text-[#0D1527] sm:text-2xl">{{ certificate.training.title }}</p>
                            <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-[#FACC15]/50 bg-[#FBF6D9] px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-[#0D1527]">
                                    <i class="bi bi-bar-chart-fill"></i> Level · {{ levelLabel }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-[#FACC15]/50 bg-[#FBF6D9] px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-[#0D1527]">
                                    <i class="bi bi-clock-history"></i> {{ durationLabel }}
                                </span>
                                <span v-if="certificate.grade !== null && certificate.grade !== undefined" class="inline-flex items-center gap-1.5 rounded-full border border-[#FACC15]/50 bg-[#FBF6D9] px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-[#0D1527]">
                                    <i class="bi bi-trophy"></i> Grade · {{ certificate.grade }}%
                                </span>
                            </div>
                        </div>

                        <!-- Details band -->
                        <div class="relative mt-3 grid grid-cols-2 gap-y-1.5 border-y border-[#FACC15]/40 bg-[#FAF8F2] px-2 py-2 text-center sm:grid-cols-4">
                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-slate-400">Registration No.</p>
                                <p class="font-mono text-[10px] font-bold text-slate-800 sm:text-xs">{{ certificate.trainee.ref_id }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-slate-400">Program Ref.</p>
                                <p class="font-mono text-[10px] font-bold text-slate-800 sm:text-xs">{{ certificate.training.ref_id }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-slate-400">Enrolled on</p>
                                <p class="text-[10px] font-bold text-slate-800 sm:text-xs">{{ certificate.enrolled_at ? formatDate(certificate.enrolled_at) : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-slate-400">Issued on</p>
                                <p class="text-[10px] font-bold text-slate-800 sm:text-xs">{{ formatDate(certificate.issued_at) }}</p>
                            </div>
                        </div>

                        <!-- Signatures -->
                        <div class="relative mt-auto flex items-end justify-between pt-2">
                            <div class="flex-1 text-center">
                                <div class="mx-auto w-28 border-t-2 border-[#0D1527]/20 sm:w-32"></div>
                                <p class="mt-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-[#0D1527]">Academy Coordinator</p>
                            </div>
                            <div class="mx-8 hidden text-center text-[#FACC15] sm:block">
                                <i class="bi bi-patch-check-fill text-lg"></i>
                            </div>
                            <div class="flex-1 text-center">
                                <div class="mx-auto w-28 border-t-2 border-[#0D1527]/20 sm:w-32"></div>
                                <p class="mt-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-[#0D1527]">Managing Director</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="relative pt-1.5 text-center">
                            <p class="font-mono text-[9px] font-bold uppercase tracking-[0.25em] text-slate-500 sm:text-[10px]">Certificate No. {{ certificate.certificate_no }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 0;
    }

    html,
    body {
        width: 100%;
        height: 100%;
        background: #fff !important;
    }

    .cert-print-root {
        min-height: 0 !important;
    }

    .cert-print-wrap {
        padding: 0 !important;
        display: flex !important;
        align-items: stretch !important;
    }

    .cert-print-box {
        width: 100% !important;
        max-width: 100% !important;
        aspect-ratio: 900 / 636 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>