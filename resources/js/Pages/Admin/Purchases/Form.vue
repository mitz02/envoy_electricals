<script setup>
import { computed, ref, watch, nextTick, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Modal from '@/Components/Modal.vue';
import { naira, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    preselected_product_ids: { type: Array, default: () => [] },
    can_view_cost: { type: Boolean, default: false },
});

const suppliersList = ref(props.suppliers.map((s) => ({ ...s })));

watch(
    () => props.suppliers,
    (value) => {
        const known = new Set(suppliersList.value.map((s) => s.id));
        suppliersList.value = [...value.map((s) => ({ ...s })), ...suppliersList.value.filter((s) => !known.has(s.id))];
    },
);

const search = ref('');
const items = ref([]);
const discount = ref(0);
const showResults = ref(false);
const selectedResultIndex = ref(-1);
const searchInputRef = ref(null);

const form = useForm({
    purchase_date: new Date().toISOString().split('T')[0],
    supplier_id: '',
    supplier_name: '',
    supplier_phone: '',
    invoice_no: '',
    discount: 0,
    amount_paid: '',
    payment_method: 'cash',
    remarks: '',
});

function stockPill(qty) {
    const cls =
        qty <= 0 ? 'bg-red-100 text-red-700' : qty <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800';
    return badgeClass(cls);
}

function clearSupplier() {
    form.supplier_id = '';
    form.supplier_name = '';
    form.supplier_phone = '';
}

function onSupplierChange() {
    if (form.supplier_id) {
        const supplier = suppliersList.value.find(s => s.id == form.supplier_id);
        if (supplier) {
            form.supplier_name = supplier.name;
            form.supplier_phone = supplier.phone || '';
        }
    } else {
        clearSupplier();
    }
}

/* ---------- Product search (POS style) ---------- */

const filteredProducts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return [];
    return props.products.filter(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            p.sku.toLowerCase().includes(q) ||
            p.id.toString() === q,
    ).slice(0, 10);
});

// Suggested products shown on focus (before typing) - top products by stock
const suggestedProducts = computed(() => {
    return props.products
        .filter(p => p.current_quantity > 0)
        .sort((a, b) => b.current_quantity - a.current_quantity)
        .slice(0, 10);
});

const cartCount = computed(() => items.value.reduce((n, i) => n + i.quantity, 0));

function addItem(product) {
    const existing = items.value.find((i) => i.product_id === product.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        items.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            unit_cost: product.cost_price || 0,
            quantity: 1,
            available: product.current_quantity,
        });
    }
    search.value = '';
    showResults.value = false;
    selectedResultIndex.value = -1;
    nextTick(() => searchInputRef.value?.focus());
}

function removeItem(index) {
    items.value.splice(index, 1);
}

// Products carried over from a bulk selection on the products list.
onMounted(() => {
    if (!props.preselected_product_ids.length) {
        return;
    }

    props.preselected_product_ids.forEach((id) => {
        const product = props.products.find((p) => p.id === Number(id));

        if (product) {
            items.value.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku,
                unit_cost: product.cost_price || 0,
                quantity: 1,
                available: product.current_quantity,
            });
        }
    });
});

function setQty(index, qty) {
    const item = items.value[index];
    if (!item) return;
    const num = Number(qty) || 1;
    item.quantity = Math.max(1, num);
}

function handleQtyInput(index, event) {
    const item = items.value[index];
    if (!item) return;
    const val = event.target.value;
    if (val === '' || val === '-') return;
    const num = Number(val);
    if (!Number.isNaN(num)) {
        item.quantity = Math.max(1, num);
    }
}

function handleQtyBlur(index) {
    const item = items.value[index];
    if (!item) return;
    if (!item.quantity || item.quantity < 1) {
        item.quantity = 1;
    }
}

function clearCart() {
    items.value = [];
}

