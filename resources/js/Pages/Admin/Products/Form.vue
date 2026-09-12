<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    categories: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    product: { type: Object, default: null },
});

const { has } = useCan();
const isEdit = computed(() => !!props.product);

const parentCategories = computed(() => props.categories.filter((c) => !c.parent_id));
const subcategories = computed(() =>
    props.categories.filter((c) => c.parent_id === (form.category_id ? Number(form.category_id) : null)),
);

const form = useForm({
    name: props.product?.name ?? '',
    sku: props.product?.sku ?? '',
    category_id: props.product?.category_id ?? '',
    subcategory_id: props.product?.subcategory_id ?? '',
    brand: props.product?.brand ?? '',
    description: props.product?.description ?? '',
    specifications: props.product?.specifications ?? '',
    unit: props.product?.unit ?? 'pcs',
    barcode: props.product?.barcode ?? '',
    supplier_id: props.product?.supplier_id ?? '',
    cost_price: props.product?.cost_price ?? '',
    selling_price: props.product?.selling_price ?? '',
    reorder_level: props.product?.reorder_level ?? 0,
    status: props.product?.status ?? 'active',
    is_featured: props.product?.is_featured ?? false,
    is_visible_online: props.product?.is_visible_online ?? true,
    allow_online_purchase: props.product?.allow_online_purchase ?? true,
    opening_quantity: 0,
    images: [],
    remove_images: [],
    featured_image_id: null,
    featured_new_index: null,
});

const canSeeCost = computed(() => !isEdit.value || has('products.price'));

/* ---------- Product images ---------- */

const existingImages = ref((props.product?.images ?? []).map((img) => ({ ...img })));
const newImages = ref([]);
const removedImages = ref([]);
const isDragOver = ref(false);
const imageInput = ref(null);

const initialFeatured =
    existingImages.value.find((img) => img.is_featured) ?? existingImages.value[0];
const coverKey = ref(initialFeatured ? `existing:${initialFeatured.id}` : '');

function imageSrc(path) {
    return path?.startsWith('/images/') ? path : `/storage/${path}`;
}

function addFiles(files) {
    [...files]
        .filter((f) => f.type?.startsWith('image/'))
        .forEach((file) => newImages.value.push({ file, url: URL.createObjectURL(file) }));
    settleCover();
}

function onFilesPicked(event) {
    addFiles([...(event.target.files || [])]);
    event.target.value = '';
}

function onDrop(event) {
    event.preventDefault();
    isDragOver.value = false;
    addFiles([...(event.dataTransfer?.files || [])]);
}

function removeNew(index) {
    const [removed] = newImages.value.splice(index, 1);
    if (removed?.url) {
        URL.revokeObjectURL(removed.url);
    }
    if (coverKey.value.startsWith('new:') && Number(coverKey.value.split(':')[1]) > index) {
        coverKey.value = `new:${Number(coverKey.value.split(':')[1]) - 1}`;
    }
    settleCover();
}

function removeExisting(id) {
    removedImages.value.push(id);
    existingImages.value = existingImages.value.filter((img) => img.id !== id);
    settleCover();
}

function setCover(key) {
    coverKey.value = key;
}

function settleCover() {
    if (coverKey.value.startsWith('existing:') && existingImages.value.some((img) => img.id === Number(coverKey.value.split(':')[1]))) {
        return;
    }
    if (coverKey.value.startsWith('new:') && Number(coverKey.value.split(':')[1]) < newImages.value.length) {
        return;
    }

    if (existingImages.value.length) {
        coverKey.value = `existing:${existingImages.value[0].id}`;
    } else if (newImages.value.length) {
        coverKey.value = 'new:0';
    } else {
        coverKey.value = '';
    }
}

const isCoverExisting = (id) => coverKey.value === `existing:${id}`;
const isCoverNew = (index) => coverKey.value === `new:${index}`;

onBeforeUnmount(() => {
    newImages.value.forEach((img) => img.url && URL.revokeObjectURL(img.url));
});

function submit() {
    form.images = newImages.value.map((n) => n.file);
    form.remove_images = removedImages.value;
    form.featured_image_id = coverKey.value.startsWith('existing:') ? Number(coverKey.value.split(':')[1]) : null;
    form.featured_new_index = coverKey.value.startsWith('new:') ? Number(coverKey.value.split(':')[1]) : null;

    if (isEdit.value) {
        form.put(`/admin/products/${props.product.id}`, { forceFormData: true });
    } else {
        form.post('/admin/products', { forceFormData: true });
    }
}
</script>

