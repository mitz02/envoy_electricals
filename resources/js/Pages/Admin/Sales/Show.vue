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
    sale: { type: Object, required: true },
    payments: { type: Array, default: () => [] },
    can_view_cost: { type: Boolean, default: false },
});

const { has } = useCan();
const showVoid = ref(false);
const voidForm = useForm({ void_reason: '' });

const showPay = ref(false);
const payForm = useForm({
    document_type: 'sale',
    document_id: props.sale.id,
    amount: Number(props.sale.balance) || 0,
    payment_method: 'cash',
    payment_date: new Date().toISOString().slice(0, 10),
    remarks: '',
});

const paystack = ref({ busy: false, error: '' });

function submitPay() {
    payForm.post('/admin/payments');
}

function xsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function payWithPaystack() {
    paystack.value.busy = true;
    paystack.value.error = '';
    try {
        const res = await fetch('/admin/payments/paystack', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify({ document_type: 'sale', document_id: props.sale.id }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || 'Could not generate a Paystack payment link.');
        }
        window.open(data.authorization_url, '_blank');
    } catch (e) {
        paystack.value.error = e.message;
    } finally {
        paystack.value.busy = false;
    }
}

function submitVoid() {
    voidForm.post(`/admin/sales/${props.sale.id}/void`);
}

function printInvoice() {
    window.print();
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
        <PageHeader
            :title="`Invoice ${sale.invoice_no}`"
            :subtitle="sale.ref_id"
        />
        <link
            v-if="showVoid"
            rel="stylesheet"
        />

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Invoice -->
            <div class="lg:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <p class="text-xl font-black text-slate-900">ENVOY <span class="text-amber-500">ELECTRIC</span></p>
                            <p class="text-xs text-slate-500">Electrical Materials &amp; Solar Installations</p>
                        </div>
                        <div class="text-right">
                            <p class="font-mono text-sm font-semibold text-slate-900">{{ sale.invoice_no }}</p>
                            <p class="text-xs text-slate-500">{{ formatDate(sale.sale_date) }}</p>
                            <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium uppercase"
                                  :class="sale.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'">
                                {{ sale.status }}
                            </span>
                        </div>
                    </div>

                    <div class="py-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Billed to</p>
                        <p class="text-sm font-semibold text-slate-800">{{ sale.customer?.name ?? 'Walk-in Customer' }}</p>
                        <p v-if="sale.customer?.phone" class="text-xs text-slate-500">{{ sale.customer.phone }}</p>
                        <p class="mt-2 text-xs text-slate-500">Salesperson: {{ sale.salesperson?.name ?? '—' }}</p>
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="rounded-l-lg px-3 py-2">Item</th>
                                <th class="px-3 py-2 text-center">Qty</th>
                                <th class="px-3 py-2 text-right">Unit Price</th>
                                <th v-if="can_view_cost" class="px-3 py-2 text-right">Unit Cost</th>
                                <th class="rounded-r-lg px-3 py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in sale.items" :key="item.id">
                                <td class="px-3 py-2 text-slate-700">{{ item.product?.name ?? item.description }}</td>
                                <td class="px-3 py-2 text-center text-slate-600">{{ item.quantity }}</td>
                                <td class="px-3 py-2 text-right text-slate-700">{{ naira(item.unit_price) }}</td>
                                <td v-if="can_view_cost" class="px-3 py-2 text-right text-slate-500">{{ naira(item.unit_cost) }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ naira(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex justify-between text-slate-600"><span>Subtotal</span><span>{{ naira(sale.subtotal) }}</span></div>
                        <div v-if="Number(sale.discount)" class="flex justify-between text-slate-600"><span>Discount</span><span>-{{ naira(sale.discount) }}</span></div>
                        <div v-if="Number(sale.tax)" class="flex justify-between text-slate-600"><span>Tax ({{ sale.tax_rate }}%)</span><span>{{ naira(sale.tax) }}</span></div>
                        <div class="flex justify-between text-base font-bold text-slate-900"><span>Total</span><span>{{ naira(sale.total) }}</span></div>
                        <div class="flex justify-between text-emerald-700"><span>Paid</span><span>{{ naira(sale.amount_paid) }}</span></div>
                        <div class="flex justify-between font-semibold" :class="sale.balance > 0 ? 'text-amber-700' : 'text-slate-500'">
                            <span>Balance</span><span>{{ naira(sale.balance) }}</span>
                        </div>
                    </div>

                    <div v-if="sale.remarks" class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                        <span class="font-semibold">Remarks:</span> {{ sale.remarks }}
                    </div>

                    <div class="mt-5 flex gap-2 print:hidden">
                        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="printInvoice">
                            Print
                        </button>
                        <button
                            v-if="has('sales.void') && sale.status === 'completed'"
                            class="rounded-lg bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
                            @click="showVoid = true"
                        >
                            Void Sale
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payments -->
            <div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">Payments</h2>
                        <button
                            v-if="has('payments.record') && Number(sale.balance) > 0 && sale.status === 'completed'"
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

                    <div v-if="paystack.error" class="mb-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">
                        {{ paystack.error }}
                    </div>

                    <button
                        v-if="has('payments.record') && Number(sale.balance) > 0 && sale.status === 'completed'"
                        class="mb-3 flex w-full items-center justify-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100 disabled:opacity-50"
                        :disabled="paystack.busy"
                        @click="payWithPaystack"
                    >
                        <svg v-if="paystack.busy" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        Send Paystack Payment Link
                    </button>

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
                    <h2 class="text-sm font-semibold text-red-800">Void Sale</h2>
                    <p class="mt-1 text-xs text-red-700">This will reverse all stock movements. This is permanent.</p>
                    <form class="mt-3 space-y-3" @submit.prevent="submitVoid">
                        <textarea v-model="voidForm.void_reason" rows="3" placeholder="Reason for voiding…" class="w-full rounded-lg border-red-300 text-sm focus:border-red-500 focus:ring-red-500" />
                        <div v-if="voidForm.errors.void_reason" class="text-xs text-red-600">{{ voidForm.errors.void_reason }}</div>
                        <div class="flex gap-2">
                            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" :disabled="voidForm.processing">
                                Confirm Void
                            </button>
                            <button type="button" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showVoid = false">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</template>