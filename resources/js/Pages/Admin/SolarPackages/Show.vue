<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    package: { type: Object, required: true },
});

const { has } = useCan();
const canManage = has('solar.manage');

const form = useForm({});

function remove() {
    if (!confirm(`Remove "${props.package.name}"?`)) {
        return;
    }
    form.delete(`/admin/solar-packages/${props.package.id}`);
}

function toggleVisible() {
    const next = !props.package.is_visible_online;
    router.put(
        `/admin/solar-packages/${props.package.id}`,
        {
            name: props.package.name,
            description: props.package.description ?? '',
            package_price: props.package.package_price,
            installation_cost: props.package.installation_cost ?? 0,
            estimated_load_capacity: props.package.estimated_load_capacity ?? '',
            inverter_capacity: props.package.inverter_capacity ?? '',
            warranty: props.package.warranty ?? '',
            is_featured: props.package.is_featured ? 1 : 0,
            availability: props.package.availability,
            is_visible_online: next ? 1 : 0,
            items: props.package.items.map((i) => ({
                product_id: i.product_id ?? '',
                name: i.name,
                quantity: i.quantity,
                specification: i.specification ?? '',
                unit_cost: i.unit_cost,
            })),
        },
        { preserveScroll: true }
    );
}

const availabilityBadge = {
    available: 'bg-emerald-100 text-emerald-800',
    unavailable: 'bg-slate-100 text-slate-500',
};
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/solar-packages" class="text-sm text-slate-500 hover:text-slate-900">← Back to packages</Link>
    </div>

    <!-- Flow Explanation -->
    <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-100">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                <i class="bi bi-info-circle text-blue-600 text-sm" />
            </div>
            <div class="text-sm text-blue-800 space-y-1">
                <p class="font-semibold">Package Usage Flow:</p>
                <p><strong>1. Quotations:</strong> Admin creates quotation for lead → selects this package → customer gets formal quote.</p>
                <p><strong>2. Calculator:</strong> Customer uses website calculator → system recommends this package based on load.</p>
                <p><strong>3. POS/Sales:</strong> Staff creates sale → adds this package as a line item.</p>
                <p><strong>4. Visibility:</strong> "Visible online" = shows on website. "Featured" = highlighted on homepage/packages page.</p>
                <p><strong>Margin:</strong> Package price − Component costs = Gross margin. Target: ≥ 25%.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Left column: package profile -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ package.ref_id }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ package.name }}</h1>
                </div>
                <button
                    v-if="canManage"
                    @click="toggleVisible"
                    class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold border"
                    :class="package.is_visible_online
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                        : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'"
                    :title="package.is_visible_online ? 'Click to hide online' : 'Click to show online'"
                >
                    {{ package.is_visible_online ? '● Visible online' : '○ Hidden online' }}
                </button>
            </div>
            <p v-if="package.description" class="mt-2 text-sm text-slate-500">{{ package.description }}</p>
            <div class="mt-2 flex items-center gap-2">
                <span :class="badgeClass(availabilityBadge[package.availability] || 'bg-slate-100 text-slate-500')">{{ package.availability }}</span>
                <span v-if="package.is_featured" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">★ Featured</span>
            </div>

            <div class="my-5 space-y-2 text-sm">
                <div class="flex justify-between rounded-lg bg-emerald-50 p-3"><span class="text-emerald-600">Package price</span><span class="font-bold text-emerald-700">{{ naira(package.package_price) }}</span></div>
                <div class="flex justify-between rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Installation</span><span class="font-bold text-slate-900">{{ naira(package.installation_cost) }}</span></div>
                <div class="flex justify-between rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Total investment</span><span class="font-bold text-slate-900">{{ naira(Number(package.package_price) + Number(package.installation_cost || 0)) }}</span></div>
            </div>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Inverter capacity</dt><dd class="font-medium text-slate-900">{{ package.inverter_capacity || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Load capacity</dt><dd class="font-medium text-slate-900">{{ package.estimated_load_capacity || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Warranty</dt><dd class="font-medium text-slate-900">{{ package.warranty || '—' }}</dd></div>
            </dl>

            <div v-if="package.images && package.images.length" class="mt-4">
                <h3 class="text-sm font-semibold text-slate-700 mb-2">Package Images</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    <div v-for="img in package.images" :key="img.id" class="group relative overflow-hidden rounded-xl border border-slate-200">
                        <img :src="img.media ? ('/storage/' + img.media.path) : ''" :alt="img.media?.alt || 'Package image'" class="h-24 w-full object-cover" />
                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/70 to-transparent px-2 pb-1.5 pt-4 text-white">
                            <span v-if="img.is_featured" class="flex items-center gap-1 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950">
                                <i class="bi bi-star-fill"></i> Cover
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <Link v-if="canManage" :href="`/admin/solar-packages/${package.id}/edit`" class="rounded-lg bg-[#0D1527] px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Edit Package</Link>
                <button v-if="canManage" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50" @click="remove">Remove</button>
            </div>
        </div>

        <!-- Right column: components -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Package Components</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ package.items.length }}</span>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            <th class="pb-2 pr-2">Item</th>
                            <th class="pb-2 px-2">Specification</th>
                            <th class="pb-2 px-2 text-right">Qty</th>
                            <th class="pb-2 px-2 text-right">Unit Cost</th>
                            <th class="pb-2 pl-2 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in package.items" :key="item.id" class="hover:bg-slate-50">
                            <td class="py-2.5 pr-2 font-medium text-slate-900">
                                {{ item.name }}
                                <span v-if="item.product" class="block text-[11px] font-normal text-slate-400">{{ item.product.sku }}</span>
                            </td>
                            <td class="py-2.5 px-2 text-slate-600">{{ item.specification || '—' }}</td>
                            <td class="py-2.5 px-2 text-right text-slate-600">{{ item.quantity }}</td>
                            <td class="py-2.5 px-2 text-right text-slate-600">{{ naira(item.unit_cost) }}</td>
                            <td class="py-2.5 pl-2 text-right font-semibold text-slate-900">{{ naira(Number(item.quantity) * Number(item.unit_cost)) }}</td>
                        </tr>
                        <tr v-if="!package.items.length">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-400">No components on this package yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>