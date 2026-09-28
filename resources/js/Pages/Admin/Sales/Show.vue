<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, maskNaira, formatDate, formatDateTime, badgeClass } from '@/lib/format';
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

const statusForm = useForm({ status: props.sale.status });

const statusOptions = ['completed', 'pending', 'void'];

function updateStatus() {
    statusForm.post(route('admin.sales.status', props.sale.id), { preserveScroll: true });
}

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

async function copyPaystackLink() {
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
        await navigator.clipboard.writeText(data.authorization_url);
        paystack.value.error = '✅ Payment link copied to clipboard!';
    } catch (e) {
        paystack.value.error = e.message;
    } finally {
        paystack.value.busy = false;
    }
}

async function emailPaymentLink() {
    paystack.value.busy = true;
    paystack.value.error = '';
    try {
        const res = await fetch('/admin/payments/send-link', {
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
            throw new Error(data.message || 'Could not send payment link email.');
        }
        paystack.value.error = `✅ ${data.message}`;
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
        <FlashMessages class="print:hidden" />
        <PageHeader
            class="print:hidden"
            :title="`Invoice ${sale.invoice_no}`"
            :subtitle="sale.ref_id"
        />
        <link
            v-if="showVoid"
            rel="stylesheet"
        />

        <div class="print-full grid gap-6 lg:grid-cols-3">
            <!-- Invoice -->
            <div class="lg:col-span-2">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_24px_70px_-30px_rgba(13,21,39,0.35)] print:rounded-none print:border-0 print:shadow-none">
                    <!-- Logo watermark background -->
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center print:opacity-100">
                        <img src="/envoy_images/logo.png" alt="" class="h-80 w-80 object-contain opacity-[0.05]" />
                    </div>

                    <!-- Company + Invoice header -->
                    <div class="relative flex flex-wrap items-start justify-between gap-6 px-6 py-7 sm:px-9">
                        <div class="flex items-start gap-3">
                            <img src="/envoy_images/logo.png" alt="{{ sale.company?.name ?? 'Envoy Electricals' }}" class="h-28 w-28 shrink-0 object-contain" />
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-black uppercase tracking-[0.2em] text-[#0D1527]">Invoice</p>
                            <p class="mt-1 text-xs text-slate-400">No. <span class="font-mono text-sm font-semibold text-slate-800">{{ sale.invoice_no }}</span></p>
                            <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold uppercase ring-1 ring-inset"
                                  :class="sale.status === 'completed'
                                      ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                      : 'bg-red-50 text-red-700 ring-red-200'">
                                <span class="h-1.5 w-1.5 rounded-full" :class="sale.status === 'completed' ? 'bg-emerald-500' : 'bg-red-500'" />
                                {{ sale.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Invoice details strip -->
                    <div class="relative mx-6 grid grid-cols-2 gap-y-3 border-y border-slate-200 py-3 sm:mx-9 sm:grid-cols-4 sm:gap-0">
                        <div class="sm:border-r sm:border-slate-200 sm:pr-4">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Invoice Date</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ formatDate(sale.sale_date) }}</p>
                        </div>
                        <div class="sm:border-r sm:border-slate-200 sm:px-4">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Reference</p>
                            <p class="mt-0.5 font-mono text-sm font-semibold text-slate-800">{{ sale.ref_id }}</p>
                        </div>
                        <div class="sm:border-r sm:border-slate-200 sm:px-4">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Payment Method</p>
                            <p class="mt-0.5 text-sm font-semibold capitalize text-slate-800">{{ sale.payment_method ? sale.payment_method.replace(/_/g, ' ') : '—' }}</p>
                        </div>
                        <div class="sm:pl-4">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Balance Due</p>
                            <p class="mt-0.5 text-sm font-bold" :class="sale.balance > 0 ? 'text-red-600' : 'text-emerald-600'">{{ naira(sale.balance) }}</p>
                        </div>
                    </div>

                    <!-- Billed to -->
                    <div class="relative mx-6 mt-6 sm:mx-9">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Billed to</p>
                        <div class="mt-1.5 max-w-sm rounded-lg border border-slate-200 bg-slate-50/60 px-4 py-3">
                            <p class="text-sm font-bold text-slate-900">{{ sale.customer?.name ?? 'Walk-in Customer' }}</p>
                            <p v-if="sale.customer?.phone" class="mt-0.5 text-xs text-slate-600">{{ sale.customer.phone }}</p>
                            <p v-if="sale.customer?.email" class="text-xs text-slate-600">{{ sale.customer.email }}</p>
                            <p v-if="sale.customer?.address" class="text-xs text-slate-600">{{ sale.customer.address }}</p>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Sales Person: <span class="font-semibold text-slate-700">{{ sale.salesperson?.name ?? '—' }}</span></p>
                        <p
                            v-if="sale.customer && Number(sale.customer.outstanding) > Number(sale.balance)"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800"
                        >
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Also owes {{ naira(Number(sale.customer.outstanding) - Number(sale.balance)) }} from other invoices
                        </p>
                    </div>

                    <!-- Items table -->
                    <div class="relative mx-6 mt-6 overflow-hidden rounded-lg border border-slate-200 sm:mx-9">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-100/70 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="px-3 py-2.5">#</th>
                                    <th class="px-3 py-2.5">Item</th>
                                    <th class="px-3 py-2.5 text-center">Qty</th>
                                    <th class="px-3 py-2.5 text-right">Unit Price</th>
                                    <th v-if="can_view_cost" class="px-3 py-2.5 text-right">Unit Cost</th>
                                    <th class="px-3 py-2.5 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, idx) in sale.items" :key="item.id">
                                    <td class="px-3 py-2.5 text-slate-400">{{ idx + 1 }}</td>
                                    <td class="px-3 py-2.5 font-medium text-slate-800">{{ item.product?.name ?? item.description }}</td>
                                    <td class="px-3 py-2.5 text-center text-slate-600">{{ item.quantity }}</td>
                                    <td class="px-3 py-2.5 text-right text-slate-700">{{ naira(item.unit_price) }}</td>
                                    <td v-if="can_view_cost" class="px-3 py-2.5 text-right text-slate-500">{{ maskNaira('cost_price', item.unit_cost) }}</td>
                                    <td class="px-3 py-2.5 text-right font-semibold text-slate-900">{{ naira(item.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Notes + Totals -->
                    <div class="relative mx-6 mt-6 grid gap-6 sm:mx-9 sm:grid-cols-[1fr_280px]">
                        <div class="space-y-4 text-xs text-slate-500">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Notes</p>
                                <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2.5 text-slate-600">{{ sale.remarks || '—' }}</p>
                            </div>
                            <div class="border-t border-slate-100 pt-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Terms</p>
                                <p class="mt-1">
                                    Payment is due on receipt. Goods purchased are not returnable without the original receipt and within 7 days of sale.
                                </p>
                            </div>
                        </div>

                        <div class="w-full sm:w-auto">
                            <div class="space-y-1.5 rounded-lg border border-slate-200 px-4 py-3.5 text-sm">
                                <div class="flex justify-between text-slate-600"><span>Subtotal</span><span>{{ naira(sale.subtotal) }}</span></div>
                                <div v-if="Number(sale.discount)" class="flex justify-between text-slate-600"><span>Discount</span><span class="text-red-600">-{{ naira(sale.discount) }}</span></div>
                                <div v-if="Number(sale.tax)" class="flex justify-between text-slate-600"><span>Tax ({{ sale.tax_rate }}%)</span><span>{{ naira(sale.tax) }}</span></div>
                                <div class="flex justify-between border-t-2 border-slate-900 pt-2 text-base font-black text-slate-900"><span>Total</span><span>{{ naira(sale.total) }}</span></div>
                                <div class="flex justify-between text-emerald-700"><span>Paid</span><span>{{ naira(sale.amount_paid) }}</span></div>
                                <div class="flex justify-between font-bold"><span>Balance</span><span :class="sale.balance > 0 ? 'text-red-600' : 'text-emerald-700'">{{ naira(sale.balance) }}</span></div>
                            </div>
                            <p v-if="sale.balance > 0" class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-center text-[11px] font-semibold text-red-700">
                                Balance due — kindly settle this invoice promptly.
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="relative mt-7 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-6 py-4 sm:px-9">
                        <p class="text-[11px] text-slate-400">Envoy Electricals Ltd · 12 Industrial Avenue, Ikeja, Lagos · hello@envoyelectric.ng</p>
                        <p class="text-[11px] text-slate-400">Printed {{ formatDateTime(new Date()) }}</p>
                    </div>

                    <div class="relative mt-5 flex flex-wrap gap-2 px-6 pb-6 print:hidden sm:px-9">
                        <a
                            :href="route('admin.sales.invoice', sale.id)"
                            target="_blank"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800"
                        >
                            <i class="bi bi-file-earmark-arrow-down text-base"></i>
                            Download Invoice
                        </a>
                        <button
                            class="inline-flex items-center gap-2 rounded-xl bg-yellow-400 px-5 py-2.5 text-sm font-bold text-[#0D1527] shadow-sm transition hover:bg-yellow-300"
                            @click="printInvoice"
                        >
                            <i class="bi bi-printer text-base"></i>
                            Print Invoice
                        </button>

                        <div v-if="has('sales.edit')" class="flex items-center gap-2">
                            <label class="text-xs font-semibold text-slate-500">Status</label>
                            <select v-model="statusForm.status" :disabled="statusForm.processing" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option v-for="s in statusOptions" :key="s" :value="s" :disabled="sale.status === 'void' && s !== 'void'">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
                            </select>
                            <button :disabled="statusForm.processing || statusForm.status === sale.status" @click="updateStatus" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                                {{ statusForm.processing ? 'Updating…' : 'Update' }}
                            </button>
                        </div>

                        <button
                            v-if="has('sales.void') && sale.status === 'completed'"
                            class="rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                            @click="showVoid = true"
                        >
                            Void Sale
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payments -->
            <div class="print:hidden">
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

                    <div v-if="has('payments.record') && Number(sale.balance) > 0 && sale.status === 'completed'" class="space-y-2">
                        <div class="flex gap-2">
                            <button
                                class="flex-1 items-center justify-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100 disabled:opacity-50"
                                :disabled="paystack.busy"
                                @click="payWithPaystack"
                            >
                                <svg v-if="paystack.busy" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                Open Paystack Payment Link
                            </button>
                            <button
                                class="items-center justify-center gap-2 rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 disabled:opacity-50"
                                :disabled="paystack.busy"
                                @click="copyPaystackLink"
                                title="Copy payment link to clipboard"
                            >
                                <svg v-if="paystack.busy" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>

                        <button
                            v-if="sale.customer?.email"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#40e0d0]/10 px-4 py-2 text-sm font-semibold text-[#40e0d0] hover:bg-[#40e0d0]/20 disabled:opacity-50"
                            :disabled="paystack.busy"
                            @click="emailPaymentLink"
                        >
                            <svg v-if="paystack.busy" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            <i class="bi bi-envelope-fill" />
                            Email Payment Link to Customer
                        </button>

                        <template v-else>
                            <p class="text-center text-xs text-slate-400">Add customer email to enable sending payment link.</p>
                        </template>
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

<style>
@media print {
    .print-full {
        display: block !important;
    }
    body {
        background: white !important;
    }
    * {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
