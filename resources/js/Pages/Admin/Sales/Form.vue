<script setup>
import { computed, ref } from 'vue';
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

const form = useForm({
    sale_date: new Date().toISOString().split('T')[0],
    customer_id: '',
    customer_name: '',
    customer_phone: '',
    discount: 0,
    tax_rate: props.tax_rate,
    amount_paid: '',
    payment_method: 'cash',
    remarks: '',
});

/* ---------- Product picker ---------- */

const filteredProducts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
        return props.products.slice(0, 12);
    }
    return props.products.filter(
        (p) => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || p.id.toString() === q,
    );
});

const cartCount = computed(() => items.value.reduce((n, i) => n + i.quantity, 0));

const itemInCart = (id) => items.value.find((i) => i.product_id === id);

function stockPill(qty) {
    const cls =
        qty <= 0 ? 'bg-red-100 text-red-700' : qty <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800';
    return badgeClass(cls);
}

function fallbackImg(event) {
    if (event.target.dataset.fallbackApplied) {
        return;
    }
    event.target.dataset.fallbackApplied = '1';
    event.target.src = '/images/landing/solar_panels_sky.jpg';
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
}

function removeItem(index) {
    items.value.splice(index, 1);
}

function setQty(index, qty) {
    const item = items.value[index];
    if (!item) {
        return;
    }
    item.quantity = Math.max(1, Math.min(Number(qty) || 1, item.available));
}

function clearCart() {
    items.value = [];
}

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

/* ---------- Customer ---------- */

const selectedCustomer = computed(
    () => customersList.value.find((c) => c.id === Number(form.customer_id)) ?? null,
);

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

/* ---------- Customer modal ---------- */

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

const filteredCustomers = computed(() => {
    const q = customerQuery.value.trim().toLowerCase();
    if (!q) {
        return customersList.value;
    }
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
        customerForm.value = { name: '', phone: '', email: '', address: '', location: '', customer_type: 'walk_in' };
    }
    customerTab.value = tab;
    showCustomerModal.value = true;
}

function selectExistingCustomer(customer) {
    form.customer_id = customer.id;
    form.customer_name = '';
    form.customer_phone = '';
    modalSuccess.value = `"${customer.name}" selected for this sale.`;
    showCustomerModal.value = false;
}

async function createCustomer() {
    customerSaving.value = true;
    customerModalError.value = '';
    customerValidationErrors.value = {};

    try {
        const { data } = await axios.post('/admin/customers/quick', {
            name: customerForm.value.name,
            phone: customerForm.value.phone,
            email: customerForm.value.email,
            address: customerForm.value.address,
            location: customerForm.value.location,
            customer_type: customerForm.value.customer_type,
        });

        const customer = data.customer;
        customersList.value.push(customer);
        form.customer_id = customer.id;
        form.customer_name = '';
        form.customer_phone = '';
        modalSuccess.value = `"${customer.name}" created & selected for this sale.`;
        showCustomerModal.value = false;
    } catch (e) {
        if (e.response?.data?.errors) {
            customerValidationErrors.value = e.response.data.errors;
        } else {
            customerModalError.value =
                e.response?.data?.message || 'Could not save customer. Please try again.';
        }
    } finally {
        customerSaving.value = false;
    }
}

/* ---------- Submit ---------- */

const hasFormErrors = computed(() => Object.keys(form.errors).length > 0);

