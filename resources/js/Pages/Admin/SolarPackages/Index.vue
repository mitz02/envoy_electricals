<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    packages: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('solar.manage');

const form = useForm({
    search: props.filters.search ?? '',
    availability: props.filters.availability ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.availability) p.set('availability', form.availability);
    window.location.search = p.toString();
}

const availabilityBadge = {
    available: 'bg-emerald-100 text-emerald-800',
    unavailable: 'bg-slate-100 text-slate-500',
};
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Solar Packages"
        subtitle="Pre-configured solar solutions offered on the website and in POS."
        :actionLabel="canManage ? 'Create Package' : ''"
        :actionHref="canManage ? '/admin/solar-packages/create' : ''"
    />

    <!-- Flow Explanation -->
    <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-100">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                <i class="bi bi-info-circle text-blue-600 text-sm" />
            </div>
            <div class="text-sm text-blue-800 space-y-1">
                <p class="font-semibold">How Solar Packages Work:</p>
                <p><strong>1. Create Package:</strong> Define name, price, installation cost, inverter capacity, load capacity, warranty.</p>
                <p><strong>2. Add Components:</strong> Select products (inverters, batteries, panels, etc.) with quantities and unit costs. System auto-calculates margin.</p>
                <p><strong>3. Visibility:</strong> "Visible online" → shows on website calculator & packages page. "Featured" → highlighted on homepage.</p>
                <p><strong>4. Availability:</strong> "Available" → customers can buy/quote. "Unavailable" → hidden from purchase.</p>
                <p><strong>Flow:</strong> Package created → Added to quotations/leads → Customer selects package → Order/Sale created.</p>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Packages</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.total }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Currently Available</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ summary.available }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Featured Online</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">{{ summary.featured }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input v-model="form.search" type="search" placeholder="Search packages…" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.availability" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All availability</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <!-- Packages Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Package</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Inverter</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Load</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Price</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Installation</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Items</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="p in packages.data" :key="p.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="`/admin/solar-packages/${p.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ p.name }}</Link>
                            <p class="text-xs text-slate-400">{{ p.ref_id }}<span v-if="p.is_featured"> · <span class="font-semibold text-amber-600">Featured</span></span></p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ p.inverter_capacity || '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ p.estimated_load_capacity || '—' }}</td>
                        <td class="px-4 py-3 text-right font-bold text-slate-900">{{ naira(p.package_price) }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ naira(p.installation_cost) }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ p.items_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="badgeClass(availabilityBadge[p.availability] || 'bg-slate-100 text-slate-500')">{{ p.availability }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Link :href="`/admin/solar-packages/${p.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                <Link v-if="canManage" :href="`/admin/solar-packages/${p.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!packages.data.length">
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">No solar packages found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="packages" />
        </div>
    </div>
</template>