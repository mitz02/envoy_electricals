<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    quotation: { type: Object, required: true },
    statuses: { type: Array, default: () => [] },
});

const page = usePage();

const hasPermission = (permission) => {
    const user = page.props.auth?.user;
    return user && (user.permissions?.includes('*') || user.permissions?.includes(permission));
};

const canEdit = hasPermission('custom-quotations.edit');
const canDelete = hasPermission('custom-quotations.delete');

const statusForm = useForm({ status: props.quotation.status });

const statusConfig = {
    draft: { class: 'bg-slate-100 text-slate-600', label: 'Draft' },
    sent: { class: 'bg-sky-100 text-sky-700', label: 'Sent' },
    viewed: { class: 'bg-blue-100 text-blue-700', label: 'Viewed' },
    accepted: { class: 'bg-emerald-100 text-emerald-800', label: 'Accepted' },
    rejected: { class: 'bg-red-100 text-red-700', label: 'Rejected' },
    expired: { class: 'bg-amber-100 text-amber-800', label: 'Expired' },
    converted: { class: 'bg-emerald-100 text-emerald-800', label: 'Converted' },
};

function getStatusConfig(status) {
    return statusConfig[status] || statusConfig.draft;
}

function saveStatus() {
    statusForm.post(`/admin/custom-quotations/${props.quotation.id}/status`, { preserveScroll: true });
}

function sendQuotation() {
    if (!props.quotation.customer_email) return;
    if (!confirm(`Send quotation ${props.quotation.ref_id} to ${props.quotation.customer_email}?`)) return;

    router.post(`/admin/custom-quotations/${props.quotation.id}/send`, {}, { preserveScroll: true });
}

function downloadPdf() {
    window.open(`/admin/custom-quotations/${props.quotation.id}/pdf`, '_blank');
}

function previewPdf() {
    window.open(`/admin/custom-quotations/${props.quotation.id}/preview`, '_blank');
}

function convertToProject() {
    if (!confirm('Mark this quotation as converted to a project?')) return;
    router.post(`/admin/custom-quotations/${props.quotation.id}/convert`, {}, { preserveScroll: true });
}

function deleteQuotation() {
    if (!confirm(`Delete quotation "${props.quotation.ref_id}"? This cannot be undone.`)) return;
    router.delete(`/admin/custom-quotations/${props.quotation.id}`, { preserveScroll: true });
}

const subtotal = computed(() => props.quotation.items.reduce((sum, item) => sum + Number(item.total), 0));
</script>

