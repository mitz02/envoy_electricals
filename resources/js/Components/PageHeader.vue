<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    actionLabel: { type: String, default: '' },
    actionHref: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-black uppercase tracking-tight text-[#0D1527] sm:text-2xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-slate-500">{{ subtitle }}</p>
        </div>
        <div v-if="actionLabel && actionHref">
            <Link
                :href="actionHref"
                class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0D1527]/90 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ actionLabel }}
            </Link>
        </div>
    </div>
</template>
