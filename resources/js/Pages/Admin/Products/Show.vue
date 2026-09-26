<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    product: { type: Object, required: true },
    stores: { type: Array, default: () => [] },
    selectedStoreId: { type: Number, default: null },
    summary: { type: Object, required: true },
    purchases: { type: Object, required: true },
    movements: { type: Object, required: true },
});

const tab = ref('purchases');

const tabs = computed(() => [
    { key: 'purchases', label: 'Purchase History', count: props.purchases?.total ?? 0 },
    { key: 'movements', label: 'Stock Movements', count: props.movements?.total ?? 0 },
]);

const margin = computed(() => {
    if (!summary.selling_price || !summary.average_cost) return null;
    return round((summary.selling_price - summary.average_cost) / summary.selling_price, 4);
});

const marginPercent = computed(() => (margin.value === null ? null : Math.round(margin.value * 100) / 100));

function round(value, precision) {
    const factor = 10 ** precision;
    return Math.round(value * factor) / factor;
}

function stockBadge() {
    if (summary.on_hand <= 0) {
        return badgeClass('bg-red-100 text-red-700');
    }
    if (summary.on_hand <= summary.reorder_level) {
        return badgeClass('bg-amber-100 text-amber-800');
    }
    return badgeClass('bg-emerald-100 text-emerald-800');
}

function stockLabel() {
    if (summary.on_hand <= 0) return 'Out of stock';
    if (summary.on_hand <= summary.reorder_level) return 'Low stock';
    return 'In stock';
}

function movementBadge(type) {
    const inbound = ['opening', 'purchase', 'transfer_in', 'return'].includes(type);
    const map = inbound
        ? 'bg-emerald-100 text-emerald-800'
        : 'bg-red-100 text-red-700';
    return badgeClass(map);
}

function movementIcon(type) {
    const inbound = ['opening', 'purchase', 'transfer_in', 'return'].includes(type);
    return inbound ? 'bi-arrow-down-left' : 'bi-arrow-up-right';
}

function goToTab(key) {
    tab.value = key;
}
</script>

