<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    asset: { type: Object, default: null },
    statuses: { type: Array, default: () => [] },
});

const editing = computed(() => Boolean(props.asset));

const form = useForm({
    name: props.asset?.name ?? '',
    category: props.asset?.category ?? '',
    purchase_date: props.asset?.purchase_date ? props.asset.purchase_date.substring(0, 10) : '',
    purchase_cost: props.asset?.purchase_cost ?? '',
    current_value: props.asset?.current_value ?? '',
    serial_number: props.asset?.serial_number ?? '',
    location: props.asset?.location ?? '',
    condition: props.asset?.condition ?? '',
    status: props.asset?.status ?? 'active',
    notes: props.asset?.notes ?? '',
});

function submit() {
    if (editing.value) {
        form.put(`/admin/assets/${props.asset.id}`);
    } else {
        form.post('/admin/assets');
    }
}
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/assets" class="text-sm text-slate-500 hover:text-slate-900">← Back to assets</Link>
    </div>

    <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900">{{ editing ? 'Edit Asset' : 'Add Asset' }}</h1>
        <p class="mt-1 text-sm text-slate-500">Record a new company asset or update its details.</p>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Asset name</label>
                    <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Toyota Hilux, Solar Panel Kit" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Category</label>
                    <input v-model="form.category" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Vehicle, Equipment, Tool" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Serial number</label>
                    <input v-model="form.serial_number" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Purchase date</label>
                    <input v-model="form.purchase_date" type="date" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Location</label>
                    <input v-model="form.location" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Main office, Ikeja warehouse" />
                </div>
            </div>

            <!-- Valuation Section -->
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="mb-3 text-sm font-semibold text-slate-700">Valuation</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-600">Purchase cost (₦)</label>
                        <input v-model="form.purchase_cost" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="form.errors.purchase_cost" class="mt-1 text-xs text-red-600">{{ form.errors.purchase_cost }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600">Current value (₦)</label>
                        <input v-model="form.current_value" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="form.errors.current_value" class="mt-1 text-xs text-red-600">{{ form.errors.current_value }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600">Condition</label>
                        <select v-model="form.condition" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">Not set</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-slate-700">Status</label>
                <select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                    <option v-for="s in statuses" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
                </select>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-slate-700">Notes</label>
                <textarea v-model="form.notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Warranty info, maintenance schedule, etc." />
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    {{ editing ? 'Save Changes' : 'Add Asset' }}
                </button>
                <Link href="/admin/assets" class="text-sm text-slate-500 hover:text-slate-900">Cancel</Link>
            </div>
        </form>
    </div>
</template>
