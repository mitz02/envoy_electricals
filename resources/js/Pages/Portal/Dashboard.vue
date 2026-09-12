<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PortalLayout from '@/Layouts/PortalLayout.vue';

defineOptions({ layout: PortalLayout });

const props = defineProps({
    trainee: { type: Object, default: null },
    enrollments: { type: Array, default: () => [] },
    certificates: { type: Array, default: () => [] },
    certificateCount: { type: Number, default: 0 },
    available: { type: Array, default: () => [] },
    paystackConfigured: { type: Boolean, default: false },
    bankDetails: { type: Object, default: () => ({}) },
    whatsappNumber: { type: String, default: '' },
});

const journey = [
    { label: 'Programs', value: props.available.length + props.enrollments.length, icon: 'bi-mortarboard-fill' },
    { label: 'In progress', value: props.enrollments.length, icon: 'bi-hourglass-split' },
    { label: 'Completed', value: props.enrollments.filter((e) => e.status === 'completed').length, icon: 'bi-check2-circle' },
    { label: 'Certificates', value: props.certificateCount, icon: 'bi-award-fill' },
];

const statusMeta = {
    pending: { label: 'Enrolled · pending', cls: 'bg-amber-100 text-amber-700' },
    active: { label: 'In progress', cls: 'bg-[#40e0d0]/15 text-teal-700' },
    completed: { label: 'Completed', cls: 'bg-emerald-100 text-emerald-700' },
    cancelled: { label: 'Cancelled', cls: 'bg-red-100 text-red-600' },
};

const typeStyles = { beginner: 'bg-emerald-100 text-emerald-800', intermediate: 'bg-amber-100 text-amber-800', advanced: 'bg-red-100 text-red-700' };

const fmtDate = (d) => new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

const prettyName = (t, fallback = 'there') => {
    let n = (t?.name || '').trim();
    if (!n) n = fallback;
    if (n.includes('@')) {
        n = n.split('@')[0].replace(/[._-]+/g, ' ').trim();
        if (!n) n = fallback;
    }
    return n;
};

const firstName = (t) => prettyName(t).split(/\s+/)[0];

const gradStyle = 'bg-gradient-to-r from-[#D97706] via-[#8B4A12] to-[#0D1527] bg-clip-text text-transparent';

const seatsLeft = (p) => {
    if (p.capacity) {
        const left = p.capacity - p.enrolled_count;
        return left > 0 ? left : 0;
    }
    return null;
};

const formatPrice = (price) => {
    if (!price || price === 0) return 'Free';
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 0 }).format(price);
};

const bank = computed(() => props.bankDetails || {});

const waNumber = props.whatsappNumber || '2348097089259';

const waLink = computed(() => {
    const lines = [
        `Hello Envoy Academy! I just enrolled in "${enrollTarget.value?.title || ''}".`,
        `Amount: ${formatPrice(enrollTarget.value?.price)}`,
        `Name: ${usePage().props.auth?.user?.name || ''}`,
        'Please find my proof of payment below.',
    ];
    return `https://wa.me/${waNumber}?text=${encodeURIComponent(lines.filter(Boolean).join('\n'))}`;
});

const enrollOpen = ref(false);
const enrollTarget = ref(null);
const enrollBusy = ref(false);
const enrollError = ref('');
const chosenPay = ref('');

const paidProgram = computed(() => enrollTarget.value && enrollTarget.value.price > 0);

function openEnroll(p) {
    enrollTarget.value = p;
    chosenPay.value = '';
    enrollError.value = '';
    enrollOpen.value = true;
}

function closeEnroll() {
    enrollOpen.value = false;
    enrollTarget.value = null;
    chosenPay.value = '';
    enrollError.value = '';
    enrollBusy.value = false;
}

