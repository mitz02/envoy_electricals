<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate, formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    tab: { type: String, default: 'calculations' },
    calculations: { type: Object, required: true },
    quotations: { type: Object, required: true },
    calc_statuses: { type: Array, default: () => [] },
    quotation_statuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('solar.leads');

const activeTab = ref(props.tab === 'quotations' ? 'quotations' : 'calculations');

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

const statusForm = useForm({});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.status) p.set('status', form.status);
    p.set('tab', activeTab.value);
    window.location.search = p.toString();
}

function changeTab(tab) {
    activeTab.value = tab;
    window.location.search = new URLSearchParams({ tab }).toString();
}

function updateStatus(record, status) {
    statusForm
        .transform(() => ({ type: activeTab.value === 'calculations' ? 'calculation' : 'quotation', id: record.id, status }))
        .post('/admin/solar-leads/status', { preserveScroll: true });
}

function remove(record) {
    if (!confirm(`Remove ${record.ref_id}?`)) {
        return;
    }
    const base = activeTab.value === 'calculations' ? '/admin/solar-leads' : '/admin/solar-leads/quotations';
    statusForm.delete(`${base}/${record.id}`, { preserveScroll: true });
}

const statusBadge = {
    new: 'bg-slate-100 text-slate-600',
    contacted: 'bg-sky-100 text-sky-700',
    quoted: 'bg-blue-100 text-blue-700',
    approved: 'bg-emerald-100 text-emerald-800',
    installation: 'bg-amber-100 text-amber-800',
    completed: 'bg-emerald-100 text-emerald-800',
    declined: 'bg-red-100 text-red-700',
    converted: 'bg-emerald-100 text-emerald-800',
    lost: 'bg-slate-100 text-slate-500',
};

const availableStatuses = () => (activeTab.value === 'calculations' ? props.calc_statuses : props.quotation_statuses);

// Pipeline stats
const pipelineStats = computed(() => {
    const calc = props.calculations.data;
    return {
        new: calc.filter(c => c.lead_status === 'new').length,
        contacted: calc.filter(c => c.lead_status === 'contacted').length,
        quoted: calc.filter(c => c.lead_status === 'quoted').length,
        approved: calc.filter(c => c.lead_status === 'approved').length,
        installation: calc.filter(c => c.lead_status === 'installation').length,
        completed: calc.filter(c => c.lead_status === 'completed').length,
        lost: calc.filter(c => c.lead_status === 'lost').length,
    };
});
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Solar Leads"
        subtitle="Calculator submissions and quotations from the website."
    />

    <!-- Flow Explanation -->
    <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-100">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                <i class="bi bi-info-circle text-blue-600 text-sm" />
            </div>
            <div class="text-sm text-blue-800 space-y-1">
                <p class="font-semibold">How Solar Leads Work:</p>
                <p><strong>Calculator Leads:</strong> Customers submit their power needs via the website calculator. You see their estimated load, recommended system, and price.</p>
                <p><strong>Pipeline:</strong> New → Contacted → Quoted → Approved → Installation → Completed</p>
                <p><strong>Creating a Quotation:</strong> Open a lead, click "Create Quotation", select a Solar Package, and send it. The lead auto-moves to "Quoted".</p>
                <p><strong>Quotations Tab:</strong> Formal quotes sent to customers. Track their status: new → contacted → quoted → approved/declined → converted.</p>
            </div>
        </div>
    </div>

    <!-- Pipeline Visualization (Calculator Leads only) -->
    <div v-if="activeTab === 'calculations'" class="mb-4 overflow-x-auto">
        <div class="flex items-center gap-2 min-w-max px-2 pb-2">
            <div
                v-for="(status, idx) in [
                    { key: 'new', label: 'New', color: 'bg-slate-400' },
                    { key: 'contacted', label: 'Contacted', color: 'bg-sky-400' },
                    { key: 'quoted', label: 'Quoted', color: 'bg-blue-400' },
                    { key: 'approved', label: 'Approved', color: 'bg-emerald-400' },
                    { key: 'installation', label: 'Installation', color: 'bg-amber-400' },
                    { key: 'completed', label: 'Completed', color: 'bg-emerald-600' },
                    { key: 'lost', label: 'Lost', color: 'bg-slate-500' },
                ]"
                :key="status.key"
            >
                <div class="flex flex-col items-center">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl border-2 flex items-center justify-center text-white font-bold text-sm" :class="status.color">
                        {{ pipelineStats[status.key] }}
                    </div>
                    <p class="mt-1 text-xs font-medium text-slate-600 text-center">{{ status.label }}</p>
                </div>
                <div v-if="idx < 6" class="hidden sm:flex items-center mx-1 text-slate-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-4 flex items-center gap-1 rounded-xl border border-slate-200/80 bg-white p-1 shadow-xs w-fit">
        <button
            @click="changeTab('calculations')"
            :class="activeTab === 'calculations' ? 'bg-[#0D1527] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
            class="rounded-lg px-4 py-1.5 text-sm font-semibold transition-colors"
        >
            Calculator Leads ({{ calculations.total }})
        </button>
        <button
            @click="changeTab('quotations')"
            :class="activeTab === 'quotations' ? 'bg-[#0D1527] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
            class="rounded-lg px-4 py-1.5 text-sm font-semibold transition-colors"
        >
            Quotations ({{ quotations.total }})
        </button>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input v-model="form.search" type="search" placeholder="Search customer name, phone, email, location, ref…" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All statuses</option>
            <option v-for="s in availableStatuses()" :key="s" :value="s">{{ s }}</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <!-- Calculator Leads Table -->
    <div v-if="activeTab === 'calculations'">
        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Lead</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">System</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Est. Price</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Received</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in calculations.data" :key="c.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/solar-leads/${c.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ c.customer_name || 'Anonymous' }}</Link>
                                <p class="text-xs text-slate-400">{{ c.ref_id }}<span v-if="c.customer_phone"> · {{ c.customer_phone }}</span></p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ c.recommended_inverter || '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ c.estimated_price ? naira(c.estimated_price) : '—' }}</td>
                            <td class="px-4 py-3">
                                <select
                                    :value="c.lead_status"
                                    class="rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20"
                                    @change="updateStatus(c, $event.target.value)"
                                >
                                    <option v-for="s in calc_statuses" :key="s" :value="s" :selected="c.lead_status === s">{{ s }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ formatDateTime(c.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <Link :href="`/admin/solar-leads/${c.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                    <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="remove(c)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!calculations.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No calculator leads found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="calculations" />
            </div>
        </div>
    </div>

    <!-- Quotations Table -->
    <div v-else>
        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Quotation</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">System</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Est. Price</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Created</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="q in quotations.data" :key="q.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/solar-leads/quotations/${q.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ q.customer_name || 'Anonymous' }}</Link>
                                <p class="text-xs text-slate-400">{{ q.ref_id }}<span v-if="q.customer_phone"> · {{ q.customer_phone }}</span></p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ q.recommended_system || '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ q.estimated_price ? naira(q.estimated_price) : '—' }}</td>
                            <td class="px-4 py-3">
                                <select
                                    :value="q.status"
                                    class="rounded-lg border-slate-300 text-xs focus:border-amber-400 focus:ring-amber-400/20"
                                    @change="updateStatus(q, $event.target.value)"
                                >
                                    <option v-for="s in quotation_statuses" :key="s" :value="s" :selected="q.status === s">{{ s }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ formatDate(q.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <Link :href="`/admin/solar-leads/quotations/${q.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                    <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="remove(q)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!quotations.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No quotations found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="quotations" />
            </div>
        </div>
    </div>
</template>