<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    jobTypes: { type: Array, default: () => [] },
});

const form = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    customer_address: '',
    customer_company: '',
    quotation_date: new Date().toISOString().split('T')[0],
    valid_until: '',
    job_type: '',
    title: '',
    description: '',
    notes: '',
    discount: 0,
    tax: 0,
    other_charges: 0,
    items: [
        { item: '', description: '', quantity: 1, unit: 'pcs', unit_price: 0 },
    ],
});

const subtotal = computed(() =>
    form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
);

const grandTotal = computed(() =>
    subtotal.value - Number(form.discount || 0) + Number(form.tax || 0) + Number(form.other_charges || 0)
);

function addItem() {
    form.items.push({ item: '', description: '', quantity: 1, unit: 'pcs', unit_price: 0 });
}

function removeItem(index) {
    if (form.items.length <= 1) return;
    form.items.splice(index, 1);
}

function updateItemTotal(item) {
    // Force reactivity
    form.items = [...form.items];
}

function formatCurrency(value) {
    return '₦' + Number(value || 0).toLocaleString('en-NG');
}

function submit() {
    form.post('/admin/custom-quotations', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('items');
            form.items = [{ item: '', description: '', quantity: 1, unit: 'pcs', unit_price: 0 }];
        },
    });
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="New Custom Quotation" subtitle="Create a quotation for CCTV, electrical, fencing, or any custom service." />

    <div class="max-w-5xl mx-auto space-y-6">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Customer Information -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Customer Information</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Customer Name *</label>
                            <input v-model="form.customer_name" type="text" required class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p v-if="form.errors.customer_name" class="mt-1 text-xs text-red-600">{{ form.errors.customer_name }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone Number *</label>
                            <input v-model="form.customer_phone" type="tel" required class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p v-if="form.errors.customer_phone" class="mt-1 text-xs text-red-600">{{ form.errors.customer_phone }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Email</label>
                            <input v-model="form.customer_email" type="email" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p v-if="form.errors.customer_email" class="mt-1 text-xs text-red-600">{{ form.errors.customer_email }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Company</label>
                            <input v-model="form.customer_company" type="text" placeholder="Optional" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Address / Location</label>
                        <textarea v-model="form.customer_address" rows="2" placeholder="Full address or location" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>
            </div>

            <!-- Quotation Information -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Quotation Information</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Quotation Date *</label>
                            <input v-model="form.quotation_date" type="date" required class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Valid Until</label>
                            <input v-model="form.valid_until" type="date" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Job Type *</label>
                            <select v-model="form.job_type" required class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option value="">Select or type job type...</option>
                                <option v-for="t in jobTypes" :key="t" :value="t">{{ t }}</option>
                                <option value="CCTV Installation">CCTV Installation</option>
                                <option value="Electrical Maintenance">Electrical Maintenance</option>
                                <option value="Electric Fence Installation">Electric Fence Installation</option>
                                <option value="General Electrical">General Electrical</option>
                                <option value="Solar Installation">Solar Installation</option>
                                <option value="Other">Other</option>
                            </select>
                            <p v-if="form.errors.job_type" class="mt-1 text-xs text-red-600">{{ form.errors.job_type }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Title *</label>
                            <input v-model="form.title" type="text" placeholder="e.g. CCTV System for Office Building" required class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Description</label>
                        <textarea v-model="form.description" rows="3" placeholder="Detailed description of the work..." class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-slate-900">Items</h2>
                    <button type="button" @click="addItem" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        <i class="bi bi-plus-lg text-xs"></i> Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] border-collapse">
                        <thead>
                            <tr class="bg-[#0D1527] text-left text-[10.5px] font-bold uppercase tracking-widest text-slate-300">
                                <th class="px-3 py-3 rounded-tl-xl">Item / Description</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Unit</th>
                                <th class="px-3 py-3">Unit Price (₦)</th>
                                <th class="px-3 py-3">Total (₦)</th>
                                <th class="px-3 py-3 rounded-tr-xl"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100">
                                <td class="px-3 py-3">
                                    <input v-model="item.item" type="text" placeholder="Item name" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                                    <input v-model="item.description" type="text" placeholder="Description (optional)" class="mt-1.5 w-full rounded-lg border-slate-200 py-1.5 text-xs text-slate-600 focus:border-amber-400 focus:ring-amber-400/20" />
                                </td>
                                <td class="px-3 py-3">
                                    <input v-model.number="item.quantity" type="number" min="0.01" step="0.01" @input="updateItemTotal(item)" class="w-20 rounded-lg border-slate-300 py-2 text-sm text-right focus:border-amber-400 focus:ring-amber-400/20" />
                                </td>
                                <td class="px-3 py-3">
                                    <input v-model="item.unit" type="text" placeholder="pcs" class="w-20 rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                                </td>
                                <td class="px-3 py-3">
                                    <input v-model.number="item.unit_price" type="number" min="0" step="100" @input="updateItemTotal(item)" class="w-full rounded-lg border-slate-300 py-2 text-sm text-right focus:border-amber-400 focus:ring-amber-400/20" />
                                </td>
                                <td class="px-3 py-3 font-bold text-slate-900 whitespace-nowrap">{{ formatCurrency(item.quantity * item.unit_price) }}</td>
                                <td class="px-3 py-3 text-right">
                                    <button type="button" :disabled="form.items.length === 1" @click="removeItem(index)" class="flex h-8 w-8 items-center justify-center mx-auto rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-30" title="Remove">
                                        <i class="bi bi-trash3 text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pricing Summary -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Pricing Summary</h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Discount (₦)</label>
                            <input v-model.number="form.discount" type="number" min="0" step="100" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Tax / VAT (₦)</label>
                            <input v-model.number="form.tax" type="number" min="0" step="100" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Other Charges (₦)</label>
                            <input v-model.number="form.other_charges" type="number" min="0" step="100" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-slate-600">Subtotal</span><span class="font-bold text-slate-900">{{ formatCurrency(subtotal) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-slate-600">Discount</span><span class="font-medium text-red-600">- {{ formatCurrency(form.discount) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-slate-600">Tax / VAT</span><span class="font-medium text-slate-900">+ {{ formatCurrency(form.tax) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-slate-600">Other Charges</span><span class="font-medium text-slate-900">+ {{ formatCurrency(form.other_charges) }}</span></div>
                        <div class="border-t border-slate-200 pt-2 flex justify-between text-lg">
                            <span class="font-semibold text-slate-900">Grand Total</span>
                            <span class="font-black text-[#40e0d0]">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes / Terms -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Notes & Terms</h2>
                <textarea v-model="form.notes" rows="4" placeholder="Payment terms, warranty info, validity notes, etc." class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link href="/admin/custom-quotations" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    {{ form.processing ? 'Creating…' : 'Create Quotation' }}
                </button>
            </div>
        </form>
    </div>
</template>