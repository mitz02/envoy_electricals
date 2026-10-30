<script setup>
import { ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Modal from '@/Components/Modal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    capacities: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCapacity = ref(null);
const saving = ref(false);
const form = ref({ name: '', label: '', sort_order: 0, is_active: true });
const validationErrors = ref({});
const modalError = ref('');

function openCreateModal() {
    form.value = { name: '', label: '', sort_order: 0, is_active: true };
    validationErrors.value = {};
    modalError.value = '';
    showCreateModal.value = true;
}

function openEditModal(capacity) {
    editingCapacity.value = capacity;
    form.value = { name: capacity.name, label: capacity.label, sort_order: capacity.sort_order ?? 0, is_active: capacity.is_active };
    validationErrors.value = {};
    modalError.value = '';
    showEditModal.value = true;
}

async function createCapacity() {
    saving.value = true;
    modalError.value = '';
    validationErrors.value = {};

    try {
        await axios.post('/admin/battery-capacities', form.value);
        showCreateModal.value = false;
        showEditModal.value = false;
        window.location.reload();
    } catch (e) {
        console.error('Create capacity error:', e);
        if (e.response?.data?.errors) {
            validationErrors.value = e.response.data.errors;
        } else {
            modalError.value = e.response?.data?.message || e.message || 'Could not save capacity. Please try again.';
        }
    } finally {
        saving.value = false;
    }
}

async function updateCapacity() {
    saving.value = true;
    modalError.value = '';
    validationErrors.value = {};

    try {
        await axios.put(`/admin/battery-capacities/${editingCapacity.value.id}`, form.value);
        showCreateModal.value = false;
        showEditModal.value = false;
        window.location.reload();
    } catch (e) {
        console.error('Update capacity error:', e);
        if (e.response?.data?.errors) {
            validationErrors.value = e.response.data.errors;
        } else {
            modalError.value = e.response?.data?.message || e.message || 'Could not update capacity. Please try again.';
        }
    } finally {
        saving.value = false;
    }
}

async function deleteCapacity(capacity) {
    if (!confirm(`Delete "${capacity.label}"? This action cannot be undone.`)) return;

    try {
        await axios.delete(`/admin/battery-capacities/${capacity.id}`);
        window.location.reload();
    } catch (e) {
        console.error('Delete capacity error:', e);
        alert(e.response?.data?.message || 'Could not delete capacity.');
    }
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="Battery Capacities" subtitle="Manage available battery capacity options for solar packages." />

    <div class="mb-4 flex items-center justify-between">
        <button type="button" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="openCreateModal">
            <i class="bi bi-plus-lg mr-2" /> Add Capacity
        </button>
        <div class="relative w-full max-w-xs">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input v-model="search" type="search" placeholder="Search capacities…" class="w-full rounded-lg border-slate-300 py-2 pl-10 pr-4 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Label</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Sort Order</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="capacity in capacities.data" :key="capacity.id" class="hover:bg-slate-50/50">
                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ capacity.name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ capacity.label }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">{{ capacity.sort_order }}</td>
                    <td class="px-4 py-3">
                        <span :class="capacity.is_active ? 'rounded-full bg-emerald-100 text-emerald-800' : 'rounded-full bg-slate-100 text-slate-600'" class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium">
                            {{ capacity.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="openEditModal(capacity)" title="Edit">
                                <i class="bi bi-pencil" />
                            </button>
                            <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" @click="deleteCapacity(capacity)" title="Delete">
                                <i class="bi bi-trash3" />
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="!capacities.data.length">
                    <td colspan="5" class="px-4 py-12 text-center text-slate-400">
                        <i class="bi bi-battery text-3xl mb-2 block" />
                        <p class="font-medium text-slate-500">No capacities yet</p>
                        <p class="text-sm mt-1">Click "Add Capacity" to create your first battery capacity option.</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="capacities.last_page > 1" class="border-t border-slate-100 px-4 py-3">
            <nav class="flex items-center justify-between" aria-label="Pagination">
                <span class="text-sm text-slate-500">Showing {{ capacities.from }} to {{ capacities.to }} of {{ capacities.total }} capacities</span>
                <div class="flex gap-1">
                    <button v-if="capacities.current_page > 1" @click="window.location.href = capacities.prev_page_url" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Previous</button>
                    <button v-if="capacities.current_page < capacities.last_page" @click="window.location.href = capacities.next_page_url" class="rounded-lg bg-[#0D1527] px-3 py-1.5 text-sm text-white hover:bg-[#0D1527]/90">Next</button>
                </div>
            </nav>
        </div>
    </div>

    <!-- Create Modal -->
    <Modal :show="showCreateModal" max-width="md" @close="showCreateModal = false">
        <div class="p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0D1527] text-white"><i class="bi bi-battery text-lg" /></span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Add Battery Capacity</h3>
                        <p class="text-xs text-slate-500">Create a new battery capacity option for solar packages.</p>
                    </div>
                </div>
                <button type="button" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showCreateModal = false"><i class="bi bi-x-lg text-sm" /></button>
            </div>

            <form @submit.prevent="createCapacity">
                <div v-if="modalError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700">{{ modalError }}</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 100ah" />
                        <p v-if="validationErrors.name" class="mt-1 text-xs text-red-600">{{ validationErrors.name[0] }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Label <span class="text-red-500">*</span></label>
                        <input v-model="form.label" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 100 Ah" />
                        <p v-if="validationErrors.label" class="mt-1 text-xs text-red-600">{{ validationErrors.label[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Sort Order</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="0" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Status</label>
                        <select v-model="form.is_active" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="true">Active</option>
                            <option value="false">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showCreateModal = false">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-60" :disabled="saving"><i :class="saving ? 'bi bi-hourglass-split animate-spin' : 'bi bi-check-lg'" />{{ saving ? 'Saving…' : 'Create' }}</button>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Edit Modal -->
    <Modal :show="showEditModal" max-width="md" @close="showEditModal = false">
        <div class="p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0D1527] text-white"><i class="bi bi-battery text-lg" /></span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Edit Battery Capacity</h3>
                        <p class="text-xs text-slate-500">Update the battery capacity details.</p>
                    </div>
                </div>
                <button type="button" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showEditModal = false"><i class="bi bi-x-lg text-sm" /></button>
            </div>

            <form @submit.prevent="updateCapacity">
                <div v-if="modalError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700">{{ modalError }}</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 100ah" />
                        <p v-if="validationErrors.name" class="mt-1 text-xs text-red-600">{{ validationErrors.name[0] }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Label <span class="text-red-500">*</span></label>
                        <input v-model="form.label" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. 100 Ah" />
                        <p v-if="validationErrors.label" class="mt-1 text-xs text-red-600">{{ validationErrors.label[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Sort Order</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="0" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Status</label>
                        <select v-model="form.is_active" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="true">Active</option>
                            <option value="false">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showEditModal = false">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-60" :disabled="saving"><i :class="saving ? 'bi bi-hourglass-split animate-spin' : 'bi bi-check-lg'" />{{ saving ? 'Saving…' : 'Update' }}</button>
                </div>
            </form>
        </div>
    </Modal>
</template>