<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({ search: props.filters.search ?? '' });

function applyFilters() {
    window.location.search = new URLSearchParams(form.data()).toString();
}
</script>

<template>
        <FlashMessages />
        <PageHeader title="Customers" subtitle="Customer records and their purchasing history." action-href="/admin/customers/create" action-label="Add Customer" />

        <div class="mb-4 flex gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <input v-model="form.search" type="search" placeholder="Search name, phone, email, refâ€¦" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Search</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Ref</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Phone</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Lifetime Purchases</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Type</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in customers.data" :key="c.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/customers/${c.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ c.name }}</Link>
                                <p v-if="c.email" class="text-xs text-slate-400">{{ c.email }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ c.ref_id }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ c.phone || 'â€”' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(c.lifetime_purchases) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium capitalize text-slate-700">{{ c.customer_type }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="`/admin/customers/${c.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="!customers.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No customers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="customers" />
            </div>
        </div>
</template>