<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    suppliers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const searchForm = useForm({ search: props.filters.search ?? '' });
const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(supplier) {
    editing.value = supplier;
    form.clearErrors();
    form.name = supplier.name;
    form.contact_person = supplier.contact_person ?? '';
    form.phone = supplier.phone ?? '';
    form.email = supplier.email ?? '';
    form.address = supplier.address ?? '';
    form.notes = supplier.notes ?? '';
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(`/admin/suppliers/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post('/admin/suppliers', {
            preserveScroll: true,
            onSuccess: () => (showModal.value = false),
        });
    }
}

function remove(supplier) {
    if (confirm(`Delete supplier ${supplier.name}?`)) {
        useForm().delete(`/admin/suppliers/${supplier.id}`, { preserveScroll: true });
    }
}

function applyFilters() {
    window.location.search = new URLSearchParams(searchForm.data()).toString();
}
</script>

<template>
        <FlashMessages />
        <PageHeader title="Suppliers" subtitle="Track suppliers and what you owe them." />

        <div class="mb-4 flex gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <input v-model="searchForm.search" type="search" placeholder="Search supplier name, phone, emailâ€¦" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Search</button>
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="openCreate">Add Supplier</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Supplier</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Contact Person</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Phone</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Total Purchases</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in suppliers.data" :key="s.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/suppliers/${s.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ s.name }}</Link>
                                <p class="text-xs text-slate-400">{{ s.ref_id }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ s.contact_person || 'â€”' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ s.phone || 'â€”' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(s.total_purchases) }}</td>
                            <td class="px-4 py-3 text-right">
                                <button class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100" @click="openEdit(s)">Edit</button>
                                <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="remove(s)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!suppliers.data.length">
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">No suppliers yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="suppliers" />
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0D1527]/50 p-4" @click.self="showModal = false">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">{{ editing ? 'Edit Supplier' : 'Add Supplier' }}</h2>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Contact person</label>
                            <input v-model="form.contact_person" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                            <input v-model="form.phone" type="tel" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input v-model="form.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                        <input v-model="form.address" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                        <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showModal = false">Cancel</button>
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" :disabled="form.processing">
                            {{ editing ? 'Save Changes' : 'Add Supplier' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
</template>