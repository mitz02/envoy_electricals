<script setup>
import { watch, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(null);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success || flash?.error) {
            visible.value = {
                type: flash.success ? 'success' : 'error',
                message: flash.success || flash.error,
            };
            setTimeout(() => {
                visible.value = null;
            }, 4000);
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div v-if="visible" class="pointer-events-none fixed inset-x-0 top-20 z-50 flex justify-center px-4">
        <div
            :class="visible.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'"
            class="pointer-events-auto flex max-w-md items-center gap-3 rounded-xl px-5 py-3 text-sm font-medium text-white shadow-xl"
        >
            <svg v-if="visible.type === 'success'" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{{ visible.message }}</span>
        </div>
    </div>
</template>
