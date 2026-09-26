<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { MASK_GLYPH } from '@/composables/permissions';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permission_modules: { type: Object, default: () => ({}) },
    maskable_fields: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
});

const staffRoles = computed(() => props.roles.filter((r) => !r.is_owner));
const selectedRole = ref(staffRoles.value[0] ?? null);
const showCreateRoleModal = ref(false);
const slugManuallyEdited = ref(false);

const moduleLabels = {
    dashboard: 'Dashboard',
    products: 'Products & Catalogue',
    inventory: 'Inventory & Stock',
    purchases: 'Purchases',
    suppliers: 'Suppliers',
    sales: 'Sales & POS',
    payments: 'Payments',
    customers: 'Customers & CRM',
    expenses: 'Expenses',
    projects: 'Projects',
    staff: 'Staff',
    payroll: 'Payroll',
    assets: 'Assets',
    solar: 'Solar',
    marketing: 'Marketing',
    website: 'Website',
    reports: 'Reports',
    settings: 'Settings',
    audit: 'Audit',
    orders: 'Online Orders',
    feedback: 'Feedback',
    training: 'Academy & Training',
};

const moduleIcons = {
    dashboard: 'bi-grid-1x2-fill',
    products: 'bi-box-seam-fill',
    inventory: 'bi-arrow-repeat',
    purchases: 'bi-basket-fill',
    suppliers: 'bi-buildings-fill',
    sales: 'bi-cart-check-fill',
    payments: 'bi-wallet2',
    customers: 'bi-people-fill',
    expenses: 'bi-receipt',
    projects: 'bi-lightning-charge-fill',
    staff: 'bi-person-badge-fill',
    payroll: 'bi-currency-dollar',
    assets: 'bi-bank',
    solar: 'bi-sun-fill',
    marketing: 'bi-megaphone-fill',
    website: 'bi-globe2',
    reports: 'bi-bar-chart-fill',
    settings: 'bi-gear',
    audit: 'bi-shield-check',
    orders: 'bi-bag-fill',
    feedback: 'bi-chat-dots',
    training: 'bi-mortarboard-fill',
};

const form = useForm({
    name: '',
    slug: '',
    description: '',
    permissions: selectedRole.value ? [...selectedRole.value.permission_slugs] : [],
    masked_fields: selectedRole.value ? [...selectedRole.value.masked_fields] : [],
    allowed_store_ids: selectedRole.value ? [...selectedRole.value.allowed_store_ids] : [],
});

const activeCount = computed(() => form.permissions.length);
const activeMasked = computed(() => form.masked_fields.length);

/**
 * Illustrative value shown next to a maskable field so the super admin can see
 * what a real figure looks like before deciding to hide it.
 */
const sampleValues = {
    money: naira(1250000),
    percent: '24.5%',
};

function sampleValue(field) {
    return sampleValues[field?.type] ?? sampleValues.money;
}

function toggleIn(list, value) {
    const index = list.indexOf(value);

    if (index === -1) {
        list.push(value);
    } else {
        list.splice(index, 1);
    }
}

function togglePermission(slug) {
    toggleIn(form.permissions, slug);
}

function toggleModule(module, slugs) {
    const allOn = slugs.every((slug) => form.permissions.includes(slug));

    form.permissions = allOn
        ? form.permissions.filter((slug) => !slugs.includes(slug))
        : [...new Set([...form.permissions, ...slugs])];
}

function toggleMask(slug) {
    toggleIn(form.masked_fields, slug);
}

function toggleStoreAccess(storeId) {
    toggleIn(form.allowed_store_ids, storeId);
}

function selectRole(role) {
    selectedRole.value = role;
    showCreateRoleModal.value = false;
    form.clearErrors();
    form.permissions = [...role.permission_slugs];
    form.masked_fields = [...role.masked_fields];
    form.allowed_store_ids = [...(role.allowed_store_ids || [])];
}

function openCreateRoleModal() {
    form.reset();
    form.permissions = [];
    form.masked_fields = [];
    form.allowed_store_ids = [];
    selectedRole.value = null;
    slugManuallyEdited.value = false;
    showCreateRoleModal.value = true;
}

function closeCreateRoleModal() {
    showCreateRoleModal.value = false;
    form.reset();
    slugManuallyEdited.value = false;
}

function generateSlug(name) {
    return name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '_')
        .replace(/-+/g, '_')
        .replace(/_+/g, '_');
}

watch(() => form.name, (newName) => {
    if (!slugManuallyEdited.value && !selectedRole.value) {
        form.slug = generateSlug(newName);
    }
});

function submit() {
    if (selectedRole.value) {
        form.post(`/admin/settings/roles/${selectedRole.value.id}`, {
            preserveScroll: true,
        });
    } else {
        form.post('/admin/settings/roles', {
            onSuccess: () => {
                closeCreateRoleModal();
                router.reload();
            },
            preserveScroll: true,
        });
    }
}

