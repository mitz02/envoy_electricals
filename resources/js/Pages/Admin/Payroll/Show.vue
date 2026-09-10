<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payroll: { type: Object, required: true },
    statuses: { type: Array, default: () => [] },
});

const { has } = useCan();
const canManage = has('payroll.manage');

const payForm = useForm({
    payment_method: 'bank',
    payment_date: new Date().toISOString().slice(0, 10),
});

const deleteForm = useForm({});

function markPaid() {
    payForm.post(`/admin/payroll/${props.payroll.id}/pay`);
}

function voidPayroll() {
    if (!confirm(`Cancel ${props.payroll.ref_id}? A paid run will be reversed in the payments ledger.`)) {
        return;
    }
    deleteForm.delete(`/admin/payroll/${props.payroll.id}`);
}

function printPayslip() {
    window.print();
}

const statusClasses = {
    pending: 'bg-amber-100 text-amber-800',
    paid: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-700',
};
</script>

<template>
        <FlashMessages />
        <div class="mb-4 flex items-center gap-2">
            <Link href="/admin/payroll" class="text-sm text-slate-500 hover:text-slate-900">← Back to payroll</Link>
        </div>

        <div class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <!-- Payslip header -->
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-400 font-black text-slate-900">E</span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Envoy Electric</p>
                            <p class="text-xs text-slate-400">Payslip {{ payroll.ref_id }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-lg font-bold text-slate-900">{{ payroll.staff?.name }}</p>
                    <p class="text-sm text-slate-500">{{ payroll.staff?.position || 'Staff' }} · {{ payroll.period_label }}</p>
                </div>
                <span :class="badgeClass(statusClasses[payroll.status])">{{ payroll.status }}</span>
            </div>

            <!-- Earnings -->
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Base salary</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ naira(payroll.base_salary) }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Allowance</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ naira(payroll.allowance) }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-4">
                    <p class="text-xs text-emerald-600">Bonus</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-700">{{ naira(payroll.bonus) }}</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-4">
                    <p class="text-xs text-amber-600">Advance (−)</p>
                    <p class="mt-1 text-lg font-semibold text-amber-700">{{ naira(payroll.advance) }}</p>
                </div>
                <div class="rounded-lg bg-red-50 p-4">
                    <p class="text-xs text-red-600">Deduction (−)</p>
                    <p class="mt-1 text-lg font-semibold text-red-700">-{{ naira(payroll.deduction) }}</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-xl bg-[#0D1527] px-5 py-4 text-white">
                <span class="text-sm font-medium text-slate-300">Net pay</span>
                <span class="text-2xl font-bold">{{ naira(payroll.amount_paid) }}</span>
            </div>

            <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Payment method</dt><dd class="font-medium text-slate-900">{{ payroll.payment_method || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Payment date</dt><dd class="font-medium text-slate-900">{{ payroll.payment_date || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Prepared by</dt><dd class="font-medium text-slate-900">{{ payroll.creator?.name || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Ledger payments</dt><dd class="font-medium text-slate-900">{{ payroll.payments.length }}</dd></div>
            </dl>

            <p v-if="payroll.notes" class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ payroll.notes }}</p>

            <!-- Actions -->
            <div v-if="canManage && (payroll.status === 'pending' || payroll.status === 'paid')" class="mt-6 border-t border-slate-100 pt-5">
                <template v-if="payroll.status === 'pending'">
                    <p v-if="payForm.errors.pay" class="mb-2 text-sm text-red-600">{{ payForm.errors.pay }}</p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Payment date</label>
                            <input v-model="payForm.payment_date" type="date" class="mt-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Method</label>
                            <select v-model="payForm.payment_method" class="mt-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option value="bank">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="pos">POS</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500" :disabled="payForm.processing" @click="markPaid">
                            Mark as Paid — {{ naira(payroll.amount_paid) }}
                        </button>
                    </div>
                    <Link :href="`/admin/payroll/${payroll.id}/edit`" class="mt-3 inline-block text-sm text-slate-600 hover:text-slate-900">Edit components</Link>
                </template>
                <p v-else class="mb-3 text-sm text-slate-500">
                    Paid {{ naira(payroll.amount_paid) }} on {{ payroll.payment_date }} — recorded in the payments ledger.
                </p>
                <p v-if="deleteForm.errors.pay" class="mb-2 text-sm text-red-600">{{ deleteForm.errors.pay }}</p>
                <button class="mt-2 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50" @click="voidPayroll">
                    Cancel payroll &amp; reverse payment
                </button>
            </div>

            <button class="mt-4 text-sm text-slate-600 hover:text-slate-900" @click="printPayslip">Print / Save PDF</button>
        </div>
</template>