function choose(mode) {
    chosenPay.value = mode;
    enrollError.value = '';
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function payOnline() {
    enrollBusy.value = true;
    enrollError.value = '';
    try {
        const response = await fetch(`/training/${enrollTarget.value.id}/paystack`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Failed to initialize payment');
        }
        if (data.authorization_url) {
            window.location.href = data.authorization_url;
            return;
        }
        throw new Error('No payment URL received');
    } catch (error) {
        enrollError.value = error.message;
        enrollBusy.value = false;
    }
}

function enrollOffline() {
    enrollBusy.value = true;
    router.post(route('portal.enroll', enrollTarget.value.id), { mode: 'offline' }, { preserveScroll: true });
}

function enrollFree() {
    enrollBusy.value = true;
    router.post(route('portal.enroll', enrollTarget.value.id), {}, { preserveScroll: true });
}

function confirmEnroll() {
    if (!chosenPay.value) return;
    if (chosenPay.value === 'online') payOnline();
    else if (chosenPay.value === 'offline') enrollOffline();
    else enrollFree();
}

function enroll(p) {
    openEnroll(p);
}
</script>

<template>
    <div class="space-y-8 sm:space-y-10">
        <!-- ===== Welcome : split card echoing /login ===== -->
        <section class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_24px_60px_rgba(13,21,39,0.10)]">
            <div class="grid lg:grid-cols-5">
                <!-- Left : greeting -->
                <div class="relative bg-[#FAF8F2] p-7 sm:p-9 lg:col-span-3">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-yellow-300 to-amber-500 text-lg font-black text-[#0D1527] shadow-[0_10px_24px_-8px_rgba(245,158,11,0.7)]">
                            {{ (trainee?.name || 'T').trim().split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase() }}
                        </div>
                        <div>
                            <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                                <span class="h-px w-6 bg-[#40e0d0]"></span> Welcome back
                            </p>
                            <h1 class="mt-1.5 text-3xl font-black tracking-tight text-[#0D1527] sm:text-4xl">
                                Hello, <span :class="gradStyle">{{ firstName(trainee) }}.</span>
                            </h1>
                        </div>
                    </div>

                    <p class="mt-5 max-w-md text-sm leading-relaxed text-slate-600">
                        The sun is up and so are you — keep your skills charged. Pick up where you left off below.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        <span v-if="trainee?.ref_id" class="inline-flex items-center gap-1.5 rounded-full border border-[#40e0d0]/30 bg-[#40e0d0]/5 px-3 py-1 text-xs font-semibold text-teal-700">
                            <i class="bi bi-person-vcard"></i> {{ trainee.ref_id }}
                        </span>
                        <span v-if="trainee?.type" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold capitalize text-slate-600">
                            <i class="bi bi-briefcase"></i> {{ trainee.type }}
                        </span>
                    </div>
                </div>

                <!-- Right : navy journey panel -->
                <div class="relative overflow-hidden bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] p-7 sm:p-9 lg:col-span-2">
                    <div class="absolute inset-0" aria-hidden="true">
                        <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px); background-size: 26px 26px;"></div>
                        <div class="absolute -top-16 -right-14 h-52 w-52 rounded-full bg-yellow-400/10 blur-[70px]"></div>
                        <div class="absolute -bottom-14 -left-14 h-52 w-52 rounded-full bg-[#40e0d0]/10 blur-[70px]"></div>
                    </div>

                    <div class="relative z-10 flex items-center gap-2.5">
                        <span class="h-px w-7 bg-gradient-to-r from-transparent via-[#FACC15]/60 to-[#FACC15]"></span>
                        <p class="text-xs font-bold uppercase tracking-[0.25em] text-[#FDE047]">Your journey</p>
                    </div>

                    <div class="relative z-10 mt-6 grid grid-cols-2 gap-3.5">
                        <div v-for="j in journey" :key="j.label" class="rounded-2xl border border-white/10 bg-white/[0.06] p-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#40e0d0]/15 text-[#40e0d0]">
                                <i :class="`bi ${j.icon} text-sm`"></i>
                            </div>
                            <p class="mt-3 text-2xl font-black text-white">{{ j.value }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ j.label }}</p>
                        </div>
                    </div>

                    <div class="relative z-10 mt-5">
                        <Link href="/portal/certificates" class="inline-flex items-center justify-center gap-2.5 rounded-full bg-yellow-400 px-5 py-3 text-sm font-black text-[#0D1527] shadow-[0_0_0_0_rgba(253,224,71,0.6)] transition-all hover:bg-yellow-300 hover:shadow-[0_0_20px_rgba(253,224,71,0.45)]">
                            View certificates
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#0D1527] text-yellow-400">
                                <i class="bi bi-arrow-right text-sm"></i>
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== My training ===== -->
        <section>
            <div class="mb-5 flex items-end justify-between">
                <div>
                    <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                        <span class="h-px w-6 bg-[#40e0d0]"></span> Enrolled programs
                    </p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-[#0D1527]">My training</h2>
                </div>
                <a href="/training" class="hidden items-center gap-2 text-sm font-bold text-[#0D1527] transition-colors hover:text-[#40e0d0] sm:inline-flex">
                    Explore programs <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>

            <div v-if="enrollments.length" class="grid gap-4 sm:grid-cols-2">
                <div v-for="e in enrollments" :key="e.id" class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_12px_34px_rgba(13,21,39,0.07)]">
                    <div class="flex items-center justify-between gap-3 border-l-4 px-5 py-4"
                        :class="{
                            'border-[#40e0d0]': e.status === 'active',
                            'border-amber-400': e.status === 'pending',
                            'border-emerald-500': e.status === 'completed',
                            'border-red-400': e.status === 'cancelled',
                        }"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-[#0D1527]">{{ e.training.title }}</p>
                            <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                {{ e.training.duration_weeks }} {{ e.training.duration_weeks === 1 ? 'week' : 'weeks' }} · <span class="capitalize">{{ e.training.level }}</span>
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide" :class="statusMeta[e.status]?.cls">
                            {{ statusMeta[e.status]?.label }}
                        </span>
                    </div>

                    <div class="px-5 pb-5">
                        <div class="mt-3.5 flex items-center justify-between text-[11px] font-semibold text-slate-500">
                            <span>Progress</span>
                            <span>{{ e.progress }}%{{ e.status === 'completed' ? ' · done' : '' }}</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#40e0d0] to-[#FACC15] transition-all duration-700" :style="{ width: e.progress + '%' }"></div>
                        </div>

                        <div
                            v-if="e.status === 'active'"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#40e0d0]/10 px-4 py-3 text-sm font-bold text-teal-700"
                        >
                            <i class="bi bi-rocket-takeoff"></i> You're on track — keep it up
                        </div>
                        <div
                            v-if="e.status === 'pending'"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full border-[1.5px] border-slate-200 px-4 py-3 text-sm font-bold text-slate-600"
                        >
                            Enrollment submitted — awaiting confirmation
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#40e0d0]/10 text-[#40e0d0]">
                    <i class="bi bi-mortarboard text-2xl"></i>
                </div>
                <p class="mt-4 text-base font-black text-[#0D1527]">No programs yet</p>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Pick a training below and start your journey toward certified electrician skills.</p>
                <a href="/training" class="mt-6 inline-flex items-center justify-center gap-2.5 rounded-full bg-[#0D1527] px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-slate-800">
                    Explore programs
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-400 text-[#0D1527]"><i class="bi bi-arrow-right text-xs font-black"></i></span>
                </a>
            </div>
        </section>

        <!-- ===== Certificates ===== -->
        <section v-if="certificates.length">
            <div class="mb-5 flex items-end justify-between">
                <div>
                    <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                        <span class="h-px w-6 bg-[#40e0d0]"></span> Earned
                    </p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-[#0D1527]">Certificates</h2>
                </div>
                <Link href="/portal/certificates" class="hidden items-center gap-2 text-sm font-bold text-[#0D1527] transition-colors hover:text-[#40e0d0] sm:inline-flex">
                    View all <i class="bi bi-arrow-right"></i>
                </Link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div v-for="c in certificates.slice(0, 2)" :key="c.id" class="flex items-center gap-4 rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.07)]">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-yellow-300 to-amber-500 text-[#0D1527] shadow-[0_8px_20px_-6px_rgba(245,158,11,0.6)]">
                        <i class="bi bi-patch-check-fill text-xl"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wide text-[#40e0d0]">{{ c.training.title }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">Issued <span v-if="c.issued_at">{{ fmtDate(c.issued_at) }}</span><span v-else>recently</span> · <span class="font-mono font-semibold">{{ c.certificate_no }}</span></p>
                    </div>
                    <Link :href="route('portal.certificates.show', c.id)" class="shrink-0 rounded-full bg-[#0D1527] p-3 text-white transition-all hover:bg-slate-800 hover:shadow-[0_0_18px_rgba(13,21,39,0.35)]" title="View certificate">
                        <i class="bi bi-arrow-right text-sm"></i>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ===== Open programs ===== -->
        <section>
            <div class="mb-5 flex items-end justify-between">
                <div>
                    <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                        <span class="h-px w-6 bg-[#40e0d0]"></span> Grow further
                    </p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-[#0D1527]">Open programs</h2>
                </div>
                <a href="/training" class="hidden items-center gap-2 text-sm font-bold text-[#0D1527] transition-colors hover:text-[#40e0d0] sm:inline-flex">
                    See all programs <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>

            <div v-if="available.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="p in available" :key="p.id" class="group flex flex-col overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_12px_34px_rgba(13,21,39,0.07)]">
                    <div class="relative flex h-24 items-start justify-between gap-3 bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] p-5">
                        <div class="absolute inset-0" aria-hidden="true">
                            <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px); background-size: 22px 22px;"></div>
                            <div class="absolute -top-10 -right-8 h-28 w-28 rounded-full bg-yellow-400/10 blur-[40px]"></div>
                        </div>
                        <div class="relative z-10 min-w-0">
                            <p class="truncate text-base font-black text-white">{{ p.title }}</p>
                            <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ p.level }}</p>
                            <p v-if="p.start_date" class="mt-1.5 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-semibold text-slate-300">
                                <i class="bi bi-calendar-event text-yellow-400"></i>
                                Starts {{ new Date(p.start_date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}
                            </p>
                        </div>
                        <div v-if="p.image_path" class="relative z-10 h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-white/20">
                            <img :src="p.image_path" :alt="p.title" class="h-full w-full object-cover" />
                        </div>
                        <span v-else class="relative z-10 shrink-0 rounded-xl bg-white/10 p-2.5 text-[#FDE047]"><i class="bi bi-lightning-charge-fill"></i></span>
                    </div>

                    <div class="flex flex-1 flex-col px-5 pb-5 pt-4">
                        <p class="line-clamp-3 text-[13px] leading-relaxed text-slate-500">{{ p.description }}</p>
                        <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-semibold text-slate-600">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1"><i class="bi bi-clock"></i> {{ p.duration_weeks }} {{ p.duration_weeks === 1 ? 'week' : 'weeks' }}</span>
                            <span v-if="seatsLeft(p) !== null" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1">
                                <i class="bi bi-people"></i> {{ seatsLeft(p) }} {{ seatsLeft(p) === 1 ? 'seat' : 'seats' }} left
                            </span>
                        </div>
                        <button
                            @click="enroll(p)"
                            class="mt-5 inline-flex w-full items-center justify-center gap-2.5 rounded-full bg-[#0D1527] px-4 py-3 text-sm font-bold text-white transition-all hover:bg-slate-800 hover:shadow-[0_10px_24px_-8px_rgba(13,21,39,0.5)]"
                        >
                            Enroll now
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-400 text-[#0D1527] transition-transform group-hover:translate-x-0.5"><i class="bi bi-arrow-right text-xs font-black"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <p class="text-base font-black text-[#0D1527]">Nothing open right now</p>
                <p class="mt-1 text-sm text-slate-500">Check back soon — new cohorts open regularly.</p>
            </div>
        </section>

        <!-- ===== Enroll modal ===== -->
        <div
            v-if="enrollOpen"
            class="fixed inset-0 z-[80] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 bg-[#0D1527]/70 backdrop-blur-sm" @click="closeEnroll"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-[0_32px_80px_rgba(13,21,39,0.45)]">
                <!-- header -->
                <div class="relative overflow-hidden bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] px-6 py-6">
                    <div class="absolute inset-0" aria-hidden="true">
                        <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px); background-size: 22px 22px;"></div>
                        <div class="absolute -top-10 -right-8 h-28 w-28 rounded-full bg-yellow-400/10 blur-[40px]"></div>
                    </div>
                    <div class="relative z-10 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                                <span class="h-px w-6 bg-[#40e0d0]"></span> Enroll now
                            </p>
                            <h3 class="mt-1.5 text-xl font-black tracking-tight text-white">{{ enrollTarget?.title }}</h3>
                        </div>
                        <button @click="closeEnroll" class="shrink-0 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/25" title="Close">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>
                    <div class="relative z-10 mt-3 inline-flex items-center gap-2 rounded-full bg-yellow-400 px-4 py-1.5 text-sm font-black text-[#0D1527]">
                        <i class="bi bi-cash-coin"></i> {{ formatPrice(enrollTarget?.price) }}
                    </div>
                </div>

                <!-- body -->
                <div class="px-6 py-6">
                    <template v-if="paidProgram">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">How would you like to pay?</p>

                        <div class="mt-3 grid gap-3">
                            <button
                                @click="choose('online')"
                                :disabled="enrollBusy || !paystackConfigured"
                                class="flex items-center gap-4 rounded-2xl border-[1.5px] px-4 py-3.5 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="chosenPay === 'online' ? 'border-[#40e0d0] bg-[#40e0d0]/10 shadow-[0_0_0_3px_rgba(64,224,208,0.18)]' : 'border-slate-200 bg-white hover:border-[#40e0d0]/50'"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#40e0d0]/15 text-[#40e0d0]">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-black text-[#0D1527]">Pay online now</span>
                                    <span class="block text-xs text-slate-500">Secure card / bank payment via Paystack — instant confirmation.</span>
                                </span>
                                <span class="ml-auto flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2" :class="chosenPay === 'online' ? 'border-[#40e0d0] bg-[#40e0d0] text-[#0D1527]' : 'border-slate-300'">
                                    <i v-if="chosenPay === 'online'" class="bi bi-check text-xs font-black"></i>
                                </span>
                            </button>

                            <button
                                @click="choose('offline')"
                                :disabled="enrollBusy"
                                class="flex items-center gap-4 rounded-2xl border-[1.5px] px-4 py-3.5 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="chosenPay === 'offline' ? 'border-amber-400 bg-amber-50 shadow-[0_0_0_3px_rgba(251,191,36,0.18)]' : 'border-slate-200 bg-white hover:border-amber-300'"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                    <i class="bi bi-bank"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-black text-[#0D1527]">Enroll & pay offline</span>
                                    <span class="block text-xs text-slate-500">Bank transfer — your seat is reserved and we confirm once payment clears.</span>
                                </span>
                                <span class="ml-auto flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2" :class="chosenPay === 'offline' ? 'border-amber-400 bg-amber-400 text-[#0D1527]' : 'border-slate-300'">
                                    <i v-if="chosenPay === 'offline'" class="bi bi-check text-xs font-black"></i>
                                </span>
                            </button>
                        </div>

                        <p v-if="!paystackConfigured" class="mt-3 flex items-center gap-1.5 text-xs font-semibold text-amber-700">
                            <i class="bi bi-info-circle"></i> Online payment isn't available right now — please choose offline.
                        </p>

                        <div v-if="chosenPay === 'offline'" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-amber-700">
                                <i class="bi bi-bank2"></i> Bank transfer details
                            </p>
                            <dl class="mt-2 space-y-1 text-sm">
                                <div>
                                    <dt class="inline text-slate-500">Account name: </dt>
                                    <dd class="inline font-bold text-[#0D1527]">{{ bank.account_name || 'Envoy Electricals' }}</dd>
                                </div>
                                <div>
                                    <dt class="inline text-slate-500">Account number: </dt>
                                    <dd class="inline font-mono font-black text-[#0D1527]">{{ bank.account_number || '5168265608' }}</dd>
                                </div>
                                <div>
                                    <dt class="inline text-slate-500">Bank: </dt>
                                    <dd class="inline font-bold text-[#0D1527]">{{ bank.bank_name || 'Moniepoint MFB' }}</dd>
                                </div>
                            </dl>
                            <p v-if="bank.instructions" class="mt-2 text-xs leading-relaxed text-slate-600">{{ bank.instructions }}</p>

                            <a
                                :href="waLink"
                                target="_blank"
                                rel="noopener"
                                class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 transition-all hover:bg-emerald-100 hover:shadow-[0_6px_20px_rgba(16,185,129,0.25)]"
                            >
                                <i class="bi bi-whatsapp text-lg leading-none"></i>
                                Send proof of payment via WhatsApp
                            </a>
                        </div>

                        <p v-if="enrollError" class="mt-4 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600">{{ enrollError }}</p>

                        <div class="mt-5 flex items-center gap-3">
                            <button
                                @click="confirmEnroll"
                                :disabled="enrollBusy || !chosenPay"
                                class="inline-flex flex-1 items-center justify-center gap-2.5 rounded-full bg-[#0D1527] px-5 py-3.5 text-sm font-bold text-white transition-all hover:bg-slate-800 hover:shadow-[0_10px_24px_-8px_rgba(13,21,39,0.5)] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <i v-if="enrollBusy" class="bi bi-arrow-repeat animate-spin"></i>
                                <span v-else-if="chosenPay === 'online'" class="flex items-center gap-2">
                                    Continue to Paystack
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-400 text-[#0D1527]"><i class="bi bi-arrow-right text-xs font-black"></i></span>
                                </span>
                                <span v-else-if="chosenPay === 'offline'">Enroll & pay offline</span>
                                <span v-else>Choose a payment option</span>
                            </button>
                            <button @click="closeEnroll" class="rounded-full px-4 py-3.5 text-sm font-bold text-slate-500 transition hover:text-[#0D1527]">Cancel</button>
                        </div>
                    </template>

                    <template v-else>
                        <p class="text-sm leading-relaxed text-slate-600">
                            This program is free. Enroll right away and start your training journey.
                        </p>

                        <p v-if="enrollError" class="mt-4 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600">{{ enrollError }}</p>

                        <div class="mt-5 flex items-center gap-3">
                            <button
                                @click="enrollFree"
                                :disabled="enrollBusy"
                                class="inline-flex flex-1 items-center justify-center gap-2.5 rounded-full bg-[#0D1527] px-5 py-3.5 text-sm font-bold text-white transition-all hover:bg-slate-800 hover:shadow-[0_10px_24px_-8px_rgba(13,21,39,0.5)] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <i v-if="enrollBusy" class="bi bi-arrow-repeat animate-spin"></i>
                                <template v-else>
                                    Enroll free
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-400 text-[#0D1527]"><i class="bi bi-arrow-right text-xs font-black"></i></span>
                                </template>
                            </button>
                            <button @click="closeEnroll" class="rounded-full px-4 py-3.5 text-sm font-bold text-slate-500 transition hover:text-[#0D1527]">Cancel</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>