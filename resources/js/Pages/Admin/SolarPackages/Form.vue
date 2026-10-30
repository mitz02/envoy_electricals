<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Modal from '@/Components/Modal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    package: { type: Object, default: null },
    products: { type: Array, default: () => [] },
    inverterCapacities: { type: Array, default: () => [] },
    batteryCapacities: { type: Array, default: () => [] },
});

const editing = computed(() => Boolean(props.package));

const inverterCapacitiesList = ref(props.inverterCapacities.map((c) => ({ ...c })));

watch(
    () => props.inverterCapacities,
    (value) => {
        const known = new Set(inverterCapacitiesList.value.map((c) => c.id));
        inverterCapacitiesList.value = [...value.map((c) => ({ ...c })), ...inverterCapacitiesList.value.filter((c) => !known.has(c.id))];
    },
);

const batteryCapacitiesList = ref(props.batteryCapacities.map((c) => ({ ...c })));

watch(
    () => props.batteryCapacities,
    (value) => {
        const known = new Set(batteryCapacitiesList.value.map((c) => c.id));
        batteryCapacitiesList.value = [...value.map((c) => ({ ...c })), ...batteryCapacitiesList.value.filter((c) => !known.has(c.id))];
    },
);

const form = useForm({
    name: props.package?.name ?? '',
    description: props.package?.description ?? '',
    package_price: props.package?.package_price ?? '',
    estimated_load_capacity: props.package?.estimated_load_capacity ?? '',
    inverter_capacity: props.package?.inverter_capacity ?? '',
    custom_inverter_capacity: props.package?.custom_inverter_capacity ?? '',
    battery_capacity: props.package?.battery_capacity ?? '',
    custom_battery_capacity: props.package?.custom_battery_capacity ?? '',
    panel_count: props.package?.panel_count ?? '',
    panel_wattage: props.package?.panel_wattage ?? '',
    warranty: props.package?.warranty ?? '',
    is_featured: props.package ? Boolean(props.package.is_featured) : false,
    availability: props.package?.availability ?? 'available',
    is_visible_online: props.package ? Boolean(props.package.is_visible_online) : true,
    items: props.package?.items?.length
        ? props.package.items.map((i) => ({
              product_id: i.product_id ?? '',
              name: i.name,
              quantity: i.quantity,
              specification: i.specification ?? '',
          }))
        : [],
    images: [],
    remove_images: [],
});

const imageFiles = ref([]);

/* ---------- Product images ---------- */
const existingImages = ref([]);
const newImages = ref([]);
const isDragOver = ref(false);

/* ---------- Inverter Capacity Modal ---------- */
const showCapacityModal = ref(false);
const capacitySaving = ref(false);
const capacityModalError = ref('');
const capacityValidationErrors = ref({});
const capacityForm = ref({
    name: '',
    label: '',
    sort_order: 0,
});

function openCapacityModal() {
    capacityModalError.value = '';
    capacityValidationErrors.value = {};
    capacityForm.value = { name: '', label: '', sort_order: 0 };
    showCapacityModal.value = true;
    nextTick(() => {
        document.getElementById('capacity-name-input')?.focus();
    });
}

async function createCapacity() {
    capacitySaving.value = true;
    capacityModalError.value = '';
    capacityValidationErrors.value = {};

    try {
        const response = await axios.post('/admin/inverter-capacities/quick', {
            name: capacityForm.value.name,
            label: capacityForm.value.label,
            sort_order: capacityForm.value.sort_order,
        });

        const capacity = response.data.capacity;
        if (!capacity) {
            throw new Error('No capacity returned from server');
        }
        inverterCapacitiesList.value.push(capacity);
        form.inverter_capacity = capacity.name;
        form.custom_inverter_capacity = '';
        showCapacityModal.value = false;
    } catch (e) {
        console.error('Create capacity error:', e);
        if (e.response?.data?.errors) {
            capacityValidationErrors.value = e.response.data.errors;
        } else {
            capacityModalError.value = e.response?.data?.message || e.message || 'Could not save capacity. Please try again.';
        }
    } finally {
        capacitySaving.value = false;
    }
}

