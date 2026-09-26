<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useCan } from '@/composables/permissions';
import { naira, maskNaira, formatDate, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    project: { type: Object, required: true },
    products: { type: Array, default: () => [] },
});

const { has } = useCan();
const page = usePage();
const isOwner = computed(() => page.props.auth?.user?.role_slug === 'owner');

const tab = ref('overview');
const tabs = ['overview', 'materials', 'payments', 'expenses', 'media'];

const statusColors = {
    draft: 'bg-slate-100 text-slate-700',
    quotation: 'bg-sky-100 text-sky-700',
    approved: 'bg-indigo-100 text-indigo-700',
    in_progress: 'bg-amber-100 text-amber-800',
    installation: 'bg-amber-100 text-amber-700',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-700',
};

const statusLabel = (s) => (s ? s.replace('_', ' ') : '-');

const costBreakdown = computed(() => [
    { label: 'Materials', value: props.project.material_cost, cls: 'text-slate-900' },
    { label: 'Labour', value: props.project.labour_cost, cls: 'text-slate-900' },
    { label: 'Transport', value: props.project.transport_cost, cls: 'text-slate-900' },
    { label: 'Other', value: props.project.other_cost, cls: 'text-slate-900' },
]);

// ---- status quick-change
const statusForm = useForm({ status: props.project.status });
function changeStatus() {
    if (statusForm.status === props.project.status) return;
    statusForm.post(`/admin/projects/${props.project.id}/status`, { preserveScroll: true });
}

// ---- materials
const materialForm = useForm({
    product_id: '',
    quantity: 1,
    unit_cost: '',
    issue_now: false,
});

function pickProduct() {
    const p = props.products.find((p) => String(p.id) === String(materialForm.product_id));
    if (p) materialForm.unit_cost = p.average_cost ?? '';
}

function addMaterial() {
    materialForm.post(`/admin/projects/${props.project.id}/materials`, {
        preserveScroll: true,
        onSuccess: () => materialForm.reset('product_id', 'quantity', 'unit_cost', 'issue_now'),
    });
}

function issueMaterial(material) {
    router.post(`/admin/projects/${props.project.id}/materials/${material.id}/issue`, {}, { preserveScroll: true });
}

function removeMaterial(material) {
    if (!confirm(`Remove ${material.product?.name} from this project?${material.issued_to_inventory ? ' Stock will be returned to inventory.' : ''}`)) return;
    router.delete(`/admin/projects/${props.project.id}/materials/${material.id}`, { preserveScroll: true });
}

// ---- payments
const paymentForm = useForm({
    payment_date: new Date().toISOString().slice(0, 10),
    amount: '',
    payment_method: 'cash',
    reference: '',
    remarks: '',
});

function addPayment() {
    paymentForm.post(`/admin/projects/${props.project.id}/payments`, {
        preserveScroll: true,
        onSuccess: () => paymentForm.reset('amount', 'reference', 'remarks'),
    });
}

function deletePayment(payment) {
    if (!confirm(`Delete payment of ${naira(payment.amount)}?`)) return;
    router.delete(`/admin/projects/${props.project.id}/payments/${payment.id}`, { preserveScroll: true });
}

// ---- expenses
const expenseForm = useForm({
    expense_type: 'labour',
    amount: '',
    expense_date: new Date().toISOString().slice(0, 10),
    payee: '',
    notes: '',
});

const expenseTypes = [
    { value: 'labour', label: 'Labour' },
    { value: 'transport', label: 'Transport' },
    { value: 'other', label: 'Other' },
];

function addExpense() {
    expenseForm.post(`/admin/projects/${props.project.id}/expenses`, {
        preserveScroll: true,
        onSuccess: () => expenseForm.reset('amount', 'payee', 'notes'),
    });
}

function deleteExpense(expense) {
    if (!confirm(`Delete ${expense.expense_type} expense of ${naira(expense.amount)}?`)) return;
    router.delete(`/admin/projects/${props.project.id}/expenses/${expense.id}`, { preserveScroll: true });
}

// ---- media
const mediaForm = useForm({
    files: [],
    stage: '',
    caption: '',
    published: false,
});

const stages = ['before', 'installation', 'completed', 'document'];

function onFiles(e) {
    mediaForm.files = [...e.target.files];
}

function uploadMedia() {
    mediaForm.post(`/admin/projects/${props.project.id}/media`, {
        preserveScroll: true,
        onSuccess: () => mediaForm.reset('files', 'stage', 'caption', 'published'),
        onFinish: () => { document.getElementById('media-input').value = ''; },
    });
}