const canSubmit = computed(
    () => items.value.length > 0 && !!(form.customer_id || form.customer_name) && !form.processing,
);

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
    <PageHeader title="New Sale" subtitle="Add products, set quantities, and record payment." />

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

    <form class="space-y-6" @submit.prevent="submit">
        <!-- ============ 1 · Product picker ============ -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="flex items-center gap-2.5 text-sm font-semibold text-slate-900">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#0D1527] text-[11px] font-bold text-white">1</span>
                    Add Products
                </h2>
                <span v-if="cartCount" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                    {{ cartCount }} item{{ cartCount === 1 ? '' : 's' }} in sale
                </span>
            </div>

            <div class="p-5">
                <div class="relative mb-4">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search product name or SKU…"
                        class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-10 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                    <button
                        v-if="search"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700"
                        @click="search = ''"
                    >
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>

                <div v-if="filteredProducts.length">
                    <!-- Mobile: card grid -->
                    <div class="grid max-h-96 gap-2.5 overflow-y-auto pr-1 sm:grid-cols-2 lg:hidden">
                        <button
                            v-for="p in filteredProducts"
                            :key="'card-' + p.id"
                            type="button"
                            class="group relative flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-left transition hover:border-[#0D1527] hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:border-slate-200 disabled:hover:shadow-none"
                            :disabled="p.current_quantity <= 0"
                            @click="addItem(p)"
                        >
                            <span
                                v-if="itemInCart(p.id)"
                                class="absolute -top-2 -right-2 flex h-6 min-w-6 items-center justify-center rounded-full bg-[#0D1527] px-1.5 text-sm font-bold text-white shadow-sm"
                            >
                                {{ itemInCart(p.id).quantity }}
                            </span>
                            <img
                                :src="p.image"
                                :alt="p.name"
                                class="h-11 w-11 shrink-0 rounded-lg object-cover ring-1 ring-slate-100"
                                loading="lazy"
                                @error="fallbackImg"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-800 group-hover:text-slate-900">{{ p.name }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ p.sku }}</p>
                            </div>
                            <div class="ml-1 shrink-0 text-right">
                                <p class="text-sm font-bold text-slate-900">{{ naira(p.selling_price) }}</p>
                                <span :class="stockPill(p.current_quantity)">
                                    {{ p.current_quantity > 0 ? `${p.current_quantity} in stock` : 'Out of stock' }}
                                </span>
                            </div>
                        </button>
                    </div>

                    <!-- Desktop: table -->
                    <div class="hidden max-h-96 overflow-y-auto pr-1 lg:block">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-2.5 text-left font-semibold text-slate-600">Product</th>
                                    <th class="px-3 py-2.5 text-right font-semibold text-slate-600">Price</th>
                                    <th class="px-3 py-2.5 text-center font-semibold text-slate-600">Stock</th>
                                    <th class="py-2.5 pl-3 pr-5 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="p in filteredProducts"
                                    :key="'row-' + p.id"
                                    class="cursor-pointer transition hover:bg-slate-50"
                                    :class="{ 'cursor-not-allowed opacity-50 hover:bg-transparent': p.current_quantity <= 0 }"
                                    @click="p.current_quantity > 0 && addItem(p)"
                                >
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <img
                                                :src="p.image"
                                                :alt="p.name"
                                                class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-slate-100"
                                                loading="lazy"
                                                @error="fallbackImg"
                                            />
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate font-medium text-slate-800 group-hover:text-slate-900">{{ p.name }}</p>
                                                    <span
                                                        v-if="itemInCart(p.id)"
                                                        class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#0D1527] px-1.5 text-[11px] font-bold text-white"
                                                    >
                                                        {{ itemInCart(p.id).quantity }}
                                                    </span>
                                                </div>
                                                <p class="text-xs text-slate-400">{{ p.sku }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold text-slate-900">
                                        {{ naira(p.selling_price) }}
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span :class="stockPill(p.current_quantity)">
                                            {{ p.current_quantity > 0 ? `${p.current_quantity} in stock` : 'Out of stock' }}
                                        </span>
                                    </td>
                                    <td class="py-3 pl-3 pr-5 text-right">
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500"
                                            :class="{
                                                'cursor-not-allowed': p.current_quantity <= 0,
                                                'group-hover:border-[#0D1527] group-hover:bg-[#0D1527] group-hover:text-white': p.current_quantity > 0,
                                            }"
                                        >
                                            <i class="bi bi-plus-lg text-sm"></i>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="rounded-xl border border-dashed border-slate-200 py-10 text-center">
                    <i class="bi bi-box-seam mb-2 block text-2xl text-slate-300"></i>
                    <p class="text-sm text-slate-400">No products match your search.</p>
                </div>
            </div>
        </div>

        <!-- ============ 2 · Sale items ============ -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="flex items-center gap-2.5 text-sm font-semibold text-slate-900">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#0D1527] text-[11px] font-bold text-white">2</span>
                    Sale Items
                </h2>
                <div v-if="items.length" class="flex items-center gap-3 text-xs">
                    <span class="text-slate-400">{{ items.length }} line{{ items.length === 1 ? '' : 's' }}</span>
                    <button type="button" class="font-medium text-red-500 hover:text-red-700" @click="clearCart">
                        Clear all
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-2.5 text-left font-semibold text-slate-600">Product</th>
                            <th class="px-3 py-2.5 text-center font-semibold text-slate-600">Qty</th>
                            <th class="px-3 py-2.5 text-right font-semibold text-slate-600">Unit Price</th>
                            <th class="px-3 py-2.5 text-right font-semibold text-slate-600">Total</th>
                            <th class="py-2.5 pl-3 pr-5 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, index) in items" :key="item.product_id">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ item.name }}</p>
                                <p class="text-xs text-slate-400">{{ item.sku }} · {{ item.available }} available</p>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="inline-flex items-center rounded-lg border border-slate-300 bg-white">
                                    <button
                                        type="button"
                                        class="px-2.5 py-1.5 text-slate-500 hover:text-slate-900 disabled:opacity-30"
                                        :disabled="item.quantity <= 1"
                                        @click="setQty(index, item.quantity - 1)"
                                    >
                                        −
                                    </button>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="1"
                                        :max="item.available"
                                        class="w-12 border-0 py-1.5 text-center text-sm focus:ring-0"
                                        @change="setQty(index, item.quantity)"
                                    />
                                    <button
                                        type="button"
                                        class="px-2.5 py-1.5 text-slate-500 hover:text-slate-900 disabled:opacity-30"
                                        :disabled="item.quantity >= item.available"
                                        @click="setQty(index, item.quantity + 1)"
                                    >
                                        +
                                    </button>
                                </div>
                                <p v-if="item.quantity > item.available" class="mt-1 text-xs text-red-600">
                                    Only {{ item.available }} available
                                </p>
                            </td>
                            <td class="px-3 py-3">
                                <div class="relative ml-auto w-28">
                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">₦</span>
                                    <input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded-lg border-slate-300 py-1.5 pl-7 pr-2 text-right text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                    />
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right font-semibold text-slate-900">
                                {{ naira(item.quantity * Number(item.unit_price || 0)) }}
                            </td>
                            <td class="py-3 pl-3 pr-5 text-right">
                                <button
                                    type="button"
                                    class="text-slate-400 transition hover:text-red-600"
                                    title="Remove item"
                                    @click="removeItem(index)"
                                >
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!items.length">
                            <td colspan="5" class="px-5 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                    <i class="bi bi-cart3 text-xl text-slate-400"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-500">Your sale is empty</p>
                                <p class="mt-0.5 text-xs text-slate-400">Tap a product above to add it to this sale.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="form.errors.items" class="border-t border-slate-100 px-5 py-2.5 text-xs text-red-600">
                {{ form.errors.items }}
            </p>
        </div>

        <!-- ============ 3 · Customer + Payment ============ -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Customer -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs lg:col-span-1">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="flex items-center gap-2.5 text-sm font-semibold text-slate-900">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#0D1527] text-[11px] font-bold text-white">3</span>
                        Customer
                    </h2>
                </div>
                <div class="p-5">
                    <div
                        v-if="selectedCustomer"
                        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3"
                    >
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0D1527] text-xs font-bold text-white"
                        >
                            {{ initials(selectedCustomer.name) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-900">{{ selectedCustomer.name }}</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ selectedCustomer.phone || selectedCustomer.email || 'No phone on file' }}
                                <span v-if="selectedCustomer.customer_type" class="text-slate-400">· {{ customerTypeLabel(selectedCustomer.customer_type) }}</span>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 text-xs font-semibold text-slate-500 underline-offset-2 hover:text-slate-800 hover:underline"
                            @click="openCustomerModal('existing')"
                        >
                            Change
                        </button>
                    </div>

                    <div v-else class="space-y-3">
                        <button
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0D1527] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90"
                            @click="openCustomerModal('existing')"
                        >
                            <i class="bi bi-person-vcard"></i>
                            Select Customer
                        </button>
                        <button
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-[#0D1527]/30 px-4 py-3 text-sm font-semibold text-[#0D1527] transition hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                            @click="openCustomerModal('new')"
                        >
                            <i class="bi bi-person-plus"></i>
                            Add New Customer
                        </button>
                    </div>

                    <div
                        v-if="selectedCustomer && selectedCustomer.has_outstanding"
                        class="mt-3 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-xs font-medium leading-relaxed text-red-700"
                        role="alert"
                    >
                        <i class="bi bi-exclamation-triangle-fill mt-0.5 shrink-0 text-red-500"></i>
                        <span>
                            This customer still owes
                            <strong>{{ naira(Number(selectedCustomer.outstanding) || 0) }}</strong>
                            from previous unpaid invoices. Consider collecting it along with this sale.
                        </span>
                    </div>

                    <div v-if="form.errors.customer_name || form.errors.customer_id" class="mt-3 text-xs text-red-600">
                        {{ form.errors.customer_name || form.errors.customer_id }}
                    </div>

                    <div
                        v-if="modalSuccess"
                        class="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-xs font-medium text-amber-800"
                    >
                        <i class="bi bi-check-circle mt-0.5"></i>
                        <span>{{ modalSuccess }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs lg:col-span-2">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="flex items-center gap-2.5 text-sm font-semibold text-slate-900">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#0D1527] text-[11px] font-bold text-white">4</span>
                        Payment
                    </h2>
                </div>
                <div class="space-y-5 p-5 sm:p-6">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Sale date</label>
                            <input
                                v-model="form.sale_date"
                                type="date"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Payment method</label>
                            <select
                                v-model="form.payment_method"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            >
                                <option value="cash">Cash</option>
                                <option value="transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="pos">POS</option>
                                <option value="credit">Credit (on balance)</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Amount paid</label>
                            <input
                                v-model="form.amount_paid"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-medium text-slate-500">Quick pay:</span>
                        <button
                            type="button"
                            class="rounded-full border border-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                            @click="setPaid(0)"
                        >
                            None
                        </button>
                        <button
                            type="button"
                            class="rounded-full border border-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                            @click="setPaid(50)"
                        >
                            50%
                        </button>
                        <button
                            type="button"
                            class="rounded-full border border-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                            @click="setPaid(100)"
                        >
                            100%
                        </button>
                    </div>

                    <p v-if="amountPaid > total" class="text-xs text-red-600">
                        Payment cannot exceed {{ naira(total, 2) }}.
                    </p>
                    <p v-if="form.errors.amount_paid || form.errors.payment_method" class="text-xs text-red-600">
                        {{ form.errors.amount_paid || form.errors.payment_method }}
                    </p>

                    <!-- Totals -->
                    <div class="grid gap-4 rounded-xl bg-[#0D1527] p-5 text-sm text-slate-200 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <div class="flex justify-between py-0.5">
                                <span class="text-slate-400">Subtotal</span>
                                <span class="font-semibold text-white">{{ naira(subtotal, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-0.5">
                                <span class="text-slate-400">Discount</span>
                                <input
                                    v-model.number="discount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-28 rounded-lg border-0 bg-white/10 py-1.5 text-right text-sm font-semibold text-white placeholder:text-slate-500 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40"
                                    placeholder="0"
                                />
                            </div>
                            <div class="flex justify-between py-0.5">
                                <span class="text-slate-400">Tax ({{ taxRate }}%)</span>
                                <span class="font-semibold text-white">{{ naira(tax, 2) }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col justify-center gap-3 border-t border-white/15 pt-3 sm:border-l sm:border-t-0 sm:pl-5 sm:pt-0">
                            <div class="flex items-center justify-between text-base font-bold text-white">
                                <span>Total</span>
                                <span class="text-amber-400">{{ naira(total, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg bg-white/10 px-3.5 py-2">
                                <span class="text-xs font-medium text-slate-400">Balance due</span>
                                <span :class="balance > 0 ? 'font-bold text-amber-400' : 'font-bold text-emerald-400'">
                                    {{ naira(balance, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Remarks</label>
                            <textarea
                                v-model="form.remarks"
                                rows="2"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                placeholder="Optional note about this sale…"
                            />
                        </div>
                        <button
                            type="submit"
                            class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                            :disabled="!canSubmit"
                        >
                            <i :class="form.processing ? 'bi bi-hourglass-split animate-spin' : 'bi bi-bag-check'"></i>
                            <span>
                                {{ form.processing ? 'Saving…' : `Complete Sale · ${naira(total, 2)}` }}
                            </span>
                        </button>
                    </div>
                    <p v-if="!items.length" class="text-center text-xs text-slate-400">
                        Add at least one product to start the sale.
                    </p>
                    <p v-else-if="!form.customer_id" class="text-center text-xs text-slate-400">
                        Select an existing customer or add a new one to continue.
                    </p>
                </div>
            </div>
        </div>
    </form>

    <!-- ============ Customer Modal ============ -->
    <Modal :show="showCustomerModal" max-width="lg" @close="showCustomerModal = false">
        <div class="p-6 sm:p-7">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0D1527] text-white">
                        <i class="bi bi-person-vcard text-lg"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Customer</h3>
                        <p class="text-xs text-slate-500">Pick an existing customer or add a new one.</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-full p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    @click="showCustomerModal = false"
                >
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <!-- Tabs -->
            <div class="mb-5 flex rounded-xl bg-slate-100 p-1">
                <button
                    type="button"
                    class="flex-1 rounded-lg py-2 text-sm font-semibold transition"
                    :class="customerTab === 'existing' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    @click="customerTab = 'existing'"
                >
                    <i class="bi bi-people mr-1.5"></i>
                    Existing customers
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg py-2 text-sm font-semibold transition"
                    :class="customerTab === 'new' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    @click="
                        customerValidationErrors = {};
                        customerModalError = '';
                        customerTab = 'new'
                    "
                >
                    <i class="bi bi-person-plus mr-1.5"></i>
                    New customer
                </button>
            </div>

            <!-- Existing -->
            <div v-if="customerTab === 'existing'">
                <div class="relative mb-3">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input
                        v-model="customerQuery"
                        type="search"
                        placeholder="Search customers…"
                        class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                </div>

                <div v-if="filteredCustomers.length" class="max-h-80 space-y-2 overflow-y-auto pr-1">
                    <button
                        v-for="c in filteredCustomers"
                        :key="c.id"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl border border-slate-200 px-3.5 py-3 text-left transition hover:border-[#0D1527] hover:bg-slate-50 hover:shadow-sm"
                        @click="selectExistingCustomer(c)"
                    >
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0D1527]/5 text-xs font-bold text-[#0D1527]"
                        >
                            {{ initials(c.name) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ c.name }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ c.phone || c.email || 'No contact' }}</span>
                        </span>
                        <span
                            v-if="c.customer_type"
                            class="hidden shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 sm:block"
                        >
                            {{ customerTypeLabel(c.customer_type) }}
                        </span>
                        <span
                            v-if="c.has_outstanding"
                            class="shrink-0 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-800"
                        >
                            Owes {{ naira(Number(c.outstanding) || 0) }}
                        </span>
                        <span class="shrink-0">
                            <i class="bi bi-chevron-right text-sm text-slate-300"></i>
                        </span>
                    </button>
                </div>
                <div v-else class="rounded-xl border border-dashed border-slate-200 py-10 text-center">
                    <i class="bi bi-search mb-2 block text-2xl text-slate-300"></i>
                    <p class="text-sm text-slate-400">No customers match.</p>
                </div>
            </div>

            <!-- New -->
            <form v-else @submit.prevent="createCustomer">
                <div
                    v-if="customerModalError"
                    class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700"
                >
                    {{ customerModalError }}
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">
                            Customer name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="customerForm.name"
                            type="text"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            placeholder="e.g. Ada Obi"
                        />
                        <p v-if="customerValidationErrors.name" class="mt-1 text-xs text-red-600">
                            {{ customerValidationErrors.name[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone</label>
                        <input
                            v-model="customerForm.phone"
                            type="tel"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            placeholder="0803 000 0000"
                        />
                        <p v-if="customerValidationErrors.phone" class="mt-1 text-xs text-red-600">
                            {{ customerValidationErrors.phone[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Email</label>
                        <input
                            v-model="customerForm.email"
                            type="email"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            placeholder="customer@email.com"
                        />
                        <p v-if="customerValidationErrors.email" class="mt-1 text-xs text-red-600">
                            {{ customerValidationErrors.email[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Customer type</label>
                        <select
                            v-model="customerForm.customer_type"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        >
                            <option value="walk_in">Walk-in</option>
                            <option value="regular">Regular</option>
                            <option value="corporate">Corporate</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Location</label>
                        <input
                            v-model="customerForm.location"
                            type="text"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            placeholder="City / area"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Address</label>
                        <textarea
                            v-model="customerForm.address"
                            rows="2"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            placeholder="Street address"
                        />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                        @click="showCustomerModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-60"
                        :disabled="customerSaving"
                    >
                        <i :class="customerSaving ? 'bi bi-hourglass-split animate-spin' : 'bi bi-check-lg'"></i>
                        {{ customerSaving ? 'Saving…' : 'Add & Select' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>