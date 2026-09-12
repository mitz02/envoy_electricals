<script setup>
import { Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';

defineOptions({ layout: PortalLayout });

defineProps({
    certificates: { type: Array, default: () => [] },
});

const typeStyles = { beginner: 'bg-emerald-100 text-emerald-800', intermediate: 'bg-amber-100 text-amber-800', advanced: 'bg-red-100 text-red-700' };

const fmtDate = (d) => new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-black uppercase tracking-tight text-[#0D1527]">My Certificates</h1>
            <p class="mt-1 text-sm text-slate-500">Certificates are issued and enabled for download by our admin team once you complete a training program.</p>
        </div>

        <div v-if="certificates.length" class="grid gap-4 sm:grid-cols-2">
            <div
                v-for="c in certificates"
                :key="c.id"
                class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_2px_10px_rgba(13,21,39,0.04)] transition-transform duration-200"
                :class="c.status === 'void' ? 'opacity-70' : 'hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-12px_rgba(13,21,39,0.16)]'"
            >
                <div class="flex items-center justify-between bg-gradient-to-r from-[#0D1527] to-[#1A365D] px-5 py-4">
                    <div class="flex items-center gap-2.5 text-white">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-yellow-400/15 text-yellow-400">
                            <i class="bi bi-patch-check-fill text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold leading-tight">Certificate of Completion</p>
                            <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">{{ c.training.title }}</p>
                        </div>
                    </div>
                    <span
                        class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                        :class="c.status === 'void' ? 'bg-red-500/20 text-red-300' : 'bg-emerald-500/20 text-emerald-300'"
                    >
                        {{ c.status }}
                    </span>
                </div>

                <div class="px-5 py-4">
                    <p class="text-xs text-slate-400">Certificate no.</p>
                    <p class="font-mono text-sm font-bold text-slate-900">{{ c.certificate_no }}</p>
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                        <span>Issued {{ c.issued_at ? fmtDate(c.issued_at) : '—' }}</span>
                        <span v-if="c.grade !== null && c.grade !== undefined">Grade: {{ c.grade }}%</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 border-t border-slate-100 px-5 py-3.5">
                    <template v-if="c.status === 'issued'">
                        <Link
                            :href="route('portal.certificates.show', c.id)"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-[#0D1527] px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-[#0D1527]/90"
                        >
                            <i class="bi bi-eye"></i> View certificate
                        </Link>
                        <Link
                            :href="route('portal.certificates.show', { certificate: c.id, download: true })"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-yellow-400 to-amber-400 px-3.5 py-2 text-xs font-bold text-[#0D1527] transition-colors hover:brightness-105"
                        >
                            <i class="bi bi-download"></i> Download
                        </Link>
                    </template>
                    <template v-else>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3.5 py-2 text-xs font-bold text-red-600">
                            <i class="bi bi-x-circle"></i> Certificate voided
                        </span>
                        <p class="text-[11px] text-slate-400">Downloading is disabled for voided certificates.</p>
                    </template>
                </div>
            </div>
        </div>

        <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-14 text-center">
            <i class="bi bi-award text-4xl text-slate-300"></i>
            <p class="mt-3 text-sm font-semibold text-slate-600">No certificates yet</p>
            <p class="mx-auto mt-1 max-w-sm text-xs text-slate-400">Finish a training program and your certificate will appear here once admin issues it — ready to download and print.</p>
            <Link href="/portal/dashboard" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#0D1527]/90">
                Go to dashboard <i class="bi bi-arrow-right"></i>
            </Link>
        </div>
    </div>
</template>