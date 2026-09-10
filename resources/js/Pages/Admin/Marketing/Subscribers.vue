<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDateTime, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    subscribers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

const actionForm = useForm({});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.status) p.set('status', form.status);
    window.location.search = p.toString();
}

function toggle(s) {
    actionForm.post(`/admin/marketing/subscribers/${s.id}/toggle`, { preserveScroll: true });
}

function remove(s) {
    if (!confirm(`Remove ${s.email} from the subscriber list?`)) {
        return;
    }
    actionForm.delete(`/admin/marketing/subscribers/${s.id}`, { preserveScroll: true });
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Newsletter Subscribers"
        subtitle="Everyone signed up for Envoy Electric updates."
    />

    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input v-model="form.search" type="search" placeholder="Search email or nameâ€¦" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All statuses</option>
            <option value="subscribed">Subscribed</option>
            <option value="unsubscribed">Unsubscribed</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Subscribed</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="s in subscribers.data" :key="s.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ s.email }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ s.name || 'â€”' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="badgeClass(s.status === 'subscribed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">{{ s.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ formatDateTime(s.created_at) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <button class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100" @click="toggle(s)">{{ s.status === 'subscribed' ? 'Unsubscribe' : 'Resubscribe' }}</button>
                                <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="remove(s)">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!subscribers.data.length">
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">No subscribers found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="subscribers" />
        </div>
    </div>
</template>