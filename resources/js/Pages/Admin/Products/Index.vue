<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, stockStatusLabel, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { can, has } = useCan();
const adjusting = ref(null);
const showVoidConfirm = ref(null);

const form = useForm({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ?? '',
    stock_status: props.filters.stock_status ?? '',
});

const adjustmentForm = useForm({
    quantity_change: 0,
    type: 'adjustment',
    reason: '',
});

const filteredProducts = computed(() => props.products.data || []);

function applyFilters() {
    router.get('/admin/products', form.data(), {
        preserveState: true,
        preserveScroll: true,
    });
}

function openAdjust(product) {
    adjustmentForm.reset();
    adjustmentForm.clearErrors();
    adjusting.value = product;
}

function submitAdjust() {
    adjustmentForm.post(`/admin/products/${adjusting.value.id}/adjust-stock`, {
        preserveScroll: true,
        onSuccess: () => {
            adjusting.value = null;
        },
    });
}

function confirmVoid(product) {
    const form = useForm({ void_reason: '' });
    showVoidConfirm.value = product;
}
</script>

<template>
        <FlashMessages />
        <PageHeader
            title="Products"
            subtitle="Manage products, prices and stock."
            :action-href="has('products.create') ? '/admin/products/create' : ''"
            action-label="Add Product"
        />

        <!-- Filters -->
        <div class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-4">
            <input
                v-model="form.search"
                type="search"
                placeholder="Search name, SKU, refâ€¦"
                class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                @keyup.enter="applyFilters"
            />
            <select v-model="form.category_id" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All Categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="form.stock_status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All Stock</option>
                <option value="low">Low Stock</option>
                <option value="out">Out of Stock</option>
            </select>
            <button
                class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90"
                @click="applyFilters"
            >
                Filter
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Selling Price</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Stock</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Stock Value</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in filteredProducts" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/products/${p.id}/edit`" class="font-medium text-slate-900 hover:text-slate-600">
                                    {{ p.name }}
                                </Link>
                                <p class="text-xs text-slate-400">{{ p.brand || 'â€”' }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ p.sku }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ p.category?.name ?? 'â€”' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(p.selling_price) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="badgeClass((stockStatusLabel[p.stock_status] || {}).cls)">
                                    {{ p.current_quantity }} Â· {{ (stockStatusLabel[p.stock_status] || {}).label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ naira(p.stock_value) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="badgeClass(p.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">
                                    {{ p.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        v-if="has('inventory.adjust')"
                                        class="rounded-md px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50"
                                        @click="openAdjust(p)"
                                    >
                                        Adjust
                                    </button>
                                    <Link
                                        v-if="has('products.edit')"
                                        :href="`/admin/products/${p.id}/edit`"
                                        class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filteredProducts.length">
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">
                                No products found. Add your first product to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="products" />
            </div>
        </div>

        <!-- Adjust stock modal -->
        <div v-if="adjusting" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0D1527]/50 p-4" @click.self="adjusting = null">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">Adjust Stock</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ adjusting.sku }} â€” {{ adjusting.name }} Â· Currently {{ adjusting.current_quantity }} in stock.
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="submitAdjust">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Quantity change</label>
                        <input
                            v-model.number="adjustmentForm.quantity_change"
                            type="number"
                            step="1"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            :class="{ 'border-red-400': adjustmentForm.errors.quantity_change }"
                        />
                        <p class="mt-1 text-xs text-slate-400">Positive increases stock, negative decreases it.</p>
                        <div v-if="adjustmentForm.errors.quantity_change" class="mt-1 text-xs text-red-600">{{ adjustmentForm.errors.quantity_change }}</div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
                        <select v-model="adjustmentForm.type" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="adjustment">Adjustment</option>
                            <option value="damage">Damaged Stock</option>
                            <option value="return">Returned Stock</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Reason <span class="text-red-500">*</span></label>
                        <textarea
                            v-model="adjustmentForm.reason"
                            rows="2"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            :class="{ 'border-red-400': adjustmentForm.errors.reason }"
                        />
                        <div v-if="adjustmentForm.errors.reason" class="mt-1 text-xs text-red-600">{{ adjustmentForm.errors.reason }}</div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="adjusting = null">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" :disabled="adjustmentForm.processing">
                            Save Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
</template>