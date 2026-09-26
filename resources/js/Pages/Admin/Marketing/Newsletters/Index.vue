<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    newsletters: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('marketing.newsletter');

const form = useForm({
    status: props.filters.status ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.status) p.set('status', form.status);
    window.location.search = p.toString();
}

const statusBadge = {
    draft: 'bg-slate-100 text-slate-500',
    scheduled: 'bg-sky-100 text-sky-700',
    sent: 'bg-emerald-100 text-emerald-800',
};
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Newsletters"
        subtitle="Create and send email campaigns to subscribers."
        :action-href="canManage ? '/admin/marketing/newsletters/create' : ''"
        action-label="New Newsletter"
    />

    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="scheduled">Scheduled</option>
            <option value="sent">Sent</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Subject</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Created By</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="n in newsletters.data" :key="n.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="`/admin/marketing/newsletters/${n.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ n.subject }}</Link>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ n.creator?.name || '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="badgeClass(statusBadge[n.status] || 'bg-slate-100 text-slate-500')">{{ n.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ formatDateTime(n.sent_at || n.scheduled_at || n.created_at) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Link :href="`/admin/marketing/newsletters/${n.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                <Link v-if="canManage && n.status !== 'sent'" :href="`/admin/marketing/newsletters/${n.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!newsletters.data.length">
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">No newsletters found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="newsletters" />
        </div>
    </div>
</template>