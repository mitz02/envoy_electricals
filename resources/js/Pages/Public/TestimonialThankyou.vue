<script setup>
import { Link, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    testimonial: { type: Object, default: null },
});
</script>

<template>
    <Head title="Thank You! — Envoy Electricals" />

    <section class="relative bg-[#FAF8F2] py-16 sm:py-24 px-4 sm:px-6 lg:px-8 overflow-hidden min-h-[60vh] flex items-center justify-center">
        <div class="absolute -top-32 -right-24 w-96 h-96 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-24 w-80 h-80 rounded-full bg-[#60d4f7]/10 blur-3xl pointer-events-none"></div>

        <div class="max-w-md mx-auto text-center relative">
            <!-- Success Icon -->
            <div class="mx-auto w-24 h-24 rounded-full bg-emerald-100 flex items-center justify-center mb-6">
                <i class="bi bi-check-circle-fill text-4xl text-emerald-600" />
            </div>

            <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tight text-slate-950 leading-tight mb-4">
                Thank You, {{ testimonial?.author_name || 'Friend' }}!
            </h1>

            <p class="text-slate-500 text-base leading-relaxed mb-8">
                Your testimonial has been submitted successfully. Our team will review it and publish it on our website once approved.
            </p>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-lg p-6 mb-8">
                <div class="flex items-center gap-3 text-slate-600">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-clock text-emerald-600 text-xl" />
                    </div>
                    <div class="text-left">
                        <p class="font-semibold text-slate-900">What happens next?</p>
                        <p class="text-sm mt-1">Our team will review your submission within 1-2 business days.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <Link href="/" class="inline-flex items-center gap-2 bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-6 py-3 rounded-full transition-colors">
                    <i class="bi bi-house" /> Back to Home
                </Link>
                <Link href="/shop" class="inline-flex items-center gap-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold px-6 py-3 rounded-full transition-colors">
                    <i class="bi bi-shop" /> Browse Products
                </Link>
            </div>

            <p class="mt-8 text-xs text-slate-400">
                If you have any questions, contact us at
                <a href="mailto:hello@envoyelectricals.com" class="text-[#016cbb] hover:underline">hello@envoyelectricals.com</a>
            </p>
        </div>
    </section>
</template>