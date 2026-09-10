<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    newsletter: { type: Object, default: null },
});

const editing = computed(() => Boolean(props.newsletter));

const form = useForm({
    subject: props.newsletter?.subject ?? '',
    content: props.newsletter?.content ?? '',
    status: props.newsletter?.status === 'sent' ? 'draft' : (props.newsletter?.status ?? 'draft'),
    scheduled_at: props.newsletter?.scheduled_at ? props.newsletter.scheduled_at.substring(0, 16) : '',
});

function submit() {
    const payload = { ...form.data(), scheduled_at: form.status === 'scheduled' ? form.scheduled_at : null };
    form.transform(() => payload);
    if (editing.value) {
        form.put(`/admin/marketing/newsletters/${props.newsletter.id}`);
    } else {
        form.post('/admin/marketing/newsletters');
    }
}
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/marketing/newsletters" class="text-sm text-slate-500 hover:text-slate-900">← Back to newsletters</Link>
    </div>

    <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900">{{ editing ? 'Edit Newsletter' : 'New Newsletter' }}</h1>
        <p class="mt-1 text-sm text-slate-500">Compose an email to your newsletter subscribers.</p>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-slate-700">Subject</label>
                <input v-model="form.subject" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. New Solar Packages for December" />
                <p v-if="form.errors.subject" class="mt-1 text-xs text-red-600">{{ form.errors.subject }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Content</label>
                <textarea v-model="form.content" rows="10" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Write your newsletter body…" />
                <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status</label>
                    <select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="draft">Draft</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                </div>
                <div v-if="form.status === 'scheduled'">
                    <label class="block text-sm font-medium text-slate-700">Send at</label>
                    <input v-model="form.scheduled_at" type="datetime-local" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    {{ editing ? 'Save Changes' : 'Create Newsletter' }}
                </button>
                <Link href="/admin/marketing/newsletters" class="text-sm text-slate-500 hover:text-slate-900">Cancel</Link>
            </div>
        </form>
    </div>
</template>