<script setup>
import { computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    project: { type: Object, default: null },
    customers: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.project);

const form = useForm({
    name: props.project?.name ?? '',
    description: props.project?.description ?? '',
    customer_id: props.project?.customer_id ?? '',
    customer_address: props.project?.customer_address ?? '',
    location: props.project?.location ?? '',
    contract_value: props.project?.contract_value ?? '',
    status: props.project?.status ?? 'draft',
    start_date: props.project?.start_date ?? '',
    expected_completion_date: props.project?.expected_completion_date ?? '',
    assigned_user_id: props.project?.assigned_user_id ?? '',
    notes: props.project?.notes ?? '',
});

watch(
    () => form.customer_id,
    (id) => {
        const c = props.customers.find((c) => String(c.id) === String(id));
        if (c && c.address) {
            form.customer_address = c.address;
        }
    },
);

function submit() {
    if (isEdit.value) {
        form.put(`/admin/projects/${props.project.id}`);
    } else {
        form.post('/admin/projects');
    }
}

function roleLabel(user) {
    const role = user.role_name || user.role?.name;
    return role ? ` · ${role}` : '';
}
</script>

<template>
        <FlashMessages />
        <PageHeader :title="isEdit ? `Edit ${project.ref_id} — ${project.name}` : 'New Project'" :subtitle="isEdit ? `Started ${formatDate(project.start_date)}` : 'Create a solar or electrical installation project.'" />

        <form class="space-y-5" @submit.prevent="submit">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Project Information</h2>
                <div class="grid gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Project name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Customer</label>
                        <select v-model="form.customer_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">— No customer —</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <div v-if="form.errors.customer_id" class="mt-1 text-xs text-red-600">{{ form.errors.customer_id }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Location</label>
                        <input v-model="form.location" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Customer address</label>
                        <input v-model="form.customer_address" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Contract value (₦) <span class="text-red-500">*</span></label>
                        <input v-model="form.contract_value" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.contract_value" class="mt-1 text-xs text-red-600">{{ form.errors.contract_value }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                        <select v-model="form.status" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option v-for="s in statuses" :key="s" :value="s">{{ s.replace('_', ' ') }}</option>
                        </select>
                        <div v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Start date</label>
                        <input v-model="form.start_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Expected completion date</label>
                        <input v-model="form.expected_completion_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.expected_completion_date" class="mt-1 text-xs text-red-600">{{ form.errors.expected_completion_date }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Assigned staff</label>
                        <select v-model="form.assigned_user_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">— Unassigned —</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}{{ roleLabel(u) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex gap-2 text-xs text-slate-400">
                    <span v-if="isEdit">Ref: {{ project.ref_id }} · Started: {{ formatDate(project.start_date) }}</span>
                </div>
                <button type="submit" class="rounded-lg bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50" :disabled="form.processing">
                    {{ isEdit ? 'Save Changes' : 'Create Project' }}
                </button>
            </div>
        </form>
</template>