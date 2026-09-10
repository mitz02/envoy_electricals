<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    staff: { type: Object, default: null },
    users: { type: Array, default: () => [] },
});

const editing = computed(() => Boolean(props.staff));

const form = useForm({
    name: props.staff?.name ?? '',
    position: props.staff?.position ?? '',
    phone: props.staff?.phone ?? '',
    email: props.staff?.email ?? '',
    date_joined: props.staff?.date_joined ?? '',
    base_salary: props.staff?.base_salary ?? '',
    housing_allowance: props.staff?.housing_allowance ?? '',
    transport_allowance: props.staff?.transport_allowance ?? '',
    other_allowance: props.staff?.other_allowance ?? '',
    user_id: props.staff?.user_id ?? '',
    is_active: props.staff ? Boolean(props.staff.is_active) : true,
    notes: props.staff?.notes ?? '',
});

const grossPay = computed(() => {
    const base = Number(form.base_salary || 0);
    const housing = Number(form.housing_allowance || 0);
    const transport = Number(form.transport_allowance || 0);
    const other = Number(form.other_allowance || 0);
    return base + housing + transport + other;
});

function submit() {
    if (editing.value) {
        form.put(`/admin/staff/${props.staff.id}`);
    } else {
        form.post('/admin/staff');
    }
}
</script>

<template>
        <FlashMessages />
        <div class="mb-4 flex items-center gap-2">
            <Link href="/admin/staff" class="text-sm text-slate-500 hover:text-slate-900">← Back to staff</Link>
        </div>

        <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">{{ editing ? 'Edit Staff Profile' : 'New Staff Profile' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Keep staff profiles separate from payroll transactions.</p>

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Full name</label>
                        <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Position</label>
                        <input v-model="form.position" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Date joined</label>
                        <input v-model="form.date_joined" type="date" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Phone</label>
                        <input v-model="form.phone" type="tel" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email</label>
                        <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="mb-3 text-sm font-semibold text-slate-700">Compensation (₦/month)</p>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600">Base salary</label>
                            <input v-model="form.base_salary" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600">Housing allowance</label>
                            <input v-model="form.housing_allowance" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600">Transport allowance</label>
                            <input v-model="form.transport_allowance" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600">Other allowance</label>
                            <input v-model="form.other_allowance" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">Monthly gross pay: <span class="font-bold text-slate-900">{{ naira(grossPay) }}</span></p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Linked login account</label>
                        <select v-model="form.user_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">No linked account</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select v-model="form.is_active" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option :value="true">Active</option>
                            <option :value="false">Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Notes</label>
                    <textarea v-model="form.notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                        {{ editing ? 'Save Changes' : 'Create Staff' }}
                    </button>
                    <Link href="/admin/staff" class="text-sm text-slate-500 hover:text-slate-900">Cancel</Link>
                </div>
            </form>
        </div>
</template>