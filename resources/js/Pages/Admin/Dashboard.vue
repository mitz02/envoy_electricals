<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { Link, router } from '@inertiajs/vue3';
import { naira, formatDate, maskNaira, maskPercent, MASK_GLYPH } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const { has } = useCan();
const canSeeFinancials = computed(() => has('reports.view'));

const props = defineProps({
    period: { type: String, default: 'month' },
    stats: { type: Object, default: () => ({}) },
    trend_curve: { type: Array, default: () => [] },
    weekday_data: { type: Array, default: () => [] },
    customer_breakdown: { type: Array, default: () => [] },
    repeat_customer_rate: { type: Number, default: 0 },
    top_products: { type: Array, default: () => [] },
    recent_sales: { type: Array, default: () => [] },
    recent_purchases: { type: Array, default: () => [] },
    recent_expenses: { type: Array, default: () => [] },
    recent_projects: { type: Array, default: () => [] },
});

// Period Selector State
const periods = [
    { key: 'today', label: 'Today' },
    { key: 'week', label: 'Last 7 days' },
    { key: 'month', label: 'This Month' },
    { key: '30days', label: 'Last 30 days' },
    { key: 'year', label: 'This Year' },
];
const selectedPeriodKey = ref(props.period);
const selectedPeriod = computed(() => periods.find((p) => p.key === selectedPeriodKey.value)?.label ?? 'This Month');
const isPeriodDropdownOpen = ref(false);

function changePeriod(key) {
    isPeriodDropdownOpen.value = false;
    if (key === selectedPeriodKey.value) return;
    router.get('/admin', { period: key }, { preserveScroll: true });
}

// Interactive Spline Chart Hover State
const hoveredPointIndex = ref(18);

const trendPoints = computed(() => (Array.isArray(props.trend_curve) ? props.trend_curve : []));

const maxCurveValue = computed(() => {
    const all = trendPoints.value.flatMap((p) => [p.current, p.previous]);
    return Math.max(...all, 1);
});

// SVG Chart Path Helpers
const chartWidth = 600;
const chartHeight = 220;
const paddingX = 24;
const paddingY = 24;

const getCoords = (index, value) => {
    const total = trendPoints.value.length;
    const x = paddingX + (index / (total - 1)) * (chartWidth - paddingX * 2);
    const y = chartHeight - paddingY - (value / maxCurveValue.value) * (chartHeight - paddingY * 2);
    return { x, y };
};

// Generates smooth cubic bezier path string
const generateSplinePath = (key) => {
    const points = trendPoints.value.map((p, idx) => getCoords(idx, p[key]));
    if (!points.length) return '';
    let d = `M ${points[0].x} ${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i === 0 ? 0 : i - 1];
        const p1 = points[i];
        const p2 = points[i + 1];
        const p3 = points[i + 2] || p2;

        const cp1x = p1.x + (p2.x - p0.x) / 6;
        const cp1y = p1.y + (p2.y - p0.y) / 6;
        const cp2x = p2.x - (p3.x - p1.x) / 6;
        const cp2y = p2.y - (p3.y - p1.y) / 6;

        d += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2.y}`;
    }
    return d;
};

const currentSplinePath = computed(() => generateSplinePath('current'));
const previousSplinePath = computed(() => generateSplinePath('previous'));

const areaSplinePath = computed(() => {
    const spline = currentSplinePath.value;
    if (!spline) return '';
    const lastX = getCoords(trendPoints.value.length - 1, 0).x;
    const firstX = getCoords(0, 0).x;
    const bottomY = chartHeight - paddingY;
    return `${spline} L ${lastX} ${bottomY} L ${firstX} ${bottomY} Z`;
});

const activePoint = computed(() => {
    if (!trendPoints.value.length) return null;
    const idx = Math.min(Math.max(hoveredPointIndex.value, 0), trendPoints.value.length - 1);
    const p = trendPoints.value[idx];
    const coords = getCoords(idx, p.current);
    return {
        ...p,
        ...coords,
        index: idx,
    };
});

function handleChartMouseMove(e) {
    if (!trendPoints.value.length) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const ratio = Math.max(0, Math.min(1, (x - paddingX) / (chartWidth - paddingX * 2)));
    hoveredPointIndex.value = Math.round(ratio * (trendPoints.value.length - 1));
}

