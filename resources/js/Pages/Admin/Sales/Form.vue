<script setup>
import { computed, ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
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
    customers: { type: Array, default: () => [] },
    tax_rate: { type: Number, default: 0 },
});

const customersList = ref(props.customers.map((c) => ({ ...c })));

const search = ref('');
const items = ref([]);
const discount = ref(0);
const taxRate = ref(props.tax_rate);
const showResults = ref(false);
const selectedResultIndex = ref(-1);
const searchInputRef = ref(null);
const dropdownRef = ref(null);

const form = useForm({
    sale_date: new Date().toISOString().split('T')[0],
    customer_id: '',
    customer_name: 'Walk-in Customer',
    customer_phone: '',
    discount: 0,
    tax_rate: props.tax_rate,
    amount_paid: '',
    payment_method: 'cash',
    remarks: '',
});

/* ---------- Click outside handler for product dropdown ---------- */
function handleClickOutside(e) {
    if (showResults.value) {
        const input = searchInputRef.value;
        const dropdown = dropdownRef.value;
        if (input && !input.contains(e.target) && dropdown && !dropdown.contains(e.target)) {
            showResults.value = false;
            selectedResultIndex.value = -1;
        }
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

/* ---------- Customer Autocomplete ---------- */

const selectedCustomer = computed(() => {
    if (!form.customer_id) return null;
    return customersList.value.find((c) => c.id === Number(form.customer_id)) ?? null;
});

const selectedOutstanding = computed(() => Number(selectedCustomer.value?.outstanding) || 0);
const maxPayable = computed(() => total.value + selectedOutstanding.value);
const overpayApplied = computed(() => Math.max(amountPaid.value - total.value, 0));

function selectCustomer(customer) {
    form.customer_id = customer.id;
    form.customer_name = '';
    form.customer_phone = '';
    customerSearch.value = '';
    showCustomerResults.value = false;
    selectedCustomerResultIndex.value = -1;
    nextTick(() => searchInputRef.value?.focus());
}

// isWalkIn computed - must be defined before watchers that use it
const isWalkIn = computed(() => selectedCustomer.value?.customer_type === 'walk_in' || !form.customer_id);

function clearCustomer() {
    form.customer_id = '';
    form.customer_name = 'Walk-in Customer';
    form.customer_phone = '';
    // Force full payment for walk-in
    form.payment_method = 'cash';
    form.amount_paid = '';
}

// Watch for customer type changes to enforce walk-in rules
watch(() => isWalkIn.value, (val) => {
    if (val) {
        form.payment_method = 'cash';
        form.amount_paid = '';
    }
}, { immediate: true });

function onCustomerChange() {
    if (form.customer_id) {
        const customer = customersList.value.find(c => c.id == form.customer_id);
        if (customer) {
            form.customer_name = customer.name;
            form.customer_phone = customer.phone || '';
        }
    } else {
        clearCustomer();
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
    ).slice(0, 8);
});

// Suggested products shown on focus (before typing) - top products by stock
const suggestedProducts = computed(() => {
    return props.products
        .filter(p => p.current_quantity > 0)
        .sort((a, b) => b.current_quantity - a.current_quantity)
        .slice(0, 8);
});

const cartCount = computed(() => items.value.reduce((n, i) => n + i.quantity, 0));

const itemInCart = (id) => items.value.find((i) => i.product_id === id);

function stockPill(qty) {
    const cls =
        qty <= 0 ? 'bg-red-100 text-red-700' : qty <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800';
    return badgeClass(cls);
}

function fallbackImg(event) {
    if (event.target.dataset.fallbackApplied) return;
    event.target.dataset.fallbackApplied = '1';
    event.target.src = '/images/landing/solar_panels_sky.jpg';
}

function productImageUrl(image) {
    if (!image) return '/images/landing/solar_panels_sky.jpg';
    if (image.startsWith('http') || image.startsWith('/storage/') || image.startsWith('/images/')) {
        return image;
    }
    return `/storage/${image}`;
}

function addItem(product) {
    const existing = itemInCart(product.id);
    if (existing) {
        setQty(items.value.indexOf(existing), existing.quantity + 1);
    } else {
        items.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            unit_price: product.selling_price,
            available: product.current_quantity,
            quantity: 1,
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

function setQty(index, qty) {
    const item = items.value[index];
    if (!item) return;
    const num = Number(qty) || 1;
    item.quantity = Math.max(1, Math.min(num, item.available));
}

function handleQtyInput(index, event) {
    const item = items.value[index];
    if (!item) return;
    const val = event.target.value;
    // Allow empty or partial input while typing
    if (val === '' || val === '-') return;
    const num = Number(val);
    if (!Number.isNaN(num)) {
        item.quantity = Math.max(1, Math.min(num, item.available));
    }
}

function handleQtyBlur(index) {
    // On blur, ensure valid quantity
    const item = items.value[index];
    if (!item) return;
    if (!item.quantity || item.quantity < 1) {
        item.quantity = 1;
    } else if (item.quantity > item.available) {
        item.quantity = item.available;
    }
}

function clearCart() {
    items.value = [];
}

// Check if any cart item exceeds available stock
const hasStockExceeded = computed(() => {
    return items.value.some(item => item.quantity > item.available);
});

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

const subtotal = computed(() => items.value.reduce((sum, i) => sum + i.quantity * Number(i.unit_price || 0), 0));
const taxable = computed(() => Math.max(subtotal.value - Number(discount.value || 0), 0));
const tax = computed(() => (taxable.value * Number(taxRate.value || 0)) / 100);
const total = computed(() => taxable.value + tax.value);

const amountPaid = computed(() => {
    const v = Number(form.amount_paid || 0);
    return Number.isFinite(v) ? v : 0;
});

const balance = computed(() => Math.max(total.value - amountPaid.value, 0));

function setPaid(pct) {
    form.amount_paid = Math.round(total.value * (pct / 100) * 100) / 100;
}

function setPaidFull() {
    form.amount_paid = Math.round(maxPayable.value * 100) / 100;
}

const customerTypeLabel = (type) =>
    ({ walk_in: 'Walk-in', regular: 'Regular', corporate: 'Corporate' }[type] ?? 'Customer');

function initials(name = '') {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');
}

/* ---------- Customer modal (for detailed creation) ---------- */

const showCustomerModal = ref(false);
const customerTab = ref('existing');
const customerQuery = ref('');
const customerSaving = ref(false);
const customerModalError = ref('');
const customerValidationErrors = ref({});
const modalSuccess = ref('');
const customerForm = ref({
    name: '',
    phone: '',
    email: '',
    address: '',
    location: '',
    customer_type: 'walk_in',
});

const modalFilteredCustomers = computed(() => {
    const q = customerQuery.value.trim().toLowerCase();
    if (!q) return customersList.value;
    return customersList.value.filter(
        (c) =>
            c.name.toLowerCase().includes(q) ||
            (c.phone ?? '').toLowerCase().includes(q) ||
            (c.email ?? '').toLowerCase().includes(q),
    );
});

function openCustomerModal(tab = 'existing') {
    customerModalError.value = '';
    customerValidationErrors.value = {};
    customerQuery.value = '';
    if (tab === 'new') {
        customerForm.value = { name: '', phone: '', email: '', address: '', location: '', customer_type: 'regular' };
    }
    customerTab.value = tab;
    showCustomerModal.value = true;
}

function selectExistingCustomer(customer) {
    form.customer_id = customer.id;
    form.customer_name = '';
    form.customer_phone = '';
    customerSearch.value = '';
    showCustomerResults.value = false;
    modalSuccess.value = `"${customer.name}" selected for this sale.`;
    showCustomerModal.value = false;
}

async function createCustomer() {
    customerSaving.value = true;
    customerModalError.value = '';
    customerValidationErrors.value = {};

    try {
        const response = await axios.post('/admin/customers/quick', {
            name: customerForm.value.name,
            phone: customerForm.value.phone,
            email: customerForm.value.email,
            address: customerForm.value.address,
            location: customerForm.value.location,
            customer_type: customerForm.value.customer_type,
        });

        const customer = response.data.customer;
        if (!customer) {
            throw new Error('No customer returned from server');
        }
        customersList.value.push(customer);
        selectCustomer(customer);
        modalSuccess.value = `"${customer.name}" created & selected for this sale.`;
        showCustomerModal.value = false;
    } catch (e) {
        console.error('Create customer error:', e);
        if (e.response?.data?.errors) {
            customerValidationErrors.value = e.response.data.errors;
        } else {
            customerModalError.value = e.response?.data?.message || e.message || 'Could not save customer. Please try again.';
        }
    } finally {
        customerSaving.value = false;
    }
}

/* ---------- Submit ---------- */

const hasFormErrors = computed(() => Object.keys(form.errors).length > 0);

const canSubmit = computed(() => items.value.length > 0 && !form.processing && !hasStockExceeded.value);

function submit() {
    form
        .transform((data) => ({
            ...data,
            discount: Number(discount.value || 0),
            tax_rate: Number(taxRate.value || 0),
            amount_paid: amountPaid.value,
            items: items.value.map((i) => ({
                product_id: i.product_id,
                quantity: i.quantity,
                unit_price: Number(i.unit_price || 0),
            })),
        }))
        .post('/admin/sales');
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="New Sale" subtitle="POS — type to add products, cart builds at bottom." />

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
        <!-- ============ TOP BAR: Customer ============ -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
<!-- Customer Select -->
                <div class="flex-1 min-w-0">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Customer</label>
                    <div class="relative">
                        <select
                            v-model="form.customer_id"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-4 pr-12 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 bg-white appearance-none"
                            style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                            @change="onCustomerChange"
                        >
                            <option value="">
                                <span class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-600">
                                        <i class="bi bi-person-fill" />
                                    </span>
                                    Walk-in Customer
                                </span>
                            </option>
                            <option v-for="c in customersList" :key="c.id" :value="c.id">
                                {{ c.name }} {{ c.phone ? `(${c.phone})` : '' }} {{ c.email ? `· ${c.email}` : '' }}
                            </option>
                        </select>
                        <button
                            type="button"
                            class="absolute right-0 top-0 h-full w-10 flex items-center justify-center rounded-r-xl border-l border-slate-200 text-[#0D1527] hover:bg-[#0D1527] hover:text-white hover:border-[#0D1527] transition-colors"
                            @click="openCustomerModal('new')"
                            title="Create new customer"
                        >
                            <i class="bi bi-plus-lg text-xl" />
                        </button>
                    </div>
                    <div v-if="form.errors.customer_id" class="mt-1 text-xs text-red-600">{{ form.errors.customer_id }}</div>
                </div>

                <!-- Cart Summary Badge -->
                <div v-if="items.length" class="flex items-center gap-3 shrink-0 sm:ml-4">
                    <div class="hidden sm:flex items-center gap-2 rounded-xl bg-[#0D1527]/5 px-3 py-1.5">
                        <span class="text-xs font-medium text-[#0D1527]">{{ items.length }} line{{ items.length === 1 ? '' : 's' }}</span>
                        <span class="text-xs font-bold text-[#0D1527]">{{ cartCount }} item{{ cartCount === 1 ? '' : 's' }}</span>
                        <span class="text-xs text-slate-400">·</span>
                        <span class="text-sm font-bold text-[#0D1527]">{{ naira(total) }}</span>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600 transition"
                        @click="clearCart"
                        title="Clear cart"
                    >
                        <i class="bi bi-trash3 text-lg" />
                    </button>
                </div>
            </div>
        </div>

        <div v-if="form.errors.customer_name || form.errors.customer_id" class="text-xs text-red-600 px-1">
            {{ form.errors.customer_name || form.errors.customer_id }}
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
                <div class="relative" @click="showResults = true">
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
                            ref="dropdownRef"
                            v-if="showResults && (filteredProducts.length || (!search && suggestedProducts.length))"
                            class="absolute z-20 top-full left-0 right-0 mt-1.5 rounded-xl border border-slate-200 bg-white shadow-lg max-h-80 overflow-y-auto"
                        >
                            <!-- Suggested Products (when no search) -->
                            <template v-if="!search && suggestedProducts.length">
                                <div class="px-3 py-2 border-b border-slate-100 flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <i class="bi bi-star-fill text-amber-500" />
                                    Popular Products
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
                                    <img
                                        :src="productImageUrl(p.image)"
                                        :alt="p.name"
                                        class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-slate-100"
                                        loading="lazy"
                                        @error="fallbackImg"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-slate-900">{{ p.name }}</p>
                                        <p class="text-xs text-slate-400">{{ p.sku }} · {{ naira(p.selling_price) }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span :class="stockPill(p.current_quantity)" class="text-[10px]">
                                            {{ p.current_quantity > 0 ? p.current_quantity + ' in stock' : 'Out of stock' }}
                                        </span>
                                        <span v-if="itemInCart(p.id)"
                                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#0D1527] px-1.5 text-[10px] font-bold text-white"
                                        >
                                            {{ itemInCart(p.id).quantity }}
                                        </span>
                                        <i v-else class="bi bi-plus-circle text-[#40e0d0] text-lg" />
                                    </div>
                                </button>
                            </template>

                            <!-- Filtered Products (when searching) -->
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
                                    <img
                                        :src="productImageUrl(p.image)"
                                        :alt="p.name"
                                        class="h-9 w-9 shrink-0 rounded-lg object-cover ring-1 ring-slate-100"
                                        loading="lazy"
                                        @error="fallbackImg"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-slate-900">{{ p.name }}</p>
                                        <p class="text-xs text-slate-400">{{ p.sku }} · {{ naira(p.selling_price) }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span :class="stockPill(p.current_quantity)" class="text-[10px]">
                                            {{ p.current_quantity > 0 ? p.current_quantity + ' in stock' : 'Out of stock' }}
                                        </span>
                                        <span v-if="itemInCart(p.id)"
                                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#0D1527] px-1.5 text-[10px] font-bold text-white"
                                        >
                                            {{ itemInCart(p.id).quantity }}
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
                    <i class="bi bi-barcode text-4xl mb-3 text-slate-200" />
                    <p class="text-sm font-medium text-slate-500">Start typing to find products</p>
                    <p class="text-xs text-slate-400 mt-1">Search by name, SKU, or scan a barcode</p>
                </div>

                <!-- Quick Add when items exist but no search -->
                <div v-if="!search && items.length" class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Quick Add (Popular)</p>
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
            <!-- Cart / Sale Items -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <div class="border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <i class="bi bi-cart3 text-[#40e0d0]" />
                        Sale Items
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
                                <th class="px-3 py-2.5 text-right font-semibold text-slate-600">Unit Price</th>
                                <th class="px-3 py-2.5 text-right font-semibold text-slate-600">Total</th>
                                <th class="py-2.5 pr-4 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, index) in items" :key="item.product_id" class="hover:bg-slate-50/50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ item.name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ item.sku }} · {{ item.available }} available</p>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="inline-flex items-center rounded-lg border" :class="item.quantity > item.available ? 'border-red-400 bg-red-50' : 'border-slate-300 bg-white'">
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
                                            :max="item.available"
                                            class="w-12 border-0 py-1.5 text-center text-sm focus:ring-0"
                                            :class="item.quantity > item.available ? 'text-red-600' : ''"
                                            @input="handleQtyInput(index, $event)"
                                            @blur="handleQtyBlur(index)"
                                            @change="handleQtyBlur(index)"
                                        />
                                        <button
                                            type="button"
                                            class="px-2.5 py-1.5 text-slate-500 hover:text-slate-900 disabled:opacity-30"
                                            :disabled="item.quantity >= item.available"
                                            @click="setQty(index, item.quantity + 1)"
                                        >+</button>
                                    </div>
                                    <p v-if="item.quantity > item.available" class="mt-1 text-[10px] text-red-600 flex items-center gap-1">
                                        <i class="bi bi-exclamation-triangle-fill" /> Only {{ item.available }} available in this store
                                    </p>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-24 rounded-lg border-slate-300 py-1.5 pr-2 text-right text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                    />
                                </td>
                                <td class="px-3 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                                    {{ naira(item.quantity * Number(item.unit_price || 0)) }}
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
                                v-model="form.sale_date"
                                type="date"
                                class="w-full rounded-lg border-slate-300 py-1.5 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600">Method</label>
                            <select
                                v-model="form.payment_method"
                                class="w-full rounded-lg border-slate-300 py-1.5 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            >
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="card">Card</option>
                                <option value="pos">POS</option>
                                <option value="credit" v-if="!isWalkIn">Credit</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-slate-600">Amount Paid</label>
                            <input
                                v-model="form.amount_paid"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-lg border-slate-300 py-1.5 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                placeholder="0.00"
                                :disabled="isWalkIn"
                                :class="{ 'bg-slate-50 cursor-not-allowed': isWalkIn }"
                            />
                            <p v-if="isWalkIn" class="mt-1 text-[10px] text-emerald-600 flex items-center gap-1">
                                <i class="bi bi-info-circle-fill" /> Walk-in customers must pay in full
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <span class="text-[11px] font-medium text-slate-500 self-center">Quick:</span>
                        <button
                            v-if="!isWalkIn"
                            type="button"
                            class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600 hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                            @click="setPaid(0)"
                        >
                            None
                        </button>
                        <button
                            v-if="!isWalkIn"
                            type="button"
                            class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600 hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                            @click="setPaid(50)"
                        >
                            50%
                        </button>
                        <button
                            type="button"
                            class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600 hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                            @click="setPaid(100)"
                        >
                            100%
                        </button>
                        <button v-if="selectedOutstanding > 0" type="button" class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800 hover:border-amber-400 hover:bg-amber-100" @click="setPaidFull">Clear Balance</button>
                    </div>

                    <p v-if="amountPaid > maxPayable" class="text-[11px] text-red-600">Payment exceeds total + outstanding.</p>
                    <p v-else-if="overpayApplied > 0" class="flex items-start gap-1 text-[11px] font-medium text-emerald-600">
                        <i class="bi bi-check-circle-fill mt-0.5 shrink-0" />
                        <span>{{ naira(overpayApplied) }} applied to outstanding balance.</span>
                    </p>

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
                        <div class="flex justify-between">
                            <span class="text-slate-400">Tax ({{ taxRate }}%)</span>
                            <span class="font-semibold text-white">{{ naira(tax) }}</span>
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

                    <div v-if="hasStockExceeded" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill shrink-0" />
                        <span>Some items exceed available stock. Reduce quantities to continue.</span>
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
                        <i :class="form.processing ? 'bi bi-hourglass-split animate-spin' : 'bi bi-bag-check'" />
                        <span>
                            {{ form.processing
                                ? 'Saving…'
                                : hasStockExceeded
                                    ? 'Fix stock issues first'
                                    : `Complete Sale · ${naira(total)}` }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty Cart State -->
        <div v-else class="rounded-2xl border border-slate-200/80 bg-white shadow-xs p-12 text-center">
            <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                <i class="bi bi-cart3 text-2xl text-slate-400" />
            </div>
            <p class="text-base font-medium text-slate-500">Cart is empty</p>
            <p class="text-sm text-slate-400 mt-1">Search and add products above to start the sale</p>
        </div>
    </form>

    <!-- ============ Customer Modal ============ -->
    <Modal :show="showCustomerModal" max-width="lg" @close="showCustomerModal = false">
        <div class="p-6 sm:p-7">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0D1527] text-white">
                        <i class="bi bi-person-vcard text-lg" />
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Customer</h3>
                        <p class="text-xs text-slate-500">Pick existing or add new.</p>
                    </div>
                </div>
                <button type="button" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showCustomerModal = false">
                    <i class="bi bi-x-lg text-sm" />
                </button>
            </div>

            <div class="mb-5 flex rounded-xl bg-slate-100 p-1">
                <button type="button" class="flex-1 rounded-lg py-2 text-sm font-semibold transition" :class="customerTab === 'existing' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'" @click="customerTab = 'existing'">
                    <i class="bi bi-people mr-1.5" /> Existing
                </button>
                <button type="button" class="flex-1 rounded-lg py-2 text-sm font-semibold transition" :class="customerTab === 'new' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'" @click="customerValidationErrors = {}; customerModalError = ''; customerTab = 'new'">
                    <i class="bi bi-person-plus mr-1.5" /> New
                </button>
            </div>

            <div v-if="customerTab === 'existing'">
                <div class="relative mb-3">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400" />
                    <input v-model="customerQuery" type="search" placeholder="Search customers…" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>
                <div v-if="filteredCustomers.length" class="max-h-80 space-y-2 overflow-y-auto pr-1">
                    <button v-for="c in filteredCustomers" :key="c.id" type="button" class="flex w-full items-center gap-3 rounded-xl border border-slate-200 px-3.5 py-3 text-left transition hover:border-[#0D1527] hover:bg-slate-50 hover:shadow-sm" @click="selectExistingCustomer(c)">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0D1527]/5 text-xs font-bold text-[#0D1527]">{{ initials(c.name) }}</span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-slate-800">{{ c.name }}</span><span class="block truncate text-xs text-slate-400">{{ c.phone || c.email || 'No contact' }}</span></span>
                        <span v-if="c.customer_type" class="hidden shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 sm:block">{{ customerTypeLabel(c.customer_type) }}</span>
                        <span v-if="c.has_outstanding" class="shrink-0 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-800">Owes {{ naira(Number(c.outstanding) || 0) }}</span>
                        <i class="bi bi-chevron-right text-sm text-slate-300 shrink-0" />
                    </button>
                </div>
                <div v-else class="rounded-xl border border-dashed border-slate-200 py-10 text-center"><i class="bi bi-search mb-2 block text-2xl text-slate-300" /><p class="text-sm text-slate-400">No customers match.</p></div>
            </div>

            <form v-else @submit.prevent="createCustomer">
                <div v-if="customerModalError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700">{{ customerModalError }}</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label><input v-model="customerForm.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Ada Obi" /><p v-if="customerValidationErrors.name" class="mt-1 text-xs text-red-600">{{ customerValidationErrors.name[0] }}</p></div>
                    <div><label class="mb-1.5 block text-xs font-medium text-slate-600">Phone</label><input v-model="customerForm.phone" type="tel" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="0803 000 0000" /><p v-if="customerValidationErrors.phone" class="mt-1 text-xs text-red-600">{{ customerValidationErrors.phone[0] }}</p></div>
                    <div><label class="mb-1.5 block text-xs font-medium text-slate-600">Email</label><input v-model="customerForm.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="customer@email.com" /><p v-if="customerValidationErrors.email" class="mt-1 text-xs text-red-600">{{ customerValidationErrors.email[0] }}</p></div>
                    <div><label class="mb-1.5 block text-xs font-medium text-slate-600">Type</label><select v-model="customerForm.customer_type" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"><option value="walk_in">Walk-in</option><option value="regular">Regular</option><option value="corporate">Corporate</option></select></div>
                    <div><label class="mb-1.5 block text-xs font-medium text-slate-600">Location</label><input v-model="customerForm.location" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="City / area" /></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-medium text-slate-600">Address</label><textarea v-model="customerForm.address" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Street address" /></div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showCustomerModal = false">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-60" :disabled="customerSaving"><i :class="customerSaving ? 'bi bi-hourglass-split animate-spin' : 'bi bi-check-lg'" />{{ customerSaving ? 'Saving…' : 'Add & Select' }}</button>
                </div>
            </form>
        </div>
    </Modal>
</template>