<template>
    <FlashMessages />
    <div class="mb-4 flex items-center gap-2">
        <Link href="/admin/custom-quotations" class="text-sm text-slate-500 hover:text-slate-900">← Back to quotations</Link>
    </div>

    <div class="space-y-6">
        <!-- Header Card -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ quotation.ref_id }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ quotation.title }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ quotation.job_type }}</p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <span :class="['rounded-full px-3 py-1 text-sm font-semibold', getStatusConfig(quotation.status).class]">
                        {{ getStatusConfig(quotation.status).label }}
                    </span>

                    <div v-if="canEdit" class="flex items-center gap-2">
                        <Link :href="`/admin/custom-quotations/${quotation.id}/edit`" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Edit
                        </Link>
                        <button @click="previewPdf" class="rounded-lg bg-[#40e0d0] px-3 py-1.5 text-sm font-semibold text-[#0D1527] hover:bg-[#40e0d0]/90">
                            <i class="bi bi-eye mr-1"></i> Preview
                        </button>
                        <button @click="downloadPdf" class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                            <i class="bi bi-download mr-1"></i> PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Subtotal</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ naira(subtotal) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Items</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ quotation.items.length }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Quotation Date</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(quotation.quotation_date) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Valid Until</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ quotation.valid_until ? formatDate(quotation.valid_until) : '—' }}</p>
                </div>
            </div>

            <!-- Grand Total -->
            <div class="mt-4 rounded-xl border-2 border-[#40e0d0] bg-[#40e0d0]/5 p-4">
                <div class="flex items-baseline justify-between">
                    <span class="text-xs font-bold uppercase tracking-wide text-[#40e0d0]">Grand Total</span>
                    <span class="text-3xl font-black text-[#0D1527]">{{ naira(quotation.grand_total) }}</span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Left Column: Customer & Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Info -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Customer Information</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Name</dt><dd class="font-medium text-slate-900">{{ quotation.customer_name }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="font-medium text-slate-900">{{ quotation.customer_phone }}</dd></div>
                        <div v-if="quotation.customer_email" class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-900 truncate">{{ quotation.customer_email }}</dd></div>
                        <div v-if="quotation.customer_company" class="flex justify-between"><dt class="text-slate-500">Company</dt><dd class="font-medium text-slate-900">{{ quotation.customer_company }}</dd></div>
                        <div v-if="quotation.customer_address" class="flex justify-between"><dt class="text-slate-500">Address</dt><dd class="font-medium text-slate-900">{{ quotation.customer_address }}</dd></div>
                    </dl>
                </div>

                <!-- Items Table -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Quotation Items</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px] border-collapse">
                            <thead>
                                <tr class="bg-[#0D1527] text-left text-[10.5px] font-bold uppercase tracking-widest text-slate-300">
                                    <th class="px-3 py-3 rounded-tl-xl">Item</th>
                                    <th class="px-3 py-3">Description</th>
                                    <th class="px-3 py-3 text-center">Qty</th>
                                    <th class="px-3 py-3 text-center">Unit</th>
                                    <th class="px-3 py-3 text-right">Unit Price</th>
                                    <th class="px-3 py-3 text-right rounded-tr-xl">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in quotation.items" :key="item.id" class="border-b border-slate-100">
                                    <td class="px-3 py-3 font-medium text-slate-800">{{ item.item }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ item.description || '—' }}</td>
                                    <td class="px-3 py-3 text-center text-slate-700">{{ item.quantity }}</td>
                                    <td class="px-3 py-3 text-center text-slate-500">{{ item.unit || 'pcs' }}</td>
                                    <td class="px-3 py-3 text-right font-semibold text-slate-900">{{ naira(item.unit_price) }}</td>
                                    <td class="px-3 py-3 text-right font-bold text-slate-900">{{ naira(item.total) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-[#FAF8F2]">
                                    <td colspan="5" class="px-3 py-3 text-right font-bold text-slate-900">Subtotal</td>
                                    <td class="px-3 py-3 text-right font-bold text-slate-900">{{ naira(subtotal) }}</td>
                                </tr>
                                <tr v-if="quotation.discount > 0" class="bg-[#FAF8F2]">
                                    <td colspan="5" class="px-3 py-3 text-right font-medium text-red-600">Discount</td>
                                    <td class="px-3 py-3 text-right font-medium text-red-600">- {{ naira(quotation.discount) }}</td>
                                </tr>
                                <tr v-if="quotation.tax > 0" class="bg-[#FAF8F2]">
                                    <td colspan="5" class="px-3 py-3 text-right font-medium text-slate-900">Tax / VAT</td>
                                    <td class="px-3 py-3 text-right font-medium text-slate-900">+ {{ naira(quotation.tax) }}</td>
                                </tr>
                                <tr v-if="quotation.other_charges > 0" class="bg-[#FAF8F2]">
                                    <td colspan="5" class="px-3 py-3 text-right font-medium text-slate-900">Other Charges</td>
                                    <td class="px-3 py-3 text-right font-medium text-slate-900">+ {{ naira(quotation.other_charges) }}</td>
                                </tr>
                                <tr class="bg-[#0D1527]">
                                    <td colspan="5" class="px-3 py-3 text-right font-bold text-white">Grand Total</td>
                                    <td class="px-3 py-3 text-right font-black text-[#FACC15]">{{ naira(quotation.grand_total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Description & Notes -->
                <div v-if="quotation.description || quotation.notes" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-6">
                    <div v-if="quotation.description">
                        <h3 class="mb-2 text-sm font-semibold text-slate-900">Description</h3>
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ quotation.description }}</p>
                    </div>
                    <div v-if="quotation.notes" class="pt-4 border-t border-slate-100">
                        <h3 class="mb-2 text-sm font-semibold text-slate-900">Notes & Terms</h3>
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ quotation.notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Actions -->
            <div class="space-y-4">
                <!-- Status Update -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Update Status</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                            <span class="text-sm font-medium text-slate-600">Current Status</span>
                            <span :class="['rounded-full px-2.5 py-1 text-sm font-semibold', getStatusConfig(quotation.status).class]">
                                {{ getStatusConfig(quotation.status).label }}
                            </span>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Change Status</label>
                            <select v-model="statusForm.status" :disabled="statusForm.processing" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                                <option v-for="s in statuses" :key="s" :value="s" :disabled="quotation.status === 'converted' && s !== 'converted'">
                                    {{ getStatusConfig(s).label }}
                                </option>
                            </select>
                            <p v-if="statusForm.errors.status" class="mt-1 text-xs text-red-600">{{ statusForm.errors.status }}</p>
                        </div>

                        <button :disabled="statusForm.processing || statusForm.status === quotation.status" @click="saveStatus" class="w-full rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                            {{ statusForm.processing ? 'Updating…' : 'Update Status' }}
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Actions</h2>
                    <div class="space-y-3">
                        <button v-if="quotation.customer_email && quotation.status === 'draft'" @click="sendQuotation" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#40e0d0] px-4 py-2 text-sm font-semibold text-[#0D1527] hover:bg-[#40e0d0]/90">
                            <i class="bi bi-send"></i> Send via Email
                        </button>
                        <button v-if="quotation.customer_email && quotation.status !== 'draft'" @click="sendQuotation" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="bi bi-send"></i> Resend via Email
                        </button>
                        <button @click="previewPdf" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="bi bi-eye"></i> Preview PDF
                        </button>
                        <button @click="downloadPdf" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="bi bi-download"></i> Download PDF
                        </button>
                        <button v-if="quotation.status !== 'converted'" @click="convertToProject" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                            <i class="bi bi-arrow-right-circle"></i> Convert to Project
                        </button>
                        <button v-if="canDelete" @click="deleteQuotation" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                            <i class="bi bi-trash3"></i> Delete Quotation
                        </button>
                    </div>
                </div>

                <!-- Dates -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Dates</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Created</dt><dd class="font-medium text-slate-900">{{ formatDate(quotation.created_at) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Updated</dt><dd class="font-medium text-slate-900">{{ formatDate(quotation.updated_at) }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</template>