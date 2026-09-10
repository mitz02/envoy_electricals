<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    testimonial: { type: Object, default: null },
    feedbackOptions: { type: Array, default: () => [] },
});

const editing = computed(() => Boolean(props.testimonial));

const form = useForm({
    feedback_id: props.testimonial?.feedback_id ?? '',
    author_name: props.testimonial?.author_name ?? '',
    author_role: props.testimonial?.author_role ?? '',
    content: props.testimonial?.content ?? '',
    rating: props.testimonial?.rating ?? 5,
    is_published: props.testimonial ? Boolean(props.testimonial.is_published) : true,
});

function submit() {
    form.transform((data) => ({ ...data, is_published: data.is_published ? 1 : 0 }));
    if (editing.value) {
        form.put(`/admin/marketing/testimonials/${props.testimonial.id}`);
    } else {
        form.post('/admin/marketing/testimonials');
    }
}
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/marketing/testimonials" class="text-sm text-slate-500 hover:text-slate-900">← Back to testimonials</Link>
    </div>

    <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900">{{ editing ? 'Edit Testimonial' : 'Add Testimonial' }}</h1>
        <p class="mt-1 text-sm text-slate-500">Customer praise shown on the public website.</p>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Author name</label>
                    <input v-model="form.author_name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <p v-if="form.errors.author_name" class="mt-1 text-xs text-red-600">{{ form.errors.author_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Author role</label>
                    <input v-model="form.author_role" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Homeowner, Lekki" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Rating</label>
                    <select v-model="form.rating" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} ★</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Link to approved feedback</label>
                    <select v-model="form.feedback_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="">No linked feedback</option>
                        <option v-for="f in feedbackOptions" :key="f.id" :value="f.id">{{ f.customer_name || 'Anonymous' }} — {{ (f.comment || '').substring(0, 40) }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Testimonial</label>
                <textarea v-model="form.content" rows="4" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
            </div>

            <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3">
                <span class="text-sm font-medium text-slate-700">Publish on website</span>
                <input v-model="form.is_published" type="checkbox" class="h-4 w-4 rounded accent-amber-500" />
            </label>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    {{ editing ? 'Save Changes' : 'Add Testimonial' }}
                </button>
                <Link href="/admin/marketing/testimonials" class="text-sm text-slate-500 hover:text-slate-900">Cancel</Link>
            </div>
        </form>
    </div>
</template>