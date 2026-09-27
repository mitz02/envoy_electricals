<script setup>
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    quotations: { type: Object, required: true },
    filters: { type: Object, required: true },
    jobTypes: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const page = usePage();

const hasPermission = (permission) => {
    const user = page.props.auth?.user;
    return user && (user.permissions?.includes('*') || user.permissions?.includes(permission));
};

const canCreate = hasPermission('custom-quotations.create');
const canEdit = hasPermission('custom-quotations.edit');
const canDelete = hasPermission('custom-quotations.delete');

function formatCurrency(value) {
    return '₦' + Number(value || 0).toLocaleString('en-NG');
}

function formatDate(date) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-NG', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function getStatusBadge(status) {
    const badges = {
        draft: 'bg-slate-100 text-slate-600',
        sent: 'bg-sky-100 text-sky-700',
        viewed: 'bg-blue-100 text-blue-700',
        accepted: 'bg-emerald-100 text-emerald-800',
        rejected: 'bg-red-100 text-red-700',
        expired: 'bg-amber-100 text-amber-800',
        converted: 'bg-emerald-100 text-emerald-800',
    };
    const labels = {
        draft: 'Draft',
        sent: 'Sent',
        viewed: 'Viewed',
        accepted: 'Accepted',
        rejected: 'Rejected',
        expired: 'Expired',
        converted: 'Converted',
    };
    return {
        class: badges[status] || 'bg-slate-100 text-slate-600',
        label: labels[status] || status,
    };
}

function deleteQuotation(quotation) {
    if (window.confirm(`Delete quotation "${quotation.ref_id}"? This cannot be undone.`)) {
        router.delete(`/admin/custom-quotations/${quotation.id}`, { preserveScroll: true });
    }
}

function updateStatus(quotation, newStatus) {
    router.post(`/admin/custom-quotations/${quotation.id}/status`, {
        status: newStatus,
    }, { preserveScroll: true });
}

