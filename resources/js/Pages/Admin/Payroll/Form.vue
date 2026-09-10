<script setup>
import { computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payroll: { type: Object, default: null },
    staff: { type: Array, default: () => [] },
    month: { type: String, default: '' },
});

const editing = computed(() => Boolean(props.payroll));

const form = useForm({
    staff_id: props.payroll?.staff_id ?? '',
    period: props.month || (props.payroll ? `${props.payroll.period_year}-${String(props.payroll.period_month).padStart(2, '0')}` : ''),
    base_salary: props.payroll?.base_salary ?? '',
    allowance: props.payroll?.allowance ?? '',
    bonus: props.payroll?.bonus ?? '',
    advance: props.payroll?.advance ?? '',
    deduction: props.payroll?.deduction ?? '',
    notes: props.payroll?.notes ?? '',
});

function onStaffChange() {
    const s = props.staff.find((x) => String(x.id) === String(form.staff_id));
    if (!s) return;
    if (!form.period) {
        const now = new Date();
        form.period = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    }
    if (!editing.value) {
        form.base_salary = s.base_salary;
        form.allowance = Number(s.housing_allowance || 0) + Number(s.transport_allowance || 0) + Number(s.other_allowance || 0);
        form.bonus = '';
        form.advance = '';
        form.deduction = '';
    }
}

const netPay = computed(() => {
    const base = Number(form.base_salary || 0);
    const allowance = Number(form.allowance || 0);
    const bonus = Number(form.bonus || 0);
    const advance = Number(form.advance || 0);
    const deduction = Number(form.deduction || 0);
    return base + allowance + bonus - advance - deduction;
});

function submit() {
    const data = {
        staff_id: form.staff_id,
        period_month: form.period ? Number(form.period.split('-')[1]) : null,
        period_year: form.period ? form.period.split('-')[0] : null,
        base_salary: form.base_salary,
        allowance: form.allowance,
        bonus: form.bonus,
        advance: form.advance,
        deduction: form.deduction,
        notes: form.notes,
    };
    if (editing.value) {
        form.put(`/admin/payroll/${props.payroll.id}`, data);
    } else {
        form.post('/admin/payroll', data);
    }
}
</script>

<template>
        <FlashMessages />
        <div class="mb-4 flex items-center gap-2">
            <Link :href="editing ? `/admin/payroll/${payroll.id}` : '/admin/payroll'" class="text-sm text-slate-500 hover:text-slate-900">← Back to payroll</Link>
        </div>

        <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">{{ editing ? `Edit Payroll ${payroll.ref_id}` : 'New Payroll Run' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Net pay = base + allowance + bonus − advance − deduction. Calculated server-side.</p>

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Staff member</label>
                        <select v-model="form.staff_id" required :disabled="editing" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="onStaffChange">
                            <option value="">Select staff…</option>
                            <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }} — {{ s.position || s.ref_id }}</option>
                        </select>
                        <p v-if="form.errors.staff_id" class="mt-1 text-xs text-red-600">{{ form.errors.staff_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Period</label>
                        <input v-model="form.period" type="month" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="form.errors.period_month || form.errors.period_year" class="mt-1 text-xs text-red-600">Choose a valid period.</p>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="grid gap-4 sm:grid-cols-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-600">Base salary</label>
                            <input v-model="form.base_salary" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600">Allowance</label>
                            <input v-model="form.allowance" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-emerald-700">Bonus</label>
                            <input v-model="form.bonus" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-amber-700">Advance (−)</label>
                            <input v-model="form.advance" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-red-700">Deduction (−)</label>
                            <input v-model="form.deduction" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>
                    <p v-if="form.errors.amount" class="mt-2 text-xs text-red-600">{{ form.errors.amount }}</p>

                    <div class="mt-4 flex items-center justify-between rounded-lg bg-white px-4 py-3 ring-1 ring-slate-200">
                        <span class="text-sm font-medium text-slate-600">Net pay estimate</span>
                        <span class="text-2xl font-bold" :class="netPay < 0 ? 'text-red-700' : 'text-slate-900'">{{ naira(netPay) }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                        {{ editing ? 'Save Changes' : 'Prepare Payroll' }}
                    </button>
                    <Link :href="editing ? `/admin/payroll/${payroll.id}` : '/admin/payroll'" class="text-sm text-slate-500 hover:text-slate-900">Cancel</Link>
                </div>
            </form>
        </div>
</template>