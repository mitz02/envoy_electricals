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
</script>

<template>
    <Head title="Certificate of Completion" />

    <div class="min-h-screen bg-slate-800 print:bg-white">
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

        <div class="flex items-center justify-center p-4 sm:p-10 print:p-0">
            <div
                class="w-full max-w-[900px] overflow-hidden rounded-md bg-white shadow-2xl ring-1 ring-slate-200"
                style="aspect-ratio: 900 / 636;"
            >
                <!-- Certificate body -->
                <div class="flex h-full flex-col border-[10px] border-[#0D1527] p-5 sm:p-8">
                    <div class="relative flex h-full flex-col border border-yellow-400/60 px-4 py-5 sm:px-10 sm:py-7">
                        <!-- Watermark -->
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.04]" aria-hidden="true">
                            <i class="bi bi-patch-check-fill text-[16rem] text-[#0D1527]"></i>
                        </div>

                        <!-- Top row -->
                        <div class="relative flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white p-1 ring-1 ring-slate-200">
                                    <img src="/envoy_images/logo.png" alt="Envoy Electric" class="h-full w-full object-contain" />
                                </div>
                                <div class="leading-tight">
                                    <p class="text-sm font-black tracking-tight text-[#0D1527] sm:text-base">Envoy Electricals Ltd.</p>
                                    <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-slate-400 sm:text-[10px]">Envoy Academy</p>
                                </div>
                            </div>
                            <svg class="h-9 w-9 text-yellow-500" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0l2.83 6.59L22 8.27l-5 4.87L18.56 21 12 17.27 5.44 21 7 13.14 2 8.27l7.17-1.68z" />
                            </svg>
                        </div>

                        <!-- Center -->
                        <div class="relative flex flex-1 flex-col items-center justify-center text-center">
                            <p class="text-[10px] font-bold uppercase tracking-[0.35em] text-yellow-500 sm:text-xs">Certificate of Completion</p>
                            <p class="mt-2 text-[11px] text-slate-400 sm:text-sm">This is to certify that</p>
                            <p class="mt-2 font-serif text-2xl font-bold uppercase tracking-wide text-[#0D1527] sm:text-4xl">
                                {{ certificate.trainee.name }}
                            </p>
                            <p class="mx-auto mt-4 max-w-md text-[11px] leading-relaxed text-slate-600 sm:text-sm">
                                has successfully completed the training program
                            </p>
                            <p class="mt-1 text-base font-black text-[#0D1527] sm:text-2xl">{{ certificate.training.title }}</p>
                            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-8 gap-y-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">
                                <span>Level · <span class="capitalize">{{ certificate.training.level }}</span></span>
                                <span>Duration · {{ certificate.training.duration_weeks }} {{ certificate.training.duration_weeks === 1 ? 'Week' : 'Weeks' }}</span>
                                <span v-if="certificate.grade !== null && certificate.grade !== undefined">Grade · {{ certificate.grade }}%</span>
                            </div>
                        </div>

                        <!-- Bottom row -->
                        <div class="relative flex items-end justify-between">
                            <div>
                                <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-400 sm:text-[11px]">Certificate No.</p>
                                <p class="font-mono text-xs font-bold text-slate-700 sm:text-sm">{{ certificate.certificate_no }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-400 sm:text-[11px]">Issued on</p>
                                <p class="text-xs font-bold text-slate-700 sm:text-sm">{{ formatDate(certificate.issued_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>