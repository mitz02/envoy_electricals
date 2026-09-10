<script setup>
import { computed, reactive } from 'vue';
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
});

const canSeeCost = computed(() => !isEdit.value || has('products.price'));

function submit() {
    if (isEdit.value) {
        form.put(`/admin/products/${props.product.id}`);
    } else {
        form.post('/admin/products');
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