function togglePublish(media) {
    router.post(`/admin/projects/${props.project.id}/media/${media.id}/publish`, { published: !media.published }, { preserveScroll: true });
}

function deleteProject() {
    if (!confirm('Delete this project? Any materials issued will be returned to inventory.')) return;
    router.delete(`/admin/projects/${props.project.id}`);
}

function deleteMedia(media) {
    if (!confirm('Delete this media item?')) return;
    router.delete(`/admin/projects/${props.project.id}/media/${media.id}`, { preserveScroll: true });
}

const hasMaterials = computed(() => has('projects.materials') || isOwner.value);
const hasPayments = computed(() => has('projects.payments') || isOwner.value);
const hasExpenses = computed(() => has('projects.expenses') || isOwner.value);
const hasMedia = computed(() => has('projects.media') || isOwner.value);
const canEdit = computed(() => has('projects.edit') || isOwner.value);
const canDelete = computed(() => has('projects.delete') || isOwner.value);
</script>

<template>
        <FlashMessages />

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Link href="/admin/projects" class="text-xs font-medium text-slate-500 hover:text-slate-800">← All Projects</Link>
                <h1 class="mt-1 flex flex-wrap items-center gap-3 text-xl font-bold text-slate-900 sm:text-2xl">
                    {{ project.name }}
                    <span :class="badgeClass(statusColors[project.status])">{{ statusLabel(project.status) }}</span>
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ project.ref_id }}
                    <template v-if="project.customer"> · {{ project.customer.name }}</template>
                    <template v-if="project.location"> · {{ project.location }}</template>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <select v-if="canEdit" v-model="statusForm.status" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="changeStatus">
                    <option v-for="s in ($page.props.statuses || [])" :key="s" :value="s">{{ statusLabel(s) }}</option>
                </select>
                <Link v-if="canEdit" :href="`/admin/projects/${project.id}/edit`" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</Link>
                <Link v-if="canDelete" :href="`/admin/projects/${project.id}`" method="delete" as="button" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50" @click="deleteProject">Delete</Link>
            </div>
        </div>

        <!-- Financial snapshot -->
        <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contract Value</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ naira(project.contract_value) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Received</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ naira(project.amount_received) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Balance</p>
                <p class="mt-2 text-2xl font-bold" :class="project.balance > 0 ? 'text-amber-700' : 'text-slate-900'">{{ naira(project.balance) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Project Cost</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ maskNaira('project_cost', project.project_cost) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Gross Profit</p>
                <p class="mt-2 text-2xl font-bold" :class="project.gross_profit >= 0 ? 'text-emerald-700' : 'text-red-700'">{{ maskNaira('gross_profit', project.gross_profit) }}</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 flex flex-wrap gap-1 border-b border-slate-200">
            <button v-for="t in tabs" :key="t" @click="tab = t"
                    class="rounded-t-lg px-4 py-2.5 text-sm font-medium capitalize"
                    :class="tab === t ? 'border-b-2 border-slate-900 text-slate-900' : 'text-slate-500 hover:text-slate-800'">
                {{ t }}
            </button>
        </div>

        <!-- OVERVIEW -->
        <div v-if="tab === 'overview'" class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Customer</dt><dd class="font-medium text-slate-900">{{ project.customer?.name || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Customer address</dt><dd class="font-medium text-slate-900">{{ project.customer_address || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Location</dt><dd class="font-medium text-slate-900">{{ project.location || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Start date</dt><dd class="font-medium text-slate-900">{{ formatDate(project.start_date) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Expected completion</dt><dd class="font-medium text-slate-900">{{ formatDate(project.expected_completion_date) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Completion date</dt><dd class="font-medium text-slate-900">{{ formatDate(project.completion_date) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Assigned staff</dt><dd class="font-medium text-slate-900">{{ project.assignedUser?.name || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Technician</dt><dd class="font-medium text-slate-900">{{ project.technicianUser?.name || '—' }}</dd></div>
                </dl>
                <p v-if="project.description" class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-600">{{ project.description }}</p>
                <p v-if="project.notes" class="mt-3 text-sm text-slate-500"><span class="font-medium text-slate-700">Notes:</span> {{ project.notes }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Profit &amp; Loss</h2>
                <div class="mb-4 overflow-hidden rounded-lg border border-slate-200">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <tr class="bg-slate-50">
                                <td class="px-4 py-2.5 font-semibold text-slate-700">Contract value</td>
                                <td class="px-4 py-2.5 text-right font-bold text-slate-900">{{ naira(project.contract_value) }}</td>
                            </tr>
                            <tr v-for="c in costBreakdown" :key="c.label">
                                <td class="px-4 py-2.5 text-slate-600">{{ c.label }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-900">-{{ naira(c.value) }}</td>
                            </tr>
                            <tr class="bg-slate-50">
                                <td class="px-4 py-2.5 font-semibold text-slate-700">Project cost</td>
                                <td class="px-4 py-2.5 text-right font-bold text-slate-900">-{{ naira(project.project_cost) }}</td>
                            </tr>
                            <tr class="bg-emerald-50">
                                <td class="px-4 py-2.5 font-bold text-emerald-800">Gross profit</td>
                                <td class="px-4 py-2.5 text-right font-bold text-emerald-800">{{ naira(project.gross_profit) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Payment status</span>
                    <span class="font-semibold" :class="project.balance > 0 ? 'text-amber-700' : 'text-emerald-700'">
                        {{ project.balance > 0 ? `${naira(project.balance)} outstanding` : 'Fully paid' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- MATERIALS -->
        <div v-if="tab === 'materials'" class="space-y-5">
            <div v-if="hasMaterials" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Add Material</h2>
                <form class="grid gap-3 sm:grid-cols-12" @submit.prevent="addMaterial">
                    <div class="sm:col-span-5">
                        <select v-model="materialForm.product_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="pickProduct">
                            <option value="">Select product…</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                        </select>
                        <div v-if="materialForm.errors.product_id" class="mt-1 text-xs text-red-600">{{ materialForm.errors.product_id }}</div>
                    </div>
                    <div class="sm:col-span-2">
                        <input v-model="materialForm.quantity" type="number" min="1" step="1" placeholder="Qty" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-2">
                        <input v-model="materialForm.unit_cost" type="number" min="0" step="0.01" placeholder="Unit cost" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-3">
                        <input v-model="materialForm.issue_now" type="checkbox" class="rounded border-slate-300" />
                        Issue now
                    </label>
                    <div class="flex items-end gap-2 sm:col-span-12">
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50" :disabled="materialForm.processing">Add / Issue</button>
                        <span class="text-xs text-slate-400">Leave unit cost blank to use the product's average cost. Issuing deducts stock automatically.</span>
                    </div>
                </form>
                <div v-if="materialForm.errors.product" class="mt-2 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700">{{ materialForm.errors.product }}</div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Qty</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Unit Cost</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Total</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Issued Date</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in project.materials" :key="m.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ m.product?.name }}</p>
                                    <p class="text-xs text-slate-400">{{ m.product?.sku }} · stock: {{ m.product?.current_quantity }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">{{ m.quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ maskNaira('inventory_value', m.unit_cost, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ maskNaira('inventory_value', m.total) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="m.issued_to_inventory ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                                        {{ m.issued_to_inventory ? 'Issued' : 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-slate-500">{{ m.issued_date ? formatDate(m.issued_date) : '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <template v-if="hasMaterials">
                                        <button v-if="!m.issued_to_inventory" class="rounded-md px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50" @click="issueMaterial(m)">Issue</button>
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="removeMaterial(m)">Remove</button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="!project.materials.length">
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">No materials added yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PAYMENTS -->
        <div v-if="tab === 'payments'" class="space-y-5">
            <div v-if="hasPayments" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Record Project Payment</h2>
                <form class="grid gap-3 sm:grid-cols-12" @submit.prevent="addPayment">
                    <div class="sm:col-span-3">
                        <input v-model="paymentForm.payment_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-2">
                        <input v-model="paymentForm.amount" type="number" min="0.01" step="0.01" placeholder="Amount (₦)" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-2">
                        <select v-model="paymentForm.payment_method" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="pos">POS</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <input v-model="paymentForm.reference" type="text" placeholder="Reference" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-3">
                        <input v-model="paymentForm.remarks" type="text" placeholder="Remarks" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-12">
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50" :disabled="paymentForm.processing">Record Payment</button>
                        <span v-if="paymentForm.errors.amount" class="ml-3 text-xs text-red-600">{{ paymentForm.errors.amount }}</span>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Ref</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Method</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Reference</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Remarks</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                                <th v-if="hasPayments" class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in project.payments" :key="p.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ p.ref_id }}</td>
                                <td class="px-4 py-3">{{ formatDate(p.payment_date) }}</td>
                                <td class="px-4 py-3 capitalize text-slate-600">{{ p.payment_method }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ p.reference || '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ p.remarks || '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ naira(p.amount) }}</td>
                                <td v-if="hasPayments" class="px-4 py-3 text-right">
                                    <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="deletePayment(p)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!project.payments.length">
                                <td :colspan="hasPayments ? 7 : 6" class="px-4 py-12 text-center text-sm text-slate-400">No payments recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- EXPENSES -->
        <div v-if="tab === 'expenses'" class="space-y-5">
            <div v-if="hasExpenses" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Record Project Expense</h2>
                <form class="grid gap-3 sm:grid-cols-12" @submit.prevent="addExpense">
                    <div class="sm:col-span-2">
                        <select v-model="expenseForm.expense_type" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option v-for="t in expenseTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <input v-model="expenseForm.amount" type="number" min="0.01" step="0.01" placeholder="Amount (₦)" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-2">
                        <input v-model="expenseForm.expense_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-3">
                        <input v-model="expenseForm.payee" type="text" placeholder="Payee" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-3">
                        <input v-model="expenseForm.notes" type="text" placeholder="Notes" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="sm:col-span-12">
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50" :disabled="expenseForm.processing">Record Expense</button>
                        <span v-if="expenseForm.errors.amount" class="ml-3 text-xs text-red-600">{{ expenseForm.errors.amount }}</span>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Ref</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Payee</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Notes</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                                <th v-if="hasExpenses" class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="e in project.expenses" :key="e.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ e.ref_id }}</td>
                                <td class="px-4 py-3 capitalize text-slate-600">{{ e.expense_type }}</td>
                                <td class="px-4 py-3">{{ formatDate(e.expense_date) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ e.payee || '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ e.notes || '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(e.amount) }}</td>
                                <td v-if="hasExpenses" class="px-4 py-3 text-right">
                                    <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="deleteExpense(e)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!project.expenses.length">
                                <td :colspan="hasExpenses ? 7 : 6" class="px-4 py-12 text-center text-sm text-slate-400">No project expenses recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MEDIA -->
        <div v-if="tab === 'media'" class="space-y-5">
            <div v-if="hasMedia" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Upload Photos / Videos / Documents</h2>
                <form class="grid gap-3 sm:grid-cols-12" @submit.prevent="uploadMedia">
                    <div class="sm:col-span-5">
                        <input id="media-input" type="file" multiple accept="image/*,video/*,.pdf,.doc,.docx" class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" @change="onFiles" />
                    </div>
                    <div class="sm:col-span-2">
                        <select v-model="mediaForm.stage" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                            <option value="">Stage…</option>
                            <option v-for="s in stages" :key="s" :value="s">{{ s.replace('_', ' ') }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <input v-model="mediaForm.caption" type="text" placeholder="Caption" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    </div>
                    <div class="flex items-center gap-4 sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input v-model="mediaForm.published" type="checkbox" class="rounded border-slate-300" />
                            Publish
                        </label>
                        <button type="submit" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90 disabled:opacity-50" :disabled="mediaForm.processing">Upload</button>
                    </div>
                    <div v-if="mediaForm.errors.files" class="sm:col-span-12 text-xs text-red-600">{{ mediaForm.errors.files }}</div>
                </form>
            </div>

            <div v-if="project.media.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div v-for="m in project.media" :key="m.id" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
                    <a v-if="m.type === 'image'" :href="`/storage/${m.path}`" target="_blank">
                        <img :src="`/storage/${m.path}`" :alt="m.caption || ''" class="h-36 w-full object-cover" />
                    </a>
                    <a v-else :href="`/storage/${m.path}`" target="_blank" class="flex h-36 items-center justify-center bg-slate-50 text-3xl">
                        {{ m.type === 'video' ? '▶' : '📄' }}
                    </a>
                    <div class="p-3">
                        <p class="text-xs font-medium capitalize text-slate-700">{{ m.type }}<template v-if="m.stage"> · {{ m.stage }}</template></p>
                        <p v-if="m.caption" class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ m.caption }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <span :class="m.published ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase">{{ m.published ? 'Published' : 'Draft' }}</span>
                            <div v-if="hasMedia" class="flex gap-1">
                                <button class="text-xs font-medium text-slate-600 hover:text-slate-900" @click="togglePublish(m)">{{ m.published ? 'Hide' : 'Publish' }}</button>
                                <button class="text-xs font-medium text-red-600 hover:text-red-800" @click="deleteMedia(m)">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center text-sm text-slate-400">
                No media yet. Upload before, installation or completed photos to build the project gallery.
            </div>
        </div>
</template>