/* ---------- Battery Capacity Modal ---------- */
const showBatteryModal = ref(false);
const batterySaving = ref(false);
const batteryModalError = ref('');
const batteryValidationErrors = ref({});
const batteryForm = ref({
    name: '',
    label: '',
    sort_order: 0,
});

function openBatteryModal() {
    batteryModalError.value = '';
    batteryValidationErrors.value = {};
    batteryForm.value = { name: '', label: '', sort_order: 0 };
    showBatteryModal.value = true;
    nextTick(() => {
        document.getElementById('battery-name-input')?.focus();
    });
}

async function createBatteryCapacity() {
    batterySaving.value = true;
    batteryModalError.value = '';
    batteryValidationErrors.value = {};

    try {
        const response = await axios.post('/admin/battery-capacities/quick', {
            name: batteryForm.value.name,
            label: batteryForm.value.label,
            sort_order: batteryForm.value.sort_order,
        });

        const capacity = response.data.capacity;
        if (!capacity) {
            throw new Error('No capacity returned from server');
        }
        batteryCapacitiesList.value.push(capacity);
        form.battery_capacity = capacity.name;
        form.custom_battery_capacity = '';
        showBatteryModal.value = false;
    } catch (e) {
        console.error('Create battery capacity error:', e);
        if (e.response?.data?.errors) {
            batteryValidationErrors.value = e.response.data.errors;
        } else {
            batteryModalError.value = e.response?.data?.message || e.message || 'Could not save battery capacity. Please try again.';
        }
    } finally {
        batterySaving.value = false;
    }
}

const fileInput = ref(null);

function initForm() {
    form.name = props.package?.name ?? '';
    form.description = props.package?.description ?? '';
    form.package_price = props.package?.package_price ?? '';
    form.estimated_load_capacity = props.package?.estimated_load_capacity ?? '';
    form.inverter_capacity = props.package?.inverter_capacity ?? '';
    form.custom_inverter_capacity = props.package?.custom_inverter_capacity ?? '';
    form.battery_capacity = props.package?.battery_capacity ?? '';
    form.custom_battery_capacity = props.package?.custom_battery_capacity ?? '';
    form.panel_count = props.package?.panel_count ?? '';
    form.panel_wattage = props.package?.panel_wattage ?? '';
    form.warranty = props.package?.warranty ?? '';
    form.is_featured = props.package ? Boolean(props.package.is_featured) : false;
    form.availability = props.package?.availability ?? 'available';
    form.is_visible_online = props.package ? Boolean(props.package.is_visible_online) : true;
    form.items = props.package?.items?.length
        ? props.package.items.map((i) => ({
              product_id: i.product_id ?? '',
              name: i.name,
              quantity: i.quantity,
              specification: i.specification ?? '',
          }))
        : [];
    form.remove_images = [];
    form.errors = {};

    // Handle custom values not in dropdown options
    const inverterNames = inverterCapacitiesList.value.map(c => c.name);
    if (form.inverter_capacity && !inverterNames.includes(form.inverter_capacity)) {
        form.custom_inverter_capacity = form.inverter_capacity;
        form.inverter_capacity = 'custom';
    }
    const batteryNames = batteryCapacitiesList.value.map(c => c.name);
    if (form.battery_capacity && !batteryNames.includes(form.battery_capacity)) {
        form.custom_battery_capacity = form.battery_capacity;
        form.battery_capacity = 'custom';
    }

    existingImages.value = (props.package?.images ?? []).map((img) => ({ ...img }));
    newImages.value.forEach(img => URL.revokeObjectURL(img.url));
    newImages.value = [];
    imageFiles.value = [];
}

watch(() => props.package?.id, (newId, oldId) => {
    if (newId && newId !== oldId) {
        initForm();
    }
}, { immediate: true });

const totalItems = computed(() =>
    form.items.reduce((sum, i) => sum + Number(i.quantity || 0), 0)
);

function imageSrc(path) {
    return path?.startsWith('/images/') ? path : `/storage/${path}`;
}

function addFiles(files) {
    [...files]
        .filter((f) => f.type?.startsWith('image/'))
        .forEach((file) => {
            newImages.value.push({ file, url: URL.createObjectURL(file) });
            imageFiles.value.push(file);
        });
}

function onFilesPicked(e) {
    addFiles(e.target.files);
    if (fileInput.value) fileInput.value.value = '';
}

