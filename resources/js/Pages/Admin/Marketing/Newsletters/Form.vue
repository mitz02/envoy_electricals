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
    remove_image: false,
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
    form.remove_image = true;
    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
        coverPreview.value = '';
    }
    if (coverInput.value) {
        coverInput.value.value = '';
    }
}

function triggerFileSelect() {
    coverInput.value?.click();
}

function onCoverFile(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file (PNG, JPG, WebP).');
        return;
    }
    form.image = file;
    form.remove_image = false;
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverPreview.value = URL.createObjectURL(file);
}

function onCoverDrop(e) {
    isCoverDragOver.value = false;
    const file = e.dataTransfer.files?.[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        alert('Please drop an image file (PNG, JPG, WebP).');
        return;
    }
    form.image = file;
    form.remove_image = false;
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverPreview.value = URL.createObjectURL(file);
}

function submit(sendNow = false) {
    if (sendNow) {
        form.status = 'send_now';
    }

    form.transform((data) => {
        const payload = {
            ...data,
            scheduled_at: form.status === 'scheduled' ? form.scheduled_at : null,
        };

        // Don't send empty or null image in payload to prevent string "null" FormData bugs
        if (!payload.image) {
            delete payload.image;
        }

        // Use _method: 'PUT' with POST for Laravel to parse multipart/form-data with file
        if (editing.value) {
            payload._method = 'PUT';
        }

        return payload;
    });

    if (editing.value) {
        form.post(`/admin/marketing/newsletters/${props.newsletter.id}`, {
            forceFormData: true,
        });
    } else {
        form.post('/admin/marketing/newsletters', {
            forceFormData: true,
        });
    }
}
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/marketing/newsletters" class="text-sm text-slate-500 hover:text-slate-900 flex items-center gap-1">
            <i class="bi bi-arrow-left" /> Back to newsletters
        </Link>
    </div>

    <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900">{{ editing ? 'Edit Newsletter' : 'New Newsletter' }}</h1>
        <p class="mt-1 text-sm text-slate-500">Compose an email campaign to send to your newsletter subscribers.</p>

        <form class="mt-6 space-y-5" @submit.prevent="submit(false)">
            <!-- Subject -->
            <div>
                <label class="block text-sm font-medium text-slate-700">Subject <span class="text-red-500">*</span></label>
                <input
                    v-model="form.subject"
                    type="text"
                    required
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    placeholder="e.g. New Solar Packages for December"
                />
                <p v-if="form.errors.subject" class="mt-1 text-xs text-red-600">{{ form.errors.subject }}</p>
            </div>

            <!-- Content -->
            <div>
                <label class="block text-sm font-medium text-slate-700">Content <span class="text-red-500">*</span></label>
                <textarea
                    v-model="form.content"
                    rows="10"
                    required
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    placeholder="Write your newsletter message body…"
                />
                <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
            </div>

            <!-- Image Upload -->
            <div>
                <label class="block text-sm font-medium text-slate-700">Featured Header Image <span class="font-normal text-slate-400">(optional)</span></label>
                <div class="mt-1 flex flex-col gap-4 sm:flex-row">
                    <div
                        class="relative flex h-36 w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed text-center transition sm:w-56"
                        :class="isCoverDragOver ? 'border-amber-500 bg-amber-50/50' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                        @click="triggerFileSelect"
                        @dragover.prevent="isCoverDragOver = true"
                        @dragleave.prevent="isCoverDragOver = false"
                        @drop.prevent="onCoverDrop"
                    >
                        <img
                            v-if="coverPreview || (props.newsletter?.image_path && !form.remove_image)"
                            :src="coverPreview || imageUrl(props.newsletter.image_path)"
                            alt="Newsletter image"
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                        <template v-else>
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                <i class="bi bi-image text-xl"></i>
                            </span>
                            <p class="mt-2 text-xs font-medium text-slate-700">Click to upload image</p>
                            <p class="mt-0.5 text-[10px] text-slate-400">PNG, JPG or WEBP · up to 5MB</p>
                        </template>

                        <input
                            ref="coverInput"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="hidden"
                            @change="onCoverFile"
                            @click.stop
                        />
                    </div>

                    <div class="min-w-0 flex-1 space-y-2 pt-1">
                        <p class="truncate text-xs font-semibold text-slate-700">
                            <i :class="['bi mr-1.5', form.image ? 'bi-paperclip text-amber-500' : (props.newsletter?.image_path && !form.remove_image) ? 'bi-check-circle text-emerald-500' : 'bi-circle text-slate-300']"></i>
                            {{ form.image ? form.image.name : (props.newsletter?.image_path && !form.remove_image) ? 'Image saved — upload a new file to replace it' : 'No image selected' }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 cursor-pointer"
                                @click="triggerFileSelect"
                            >
                                <i class="bi bi-upload"></i> {{ (form.image || (props.newsletter?.image_path && !form.remove_image)) ? 'Replace Image' : 'Choose File' }}
                            </button>
                            <button
                                v-if="form.image || (props.newsletter?.image_path && !form.remove_image)"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50/50 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-100 cursor-pointer"
                                @click="clearCover"
                            >
                                <i class="bi bi-trash3"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                <InputError class="mt-1.5" :message="form.errors.image" />
            </div>

            <!-- Sending Options / Status -->
            <div class="grid gap-4 sm:grid-cols-2 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Sending Action</label>
                    <select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="draft">Save as Draft</option>
                        <option value="send_now">Send Immediately to Subscribers</option>
                        <option value="scheduled">Schedule for Later</option>
                    </select>
                </div>

                <div v-if="form.status === 'scheduled'">
                    <label class="block text-sm font-medium text-slate-700">Dispatch Time</label>
                    <input
                        v-model="form.scheduled_at"
                        type="datetime-local"
                        required
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-3 pt-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 transition-colors shadow-sm cursor-pointer"
                >
                    {{ form.status === 'send_now' ? 'Save & Send Now' : (editing ? 'Save Changes' : 'Save Newsletter') }}
                </button>

                <button
                    v-if="form.status !== 'send_now'"
                    type="button"
                    :disabled="form.processing"
                    @click="submit(true)"
                    class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50 transition-colors shadow-sm flex items-center gap-1.5 cursor-pointer"
                >
                    <i class="bi bi-send-fill text-xs" />
                    Save & Send Now
                </button>

                <Link href="/admin/marketing/newsletters" class="text-sm text-slate-500 hover:text-slate-900 ml-auto">Cancel</Link>
            </div>
        </form>
    </div>
</template>