<template>
    <FlashMessages />

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <Link href="/admin/products" class="text-sm font-medium text-slate-500 hover:text-slate-800">
            <i class="bi bi-arrow-left mr-1" />Products
        </Link>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            <Link
                :href="`/admin/stock/adjust?product=${product.id}`"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                <i class="bi bi-sliders" />Adjust Stock
            </Link>
            <Link
                :href="`/admin/products/${product.id}/edit`"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                <i class="bi bi-pencil" />Edit
            </Link>
            <Link
                href="/admin/purchases/create"
                class="inline-flex items-center gap-1.5 rounded-lg bg-[#0D1527] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90"
            >
                <i class="bi bi-bag-plus" />Record a Purchase
            </Link>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center gap-4">
        <img
            v-if="product.images?.length"
            :src="`/storage/${product.images[0].path}`"
            :alt="product.name"
            class="h-20 w-20 rounded-xl border border-slate-200 object-cover"
        />
        <div
            v-else
            class="flex h-20 w-20 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-2xl font-bold text-slate-300"
        >
            {{ product.name.charAt(0).toUpperCase() }}
        </div>
        <div class="min-w-0">
            <PageHeader :title="product.name" :subtitle="`${product.sku} · ${product.ref_id}`" />
            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                <span :class="stockBadge()">{{ stockLabel() }} · {{ summary.on_hand }} {{ product.unit }}</span>
                <span
                    v-if="product.category"
                    class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600"
                >{{ product.category.name }}</span>
                <span
                    v-if="product.brand"
                    class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600"
                >{{ product.brand.name }}</span>
                <span
                    class="rounded-full px-2.5 py-1 font-medium"
                    :class="product.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                >{{ product.status }}</span>
            </div>
        </div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-slate-500">Stock on Hand</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ summary.on_hand }}</p>
            <p class="text-xs text-slate-400">Reorder at {{ summary.reorder_level }} {{ product.unit }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-slate-500">Average Cost</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ naira(summary.average_cost) }}</p>
            <p class="text-xs text-slate-400">Weighted average</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-slate-500">Stock Value</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ naira(summary.stock_value) }}</p>
            <p class="text-xs text-slate-400">On hand × average cost</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-slate-500">Selling Price</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ naira(summary.selling_price) }}</p>
            <p class="text-xs text-slate-400">
                <template v-if="marginPercent !== null">Margin {{ marginPercent }}%</template>
                <template v-else>No margin set</template>
            </p>
        </div>
    </div>

    <div class="mb-5 grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Activity</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total purchased</dt>
                    <dd class="font-medium text-slate-800">{{ summary.total_purchased }} {{ product.unit }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total sold</dt>
                    <dd class="font-medium text-slate-800">{{ summary.total_sold }} {{ product.unit }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Preferred supplier</dt>
                    <dd class="font-medium text-slate-800">{{ product.supplier?.name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Last purchase</dt>
                    <dd class="text-right font-medium text-slate-800">
                        <template v-if="summary.last_purchase">
                            <span class="font-mono text-xs">{{ summary.last_purchase.ref_id }}</span>
                            <span class="block text-xs font-normal text-slate-400">
                                {{ formatDate(summary.last_purchase.purchase_date) }}
                            </span>
                        </template>
                        <template v-else>—</template>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Details</h2>
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Barcode</dt>
                    <dd class="truncate font-medium text-slate-800">{{ product.barcode || '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Subcategory</dt>
                    <dd class="font-medium text-slate-800">{{ product.subcategory?.name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Listed online</dt>
                    <dd class="font-medium text-slate-800">{{ product.is_visible_online ? 'Yes' : 'No' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Featured</dt>
                    <dd class="font-medium text-slate-800">{{ product.is_featured ? 'Yes' : 'No' }}</dd>
                </div>
            </dl>
            <p v-if="product.description" class="mt-4 text-sm leading-relaxed text-slate-600">
                {{ product.description }}
            </p>
        </div>
    </div>

    <div v-if="stores.length" class="mb-5 rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <h2 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">
            Stock by Store
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Store</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">On Hand</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Average Cost</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Stock Value</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Reorder At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="s in stores"
                        :key="s.store_id"
                        class="hover:bg-slate-50"
                        :class="{ 'bg-[#0D1527]/[0.04]': s.store_id === selectedStoreId }"
                    >
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-900">{{ s.store_name }}</span>
                            <span
                                v-if="s.store_id === selectedStoreId"
                                class="ml-2 rounded-full bg-[#0D1527] px-2 py-0.5 text-[10px] font-bold uppercase text-white"
                            >Selected</span>
                            <span
                                v-else-if="s.is_default"
                                class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-500"
                            >Default</span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ s.current_quantity }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ naira(s.average_cost) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(s.stock_value) }}</td>
                        <td class="px-4 py-3 text-right text-slate-400">{{ s.reorder_level }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="flex flex-wrap items-center gap-1 border-b border-slate-100 px-3 py-2">
            <button
                v-for="t in tabs"
                :key="t.key"
                @click="goToTab(t.key)"
                class="rounded-lg px-3 py-2 text-sm font-medium transition"
                :class="tab === t.key ? 'bg-[#0D1527] text-white' : 'text-slate-600 hover:bg-slate-100'"
            >
                {{ t.label }}
                <span class="ml-1 text-xs opacity-70">{{ t.count }}</span>
            </button>
        </div>

        <div v-if="tab === 'purchases'" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Purchase</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Supplier</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Qty</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Unit Cost</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="item in purchases.data" :key="item.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link
                                v-if="item.purchase"
                                :href="`/admin/purchases/${item.purchase.id}`"
                                class="font-mono text-xs font-medium text-slate-700 hover:text-slate-900"
                            >{{ item.purchase.ref_id }}</Link>
                            <span v-else class="text-slate-300">—</span>
                            <span
                                v-if="item.purchase?.status === 'void'"
                                class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase text-red-700"
                            >Void</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ item.purchase ? formatDate(item.purchase.purchase_date) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ item.purchase?.supplier?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ item.quantity }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ naira(item.unit_cost) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(item.total) }}</td>
                    </tr>
                    <tr v-if="!purchases.data.length">
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                            This product has never been purchased.
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="purchases" />
            </div>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Change</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Balance</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Unit Cost</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Reference</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Store</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="m in movements.data" :key="m.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-600">{{ formatDate(m.movement_date) }}</td>
                        <td class="px-4 py-3">
                            <span :class="movementBadge(m.type)" class="inline-flex items-center gap-1">
                                <i class="bi text-xs" :class="movementIcon(m.type)" />
                                {{ m.type.replace(/_/g, ' ') }}
                            </span>
                        </td>
                        <td
                            class="px-4 py-3 text-right font-semibold"
                            :class="m.quantity_change >= 0 ? 'text-emerald-700' : 'text-red-700'"
                        >
                            {{ m.quantity_change > 0 ? '+' : '' }}{{ m.quantity_change }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ m.new_quantity }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">
                            {{ m.unit_cost !== null ? naira(m.unit_cost) : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <Link
                                v-if="m.document_type === 'purchase' && m.document_id"
                                :href="`/admin/purchases/${m.document_id}`"
                                class="font-mono text-xs text-slate-600 hover:text-slate-900"
                            >{{ m.reference }}</Link>
                            <span v-else class="text-xs text-slate-500">{{ m.reference || '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ m.store?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ m.user?.name ?? '—' }}</td>
                    </tr>
                    <tr v-if="!movements.data.length">
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">
                            No stock movements recorded for this product.
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="movements" />
            </div>
        </div>
    </div>
</template>
