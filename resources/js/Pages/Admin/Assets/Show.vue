<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira, formatDate, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    asset: { type: Object, required: true },
    maintenances: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const { has } = useCan();
const canManage = has('assets.manage');

const form = useForm({});
const maintenanceForm = useForm({
    maintenance_date: '',
    type: '',
    description: '',
    cost: '',
});

function remove() {
    if (!confirm(`Remove ${props.asset.name} from the assets register?`)) {
        return;
    }
    form.delete(`/admin/assets/${props.asset.id}`);
}

function saveMaintenance() {
    maintenanceForm.post(`/admin/assets/${props.asset.id}/maintenance`, {
        preserveScroll: true,
        onSuccess: () => maintenanceForm.reset(),
    });
}

function removeMaintenance(m) {
    if (!confirm('Remove this maintenance record?')) {
        return;
    }
    form.delete(`/admin/assets/${props.asset.id}/maintenance/${m.id}`, { preserveScroll: true });
}

const statusBadge = {
    active: 'bg-emerald-100 text-emerald-800',
    inactive: 'bg-slate-100 text-slate-500',
    disposed: 'bg-red-100 text-red-700',
};

const conditionBadge = {
    excellent: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    good: 'bg-sky-50 text-sky-700 border border-sky-200',
    fair: 'bg-amber-50 text-amber-700 border border-amber-200',
    poor: 'bg-red-50 text-red-700 border border-red-200',
};
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/assets" class="text-sm text-slate-500 hover:text-slate-900">← Back to assets</Link>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Left column: Asset profile -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ asset.ref_id }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ asset.name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ asset.category || 'Uncategorised' }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span :class="badgeClass(statusBadge[asset.status] || 'bg-slate-100 text-slate-500')">{{ asset.status }}</span>
                <span v-if="asset.condition" :class="conditionBadge[asset.condition] || 'bg-slate-50 text-slate-600 border border-slate-200'">{{ asset.condition }}</span>
            </div>

            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Serial number</dt><dd class="font-medium text-slate-900">{{ asset.serial_number || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Location</dt><dd class="font-medium text-slate-900">{{ asset.location || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Purchase date</dt><dd class="font-medium text-slate-900">{{ asset.purchase_date ? formatDate(asset.purchase_date) : '—' }}</dd></div>
            </dl>

            <div class="my-5 space-y-2 text-sm">
                <div class="flex justify-between rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Purchase cost</span><span class="font-bold text-slate-900">{{ naira(asset.purchase_cost) }}</span></div>
                <div class="flex justify-between rounded-lg bg-emerald-50 p-3"><span class="text-emerald-600">Current value</span><span class="font-bold text-emerald-700">{{ naira(asset.current_value) }}</span></div>
            </div>

            <p v-if="asset.notes" class="rounded-lg border border-slate-200 p-3 text-sm text-slate-600">{{ asset.notes }}</p>

            <div class="mt-4 flex gap-2">
                <Link v-if="canManage" :href="`/admin/assets/${asset.id}/edit`" class="rounded-lg bg-[#0D1527] px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Edit Asset</Link>
                <button v-if="canManage" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50" @click="remove">Remove</button>
            </div>
        </div>

        <!-- Right column: Maintenance -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Maintenance Records</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ maintenances.length }}</span>
            </div>

            <!-- Add maintenance form -->
            <form class="mt-4 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2" @submit.prevent="saveMaintenance">
                <div>
                    <label class="block text-xs font-medium text-slate-600">Date</label>
                    <input v-model="maintenanceForm.maintenance_date" type="date" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <p v-if="maintenanceForm.errors.maintenance_date" class="mt-1 text-xs text-red-600">{{ maintenanceForm.errors.maintenance_date }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Type</label>
                    <input v-model="maintenanceForm.type" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Servicing, Repair" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-600">Description</label>
                    <textarea v-model="maintenanceForm.description" rows="2" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <p v-if="maintenanceForm.errors.description" class="mt-1 text-xs text-red-600">{{ maintenanceForm.errors.description }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600">Cost (₦)</label>
                    <input v-model="maintenanceForm.cost" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <p v-if="maintenanceForm.errors.cost" class="mt-1 text-xs text-red-600">{{ maintenanceForm.errors.cost }}</p>
                </div>
                <div class="flex items-end">
                    <button type="submit" :disabled="maintenanceForm.processing" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">Add Record</button>
                </div>
            </form>

            <!-- Maintenance list -->
            <div class="mt-4 space-y-2">
                <div
                    v-for="m in maintenances"
                    :key="m.id"
                    class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50/50 p-3"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-800">{{ m.type || 'Maintenance' }}</span>
                            <span class="rounded-md bg-slate-200/70 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">{{ formatDate(m.maintenance_date) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ m.description }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <span class="text-sm font-bold text-slate-900">{{ naira(m.cost) }}</span>
                        <button v-if="canManage" class="rounded-md p-1 text-slate-400 hover:text-red-600" @click="removeMaintenance(m)">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
                <p v-if="!maintenances.length" class="py-8 text-center text-sm text-slate-400">No maintenance records yet.</p>
            </div>
        </div>
    </div>
</template>