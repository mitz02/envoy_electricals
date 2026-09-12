<script setup>
import { usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    training: { type: Object, required: true },
    paystackConfigured: { type: Boolean, default: false },
    bankDetails: { type: Object, default: () => ({}) },
    whatsappNumber: { type: String, default: '' },
});

const hasUser = !!usePage().props.auth?.user;
const isProcessing = ref(false);
const paymentError = ref(null);

const bank = computed(() => props.bankDetails || {});

const waNumber = props.whatsappNumber || '2348097089259';

const waLink = computed(() => {
    const lines = [
        `Hello Envoy Academy! I just enrolled in "${props.training.title}".`,
        `Amount: ${formatPrice(props.training.price)}`,
        `Name: ${usePage().props.auth?.user?.name || ''}`,
        'Please find my proof of payment below.',
    ];
    return `https://wa.me/${waNumber}?text=${encodeURIComponent(lines.filter(Boolean).join('\n'))}`;
});

const paymentOpen = ref(false);
const chosenPay = ref('');
const enrollBusy = ref(false);
const enrollError = ref('');

const paidProgram = computed(() => props.training.price && props.training.price > 0);

function openPayment() {
    chosenPay.value = '';
    enrollError.value = '';
    paymentOpen.value = true;
}

function closePayment() {
    paymentOpen.value = false;
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
    if (!training.capacity) return { text: 'Unlimited seats available', class: 'text-emerald-600', icon: 'bi-people' };
    const remaining = training.capacity - training.enrolled_count;
    if (remaining <= 0) return { text: 'Program is full', class: 'text-red-600', icon: 'bi-x-circle' };
    if (remaining <= 5) return { text: `Only ${remaining} seats remaining`, class: 'text-amber-600', icon: 'bi-exclamation-triangle' };
    return { text: `${remaining} of ${training.capacity} seats available`, class: 'text-emerald-600', icon: 'bi-people' };
};

const curriculumItems = (curriculum) => {
    if (!curriculum) return [];
    return curriculum.split('\n').filter(item => item.trim()).map(item => item.trim());
};

const parseJson = async (res) => {
    try {
        return await res.json();
    } catch {
        return null;
    }
};

const unknownFetchError = (res) => {
    if (res.status === 419) return 'Session expired — please refresh the page and try again.';
    if (res.status === 401) return 'Please log in to continue.';
    return null;
};

const handleEnroll = async () => {
    if (!hasUser) {
        router.visit('/portal/register');
        return;
    }

    if (paidProgram.value) {
        openPayment();
    } else {
        await enrollDirectly();
    }
};

