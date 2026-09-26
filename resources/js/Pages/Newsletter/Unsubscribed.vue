<script setup>
import { Link, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

defineProps({
    status: { type: String, required: true },
    email: { type: String, default: null },
});
</script>

<template>
    <Head>
        <meta name="robots" content="noindex, nofollow" />
    </Head>
    <div class="mx-auto flex min-h-[60vh] max-w-xl flex-col items-center justify-center px-4 py-16 text-center">
        <div
            class="flex h-16 w-16 items-center justify-center rounded-2xl"
            :class="status === 'unsubscribed' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'"
        >
            <i :class="['bi text-2xl', status === 'unsubscribed' ? 'bi-envelope-dash' : 'bi-exclamation-triangle']"></i>
        </div>

        <h1 class="mt-6 text-2xl font-black text-slate-900">
            {{ status === 'unsubscribed' ? 'You have been unsubscribed' : 'Unsubscribe link invalid' }}
        </h1>

        <p class="mt-3 text-sm leading-relaxed text-slate-500">
            <template v-if="status === 'unsubscribed'">
                <strong>{{ email }}</strong> will no longer receive newsletter emails from Envoy Electricals.
                We're sorry to see you go — our doors are always open if you change your mind.
            </template>
            <template v-else>
                This link is expired or was already used. If you'd like to stop receiving emails, reply to any
                newsletter and ask to be removed.
            </template>
        </p>

        <Link href="/" class="mt-8 rounded-xl bg-[#0D1527] px-6 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
            Back to Home
        </Link>
    </div>
</template>