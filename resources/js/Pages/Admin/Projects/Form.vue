<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Modal from '@/Components/Modal.vue';
import { formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    project: { type: Object, default: null },
    customers: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.project);

const customersList = ref(props.customers.map((c) => ({ ...c })));

watch(
    () => props.customers,
    (value) => {
        const known = new Set(customersList.value.map((c) => c.id));
        customersList.value = [...value.map((c) => ({ ...c })), ...customersList.value.filter((c) => !known.has(c.id))];
    },
);

const form = useForm({
    name: props.project?.name ?? '',
    description: props.project?.description ?? '',
    customer_id: props.project?.customer_id ?? '',
    customer_address: props.project?.customer_address ?? '',
    location: props.project?.location ?? '',
    contract_value: props.project?.contract_value ?? '',
    status: props.project?.status ?? 'draft',
    start_date: props.project?.start_date ?? '',
    expected_completion_date: props.project?.expected_completion_date ?? '',
    assigned_user_id: props.project?.assigned_user_id ?? '',
    notes: props.project?.notes ?? '',
    published: props.project?.published ?? false,
});

watch(
    () => form.customer_id,
    (id) => {
        const c = customersList.value.find((c) => String(c.id) === String(id));
        if (c && c.address) {
            form.customer_address = c.address;
        }
    },
);

const showCustomerModal = ref(false);
const customerSaving = ref(false);
const customerModalError = ref('');
const customerValidationErrors = ref({});
const customerForm = ref({
    name: '',
    phone: '',
    email: '',
    address: '',
    location: '',
    customer_type: 'regular',
});

function openCustomerModal() {
    customerModalError.value = '';
    customerValidationErrors.value = {};
    customerForm.value = { name: '', phone: '', email: '', address: '', location: '', customer_type: 'regular' };
    showCustomerModal.value = true;
    nextTick(() => {
        document.getElementById('customer-name-input')?.focus();
    });
}

async function createCustomer() {
    customerSaving.value = true;
    customerModalError.value = '';
    customerValidationErrors.value = {};

    try {
        const response = await axios.post('/admin/customers/quick', {
            name: customerForm.value.name,
            phone: customerForm.value.phone,
            email: customerForm.value.email,
            address: customerForm.value.address,
            location: customerForm.value.location,
            customer_type: customerForm.value.customer_type,
        });

        const customer = response.data.customer;
        if (!customer) {
            throw new Error('No customer returned from server');
        }
        customersList.value.push(customer);
        form.customer_id = customer.id;
        if (customer.address) {
            form.customer_address = customer.address;
        }
        showCustomerModal.value = false;
    } catch (e) {
        console.error('Create customer error:', e);
        if (e.response?.data?.errors) {
            customerValidationErrors.value = e.response.data.errors;
        } else {
            customerModalError.value = e.response?.data?.message || e.message || 'Could not save customer. Please try again.';
        }
    } finally {
        customerSaving.value = false;
    }
}

function submit() {
    if (isEdit.value) {
        form.put(`/admin/projects/${props.project.id}`);
    } else {
        form.post('/admin/projects');
    }
}

function roleLabel(user) {
    const role = user.role_name || user.role?.name;
    return role ? ` · ${role}` : '';
}
</script>

