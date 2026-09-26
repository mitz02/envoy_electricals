<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { useCan } from '@/composables/permissions';
import { formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const { has } = useCan();

const props = defineProps({
    stores: { type: Object, default: () => ({}) },
});

const search = ref('');

function deleteStore(store) {
    if (confirm(`Delete "${store.name}"? This cannot be undone.`)) {
        router.delete(`/admin/stores/${store.code}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="space-y-6">
        <FlashMessages />

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#0D1527]">Stores</h1>
                <p class="text-sm text-slate-500 mt-1">Manage store locations and branches</p>
            </div>

            <Link
                v-if="has('stores.create')"
                href="/admin/stores/create"
                class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90 transition-colors"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Store
            </Link>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                        <th class="py-3 px-4">Store Name</th>
                        <th class="py-3 px-4">Code</th>
                        <th class="py-3 px-4 hidden md:table-cell">Address</th>
                        <th class="py-3 px-4 hidden lg:table-cell">Phone</th>
                        <th class="py-3 px-4 hidden lg:table-cell">Email</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Default</th>
                        <th class="py-3 px-4">Created</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="store in stores.data" :key="store.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#40e0d0]/10 text-[#40e0d0]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">{{ store.name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 font-mono text-sm font-semibold text-slate-700">{{ store.code }}</td>
                        <td class="py-3 px-4 hidden md:table-cell text-sm text-slate-500 truncate max-w-xs">{{ store.address || '—' }}</td>
                        <td class="py-3 px-4 hidden lg:table-cell text-sm text-slate-500">{{ store.phone || '—' }}</td>
                        <td class="py-3 px-4 hidden lg:table-cell text-sm text-slate-500">{{ store.email || '—' }}</td>
                        <td class="py-3 px-4">
                            <span :class="store.is_active
                                ? 'inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700'
                                : 'inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600'">
                                {{ store.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span v-if="store.is_default" class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">
                                Default
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-slate-500">{{ formatDate(store.created_at) }}</td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Link
                                    v-if="has('stores.view')"
                                    :href="`/admin/stores/${store.code}`"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                    title="View"
                                >
                                    <i class="bi bi-eye text-base" />
                                </Link>
                                <Link
                                    v-if="has('stores.edit')"
                                    :href="`/admin/stores/${store.code}/edit`"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                    title="Edit"
                                >
                                    <i class="bi bi-pencil text-base" />
                                </Link>
                                <button
                                    v-if="has('stores.delete') && !store.is_default"
                                    @click="deleteStore(store)"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                                    title="Delete"
                                >
                                    <i class="bi bi-trash text-base" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!stores.data.length">
                        <td colspan="9" class="py-12 text-center text-slate-400">No stores found.</td>
                    </tr>
                </tbody>
            </table>

            <div v-if="stores.last_page > 1" class="px-4 py-3 border-t border-slate-100">
                <Pagination :meta="stores" />
            </div>
        </div>
    </div>
</template>