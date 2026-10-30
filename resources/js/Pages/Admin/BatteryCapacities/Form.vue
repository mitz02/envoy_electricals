<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    capacity: { type: Object, default: null },
});

const editing = computed(() => !!props.capacity);

const form = useForm({
    name: props.capacity?.name ?? '',
    label: props.capacity?.label ?? '',
    sort_order: props.capacity?.sort_order ?? 0,
    is_active: props.capacity?.is_active ?? true,
});

function submit() {
    if (editing.value) {
        form.put(`/admin/battery-capacities/${props.capacity.id}`);
    } else {
        form.post('/admin/battery-capacities');
    }
}
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/battery-capacities" class="text-sm text-slate-500 hover:text-slate-900">← Back to capacities</Link>
    </div>

    <div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900">{{ editing ? 'Edit Battery Capacity' : 'New Battery Capacity' }}</h1>
        <p class="mt-1 text-sm text-slate-500">Define a battery capacity option for solar packages.</p>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 100ah" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Label <span class="text-red-500">*</span></label>
                    <input v-model="form.label" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 100 Ah" />
                    <p v-if="form.errors.label" class="mt-1 text-xs text-red-600">{{ form.errors.label }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Sort Order</label>
                    <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="0" />
                    <p v-if="form.errors.sort_order" class="mt-1 text-xs text-red-600">{{ form.errors.sort_order }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Status</label>
                    <select v-model="form.is_active" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    {{ editing ? 'Save Changes' : 'Create Capacity' }}
                </button>
                <Link href="/admin/battery-capacities" class="text-sm text-slate-500 hover:text-slate-900">Cancel</Link>
            </div>
        </form>
    </div>
</template>