<template>
        <FlashMessages />
        <PageHeader
            :title="isEdit ? `Edit Product: ${props.product.sku}` : 'Add Product'"
            subtitle="Set product details, pricing and opening stock."
        />

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-5 lg:col-span-2">
                <!-- Basic details -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Product Information</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">SKU <span class="text-red-500">*</span></label>
                            <input v-model="form.sku" type="text" placeholder="EV-SOL-550W-001" class="w-full rounded-lg border-slate-300 font-mono text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <div v-if="form.errors.sku" class="mt-1 text-xs text-red-600">{{ form.errors.sku }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                            <select v-model="form.category_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="form.subcategory_id = ''">
                                <option value="">Select category</option>
                                <option v-for="c in parentCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Subcategory</label>
                            <select v-model="form.subcategory_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option value="">Select subcategory</option>
                                <option v-for="c in subcategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Brand</label>
                            <input v-model="form.brand" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Unit</label>
                            <input v-model="form.unit" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Barcode</label>
                            <input v-model="form.barcode" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Supplier</label>
                            <select v-model="form.supplier_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option value="">Select supplier</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Specifications</label>
                        <textarea v-model="form.specifications" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                </div>

                <!-- Product images (multiple allowed) -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">Product Images</h2>
                        <span class="text-xs text-slate-400">Up to 10 images · one cover</span>
                    </div>

                    <div
                        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-8 text-center transition"
                        :class="isDragOver ? 'border-[#0D1527] bg-[#0D1527]/5' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                        @click="imageInput?.click()"
                        @dragover.prevent="isDragOver = true"
                        @dragleave.prevent="isDragOver = false"
                        @drop.prevent="onDrop"
                    >
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#0D1527]/5 text-[#0D1527]">
                            <i class="bi bi-images text-xl"></i>
                        </span>
                        <p class="text-sm font-medium text-slate-700">
                            Click to upload or drag &amp; drop
                        </p>
                        <p class="text-xs text-slate-400">PNG, JPG or WEBP · up to 10MB each</p>
                        <input
                            ref="imageInput"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            multiple
                            class="hidden"
                            @change="onFilesPicked"
                        />
                    </div>

                    <div v-if="form.errors.images" class="mt-2 text-xs text-red-600">{{ form.errors.images }}</div>

                    <div
                        v-if="existingImages.length || newImages.length"
                        class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                    >
                        <!-- Existing images -->
                        <div
                            v-for="img in existingImages"
                            :key="'existing-' + img.id"
                            class="group relative overflow-hidden rounded-xl border border-slate-200"
                        >
                            <img :src="imageSrc(img.path)" :alt="img.alt || 'Product image'" class="h-24 w-full object-cover" />
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/70 to-transparent px-2 pb-1.5 pt-4 text-white">
                                <button
                                    v-if="!isCoverExisting(img.id)"
                                    type="button"
                                    class="flex items-center gap-1 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold backdrop-blur-sm transition hover:bg-white/40"
                                    @click="setCover(`existing:${img.id}`)"
                                >
                                    <i class="bi bi-star"></i> Cover
                                </button>
                                <span
                                    v-else
                                    class="flex items-center gap-1 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950"
                                >
                                    <i class="bi bi-star-fill"></i> Cover
                                </span>
                            </div>
                            <button
                                type="button"
                                class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white opacity-0 transition hover:bg-red-600 group-hover:opacity-100"
                                title="Remove image"
                                @click="removeExisting(img.id)"
                            >
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>

                        <!-- New uploads -->
                        <div
                            v-for="(img, index) in newImages"
                            :key="'new-' + index"
                            class="group relative overflow-hidden rounded-xl border border-slate-200"
                        >
                            <img :src="img.url" alt="New upload" class="h-24 w-full object-cover" />
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/70 to-transparent px-2 pb-1.5 pt-4 text-white">
                                <button
                                    v-if="!isCoverNew(index)"
                                    type="button"
                                    class="flex items-center gap-1 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold backdrop-blur-sm transition hover:bg-white/40"
                                    @click="setCover(`new:${index}`)"
                                >
                                    <i class="bi bi-star"></i> Cover
                                </button>
                                <span
                                    v-else
                                    class="flex items-center gap-1 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950"
                                >
                                    <i class="bi bi-star-fill"></i> Cover
                                </span>
                            </div>
                            <button
                                type="button"
                                class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white opacity-0 transition hover:bg-red-600 group-hover:opacity-100"
                                title="Remove image"
                                @click="removeNew(index)"
                            >
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pricing & stock -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Pricing &amp; Stock</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div v-if="canSeeCost">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Cost Price (₦)</label>
                            <input v-model.number="form.cost_price" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <div v-if="form.errors.cost_price" class="mt-1 text-xs text-red-600">{{ form.errors.cost_price }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Selling Price (₦) <span class="text-red-500">*</span></label>
                            <input v-model.number="form.selling_price" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <div v-if="form.errors.selling_price" class="mt-1 text-xs text-red-600">{{ form.errors.selling_price }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Reorder Level</label>
                            <input v-model.number="form.reorder_level" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div v-if="!isEdit">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Opening Stock</label>
                            <input v-model.number="form.opening_quantity" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                            <p class="mt-1 text-xs text-slate-400">Initial stock recorded as an opening movement.</p>
                        </div>
                        <div v-else class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 sm:col-span-2">
                            Current stock: <span class="font-semibold">{{ props.product.current_quantity }}</span> ·
                            Average cost: <span class="font-semibold">₦{{ Number(props.product.average_cost).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar: status, visibility, save -->
            <div class="space-y-5">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Status &amp; Visibility</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                            <select v-model="form.status" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                            <input v-model="form.is_featured" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400/20" />
                            Featured product
                        </label>
                        <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                            <input v-model="form.is_visible_online" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400/20" />
                            Visible on website
                        </label>
                        <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                            <input v-model="form.allow_online_purchase" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400/20" />
                            Allow online purchase
                        </label>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-[#0D1527] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ isEdit ? 'Save Changes' : 'Create Product' }}
                    </button>
                </div>
            </div>
        </form>
</template>