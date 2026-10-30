<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    newsletter: { type: Object, required: true },
    subscriber_count: { type: Number, default: 0 },
    current_user_email: { type: String, default: '' },
    mail_from: { type: Object, default: () => ({ name: 'Envoy Electricals', address: 'hello@example.com' }) },
});

const { has } = useCan();
const canManage = has('marketing.newsletter');

const form = useForm({});
const showTestModal = ref(false);
const testForm = useForm({
    email: props.current_user_email || '',
});

const subscribeMeForm = useForm({
    email: props.current_user_email || '',
    name: 'Admin User',
});

function send() {
    if (!confirm(`Send "${props.newsletter.subject}" to ${props.subscriber_count} subscribers?`)) {
        return;
    }
    form.post(`/admin/marketing/newsletters/${props.newsletter.id}/send`);
}

function resend() {
    if (!confirm(`Resend "${props.newsletter.subject}" to ${props.subscriber_count} subscribers?`)) {
        return;
    }
    form.post(`/admin/marketing/newsletters/${props.newsletter.id}/resend`);
}

function sendTestEmail() {
    testForm.post(`/admin/marketing/newsletters/${props.newsletter.id}/test`, {
        preserveScroll: true,
        onSuccess: () => {
            showTestModal.value = false;
        },
    });
}

function addMeAsSubscriber() {
    subscribeMeForm.post('/admin/marketing/subscribers', {
        preserveScroll: true,
    });
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
        <Link href="/admin/marketing/newsletters" class="text-sm text-slate-500 hover:text-slate-900 flex items-center gap-1">
            <i class="bi bi-arrow-left" /> Back to newsletters
        </Link>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Sidebar Details & Actions -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-5">
            <div>
                <h1 class="text-xl font-bold text-slate-900 leading-snug">{{ newsletter.subject }}</h1>
                <div class="mt-2">
                    <span :class="badgeClass(statusBadge[newsletter.status] || 'bg-slate-100 text-slate-500')" class="uppercase tracking-wider text-[10px] font-bold">
                        {{ newsletter.status }}
                    </span>
                </div>
            </div>

            <dl class="space-y-2 text-sm border-t border-b border-slate-100 py-3">
                <div class="flex justify-between"><dt class="text-slate-500">Created by</dt><dd class="font-medium text-slate-900">{{ newsletter.creator?.name || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Recipients</dt><dd class="font-bold text-slate-900">{{ subscriber_count }} active subscriber{{ subscriber_count === 1 ? '' : 's' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Scheduled</dt><dd class="font-medium text-slate-900">{{ newsletter.scheduled_at ? formatDateTime(newsletter.scheduled_at) : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Sent</dt><dd class="font-medium text-slate-900">{{ newsletter.sent_at ? formatDateTime(newsletter.sent_at) : '—' }}</dd></div>
            </dl>

            <div class="flex flex-col gap-2">
                <!-- Send to all subscribers -->
                <button
                    v-if="canManage && newsletter.status !== 'sent'"
                    :disabled="form.processing || subscriber_count === 0"
                    class="rounded-lg bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-40 transition-colors shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                    @click="send"
                >
                    <i class="bi bi-send-fill text-xs" />
                    {{ form.processing ? 'Sending…' : `Send to ${subscriber_count} Subscriber${subscriber_count === 1 ? '' : 's'}` }}
                </button>

                <!-- Resend button -->
                <button
                    v-if="canManage && newsletter.status === 'sent'"
                    :disabled="form.processing || subscriber_count === 0"
                    class="rounded-lg bg-[#0D1527] px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-800 disabled:opacity-40 transition-colors shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                    @click="resend"
                >
                    <i class="bi bi-arrow-repeat text-xs" />
                    {{ form.processing ? 'Resending…' : `Resend to ${subscriber_count} Subscribers` }}
                </button>

                <!-- Send Test Email Button -->
                <button
                    v-if="canManage"
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center justify-center gap-2 shadow-2xs cursor-pointer"
                    @click="showTestModal = true"
                >
                    <i class="bi bi-envelope-paper text-amber-500 text-sm" />
                    Send Test Email
                </button>

                <!-- Notice when 0 subscribers -->
                <div v-if="subscriber_count === 0" class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900 space-y-2">
                    <p class="font-medium">
                        <i class="bi bi-info-circle-fill text-amber-600 mr-1" />
                        There are 0 active subscribers. Add subscribers or test email delivery using the button below.
                    </p>
                    <button
                        v-if="current_user_email"
                        type="button"
                        :disabled="subscribeMeForm.processing"
                        @click="addMeAsSubscriber"
                        class="w-full py-1.5 px-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold rounded-lg transition-colors cursor-pointer text-center text-[11px]"
                    >
                        {{ subscribeMeForm.processing ? 'Adding…' : `Add ${current_user_email} as Subscriber` }}
                    </button>
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    <Link
                        v-if="canManage && newsletter.status !== 'sent'"
                        :href="`/admin/marketing/newsletters/${newsletter.id}/edit`"
                        class="flex-1 rounded-lg border border-slate-200 px-3 py-1.5 text-center text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="canManage"
                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 transition-colors cursor-pointer"
                        @click="remove"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Newsletter Content Preview -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs lg:col-span-2">
            <h2 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-400">Content Preview</h2>
            <div class="rounded-xl border border-slate-200 p-6 bg-slate-50/30">
                <img
                    v-if="newsletter.image_path"
                    :src="`/storage/${newsletter.image_path}`"
                    alt="Newsletter image"
                    class="mb-6 max-h-72 w-full rounded-xl object-cover border border-slate-200"
                />
                <h3 class="text-xl font-bold text-slate-900">{{ newsletter.subject }}</h3>
                <p class="mt-1 text-xs text-slate-400 font-mono">From: {{ mail_from.name }} &lt;{{ mail_from.address }}&gt;</p>
                <hr class="my-4 border-slate-200" />
                <div class="whitespace-pre-line text-sm leading-relaxed text-slate-800">{{ newsletter.content }}</div>
            </div>
        </div>
    </div>

    <!-- Test Email Modal -->
    <div
        v-if="showTestModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="bg-white rounded-2xl max-w-md w-full border border-slate-200 shadow-2xl p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <i class="bi bi-envelope-paper text-base" />
                    </span>
                    <h3 class="text-sm font-bold text-slate-900">Send Test Email</h3>
                </div>
                <button
                    type="button"
                    @click="showTestModal = false"
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-600"
                >
                    <i class="bi bi-x-lg" />
                </button>
            </div>

            <form class="mt-4 space-y-4" @submit.prevent="sendTestEmail">
                <div>
                    <label class="block text-xs font-medium text-slate-700">Recipient Email Address</label>
                    <input
                        v-model="testForm.email"
                        type="email"
                        required
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        placeholder="your-email@example.com"
                    />
                    <p class="mt-1 text-[11px] text-slate-400">
                        A preview of this newsletter will be sent to this email address immediately.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showTestModal = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="testForm.processing"
                        class="px-4 py-2 text-xs font-bold text-white bg-[#0D1527] hover:bg-slate-800 disabled:opacity-50 rounded-lg shadow-sm transition-colors flex items-center gap-1.5"
                    >
                        <i class="bi bi-send text-xs" />
                        {{ testForm.processing ? 'Sending Test…' : 'Send Test Now' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
