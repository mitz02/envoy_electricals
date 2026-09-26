<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    staff: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('staff.manage');

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.status) p.set('status', form.status);
    window.location.search = p.toString();
}

const activeBadge = { true: 'bg-emerald-100 text-emerald-800', false: 'bg-slate-100 text-slate-500' };
</script>

<template>
        <FlashMessages />
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-black uppercase tracking-tight text-[#0D1527] sm:text-2xl">Staff</h1>
                <p class="mt-1 text-sm text-slate-500">Staff profiles, payroll and balances.</p>
            </div>
            <Link
                v-if="canManage"
                href="/admin/staff/create"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-yellow-400 px-4 py-2 text-sm font-bold text-[#0D1527] shadow-sm transition hover:bg-yellow-300"
            >
                <i class="bi bi-plus-lg text-base"></i>
                Add New Staff
            </Link>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Staff</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.total }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active Staff</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ summary.active }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Paid This Month</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(summary.month_paid) }}</p>
            </div>
        </div>

        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
            <input v-model="form.search" type="search" placeholder="Search name, position, ref, phone…" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Staff</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Position</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Base Salary</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Total Paid</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Payrolls</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in staff.data" :key="s.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/staff/${s.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ s.name }}</Link>
                                <p class="text-xs text-slate-400">{{ s.ref_id }} · {{ s.email || s.phone || 'No contact' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ s.position || '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(s.base_salary) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ naira(s.total_paid) }}</td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ s.payrolls_count }}</td>
                            <td class="px-4 py-3">
                                <span :class="badgeClass(activeBadge[String(s.is_active)])">{{ s.is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <Link :href="`/admin/staff/${s.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                    <Link v-if="canManage" :href="`/admin/staff/${s.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!staff.data.length">
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">No staff found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="staff" />
            </div>
        </div>
</template>