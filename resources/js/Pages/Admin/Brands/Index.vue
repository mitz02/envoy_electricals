<script setup>
import { ref, watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { useCan } from '@/composables/permissions';
import { badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    brands: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();

const searchForm = useForm({ search: props.filters.search ?? '' });
const showModal = ref(false);
const editing = ref(null);

watch(
    () => props.filters.search,
    (value) => {
        searchForm.search = value ?? '';
    },
);

const form = useForm({
    name: '',
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.name = '';
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
}

function openEdit(brand) {
    editing.value = brand;
    form.clearErrors();
    form.name = brand.name;
    form.is_active = brand.is_active;
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(`/admin/brands/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post('/admin/brands', {
            preserveScroll: true,
            onSuccess: () => (showModal.value = false),
        });
    }
}

function remove(brand) {
    const message = brand.products_count
        ? `Delete brand ${brand.name}? ${brand.products_count} product(s) will be left without a brand.`
        : `Delete brand ${brand.name}?`;

    if (confirm(message)) {
        useForm().delete(`/admin/brands/${brand.id}`, { preserveScroll: true });
    }
}

function applyFilters() {
    router.get(
        '/admin/brands',
        { search: searchForm.search || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function clearFilters() {
    searchForm.search = '';
    applyFilters();
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="Brands" subtitle="Product manufacturers and the ranges you carry." />

    <div class="mb-4 flex gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <input
            v-model="searchForm.search"
            type="search"
            placeholder="Search brands…"
            class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
            @keyup.enter="applyFilters"
        />
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Search</button>
        <button v-if="filters.search" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50" @click="clearFilters">Clear</button>
        <button v-if="has('products.create')" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="openCreate">Add Brand</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Brand</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Slug</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Products</th>
                        <th v-if="has('products.edit') || has('products.delete')" class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="b in brands.data" :key="b.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#0D1527]/5 text-xs font-bold text-[#0D1527]">
                                    {{ b.name.charAt(0).toUpperCase() }}
                                </span>
                                <Link :href="`/admin/brands/${b.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ b.name }}</Link>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ b.slug }}</td>
                        <td class="px-4 py-3">
                            <span :class="badgeClass(b.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">
                                {{ b.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ b.products_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <button v-if="has('products.edit')" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100" @click="openEdit(b)">Edit</button>
                            <button v-if="has('products.delete')" class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="remove(b)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!brands.data.length">
                        <td :colspan="has('products.edit') || has('products.delete') ? 5 : 4" class="px-4 py-12 text-center text-sm text-slate-400">
                            <template v-if="filters.search">No brands match "{{ filters.search }}".</template>
                            <template v-else>No brands yet.</template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="brands" />
        </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0D1527]/50 p-4" @click.self="showModal = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h2 class="text-lg font-bold text-slate-900">{{ editing ? 'Edit Brand' : 'Add Brand' }}</h2>
            <form class="mt-4 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                </div>
                <label v-if="editing" class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500" />
                    Active
                </label>
                <p class="text-xs text-slate-400">The URL slug is generated automatically from the name.</p>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showModal = false">Cancel</button>
                    <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" :disabled="form.processing">
                        {{ editing ? 'Save Changes' : 'Add Brand' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
