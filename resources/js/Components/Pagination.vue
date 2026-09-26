<script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    meta: { type: Object, required: true },
    preserveScroll: { type: Boolean, default: true },
});

const pages = computed(() => {
    const total = props.meta?.last_page ?? 1;
    const current = props.meta?.current_page ?? 1;
    const out = [];
    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || Math.abs(i - current) <= 2) {
            out.push(i);
        } else if (out[out.length - 1] !== '…') {
            out.push('…');
        }
    }
    return out;
});

function go(pageNumber) {
    if (!pageNumber || pageNumber < 1 || pageNumber > props.meta.last_page) {
        return;
    }

    const query = Object.fromEntries(new URLSearchParams(window.location.search));
    query.page = pageNumber;

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: props.preserveScroll,
    });
}
</script>

<template>
    <div v-if="meta && meta.total > meta.per_page" class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-xs text-slate-500">
            Showing {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} of {{ meta.total }}
        </p>
        <nav class="flex gap-1">
            <button
                class="rounded-md px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-40"
                :disabled="!meta.prev_page_url"
                @click="go(meta.current_page - 1)"
            >
                Prev
            </button>
            <template v-for="(p, i) in pages" :key="i">
                <span v-if="p === '…'" class="px-1 py-1 text-sm text-slate-400">…</span>
                <button
                    v-else
                    :class="p === meta.current_page ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    class="rounded-md px-3 py-1.5 text-sm font-medium"
                    @click="go(p)"
                >
                    {{ p }}
                </button>
            </template>
            <button
                class="rounded-md px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-40"
                :disabled="!meta.next_page_url"
                @click="go(meta.current_page + 1)"
            >
                Next
            </button>
        </nav>
    </div>
</template>