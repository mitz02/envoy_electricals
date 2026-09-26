<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, maskNaira, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payrolls: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    staff: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('payroll.manage');

const form = useForm({
    search: props.filters.search ?? '',
    staff_id: props.filters.staff_id ?? '',
    status: props.filters.status ?? '',
    period: props.filters.period_year && props.filters.period_month
        ? `${props.filters.period_year}-${String(props.filters.period_month).padStart(2, '0')}`
        : '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.staff_id) p.set('staff_id', form.staff_id);
    if (form.status) p.set('status', form.status);
    if (form.period) {
        const [y, m] = form.period.split('-');
        p.set('period_year', y);
        p.set('period_month', m);
    }
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
        <PageHeader
            title="Payroll"
            subtitle="Prepare and pay staff payroll runs."
            :action-href="canManage ? '/admin/payroll/create' : ''"
            action-label="New Payroll Run"
        />

        <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-6">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Runs</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.count }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Gross</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ maskNaira('salary', summary.gross) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Bonuses</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ maskNaira('salary', summary.bonus) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Net Payable</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ maskNaira('salary', summary.net_payable) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Paid</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ maskNaira('salary', summary.paid) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Advances</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ maskNaira('salary', summary.advance) }}</p>
            </div>
        </div>

        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
            <input v-model="form.search" type="search" placeholder="Search staff name…" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            <select v-model="form.staff_id" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                <option value="">All staff</option>
                <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
            </select>
            <input v-model="form.period" type="month" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Staff</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Period</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Gross</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Bonus</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Advances</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Deductions</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Net Pay</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in payrolls.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/payroll/${p.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ p.staff?.name }}</Link>
                                <p class="text-xs text-slate-400">{{ p.ref_id }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ p.period_label }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ maskNaira('salary', p.base_salary + p.allowance) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ maskNaira('salary', p.bonus) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ maskNaira('salary', p.advance) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ maskNaira('salary', p.deduction) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ maskNaira('salary', p.amount_paid) }}</td>
                            <td class="px-4 py-3 text-center"><span :class="badgeClass(statusClasses[p.status])">{{ p.status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="`/admin/payroll/${p.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                <Link v-if="canManage && p.status === 'pending'" :href="`/admin/payroll/${p.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!payrolls.data.length">
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-400">No payroll runs found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="payrolls" />
            </div>
        </div>
</template>