const enrollOffline = async () => {
    enrollBusy.value = true;
    enrollError.value = '';

    try {
        const response = await fetch(`/portal/enroll/${props.training.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ mode: 'offline' }),
        });

        const data = await parseJson(response);

        if (!response.ok) {
            throw new Error(unknownFetchError(response) || data?.message || 'Offline enrollment failed');
        }

        if (response.url.includes('/portal/')) {
            window.location.href = '/portal/dashboard';
            return;
        }

        window.location.href = '/portal/dashboard';
    } catch (error) {
        enrollError.value = error.message;
        enrollBusy.value = false;
    }
};

const confirmPayment = () => {
    if (!chosenPay.value) return;
    if (chosenPay.value === 'online') {
        paymentOpen.value = false;
        initiatePaystackPayment();
    } else if (chosenPay.value === 'offline') {
        enrollOffline();
    }
};

const initiatePaystackPayment = async () => {
    isProcessing.value = true;
    paymentError.value = null;

    try {
        const response = await fetch(`/training/${props.training.id}/paystack`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        const data = await parseJson(response);

        if (!response.ok) {
            throw new Error(unknownFetchError(response) || data?.message || 'Failed to initialize payment');
        }

        if (data?.authorization_url) {
            window.location.href = data.authorization_url;
            return;
        }

        throw new Error('No payment URL received');
    } catch (error) {
        paymentError.value = error.message;
        enrollError.value = error.message;
        isProcessing.value = false;
        paymentOpen.value = true;
    }
};

const enrollDirectly = async () => {
    isProcessing.value = true;
    paymentError.value = null;

    try {
        const response = await fetch(`/portal/enroll/${props.training.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        const data = await parseJson(response);

        if (!response.ok) {
            throw new Error(unknownFetchError(response) || data?.message || 'Enrollment failed');
        }

        router.visit('/portal/dashboard');
    } catch (error) {
        paymentError.value = error.message;
        isProcessing.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#FAF8F2] font-sans text-slate-800 antialiased">
        <!-- Program Header -->
        <section class="relative bg-gradient-to-b from-slate-50 to-white">
            <div class="absolute inset-0" aria-hidden="true">
                <div class="absolute -top-20 right-20 h-64 w-64 rounded-full bg-yellow-400/10 blur-[100px]"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-3">
                    <!-- Main Info -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-semibold" :class="levelStyles[training.level] || 'bg-slate-100 text-slate-600 border-slate-200'">
                                <i :class="['bi', levelIcons[training.level] || 'bi-mortarboard']"></i>
                                {{ training.level.charAt(0).toUpperCase() + training.level.slice(1) }}
                            </span>
                            <span class="text-xs font-medium text-slate-500">{{ training.ref_id }}</span>
                            <span v-if="training.start_date" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                <i class="bi bi-calendar-event text-[#40e0d0]"></i>
                                Starts {{ new Date(training.start_date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                            </span>
                            <span v-if="training.is_featured" class="inline-flex items-center gap-1.5 rounded-full border border-yellow-300 bg-yellow-50 px-3 py-1 text-xs font-semibold text-yellow-700">
                                <i class="bi bi-star-fill"></i> Featured
                            </span>
                        </div>

                        <img
                            v-if="training.image_path"
                            :src="training.image_path"
                            :alt="training.title"
                            class="mb-6 aspect-[21/9] w-full rounded-2xl border border-slate-200 object-cover shadow-sm"
                        />

                        <h1 class="text-3xl font-black text-slate-950 sm:text-4xl mb-4">{{ training.title }}</h1>

                        <p class="text-lg text-slate-500 leading-relaxed mb-6">{{ training.description || 'Comprehensive hands-on training program at Envoy Academy.' }}</p>

                        <!-- Objectives & Outcomes -->
                        <div v-if="training.objectives || training.learning_outcomes" class="mb-8 grid gap-5 sm:grid-cols-2">
                            <div v-if="training.objectives" class="rounded-2xl border border-slate-100 bg-white p-5">
                                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#40e0d0]">
                                    <i class="bi bi-bullseye"></i> Program objectives
                                </h3>
                                <ul class="mt-3 space-y-2">
                                    <li v-for="(o, i) in training.objectives.split('\n').filter(Boolean)" :key="i" class="flex items-start gap-2 text-sm text-slate-600">
                                        <i class="bi bi-check2-circle mt-0.5 text-emerald-500"></i><span>{{ o }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div v-if="training.learning_outcomes" class="rounded-2xl border border-slate-100 bg-white p-5">
                                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-[#40e0d0]">
                                    <i class="bi bi-flag"></i> What you'll achieve
                                </h3>
                                <ul class="mt-3 space-y-2">
                                    <li v-for="(o, i) in training.learning_outcomes.split('\n').filter(Boolean)" :key="i" class="flex items-start gap-2 text-sm text-slate-600">
                                        <i class="bi bi-flag mt-0.5 text-yellow-500"></i><span>{{ o }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Meta Grid -->
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="rounded-xl bg-white p-4 border border-slate-100">
                                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 mb-2">
                                    <i class="bi bi-calendar3 text-emerald-600"></i>
                                </div>
                                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Duration</p>
                                <p class="text-lg font-bold text-slate-900">{{ training.duration_weeks }} {{ training.duration_weeks === 1 ? 'week' : 'weeks' }}</p>
                            </div>

                            <div class="rounded-xl bg-white p-4 border border-slate-100">
                                <div class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1.5 mb-2">
                                    <i class="bi bi-people text-blue-600"></i>
                                </div>
                                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Capacity</p>
                                <p class="text-lg font-bold text-slate-900">{{ training.capacity ? training.capacity : 'Unlimited' }}</p>
                            </div>

                            <div class="rounded-xl bg-white p-4 border border-slate-100">
                                <div class="inline-flex items-center gap-2 rounded-full bg-purple-100 px-3 py-1.5 mb-2">
                                    <i class="bi bi-person-check text-purple-600"></i>
                                </div>
                                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Enrolled</p>
                                <p class="text-lg font-bold text-slate-900">{{ training.enrolled_count }}</p>
                            </div>

                            <div class="rounded-xl bg-white p-4 border border-slate-100">
                                <div class="inline-flex items-center gap-2 rounded-full bg-yellow-100 px-3 py-1.5 mb-2">
                                    <i class="bi bi-currency-exchange text-yellow-600"></i>
                                </div>
                                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Price</p>
                                <p class="text-lg font-bold text-slate-900">{{ formatPrice(training.price) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Card -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-slate-900">Program Fee</h3>
                            </div>

                            <div class="text-3xl font-black text-slate-900 mb-2">{{ formatPrice(training.price) }}</div>
                            <p class="text-sm text-slate-500 mb-6">One-time payment. Includes materials, certification, and workshop access.</p>

                            <!-- Capacity Status -->
                            <div class="mb-6 p-4 rounded-xl" :class="[getCapacityStatus(training).class.replace('text-', 'bg-').replace('600', '50') + ' border', 'border-current/20']">
                                <div class="flex items-center gap-2">
                                    <i :class="['bi', getCapacityStatus(training).icon, 'text-lg', getCapacityStatus(training).class]"></i>
                                    <span class="text-sm font-medium" :class="getCapacityStatus(training).class">{{ getCapacityStatus(training).text }}</span>
                                </div>
                            </div>

                            <!-- Paystack Badge -->
                            <div v-if="paystackConfigured" class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-100">
                                <div class="flex items-center gap-2 text-sm text-emerald-800">
                                    <i class="bi bi-shield-check text-lg"></i>
                                    <span>Secure payment via Paystack</span>
                                </div>
                            </div>

                            <!-- Enroll Button -->
                            <button
                                @click="handleEnroll"
                                :disabled="isProcessing || (training.capacity && training.enrolled_count >= training.capacity)"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#0D1527] px-6 py-4 text-base font-bold text-white hover:bg-[#0D1527]/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-[#0D1527]/30"
                            >
                                <i v-if="isProcessing" class="bi bi-arrow-clockwise animate-spin"></i>
                                <i v-else-if="training.price && training.price > 0" class="bi bi-credit-card"></i>
                                <i v-else class="bi bi-check-circle"></i>
                                <span v-if="isProcessing">Processing...</span>
                                <span v-else-if="training.price && training.price > 0">Pay & Enroll Now</span>
                                <span v-else>Enroll for Free</span>
                            </button>

                            <p v-if="paymentError" class="mt-4 text-center text-sm text-red-600 bg-red-50 p-3 rounded-lg">{{ paymentError }}</p>

                            <p v-if="!hasUser && !isProcessing" class="mt-4 text-center text-sm text-slate-500">
                                You'll be redirected to register/login first, then complete enrollment.
                            </p>

                            <div v-if="training.capacity && training.enrolled_count >= training.capacity" class="mt-4 text-center">
                                <p class="text-sm text-red-600">This program has reached capacity.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Curriculum Section -->
        <section v-if="training.weeks.length || training.curriculum" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center mb-12">
                <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                    <span class="h-px w-8 bg-[#40e0d0]"></span> Curriculum
                </span>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">What You'll Learn</h2>
                <p class="mt-3 text-lg text-slate-500">A structured curriculum designed by industry experts</p>
            </div>

            <!-- Prerequisites -->
            <div v-if="training.prerequisites" class="mx-auto mb-10 max-w-3xl">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-amber-700">
                        <i class="bi bi-shield-check"></i> Who should join / prerequisites
                    </h3>
                    <ul class="mt-3 space-y-2">
                        <li v-for="(p, i) in training.prerequisites.split('\n').filter(Boolean)" :key="i" class="flex items-start gap-2 text-sm text-slate-700">
                            <i class="bi bi-check-circle-fill mt-0.5 text-amber-500"></i><span>{{ p }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Structured weeks -->
            <div v-if="training.weeks.length" class="mx-auto max-w-3xl space-y-4">
                <div v-for="week in training.weeks" :key="week.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white transition-all hover:border-yellow-300 hover:shadow-md">
                    <div class="flex items-center gap-4 border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0D1527] text-sm font-black text-yellow-400">
                            {{ week.week_number }}
                        </span>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-slate-900">{{ week.title }}</h3>
                            <p v-if="week.summary" class="mt-0.5 text-sm text-slate-500">{{ week.summary }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">{{ week.lessons.length }} lessons</span>
                    </div>
                    <ul v-if="week.lessons.length" class="divide-y divide-slate-100">
                        <li v-for="lesson in week.lessons" :key="lesson.id" class="group flex items-center gap-3 px-5 py-3.5">
                            <i class="bi bi-play-circle-fill text-lg text-[#40e0d0]"></i>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">{{ lesson.title }}</p>
                                <p v-if="lesson.description" class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ lesson.description }}</p>
                            </div>
                            <span v-if="lesson.duration_minutes" class="shrink-0 text-xs font-semibold text-slate-400">
                                <i class="bi bi-clock mr-1"></i>{{ lesson.duration_minutes }} min
                            </span>
                        </li>
                    </ul>
                    <p v-else class="px-5 py-3 text-xs text-slate-400">Lessons coming soon.</p>
                </div>
            </div>

            <!-- Legacy plain-text curriculum -->
            <div v-else class="max-w-3xl mx-auto space-y-4">
                <div
                    v-for="(item, index) in curriculumItems(training.curriculum)"
                    :key="index"
                    class="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 hover:border-yellow-300 hover:shadow-md transition-all"
                >
                    <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-600 group-hover:bg-yellow-400 group-hover:text-white transition-all">
                        <span class="text-lg font-bold">{{ index + 1 }}</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-slate-700 leading-relaxed">{{ item }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features/Benefits -->
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 bg-white border-y border-slate-200">
            <div class="max-w-3xl mx-auto text-center mb-12">
                <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                    <span class="h-px w-8 bg-[#40e0d0]"></span> Why Choose This Program
                </span>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Benefits & Features</h2>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="(feature, index) in [
                    { icon: 'bi-tools', title: 'Hands-on Training', desc: 'Real workshop experience with actual equipment' },
                    { icon: 'bi-patch-check', title: 'Certified Curriculum', desc: 'Industry-recognized certification upon completion' },
                    { icon: 'bi-people', title: 'Expert Instructors', desc: 'Learn from working professionals with years of field experience' },
                    { icon: 'bi-briefcase', title: 'Career Support', desc: 'Job placement assistance and industry connections' }
                ]" :key="index" class="text-center p-6 rounded-2xl border border-slate-100 hover:border-yellow-200 hover:shadow-lg transition-all">
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-yellow-100 text-yellow-600 mb-4">
                        <i :class="['bi', feature.icon, 'text-2xl']"></i>
                    </div>
                    <h3 class="font-semibold text-slate-900 mb-1">{{ feature.title }}</h3>
                    <p class="text-sm text-slate-500">{{ feature.desc }}</p>
                </div>
            </div>
        </section>

        <!-- Instructor Info -->
        <section v-if="training.creator" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 h-16 w-16 rounded-2xl bg-gradient-to-br from-yellow-400 to-[#40e0d0] flex items-center justify-center">
                            <i class="bi bi-person-badge text-3xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Lead Instructor</h3>
                            <p class="text-sm text-slate-500 mt-1">{{ training.creator.name }}</p>
                            <p class="text-sm text-slate-400 mt-2">Experienced industry professional with extensive field and training experience.</p>
                        </div>
                    </div>
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

                <div class="relative max-w-2xl mx-auto">
                    <h2 class="text-3xl font-black text-white sm:text-4xl">
                        Don't Miss Out — Limited Seats Available
                    </h2>
                    <p class="mt-4 text-lg text-slate-300">
                        Secure your spot in the next cohort. Classes fill up quickly.
                    </p>
                    <button
                        @click="handleEnroll"
                        :disabled="isProcessing || (training.capacity && training.enrolled_count >= training.capacity)"
                        class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-yellow-400 px-8 py-4 text-base font-bold text-[#0D1527] hover:bg-yellow-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-yellow-400/30"
                    >
                        <i v-if="isProcessing" class="bi bi-arrow-clockwise animate-spin"></i>
                        <i v-else-if="training.price && training.price > 0" class="bi bi-credit-card"></i>
                        <i v-else class="bi bi-check-circle"></i>
                        <span v-if="isProcessing">Processing...</span>
                        <span v-else-if="training.price && training.price > 0">Pay & Enroll Now</span>
                        <span v-else>Enroll for Free</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- ===== Payment method chooser modal ===== -->
        <div
            v-if="paymentOpen"
            class="fixed inset-0 z-[80] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 bg-[#0D1527]/70 backdrop-blur-sm" @click="closePayment"></div>

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
                            <h3 class="mt-1.5 text-xl font-black tracking-tight text-white">{{ props.training.title }}</h3>
                        </div>
                        <button @click="closePayment" class="shrink-0 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/25" title="Close">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>
                    <div class="relative z-10 mt-3 inline-flex items-center gap-2 rounded-full bg-yellow-400 px-4 py-1.5 text-sm font-black text-[#0D1527]">
                        <i class="bi bi-cash-coin"></i> {{ formatPrice(props.training.price) }}
                    </div>
                </div>

                <!-- body -->
                <div class="px-6 py-6">
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
                            @click="confirmPayment"
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
                        <button @click="closePayment" class="rounded-full px-4 py-3.5 text-sm font-bold text-slate-500 transition hover:text-[#0D1527]">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>