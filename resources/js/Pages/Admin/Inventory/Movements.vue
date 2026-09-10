<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDateTime, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    movements: { type: Object, required: true },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    types: { type: Array, default: () => [] },
});

const form = useForm({
    product_id: props.filters.product_id ?? '',
    type: props.filters.type ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const typeColors = {
    opening: 'bg-emerald-100 text-emerald-800',
    purchase: 'bg-amber-100 text-amber-800',
    sale: 'bg-indigo-100 text-indigo-800',
    project_issue: 'bg-amber-100 text-amber-800',
    project_return: 'bg-teal-100 text-teal-800',
    damage: 'bg-red-100 text-red-700',
    return: 'bg-green-100 text-green-800',
    adjustment: 'bg-slate-100 text-slate-700',
};

function apply() {
    const params = {};
    Object.entries(form.data()).forEach(([k, v]) => {
        if (v !== '' && v !== null) {
            params[k] = v;
        }
    });
    window.location.search = new URLSearchParams(params).toString();
}
</script>

<template>
        <FlashMessages />
        <PageHeader title="Stock Movements" subtitle="Complete inventory audit trail." />

        <div class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-5">
            <select v-model="form.product_id" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="apply">
                <option value="">All Products</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
            </select>
            <select v-model="form.type" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="apply">
                <option value="">All Types</option>
                <option v-for="t in types" :key="t" :value="t">{{ t.replace('_', ' ') }}</option>
            </select>
            <input v-model="form.from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="apply" />
            <input v-model="form.to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="apply" />
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="apply">
                Apply
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Ref</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Reference</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Change</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Prev â†’ New</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Unit Cost</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in movements.data" :key="m.id" class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ formatDateTime(m.movement_date) }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ m.ref_id }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ m.product?.name ?? 'Deleted product' }}</p>
                                <p class="text-xs text-slate-400">{{ m.product?.sku }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="badgeClass(typeColors[m.type] ?? 'bg-slate-100 text-slate-600')">
                                    {{ m.type.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ m.reference }}</td>
                            <td class="px-4 py-3 text-right">
                                <span :class="m.quantity_change >= 0 ? 'text-emerald-700' : 'text-red-700'" class="font-semibold">
                                    {{ m.quantity_change >= 0 ? '+' : '' }}{{ m.quantity_change }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ m.prev_quantity }} â†’ {{ m.new_quantity }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ naira(m.unit_cost) }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ m.user?.name ?? 'â€”' }}</td>
                        </tr>
                        <tr v-if="!movements.data.length">
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-400">No stock movements recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="movements" />
            </div>
        </div>
</template>