<template>
        <FlashMessages />
        <PageHeader :title="isEdit ? `Edit ${project.ref_id} — ${project.name}` : 'New Project'" :subtitle="isEdit ? `Started ${formatDate(project.start_date)}` : 'Create a solar or electrical installation project.'" />

        <form class="space-y-5" @submit.prevent="submit">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Project Information</h2>
                <div class="grid gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Project name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Customer</label>
                        <div class="flex">
                            <select v-model="form.customer_id" class="flex-1 h-10 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20 rounded-r-none" @change="form.customer_id">
                                <option value="">— No customer —</option>
                                <option v-for="c in customersList" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <button
                                type="button"
                                class="flex h-10 items-center justify-center w-10 rounded-r-lg border border-slate-300 border-l-0 bg-white text-[#0D1527] hover:bg-[#0D1527] hover:text-white hover:border-[#0D1527] transition-colors"
                                @click="openCustomerModal"
                                title="Create new customer"
                            >
                                <i class="bi bi-plus-lg text-xl" />
                            </button>
                        </div>
                        <div v-if="form.errors.customer_id" class="mt-1 text-xs text-red-600">{{ form.errors.customer_id }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Location</label>
                        <input v-model="form.location" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Customer address</label>
                        <input v-model="form.customer_address" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Contract value (₦) <span class="text-red-500">*</span></label>
                        <input v-model="form.contract_value" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.contract_value" class="mt-1 text-xs text-red-600">{{ form.errors.contract_value }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                        <select v-model="form.status" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option v-for="s in statuses" :key="s" :value="s">{{ s.replace('_', ' ') }}</option>
                        </select>
                        <div v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Start date</label>
                        <input v-model="form.start_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Expected completion date</label>
                        <input v-model="form.expected_completion_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                        <div v-if="form.errors.expected_completion_date" class="mt-1 text-xs text-red-600">{{ form.errors.expected_completion_date }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Assigned staff</label>
                        <select v-model="form.assigned_user_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">— Unassigned —</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}{{ roleLabel(u) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.published" type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-amber-500 focus:ring-amber-400/20" />
                        <label class="text-sm font-medium text-slate-700">Published on website (visible in project gallery)</label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex gap-2 text-xs text-slate-400">
                    <span v-if="isEdit">Ref: {{ project.ref_id }} · Started: {{ formatDate(project.start_date) }}</span>
                </div>
                <button type="submit" class="rounded-lg bg-[#0D1527] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50" :disabled="form.processing">
                    {{ isEdit ? 'Save Changes' : 'Create Project' }}
                </button>
            </div>
        </form>

    <!-- ============ Customer Modal ============ -->
    <Modal :show="showCustomerModal" max-width="lg" @close="showCustomerModal = false">
        <div class="p-6 sm:p-7">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0D1527] text-white">
                        <i class="bi bi-person-plus text-lg" />
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Customer</h3>
                        <p class="text-xs text-slate-500">Create new customer with details.</p>
                    </div>
                </div>
                <button type="button" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="showCustomerModal = false">
                    <i class="bi bi-x-lg text-sm" />
                </button>
            </div>

            <form @submit.prevent="createCustomer">
                <div v-if="customerModalError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-medium text-red-700">{{ customerModalError }}</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Name <span class="text-red-500">*</span></label>
                        <input id="customer-name-input" v-model="customerForm.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. John Doe" />
                        <p v-if="customerValidationErrors.name" class="mt-1 text-xs text-red-600">{{ customerValidationErrors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone</label>
                        <input v-model="customerForm.phone" type="tel" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="0803 000 0000" />
                        <p v-if="customerValidationErrors.phone" class="mt-1 text-xs text-red-600">{{ customerValidationErrors.phone[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Email</label>
                        <input v-model="customerForm.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="customer@email.com" />
                        <p v-if="customerValidationErrors.email" class="mt-1 text-xs text-red-600">{{ customerValidationErrors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Type</label>
                        <select v-model="customerForm.customer_type" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="walk_in">Walk-in</option>
                            <option value="regular">Regular</option>
                            <option value="corporate">Corporate</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Address</label>
                        <textarea v-model="customerForm.address" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="Street address" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Location</label>
                        <input v-model="customerForm.location" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="City/Region" />
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="showCustomerModal = false">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-60" :disabled="customerSaving"><i :class="customerSaving ? 'bi bi-hourglass-split animate-spin' : 'bi bi-check-lg'" />{{ customerSaving ? 'Saving…' : 'Add & Select' }}</button>
                </div>
            </form>
        </div>
    </Modal>
</template>