<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    sales: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const has = useCan().has;

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

// Simple full-page query navigation for filters
function applyFilters() {
    const params = {};
    Object.entries(form.data()).forEach(([k, v]) => {
        if (v) {
            params[k] = v;
        }
    });
    window.location.search = new URLSearchParams(params).toString();
}
</script>

<template>
        <FlashMessages />
        <PageHeader
            title="Sales"
            subtitle="Sales history and invoices."
            :action-href="has('sales.create') ? '/admin/sales/create' : ''"
            action-label="New Sale"
        />

        <div class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-5">
            <input v-model="form.search" type="search" placeholder="Invoice, ref, customerâ€¦" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="void">Void</option>
            </select>
            <input v-model="form.from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters" />
            <input v-model="form.to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters" />
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">
                Filter
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Invoice</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Total</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Paid</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Balance</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in sales.data" :key="s.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/sales/${s.id}`" class="font-mono text-xs font-medium text-slate-900 hover:text-slate-600">
                                    {{ s.invoice_no }}
                                </Link>
                                <p class="text-xs text-slate-400">{{ s.ref_id }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(s.sale_date) }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-800">{{ s.customer?.name ?? 'Walk-in' }}</span>
                                <span
                                    v-if="s.customer?.outstanding > 0"
                                    class="mt-0.5 inline-flex w-fit items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                                >
                                    <i class="bi bi-exclamation-triangle text-[10px]"></i>
                                    Owes {{ naira(s.customer.outstanding) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(s.total) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">{{ naira(s.amount_paid) }}</td>
                            <td class="px-4 py-3 text-right" :class="s.balance > 0 ? 'text-amber-700 font-semibold' : 'text-slate-400'">
                                {{ naira(s.balance) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span :class="badgeClass(s.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700')">
                                    {{ s.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!sales.data.length">
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">
                                No sales found. Create your first sale.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="sales" />
            </div>
        </div>
</template>