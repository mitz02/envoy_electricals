<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { useCan } from '@/composables/permissions';
import { naira, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const { has } = useCan();

const props = defineProps({
    store: { type: Object, default: () => ({}) },
    inventoryStats: { type: Object, default: () => ({}) },
    recentSales: { type: Array, default: () => [] },
    recentTransfers: { type: Array, default: () => [] },
    allStores: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const store = computed(() => props.store);

// Transfer Stock Modal
const showTransferModal = ref(false);
const transferForm = useForm({
    product_id: '',
    from_store_id: props.store.id,
    to_store_id: '',
    quantity: 1,
    reason: '',
});

function openTransferModal(direction = 'out') {
    if (direction === 'out') {
        transferForm.from_store_id = props.store.id;
        transferForm.to_store_id = props.allStores[0]?.id || '';
    } else {
        transferForm.from_store_id = props.allStores[0]?.id || '';
        transferForm.to_store_id = props.store.id;
    }
    transferForm.product_id = props.products[0]?.id || '';
    transferForm.quantity = 1;
    transferForm.reason = '';
    showTransferModal.value = true;
}

function submitTransfer() {
    transferForm.post('/admin/stores/transfer', {
        preserveScroll: true,
        onSuccess: () => {
            showTransferModal.value = false;
            transferForm.reset();
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <FlashMessages />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#40e0d0]/10 text-[#40e0d0]">
                    <i class="bi bi-shop text-3xl"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-[#0D1527]">{{ store.name }}</h1>
                        <span
                            v-if="store.is_default"
                            class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800"
                        >
                            Default Store
                        </span>
                        <span
                            :class="store.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        >
                            {{ store.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Code: <span class="font-mono font-bold">{{ store.code }}</span> • {{ store.address || 'No address specified' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <button
                    v-if="has('inventory.adjust') && allStores.length"
                    type="button"
                    @click="openTransferModal('out')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition-colors"
                >
                    <i class="bi bi-arrow-left-right text-[#40e0d0]"></i>
                    Transfer Stock
                </button>
                <Link
                    v-if="has('stores.edit')"
                    :href="`/admin/stores/${store.code}/edit`"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                >
                    Edit Store
                </Link>
                <Link href="/admin/stores" class="rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90 transition-colors">
                    All Stores
                </Link>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Inventory Value</p>
                <p class="mt-1 text-xl font-bold text-[#0D1527]">{{ naira(inventoryStats.total_value || 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Total Units in Stock</p>
                <p class="mt-1 text-xl font-bold text-[#0D1527]">{{ inventoryStats.total_stock || 0 }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Products Tracked</p>
                <p class="mt-1 text-xl font-bold text-[#0D1527]">{{ inventoryStats.total_products || 0 }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Low Stock Alert</p>
                <p class="mt-1 text-xl font-bold" :class="inventoryStats.low_stock > 0 ? 'text-amber-600' : 'text-slate-900'">
                    {{ inventoryStats.low_stock || 0 }}
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Total Sales Count</p>
                <p class="mt-1 text-xl font-bold text-[#0D1527]">{{ store.sales_count || 0 }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Active Staff</p>
                <p class="mt-1 text-xl font-bold text-[#0D1527]">{{ store.staff_count || 0 }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Store Details -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-[#0D1527] mb-4">Branch Details</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Branch Name</dt>
                        <dd class="font-medium text-slate-900">{{ store.name }}</dd>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Store Code</dt>
                        <dd class="font-mono font-bold text-slate-800">{{ store.code }}</dd>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Default Store</dt>
                        <dd class="font-medium">{{ store.is_default ? 'Yes (Primary)' : 'No' }}</dd>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Physical Address</dt>
                        <dd class="font-medium text-right text-slate-800 max-w-xs">{{ store.address || '—' }}</dd>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="font-medium text-right text-slate-800">{{ store.phone || '—' }}</dd>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium text-right text-slate-800">{{ store.email || '—' }}</dd>
                    </div>
                    <div class="flex justify-between py-1">
                        <dt class="text-slate-500">Registered Date</dt>
                        <dd class="font-medium text-right text-slate-800">{{ formatDate(store.created_at) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-[#0D1527] mb-4">Branch Actions</h2>
                <div class="space-y-3">
                    <Link
                        v-if="has('sales.create')"
                        href="/admin/sales/create"
                        class="flex items-center gap-3 rounded-xl p-3 border border-slate-200 hover:bg-slate-50 transition-colors"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#40e0d0]/10 text-[#40e0d0]">
                            <i class="bi bi-cart-plus text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">New POS Sale</p>
                            <p class="text-xs text-slate-500">Create a walk-in sale or quotation</p>
                        </div>
                    </Link>

                    <button
                        v-if="has('inventory.adjust') && allStores.length"
                        type="button"
                        @click="openTransferModal('out')"
                        class="w-full text-left flex items-center gap-3 rounded-xl p-3 border border-slate-200 hover:bg-slate-50 transition-colors"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                            <i class="bi bi-box-arrow-right text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">Transfer Out to Another Branch</p>
                            <p class="text-xs text-slate-500">Send products from {{ store.name }} to another branch</p>
                        </div>
                    </button>

                    <Link
                        v-if="has('products.view')"
                        href="/admin/stock/movements"
                        class="flex items-center gap-3 rounded-xl p-3 border border-slate-200 hover:bg-slate-50 transition-colors"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600">
                            <i class="bi bi-journal-text text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">View Stock Movement Ledger</p>
                            <p class="text-xs text-slate-500">Inspect inbound, outbound, and transfer movements</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Recent Stock Transfers -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-[#0D1527]">Recent Inter-Store Stock Transfers</h2>
                <button
                    v-if="has('inventory.adjust') && allStores.length"
                    @click="openTransferModal('out')"
                    class="text-xs font-semibold text-[#40e0d0] hover:underline"
                >
                    + New Transfer
                </button>
            </div>

            <div v-if="recentTransfers.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase text-slate-400 border-b border-slate-100">
                            <th class="py-2 px-3">Date</th>
                            <th class="py-2 px-3">Ref</th>
                            <th class="py-2 px-3">Type</th>
                            <th class="py-2 px-3">Product</th>
                            <th class="py-2 px-3">From</th>
                            <th class="py-2 px-3">To</th>
                            <th class="py-2 px-3 text-right">Qty</th>
                            <th class="py-2 px-3">Initiated By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="t in recentTransfers" :key="t.id" class="hover:bg-slate-50">
                            <td class="py-2.5 px-3 text-slate-600">{{ formatDate(t.movement_date || t.created_at) }}</td>
                            <td class="py-2.5 px-3 font-mono text-xs font-semibold text-slate-800">{{ t.reference }}</td>
                            <td class="py-2.5 px-3">
                                <span
                                    :class="t.type === 'transfer_in' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium uppercase"
                                >
                                    {{ t.type === 'transfer_in' ? 'Received' : 'Sent Out' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-medium text-slate-900">{{ t.product?.name || '—' }}</td>
                            <td class="py-2.5 px-3 text-slate-600">{{ t.from_store?.name || (t.from_store_id ? '#' + t.from_store_id : '—') }}</td>
                            <td class="py-2.5 px-3 text-slate-600">{{ t.to_store?.name || (t.to_store_id ? '#' + t.to_store_id : '—') }}</td>
                            <td class="py-2.5 px-3 text-right font-bold text-slate-900">{{ Math.abs(t.quantity_change) }}</td>
                            <td class="py-2.5 px-3 text-slate-500 text-xs">{{ t.user?.name || 'Admin' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-slate-400 text-center py-6 text-sm">No stock transfers recorded yet for this branch.</p>
        </div>

        <!-- Transfer Modal -->
        <div v-if="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-[#0D1527]">Inter-Store Stock Transfer</h3>
                    <button @click="showTransferModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitTransfer" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Product *</label>
                        <select
                            v-model="transferForm.product_id"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#40e0d0] focus:ring-1 focus:ring-[#40e0d0]"
                        >
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                {{ p.name }} ({{ p.sku }})
                            </option>
                        </select>
                        <p v-if="transferForm.errors.product_id" class="text-xs text-rose-600 mt-1">{{ transferForm.errors.product_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">From Store *</label>
                            <select
                                v-model="transferForm.from_store_id"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                            >
                                <option :value="store.id">{{ store.name }} (This Store)</option>
                                <option v-for="s in allStores" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="transferForm.errors.from_store_id" class="text-xs text-rose-600 mt-1">{{ transferForm.errors.from_store_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">To Store *</label>
                            <select
                                v-model="transferForm.to_store_id"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"
                            >
                                <option :value="store.id">{{ store.name }} (This Store)</option>
                                <option v-for="s in allStores" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="transferForm.errors.to_store_id" class="text-xs text-rose-600 mt-1">{{ transferForm.errors.to_store_id }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Quantity *</label>
                        <input
                            v-model.number="transferForm.quantity"
                            type="number"
                            min="1"
                            required
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-[#40e0d0] focus:ring-1 focus:ring-[#40e0d0]"
                        />
                        <p v-if="transferForm.errors.quantity" class="text-xs text-rose-600 mt-1">{{ transferForm.errors.quantity }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Reason / Notes</label>
                        <textarea
                            v-model="transferForm.reason"
                            rows="2"
                            placeholder="e.g. Branch replenishment, customer pickup request"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showTransferModal = false"
                            class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="transferForm.processing"
                            class="rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50"
                        >
                            {{ transferForm.processing ? 'Transferring…' : 'Execute Transfer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>