function sendQuotation(quotation) {
    if (window.confirm(`Send quotation ${quotation.ref_id} to ${quotation.customer_email}?`)) {
        router.post(`/admin/custom-quotations/${quotation.id}/send`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="Custom Quotations" subtitle="Manage general quotations for CCTV, electrical, fencing, and other services." />

    <div class="space-y-6">
        <!-- Filters -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <form @submit.prevent class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Search</label>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Ref ID, customer, job type..."
                        class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                </div>

                <div class="min-w-[180px]">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Status</label>
                    <select
                        v-model="filters.status"
                        class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    >
                        <option value="">All Statuses</option>
                        <option v-for="s in statuses" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
                    </select>
                </div>

                <div class="min-w-[180px]">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Job Type</label>
                    <select
                        v-model="filters.job_type"
                        class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    >
                        <option value="">All Types</option>
                        <option v-for="t in jobTypes" :key="t" :value="t">{{ t }}</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90"
                >
                    <i class="bi bi-funnel text-sm"></i> Filter
                </button>

                <Link v-if="filters.search || filters.status || filters.job_type"
                    :href="`/admin/custom-quotations`"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    <i class="bi bi-x-lg text-sm"></i> Clear
                </Link>

                <Link v-if="canCreate"
                    href="/admin/custom-quotations/create"
                    class="ml-auto inline-flex items-center gap-2 rounded-lg bg-[#40e0d0] px-5 py-2.5 text-sm font-semibold text-[#0D1527] hover:bg-[#40e0d0]/90"
                >
                    <i class="bi bi-plus-lg text-sm"></i> New Quotation
                </Link>
            </form>
        </div>

        <!-- Quotations Table -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            <div v-if="!quotations.data.length" class="py-16 text-center text-sm text-slate-400">
                <i class="bi bi-file-earmark-text text-4xl text-slate-200 mb-3"></i>
                <p>No quotations found. <Link href="/admin/custom-quotations/create" class="text-[#40e0d0] hover:underline">Create your first quotation</Link></p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-[#0D1527] text-left text-[10.5px] font-bold uppercase tracking-widest text-slate-300">
                            <th class="px-4 py-3 rounded-tl-xl whitespace-nowrap">Quotation</th>
                            <th class="px-4 py-3 whitespace-nowrap">Customer</th>
                            <th class="px-4 py-3 whitespace-nowrap">Job Type</th>
                            <th class="px-4 py-3 whitespace-nowrap">Items</th>
                            <th class="px-4 py-3 whitespace-nowrap">Amount</th>
                            <th class="px-4 py-3 whitespace-nowrap">Date</th>
                            <th class="px-4 py-3 whitespace-nowrap">Status</th>
                            <th class="px-4 py-3 rounded-tr-xl whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="q in quotations.data" :key="q.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <div>
                                    <Link :href="`/admin/custom-quotations/${q.id}`" class="font-semibold text-slate-800 hover:text-[#40e0d0]">{{ q.ref_id }}</Link>
                                    <p class="text-[11px] text-slate-400 truncate max-w-xs">{{ q.title }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-slate-800">{{ q.customer_name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ q.customer_phone }}</p>
                                    <p v-if="q.customer_company" class="text-[11px] text-slate-500">{{ q.customer_company }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#40e0d0]/10 px-2.5 py-1 text-[11px] font-semibold text-[#40e0d0]">{{ q.job_type }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ q.items_count }} item{{ q.items_count !== 1 ? 's' : '' }}</td>
                            <td class="px-4 py-3 font-bold text-slate-900">{{ formatCurrency(q.grand_total) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ formatDate(q.quotation_date) }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2.5 py-1 text-[11px] font-semibold', getStatusBadge(q.status).class]">
                                    {{ getStatusBadge(q.status).label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Link :href="`/admin/custom-quotations/${q.id}`" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="View">
                                        <i class="bi bi-eye text-sm"></i>
                                    </Link>
                                    <Link v-if="canEdit" :href="`/admin/custom-quotations/${q.id}/edit`" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Edit">
                                        <i class="bi bi-pencil text-sm"></i>
                                    </Link>
                                    <Link v-if="canEdit" :href="`/admin/custom-quotations/${q.id}/preview`" target="_blank" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Preview PDF">
                                        <i class="bi bi-file-earmark-pdf text-sm"></i>
                                    </Link>
                                    <div class="relative" v-if="canEdit">
                                        <button @click="sendQuotation(q)" :disabled="!q.customer_email || q.status !== 'draft'" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-sky-100 hover:text-sky-600 disabled:opacity-30" title="Send via Email">
                                            <i class="bi bi-send text-sm"></i>
                                        </button>
                                    </div>
                                    <div class="relative" v-if="canEdit">
                                        <select @change="updateStatus(q, $event.target.value)" :value="q.status" :disabled="q.status === 'converted'" class="w-32 rounded-lg border-slate-300 text-[11px] py-1.5 pl-2 pr-6 appearance-none bg-white focus:border-amber-400 focus:ring-amber-400/20" title="Change Status">
                                            <option v-for="s in statuses" :key="s" :value="s" :disabled="q.status === 'converted' && s !== 'converted'">{{ getStatusBadge(s).label }}</option>
                                        </select>
                                    </div>
                                    <button v-if="canDelete" @click="deleteQuotation(q)" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" title="Delete">
                                        <i class="bi bi-trash3 text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="quotations.last_page > 1" class="px-4 py-3 border-t border-slate-100">
                <nav class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Showing {{ quotations.from }} to {{ quotations.to }} of {{ quotations.total }} results</span>
                    <div class="flex gap-2">
                        <button v-if="quotations.current_page > 1" @click="$refs.pagination.previousPage()" class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-sm hover:bg-slate-50">←</button>
                        <button v-if="quotations.current_page < quotations.last_page" @click="$refs.pagination.nextPage()" class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 text-sm hover:bg-slate-50">→</button>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</template>