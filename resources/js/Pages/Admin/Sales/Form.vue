<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    tax_rate: { type: Number, default: 0 },
});

const search = ref('');
const items = ref([]);

const filteredProducts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
        return props.products.slice(0, 12);
    }
    return props.products.filter(
        (p) => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || p.id.toString() === q,
    );
});

function addItem(product) {
    const existing = items.value.find((i) => i.product_id === product.id);
    if (existing) {
        if (existing.quantity < product.current_quantity) {
            existing.quantity += 1;
        }
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

const subtotal = computed(() => items.value.reduce((sum, i) => sum + i.quantity * i.unit_price, 0));
const discount = ref(0);
const taxRate = ref(props.tax_rate);
const taxable = computed(() => Math.max(subtotal.value - Number(discount.value || 0), 0));
const tax = computed(() => (taxable.value * Number(taxRate.value || 0)) / 100);
const total = computed(() => taxable.value + tax.value);
const maxPaid = computed(() => total.value);

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
    items: items,
});

function useNewCustomer() {
    form.customer_id = '';
}

function companyCustomerSelected() {
    form.customer_name = '';
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            discount: Number(discount.value || 0),
            tax_rate: Number(taxRate.value || 0),
            amount_paid: Number(form.amount_paid || 0),
            items: items.value.map((i) => ({
                product_id: i.product_id,
                quantity: i.quantity,
                unit_price: i.unit_price,
            })),
        }))
        .post('/admin/sales');
}
</script>

<template>
        <FlashMessages />
        <PageHeader title="New Sale" subtitle="Add products, set quantity and record payment." />

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <!-- Product Picker -->
            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">1 · Add Products</h2>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search product name or SKU…"
                        class="mb-3 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                    <div class="grid max-h-64 gap-2 overflow-y-auto sm:grid-cols-2">
                        <button
                            v-for="p in filteredProducts"
                            :key="p.id"
                            type="button"
                            class="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-left transition hover:border-slate-900 hover:bg-slate-50"
                            :disabled="p.current_quantity <= 0"
                            @click="addItem(p)"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800">{{ p.name }}</p>
                                <p class="text-xs text-slate-400">{{ p.sku }}</p>
                            </div>
                            <div class="ml-2 shrink-0 text-right">
                                <p class="text-sm font-semibold text-slate-900">{{ naira(p.selling_price) }}</p>
                                <span :class="badgeClass(p.current_quantity <= 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800')">
                                    {{ p.current_quantity }} in stock
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Line items -->
                <div class="mt-6 rounded-xl border border-slate-200/80 bg-white shadow-xs">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-900">2 · Sale Items</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-slate-600">Product</th>
                                    <th class="px-4 py-2 text-center font-semibold text-slate-600">Qty</th>
                                    <th class="px-4 py-2 text-right font-semibold text-slate-600">Unit Price</th>
                                    <th class="px-4 py-2 text-right font-semibold text-slate-600">Total</th>
                                    <th class="px-4 py-2 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, index) in items" :key="item.product_id">
                                    <td class="px-4 py-2">
                                        <p class="font-medium text-slate-800">{{ item.name }}</p>
                                        <p class="text-xs text-slate-400">{{ item.sku }} · {{ item.available }} available</p>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            :max="item.available"
                                            class="w-20 rounded-lg border-slate-300 text-center text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                        />
                                        <p v-if="item.quantity > item.available" class="text-xs text-red-600">
                                            Only {{ item.available }} available
                                        </p>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-32 rounded-lg border-slate-300 text-right text-sm focus:border-amber-400 focus:ring-amber-400/20"
                                        />
                                    </td>
                                    <td class="px-4 py-2 text-right font-semibold text-slate-900">{{ naira(item.quantity * item.unit_price) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <button type="button" class="text-red-500 hover:text-red-700" @click="removeItem(index)">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!items.length">
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">
                                        No items yet — tap a product to add it.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right column: customer + totals -->
            <div class="space-y-5">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">3 · Customer</h2>
                    <div class="space-y-3">
                        <select
                            v-model="form.customer_id"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            @change="companyCustomerSelected"
                        >
                            <option value="">— Existing customer —</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} ({{ c.phone || 'no phone' }})</option>
                        </select>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="h-px flex-1 bg-slate-200" /> OR <span class="h-px flex-1 bg-slate-200" />
                        </div>
                        <input v-model="form.customer_name" type="text" placeholder="New customer name" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @input="useNewCustomer" />
                        <input v-model="form.customer_phone" type="tel" placeholder="Customer phone" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @input="useNewCustomer" />
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">4 · Payment</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Sale date</label>
                            <input v-model="form.sale_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Payment method</label>
                            <select v-model="form.payment_method" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option value="cash">Cash</option>
                                <option value="transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="pos">POS</option>
                                <option value="credit">Credit (on balance)</option>
                            </select>
                        </div>
                        <div class="space-y-1.5 border-t border-slate-100 pt-3 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal</span><span>{{ naira(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Discount</span>
                                <input v-model.number="discount" type="number" step="0.01" min="0" class="w-28 rounded-lg border-slate-300 text-right text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Tax ({{ taxRate }}%)</span><span>{{ naira(tax) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-slate-900">
                                <span>Total</span><span>{{ naira(total) }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Amount paid</label>
                            <input v-model.number="form.amount_paid" type="number" step="0.01" min="0" :max="maxPaid" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p v-if="form.amount_paid > total" class="mt-1 text-xs text-red-600">Payment cannot exceed {{ naira(total) }}.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Remarks</label>
                            <textarea v-model="form.remarks" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50"
                    :disabled="form.processing || !items.length"
                >
                    {{ form.processing ? 'Saving…' : 'Complete Sale' }}
                </button>
            </div>
        </form>
</template>