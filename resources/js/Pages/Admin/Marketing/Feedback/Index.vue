<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDateTime, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    feedback: { type: Object, required: true },
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

function remove(f) {
    if (!confirm(`Remove feedback #${f.id}?`)) {
        return;
    }
    actionForm.delete(`/admin/marketing/feedback/${f.id}`, { preserveScroll: true });
}

const statusBadge = {
    new: 'bg-amber-100 text-amber-800',
    reviewed: 'bg-sky-100 text-sky-700',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-700',
};

function stars(rating) {
    return 'â˜…'.repeat(rating || 0) + 'â˜†'.repeat(Math.max(0, 5 - (rating || 0)));
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Customer Feedback"
        subtitle="Ratings, reviews and suggestions from customers."
    />

    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input v-model="form.search" type="search" placeholder="Search name, email or commentâ€¦" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All statuses</option>
            <option value="new">New</option>
            <option value="reviewed">Reviewed</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Comment</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Rating</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="f in feedback.data" :key="f.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ f.customer_name || 'Anonymous' }}</p>
                            <p class="text-xs text-slate-400">{{ f.customer_email || 'â€”' }}</p>
                        </td>
                        <td class="px-4 py-3 max-w-xs truncate text-slate-600" :title="f.comment || f.experience || ''">{{ f.comment || f.experience || 'â€”' }}</td>
                        <td class="px-4 py-3 text-center text-amber-500">{{ stars(f.rating) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="badgeClass(statusBadge[f.status] || 'bg-slate-100 text-slate-500')">{{ f.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ formatDateTime(f.created_at) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Link :href="`/admin/marketing/feedback/${f.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Review</Link>
                                <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="remove(f)">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!feedback.data.length">
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No feedback found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="feedback" />
        </div>
    </div>
</template>