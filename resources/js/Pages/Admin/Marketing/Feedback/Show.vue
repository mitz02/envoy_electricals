<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { formatDateTime, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    feedback: { type: Object, required: true },
});

const form = useForm({
    status: props.feedback?.status ?? 'new',
    admin_response: props.feedback?.admin_response ?? '',
});

function save() {
    form.post(`/admin/marketing/feedback/${props.feedback.id}/respond`, { preserveScroll: true });
}

const statusBadge = {
    new: 'bg-amber-100 text-amber-800',
    reviewed: 'bg-sky-100 text-sky-700',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-700',
};

function stars(rating) {
    return '★'.repeat(rating || 0) + '☆'.repeat(Math.max(0, 5 - (rating || 0)));
}
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/marketing/feedback" class="text-sm text-slate-500 hover:text-slate-900">← Back to feedback</Link>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <h1 class="text-xl font-bold text-slate-900">{{ feedback.customer_name || 'Anonymous Feedback' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ feedback.customer_email || 'No email' }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span class="text-amber-500 text-lg">{{ stars(feedback.rating) }}</span>
                <span :class="badgeClass(statusBadge[feedback.status] || 'bg-slate-100 text-slate-500')">{{ feedback.status }}</span>
            </div>
            <p class="mt-2 text-xs text-slate-400">{{ formatDateTime(feedback.created_at) }}</p>

            <div class="mt-4 space-y-3 text-sm">
                <div v-if="feedback.comment" class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Comment</p>
                    <p class="mt-1 text-slate-700">{{ feedback.comment }}</p>
                </div>
                <div v-if="feedback.experience" class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Experience</p>
                    <p class="mt-1 text-slate-700">{{ feedback.experience }}</p>
                </div>
                <div v-if="feedback.challenge" class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Challenge</p>
                    <p class="mt-1 text-slate-700">{{ feedback.challenge }}</p>
                </div>
                <div v-if="feedback.suggestion" class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Suggestion</p>
                    <p class="mt-1 text-slate-700">{{ feedback.suggestion }}</p>
                </div>
                <p v-if="!feedback.comment && !feedback.experience && !feedback.challenge && !feedback.suggestion" class="text-sm text-slate-400">No detail provided.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Respond &amp; Process</h2>

            <form class="space-y-4" @submit.prevent="save">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status</label>
                    <select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="new">New</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="approved">Approved (auto-publish as testimonial)</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Admin response</label>
                    <textarea v-model="form.admin_response" rows="4" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Reply to this customer…" />
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">Save Response</button>
                </div>
            </form>

            <p v-if="feedback.admin_response" class="mt-5 rounded-lg border border-amber-100 bg-amber-50 p-3 text-sm text-amber-900">
                <span class="font-semibold">Your response:</span> {{ feedback.admin_response }}
            </p>
        </div>
    </div>
</template>