function deleteRole(role) {
    if (!confirm(`Delete role "${role.name}"? This cannot be undone.`)) {
        return;
    }
    router.delete(`/admin/settings/roles/${role.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedRole.value?.id === role.id) {
                selectedRole.value = staffRoles.value[0] ?? null;
            }
        },
    });
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="Roles & Access" subtitle="Choose which menus staff roles can see and which sensitive values appear masked on screen." />

    <!-- Role selector -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap gap-2">
            <div
                v-for="role in staffRoles"
                :key="role.id"
                class="inline-flex items-center rounded-xl border text-sm font-bold transition-all"
                :class="selectedRole?.id === role.id ? 'border-[#0D1527] bg-[#0D1527] text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            >
                <button
                    type="button"
                    @click="selectRole(role)"
                    class="inline-flex items-center gap-2 px-4 py-2.5"
                >
                    <i class="bi bi-person-badge"></i>
                    {{ role.name }}
                    <span
                        class="rounded-full px-1.5 py-0.5 text-[10px]"
                        :class="selectedRole?.id === role.id ? 'bg-yellow-400 text-[#0D1527]' : 'bg-slate-100 text-slate-500'"
                    >
                        {{ role.staff_count }}
                    </span>
                </button>
                <button
                    type="button"
                    class="mr-3 rounded p-1 text-slate-400 hover:text-red-500"
                    :class="selectedRole?.id === role.id ? 'hover:bg-white/20' : ''"
                    @click="deleteRole(role)"
                    :title="role.staff_count > 0 ? 'Cannot delete - assigned to staff' : 'Delete role'"
                    :disabled="role.staff_count > 0"
                >
                    <i class="bi bi-trash" :class="role.staff_count > 0 ? 'opacity-30' : ''" />
                </button>
            </div>
        </div>
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
            @click="openCreateRoleModal"
        >
            <i class="bi bi-plus-lg"></i> Create New Role
        </button>
    </div>

    <p class="mb-5 text-xs text-slate-400">
        Owner / Super Admin always has full access — it cannot be edited here.
    </p>

    <form v-if="selectedRole" @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
        <!-- Menus & permissions -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#0D1527] text-[#40e0d0]">
                        <i class="bi bi-layout-sidebar"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Menus & permissions</h2>
                        <p class="text-xs text-slate-400">These control which menu items “{{ selectedRole.name }}” staff see in the sidebar.</p>
                    </div>
                </div>
                <span class="rounded-full bg-[#0D1527]/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#0D1527]">
                    {{ activeCount }} selected
                </span>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div v-for="(perms, module) in permission_modules" :key="module" class="rounded-xl border border-slate-100 p-4">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h3 class="flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-700">
                            <i :class="['bi', moduleIcons[module] || 'bi-folder']" class="text-[#40e0d0]"></i>
                            {{ moduleLabels[module] || module }}
                        </h3>
                        <button
                            type="button"
                            @click="toggleModule(module, perms.map((p) => p.slug))"
                            class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide transition"
                            :class="perms.every((p) => form.permissions.includes(p.slug)) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                        >
                            {{ perms.every((p) => form.permissions.includes(p.slug)) ? 'On' : 'Off' }}
                        </button>
                    </div>
                    <div class="space-y-1.5">
                        <label
                            v-for="perm in perms"
                            :key="perm.slug"
                            class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 transition hover:bg-slate-50"
                        >
                            <input
                                type="checkbox"
                                :checked="form.permissions.includes(perm.slug)"
                                @change="togglePermission(perm.slug)"
                                class="h-4 w-4 rounded accent-yellow-500"
                            />
                            <span class="min-w-0">
                                <span class="block text-[13px] font-semibold text-slate-700">{{ perm.name }}</span>
                                <span class="block truncate text-[11px] text-slate-400">{{ perm.description }}</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Masked fields -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="mb-1 flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                        <i class="bi bi-eye-slash"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Hidden values (asterisks)</h2>
                        <p class="text-xs text-slate-400">Sensitive figures show as {{ MASK_GLYPH }} for this role.</p>
                    </div>
                </div>
                <span class="mt-2 inline-block rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-800">
                    {{ activeMasked }} of {{ maskable_fields.length }} masked
                </span>

                <div class="mt-4 space-y-2">
                    <label
                        v-for="field in maskable_fields"
                        :key="field.slug"
                        class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-amber-200"
                        :class="form.masked_fields.includes(field.slug) ? 'bg-amber-50' : 'bg-white'"
                    >
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 text-[13px] font-semibold text-slate-800">
                                {{ field.label }}
                                <i v-if="form.masked_fields.includes(field.slug)" class="bi bi-eye-slash text-amber-500"></i>
                            </span>
                            <span class="mt-0.5 block truncate text-[11px] text-slate-400">{{ field.hint }}</span>
                        </span>
                        <span class="flex shrink-0 items-center gap-2">
                            <span class="font-mono text-xs" :class="form.masked_fields.includes(field.slug) ? 'text-amber-600' : 'text-slate-500'">
                                {{ form.masked_fields.includes(field.slug) ? MASK_GLYPH : sampleValue(field) }}
                            </span>
                            <input
                                type="checkbox"
                                :checked="form.masked_fields.includes(field.slug)"
                                @change="toggleMask(field.slug)"
                                class="h-4 w-4 rounded accent-yellow-500"
                            />
                        </span>
                    </label>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h3 class="flex items-center gap-2 text-xs font-black uppercase tracking-wide text-slate-700">
                    <i class="bi bi-info-circle text-[#40e0d0]"></i> How it works
                </h3>
                <ul class="mt-3 space-y-2 text-xs leading-relaxed text-slate-500">
                    <li class="flex gap-2"><i class="bi bi-check2-circle text-emerald-500"></i> Unchecking a menu hides that item from the staff sidebar immediately.</li>
                    <li class="flex gap-2"><i class="bi bi-check2-circle text-emerald-500"></i> Masked values render as {{ MASK_GLYPH }} in the dashboard, reports, product cost and profit views.</li>
                    <li class="flex gap-2"><i class="bi bi-check2-circle text-emerald-500"></i> The owner role always sees everything.</li>
                </ul>
            </div>

            <!-- Store Access -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="mb-3 flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#40e0d0]/10 text-[#40e0d0]">
                        <i class="bi bi-shop"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Store access</h2>
                        <p class="text-xs text-slate-400">Limit which branches “{{ selectedRole.name }}” staff can switch to. Leave empty for all stores.</p>
                    </div>
                </div>
                <span class="mt-2 inline-block rounded-full bg-[#40e0d0]/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#40e0d0]">
                    {{ form.allowed_store_ids.length }} of {{ props.stores.length }} stores allowed
                </span>

                <div class="mt-4 space-y-2 max-h-60 overflow-y-auto">
                    <label
                        v-for="store in props.stores"
                        :key="store.id"
                        class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-[#40e0d0]/30"
                        :class="form.allowed_store_ids.includes(store.id) ? 'bg-[#40e0d0]/5' : 'bg-white'"
                    >
                        <span class="min-w-0 flex items-center gap-2">
                            <span :class="store.is_active ? 'text-emerald-600' : 'text-slate-400'" class="h-2 w-2 rounded-full shrink-0" />
                            <span class="block text-[13px] font-semibold text-slate-800 truncate">{{ store.name }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ store.code }}</span>
                        </span>
                        <input
                            type="checkbox"
                            :checked="form.allowed_store_ids.includes(store.id)"
                            @change="toggleStoreAccess(store.id)"
                            class="h-4 w-4 rounded accent-[#40e0d0] shrink-0"
                        />
                    </label>
                    <p v-if="!props.stores.length" class="text-center text-xs text-slate-400 py-4">No stores configured yet.</p>
                </div>
            </div>

            <div class="sticky bottom-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-lg">
                <div class="flex items-center justify-between gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#0D1527] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0D1527]/90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <i v-if="form.processing" class="bi bi-arrow-clockwise animate-spin"></i>
                        <i v-else class="bi bi-check2-square"></i>
                        {{ form.processing ? 'Saving…' : 'Save access for ' + selectedRole.name }}
                    </button>
                </div>
                <div v-if="form.errors.permissions || form.errors.masked_fields" class="mt-2 text-xs text-red-600">
                    {{ form.errors.permissions || form.errors.masked_fields }}
                </div>
            </div>
        </div>
    </form>

    <!-- Create New Role Modal -->
    <div
        v-if="showCreateRoleModal"
        class="fixed inset-0 z-[60] bg-[#0D1527]/80 backdrop-blur-md flex items-center justify-center p-4"
        @click.self="closeCreateRoleModal"
    >
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-[#0D1527] to-[#1A365D] px-6 py-4 flex items-center justify-between">
                <h3 class="text-white font-extrabold">Create New Role</h3>
                <button type="button" @click="closeCreateRoleModal" class="text-white/70 hover:text-white text-2xl font-bold p-1">✕</button>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Role Name *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="e.g. Sales Manager, Warehouse Supervisor"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Slug *</label>
                    <input
                        v-model="form.slug"
                        type="text"
                        required
                        placeholder="e.g. sales_manager, warehouse_supervisor"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        @input="slugManuallyEdited = true"
                        @blur="slugManuallyEdited = true"
                    />
                    <p class="mt-1 text-xs text-slate-400">Auto-generated from name. Lowercase, alphanumeric and underscores only.</p>
                    <p v-if="form.errors.slug" class="mt-1 text-xs text-red-600">{{ form.errors.slug }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        placeholder="Optional description of this role"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button
                        type="button"
                        @click="closeCreateRoleModal"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Creating…' : 'Create Role' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>