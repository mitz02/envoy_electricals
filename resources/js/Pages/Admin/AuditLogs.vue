<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDateTime } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    logs: { type: Object, required: true },
});

function actionColor(action) {
    const map = {
        create: 'bg-emerald-100 text-emerald-800',
        update: 'bg-amber-100 text-amber-800',
        delete: 'bg-red-100 text-red-700',
        void: 'bg-red-100 text-red-700',
        login: 'bg-slate-200 text-slate-700',
        logout: 'bg-slate-200 text-slate-700',
    };
    return map[action] ?? 'bg-slate-100 text-slate-700';
}
</script>

<template>
        <FlashMessages />
        <PageHeader title="Audit Log" subtitle="A trail of every action performed in the system." />

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">User</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Resource</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in logs.data" :key="log.id" class="align-top hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ formatDateTime(log.created_at) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ log.user?.name ?? 'System' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="actionColor(log.action)">{{ log.action }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-slate-600">{{ log.model_type }}</span>
                                <span v-if="log.model_id" class="text-slate-400"> #{{ log.model_id }}</span>
                                <span v-if="log.ip" class="ml-2 block text-xs text-slate-400">IP {{ log.ip }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-slate-600">{{ log.description || '—' }}</p>
                                <details v-if="log.changes" class="mt-1">
                                    <summary class="cursor-pointer text-xs font-medium text-slate-400 hover:text-slate-600">changes</summary>
                                    <pre class="mt-1 max-h-40 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-2 text-[10px] text-slate-500">{{ JSON.stringify(log.changes, null, 2) }}</pre>
                                </details>
                            </td>
                        </tr>
                        <tr v-if="!logs.data.length">
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">No audit entries yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="logs" />
            </div>
        </div>
</template>