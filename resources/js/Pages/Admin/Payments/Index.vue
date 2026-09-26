<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDateTime, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    totals: { type: Object, default: () => ({ received: 0, paid_out: 0 }) },
});

const form = useForm({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function applyFilters() {
    const params = {};
    Object.entries(form.data()).forEach(([k, v]) => {
        if (v) {
            params[k] = v;
        }
    });
    window.location.search = new URLSearchParams(params).toString();
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
        <PageHeader title="Payments" subtitle="All money received and paid out." />

        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Money received (payments in)</p>
                <p class="mt-1 text-2xl font-black text-emerald-800">{{ naira(totals.received) }}</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Money paid out</p>
                <p class="mt-1 text-2xl font-black text-red-800">{{ naira(totals.paid_out) }}</p>
            </div>
        </div>

        <div class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-6">
            <input v-model="form.search" type="search" placeholder="Ref, customer, supplier…" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20 sm:col-span-2" @keyup.enter="applyFilters" />
            <select v-model="form.type" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All Types</option>
                <option value="payment_in">Payment In</option>
                <option value="payment_out">Payment Out</option>
            </select>
            <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All Status</option>
                <option value="success">Success</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
                <option value="reversed">Reversed</option>
                <option value="refunded">Refunded</option>
            </select>
            <input v-model="form.from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters" />
            <input v-model="form.to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters" />
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Reference</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Party</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Method</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-medium text-slate-900">{{ p.ref_id }}</span>
                                <p class="text-xs text-slate-400">{{ p.document_type }} #{{ p.document_id }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDateTime(p.payment_date) }}</td>
                            <td class="px-4 py-3">
                                <span :class="badgeClass(p.type === 'payment_in' ? 'bg-emerald-100 text-emerald-800' : 'bg-orange-100 text-orange-800')">
                                    {{ p.type === 'payment_in' ? 'In' : 'Out' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-800">
                                    {{ p.customer?.name ?? p.supplier?.name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 capitalize">{{ p.payment_method }}</td>
                            <td class="px-4 py-3 text-right font-semibold" :class="p.type === 'payment_in' ? 'text-emerald-700' : 'text-red-700'">
                                {{ p.type === 'payment_in' ? '+' : '-' }}{{ naira(p.amount) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span :class="statusBadge(p.status)">{{ p.status }}</span>
                            </td>
                        </tr>
                        <tr v-if="!payments.data.length">
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">
                                No payments recorded.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="payments" />
            </div>
        </div>
</template>