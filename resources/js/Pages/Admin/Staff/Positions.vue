<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    positions: { type: Array, default: () => [] },
});

const showCreateModal = ref(false);
const editingPosition = ref(null);
const form = useForm({
    name: '',
    description: '',
    is_active: true,
    sort_order: 0,
});

function openCreateModal() {
    form.reset();
    editingPosition.value = null;
    showCreateModal.value = true;
}

function openEditModal(position) {
    form.reset();
    form.name = position.name;
    form.description = position.description || '';
    form.is_active = position.is_active;
    form.sort_order = position.sort_order || 0;
    editingPosition.value = position;
    showCreateModal.value = true;
}

function closeModal() {
    showCreateModal.value = false;
    form.reset();
    editingPosition.value = null;
}

function submit() {
    if (editingPosition.value) {
        form.put(`/admin/positions/${editingPosition.value.id}`, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post('/admin/positions', {
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

function confirmDelete(position) {
    if (!confirm(`Delete position "${position.name}"?`)) {
        return;
    }
    router.delete(`/admin/positions/${position.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Staff Positions"
        subtitle="Manage job positions for staff profiles."
    />

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Positions</h2>
            <p class="text-sm text-slate-500">Select from these when creating staff profiles</p>
        </div>
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            @click="openCreateModal"
        >
            <i class="bi bi-plus-lg" /> Add Position
        </button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div v-if="props.positions.length" class="divide-y divide-slate-100">
            <div
                v-for="position in props.positions"
                :key="position.id"
                class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 transition-colors"
            >
                <div class="flex items-center gap-4 min-w-0">
                    <span
                        :class="position.is_active ? 'h-2 w-2 rounded-full bg-emerald-500' : 'h-2 w-2 rounded-full bg-slate-300'"
                    />
                    <div>
                        <p class="font-medium text-slate-900">{{ position.name }}</p>
                        <p v-if="position.description" class="text-sm text-slate-500 truncate max-w-xs">{{ position.description }}</p>
                        <p class="text-xs text-slate-400">Sort order: {{ position.sort_order }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span
                        class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                        :class="position.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                    >
                        {{ position.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <button
                        type="button"
                        class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        @click="openEditModal(position)"
                        title="Edit"
                    >
                        <i class="bi bi-pencil" />
                    </button>
                    <button
                        type="button"
                        class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-red-600"
                        @click="confirmDelete(position)"
                        title="Delete"
                    >
                        <i class="bi bi-trash" />
                    </button>
                </div>
            </div>
        </div>
        <div v-else class="px-6 py-12 text-center">
            <i class="bi bi-briefcase text-3xl text-slate-300" />
            <p class="mt-3 text-sm font-medium text-slate-600">No positions yet</p>
            <p class="mt-1 text-xs text-slate-400">Create your first position to get started.</p>
            <button
                type="button"
                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                @click="openCreateModal"
            >
                <i class="bi bi-plus-lg" /> Create Position
            </button>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
        v-if="showCreateModal"
        class="fixed inset-0 z-[60] bg-[#0D1527]/80 backdrop-blur-md flex items-center justify-center p-4"
        @click.self="closeModal"
    >
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-[#0D1527] to-[#1A365D] px-6 py-4 flex items-center justify-between">
                <h3 class="text-white font-extrabold">
                    {{ editingPosition ? 'Edit Position' : 'New Position' }}
                </h3>
                <button type="button" @click="closeModal" class="text-white/70 hover:text-white text-2xl font-bold p-1">✕</button>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Name *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="e.g. Sales Executive, Technician, Manager"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Optional description of this position"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Sort Order</label>
                        <input
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-slate-300 text-[#0D1527] focus:ring-[#0D1527]/20"
                            />
                            <span class="text-sm font-medium text-slate-700">Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button
                        type="button"
                        @click="closeModal"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving…' : (editingPosition ? 'Save Changes' : 'Create Position') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>