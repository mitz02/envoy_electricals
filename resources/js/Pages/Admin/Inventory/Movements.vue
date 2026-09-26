<script setup>
import { computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { maskNaira, formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    movements: { type: Object, required: true },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    types: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ entries: 0, units_in: 0, units_out: 0, net: 0 }) },
    activeFilterCount: { type: Number, default: 0 },
});

const { has } = useCan();

const form = useForm({
    product_id: props.filters.product_id ?? '',
    type: props.filters.type ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

/** Only show the movement types that actually have rows for this filter. */
const visibleTypes = computed(() => props.types.filter((t) => t.entries > 0));

const typeColors = {
    opening: 'bg-emerald-100 text-emerald-800',
    purchase: 'bg-amber-100 text-amber-800',
    sale: 'bg-indigo-100 text-indigo-800',
    project_issue: 'bg-amber-100 text-amber-800',
    project_return: 'bg-teal-100 text-teal-800',
    damage: 'bg-red-100 text-red-700',
    return: 'bg-green-100 text-green-800',
    adjustment: 'bg-slate-100 text-slate-700',
    transfer_in: 'bg-emerald-100 text-emerald-800',
    transfer_out: 'bg-amber-100 text-amber-800',
};

const typeIcons = {
    opening: 'bi-box-seam',
    purchase: 'bi-cart-check',
    sale: 'bi-cart-dash',
    project_issue: 'bi-tools',
    project_return: 'bi-arrow-uturn-left',
    damage: 'bi-exclamation-triangle',
    return: 'bi-arrow-return-left',
    adjustment: 'bi-sliders',
    transfer_in: 'bi-arrow-right-circle',
    transfer_out: 'bi-arrow-left-circle',
};

const selectedProductName = computed(
    () => props.products.find((p) => String(p.id) === String(form.product_id))?.name ?? null,
);

function apply() {
    router.get(
        '/admin/stock/movements',
        Object.fromEntries(Object.entries(form.data()).filter(([, v]) => v !== '' && v !== null)),
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function pickType(value) {
    form.type = form.type === value ? '' : value;
    apply();
}

function clearFilters() {
    form.reset();
    apply();
}
</script>

<template>
    <FlashMessages />

    <!-- Explains what this page actually is -->
    <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <h1 class="text-xl font-bold text-slate-900">Stock Movements</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Every single time stock enters or leaves a branch is written here, automatically. You never
                    create a movement yourself &mdash; purchases, sales, project usage, damage and corrections all
                    land on this page so you can trace any quantity back to its cause.
                </p>
            </div>
            <Link
                v-if="has('inventory.adjust')"
                href="/admin/stock/adjust"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90"
            >
                <i class="bi bi-sliders" />Correct a wrong count
            </Link>
        </div>
    </div>

    <!-- Plain-language totals for whatever is filtered right now -->
    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Entries shown</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ summary.entries }}</p>
            <p class="mt-0.5 text-xs text-slate-400">matching your filters</p>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">Units added</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">+{{ summary.units_in }}</p>
            <p class="mt-0.5 text-xs text-slate-400">stock that came in</p>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-red-600">Units removed</p>
            <p class="mt-1 text-2xl font-bold text-red-700">−{{ summary.units_out }}</p>
            <p class="mt-0.5 text-xs text-slate-400">stock that went out</p>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Net change</p>
            <p class="mt-1 text-2xl font-bold" :class="summary.net >= 0 ? 'text-slate-900' : 'text-red-700'">
                {{ summary.net > 0 ? '+' : '' }}{{ summary.net }}
            </p>
            <p class="mt-0.5 text-xs text-slate-400">added minus removed</p>
        </div>
    </div>

    <!-- Filter bar, each field labelled -->
    <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-medium text-slate-600">Product</label>
                <select v-model="form.product_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="apply">
                    <option value="">All products</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">Kind of movement</label>
                <select v-model="form.type" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="apply">
                    <option value="">All kinds</option>
                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">From date</label>
                <input v-model="form.from" type="date" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="apply" />
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">To date</label>
                <input v-model="form.to" type="date" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="apply" />
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90" @click="apply">
                Apply filters
            </button>
            <button v-if="activeFilterCount" class="text-sm font-medium text-slate-500 hover:text-slate-800" @click="clearFilters">
                Clear all
            </button>
            <p v-if="selectedProductName" class="text-sm text-slate-500">
                Showing history for <span class="font-medium text-slate-900">{{ selectedProductName }}</span>.
            </p>
        </div>
    </div>

    <!-- Legend: what each movement kind means, clickable to filter -->
    <div v-if="visibleTypes.length" class="mb-5">
        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">What these numbers mean</h2>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <button
                v-for="t in visibleTypes"
                :key="t.value"
                class="rounded-xl border p-3 text-left transition"
                :class="form.type === t.value
                    ? 'border-[#0D1527] bg-[#0D1527]/5'
                    : 'border-slate-200/80 bg-white hover:border-slate-300'"
                @click="pickType(t.value)"
            >
                <div class="flex items-center gap-2">
                    <i :class="[typeIcons[t.value] ?? 'bi-arrow-left-right', 'text-sm', t.direction === 'in' ? 'text-emerald-600' : t.direction === 'out' ? 'text-red-600' : 'text-slate-500']" />
                    <span class="text-sm font-semibold text-slate-900">{{ t.label }}</span>
                    <span class="ml-auto font-mono text-xs text-slate-500">
                        {{ t.units > 0 ? '+' : '' }}{{ t.units }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ t.description }}</p>
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">What happened</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Caused by</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Change</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Count before &rarr; after</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Unit cost</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Recorded by</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="m in movements.data" :key="m.id" class="align-top hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ formatDateTime(m.movement_date) }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ m.product?.name ?? 'Deleted product' }}</p>
                            <p class="font-mono text-xs text-slate-400">{{ m.product?.sku }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="badgeClass(typeColors[m.type] ?? 'bg-slate-100 text-slate-600')">
                                <i :class="[typeIcons[m.type] ?? 'bi-arrow-left-right', 'mr-1 text-[9px]']" />
                                {{ (types.find((t) => t.value === m.type)?.label ?? m.type).replace(/_/g, ' ') }}
                            </span>
                            <p v-if="m.reason" class="mt-1 max-w-[220px] text-xs text-slate-500">{{ m.reason }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ m.reference }}</td>
                        <td class="px-4 py-3 text-right font-semibold" :class="m.quantity_change >= 0 ? 'text-emerald-700' : 'text-red-700'">
                            {{ m.quantity_change > 0 ? '+' : '' }}{{ m.quantity_change }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap text-slate-600">
                            {{ m.prev_quantity }} &rarr; <span class="font-semibold text-slate-900">{{ m.new_quantity }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ maskNaira('cost_price', m.unit_cost) }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ m.user?.name ?? 'System' }}</td>
                    </tr>
                    <tr v-if="!movements.data.length">
                        <td colspan="8" class="px-4 py-14 text-center">
                            <i class="bi bi-inboxes mb-2 block text-3xl text-slate-300" />
                            <p class="text-sm font-medium text-slate-600">
                                {{ activeFilterCount ? 'Nothing matches these filters.' : 'No stock has moved yet.' }}
                            </p>
                            <p class="mt-1 text-sm text-slate-400">
                                <template v-if="activeFilterCount">Try widening the date range or clearing the filters.</template>
                                <template v-else>Record a Purchase, make a Sale, or correct a count to create the first movement.</template>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="movements" />
        </div>
    </div>
</template>
