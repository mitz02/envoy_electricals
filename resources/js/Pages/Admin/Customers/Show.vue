<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, formatDate, formatDateTime } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    customer: { type: Object, required: true },
    totals: { type: Object, default: () => ({ sales_sum: 0, outstanding: 0, count: 0 }) },
    recent_payments: { type: Array, default: () => [] },
});

const { has } = useCan();
</script>

<template>
        <FlashMessages />
        <PageHeader
            :title="customer.name"
            :subtitle="customer.ref_id"
            :action-href="has('customers.manage') ? `/admin/customers/${customer.id}/edit` : ''"
            action-label="Edit"
        />

        <div
            v-if="Number(totals.outstanding) > 0"
            class="mb-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium leading-relaxed text-red-700"
            role="alert"
        >
            <i class="bi bi-exclamation-triangle-fill mt-0.5 text-red-500"></i>
            <span>
                This customer has an outstanding balance of
                <strong>{{ naira(totals.outstanding) }}</strong>
                across unpaid invoices.
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Lifetime Purchases</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(totals.sales_sum) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Invoices / Count</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ totals.count }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Outstanding</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ naira(totals.outstanding) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contact</p>
                <p class="mt-2 text-sm font-semibold text-slate-700">{{ customer.phone || 'No phone' }}</p>
                <p class="text-xs text-slate-400">{{ customer.email || 'No email' }}</p>
                <p class="mt-2">
                    <span v-if="customer.store" class="inline-flex items-center gap-1 rounded-full bg-[#40e0d0]/10 px-2.5 py-0.5 text-xs font-semibold text-[#0D1527]">
                        <i class="bi bi-shop text-[10px]"></i>
                        {{ customer.store.name }}
                    </span>
                    <span v-else class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                        <i class="bi bi-globe2 text-[10px]"></i>
                        Unassigned
                    </span>
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Recent Sales</h2>
                <div v-if="customer.sales?.length" class="space-y-3">
                    <div v-for="s in customer.sales" :key="s.id" class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-0">
                        <div>
                            <p class="font-mono text-xs font-medium text-slate-700">{{ s.invoice_no }}</p>
                            <p class="text-xs text-slate-400">{{ formatDate(s.sale_date) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-900">{{ naira(s.total) }}</p>
                            <p class="text-xs" :class="s.balance > 0 ? 'text-amber-700' : 'text-slate-400'">
                                {{ s.balance > 0 ? `Bal: ${naira(s.balance)}` : 'Paid' }}
                            </p>
                        </div>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-slate-400">No sales yet.</p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Recent Payments</h2>
                <div v-if="recent_payments.length" class="space-y-3">
                    <div v-for="p in recent_payments" :key="p.id" class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ naira(p.amount) }}</p>
                            <p class="text-xs text-slate-400">{{ formatDateTime(p.created_at) }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ p.payment_method }}</span>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-slate-400">No payments yet.</p>
            </div>
        </div>
</template>