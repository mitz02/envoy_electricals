<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    categories: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    currentStoreId: { type: [Number, String, null], default: null },
    product: { type: Object, default: null },
});

const { has } = useCan();
const isEdit = computed(() => !!props.product);

const categoriesList = ref([...props.categories].map((c) => ({ ...c })));
const parentCategories = computed(() => categoriesList.value.filter((c) => !c.parent_id));

const suppliersList = ref([...props.suppliers].map((s) => ({ ...s })));
const brandsList = ref([...props.brands].map((b) => ({ ...b })));

/* ---------- Quick add category (attached to the Category select) ---------- */

const showCategoryModal = ref(false);
const newCategoryName = ref('');
const categorySaving = ref(false);
const categoryError = ref('');

/* ---------- Quick add supplier (attached to the Supplier select) ---------- */

const showSupplierModal = ref(false);
const supplierSaving = ref(false);
const supplierModalError = ref('');
const supplierValidationErrors = ref({});
const supplierForm = ref({
    name: '',
    phone: '',
    email: '',
    address: '',
    contact_person: '',
});

async function createSupplier() {
    supplierSaving.value = true;
    supplierModalError.value = '';
    supplierValidationErrors.value = {};

    try {
        const { data } = await axios.post('/admin/suppliers/quick', {
            name: supplierForm.value.name,
            phone: supplierForm.value.phone,
            email: supplierForm.value.email,
            address: supplierForm.value.address,
            contact_person: supplierForm.value.contact_person,
        });

        const supplier = data.supplier;
        suppliersList.value.push(supplier);
        form.supplier_id = supplier.id;
        supplierModalError.value = '';
        supplierValidationErrors.value = {};
        showSupplierModal.value = false;
    } catch (e) {
        if (e.response?.data?.errors) {
            supplierValidationErrors.value = e.response.data.errors;
        } else {
            supplierModalError.value = e.response?.data?.message || 'Could not save supplier. Please try again.';
        }
    } finally {
        supplierSaving.value = false;
    }
}

function openSupplierModal() {
    supplierModalError.value = '';
    supplierValidationErrors.value = {};
    supplierForm.value = { name: '', phone: '', email: '', address: '', contact_person: '' };
    showSupplierModal.value = true;
}

function openCategoryModal() {
    newCategoryName.value = '';
    categoryError.value = '';
    showCategoryModal.value = true;
}

/* ---------- Quick add brand (attached to the Brand select) ---------- */

const showBrandModal = ref(false);
const brandSaving = ref(false);
const brandModalError = ref('');
const brandValidationErrors = ref({});
const brandForm = ref({
    name: '',
});

async function createBrand() {
    brandSaving.value = true;
    brandModalError.value = '';
    brandValidationErrors.value = {};

    try {
        const { data } = await axios.post('/admin/brands/quick', {
            name: brandForm.value.name,
        });

        const brand = data.brand;
        brandsList.value.push(brand);
        form.brand_id = brand.id;
        brandModalError.value = '';
        brandValidationErrors.value = {};
        showBrandModal.value = false;
    } catch (e) {
        if (e.response?.data?.errors) {
            brandValidationErrors.value = e.response.data.errors;
        } else {
            brandModalError.value = e.response?.data?.message || 'Could not save brand. Please try again.';
        }
    } finally {
        brandSaving.value = false;
    }
}

function openBrandModal() {
    brandModalError.value = '';
    brandValidationErrors.value = {};
    brandForm.value = { name: '' };
    showBrandModal.value = true;
}

function addCategoryFromForm() {
    if (!newCategoryName.value.trim() || categorySaving.value) return;
    categorySaving.value = true;
    categoryError.value = '';

    axios
        .post('/admin/settings/categories', {
            name: newCategoryName.value.trim(),
        })
        .then(({ data }) => {
            const category = data.category;
            if (!categoriesList.value.some((c) => c.id === category.id)) {
                categoriesList.value.push(category);
            }
            form.category_id = category.id;
            form.subcategory_id = '';
            showCategoryModal.value = false;
        })
        .catch((error) => {
            const errors = error.response?.data?.errors;
            categoryError.value = errors && Object.values(errors)?.[0]?.[0] ? Object.values(errors)[0][0] : 'Could not add category.';
        })
        .finally(() => {
            categorySaving.value = false;
        });
}

const form = useForm({
    name: props.product?.name ?? '',
    category_id: props.product?.category_id ?? '',
    subcategory_id: props.product?.subcategory_id ?? '',
    brand_id: props.product?.brand_id ?? '',
    description: props.product?.description ?? '',
    specifications: props.product?.specifications ?? '',
    unit: props.product?.unit ?? 'pcs',
    supplier_id: props.product?.supplier_id ?? '',
    cost_price: props.product?.cost_price ?? '',
    selling_price: props.product?.selling_price ?? '',
    reorder_level: props.product?.reorder_level ?? 0,
    allow_online_purchase: props.product?.allow_online_purchase ?? true,
    opening_quantity: 0,
    images: [],
    remove_images: [],
    featured_image_id: null,
    featured_new_index: null,
    specs: [],
    store_id: props.product?.store_id ?? props.currentStoreId ?? '',
});

