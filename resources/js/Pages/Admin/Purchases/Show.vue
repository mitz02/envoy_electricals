<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, formatDate, formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    purchase: { type: Object, required: true },
    payments: { type: Array, default: () => [] },
    can_view_cost: { type: Boolean, default: false },
});

const { has } = useCan();
const showVoid = ref(false);
const voidForm = useForm({ void_reason: '' });

const showPay = ref(false);
const payForm = useForm({
    document_type: 'purchase',
    document_id: props.purchase.id,
    amount: Number(props.purchase.balance) || 0,
    payment_method: 'bank_transfer',
    payment_date: new Date().toISOString().slice(0, 10),
    remarks: '',
});

function submitPay() {
    payForm.post('/admin/payments');
}

function submitVoid() {
    voidForm.post(`/admin/purchases/${props.purchase.id}/void`);
}

function statusBadge(status) {
    const map = {
        success: 'bg-emerald-100 text-emerald-800',
        pending: 'bg-amber-100 text-amber-800',
        failed: 'bg-red-100 text-red-700',
        reversed: 'bg-slate-200 text-slate-600',
        refunded: 'bg-slate-200 text-slate-600',
    };
    return badgeClass(map[status] ?? 'bg-slate-200 text-slate-600');
}
</script>

<template>
        <FlashMessages />
        <PageHeader :title="`Purchase ${purchase.ref_id}`" />

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <p class="text-lg font-black text-slate-900">PURCHASE ORDER</p>
                            <p class="text-xs text-slate-500">{{ purchase.ref_id }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-600">{{ formatDate(purchase.purchase_date) }}</p>
                            <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium uppercase"
                                  :class="purchase.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'">
                                {{ purchase.status }}
                            </span>
                        </div>
                    </div>

                    <div class="py-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Supplier</p>
                        <p class="text-sm font-semibold text-slate-800">{{ purchase.supplier?.name ?? 'N/A' }}</p>
                        <p v-if="purchase.supplier?.phone" class="text-xs text-slate-500">{{ purchase.supplier.phone }}</p>
                        <p v-if="purchase.invoice_no" class="mt-2 text-xs text-slate-500">Supplier invoice: {{ purchase.invoice_no }}</p>
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="rounded-l-lg px-3 py-2">Product</th>
                                <th class="px-3 py-2 text-center">Qty</th>
                                <th class="px-3 py-2 text-right">Unit Cost</th>
                                <th class="rounded-r-lg px-3 py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in purchase.items" :key="item.id">
                                <td class="px-3 py-2 text-slate-700">{{ item.product?.name }}</td>
                                <td class="px-3 py-2 text-center text-slate-600">{{ item.quantity }}</td>
                                <td class="px-3 py-2 text-right text-slate-700">{{ naira(item.unit_cost) }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ naira(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
                        <div v-if="Number(purchase.discount)" class="flex justify-between text-slate-600"><span>Discount</span><span>-{{ naira(purchase.discount) }}</span></div>
                        <div class="flex justify-between text-base font-bold text-slate-900"><span>Total</span><span>{{ naira(purchase.total) }}</span></div>
                        <div class="flex justify-between text-emerald-700"><span>Paid</span><span>{{ naira(purchase.amount_paid) }}</span></div>
                        <div class="flex justify-between font-semibold" :class="purchase.balance > 0 ? 'text-amber-700' : 'text-slate-500'">
                            <span>Balance</span><span>{{ naira(purchase.balance) }}</span>
                        </div>
                    </div>

                    <div v-if="purchase.remarks" class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                        <span class="font-semibold">Remarks:</span> {{ purchase.remarks }}
                    </div>

                    <div class="mt-5">
                        <button
                            v-if="has('purchases.void') && purchase.status === 'completed'"
                            class="rounded-lg bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
                            @click="showVoid = true"
                        >
                            Void Purchase
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">Payments</h2>
                        <button
                            v-if="has('payments.record') && Number(purchase.balance) > 0 && purchase.status === 'completed'"
                            class="rounded-lg bg-[#0D1527] px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
                            @click="showPay = !showPay"
                        >
                            Record Payment
                        </button>
                    </div>

                    <div v-if="showPay" class="mb-4 space-y-3 rounded-lg bg-slate-50 p-4">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Amount</label>
                            <input v-model="payForm.amount" type="number" min="0.01" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p v-if="payForm.errors.amount" class="text-xs text-red-600">{{ payForm.errors.amount }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Method</label>
                                <select v-model="payForm.payment_method" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="pos">POS</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Date</label>
                                <input v-model="payForm.payment_date" type="date" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">Remarks</label>
                            <input v-model="payForm.remarks" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <button class="w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800" :disabled="payForm.processing" @click="submitPay">
                            Save Payment
                        </button>
                    </div>

                    <div v-if="payments.length" class="space-y-2">
                        <div v-for="p in payments" :key="p.id" class="flex items-center justify-between rounded-lg bg-slate-50 p-3 text-sm">
                            <div>
                                <p class="font-mono text-xs text-slate-400">{{ p.ref_id }}</p>
                                <p class="font-semibold text-slate-800">{{ naira(p.amount) }}</p>
                                <p class="text-xs text-slate-400">{{ formatDateTime(p.payment_date) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs capitalize text-slate-600">{{ p.payment_method }}{{ p.gateway === 'paystack' ? ' · Paystack' : '' }}</span>
                                <span :class="statusBadge(p.status)">{{ p.status }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-6 text-center text-sm text-slate-400">No payments recorded.</p>
                </div>

                <div v-if="showVoid" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-5">
                    <h2 class="text-sm font-semibold text-red-800">Void Purchase</h2>
                    <p class="mt-1 text-xs text-red-700">Stock will be deducted back. This is permanent.</p>
                    <form class="mt-3 space-y-3" @submit.prevent="submitVoid">
                        <textarea v-model="voidForm.void_reason" rows="3" placeholder="Reason…" class="w-full rounded-lg border-red-300 text-sm focus:border-red-500 focus:ring-red-500" />
                        <div v-if="voidForm.errors.void_reason" class="text-xs text-red-600">{{ voidForm.errors.void_reason }}</div>
                        <div class="flex gap-2">
                            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" :disabled="voidForm.processing">Confirm Void</button>
                            <button type="button" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showVoid = false">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</template>