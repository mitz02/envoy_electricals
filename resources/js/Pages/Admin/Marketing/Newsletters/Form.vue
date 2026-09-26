<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import InputError from '@/Components/InputError.vue';

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
    image: null,
});

const coverInput = ref(null);
const isCoverDragOver = ref(false);
const coverPreview = ref('');
const imageUrl = (path) => (path ? `/storage/${path}` : '');

onBeforeUnmount(() => {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
});

function clearCover() {
    form.image = null;
    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
        coverPreview.value = '';
    }
}

function onCoverFile(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    form.image = file;
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverPreview.value = URL.createObjectURL(file);
}

function onCoverDrop(e) {
    const file = e.dataTransfer.files?.[0];
    if (!file) return;
    form.image = file;
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverPreview.value = URL.createObjectURL(file);
}

function submit() {
    form.transform((data) => ({
        ...data,
        scheduled_at: form.status === 'scheduled' ? form.scheduled_at : null,
    }));
    if (editing.value) {
        form.put(`/admin/marketing/newsletters/${props.newsletter.id}`, { forceFormData: true });
    } else {
        form.post('/admin/marketing/newsletters', { forceFormData: true });
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

            <div>
                <label class="block text-sm font-medium text-slate-700">Image <span class="font-normal text-slate-400">(optional)</span></label>
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div
                        class="relative flex h-36 w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed text-center transition sm:w-56"
                        :class="isCoverDragOver ? 'border-[#0D1527] bg-[#0D1527]/5' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                        @click="coverInput?.click()"
                        @dragover.prevent="isCoverDragOver = true"
                        @dragleave.prevent="isCoverDragOver = false"
                        @drop.prevent="onCoverDrop"
                    >
                        <img
                            v-if="coverPreview || (props.newsletter?.image_path && !form.image)"
                            :src="coverPreview || imageUrl(props.newsletter.image_path)"
                            alt="Newsletter image"
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                        <template v-else>
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#0D1527]/5 text-[#0D1527]">
                                <i class="bi bi-image text-xl"></i>
                            </span>
                            <p class="mt-2 text-xs font-medium text-slate-600">Click to upload image</p>
                            <p class="mt-0.5 text-[10px] text-slate-400">PNG, JPG or WEBP · up to 5MB</p>
                        </template>
                        <input
                            ref="coverInput"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="hidden"
                            @change="onCoverFile"
                        />
                    </div>
                    <div class="min-w-0 flex-1 space-y-2 pt-1">
                        <p class="truncate text-xs font-semibold text-slate-600">
                            <i :class="['bi mr-1', form.image ? 'bi-paperclip text-[#40e0d0]' : props.newsletter?.image_path ? 'bi-check-circle text-emerald-500' : 'bi-circle text-slate-300']"></i>
                            {{ form.image ? form.image.name : props.newsletter?.image_path ? 'Image saved — upload a new file to replace it' : 'No image uploaded yet' }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-[#0D1527] hover:text-[#0D1527]"
                                @click="coverInput?.click()"
                            >
                                <i class="bi bi-upload"></i> {{ form.image ? 'Replace' : 'Choose file' }}
                            </button>
                            <button
                                v-if="form.image"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:border-red-200 hover:text-red-600"
                                @click="clearCover"
                            >
                                <i class="bi bi-x-lg"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                <InputError class="mt-1.5" :message="form.errors.image" />
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