<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    newsletter: { type: Object, required: true },
    subscriber_count: { type: Number, default: 0 },
    mail_from: { type: Object, default: () => ({ name: 'Envoy Electricals', address: 'hello@example.com' }) },
});

const { has } = useCan();
const canManage = has('marketing.newsletter');

const form = useForm({});

function send() {
    if (!confirm(`Send "${props.newsletter.subject}" to ${props.subscriber_count} subscribers?`)) {
        return;
    }
    form.post(`/admin/marketing/newsletters/${props.newsletter.id}/send`);
}

function remove() {
    if (!confirm(`Remove "${props.newsletter.subject}"?`)) {
        return;
    }
    form.delete(`/admin/marketing/newsletters/${props.newsletter.id}`);
}

const statusBadge = {
    draft: 'bg-slate-100 text-slate-500',
    scheduled: 'bg-sky-100 text-sky-700',
    sent: 'bg-emerald-100 text-emerald-800',
};
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/marketing/newsletters" class="text-sm text-slate-500 hover:text-slate-900">← Back to newsletters</Link>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <h1 class="text-xl font-bold text-slate-900">{{ newsletter.subject }}</h1>
            <div class="mt-2">
                <span :class="badgeClass(statusBadge[newsletter.status] || 'bg-slate-100 text-slate-500')">{{ newsletter.status }}</span>
            </div>

            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Created by</dt><dd class="font-medium text-slate-900">{{ newsletter.creator?.name || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Recipients</dt><dd class="font-medium text-slate-900">{{ subscriber_count }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Scheduled</dt><dd class="font-medium text-slate-900">{{ newsletter.scheduled_at ? formatDateTime(newsletter.scheduled_at) : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Sent</dt><dd class="font-medium text-slate-900">{{ newsletter.sent_at ? formatDateTime(newsletter.sent_at) : '—' }}</dd></div>
            </dl>

            <div class="mt-4 flex flex-col gap-2">
                <button
                    v-if="canManage && newsletter.status !== 'sent'"
                    :disabled="form.processing || subscriber_count === 0"
                    class="rounded-lg bg-[#0D1527] px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                    @click="send"
                >
                    {{ form.processing ? 'Sending…' : `Send to ${subscriber_count} Subscribers` }}
                </button>
                <p v-if="subscriber_count === 0 && newsletter.status !== 'sent'" class="text-[11px] text-amber-700">
                    No active subscribers yet — add some before sending.
                </p>
                <Link v-if="canManage && newsletter.status !== 'sent'" :href="`/admin/marketing/newsletters/${newsletter.id}/edit`" class="rounded-lg border border-slate-200 px-3 py-1.5 text-center text-xs font-semibold text-slate-600 hover:bg-slate-50">Edit</Link>
                <button v-if="canManage" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50" @click="remove">Delete</button>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Content Preview</h2>
            <div class="rounded-xl border border-slate-200 p-5">
                <img v-if="newsletter.image_path" :src="`/storage/${newsletter.image_path}`" alt="Newsletter image" class="mb-4 max-h-60 w-full rounded-lg object-cover" />
                <p class="text-lg font-bold text-slate-900">{{ newsletter.subject }}</p>
                <p class="mt-1 text-xs text-slate-400">From: {{ mail_from.name }} &lt;{{ mail_from.address }}&gt;</p>
                <hr class="my-4 border-slate-100" />
                <div class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ newsletter.content }}</div>
            </div>
        </div>
    </div>
</template>