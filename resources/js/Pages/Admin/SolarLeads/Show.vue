<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira, formatDate, formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    record: { type: Object, required: true },
    type: { type: String, required: true },
    statuses: { type: Array, default: () => [] },
});

const { has } = useCan();
const canManage = has('solar.leads');

const isCalc = computed(() => props.type === 'calculation');
const statusKey = computed(() => (isCalc.value ? 'lead_status' : 'status'));

const form = useForm({});
const statusForm = useForm({ status: props.record[statusKey.value] });

function saveStatus() {
    statusForm
        .transform(() => ({ type: isCalc.value ? 'calculation' : 'quotation', id: props.record.id, status: statusForm.status }))
        .post('/admin/solar-leads/status', { preserveScroll: true });
}

function remove() {
    if (!confirm(`Remove ${props.record.ref_id}?`)) {
        return;
    }
    const base = isCalc.value ? '/admin/solar-leads' : '/admin/solar-leads/quotations';
    form.delete(`${base}/${props.record.id}`);
}

const statusBadge = {
    new: 'bg-slate-100 text-slate-600',
    contacted: 'bg-sky-100 text-sky-700',
    quoted: 'bg-blue-100 text-blue-700',
    approved: 'bg-emerald-100 text-emerald-800',
    installation: 'bg-amber-100 text-amber-800',
    completed: 'bg-emerald-100 text-emerald-800',
    declined: 'bg-red-100 text-red-700',
    converted: 'bg-emerald-100 text-emerald-800',
    lost: 'bg-slate-100 text-slate-500',
};

const appliances = computed(() => {
    const raw = props.record?.appliances_json;
    if (Array.isArray(raw)) return raw;
    if (raw && typeof raw === 'object') return Object.values(raw);
    return [];
});
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/solar-leads" class="text-sm text-slate-500 hover:text-slate-900">← Back to solar leads</Link>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Left column: lead profile -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs" v-if="isCalc">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ record.ref_id }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ record.customer_name || 'Anonymous Lead' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ record.location || 'No location' }}</p>

            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="font-medium text-slate-900">{{ record.customer_phone || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-900 truncate">{{ record.customer_email || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Submitted</dt><dd class="font-medium text-slate-900">{{ formatDateTime(record.created_at) }}</dd></div>
            </dl>

            <div class="my-5 space-y-2 text-sm">
                <div class="flex justify-between rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Connected load</span><span class="font-bold text-slate-900">{{ record.total_connected_load }} W</span></div>
                <div class="flex justify-between rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Daily consumption</span><span class="font-bold text-slate-900">{{ record.daily_consumption_kwh }} kWh</span></div>
                <div class="flex justify-between rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Peak load</span><span class="font-bold text-slate-900">{{ record.peak_load_kw }} kW</span></div>
                <div class="flex justify-between rounded-lg bg-emerald-50 p-3"><span class="text-emerald-600">Estimated price</span><span class="font-bold text-emerald-700">{{ record.estimated_price ? naira(record.estimated_price) : '—' }}</span></div>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                <span class="text-sm font-medium text-slate-600">Status</span>
                <select v-model="statusForm.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>

            <div class="mt-4 flex gap-2">
                <button :disabled="statusForm.processing" class="rounded-lg bg-[#0D1527] px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50" @click="saveStatus">Update Status</button>
                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50" @click="remove">Remove</button>
            </div>
        </div>

        <!-- Quotation profile -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs" v-else>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ record.ref_id }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ record.customer_name || 'Anonymous Quotation' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ record.location || 'No location' }}</p>

            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="font-medium text-slate-900">{{ record.customer_phone || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-900 truncate">{{ record.customer_email || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Recommended system</dt><dd class="font-medium text-slate-900 text-right">{{ record.recommended_system || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Created</dt><dd class="font-medium text-slate-900">{{ formatDate(record.created_at) }}</dd></div>
            </dl>

            <div class="my-5 space-y-2 text-sm">
                <div class="flex justify-between rounded-lg bg-emerald-50 p-3"><span class="text-emerald-600">Estimated price</span><span class="font-bold text-emerald-700">{{ record.estimated_price ? naira(record.estimated_price) : '—' }}</span></div>
                <div class="flex justify-between rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Linked package</span><span class="font-bold text-slate-900">{{ record.solar_package?.name || '—' }}</span></div>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                <span class="text-sm font-medium text-slate-600">Status</span>
                <select v-model="statusForm.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>

            <div class="mt-4 flex gap-2">
                <button :disabled="statusForm.processing" class="rounded-lg bg-[#0D1527] px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50" @click="saveStatus">Update Status</button>
                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50" @click="remove">Remove</button>
            </div>
        </div>

        <!-- Right column: appliances -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Calculated Appliances</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ appliances.length }}</span>
            </div>

            <div class="mt-4 space-y-2">
                <div
                    v-for="(app, idx) in appliances"
                    :key="idx"
                    class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">{{ app.name || app.appliance || 'Item' }}</p>
                        <p class="text-[11px] text-slate-400">
                            {{ app.quantity ?? app.qty }} × {{ app.watts || app.wattage || 0 }}W
                            <template v-if="app.hours"> · {{ app.hours }} hrs/day</template>
                        </p>
                    </div>
                    <span class="text-sm font-bold text-slate-900">{{ naira(app.cost || app.estimated_cost || 0) }}</span>
                </div>
                <p v-if="!appliances.length" class="py-8 text-center text-sm text-slate-400">No appliance data recorded.</p>
            </div>

            <div v-if="record.notes" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Notes</p>
                {{ record.notes }}
            </div>
        </div>
    </div>
</template>