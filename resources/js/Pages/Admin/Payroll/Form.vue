<script setup>
import { computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
    period_month: null,
    period_year: null,
    base_salary: props.payroll?.base_salary ?? '',
    allowance: props.payroll?.allowance ?? '',
    bonus: props.payroll?.bonus ?? '',
    advance: props.payroll?.advance ?? '',
    deduction: props.payroll?.deduction ?? '',
    notes: props.payroll?.notes ?? '',
});

watch(form.period, (v) => {
    if (v) {
        const [year, month] = v.split('-');
        form.period_year = year;
        form.period_month = Number(month);
    }
}, { immediate: true });

const earnings = computed(() => Number(form.base_salary || 0) + Number(form.allowance || 0) + Number(form.bonus || 0));
const deductions = computed(() => Number(form.advance || 0) + Number(form.deduction || 0));
const netPay = computed(() => earnings.value - deductions.value);

const periodError = computed(
    () => form.errors.period || form.errors.period_month || form.errors.period_year,
);

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

function submit() {
    if (!form.period) {
        form.setError('period', 'Choose a pay period.');
        return;
    }
    if (editing.value) {
        form.put(`/admin/payroll/${props.payroll.id}`);
    } else {
        form.post('/admin/payroll');
    }
}
</script>

<template>
        <FlashMessages />
        <div class="mb-4">
            <Link
                :href="editing ? `/admin/payroll/${payroll.id}` : '/admin/payroll'"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900"
            >
                <i class="bi bi-arrow-left"></i>
                Back to payroll
            </Link>
        </div>

        <PageHeader
            :title="editing ? `Edit ${payroll.ref_id}` : 'New Payroll Run'"
            subtitle="Net pay = base + allowance + bonus − advance − deduction."
        />

        <form class="space-y-5" @submit.prevent="submit">
            <!-- Staff & period -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0D1527] text-white">
                        <i class="bi bi-person-fill text-sm"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Staff &amp; Pay Period</h2>
                        <p class="text-xs text-slate-400">Who is being paid, and for which month.</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Staff member</label>
                        <select v-model="form.staff_id" :disabled="editing" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="onStaffChange">
                            <option value="">Select staff…</option>
                            <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }} — {{ s.position || s.ref_id }}</option>
                        </select>
                        <p v-if="form.errors.staff_id" class="mt-1 text-xs text-red-600">{{ form.errors.staff_id }}</p>
                        <p v-if="!editing" class="mt-1 text-xs text-slate-400">Salary &amp; allowances auto-fill from the staff profile.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Pay period</label>
                        <input v-model="form.period" type="month" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="periodError" class="mt-1 text-xs text-red-600">{{ periodError }}</p>
                        <p v-else class="mt-1 text-xs text-slate-400">Example: {{ new Date().getFullYear() }}-{{ String(new Date().getMonth() + 1).padStart(2, '0') }}</p>
                    </div>
                </div>
            </div>

            <!-- Salary components -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-400 text-slate-950">
                        <i class="bi bi-cash-coin text-sm"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Salary Components</h2>
                        <p class="text-xs text-slate-400">Earnings build up, advances and deductions come off.</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid grid-cols-3 gap-3 sm:col-span-2 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Base salary (₦)</label>
                            <input v-model="form.base_salary" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Allowance (₦)</label>
                            <input v-model="form.allowance" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-emerald-700">Bonus (₦)</label>
                            <input v-model="form.bonus" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-red-700">Advance (₦) −</label>
                        <input v-model="form.advance" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-red-700">Deduction (₦) −</label>
                        <input v-model="form.deduction" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>

                <p v-if="form.errors.amount" class="mt-2 text-xs text-red-600">{{ form.errors.amount }}</p>

                <div class="mt-4 overflow-hidden rounded-xl bg-gradient-to-r from-[#0D1527] to-[#1A365D] px-5 py-4 text-white">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <p class="text-xs text-slate-300">Net pay estimate</p>
                            <p class="text-[10px] text-slate-400">+ {{ naira(earnings) }} − {{ naira(deductions) }}</p>
                        </div>
                        <p class="text-3xl font-black tracking-tight" :class="netPay < 0 ? 'text-red-400' : ''">{{ naira(netPay) }}</p>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="mb-3 flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <i class="bi bi-sticky text-sm"></i>
                    </span>
                    <h2 class="text-sm font-semibold text-slate-900">Notes</h2>
                </div>
                <textarea v-model="form.notes" rows="2" placeholder="Anything to remember about this run…" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-400">
                    {{ editing ? 'Edits are locked once a run is paid.' : 'Creating a run prepares it — you mark it paid afterwards.' }}
                </p>
                <div class="flex items-center gap-3">
                    <Link :href="editing ? `/admin/payroll/${payroll.id}` : '/admin/payroll'" class="text-sm font-medium text-slate-500 transition hover:text-slate-900">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                    >
                        <i class="bi bi-check-lg"></i>
                        {{ editing ? 'Save Changes' : 'Prepare Payroll' }}
                    </button>
                </div>
            </div>
        </form>
</template>