function handleSearchKeydown(e) {
    const results = search.value.trim() ? filteredProducts.value : suggestedProducts.value;
    if (!results.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedResultIndex.value = Math.min(selectedResultIndex.value + 1, results.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedResultIndex.value = Math.max(selectedResultIndex.value - 1, 0);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (selectedResultIndex.value >= 0 && results[selectedResultIndex.value]) {
            addItem(results[selectedResultIndex.value]);
        }
    } else if (e.key === 'Escape') {
        showResults.value = false;
        selectedResultIndex.value = -1;
    }
}

watch(search, (val) => {
    // Show results when typing OR when empty (to show suggestions on focus)
    showResults.value = val.trim().length > 0 || val === '';
    selectedResultIndex.value = -1;
});

/* ---------- Totals ---------- */

const subtotal = computed(() => items.value.reduce((sum, i) => sum + i.quantity * Number(i.unit_cost || 0), 0));
const total = computed(() => Math.max(subtotal.value - Number(discount.value || 0), 0));

const amountPaid = computed(() => {
    const v = Number(form.amount_paid || 0);
    return Number.isFinite(v) ? v : 0;
});

const balance = computed(() => Math.max(total.value - amountPaid.value, 0));

function setPaid(pct) {
    form.amount_paid = Math.round(total.value * (pct / 100) * 100) / 100;
}

function setPaidFull() {
    form.amount_paid = Math.round(total.value * 100) / 100;
}

/* ---------- Supplier modal (for detailed creation) ---------- */

const showSupplierModal = ref(false);
const supplierSaving = ref(false);
const supplierModalError = ref('');
const supplierValidationErrors = ref({});
const modalSuccess = ref('');
const supplierForm = ref({
    name: '',
    phone: '',
    email: '',
    address: '',
    contact_person: '',
});

function openSupplierModal() {
    supplierModalError.value = '';
    supplierValidationErrors.value = {};
    supplierForm.value = { name: '', phone: '', email: '', address: '', contact_person: '' };
    showSupplierModal.value = true;
}

async function createSupplier() {
    supplierSaving.value = true;
    supplierModalError.value = '';
    supplierValidationErrors.value = {};

    try {
        const response = await axios.post('/admin/suppliers/quick', {
            name: supplierForm.value.name,
            phone: supplierForm.value.phone,
            email: supplierForm.value.email,
            address: supplierForm.value.address,
            contact_person: supplierForm.value.contact_person,
        });

        const supplier = response.data.supplier;
        if (!supplier) {
            throw new Error('No supplier returned from server');
        }
        suppliersList.value.push(supplier);
        form.supplier_id = supplier.id;
        form.supplier_name = supplier.name;
        form.supplier_phone = supplier.phone || '';
        modalSuccess.value = `"${supplier.name}" created & selected for this purchase.`;
        showSupplierModal.value = false;
    } catch (e) {
        console.error('Create supplier error:', e);
        if (e.response?.data?.errors) {
            supplierValidationErrors.value = e.response.data.errors;
        } else {
            supplierModalError.value = e.response?.data?.message || e.message || 'Could not save supplier. Please try again.';
        }
    } finally {
        supplierSaving.value = false;
    }
}

/* ---------- Submit ---------- */

const hasFormErrors = computed(() => Object.keys(form.errors).length > 0);

const canSubmit = computed(() => items.value.length > 0 && !form.processing);

function submit() {
    form
        .transform((data) => ({
            ...data,
            discount: Number(discount.value || 0),
            amount_paid: amountPaid.value,
            items: items.value.map((i) => ({
                product_id: i.product_id,
                quantity: i.quantity,
                unit_cost: Number(i.unit_cost || 0),
            })),
        }))
        .post('/admin/purchases');
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="New Purchase" subtitle="POS — type to add products, cart builds at bottom." />

    <!-- Global server errors -->
    <div
        v-if="hasFormErrors"
        class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        role="alert"
    >
        <p class="font-semibold">Please fix the errors below to continue.</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
            <li v-for="(msg, key) in form.errors" :key="key">{{ msg }}</li>
        </ul>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
        <!-- ============ TOP BAR: Supplier ============ -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <!-- Supplier Select -->
                <div class="flex-1 min-w-0">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Supplier</label>
                    <div class="relative">
                        <select
                            v-model="form.supplier_id"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-4 pr-12 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 bg-white appearance-none"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            @change="onSupplierChange"
                        >
                            <option value="">
                                <span class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-600">
                                        <i class="bi bi-building" />
                                    </span>
                                    No supplier selected
                                </span>
                            </option>
                            <option v-for="s in suppliersList" :key="s.id" :value="s.id">
                                {{ s.name }} {{ s.phone ? `(${s.phone})` : '' }}
                            </option>
                        </select>
                        <button
                            type="button"
                            class="absolute right-0 top-0 h-full w-10 flex items-center justify-center rounded-r-xl border-l border-slate-200 text-[#0D1527] hover:bg-[#0D1527] hover:text-white hover:border-[#0D1527] transition-colors"
                            @click="openSupplierModal"
                            title="Create new supplier"
                        >
                            <i class="bi bi-plus-lg text-xl" />
                        </button>
                    </div>
                    <div v-if="form.errors.supplier_id" class="mt-1 text-xs text-red-600">{{ form.errors.supplier_id }}</div>
                </div>
            </div>
        </div>

        <div v-if="form.errors.supplier_id || form.errors.supplier_name" class="text-xs text-red-600 px-1">
            {{ form.errors.supplier_id || form.errors.supplier_name }}
        </div>

        <!-- ============ MIDDLE: Product Search (Full Width) ============ -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <i class="bi bi-search text-[#40e0d0]" />
                    Add Products
                </h2>
            </div>

            <div class="p-4">
                <!-- Search Input with Dropdown Results -->
                <div class="relative" @click.outside="showResults = false">
                    <div class="relative">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400" />
                        <input
                            ref="searchInputRef"
                            v-model="search"
                            type="search"
                            placeholder="Type product name, SKU, or scan barcode…"
                            class="w-full rounded-xl border-slate-300 py-3 pl-10 pr-10 text-base focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20"
                            @keydown="handleSearchKeydown"
                            @focus="showResults = true"
                            @click.stop
                            autocomplete="off"
                        />
                        <button
                            v-if="search"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700"
                            @click="search = ''"
                        >
                            <i class="bi bi-x-lg text-xs" />
                        </button>
                    </div>

                    <!-- Dropdown Results -->
                    <transition enter-active-class="transition duration-100" leave-active-class="transition duration-75">
                        <div
                            v-if="showResults && (filteredProducts.length || (!search && suggestedProducts.length))"
                            class="absolute z-20 top-full left-0 right-0 mt-1.5 rounded-xl border border-slate-200 bg-white shadow-lg max-h-80 overflow-y-auto"
                        >
                            <!-- Suggested products when no search -->
                            <template v-if="!search && suggestedProducts.length">
                                <div class="px-3 py-2 border-b border-slate-100 flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <i class="bi bi-star-fill text-amber-500" />
                                    Suggested (top stock)
                                </div>
                                <button
                                    v-for="(p, idx) in suggestedProducts"
                                    :key="p.id"
                                    type="button"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 text-left transition hover:bg-slate-50"
                                    :class="{
                                        'bg-[#0D1527]/5': idx === selectedResultIndex,
                                        'opacity-50 cursor-not-allowed': p.current_quantity <= 0,
                                    }"
                                    :disabled="p.current_quantity <= 0"
                                    @click="addItem(p)"
                                    @mouseenter="selectedResultIndex = idx"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-slate-900">{{ p.name }}</p>
                                        <p class="text-xs text-slate-400">{{ p.sku }} · {{ naira(p.cost_price) }} · {{ p.current_quantity }} in stock</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span :class="stockPill(p.current_quantity)" class="text-[10px]">
                                            {{ p.current_quantity > 0 ? p.current_quantity + ' in stock' : 'Out of stock' }}
                                        </span>
                                        <span v-if="items.some(i => i.product_id === p.id)"
                                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#0D1527] px-1.5 text-[10px] font-bold text-white"
                                        >
                                            {{ items.find(i => i.product_id === p.id).quantity }}
                                        </span>
                                        <i v-else class="bi bi-plus-circle text-[#40e0d0] text-lg" />
                                    </div>
                                </button>
                            </template>

                            <!-- Filtered products when searching -->
                            <template v-else>
                                <button
                                    v-for="(p, idx) in filteredProducts"
                                    :key="p.id"
                                    type="button"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 text-left transition hover:bg-slate-50"
                                    :class="{
                                        'bg-[#0D1527]/5': idx === selectedResultIndex,
                                        'opacity-50 cursor-not-allowed': p.current_quantity <= 0,
                                    }"
                                    :disabled="p.current_quantity <= 0"
                                    @click="addItem(p)"
                                    @mouseenter="selectedResultIndex = idx"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-slate-900">{{ p.name }}</p>
                                        <p class="text-xs text-slate-400">{{ p.sku }} · {{ naira(p.cost_price) }} · {{ p.current_quantity }} in stock</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span :class="stockPill(p.current_quantity)" class="text-[10px]">
                                            {{ p.current_quantity > 0 ? p.current_quantity + ' in stock' : 'Out of stock' }}
                                        </span>
                                        <span v-if="items.some(i => i.product_id === p.id)"
                                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#0D1527] px-1.5 text-[10px] font-bold text-white"
                                        >
                                            {{ items.find(i => i.product_id === p.id).quantity }}
                                        </span>
                                        <i v-else class="bi bi-plus-circle text-[#40e0d0] text-lg" />
                                    </div>
                                </button>

                                <div v-if="search && !filteredProducts.length" class="px-3 py-4 text-center text-sm text-slate-400">
                                    No products match "<span class="font-medium">{{ search }}</span>"
                                </div>
                            </template>
                        </div>
                    </transition>
                </div>

                <!-- Empty State / Hint -->
                <div v-if="!search && !items.length" class="mt-8 flex flex-col items-center justify-center text-center text-slate-400 py-12">
                    <i class="bi bi-box-arrow-in-down text-4xl mb-3 text-slate-200" />
                    <p class="text-sm font-medium text-slate-500">Start typing to find products</p>
                    <p class="text-xs text-slate-400 mt-1">Search by name, SKU, or scan a barcode</p>
                </div>

                <!-- Quick Add when items exist but no search -->
                <div v-if="!search && items.length" class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Quick Add (Recent)</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="p in props.products.slice(0, 8)"
                            :key="p.id"
                            type="button"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:border-[#0D1527] hover:bg-[#0D1527]/5 hover:text-[#0D1527] transition disabled:opacity-40"
                            :disabled="p.current_quantity <= 0"
                            @click="addItem(p)"
                        >
                            {{ p.name }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ BOTTOM: Cart + Payment (Stacked) ============ -->
        <div v-if="items.length" class="space-y-5">
            <!-- Cart / Purchase Items -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <div class="border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <i class="bi bi-cart3 text-[#40e0d0]" />
                        Purchase Items
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                            {{ items.length }} line{{ items.length === 1 ? '' : 's' }} · {{ cartCount }} item{{ cartCount === 1 ? '' : 's' }}
                        </span>
                    </h2>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 transition"
                        @click="clearCart"
                        title="Clear all"
                    >
                        <i class="bi bi-trash3 text-base" />
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-slate-600">Product</th>
                                <th class="px-3 py-2.5 text-center font-semibold text-slate-600">Qty</th>
                                <th class="px-3 py-2.5 text-right font-semibold text-slate-600">Unit Cost</th>
                                <th class="px-3 py-2.5 text-right font-semibold text-slate-600">Total</th>
                                <th class="py-2.5 pr-4 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, index) in items" :key="item.product_id" class="hover:bg-slate-50/50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ item.name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ item.sku }}</p>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="inline-flex items-center rounded-lg border border-slate-300 bg-white">
                                        <button
                                            type="button"
                                            class="px-2.5 py-1.5 text-slate-500 hover:text-slate-900 disabled:opacity-30"
                                            :disabled="item.quantity <= 1"
                                            @click="setQty(index, item.quantity - 1)"
                                        >−</button>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            class="w-12 border-0 py-1.5 text-center text-sm focus:ring-0"
                                            @input="handleQtyInput(index, $event)"
                                            @blur="handleQtyBlur(index)"
                                            @change="handleQtyBlur(index)"
                                        />
                                        <button
                                            type="button"
                                            class="px-2.5 py-1.5 text-slate-500 hover:text-slate-900 disabled:opacity-30"
                                            @click="setQty(index, item.quantity + 1)"
                                        >+</button>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <input
                                        v-model.number="item.unit_cost"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-24 rounded-lg border-slate-300 py-1.5 pr-2 text-right text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                    />
                                </td>
                                <td class="px-3 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                                    {{ naira(item.quantity * Number(item.unit_cost || 0)) }}
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <button
                                        type="button"
                                        class="text-slate-400 hover:text-red-600 transition"
                                        @click="removeItem(index)"
                                        title="Remove"
                                    >
                                        <i class="bi bi-trash3" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="form.errors.items" class="border-t border-slate-100 px-4 py-2 text-xs text-red-600">
                    {{ form.errors.items }}
                </div>
            </div>

            <!-- Payment Section -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <i class="bi bi-credit-card text-[#40e0d0]" />
                        Payment
                    </h2>
                </div>
                <div class="p-4 space-y-4">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600">Date</label>
                            <input
                                v-model="form.purchase_date"
                                type="date"
                                class="w-full rounded-lg border-slate-300 py-1.5 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600">Supplier Invoice No</label>
                            <input
                                v-model="form.invoice_no"
                                type="text"
                                class="w-full rounded-lg border-slate-300 py-1.5 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                placeholder="Optional"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600">Method</label>
                            <select
                                v-model="form.payment_method"
                                class="w-full rounded-lg border-slate-300 py-1.5 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            >
                                <option value="cash">Cash</option>
                                <option value="transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="pos">POS</option>
                                <option value="credit">Credit (on balance)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <span class="text-[11px] font-medium text-slate-500 self-center">Quick:</span>
                        <button type="button" class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600 hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white" @click="setPaid(0)">None</button>
                        <button type="button" class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600 hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white" @click="setPaid(50)">50%</button>
                        <button type="button" class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600 hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white" @click="setPaid(100)">100%</button>
                    </div>

                    <p v-if="amountPaid > total" class="text-[11px] text-red-600">Payment exceeds total.</p>

                    <!-- Totals Card -->
                    <div class="rounded-xl bg-[#0D1527] p-4 text-sm text-slate-200 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Subtotal</span>
                            <span class="font-semibold text-white">{{ naira(subtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-400 flex-1">Discount</span>
                            <input
                                v-model.number="discount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-24 rounded-lg border-0 bg-white/10 py-1 pr-2 text-right text-sm font-semibold text-white placeholder:text-slate-500 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
                                placeholder="0"
                            />
                        </div>
                        <div class="pt-2 border-t border-white/15 flex justify-between text-base font-bold text-white">
                            <span>Total</span>
                            <span class="text-amber-400">{{ naira(total) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-white/10 px-3 py-2">
                            <span class="text-[11px] font-medium text-slate-400">Balance Due</span>
                            <span :class="balance > 0 ? 'font-bold text-amber-400' : 'font-bold text-emerald-400'">
                                {{ naira(balance) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Remarks</label>
                        <textarea
                            v-model="form.remarks"
                            rows="2"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            placeholder="Optional note…"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canSubmit"
                    >
                        <i :class="form.processing ? 'bi bi-hourglass-split animate-spin' : 'bi bi-box-seam'" />
                        <span>{{ form.processing ? 'Saving…' : `Record Purchase · ${naira(total)}` }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty Cart State -->
        <div v-else class="rounded-2xl border border-slate-200/80 bg-white shadow-xs p-12 text-center">
            <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                <i class="bi bi-box-seam text-2xl text-slate-400" />
            </div>
            <p class="text-base font-medium text-slate-500">Cart is empty</p>
            <p class="text-sm text-slate-400 mt-1">Search and add products above to start the purchase</p>
        </div>
    </form>

    <!-- ============ Supplier Modal ============ -->
    <Modal :show="showSupplierModal" max-width="lg" @close="showSupplierModal = false">
        <div class="p-6 sm:p-7">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0D1527] text-white">
                        <i class="bi bi-building text-lg" />
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Supplier</h3>
                        <p class="text-xs text-slate-500">Create new supplier with details.</p>
                    </div>
                </div>
                <button type="button" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showSupplierModal = false">
                    <i class="bi bi-x-lg text-sm" />
                </button>
            </div>

            <form @submit.prevent="createSupplier">
                <div v-if="supplierModalError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700">{{ supplierModalError }}</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label><input v-model="supplierForm.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Solar Tech Ltd" /><p v-if="supplierValidationErrors.name" class="mt-1 text-xs text-red-600">{{ supplierValidationErrors.name[0] }}</p></div>
                    <div><label class="mb-1.5 block text-xs font-medium text-slate-600">Phone</label><input v-model="supplierForm.phone" type="tel" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="0803 000 0000" /><p v-if="supplierValidationErrors.phone" class="mt-1 text-xs text-red-600">{{ supplierValidationErrors.phone[0] }}</p></div>
                    <div><label class="mb-1.5 block text-xs font-medium text-slate-600">Email</label><input v-model="supplierForm.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="supplier@email.com" /><p v-if="supplierValidationErrors.email" class="mt-1 text-xs text-red-600">{{ supplierValidationErrors.email[0] }}</p></div>
                    <div><label class="mb-1.5 block text-xs font-medium text-slate-600">Contact Person</label><input v-model="supplierForm.contact_person" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Contact name" /></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-medium text-slate-600">Address</label><textarea v-model="supplierForm.address" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Street address" /></div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showSupplierModal = false">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-60" :disabled="supplierSaving"><i :class="supplierSaving ? 'bi bi-hourglass-split animate-spin' : 'bi bi-check-lg'" />{{ supplierSaving ? 'Saving…' : 'Add & Select' }}</button>
                </div>
            </form>
        </div>
    </Modal>
</template>