<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import { naira, maskNaira, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    selectedStoreId: { type: [Number, String, null], default: null },
    brands: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
});

const { has } = useCan();
const adjusting = ref(null);
const bulkAction = ref('');
const showBulkBrandModal = ref(false);
const showBulkStockModal = ref(false);
const showBulkStoreModal = ref(false);

const selectedIds = ref([]);

const form = useForm({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ?? '',
    stock_status: props.filters.stock_status ?? '',
});

const deleteForm = useForm({});

const adjustmentForm = useForm({
    quantity_change: 0,
    type: 'adjustment',
    reason: '',
});

const bulkForm = useForm({
    ids: [],
    action: '',
    status: '',
});

const bulkBrandForm = useForm({
    ids: [],
    brand_id: '',
});

const bulkStockForm = useForm({
    ids: [],
    quantity_change: 0,
    type: 'adjustment',
    reason: '',
    store_id: '',
});

const bulkStoreForm = useForm({
    ids: [],
    store_id: '',
});

const filteredProducts = computed(() => props.products.data || []);

const allSelected = computed(() =>
    filteredProducts.value.length > 0 && filteredProducts.value.every(p => selectedIds.value.includes(p.id))
);

const someSelected = computed(() =>
    selectedIds.value.length > 0 && !allSelected.value
);

watch(() => filteredProducts.value, () => {
    selectedIds.value = [];
});

function applyFilters() {
    router.get('/admin/products', form.data(), {
        preserveState: true,
        preserveScroll: true,
    });
}

function deleteProduct(product) {
    const confirmed = window.confirm(`Delete "${product.name}" (${product.sku})? It will be removed from the product list.`);
    if (!confirmed) return;

    deleteForm.delete(route('admin.products.destroy', product.id), {
        preserveScroll: true,
    });
}

function deleteSelectedProducts() {
    const count = selectedIds.value.length;
    if (!count) return;

    const confirmed = window.confirm(`Delete ${count} selected product${count === 1 ? '' : 's'}? They will be removed from the product list.`);
    if (!confirmed) return;

    bulkForm.ids = [...selectedIds.value];
    bulkForm.action = 'delete';
    bulkForm.post(route('admin.products.bulk-delete'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            bulkAction.value = '';
        },
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

function toggleSelectAll() {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = filteredProducts.value.map(p => p.id);
    }
}

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(idx, 1);
    }
}

function executeBulkAction() {
    if (!bulkAction.value || selectedIds.value.length === 0) return;

    // Special handling for receive_stock - start a purchase pre-filled with the selected products
    if (bulkAction.value === 'receive_stock') {
        const ids = selectedIds.value.join(',');
        router.visit(`/admin/purchases/create?products=${ids}`, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                bulkAction.value = '';
            },
        });
        return;
    }

    // Special handling for bulk_brand - open modal
    if (bulkAction.value === 'bulk_brand') {
        bulkBrandForm.ids = [...selectedIds.value];
        bulkBrandForm.brand_id = '';
        bulkBrandForm.clearErrors();
        showBulkBrandModal.value = true;
        bulkAction.value = '';
        return;
    }

    // Special handling for bulk_adjust_stock - open modal
    if (bulkAction.value === 'bulk_adjust_stock') {
        bulkStockForm.ids = [...selectedIds.value];
        bulkStockForm.quantity_change = 0;
        bulkStockForm.type = 'adjustment';
        bulkStockForm.reason = '';
        bulkStockForm.store_id = '';
        bulkStockForm.clearErrors();
        showBulkStockModal.value = true;
        bulkAction.value = '';
        return;
    }

    // Special handling for bulk_store - open modal
    if (bulkAction.value === 'bulk_store') {
        bulkStoreForm.ids = [...selectedIds.value];
        bulkStoreForm.store_id = '';
        bulkStoreForm.clearErrors();
        showBulkStoreModal.value = true;
        bulkAction.value = '';
        return;
    }

    bulkForm.ids = [...selectedIds.value];
    bulkForm.action = bulkAction.value;

    const routeMap = {
        activate: '/admin/products/bulk-status',
        deactivate: '/admin/products/bulk-status',
        featured_on: '/admin/products/bulk-featured',
        featured_off: '/admin/products/bulk-featured',
        online_on: '/admin/products/bulk-online',
        online_off: '/admin/products/bulk-online',
    };

    const url = routeMap[bulkAction.value];
    if (!url) return;

    bulkForm.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            bulkAction.value = '';
        },
    });
}

function submitBulkBrand() {
    bulkBrandForm.post('/admin/products/bulk-brand', {
        preserveScroll: true,
        onSuccess: () => {
            showBulkBrandModal.value = false;
            selectedIds.value = [];
        },
    });
}

