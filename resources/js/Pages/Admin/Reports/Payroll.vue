<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    rows: { type: Object, required: true },
    by_staff: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    staff: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    period: props.filters.period_year && props.filters.period_month
        ? `${props.filters.period_year}-${String(props.filters.period_month).padStart(2, '0')}`
        : '',
    staff_id: props.filters.staff_id ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.period) {
        const [y, m] = form.period.split('-');
        p.set('period_year', y);
        p.set('period_month', m);
    }
    if (form.staff_id) p.set('staff_id', form.staff_id);
    window.location.search = p.toString();
}

const statusClasses = {
    pending: 'bg-amber-100 text-amber-800',
    paid: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-700',
};
</script>

<template>
        <FlashMessages />
        <PageHeader title="Payroll Report" subtitle="Staff payroll, bonuses, advances and deductions by period." />

        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
            <input v-model="form.period" type="month" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <select v-model="form.staff_id" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                <option value="">All staff</option>
                <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
            <Link
                :href="`/admin/reports/export?type=payroll&period_year=${form.period ? form.period.split('-')[0] : ''}&period_month=${form.period ? form.period.split('-')[1] : ''}`"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Export CSV
            </Link>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-6">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Runs</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.count }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Gross Pay</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.gross) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Bonuses</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ naira(summary.bonus) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Net Payable</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.net_payable) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Paid</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ naira(summary.paid) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Advances</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ naira(summary.advance) }}</p>
            </div>
        </div>

        <div class="mb-4 grid gap-4 lg:grid-cols-4">
            <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs lg:col-span-2">
                <p class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">Payroll by Staff</p>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-slate-600">Staff</th>
                            <th class="px-4 py-2 text-right font-semibold text-slate-600">Gross</th>
                            <th class="px-4 py-2 text-right font-semibold text-slate-600">Net</th>
                            <th class="px-4 py-2 text-right font-semibold text-slate-600">Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="b in by_staff" :key="b.staff_id" class="hover:bg-slate-50">
                            <td class="px-4 py-2 font-medium text-slate-900">{{ b.staff?.name || 'â€”' }}</td>
                            <td class="px-4 py-2 text-right text-slate-700">{{ naira(b.base + b.allowance + b.bonus) }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-slate-900">{{ naira(b.amount_paid) }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-emerald-700">{{ naira(b.paid) }}</td>
                        </tr>
                        <tr v-if="!by_staff.length">
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">No payroll for this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs lg:col-span-2">
                <p class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">Payroll Runs</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Ref / Staff</th>
                                <th class="px-4 py-2 text-right font-semibold text-slate-600">Net Pay</th>
                                <th class="px-4 py-2 text-center font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-2 text-right font-semibold text-slate-600"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in rows.data" :key="p.id" class="hover:bg-slate-50">
                                <td class="px-4 py-2">
                                    <p class="font-medium text-slate-900">{{ p.staff?.name }}</p>
                                    <p class="text-xs text-slate-400">{{ p.ref_id }}</p>
                                </td>
                                <td class="px-4 py-2 text-right font-semibold text-slate-900">{{ naira(p.amount_paid) }}</td>
                                <td class="px-4 py-2 text-center"><span :class="badgeClass(statusClasses[p.status])">{{ p.status }}</span></td>
                                <td class="px-4 py-2 text-right">
                                    <Link :href="`/admin/payroll/${p.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                </td>
                            </tr>
                            <tr v-if="!rows.data.length">
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">No payroll runs found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :meta="rows" />
                </div>
            </div>
        </div>
</template>