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
    suppliers: { type: Array, default: () => [] },
    can_view_cost: { type: Boolean, default: false },
});

const search = ref('');
const items = ref([]);

const filteredProducts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
        return props.products.slice(0, 12);
    }
    return props.products.filter((p) => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q));
});

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
        });
    }
    search.value = '';
}

function removeItem(index) {
    items.value.splice(index, 1);
}

const subtotal = computed(() => items.value.reduce((sum, i) => sum + i.quantity * i.unit_cost, 0));
const discount = ref(0);
const total = computed(() => Math.max(subtotal.value - Number(discount.value || 0), 0));

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

function selectSupplier() {
    form.supplier_name = '';
}

function newSupplier() {
    form.supplier_id = '';
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            discount: Number(discount.value || 0),
            amount_paid: Number(form.amount_paid || 0),
            items: items.value.map((i) => ({
                product_id: i.product_id,
                quantity: i.quantity,
                unit_cost: i.unit_cost,
            })),
        }))
        .post('/admin/purchases');
}
</script>

<template>
        <FlashMessages />
        <PageHeader title="New Purchase" subtitle="Record stock received from suppliers. Inventory updates automatically." />

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
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
                            @click="addItem(p)"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800">{{ p.name }}</p>
                                <p class="text-xs text-slate-400">{{ p.sku }}</p>
                            </div>
                            <div class="ml-2 shrink-0 text-right">
                                <p class="text-sm font-semibold text-slate-900">{{ naira(p.cost_price || 0) }}</p>
                                <span class="text-xs text-slate-400">{{ p.current_quantity }} in stock</span>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-slate-200/80 bg-white shadow-xs">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-900">2 · Purchase Items</h2>
                    </div>
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Product</th>
                                <th class="px-4 py-2 text-center font-semibold text-slate-600">Qty</th>
                                <th class="px-4 py-2 text-right font-semibold text-slate-600">Unit Cost</th>
                                <th class="px-4 py-2 text-right font-semibold text-slate-600">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, index) in items" :key="item.product_id">
                                <td class="px-4 py-2">
                                    <p class="font-medium text-slate-800">{{ item.name }}</p>
                                    <p class="text-xs text-slate-400">{{ item.sku }}</p>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input v-model.number="item.quantity" type="number" min="1" class="w-20 rounded-lg border-slate-300 text-center text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <input v-model.number="item.unit_cost" type="number" step="0.01" min="0" class="w-32 rounded-lg border-slate-300 text-right text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                                </td>
                                <td class="px-4 py-2 text-right font-semibold text-slate-900">{{ naira(item.quantity * item.unit_cost) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <button type="button" class="text-red-500 hover:text-red-700" @click="removeItem(index)">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!items.length">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">No items yet — tap a product to add it.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">3 · Supplier</h2>
                    <div class="space-y-3">
                        <select v-model="form.supplier_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="selectSupplier">
                            <option value="">— Existing supplier —</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="h-px flex-1 bg-slate-200" /> OR <span class="h-px flex-1 bg-slate-200" />
                        </div>
                        <input v-model="form.supplier_name" type="text" placeholder="New supplier name" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @input="newSupplier" />
                        <input v-model="form.supplier_phone" type="tel" placeholder="Supplier phone" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @input="newSupplier" />
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-3 text-sm font-semibold text-slate-900">4 · Payment</h2>
                    <div class="space-y-3">
                        <input v-model="form.purchase_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <input v-model="form.invoice_no" type="text" placeholder="Supplier invoice no (optional)" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <select v-model="form.payment_method" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="cash">Cash</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                            <option value="pos">POS</option>
                            <option value="credit">Credit (on balance)</option>
                        </select>
                        <div class="space-y-1.5 border-t border-slate-100 pt-3 text-sm">
                            <div class="flex justify-between text-slate-600"><span>Subtotal</span><span>{{ naira(subtotal) }}</span></div>
                            <div class="flex justify-between text-slate-600">
                                <span>Discount</span>
                                <input v-model.number="discount" type="number" step="0.01" min="0" class="w-28 rounded-lg border-slate-300 text-right text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-slate-900"><span>Total</span><span>{{ naira(total) }}</span></div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Amount paid</label>
                            <input v-model.number="form.amount_paid" type="number" step="0.01" min="0" :max="total" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p v-if="form.amount_paid > total" class="mt-1 text-xs text-red-600">Payment cannot exceed {{ naira(total) }}.</p>
                        </div>
                        <textarea v-model="form.remarks" rows="2" placeholder="Remarks" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-[#0D1527] px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-slate-800 disabled:opacity-50"
                    :disabled="form.processing || !items.length"
                >
                    {{ form.processing ? 'Saving…' : 'Record Purchase' }}
                </button>
            </div>
        </form>
</template>