<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import ImageCropper from '@/Components/ImageCropper.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    testimonial: { type: Object, default: null },
});

const t = computed(() => props.testimonial ?? {});

const form = useForm({
    author_name: t.value.author_name ?? '',
    author_role: t.value.author_role ?? '',
    email: t.value.email ?? '',
    phone: t.value.phone ?? '',
    content: t.value.content ?? '',
    rating: t.value.rating ?? 5,
    photo: null,
});

const photoPreview = ref(t.value.photo_path ? `/storage/${t.value.photo_path}` : null);
const showCropper = ref(false);
const cropperImageSrc = ref(null);
const fileInput = ref(null);

function onPhotoChange(e) {
    const file = e.target.files?.[0];
    if (file) {
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file (JPEG, PNG, WebP).');
            return;
        }
        if (file.size > 15 * 1024 * 1024) {
            alert('Image too large. Please select a file under 15MB.');
            return;
        }

        if (cropperImageSrc.value) {
            URL.revokeObjectURL(cropperImageSrc.value);
        }
        cropperImageSrc.value = URL.createObjectURL(file);
        showCropper.value = true;
    }
}

function handleCrop(blob) {
    const croppedFile = new File([blob], `photo_${Date.now()}.jpg`, { type: 'image/jpeg' });
    form.photo = croppedFile;

    if (photoPreview.value && photoPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(photoPreview.value);
    }
    photoPreview.value = URL.createObjectURL(croppedFile);

    showCropper.value = false;
    if (cropperImageSrc.value) {
        URL.revokeObjectURL(cropperImageSrc.value);
        cropperImageSrc.value = null;
    }

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function cancelCrop() {
    showCropper.value = false;
    if (cropperImageSrc.value) {
        URL.revokeObjectURL(cropperImageSrc.value);
        cropperImageSrc.value = null;
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function removePhoto() {
    form.photo = null;
    if (photoPreview.value && photoPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(photoPreview.value);
    }
    photoPreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit() {
    if (props.testimonial?.token) {
        form.post(route('public.testimonial.submit', props.testimonial.token));
    } else if (props.testimonial?.id) {
        form.post(route('public.testimonial.submit', props.testimonial.id));
    } else {
        form.post(route('public.testimonial.store'));
    }
}
</script>

<template>
    <Head title="Share Your Experience — Envoy Electricals" />

    <Teleport to="body">
        <ImageCropper
            v-if="showCropper"
            :image-src="cropperImageSrc"
            :aspect-ratio="1"
            :circle="true"
            :output-width="600"
            @crop="handleCrop"
            @cancel="cancelCrop"
        />
    </Teleport>

    <section class="relative bg-[#FAF8F2] py-16 sm:py-24 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <div class="absolute -top-32 -right-24 w-96 h-96 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-24 w-80 h-80 rounded-full bg-[#60d4f7]/10 blur-3xl pointer-events-none"></div>

        <div class="max-w-3xl mx-auto relative">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 text-[#016cbb] text-xs font-bold uppercase tracking-[0.3em] mb-4">
                    <span class="w-8 h-px bg-[#016cbb]"></span>
                    Share Your Experience
                    <span class="w-8 h-px bg-[#016cbb]"></span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tight text-slate-950 leading-tight">
                    We'd Love to<br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-[#016cbb]">Hear From You</span>
                </h1>
                <p class="mt-4 text-slate-500 text-base leading-relaxed max-w-xl mx-auto">
                    Your feedback helps us improve and helps other customers make confident decisions about their solar journey.
                </p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xl p-6 sm:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Your Name <span class="text-red-500">*</span></label>
                            <input v-model="form.author_name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="John Doe" />
                            <p v-if="form.errors.author_name" class="mt-1 text-xs text-red-600">{{ form.errors.author_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Your Role / Location</label>
                            <input v-model="form.author_role" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Homeowner, Lagos" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Email <span class="text-red-500">*</span></label>
                            <input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="john@example.com" />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Phone Number</label>
                            <input v-model="form.phone" type="tel" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="+234 800 123 4567" />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Rating <span class="text-red-500">*</span></label>
                        <div class="mt-1 flex items-center gap-2">
                            <select v-model="form.rating" class="w-auto rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} ★</option>
                            </select>
                            <span class="text-sm text-slate-500">(5 = Excellent)</span>
                        </div>
                        <p v-if="form.errors.rating" class="mt-1 text-xs text-red-600">{{ form.errors.rating }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Your Testimonial <span class="text-red-500">*</span></label>
                        <textarea v-model="form.content" rows="5" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Tell us about your experience with Envoy Electricals..." />
                        <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
                        <p class="mt-1 text-xs text-slate-500">Minimum 10 characters. Be specific about what you liked!</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Photo (Optional)</label>
                        <div class="mt-2 flex items-center gap-4">
                            <div v-if="photoPreview" class="relative w-20 h-20 rounded-full overflow-hidden border-2 border-amber-400/80 shadow-md flex-shrink-0 bg-slate-100">
                                <img :src="photoPreview" alt="Preview" class="w-full h-full object-cover" />
                                <button
                                    type="button"
                                    @click="removePhoto"
                                    class="absolute top-0 right-0 w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center text-xs hover:bg-red-700 shadow transition-colors"
                                    title="Remove photo"
                                    aria-label="Remove photo"
                                >
                                    ×
                                </button>
                            </div>
                            <div v-else class="w-20 h-20 rounded-full border-2 border-dashed border-slate-300 flex items-center justify-center text-slate-400 flex-shrink-0 bg-slate-50">
                                <i class="bi bi-person text-3xl" />
                            </div>
                            <div class="flex-1">
                                <input
                                    ref="fileInput"
                                    id="photo-input"
                                    type="file"
                                    accept="image/*"
                                    @change="onPhotoChange"
                                    class="sr-only"
                                />
                                <button
                                    type="button"
                                    @click="fileInput?.click()"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-sm transition-colors cursor-pointer"
                                >
                                    <i class="bi bi-camera" />
                                    {{ photoPreview ? 'Change Photo' : 'Upload Photo' }}
                                </button>
                                <p v-if="form.errors.photo" class="mt-1 text-xs text-red-600">{{ form.errors.photo }}</p>
                                <p class="mt-1 text-xs text-slate-500">Photo will be cropped to a circle avatar (JPG, PNG, WebP)</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <button type="submit" :disabled="form.processing" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-8 py-3 rounded-full transition-colors disabled:opacity-50 shadow-md">
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Submitting...
                            </span>
                            <span v-else>Submit Testimonial</span>
                        </button>
                    </div>
                </form>

                <p class="mt-6 text-center text-xs text-slate-500">
                    By submitting, you agree that Envoy Electricals may publish your testimonial on our website and marketing materials.
                </p>
            </div>

            <!-- Back to Home -->
            <div class="mt-8 text-center">
                <Link href="/" class="text-sm text-slate-500 hover:text-slate-900 flex items-center justify-center gap-1">
                    <i class="bi bi-arrow-left" /> Back to Home
                </Link>
            </div>
        </div>
    </section>
</template>
