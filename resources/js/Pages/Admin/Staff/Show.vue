<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    staff: { type: Object, required: true },
    payrolls: { type: Object, required: true },
});

const { has } = useCan();
const canManage = has('staff.manage');

const form = useForm({});

function remove() {
    if (!confirm(`Remove ${props.staff.name}'s staff record? Payroll history is kept.`)) {
        return;
    }
    form.delete(`/admin/staff/${props.staff.id}`);
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
            <Link href="/admin/staff" class="text-sm text-slate-500 hover:text-slate-900">← Back to staff</Link>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ staff.ref_id }}</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ staff.name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ staff.position || 'No position' }}</p>
                <div class="mt-2">
                    <span :class="badgeClass(staff.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">
                        {{ staff.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <dl class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="font-medium text-slate-900">{{ staff.phone || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-900">{{ staff.email || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Date joined</dt><dd class="font-medium text-slate-900">{{ staff.date_joined ? formatDate(staff.date_joined) : '—' }}</dd></div>
                </dl>

                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Branch access</p>
                    <div v-if="staff.user?.stores?.length" class="mt-2 flex flex-wrap gap-1.5">
                        <span
                            v-for="store in staff.user.stores"
                            :key="store.id"
                            class="rounded-full bg-[#0D1527] px-2.5 py-1 text-xs font-medium text-white"
                        >{{ store.name }}</span>
                    </div>
                    <p v-else class="mt-1.5 text-sm text-slate-500">
                        <i class="bi bi-unlock me-1 text-emerald-600" />Can see every branch.
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        {{ staff.user ? 'This login is limited to the branches ticked above. Everything else is hidden.' : 'This staff record has no login account yet.' }}
                    </p>
                </div>

                <div class="mt-4 flex gap-2">
                    <Link v-if="canManage" :href="`/admin/staff/${staff.id}/edit`" class="rounded-lg bg-[#0D1527] px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Edit Profile</Link>
                    <button v-if="canManage" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50" @click="remove">Remove</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Compensation</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Base salary</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ naira(staff.base_salary) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Allowances</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ naira(staff.total_allowance) }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3">
                        <p class="text-xs text-emerald-600">Total paid</p>
                        <p class="mt-1 text-lg font-bold text-emerald-700">{{ naira(staff.total_paid) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Bonuses</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ naira(staff.total_bonuses) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Advances</p>
                        <p class="mt-1 text-lg font-bold text-amber-700">{{ naira(staff.total_advances) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Deductions</p>
                        <p class="mt-1 text-lg font-bold text-red-700">{{ naira(staff.total_deductions) }}</p>
                    </div>
                </div>

                <h2 class="mb-3 mt-8 text-sm font-semibold uppercase tracking-wide text-slate-500">Payroll History</h2>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Ref</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Period</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-600">Gross</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-600">Net Paid</th>
                                <th class="px-3 py-2 text-center font-semibold text-slate-600">Status</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in payrolls.data" :key="p.id" class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ p.ref_id }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ p.period_label }}</td>
                                <td class="px-3 py-2 text-right text-slate-700">{{ naira(p.base_salary + p.allowance + p.bonus) }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ naira(p.amount_paid) }}</td>
                                <td class="px-3 py-2 text-center"><span :class="badgeClass(statusClasses[p.status])">{{ p.status }}</span></td>
                                <td class="px-3 py-2 text-right">
                                    <Link :href="`/admin/payroll/${p.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                </td>
                            </tr>
                            <tr v-if="!payrolls.data.length">
                                <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-400">No payroll runs yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <Pagination :meta="payrolls" />
                </div>
            </div>
        </div>
</template>