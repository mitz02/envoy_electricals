<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    fields: { type: Object, required: true },
    values: { type: Object, required: true },
    mediaCount: { type: Number, default: 0 },
});

const form = useForm({ values: { ...props.values } });

function save() {
    form.post('/admin/website', { preserveScroll: true });
}

const groupLabels = {
    business: 'Business Details',
    website: 'Website Content',
    sales: 'Sales & Inventory',
};

function inputType(kind) {
    return { text: 'text', email: 'email', number: 'number', textarea: 'textarea', boolean: 'checkbox' }[kind] ?? 'text';
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Website Settings"
        subtitle="Control the public Envoy Electric website content."
    />

    <div class="mb-4 flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <p class="text-sm text-slate-600">Manage media library ({{ mediaCount }} files)</p>
        <Link href="/admin/media" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90">Open Media Library</Link>
    </div>

    <form class="max-w-3xl space-y-5" @submit.prevent="save">
        <div
            v-for="(group, groupKey) in fields"
            :key="groupKey"
            class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs"
        >
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ groupLabels[groupKey] || groupKey }}</h2>
            <div class="space-y-4">
                <div v-for="(kind, key) in group" :key="key">
                    <label class="flex items-center justify-between text-sm font-medium text-slate-700">
                        <span>{{ key.replace(/\./g, ' ').replace(/(^|\s)\S/g, (c) => c.toUpperCase()) }}</span>
                        <input
                            v-if="kind === 'boolean'"
                            v-model="form.values[key]"
                            type="checkbox"
                            class="h-4 w-4 rounded accent-amber-500"
                        />
                    </label>
                    <template v-if="kind !== 'boolean'">
                        <textarea
                            v-if="kind === 'textarea'"
                            v-model="form.values[key]"
                            rows="3"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <input
                            v-else
                            v-model="form.values[key]"
                            :type="inputType(kind)"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                    </template>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                Save Settings
            </button>
        </div>
    </form>
</template>