const xAxisLabels = computed(() => {
    const pts = trendPoints.value;
    const total = pts.length;
    if (!total) return [];
    const indices = [0, Math.floor(total * 0.25), Math.floor(total * 0.5), Math.floor(total * 0.75), total - 1];
    return indices.map((i) => pts[i]?.short_label || '');
});

// Weekday Data
const weekdays = computed(() => (Array.isArray(props.weekday_data) ? props.weekday_data : []));

// Active Right Tab in Bottom Grid
const bottomTab = ref('projects');
const showExportModal = ref(false);

function getStatusBadge(status) {
    const map = {
        in_progress: { label: 'In Progress', bg: 'bg-amber-50 text-amber-700 border-amber-200' },
        installation: { label: 'Installation', bg: 'bg-amber-50 text-amber-700 border-amber-200' },
        approved: { label: 'Approved', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        quotation: { label: 'Quotation', bg: 'bg-purple-50 text-purple-700 border-purple-200' },
        completed: { label: 'Completed', bg: 'bg-slate-100 text-slate-700 border-slate-200' },
    };
    return map[status] || { label: status, bg: 'bg-slate-50 text-slate-600 border-slate-200' };
}
</script>

<template>
    <div class="space-y-6">
        <FlashMessages />

        <!-- Top Header & Action Controls - Creative Design -->
        <div class="relative z-20 rounded-3xl bg-gradient-to-r from-[#0D1527] via-[#12203C] to-[#1A365D] p-6 sm:p-7 shadow-xl">
            <!-- Animated Background Layer -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl" aria-hidden="true">
                <!-- Animated gradient orbs -->
                <div class="absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[#40e0d0]/15 blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-24 right-32 h-56 w-56 rounded-full bg-[#FACC15]/10 blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
                <div class="absolute -top-32 left-16 h-48 w-48 rounded-full bg-[#40e0d0]/10 blur-3xl animate-pulse-slow" style="animation-delay: 2s;"></div>
                
                <!-- Subtle grid pattern overlay -->
                <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 stroke=%22%23ffffff%22 stroke-width=%220.3%22 stroke-opacity=%220.1%22%3E%3Cpath d=%22M0 0h40v40H0z%22/%3E%3C/g%3E%3C/svg%3E');"></div>
                
                <!-- Animated diagonal lines -->
                <div class="absolute inset-0 overflow-hidden">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <pattern id="diagonal-lines" patternUnits="userSpaceOnUse" width="20" height="20">
                                <line x1="0" y1="20" x2="20" y2="0" stroke="%2340e0d0" stroke-width="0.3" stroke-opacity="0.05"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(%23diagonal-lines)" />
                    </svg>
                </div>
            </div>

            <!-- Floating particles animation -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
                <div class="absolute top-1/4 left-1/4 w-3 h-3 rounded-full bg-yellow-400/20 animate-float"></div>
                <div class="absolute top-1/3 right-1/3 w-2 h-2 rounded-full bg-[#40e0d0]/20 animate-float" style="animation-delay: 1s;"></div>
                <div class="absolute bottom-1/4 left-1/3 w-1.5 h-1.5 rounded-full bg-yellow-400/15 animate-float" style="animation-delay: 2s;"></div>
                <div class="absolute bottom-1/3 right-1/4 w-2.5 h-2.5 rounded-full bg-[#40e0d0]/15 animate-float" style="animation-delay: 1.5s;"></div>
            </div>

            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl relative">
                    <!-- Animated accent line -->
                    <div class="absolute -left-6 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-gradient-to-br from-yellow-400 to-[#40e0d0] animate-pulse hidden lg:block"></div>
                    
                    <span class="inline-flex items-center gap-2 text-yellow-400 text-[10px] font-bold uppercase tracking-[0.3em] mb-1.5">
                        <span class="relative w-6 h-px bg-gradient-to-r from-yellow-400 to-[#40e0d0]">
                            <span class="absolute left-0 top-0 w-full h-full bg-gradient-to-r from-yellow-400 to-[#40e0d0] animate-shimmer"></span>
                        </span>
                        Admin Overview
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white relative">
                        <span class="relative z-10">Business Dashboard</span>
                        <span class="absolute -bottom-1 left-0 w-full h-2 bg-gradient-to-r from-yellow-400/30 to-[#40e0d0]/30 blur-sm -z-10 animate-shimmer"></span>
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-300/90 sm:text-sm">Real-time overview of sales, stock valuation, and solar installation projects.</p>
                    
                    <!-- Live indicator -->
                    <div class="mt-4 flex items-center gap-3">
                        <div class="flex items-center gap-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-3 py-1">
                            <span class="relative w-2 h-2 rounded-full bg-emerald-400 animate-pulse">
                                <span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-75"></span>
                            </span>
                            <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Live</span>
                        </div>
                        <div class="hidden sm:flex items-center gap-1.5 rounded-full bg-white/5 border border-white/10 px-3 py-1">
                            <svg class="w-3 h-3 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span class="text-[10px] font-medium text-slate-300 uppercase tracking-wider">Solar</span>
                        </div>
                        <div class="hidden sm:flex items-center gap-1.5 rounded-full bg-white/5 border border-white/10 px-3 py-1">
                            <svg class="w-3 h-3 text-[#FACC15]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2"/></svg>
                            <span class="text-[10px] font-medium text-slate-300 uppercase tracking-wider">Inventory</span>
                        </div>
                    </div>
                </div>

                <!-- Header Controls & Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Period Dropdown -->
                    <div class="relative group">
                        <button
                            @click="isPeriodDropdownOpen = !isPeriodDropdownOpen"
                            class="flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3.5 py-2 text-xs font-medium text-white backdrop-blur transition-all duration-300 hover:bg-white/20 hover:border-white/30 hover:shadow-lg hover:shadow-yellow-400/10"
                        >
                            <div class="relative">
                                <svg class="h-4 w-4 shrink-0 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="relative z-10">{{ selectedPeriod }}</span>
                            </div>
                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 transition-transform duration-300 group-hover:rotate-180" :class="isPeriodDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            v-if="isPeriodDropdownOpen"
                            class="absolute right-0 top-full z-50 mt-1.5 w-36 rounded-xl border border-white/15 bg-[#0D1527] py-1 shadow-2xl animate-slide-down"
                        >
                            <button
                                v-for="p in periods"
                                :key="p.key"
                                @click="changePeriod(p.key)"
                                class="flex w-full items-center px-3 py-1.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors"
                                :class="selectedPeriodKey === p.key ? 'font-semibold text-yellow-400' : ''"
                            >
                                <span class="flex items-center gap-2">
                                    <span v-if="selectedPeriodKey === p.key" class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span>
                                    {{ p.label }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Record Expense -->
                    <Link
                        v-if="has('expenses.create')"
                        href="/admin/expenses/create"
                        class="hidden sm:flex items-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-xs font-medium text-white backdrop-blur transition-all duration-300 hover:bg-white/20 hover:border-white/30 hover:shadow-lg hover:shadow-yellow-400/10"
                    >
                        <svg class="h-4 w-4 shrink-0 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Record Expense</span>
                    </Link>

                    <!-- New Solar Project -->
                    <Link
                        v-if="has('projects.create')"
                        href="/admin/projects/create"
                        class="hidden sm:flex items-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-xs font-medium text-white backdrop-blur transition-all duration-300 hover:bg-white/20 hover:border-[#40e0d0]/30 hover:shadow-lg hover:shadow-[#40e0d0]/10"
                    >
                        <svg class="h-4 w-4 shrink-0 text-[#40e0d0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>New Project</span>
                    </Link>

                    <!-- Primary Action: New Sale (POS) -->
                    <Link
                        v-if="has('sales.create')"
                        href="/admin/sales/create"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-yellow-400 to-amber-400 px-4 py-2 text-xs font-bold text-[#0D1527] shadow-lg shadow-[#FACC15]/30 transition-all duration-300 hover:from-amber-400 hover:to-yellow-400 hover:scale-[1.02] hover:shadow-xl hover:shadow-[#FACC15]/40 active:scale-[0.98]"
                    >
                        <div class="relative">
                            <svg class="h-4 w-4 shrink-0 text-[#0D1527]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="absolute inset-0 bg-gradient-to-r from-yellow-400 to-amber-400 opacity-20 blur-sm rounded-xl"></span>
                        </div>
                        <span class="relative z-10">New Sale / POS</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- 4 Primary KPI Metric Cards (Envoy Electricals Operational & Financial Metrics) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- 1. Monthly Sales & Revenue -->
            <div class="group relative overflow-hidden rounded-3xl border border-[#0D1527]/5 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)] transition-all hover:border-[#40e0d0]/40 hover:shadow-lg">
                <span class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#40e0d0] to-[#FACC15]"></span>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">{{ stats.period_label || 'This Month' }} Sales Revenue</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#40e0d0]/10 text-[#40e0d0]">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-[#0D1527]">{{ canSeeFinancials ? naira(stats.month_sales) : MASK_GLYPH }}</span>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-[#40e0d0]/10 px-2 py-0.5 text-[11px] font-semibold text-teal-700 border border-[#40e0d0]/30">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                        +{{ stats.sales_growth ?? 0 }}%
                    </span>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Today: {{ canSeeFinancials ? naira(stats.today_sales) : MASK_GLYPH }}</span>
                    <span class="font-medium text-slate-600">{{ stats.today_orders_count || 0 }} sales</span>
                </div>
            </div>

            <!-- 2. Profit & Margins -->
            <div class="group relative overflow-hidden rounded-3xl border border-[#0D1527]/5 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)] transition-all hover:border-[#0D1527]/20 hover:shadow-lg">
                <span class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#FACC15] to-[#40e0d0]"></span>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">{{ stats.period_label || 'This Month' }} Net Profit</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#0D1527]/5 text-[#0D1527]">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-[#0D1527]">{{ canSeeFinancials ? maskNaira('net_profit', stats.month_net_profit ?? stats.month_gross_profit ?? 0) : MASK_GLYPH }}</span>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-yellow-400/15 px-2 py-0.5 text-[11px] font-semibold text-yellow-600 border border-yellow-400/30">
                        {{ canSeeFinancials ? maskPercent('profit_margin', stats.profit_margin ?? 0) : MASK_GLYPH }} Margin
                    </span>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Gross: {{ canSeeFinancials ? maskNaira('gross_profit', stats.month_gross_profit) : MASK_GLYPH }}</span>
                    <span>Exp: {{ canSeeFinancials ? naira(stats.month_expenses) : MASK_GLYPH }}</span>
                </div>
            </div>

            <!-- 3. Stock & Inventory Valuation -->
            <div class="group relative overflow-hidden rounded-3xl border border-[#0D1527]/5 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)] transition-all hover:border-yellow-400/60 hover:shadow-lg">
                <span class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#40e0d0] to-[#FACC15]"></span>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Stock Valuation</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-yellow-400/15 text-yellow-600">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-[#0D1527]">{{ maskNaira('inventory_value', stats.inventory_value) }}</span>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400">{{ stats.total_products || 0 }} Products</span>
                    <span v-if="stats.low_stock > 0 || stats.out_of_stock > 0" class="inline-flex items-center gap-1 font-semibold text-amber-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse" />
                        {{ stats.low_stock }} Low, {{ stats.out_of_stock }} Out
                    </span>
                    <span v-else class="text-teal-700 font-medium">Stock Healthy</span>
                </div>
            </div>

            <!-- 4. Solar & Electrical Projects -->
            <div class="group relative overflow-hidden rounded-3xl border border-[#0D1527]/5 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)] transition-all hover:border-[#40e0d0]/40 hover:shadow-lg">
                <span class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#FACC15] to-[#40e0d0]"></span>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Solar Projects</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-[#40e0d0]/15 to-[#FACC15]/20 text-[#0D1527]">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-[#0D1527]">{{ stats.active_projects || 0 }} Active</span>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-[#0D1527]/5 px-2 py-0.5 text-[11px] font-semibold text-[#0D1527] border border-[#0D1527]/10">
                        {{ stats.completed_projects || 0 }} Done
                    </span>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Project Profit: {{ canSeeFinancials ? maskNaira('project_profit', stats.project_profit) : MASK_GLYPH }}</span>
                    <Link href="/admin/projects" class="font-medium text-[#40e0d0] hover:underline">View All →</Link>
                </div>
            </div>
        </div>

        <!-- Middle Section: 30-Day Sales Trend (8 cols) & Right Stacked Operational Widgets (4 cols) -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left Chart Card (8 cols) -->
            <div class="lg:col-span-8 rounded-3xl border border-[#0D1527]/5 bg-white p-6 shadow-[0_12px_34px_rgba(13,21,39,0.06)] flex flex-col justify-between">
                <div>
                    <!-- Top Area: Metric Headline & Comparison Badge -->
                    <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500">Sales & Revenue Trend</p>
                            <div class="mt-1 flex items-baseline gap-3">
                                <span class="text-3xl font-extrabold tracking-tight text-[#0D1527]">{{ canSeeFinancials ? naira(stats.month_sales) : MASK_GLYPH }}</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#40e0d0]/10 px-2.5 py-0.5 text-xs font-semibold text-teal-700 border border-[#40e0d0]/30">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                    +{{ stats.sales_growth ?? 0 }}%
                                    <span class="font-normal text-teal-700/80">vs. last period</span>
                                </span>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="hidden sm:flex items-center gap-3 text-[11px] text-slate-400 font-medium">
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#0D1527]" /> Current</span>
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#FACC15]" /> Previous</span>
                        </div>
                    </div>

                    <!-- Interactive Area Spline Chart -->
                    <div class="relative mt-6 h-56 w-full select-none" @mousemove="handleChartMouseMove" @mouseleave="hoveredPointIndex = 18">
                        <div v-if="!trendPoints.length" class="absolute inset-0 flex items-center justify-center">
                            <p class="rounded-xl bg-white/95 px-4 py-2 text-xs text-slate-500 shadow-sm border border-slate-100">No sales data available for this range yet.</p>
                        </div>
                        <template v-else>
                            <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="h-full w-full overflow-visible" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="envoyGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#40e0d0" stop-opacity="0.40" />
                                    <stop offset="60%" stop-color="#40e0d0" stop-opacity="0.10" />
                                    <stop offset="100%" stop-color="#40e0d0" stop-opacity="0.00" />
                                </linearGradient>
                            </defs>

                            <!-- Horizontal Gridlines & Y-Axis Markers -->
                            <g class="text-slate-200">
                                <line :x1="paddingX" :y1="getCoords(0, maxCurveValue).y" :x2="chartWidth - paddingX" :y2="getCoords(0, maxCurveValue).y" stroke="currentColor" stroke-dasharray="3 3" stroke-width="1" />
                                <line :x1="paddingX" :y1="getCoords(0, maxCurveValue * 0.66).y" :x2="chartWidth - paddingX" :y2="getCoords(0, maxCurveValue * 0.66).y" stroke="currentColor" stroke-dasharray="3 3" stroke-width="1" />
                                <line :x1="paddingX" :y1="getCoords(0, maxCurveValue * 0.33).y" :x2="chartWidth - paddingX" :y2="getCoords(0, maxCurveValue * 0.33).y" stroke="currentColor" stroke-dasharray="3 3" stroke-width="1" />
                                <line :x1="paddingX" :y1="chartHeight - paddingY" :x2="chartWidth - paddingX" :y2="chartHeight - paddingY" stroke="#E2E8F0" stroke-width="1" />
                            </g>

                            <!-- Area Gradient Fill -->
                            <path :d="areaSplinePath" fill="url(#envoyGradient)" />

                            <!-- Previous Period Comparison Spline (Dashed Yellow) -->
                            <path :d="previousSplinePath" fill="none" stroke="#FACC15" stroke-width="1.8" stroke-dasharray="4 4" />

                            <!-- Current Period Spline (Solid Blue) -->
                            <path :d="currentSplinePath" fill="none" stroke="#0D1527" stroke-width="2.5" stroke-linecap="round" />

                            <!-- Active Indicator Line & Point -->
                            <g v-if="activePoint">
                                <line
                                    :x1="activePoint.x"
                                    :y1="paddingY"
                                    :x2="activePoint.x"
                                    :y2="chartHeight - paddingY"
                                    stroke="#94A3B8"
                                    stroke-dasharray="3 3"
                                    stroke-width="1.2"
                                />
                                <circle
                                    :cx="activePoint.x"
                                    :cy="activePoint.y"
                                    r="5.5"
                                    fill="#0D1527"
                                    stroke="#FFFFFF"
                                    stroke-width="2.5"
                                    class="shadow-md transition-all duration-75"
                                />
                            </g>
                        </svg>

                        <!-- Floating Tooltip with Real Naira Format -->
                        <div
                            v-if="activePoint"
                            class="pointer-events-none absolute z-10 w-48 rounded-xl border border-slate-200/90 bg-white/95 p-2.5 shadow-xl backdrop-blur-xs transition-all duration-75"
                            :style="{
                                left: `${Math.min(Math.max((activePoint.x / chartWidth) * 100, 15), 75)}%`,
                                top: `${Math.max((activePoint.y / chartHeight) * 100 - 48, 5)}%`,
                                transform: 'translate(-50%, -50%)'
                            }"
                        >
                            <p class="text-[11px] font-semibold text-slate-800">{{ activePoint.date_label }}</p>
                            <div class="mt-1 space-y-0.5">
                                <p class="flex items-center justify-between text-[11px] font-semibold text-[#0D1527]">
                                    <span class="flex items-center gap-1"><span class="h-1.5 w-3 rounded-full bg-[#0D1527]" /> current</span>
                                    <span>{{ canSeeFinancials ? naira(activePoint.current) : MASK_GLYPH }}</span>
                                </p>
                                <p class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span class="flex items-center gap-1"><span class="h-1.5 w-3 rounded-full bg-[#FACC15]" /> previous</span>
                                    <span>{{ canSeeFinancials ? naira(activePoint.previous) : MASK_GLYPH }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- X-Axis Labels -->
                        <div class="mt-2 flex justify-between px-3 text-[10px] font-medium text-slate-400">
                            <span v-for="(label, i) in xAxisLabels" :key="i">{{ label }}</span>
                        </div>
                        </template>
                    </div>
                </div>

                <!-- Bottom Segment Cards (Envoy Electricals Customer Segments) -->
                <div class="mt-6 border-t border-slate-100 pt-4">
                    <p class="text-[11px] font-medium text-slate-400 mb-2">Customer & Client Breakdown</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div
                            v-for="seg in customer_breakdown"
                            :key="seg.label"
                            class="rounded-xl border-l-4 bg-[#FAF8F2] p-3"
                            :class="{
                                'border-[#40e0d0]': seg.color === 'blue',
                                'border-[#FACC15]': seg.color === 'emerald',
                                'border-[#0D1527]': seg.color === 'amber'
                            }"
                        >
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span class="font-medium text-slate-600">{{ seg.label }}</span>
                                <span class="font-bold text-slate-400 text-[11px]">{{ seg.share }}%</span>
                            </div>
                            <p class="mt-1 text-lg font-bold text-[#0D1527]">{{ seg.count?.toLocaleString() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column (4 cols) -> Peak Day Active & Receivables/Payables -->
            <div class="lg:col-span-4 space-y-6 flex flex-col justify-between">
                <!-- Widget 1: Peak Sales Activity (Weekday Distribution) -->
                <div class="rounded-3xl border border-[#0D1527]/5 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xs font-semibold text-[#0D1527]">Peak Sales Activity</h2>
                            <p class="text-[10px] text-slate-400">Weekday transaction volume (last 60 days)</p>
                        </div>
                        <span class="rounded-full bg-yellow-400 px-2 py-0.5 text-[10px] font-bold text-[#0D1527]">60 Days</span>
                    </div>

                    <!-- Bar Chart with Peak Day Pill -->
                    <p v-if="!weekdays.length" class="mt-4 rounded-xl bg-slate-50 px-3 py-8 text-center text-xs text-slate-400">
                        No sales recorded in the last 60 days.
                    </p>
                    <div v-else class="mt-4 flex h-36 items-end justify-between gap-2 pt-6">
                        <div
                            v-for="item in weekdays"
                            :key="item.day"
                            class="flex flex-1 flex-col items-center justify-end h-full group relative"
                        >
                            <!-- Floating Peak Value Badge on Top -->
                            <div
                                v-if="item.is_peak"
                                class="absolute -top-6 z-10 whitespace-nowrap rounded-md bg-gradient-to-r from-[#0D1527] to-[#1A365D] px-1.5 py-0.5 text-[10px] font-bold text-white shadow-xs"
                            >
                                {{ canSeeFinancials ? naira(item.value) : MASK_GLYPH }}
                            </div>

                            <!-- Bar Column -->
                            <div
                                class="w-full rounded-full transition-all duration-300"
                                :class="item.is_peak ? 'bg-gradient-to-t from-[#40e0d0] to-[#FACC15] shadow-md shadow-[#40e0d0]/30' : 'bg-slate-100 group-hover:bg-slate-200'"
                                :style="{ height: `${Math.max(item.height_pct, 15)}%` }"
                            />

                            <!-- Day Label -->
                            <span
                                class="mt-2 text-[10px] font-medium"
                                :class="item.is_peak ? 'font-bold text-[#0D1527]' : 'text-slate-400'"
                            >
                                {{ item.day }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Widget 2: Cash Flow & Debt Management (Receivables vs Payables) -->
                <div class="rounded-3xl border border-[#0D1527]/5 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)] flex flex-col justify-between">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-xs font-semibold text-[#0D1527]">Debt & Cash Flow</h2>
                            <p class="text-[10px] text-slate-400">Outstanding balances overview</p>
                        </div>
                        <Link href="/admin/payments" class="text-[11px] font-medium text-[#40e0d0] hover:underline">
                            Ledger →
                        </Link>
                    </div>

                    <div class="my-4 space-y-3">
                        <!-- Receivables -->
                        <div class="rounded-xl bg-[#40e0d0]/10 p-3 border border-[#40e0d0]/25">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-teal-700">Customer Receivables</span>
                                <span class="text-xs font-bold text-teal-700">{{ maskNaira('receivables', stats.receivables) }}</span>
                            </div>
                            <p class="mt-0.5 text-[10px] text-teal-700/80">Pending customer invoice balances</p>
                        </div>

                        <!-- Payables -->
                        <div class="rounded-xl bg-yellow-400/10 p-3 border border-yellow-400/25">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-yellow-700">Supplier Payables</span>
                                <span class="text-xs font-bold text-yellow-700">{{ maskNaira('payables', stats.payables) }}</span>
                            </div>
                            <p class="mt-0.5 text-[10px] text-yellow-700/80">Balance owed on stock purchases</p>
                        </div>
                    </div>

                    <!-- Repeat Customer Retention Pill -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                        <span>Repeat Customer Rate</span>
                        <span class="font-bold text-[#0D1527]">{{ repeat_customer_rate }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Fast-Moving Products (7 cols) & Operational Feeds (5 cols) -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Fast-Moving Solar & Electrical Products Table (7 cols) -->
            <div class="lg:col-span-7 rounded-3xl border border-[#0D1527]/5 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)]">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-xs font-bold text-[#0D1527]">Fast-Moving Products & Stock</h2>
                        <p class="text-[10px] text-slate-400">Top selling solar inverters, batteries, panels & electrical materials</p>
                    </div>
                    <Link href="/admin/products" class="text-xs font-semibold text-[#40e0d0] hover:text-[#0D1527]">
                        View Catalog →
                    </Link>
                </div>

                <div class="mt-2 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th class="py-2.5 px-2">Ref / SKU</th>
                                <th class="py-2.5 px-2">Product Name</th>
                                <th class="py-2.5 px-2 text-right">Sold</th>
                                <th class="py-2.5 px-2 text-right">Price</th>
                                <th class="py-2.5 px-2 text-right">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="product in top_products"
                                :key="product.id"
                                class="hover:bg-slate-50/80 transition-colors group"
                            >
                                <td class="py-3 px-2 font-mono text-[11px] text-slate-400">
                                    {{ product.id }}
                                </td>
                                <td class="py-3 px-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#40e0d0]/10 text-[#40e0d0]">
                                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-[#0D1527] truncate max-w-[180px] sm:max-w-xs">{{ product.name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ product.category }} · {{ product.sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-2 text-right font-medium text-slate-600">
                                    {{ product.qty?.toLocaleString() }} units
                                </td>
                                <td class="py-3 px-2 text-right font-semibold text-[#0D1527]">
                                    {{ naira(product.price) }}
                                </td>
                                <td class="py-3 px-2 text-right">
                                    <span
                                        v-if="product.stock <= 0"
                                        class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-600 border border-rose-100"
                                    >
                                        Out of stock
                                    </span>
                                    <span
                                        v-else-if="product.stock <= product.reorder"
                                        class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-600 border border-amber-100"
                                    >
                                        {{ product.stock }} left (Low)
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex rounded-full bg-[#40e0d0]/10 px-2 py-0.5 text-[10px] font-bold text-teal-700 border border-[#40e0d0]/25"
                                    >
                                        {{ product.stock }} in stock
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Operational Tabs: Solar Installations & Recent Sales (5 cols) -->
            <div class="lg:col-span-5 rounded-3xl border border-[#0D1527]/5 bg-white p-5 shadow-[0_12px_34px_rgba(13,21,39,0.06)] flex flex-col justify-between">
                <div>
                    <!-- Tab Switcher -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-1 rounded-xl bg-[#FAF8F2] p-1">
                            <button
                                @click="bottomTab = 'projects'"
                                :class="bottomTab === 'projects' ? 'bg-[#0D1527] font-semibold text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                class="rounded-lg px-3 py-1 text-xs transition-colors"
                            >
                                Solar Projects
                            </button>
                            <button
                                @click="bottomTab = 'sales'"
                                :class="bottomTab === 'sales' ? 'bg-[#0D1527] font-semibold text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                class="rounded-lg px-3 py-1 text-xs transition-colors"
                            >
                                Recent Sales
                            </button>
                        </div>

                        <Link
                            :href="bottomTab === 'projects' ? '/admin/projects' : '/admin/sales'"
                            class="text-xs font-semibold text-[#40e0d0] hover:underline"
                        >
                            View all →
                        </Link>
                    </div>

                    <!-- Tab 1: Solar Projects Feed -->
                    <div v-if="bottomTab === 'projects'" class="mt-3 space-y-3">
                        <div
                            v-for="proj in recent_projects"
                            :key="proj.id"
                            class="flex items-center justify-between rounded-xl border border-[#0D1527]/5 bg-[#FAF8F2]/60 p-3 hover:bg-[#FAF8F2] transition-colors"
                        >
                            <div class="min-w-0 flex-1 pr-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[10px] text-slate-400 font-semibold">{{ proj.ref_id }}</span>
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold border"
                                        :class="getStatusBadge(proj.status).bg"
                                    >
                                        {{ getStatusBadge(proj.status).label }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs font-bold text-[#0D1527] truncate">{{ proj.name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ proj.customer }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-bold text-[#0D1527]">{{ naira(proj.contract_value) }}</p>
                                <p v-if="proj.balance > 0" class="text-[10px] text-amber-600 font-medium">Bal: {{ naira(proj.balance) }}</p>
                                <p v-else class="text-[10px] text-emerald-600 font-medium">Fully Paid</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Recent Sales Invoices Feed -->
                    <div v-else class="mt-3 space-y-3">
                        <div
                            v-for="sale in recent_sales"
                            :key="sale.id"
                            class="flex items-center justify-between rounded-xl border border-[#0D1527]/5 bg-[#FAF8F2]/60 p-3 hover:bg-[#FAF8F2] transition-colors"
                        >
                            <div class="min-w-0 flex-1 pr-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[10px] text-slate-500 font-bold">{{ sale.invoice_no || sale.ref_id }}</span>
                                    <span class="text-[10px] text-slate-400">{{ sale.date }}</span>
                                </div>
                                <p class="mt-0.5 text-xs font-semibold text-[#0D1527] truncate">{{ sale.customer }}</p>
                                <p class="text-[10px] text-slate-400">{{ sale.payment_method }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-bold text-[#0D1527]">{{ naira(sale.total) }}</p>
                                <p v-if="sale.balance > 0" class="text-[10px] text-amber-600 font-medium">Bal: {{ naira(sale.balance) }}</p>
                                <p v-else class="text-[10px] text-emerald-600 font-semibold">Completed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Row -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Automated transaction audit enabled</span>
                    <Link
                        v-if="bottomTab === 'projects'"
                        href="/admin/projects/create"
                        class="text-xs font-semibold text-[#40e0d0] hover:text-[#0D1527]"
                    >
                        + Create Installation
                    </Link>
                    <Link
                        v-else
                        href="/admin/sales/create"
                        class="text-xs font-semibold text-[#40e0d0] hover:text-[#0D1527]"
                    >
                        + Create POS Sale
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>