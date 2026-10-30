<script setup>
import { computed, ref } from 'vue';
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
    email: props.testimonial?.email ?? '',
    phone: props.testimonial?.phone ?? '',
    content: props.testimonial?.content ?? '',
    rating: props.testimonial?.rating ?? 5,
    status: props.testimonial?.status ?? 'approved',
    admin_notes: props.testimonial?.admin_notes ?? '',
    is_published: props.testimonial ? Boolean(props.testimonial.is_published) : true,
    photo: null,
    remove_photo: false,
});

const photoPreview = ref(props.testimonial?.photo_path ? `/storage/${props.testimonial.photo_path}` : null);
const photoInput = ref(null);

function submit() {
    form.transform((data) => ({
        ...data,
        is_published: data.is_published ? 1 : 0,
    }));

    if (editing.value) {
        // Laravel PUT with file upload needs _method or post simulation
        if (form.photo) {
            form.transform((data) => ({
                ...data,
                _method: 'PUT',
                is_published: data.is_published ? 1 : 0,
            })).post(`/admin/marketing/testimonials/${props.testimonial.id}`);
        } else {
            form.put(`/admin/marketing/testimonials/${props.testimonial.id}`);
        }
    } else {
        form.post('/admin/marketing/testimonials');
    }
}

function onPhotoChange(e) {
    const file = e.target.files?.[0];
    if (file) {
        form.photo = file;
        form.remove_photo = false;
        photoPreview.value = URL.createObjectURL(file);
    }
}

function removePhoto() {
    form.photo = null;
    form.remove_photo = true;
    photoPreview.value = null;
    if (photoInput.value) photoInput.value.value = '';
}

function approveAndPublish() {
    form.status = 'approved';
    form.is_published = true;
    submit();
}
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/marketing/testimonials" class="text-sm text-slate-500 hover:text-slate-900 flex items-center gap-1">
            <i class="bi bi-arrow-left" /> Back to testimonials
        </Link>
    </div>

    <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h1 class="text-xl font-bold text-slate-900">{{ editing ? 'Edit Testimonial' : 'Add Testimonial' }}</h1>
                <p class="mt-1 text-sm text-slate-500">Customer feedback and testimonials displayed on the public website.</p>
            </div>
            <div v-if="editing && form.status === 'pending'" class="flex items-center gap-2">
                <button
                    type="button"
                    @click="approveAndPublish"
                    class="px-3.5 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors flex items-center gap-1.5"
                >
                    <i class="bi bi-check-lg text-sm" />
                    Approve & Publish Now
                </button>
            </div>
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <!-- Author Name and Role -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Author name <span class="text-red-500">*</span></label>
                    <input v-model="form.author_name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <p v-if="form.errors.author_name" class="mt-1 text-xs text-red-600">{{ form.errors.author_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Author role / location</label>
                    <input v-model="form.author_role" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Homeowner, Lekki" />
                    <p v-if="form.errors.author_role" class="mt-1 text-xs text-red-600">{{ form.errors.author_role }}</p>
                </div>
            </div>

            <!-- Email & Phone -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email address</label>
                    <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="customer@example.com" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Phone number</label>
                    <input v-model="form.phone" type="tel" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="+234..." />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                </div>
            </div>

            <!-- Rating & Status & Feedback Link -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Rating</label>
                    <select v-model="form.rating" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} ★</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Approval Status</label>
                    <select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="pending">Pending Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Link to approved feedback</label>
                    <select v-model="form.feedback_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="">No linked feedback</option>
                        <option v-for="f in feedbackOptions" :key="f.id" :value="f.id">{{ f.customer_name || 'Anonymous' }} — {{ (f.comment || '').substring(0, 30) }}</option>
                    </select>
                </div>
            </div>

            <!-- Author Photo -->
            <div>
                <label class="block text-sm font-medium text-slate-700">Author Photo (Optional)</label>
                <div class="mt-1 flex items-center gap-4">
                    <div v-if="photoPreview" class="relative w-20 h-20 rounded-full overflow-hidden border-2 border-amber-400/80 flex-shrink-0 bg-slate-100 shadow-sm">
                        <img :src="photoPreview" alt="Preview" class="w-full h-full object-cover" />
                        <button
                            type="button"
                            @click="removePhoto"
                            class="absolute top-0 right-0 w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center text-xs hover:bg-red-700 shadow transition-colors"
                            title="Remove photo"
                        >
                            ×
                        </button>
                    </div>
                    <div v-else class="w-20 h-20 rounded-full border-2 border-dashed border-slate-300 flex items-center justify-center text-slate-400 flex-shrink-0 bg-slate-50">
                        <i class="bi bi-person text-2xl" />
                    </div>
                    <div class="flex-1">
                        <input
                            id="photo-input"
                            type="file"
                            accept="image/*"
                            @change="onPhotoChange"
                            class="sr-only"
                            ref="photoInput"
                        />
                        <button
                            type="button"
                            @click="photoInput?.click()"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-2xs"
                        >
                            <i class="bi bi-upload" />
                            {{ photoPreview ? 'Change Photo' : 'Upload Photo' }}
                        </button>
                        <p v-if="form.errors.photo" class="mt-1 text-xs text-red-600">{{ form.errors.photo }}</p>
                        <p class="mt-1 text-xs text-slate-500">Max 2MB · JPG, PNG, WebP · Square/circle recommended</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial Content -->
            <div>
                <label class="block text-sm font-medium text-slate-700">Testimonial Content <span class="text-red-500">*</span></label>
                <textarea v-model="form.content" rows="4" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
            </div>

            <!-- Admin Internal Notes -->
            <div>
                <label class="block text-sm font-medium text-slate-700">Admin Internal Notes (Optional)</label>
                <textarea v-model="form.admin_notes" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Internal notes about customer verification, project, or review details..." />
                <p v-if="form.errors.admin_notes" class="mt-1 text-xs text-red-600">{{ form.errors.admin_notes }}</p>
            </div>

            <!-- Publish Checkbox -->
            <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3 bg-slate-50/50">
                <div>
                    <span class="text-sm font-semibold text-slate-800 block">Publish on website</span>
                    <span class="text-xs text-slate-500">When checked, this testimonial is visible to customers across the site.</span>
                </div>
                <input v-model="form.is_published" type="checkbox" class="h-5 w-5 rounded accent-amber-500 cursor-pointer" />
            </label>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 transition-colors shadow-sm">
                    {{ editing ? 'Save Changes' : 'Add Testimonial' }}
                </button>
                <Link href="/admin/marketing/testimonials" class="text-sm text-slate-500 hover:text-slate-900">Cancel</Link>
            </div>
        </form>
    </div>
</template>