/* ---------- Specifications (label/value rows) ---------- */

function parseSpecRows(json, text) {
    if (Array.isArray(json) && json.length) {
        return json
            .map((s) => ({ label: String(s?.label ?? '').trim(), value: String(s?.value ?? '').trim() }))
            .filter((s) => s.label || s.value);
    }
    if (typeof text === 'string' && text.trim()) {
        return text
            .split(/\r?\n/)
            .map((line) => {
                const [label, ...rest] = line.split(':');
                return { label: (label ?? '').trim(), value: rest.join(':').trim() };
            })
            .filter((s) => s.label || s.value);
    }
    return [];
}

const specs = ref(props.product ? parseSpecRows(props.product.specifications_json, props.product.specifications) : []);
const specSuggestions = ['Wattage', 'Voltage', 'Warranty', 'Capacity', 'Panel Type', 'Efficiency', 'Weight', 'Dimensions'];

function addSpec() {
    if (specs.value.length >= 40) return;
    specs.value.push({ label: '', value: '' });
}

function removeSpec(index) {
    specs.value.splice(index, 1);
}

function fillFromSuggestion(suggestion) {
    if (specs.value.some((s) => s.label.trim().toLowerCase() === suggestion.toLowerCase())) return;
    specs.value.push({ label: suggestion, value: '' });
}

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
    form.specs = specs.value
        .map((s) => ({ label: (s.label ?? '').trim(), value: (s.value ?? '').trim() }))
        .filter((s) => s.label || s.value);

    const url = isEdit.value ? `/admin/products/${props.product.id}` : '/admin/products';
    const method = isEdit.value ? 'put' : 'post';

    form[method](url, {
        forceFormData: true,
        onSuccess: (page) => {
            const message = isEdit.value ? 'Product updated successfully.' : 'Product created successfully.';
            router.visit(page.url, { preserveState: true, onSuccess: () => {
                // Flash message is handled by Inertia
            }});
        },
        onError: (errors) => {
            // Errors are automatically set on form.errors by Inertia
            console.log('Validation errors:', errors);
        },
    });
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        :title="isEdit ? `Edit Product: ${props.product.sku}` : 'Add Product'"
        subtitle="Set product details, pricing and opening stock."
    />

    <!-- Global form errors -->
    <div v-if="Object.keys(form.errors).length > 0" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        <p class="font-semibold">Please fix the errors below to continue.</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
            <li v-for="(msg, key) in form.errors" :key="key">{{ msg }}</li>
        </ul>
    </div>

    <form class="space-y-5" @submit.prevent="submit">
        <div class="space-y-5">
            <!-- Basic details -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Product Information</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" placeholder="e.g. 550W Solar Panel" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                    </div>
                    <div v-if="isEdit">
                        <label class="mb-1 block text-sm font-medium text-slate-700">SKU</label>
                        <input :value="product.sku" type="text" disabled class="w-full rounded-lg border-slate-200 bg-slate-50 font-mono text-sm text-slate-500" />
                        <p class="mt-1 text-xs text-slate-400">Auto-generated. Not editable.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                        <div class="flex gap-2">
                            <select v-model="form.category_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="form.subcategory_id = ''">
                                <option value="">Select category</option>
                                <option v-for="c in parentCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <button
                                type="button"
                                title="Add new category"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-[#0D1527] transition hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                                @click="openCategoryModal(false)"
                            >
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
<div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Brand <span class="font-normal text-slate-400">(optional)</span></label>
                            <div class="flex gap-2">
                                <select v-model="form.brand_id" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                    <option value="">Select brand</option>
                                    <option v-for="b in brandsList" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <button
                                    type="button"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-[#0D1527] transition hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                                    @click="openBrandModal"
                                    title="Add new brand"
                                >
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Unit</label>
                        <input v-model="form.unit" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Supplier</label>
                        <div class="flex gap-2">
                            <select v-model="form.supplier_id" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option value="">Select supplier</option>
                                <option v-for="s in suppliersList" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <button
                                type="button"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-[#0D1527] transition hover:border-[#0D1527] hover:bg-[#0D1527] hover:text-white"
                                @click="openSupplierModal"
                                title="Add new supplier"
                            >
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Store <span class="text-red-500">*</span></label>
                        <select v-model="form.store_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">Select store</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }} ({{ s.code }})</option>
                        </select>
                        <div v-if="form.errors.store_id" class="mt-1 text-xs text-red-600">{{ form.errors.store_id }}</div>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea v-model="form.description" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>
                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Specifications</label>
                    <p class="mb-3 text-xs text-slate-400">
                        Details your customers see on the product page. Each row pairs a
                        <span class="font-semibold text-slate-500">feature</span> with its
                        <span class="font-semibold text-slate-500">value</span> —
                        e.g. <span class="text-slate-500">Wattage → 550W</span>, <span class="text-slate-500">Warranty → 12 months</span>.
                    </p>

                    <div v-if="specs.length" class="space-y-2">
                        <div v-for="(spec, index) in specs" :key="index" class="flex items-center gap-2">
                            <input
                                v-model="spec.label"
                                type="text"
                                placeholder="Feature (e.g. Wattage)"
                                class="w-2/5 rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <input
                                v-model="spec.value"
                                type="text"
                                placeholder="Value (e.g. 550W)"
                                class="flex-1 rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <button
                                type="button"
                                @click="removeSpec(index)"
                                title="Remove specification"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-center">
                        <p class="text-xs text-slate-400">No specifications yet — add detail rows below or tap a suggestion.</p>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="addSpec()"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-[#0D1527] hover:text-[#0D1527]"
                        >
                            <i class="bi bi-plus-lg"></i> Add specification
                        </button>
                        <button
                            v-for="suggestion in specSuggestions"
                            :key="suggestion"
                            type="button"
                            @click="fillFromSuggestion(suggestion)"
                            class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-500 transition hover:border-[#40e0d0] hover:text-[#0D1527]"
                        >
                            {{ suggestion }}
                        </button>
                    </div>
                    <div v-if="form.errors.specs" class="mt-2 text-xs text-red-600">{{ form.errors.specs }}</div>
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
                        Click to upload or drag & drop
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
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Pricing & Stock</h2>
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

            <!-- Online store toggle -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Online Store</h2>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Allow online purchase</p>
                        <p class="mt-1 text-xs text-slate-400">New products are published to the website automatically.</p>
                    </div>
                    <input v-model="form.allow_online_purchase" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-amber-500 focus:ring-amber-400/20" />
                </div>
            </div>

            <!-- Save -->
            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                <p v-if="isEdit" class="text-xs text-slate-400">
                    Last updated: {{ props.product.updated_at }}
                </p>
                <p v-else class="text-xs text-slate-400">
                    SKU is generated automatically for you.
                </p>
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50 sm:w-auto"
                    :disabled="form.processing"
                >
                    <i v-if="form.processing" class="bi bi-hourglass-split animate-spin"></i>
                    <i v-else class="bi bi-check-lg"></i>
                    {{ form.processing ? (isEdit ? 'Saving…' : 'Creating…') : (isEdit ? 'Save Changes' : 'Create Product') }}
                </button>
            </div>
        </div>
    </form>

    <!-- Quick-add category modal -->
    <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" @click.self="showCategoryModal = false">
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Add Category</h3>
                <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showCategoryModal = false">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form @submit.prevent="addCategoryFromForm" class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                    <input v-model="newCategoryName" type="text" placeholder="e.g. Micro Inverters" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>
                <div v-if="categoryError" class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600">{{ categoryError }}</div>
                <div class="flex gap-2 pt-1">
                    <button
                        type="button"
                        class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        @click="showCategoryModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#0D1527] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                        :disabled="categorySaving || !newCategoryName.trim()"
                    >
                        {{ categorySaving ? 'Adding…' : 'Add' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick-add supplier modal -->
    <div v-if="showSupplierModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" @click.self="showSupplierModal = false">
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Add Supplier</h3>
                <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showSupplierModal = false">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form @submit.prevent="createSupplier" class="space-y-3">
                <div v-if="supplierModalError" class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ supplierModalError }}</div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                        <input v-model="supplierForm.name" type="text" placeholder="e.g. Solar Tech Ltd" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="supplierValidationErrors.name" class="mt-1 text-xs text-red-600">{{ supplierValidationErrors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input v-model="supplierForm.phone" type="tel" placeholder="0803 000 0000" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="supplierValidationErrors.phone" class="mt-1 text-xs text-red-600">{{ supplierValidationErrors.phone[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input v-model="supplierForm.email" type="email" placeholder="supplier@email.com" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <p v-if="supplierValidationErrors.email" class="mt-1 text-xs text-red-600">{{ supplierValidationErrors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Contact Person</label>
                        <input v-model="supplierForm.contact_person" type="text" placeholder="Contact name" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                        <textarea v-model="supplierForm.address" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Street address" />
                    </div>
                </div>
                <div class="mt-4 flex gap-2 pt-1">
                    <button
                        type="button"
                        class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        @click="showSupplierModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#0D1527] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                        :disabled="supplierSaving"
                    >
                        {{ supplierSaving ? 'Saving…' : 'Add & Select' }}
                    </button>
                </div>
</form>
        </div>
    </div>

    <!-- Quick-add brand modal -->
    <div v-if="showBrandModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" @click.self="showBrandModal = false">
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Add Brand</h3>
                <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showBrandModal = false">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form @submit.prevent="createBrand" class="space-y-3">
                <div v-if="brandModalError" class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ brandModalError }}</div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                    <input v-model="brandForm.name" type="text" placeholder="e.g. JA Solar, Huawei" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <p v-if="brandValidationErrors.name" class="mt-1 text-xs text-red-600">{{ brandValidationErrors.name[0] }}</p>
                </div>
                <div class="mt-4 flex gap-2 pt-1">
                    <button
                        type="button"
                        class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        @click="showBrandModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#0D1527] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                        :disabled="brandSaving"
                    >
                        {{ brandSaving ? 'Saving…' : 'Add & Select' }}
                    </button>
                </div>
</form>
        </div>
    </div>
</template>