function submitBulkStock() {
    bulkStockForm.post('/admin/products/bulk-adjust-stock', {
        preserveScroll: true,
        onSuccess: () => {
            showBulkStockModal.value = false;
            selectedIds.value = [];
        },
    });
}

function submitBulkStore() {
    bulkStoreForm.post('/admin/products/bulk-store', {
        preserveScroll: true,
        onSuccess: () => {
            showBulkStoreModal.value = false;
            selectedIds.value = [];
        },
    });
}
</script>

<template>
    <div class="w-full">
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
                placeholder="Search name, SKU, ref…"
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

        <!-- Bulk Actions Bar -->
        <div v-if="selectedIds.length > 0" class="mb-4 flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-sm font-medium text-amber-800">
                {{ selectedIds.length }} product{{ selectedIds.length === 1 ? '' : 's' }} selected
            </span>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="has('products.delete')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="bulkForm.processing"
                    @click="deleteSelectedProducts"
                >
                    <i class="bi bi-trash3"></i>
                    Delete selected
                </button>
                <select
                    v-model="bulkAction"
                    class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                >
                    <option value="">Bulk action…</option>
                    <option v-if="has('products.edit')" value="activate">Activate</option>
                    <option v-if="has('products.edit')" value="deactivate">Deactivate</option>
                    <option v-if="has('products.edit')" value="featured_on">Mark featured</option>
                    <option v-if="has('products.edit')" value="featured_off">Remove featured</option>
                    <option v-if="has('products.edit')" value="online_on">Enable online sale</option>
                    <option v-if="has('products.edit')" value="online_off">Disable online sale</option>
                    <option v-if="has('products.edit')" value="bulk_brand">Change brand</option>
                    <option v-if="has('products.edit')" value="bulk_store">Change store</option>
                    <option v-if="has('inventory.adjust')" value="bulk_adjust_stock">Adjust stock (add/remove)</option>
                    <option v-if="has('purchases.create')" value="receive_stock">Receive stock via purchase</option>
                </select>
                <button
                    :disabled="!bulkAction"
                    @click="executeBulkAction"
                    class="rounded-lg bg-[#0D1527] px-3 py-1.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50"
                >
                    Apply
                </button>
                <button
                    @click="selectedIds = []"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-center w-12">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    :indeterminate="someSelected"
                                    @change="toggleSelectAll"
                                    class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                                />
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                            <th v-if="props.selectedStoreId" class="px-4 py-3 text-left font-semibold text-slate-600">Store</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Selling Price</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Stock</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Stock Value</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="p in filteredProducts"
                            :key="p.id"
                            :class="selectedIds.includes(p.id) ? 'bg-amber-50/80 hover:bg-amber-50/80' : p.current_quantity <= 0 ? 'bg-red-50 hover:bg-red-50' : 'hover:bg-slate-50'"
                        >
                            <td class="px-4 py-3 text-center">
                                <input
                                    type="checkbox"
                                    :value="p.id"
                                    :checked="selectedIds.includes(p.id)"
                                    @change="toggleSelect(p.id)"
                                    class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="`/admin/products/${p.id}`" class="font-medium text-slate-900 hover:text-slate-600">
                                    {{ p.name }}
                                </Link>
                                <p class="text-xs text-slate-400">{{ p.brand?.name || '—' }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ p.sku }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ p.category?.name ?? '—' }}</td>
                            <td v-if="props.selectedStoreId" class="px-4 py-3 text-sm text-slate-500">
                                {{ p.stores?.find(s => s.id === Number(props.selectedStoreId))?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(p.selling_price) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="p.current_quantity <= 0 ? 'inline-flex items-center justify-center px-2.5 py-1 rounded-full bg-red-50 text-red-700 font-semibold' : 'text-slate-900 font-medium'">
                                    {{ p.current_quantity <= 0 ? 'Out of stock' : p.current_quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ maskNaira('inventory_value', p.stock_value) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="badgeClass(p.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">
                                    {{ p.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        v-if="has('inventory.adjust')"
                                        class="rounded-md p-1.5 text-slate-500 hover:bg-amber-50 hover:text-amber-700 transition"
                                        @click="openAdjust(p)"
                                        title="Adjust stock"
                                    >
                                        <i class="bi bi-sliders text-base" />
                                    </button>
                                    <Link
                                        v-if="has('products.edit')"
                                        :href="`/admin/products/${p.id}/edit`"
                                        class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition"
                                        title="Edit product"
                                    >
                                        <i class="bi bi-pencil text-base" />
                                    </Link>
                                    <Dropdown v-if="has('products.delete')" align="right" width="48" content-classes="py-1 bg-white">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                class="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                                title="More actions"
                                                :aria-label="`More actions for ${p.name}`"
                                            >
                                                <i class="bi bi-three-dots-vertical text-base" />
                                            </button>
                                        </template>
                                        <template #content>
                                            <button
                                                type="button"
                                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="deleteForm.processing"
                                                @click="deleteProduct(p)"
                                            >
                                                <i class="bi bi-trash3" />
                                                Delete product
                                            </button>
                                        </template>
                                    </Dropdown>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filteredProducts.length">
                            <td :colspan="props.selectedStoreId ? 10 : 9" class="px-4 py-12 text-center text-sm text-slate-400">
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
                    {{ adjusting.sku }} — {{ adjusting.name }} · Currently {{ adjusting.current_quantity }} in stock.
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

        <!-- Bulk Brand Modal -->
        <Modal :show="showBulkBrandModal" max-width="md" @close="showBulkBrandModal = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Change Brand for {{ selectedIds.length }} Product(s)</h2>
                <p class="mt-1 text-sm text-slate-500">Select a new brand or choose "None" to remove brand assignment.</p>

                <form class="mt-4 space-y-4" @submit.prevent="submitBulkBrand">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Brand <span class="text-red-500">*</span></label>
                        <select v-model="bulkBrandForm.brand_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" :class="{ 'border-red-400': bulkBrandForm.errors.brand_id }">
                            <option value="">-- None (remove brand) --</option>
                            <option v-for="b in props.brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <div v-if="bulkBrandForm.errors.brand_id" class="mt-1 text-xs text-red-600">{{ bulkBrandForm.errors.brand_id }}</div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showBulkBrandModal = false">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" :disabled="bulkBrandForm.processing">
                            {{ bulkBrandForm.processing ? 'Saving…' : 'Apply Brand' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Bulk Adjust Stock Modal -->
        <Modal :show="showBulkStockModal" max-width="md" @close="showBulkStockModal = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Adjust Stock for {{ selectedIds.length }} Product(s)</h2>
                <p class="mt-1 text-sm text-slate-500">Enter quantity change (positive to add, negative to remove) and reason.</p>

                <form class="mt-4 space-y-4" @submit.prevent="submitBulkStock">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Quantity Change <span class="text-red-500">*</span></label>
                        <input
                            v-model.number="bulkStockForm.quantity_change"
                            type="number"
                            step="1"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            :class="{ 'border-red-400': bulkStockForm.errors.quantity_change }"
                            placeholder="e.g. 10 or -5"
                        />
                        <p class="mt-1 text-xs text-slate-400">Positive adds stock, negative removes stock.</p>
                        <div v-if="bulkStockForm.errors.quantity_change" class="mt-1 text-xs text-red-600">{{ bulkStockForm.errors.quantity_change }}</div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Type <span class="text-red-500">*</span></label>
                        <select v-model="bulkStockForm.type" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" :class="{ 'border-red-400': bulkStockForm.errors.type }">
                            <option value="adjustment">Adjustment (cycle count, found stock, etc.)</option>
                            <option value="damage">Damaged Stock (write-off)</option>
                            <option value="return">Returned Stock</option>
                        </select>
                        <div v-if="bulkStockForm.errors.type" class="mt-1 text-xs text-red-600">{{ bulkStockForm.errors.type }}</div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Reason <span class="text-red-500">*</span></label>
                        <textarea
                            v-model="bulkStockForm.reason"
                            rows="2"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            :class="{ 'border-red-400': bulkStockForm.errors.reason }"
                            placeholder="e.g. Cycle count adjustment / Damaged in storage / Customer returns"
                        />
                        <div v-if="bulkStockForm.errors.reason" class="mt-1 text-xs text-red-600">{{ bulkStockForm.errors.reason }}</div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Store (optional)</label>
                        <select v-model="bulkStockForm.store_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">Default store</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showBulkStockModal = false">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" :disabled="bulkStockForm.processing">
                            {{ bulkStockForm.processing ? 'Saving…' : 'Adjust Stock' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Bulk Store Modal -->
        <Modal :show="showBulkStoreModal" max-width="md" @close="showBulkStoreModal = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Add to Store for {{ selectedIds.length }} Product(s)</h2>
                <p class="mt-1 text-sm text-slate-500">Select a store to add these products to. Products already in the store will be skipped.</p>

                <form class="mt-4 space-y-4" @submit.prevent="submitBulkStore">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Store <span class="text-red-500">*</span></label>
                        <select v-model="bulkStoreForm.store_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" :class="{ 'border-red-400': bulkStoreForm.errors.store_id }">
                            <option value="">Select a store</option>
                            <option v-for="s in props.stores" :key="s.id" :value="s.id">{{ s.name }} ({{ s.code }})</option>
                        </select>
                        <div v-if="bulkStoreForm.errors.store_id" class="mt-1 text-xs text-red-600">{{ bulkStoreForm.errors.store_id }}</div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showBulkStoreModal = false">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" :disabled="bulkStoreForm.processing">
                            {{ bulkStoreForm.processing ? 'Saving…' : 'Add to Store' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>