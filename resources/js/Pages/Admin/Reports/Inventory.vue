<script setup>
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, maskNaira, stockStatusLabel, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const exportLink = computed(() => '/admin/reports/export?type=inventory');

function stockStatus(p) {
    if (p.current_quantity <= 0) {
        return stockStatusLabel.out_of_stock;
    }
    if (p.current_quantity <= p.reorder_level) {
        return stockStatusLabel.low_stock;
    }
    return stockStatusLabel.in_stock;
}

function applyStatus(status) {
    const params = new URLSearchParams(props.filters);
    if (props.filters.status === status) {
        params.delete('status');
    } else {
        params.set('status', status);
    }
    window.location.search = params.toString();
}
</script>

<template>
        <FlashMessages />

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Inventory Report</h1>
                <p class="mt-1 text-sm text-slate-500">Stock on hand, average cost and reorder alerts.</p>
            </div>
            <a :href="exportLink" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Export CSV</a>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <button class="rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm hover:bg-slate-50" @click="applyStatus('')">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Stock Value</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ maskNaira('inventory_value', summary.total_value) }}</p>
            </button>
            <button class="rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm hover:bg-slate-50" @click="applyStatus('low')">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Low Stock</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ summary.low }}</p>
            </button>
            <button class="rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm hover:bg-slate-50" @click="applyStatus('out')">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Out of Stock</p>
                <p class="mt-2 text-2xl font-bold text-red-700">{{ summary.out }}</p>
            </button>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Valuated Items</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.unit_cost }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <form class="flex gap-3" method="get">
                <input :default-value="props.filters.search" name="search" type="search" placeholder="Search by name or SKU…" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90">Search</button>
            </form>
        </div>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Qty</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Avg Cost</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Value</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in products.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ p.name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ p.sku }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-700">{{ p.current_quantity }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ maskNaira('cost_price', p.average_cost) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ maskNaira('inventory_value', p.current_quantity * p.average_cost) }}</td>
                            <td class="px-4 py-3 text-center"><span :class="badgeClass(stockStatus(p).cls)">{{ stockStatus(p).label }}</span></td>
                        </tr>
                        <tr v-if="!products.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No products found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="products" />
            </div>
        </div>
</template>