function onDrop(e) {
    isDragOver.value = false;
    addFiles(e.dataTransfer.files);
}

function removeNew(index) {
    URL.revokeObjectURL(newImages.value[index].url);
    newImages.value.splice(index, 1);
    imageFiles.value.splice(index, 1);
}

function removeExisting(id) {
    form.remove_images.push(id);
    existingImages.value = existingImages.value.filter((img) => img.id !== id);
}

function submit() {
    form.transform((data) => ({
        ...data,
        is_featured: data.is_featured ? 1 : 0,
        is_visible_online: data.is_visible_online ? 1 : 0,
        images: imageFiles.value,
        ...(editing.value && imageFiles.value.length > 0 ? { _method: 'PUT' } : {}),
    }));

    if (editing.value) {
        if (imageFiles.value.length > 0) {
            form.post(`/admin/solar-packages/${props.package.id}`);
        } else {
            form.put(`/admin/solar-packages/${props.package.id}`);
        }
    } else {
        form.post('/admin/solar-packages');
    }
}

function addItem() {
    form.items.push({ product_id: '', name: '', quantity: 1, specification: '' });
}

function removeItem(index) {
    form.items.splice(index, 1);
}
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/solar-packages" class="text-sm text-slate-500 hover:text-slate-900">← Back to packages</Link>
    </div>

    <div class="max-w-4xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900">{{ editing ? 'Edit Solar Package' : 'New Solar Package' }}</h1>
        <p class="mt-1 text-sm text-slate-500">Configure a pre-built solar solution for the storefront and POS.</p>

        <!-- Flow Explanation -->
        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-100">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="bi bi-info-circle text-blue-600 text-sm" />
                </div>
                <div class="text-sm text-blue-800 space-y-1">
                    <p class="font-semibold">Package Creation Guide:</p>
                    <p><strong>1. Basic Info:</strong> Name, description, total package price (equipment and installation are both included in this price).</p>
                    <p><strong>2. Specs:</strong> Load capacity (kW), inverter capacity, warranty. These help the calculator recommend the right package.</p>
                    <p><strong>3. Components:</strong> Add the items included in the package (inverter, batteries, panels, mounting, etc.). Customers see them as part of the package at no additional cost.</p>
                    <p><strong>4. Images:</strong> Upload multiple images (max 10). First image becomes the cover. Drag & drop supported.</p>
                    <p><strong>5. Visibility:</strong> <em>Visible online</em> = shows on website calculator & packages page. <em>Featured</em> = highlighted on homepage.</p>
                    <p><strong>6. Availability:</strong> <em>Available</em> = customers can purchase/quote. <em>Unavailable</em> = hidden from purchase.</p>
                </div>
            </div>
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Package name</label>
                    <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 3.5kVA Complete Solar Home Kit" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Package price (₦)</label>
                    <input v-model="form.package_price" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <p v-if="form.errors.package_price" class="mt-1 text-xs text-red-600">{{ form.errors.package_price }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Estimated load capacity</label>
                    <input v-model="form.estimated_load_capacity" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 1.8kW continuous" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Inverter capacity</label>
                    <div class="flex">
                        <select v-model="form.inverter_capacity" class="mt-1 flex-1 h-10 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20 rounded-r-none">
                            <option value="">Select capacity</option>
                            <option v-for="cap in inverterCapacitiesList" :key="cap.id" :value="cap.name">{{ cap.label }}</option>
                            <option value="custom">Custom...</option>
                        </select>
                        <button
                            type="button"
                            class="flex h-10 items-center justify-center w-10 rounded-r-lg border border-slate-300 border-l-0 bg-white text-[#0D1527] hover:bg-[#0D1527] hover:text-white hover:border-[#0D1527] transition-colors"
                            @click="openCapacityModal"
                            title="Create new inverter capacity"
                        >
                            <i class="bi bi-plus-lg text-xl" />
                        </button>
                    </div>
                    <input v-if="form.inverter_capacity === 'custom'" v-model="form.custom_inverter_capacity" type="text" class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Enter custom capacity (e.g. 4.5kva)" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Battery capacity</label>
                    <div class="flex">
                        <select v-model="form.battery_capacity" class="mt-1 flex-1 h-10 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20 rounded-r-none">
                            <option value="">Select capacity</option>
                            <option v-for="cap in batteryCapacitiesList" :key="cap.id" :value="cap.name">{{ cap.label }}</option>
                            <option value="custom">Custom...</option>
                        </select>
                        <button
                            type="button"
                            class="flex h-10 items-center justify-center w-10 rounded-r-lg border border-slate-300 border-l-0 bg-white text-[#0D1527] hover:bg-[#0D1527] hover:text-white hover:border-[#0D1527] transition-colors"
                            @click="openBatteryModal"
                            title="Create new battery capacity"
                        >
                            <i class="bi bi-plus-lg text-xl" />
                        </button>
                    </div>
                    <input v-if="form.battery_capacity === 'custom'" v-model="form.custom_battery_capacity" type="text" class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Enter custom capacity (e.g. 150ah)" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Panel count</label>
                    <input v-model="form.panel_count" type="number" min="0" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 6" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Panel wattage</label>
                    <input v-model="form.panel_wattage" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 450W" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Warranty</label>
                    <input v-model="form.warranty" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 12 months" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Availability</label>
                    <select v-model="form.availability" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>
            </div>

            <!-- Package Images -->
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-700">Package Images</p>
                    <span class="text-xs text-slate-400">Max 10 images · PNG, JPG, WEBP · 10MB each</span>
                </div>

                <div
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-8 text-center transition"
                    :class="isDragOver ? 'border-[#0D1527] bg-[#0D1527]/5' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                    @click="fileInput?.click()"
                    @dragover.prevent="isDragOver = true"
                    @dragleave.prevent="isDragOver = false"
                    @drop.prevent="onDrop"
                >
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#0D1527]/5 text-[#0D1527]">
                        <i class="bi bi-images text-xl"></i>
                    </span>
                    <p class="text-sm font-medium text-slate-700">Click to upload or drag & drop</p>
                    <p class="text-xs text-slate-400">PNG, JPG or WEBP · up to 10MB each</p>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        multiple
                        class="hidden"
                        @change="onFilesPicked"
                    />
                </div>

                <div v-if="form.errors.images" class="mt-2 text-xs text-red-600">{{ form.errors.images }}</div>

                <div v-if="existingImages.length || newImages.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    <!-- Existing images -->
                    <div
                        v-for="img in existingImages"
                        :key="'existing-' + img.id"
                        class="group relative overflow-hidden rounded-xl border border-slate-200"
                    >
                        <img :src="imageSrc(img.media?.path)" :alt="img.media?.alt || 'Package image'" class="h-24 w-full object-cover" />
                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/70 to-transparent px-2 pb-1.5 pt-4 text-white">
                            <span v-if="img.is_featured" class="flex items-center gap-1 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950">
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

            <!-- Package Components / Items -->
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Package Components</p>
                        <p class="text-xs text-slate-500">List the equipment & components included in this package (inverter, batteries, panels, mounting rails, etc.). No individual prices needed — they are part of the total package price.</p>
                    </div>
                    <button type="button" class="rounded-lg border border-[#0D1527]/20 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-[#0D1527] hover:bg-[#0D1527]/10" @click="addItem">+ Add Component</button>
                </div>

                <div class="space-y-2">
                    <!-- Column Header -->
                    <div v-if="form.items.length" class="hidden sm:grid grid-cols-12 gap-2 px-2 text-xs font-semibold text-slate-600">
                        <div class="col-span-5">Component Name *</div>
                        <div class="col-span-2">Quantity *</div>
                        <div class="col-span-4">Specification / Notes</div>
                        <div class="col-span-1 text-center">Action</div>
                    </div>

                    <div v-for="(item, idx) in form.items" :key="idx" class="grid grid-cols-12 gap-2 items-center rounded-lg border border-slate-100 bg-slate-50/60 p-2">
                        <div class="col-span-12 sm:col-span-5">
                            <input v-model="item.name" type="text" required placeholder="Component name (e.g. 12V 200Ah Gel Battery)" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <input v-model="item.quantity" type="number" min="1" placeholder="Qty" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <input v-model="item.specification" type="text" placeholder="e.g. Tier-1 Monocrystalline" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div class="col-span-2 sm:col-span-1 text-center">
                            <button type="button" class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600 transition" @click="removeItem(idx)" title="Remove component">
                                <i class="bi bi-trash text-sm" />
                            </button>
                        </div>
                    </div>
                    <p v-if="!form.items.length" class="py-4 text-center text-sm text-slate-400">No components added yet. Click "+ Add Component" to add the items included in this package.</p>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-600">
                    <span>Total items: <span class="font-bold text-slate-900">{{ totalItems }}</span></span>
                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[11px] font-medium">✓ Components are bundled into the package with no extra price</span>
                </div>
            </div>

            <!-- Toggles -->
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3">
                    <span class="text-sm font-medium text-slate-700">Featured on website</span>
                    <input v-model="form.is_featured" type="checkbox" class="h-4 w-4 rounded accent-amber-500" />
                </label>
                <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3">
                    <span class="text-sm font-medium text-slate-700">Visible online</span>
                    <input v-model="form.is_visible_online" type="checkbox" class="h-4 w-4 rounded accent-amber-500" />
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    {{ editing ? 'Save Changes' : 'Create Package' }}
                </button>
                <Link href="/admin/solar-packages" class="text-sm text-slate-500 hover:text-slate-900">Cancel</Link>
            </div>
        </form>
    </div>

    <!-- ============ Inverter Capacity Modal ============ -->
    <Modal :show="showCapacityModal" max-width="md" @close="showCapacityModal = false">
        <div class="p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0D1527] text-white"><i class="bi bi-sliders text-lg" /></span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Add Inverter Capacity</h3>
                        <p class="text-xs text-slate-500">Create a new inverter capacity option for solar packages.</p>
                    </div>
                </div>
                <button type="button" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showCapacityModal = false"><i class="bi bi-x-lg text-sm" /></button>
            </div>

            <form @submit.prevent="createCapacity">
                <div v-if="capacityModalError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700">{{ capacityModalError }}</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
                        <input id="capacity-name-input" v-model="capacityForm.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 1kva" />
                        <p v-if="capacityValidationErrors.name" class="mt-1 text-xs text-red-600">{{ capacityValidationErrors.name[0] }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Label <span class="text-red-500">*</span></label>
                        <input v-model="capacityForm.label" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 1 kVA" />
                        <p v-if="capacityValidationErrors.label" class="mt-1 text-xs text-red-600">{{ capacityValidationErrors.label[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Sort Order</label>
                        <input v-model.number="capacityForm.sort_order" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="0" />
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showCapacityModal = false">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-60" :disabled="capacitySaving"><i :class="capacitySaving ? 'bi bi-hourglass-split animate-spin' : 'bi bi-check-lg'" />{{ capacitySaving ? 'Saving…' : 'Add & Select' }}</button>
                </div>
            </form>
        </div>
    </Modal>

    <!-- ============ Battery Capacity Modal ============ -->
    <Modal :show="showBatteryModal" max-width="md" @close="showBatteryModal = false">
        <div class="p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0D1527] text-white"><i class="bi bi-battery text-lg" /></span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Add Battery Capacity</h3>
                        <p class="text-xs text-slate-500">Create a new battery capacity option for solar packages.</p>
                    </div>
                </div>
                <button type="button" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showBatteryModal = false"><i class="bi bi-x-lg text-sm" /></button>
            </div>

            <form @submit.prevent="createBatteryCapacity">
                <div v-if="batteryModalError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700">{{ batteryModalError }}</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
                        <input id="battery-name-input" v-model="batteryForm.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 100ah" />
                        <p v-if="batteryValidationErrors.name" class="mt-1 text-xs text-red-600">{{ batteryValidationErrors.name[0] }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Label <span class="text-red-500">*</span></label>
                        <input v-model="batteryForm.label" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 100 Ah" />
                        <p v-if="batteryValidationErrors.label" class="mt-1 text-xs text-red-600">{{ batteryValidationErrors.label[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Sort Order</label>
                        <input v-model.number="batteryForm.sort_order" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="0" />
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showBatteryModal = false">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-60" :disabled="batterySaving"><i :class="batterySaving ? 'bi bi-hourglass-split animate-spin' : 'bi bi-check-lg'" />{{ batterySaving ? 'Saving…' : 'Add & Select' }}</button>
                </div>
            </form>
        </div>
    </Modal>
</template>