<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    package: { type: Object, default: null },
    products: { type: Array, default: () => [] },
});

const editing = computed(() => Boolean(props.package));

const form = useForm({
    name: props.package?.name ?? '',
    description: props.package?.description ?? '',
    package_price: props.package?.package_price ?? '',
    installation_cost: props.package?.installation_cost ?? 0,
    estimated_load_capacity: props.package?.estimated_load_capacity ?? '',
    inverter_capacity: props.package?.inverter_capacity ?? '',
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
              unit_cost: i.unit_cost,
          }))
        : [],
});

const totalCost = computed(() =>
    form.items.reduce((sum, i) => sum + Number(i.quantity || 0) * Number(i.unit_cost || 0), 0)
);

const margin = computed(() => {
    const price = Number(form.package_price || 0);
    const cost = totalCost.value;
    if (price <= 0) return 0;
    return ((price - cost) / price) * 100;
});

function addItem() {
    form.items.push({ product_id: '', name: '', quantity: 1, specification: '', unit_cost: '' });
}

function removeItem(index) {
    form.items.splice(index, 1);
}

function onProductSelect(item) {
    const product = props.products.find((p) => String(p.id) === String(item.product_id));
    if (product && !item.name) {
        item.name = product.name;
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        is_featured: data.is_featured ? 1 : 0,
        is_visible_online: data.is_visible_online ? 1 : 0,
    }));
    if (editing.value) {
        form.put(`/admin/solar-packages/${props.package.id}`);
    } else {
        form.post('/admin/solar-packages');
    }
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
                    <label class="block text-sm font-medium text-slate-700">Installation cost (₦)</label>
                    <input v-model="form.installation_cost" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Estimated load capacity</label>
                    <input v-model="form.estimated_load_capacity" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 1.8kW continuous" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Inverter capacity</label>
                    <select v-model="form.inverter_capacity" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="">Select capacity</option>
                        <option v-for="cap in ['1kva', '2.5kva', '3kva', '5kva', '7.5kva', '10kva', '15kva', 'custom']" :key="cap" :value="cap">{{ cap }}</option>
                    </select>
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

            <!-- Package Components / Items -->
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-700">Components &amp; Items</p>
                    <button type="button" class="rounded-lg border border-[#0D1527]/20 px-3 py-1.5 text-xs font-semibold text-[#0D1527] hover:bg-[#0D1527]/5" @click="addItem">+ Add Item</button>
                </div>

                <div class="space-y-2">
                    <div v-for="(item, idx) in form.items" :key="idx" class="grid grid-cols-12 gap-2 rounded-lg border border-slate-100 bg-slate-50/60 p-2">
                        <div class="col-span-6 lg:col-span-4">
                            <select v-model="item.product_id" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20" @change="onProductSelect(item)">
                                <option value="">— choose product —</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div class="col-span-6 lg:col-span-3">
                            <input v-model="item.name" type="text" required placeholder="Item name" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div class="col-span-3 lg:col-span-1">
                            <input v-model="item.quantity" type="number" min="1" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div class="col-span-6 lg:col-span-2">
                            <input v-model="item.unit_cost" type="number" min="0" step="0.01" placeholder="Unit cost" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div class="col-span-3 lg:col-span-2">
                            <input v-model="item.specification" type="text" placeholder="Specification" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>
                    <p v-if="!form.items.length" class="py-4 text-center text-sm text-slate-400">No items added yet.</p>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-600">
                    <span>Item cost total: <span class="font-bold text-slate-900">{{ naira(totalCost) }}</span></span>
                    <span>Margin: <span :class="margin < 0 ? 'font-bold text-red-600' : 'font-bold text-emerald-600'">{{ margin.toFixed(1) }}%</span></span>
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
</template>