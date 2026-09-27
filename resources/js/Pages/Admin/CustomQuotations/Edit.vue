<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    quotation: { type: Object, required: true },
});

const form = useForm({
    customer_name: props.quotation.customer_name,
    customer_email: props.quotation.customer_email,
    customer_phone: props.quotation.customer_phone,
    customer_address: props.quotation.customer_address,
    job_description: props.quotation.description,
    items: props.quotation.items.map(item => ({
        id: item.id,
        item: item.item,
        quantity: item.quantity,
        unit_price: item.unit_price,
    })),
});

const subtotal = computed(() =>
    form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
);

function addItem() {
    form.items.push({ item: '', quantity: 1, unit_price: 0 });
}

function removeItem(index) {
    if (form.items.length <= 1) return;
    form.items.splice(index, 1);
}

function formatCurrency(value) {
    return '₦' + Number(value || 0).toLocaleString('en-NG');
}

function submit() {
    form.put(`/admin/custom-quotations/${props.quotation.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="Edit Quotation" subtitle="Update quotation details and items." />

    <div class="max-w-3xl mx-auto space-y-6">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Customer Information -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Customer Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Customer Name *</label>
                        <input v-model="form.customer_name" type="text" required class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="form.errors.customer_name" class="mt-1 text-xs text-red-600">{{ form.errors.customer_name }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Email (Optional)</label>
                            <input v-model="form.customer_email" type="email" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p v-if="form.errors.customer_email" class="mt-1 text-xs text-red-600">{{ form.errors.customer_email }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone (Optional)</label>
                            <input v-model="form.customer_phone" type="tel" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Address / Location</label>
                        <textarea v-model="form.customer_address" rows="2" placeholder="Full address or location" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>
            </div>

            <!-- Job Description -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Job Description</h2>
                <textarea v-model="form.job_description" rows="4" placeholder="Describe the work to be done..." class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
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
                    <table class="w-full min-w-[600px] border-collapse">
                        <thead>
                            <tr class="bg-[#0D1527] text-left text-[10.5px] font-bold uppercase tracking-widest text-slate-300">
                                <th class="px-3 py-3 rounded-tl-xl">Item</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Amount (₦)</th>
                                <th class="px-3 py-3">Total (₦)</th>
                                <th class="px-3 py-3 rounded-tr-xl"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="item.id || index" class="border-b border-slate-100">
                                <td class="px-3 py-3">
                                    <input v-model="item.item" type="text" placeholder="Item name" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                                </td>
                                <td class="px-3 py-3">
                                    <input v-model.number="item.quantity" type="number" min="1" step="1" class="w-20 rounded-lg border-slate-300 py-2 text-sm text-right focus:border-amber-400 focus:ring-amber-400/20" />
                                </td>
                                <td class="px-3 py-3">
                                    <input v-model.number="item.unit_price" type="number" min="0" step="100" class="w-full rounded-lg border-slate-300 py-2 text-sm text-right focus:border-amber-400 focus:ring-amber-400/20" />
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

            <!-- Total -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-slate-600">Subtotal</span><span class="font-bold text-slate-900">{{ formatCurrency(subtotal) }}</span></div>
                    <div class="border-t border-slate-200 pt-2 flex justify-between text-lg">
                        <span class="font-semibold text-slate-900">Total</span>
                        <span class="font-black text-[#40e0d0]">{{ formatCurrency(subtotal) }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link :href="`/admin/custom-quotations/${props.quotation.id}`" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    {{ form.processing ? 'Saving…' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>