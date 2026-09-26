<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    brand: { type: Object, required: true },
});
</script>

<template>
    <FlashMessages />

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <Link href="/admin/brands" class="text-sm font-medium text-slate-500 hover:text-slate-800">
            <i class="bi bi-arrow-left mr-1" />Brands
        </Link>
        <div class="ml-auto flex items-center gap-2">
            <Link
                :href="`/admin/products?brand_id=${brand.id}`"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                <i class="bi bi-box-seam" />View in Products
            </Link>
            <Link
                :href="`/admin/products/create?brand_id=${brand.id}`"
                class="inline-flex items-center gap-1.5 rounded-lg bg-[#0D1527] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90"
            >
                <i class="bi bi-plus-lg" />Add Product
            </Link>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center gap-4">
        <span class="flex h-16 w-16 items-center justify-center rounded-xl bg-[#0D1527] text-2xl font-bold text-white">
            {{ brand.name.charAt(0).toUpperCase() }}
        </span>
        <div>
            <PageHeader :title="brand.name" :subtitle="brand.slug" />
            <div class="mt-1 flex items-center gap-2 text-xs">
                <span :class="badgeClass(brand.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">
                    {{ brand.is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                    {{ brand.products_count ?? brand.products?.length ?? 0 }} product(s)
                </span>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <h2 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">Products</h2>

        <div v-if="brand.products?.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">On Hand</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Cost</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Price</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="p in brand.products" :key="p.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="`/admin/products/${p.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ p.name }}</Link>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ p.sku }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="badgeClass(p.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">
                                {{ p.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ p.current_quantity }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ naira(p.cost_price) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(p.selling_price) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-else class="px-4 py-12 text-center text-sm text-slate-400">
            No products carry this brand yet.
        </p>

        <p v-if="brand.products?.length === 10" class="border-t border-slate-100 px-4 py-3 text-xs text-slate-400">
            Showing the 10 most recent products.
            <Link :href="`/admin/products?brand_id=${brand.id}`" class="font-medium text-slate-600 hover:text-slate-900">View all in Products</Link>
        </p>
    </div>
</template>
