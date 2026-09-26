<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, maskNaira, formatDate, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    projects: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    customers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const viewMode = ref('table'); // 'cards' | 'table'
const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    customer_id: props.filters.customer_id ?? '',
});

function applyFilters() {
    router.get('/admin/projects', form.data(), {
        preserveState: true,
        preserveScroll: true,
    });
}

const statusColors = {
    draft: 'bg-slate-100 text-slate-700 border-slate-200',
    quotation: 'bg-sky-50 text-sky-700 border-sky-200',
    approved: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    in_progress: 'bg-amber-50 text-amber-800 border-amber-200',
    installation: 'bg-orange-50 text-orange-700 border-orange-200',
    completed: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

const statusIcons = {
    draft: 'bi bi-file-text',
    quotation: 'bi bi-calculator',
    approved: 'bi bi-check-circle',
    in_progress: 'bi bi-hourglass-split',
    installation: 'bi bi-tools',
    completed: 'bi bi-check2-all',
    cancelled: 'bi bi-x-circle',
};

function statusLabel(s) {
    return s ? s.replace('_', ' ') : '—';
}

const summaryCards = computed(() => [
    { 
        label: 'Active Projects', 
        value: props.summary.active_projects ?? 0, 
        icon: 'bi bi-briefcase-fill', 
        accent: '#0D1527',
        accentSoft: '#0D152715',
        trend: { value: props.summary.active_vs_last_month ?? 0, label: 'vs last month' },
        statType: 'count'
    },
    { 
        label: 'Project Revenue', 
        value: naira(props.summary.projects_revenue ?? 0), 
        icon: 'bi bi-cash-stack', 
        accent: '#059669',
        accentSoft: '#05966915',
        trend: { value: props.summary.revenue_vs_last_month ?? 0, label: 'vs last month' },
        statType: 'currency'
    },
    { 
        label: 'Project Cost', 
        value: maskNaira('project_cost', props.summary.projects_cost ?? 0), 
        icon: 'bi bi-credit-card', 
        accent: '#374151',
        accentSoft: '#37415115',
        trend: { value: props.summary.cost_vs_last_month ?? 0, label: 'vs last month' },
        statType: 'currency'
    },
    { 
        label: 'Project Profit', 
        value: maskNaira('gross_profit', props.summary.projects_profit ?? 0), 
        icon: 'bi bi-graph-up-arrow', 
        accent: '#FACC15',
        accentSoft: '#FACC1533',
        textAccent: '#B8860B',
        trend: { value: props.summary.profit_vs_last_month ?? 0, label: 'vs last month' },
        statType: 'currency'
    },
    { 
        label: 'Outstanding', 
        value: naira(props.summary.outstanding ?? 0), 
        icon: 'bi bi-clock-history', 
        accent: '#ea580c',
        accentSoft: '#ea580c15',
        trend: { value: props.summary.outstanding_vs_last_month ?? 0, label: 'vs last month' },
        statType: 'currency'
    },
]);

function getProgress(p) {
    if (!p.start_date || !p.expected_completion_date) return null;
    const start = new Date(p.start_date).getTime();
    const end = new Date(p.expected_completion_date).getTime();
    const now = Date.now();
    if (now < start) return 0;
    if (now > end) return 100;
    return Math.round(((now - start) / (end - start)) * 100);
}

function profitClass(profit) {
    if (profit === null || profit === undefined) return 'text-slate-500';
    return profit >= 0 ? 'text-emerald-700' : 'text-red-700';
}

function balanceClass(balance) {
    if (balance > 0) return 'text-amber-700 font-semibold';
    if (balance < 0) return 'text-red-700 font-semibold';
    return 'text-emerald-700 font-semibold';
}

function formatTrendValue(value, type) {
    if (type === 'currency') return naira(value);
    return value + '%';
}
</script>

<template>
    <FlashMessages />
    <PageHeader 
        title="Projects" 
        subtitle="Solar and electrical installation projects with profitability tracking." 
        action-href="/admin/projects/create" 
        action-label="New Project" 
    />

    <!-- Summary Cards -->
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div v-for="(card, idx) in summaryCards" :key="idx" class="group relative overflow-hidden rounded-2xl bg-white/80 backdrop-blur-sm border border-slate-100 p-4 shadow-sm transition-all duration-500 hover:shadow-lg hover:border-slate-200 hover:-translate-y-0.5">
            <!-- Subtle accent top border -->
            <div class="absolute top-0 left-0 right-0 h-1 opacity-0 group-hover:opacity-100 transition-opacity duration-500" :style="{ background: `linear-gradient(90deg, transparent, ${card.accent}, transparent)` }"></div>
            
            <!-- Floating decorative elements -->
            <div class="absolute -top-3 -right-3 w-20 h-20 rounded-full opacity-0 group-hover:opacity-100 transition-all duration-500" :style="{ background: `radial-gradient(circle at center, ${card.accentSoft} 0%, transparent 70%)` }"></div>
            <div class="absolute bottom-3 left-3 w-12 h-12 rounded-full opacity-0 group-hover:opacity-100 transition-all duration-500 delay-100" :style="{ background: `radial-gradient(circle at center, ${card.accentSoft} 0%, transparent 70%)` }"></div>
            
            <div class="relative flex flex-col h-full">
                <!-- Icon container - elegant and minimal -->
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:scale-105" :style="{ background: card.accentSoft, boxShadow: `0 4px 16px ${card.accentSoft}` }">
                    <i :class="card.icon + ' text-lg'" :style="{ color: card.accent }" />
                </div>
                
                <div class="flex-1 flex flex-col justify-between mt-3 min-w-0">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 group-hover:text-slate-700 transition-colors">{{ card.label }}</p>
                        <p class="mt-1 text-xl font-bold text-slate-950 tracking-tight">{{ card.value }}</p>
                    </div>
                    
                    <!-- Trend indicator - elegant pill -->
                    <div v-if="card.trend && card.trend.value !== 0" class="mt-3 flex items-center gap-2 px-2.5 py-1.5 rounded-lg border transition-all duration-300" :style="{ background: card.trend.value > 0 ? '#ECFDF5' : '#FEF2F2', borderColor: card.trend.value > 0 ? '#A7F3D0' : '#FECACA' }">
                        <div class="flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center" :style="{ background: card.trend.value > 0 ? '#D1FAE5' : '#FEE2E2' }">
                            <i :class="card.trend.value > 0 ? 'bi bi-arrow-up-right text-emerald-600' : 'bi bi-arrow-down-right text-red-600'" style="font-size: 9px;" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[9px] font-medium uppercase tracking-wider text-slate-500">{{ card.trend.label }}</p>
                            <p class="text-xs font-semibold" :style="{ color: card.trend.value > 0 ? '#059669' : '#DC2626' }">
                                {{ card.trend.value > 0 ? '+' : '' }}{{ formatTrendValue(card.trend.value, card.statType) }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Creative visual: animated dots for cards without trend -->
                    <div v-else class="mt-3 flex items-center gap-1">
                        <span v-for="i in 6" :key="i" class="w-1 h-1 rounded-full transition-all duration-500 group-hover:scale-125" :style="{ background: i <= 3 ? card.accent : '#E2E8F0', opacity: i <= 3 ? 1 : 0.3, animationDelay: `${i * 80}ms` }"></span>
                        <span class="ml-1.5 text-[9px] font-medium text-slate-400 uppercase tracking-wider">Trending</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & View Toggle -->
    <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center gap-3 flex-1">
            <div class="relative flex-1 max-w-xs">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input v-model="form.search" type="search" placeholder="Search name, ref, location, customer…" class="w-full rounded-lg border-slate-300 text-sm pl-10 pr-4 py-2.5 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            </div>
            <select v-model="form.status" class="w-full sm:w-40 rounded-lg border-slate-300 text-sm py-2.5 px-4 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
            </select>
            <select v-model="form.customer_id" class="w-full sm:w-48 rounded-lg border-slate-300 text-sm py-2.5 px-4 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All customers</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button @click="viewMode = 'cards'" :class="['rounded-lg px-3 py-2 text-sm font-medium transition', viewMode === 'cards' ? 'bg-[#0D1527] text-white' : 'text-slate-600 hover:bg-slate-100']" title="Cards view">
                <i class="bi bi-grid-3x3-gap" />
            </button>
            <button @click="viewMode = 'table'" :class="['rounded-lg px-3 py-2 text-sm font-medium transition', viewMode === 'table' ? 'bg-[#0D1527] text-white' : 'text-slate-600 hover:bg-slate-100']" title="Table view">
                <i class="bi bi-table" />
            </button>
        </div>
    </div>

    <!-- Cards View -->
    <div v-if="viewMode === 'cards'" class="space-y-4">
        <div v-if="!projects.data.length" class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-12 text-center">
            <i class="bi bi-briefcase text-5xl text-slate-300 mb-3" />
            <p class="text-lg font-medium text-slate-600">No projects found</p>
            <p class="text-sm text-slate-400 mt-1">Create your first project to get started</p>
        </div>
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <article v-for="p in projects.data" :key="p.id" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-xl hover:border-slate-300 hover:-translate-y-1">
                <!-- Status Bar -->
                <div class="absolute top-0 left-0 right-0 h-1.5" :style="{ background: `linear-gradient(90deg, ${statusColors[p.status]?.split(' ')[0].replace('bg-', 'bg-') || '#64748b'} 0%, ${statusColors[p.status]?.split(' ')[0].replace('bg-', 'bg-') || '#64748b'} 100%)` }"></div>
                
                <div class="p-5">
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex-1 min-w-0">
                            <Link :href="`/admin/projects/${p.id}`" class="font-semibold text-slate-900 hover:text-slate-700 truncate block group-hover:underline">
                                {{ p.name }}
                            </Link>
                            <p class="mt-0.5 text-xs text-slate-500 font-mono">{{ p.ref_id }}</p>
                        </div>
                        <span class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border" :class="statusColors[p.status]">
                            <i :class="statusIcons[p.status] + ' text-[10px]'" />
                            {{ statusLabel(p.status) }}
                        </span>
                    </div>

                    <!-- Customer & Location -->
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="bi bi-person-badge text-slate-400 w-4" />
                            <span class="truncate">{{ p.customer?.name || 'No customer assigned' }}</span>
                        </div>
                        <div v-if="p.location" class="flex items-center gap-2 text-slate-600">
                            <i class="bi bi-geo-alt text-slate-400 w-4" />
                            <span class="truncate">{{ p.location }}</span>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="border-t border-slate-100 pt-4 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Contract</p>
                                <p class="mt-0.5 font-bold text-slate-900">{{ naira(p.contract_value) }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Received</p>
                                <p class="mt-0.5 font-bold text-slate-700">{{ naira(p.amount_received) }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl p-3" :class="p.balance > 0 ? 'bg-amber-50' : p.balance < 0 ? 'bg-red-50' : 'bg-emerald-50'">
                                <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Balance</p>
                                <p class="mt-0.5 font-bold" :class="balanceClass(p.balance)">{{ naira(p.balance) }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Profit</p>
                                <p class="mt-0.5 font-bold" :class="profitClass(p.gross_profit)">{{ maskNaira('gross_profit', p.gross_profit) }}</p>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div v-if="getProgress(p) !== null" class="pt-2">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-500">Timeline Progress</span>
                                <span class="font-medium text-slate-700">{{ getProgress(p) }}%</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500" :style="{ width: getProgress(p) + '%', background: p.status === 'completed' ? 'linear-gradient(90deg, #10b981, #059669)' : 'linear-gradient(90deg, #6366f1, #4f46e5)' }"></div>
                            </div>
                        </div>
                        <div v-else-if="p.status === 'completed'" class="pt-2">
                            <div class="h-1.5 rounded-full bg-emerald-100 overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="mt-4 flex items-center justify-between pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-3 text-xs text-slate-500">
                            <span v-if="p.assigned_user"><i class="bi bi-person-circle mr-1" />{{ p.assigned_user.name }}</span>
                            <span class="hidden sm:inline-flex"><i class="bi bi-calendar-event mr-1" />{{ p.start_date ? formatDate(p.start_date) : '—' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Link :href="`/admin/projects/${p.id}/edit`" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition" title="Edit">
                                <i class="bi bi-pencil text-base" />
                            </Link>
                            <Link :href="`/admin/projects/${p.id}`" class="rounded-lg p-2 bg-[#0D1527] text-white hover:bg-[#0D1527]/90 transition" title="View Details">
                                <i class="bi bi-eye text-base" />
                            </Link>
                        </div>
                    </div>
                </div>
            </article>
        </div>
        
        <!-- Pagination -->
        <div class="border-t border-slate-100 pt-4">
            <Pagination :meta="projects" />
        </div>
    </div>

    <!-- Table View -->
    <div v-else class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Project</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Contract</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Received</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Balance</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Profit</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Timeline</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="p in projects.data" :key="p.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-4">
                            <Link :href="`/admin/projects/${p.id}`" class="font-medium text-slate-900 hover:text-slate-700">{{ p.name }}</Link>
                            <p class="text-xs text-slate-400 font-mono">{{ p.ref_id }}</p>
                        </td>
                        <td class="px-4 py-4 text-slate-600">
                            <span v-if="p.customer">{{ p.customer.name }}</span>
                            <span v-else class="text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border" :class="statusColors[p.status]">
                                <i :class="statusIcons[p.status] + ' text-[10px]'" />
                                {{ statusLabel(p.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-right font-semibold text-slate-900">{{ naira(p.contract_value) }}</td>
                        <td class="px-4 py-4 text-right text-slate-700">{{ naira(p.amount_received) }}</td>
                        <td class="px-4 py-4 text-right font-medium" :class="balanceClass(p.balance)">{{ naira(p.balance) }}</td>
                        <td class="px-4 py-4 text-right font-semibold" :class="profitClass(p.gross_profit)">{{ maskNaira('gross_profit', p.gross_profit) }}</td>
                        <td class="px-4 py-4 text-right text-xs text-slate-500">
                            {{ p.start_date ? formatDate(p.start_date) : '—' }}
                            <span v-if="p.expected_completion_date" class="text-slate-400 mx-1">→</span>
                            <span v-if="p.expected_completion_date">{{ formatDate(p.expected_completion_date) }}</span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <Link :href="`/admin/projects/${p.id}`" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition" title="View">
                                    <i class="bi bi-eye text-base" />
                                </Link>
                                <Link :href="`/admin/projects/${p.id}/edit`" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition" title="Edit">
                                    <i class="bi bi-pencil text-base" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!projects.data.length">
                        <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-400">No projects found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="projects" />
        </div>
    </div>
</template>