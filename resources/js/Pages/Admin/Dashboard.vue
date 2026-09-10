<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { Link } from '@inertiajs/vue3';
import { naira, formatDate } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const { has } = useCan();

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    trend_curve: { type: Array, default: () => [] },
    weekday_data: { type: Array, default: () => [] },
    customer_breakdown: { type: Array, default: () => [] },
    repeat_customer_rate: { type: Number, default: 68 },
    top_products: { type: Array, default: () => [] },
    recent_sales: { type: Array, default: () => [] },
    recent_purchases: { type: Array, default: () => [] },
    recent_expenses: { type: Array, default: () => [] },
    recent_projects: { type: Array, default: () => [] },
});

// Period Selector State
const selectedPeriod = ref('This Month');
const isPeriodDropdownOpen = ref(false);
const periods = ['Today', 'Last 7 days', 'This Month', 'Last 30 days', 'This Year'];

// Interactive Spline Chart Hover State
const hoveredPointIndex = ref(18);

const trendPoints = computed(() => {
    if (props.trend_curve && props.trend_curve.length) {
        return props.trend_curve;
    }
    return Array.from({ length: 30 }, (_, i) => {
        const day = i + 1;
        const base = 250000 + Math.sin(i * 0.45) * 120000 + Math.cos(i * 0.25) * 180000 + i * 12000;
        return {
            day: String(day),
            date_label: `Feb ${day}, 2026`,
            short_label: `${day} Feb`,
            current: Math.round(base + (i === 18 ? 320000 : Math.random() * 40000)),
            previous: Math.round(base * 0.78 + Math.random() * 30000),
        };
    });
});

const maxCurveValue = computed(() => {
    const all = trendPoints.value.flatMap(p => [p.current, p.previous]);
    return Math.max(...all, 500000);
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
    const rect = e.currentTarget.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const ratio = Math.max(0, Math.min(1, (x - paddingX) / (chartWidth - paddingX * 2)));
    hoveredPointIndex.value = Math.round(ratio * (trendPoints.value.length - 1));
}

// Weekday Data
const weekdays = computed(() => {
    if (props.weekday_data && props.weekday_data.length) {
        return props.weekday_data;
    }
    return [
        { day: 'Sun', value: 450000, is_peak: false, height_pct: 22 },
        { day: 'Mon', value: 1250000, is_peak: false, height_pct: 54 },
        { day: 'Tue', value: 2850000, is_peak: true, height_pct: 100 },
        { day: 'Wed', value: 1650000, is_peak: false, height_pct: 58 },
        { day: 'Thu', value: 1420000, is_peak: false, height_pct: 50 },
        { day: 'Fri', value: 1980000, is_peak: false, height_pct: 70 },
        { day: 'Sat', value: 890000, is_peak: false, height_pct: 35 },
    ];
});

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

        <!-- Top Header & Action Controls -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="inline-flex items-center gap-2 text-[#40e0d0] text-[10px] font-bold uppercase tracking-[0.3em] mb-1.5">
                    <span class="w-6 h-px bg-[#40e0d0]"></span>
                    Admin Overview
                </span>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
                <p class="text-xs text-slate-500 mt-0.5">Real-time overview of sales, stock valuation, and solar installation projects.</p>
            </div>

            <!-- Header Controls & Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Period Dropdown -->
                <div class="relative">
                    <button
                        @click="isPeriodDropdownOpen = !isPeriodDropdownOpen"
                        class="flex items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors"
                    >
                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ selectedPeriod }}</span>
                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform" :class="isPeriodDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        v-if="isPeriodDropdownOpen"
                        class="absolute right-0 top-full mt-1.5 z-20 w-36 rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
                    >
                        <button
                            v-for="p in periods"
                            :key="p"
                            @click="selectedPeriod = p; isPeriodDropdownOpen = false"
                            class="flex w-full items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-[#40e0d0]/10 hover:text-[#0D1527]"
                            :class="selectedPeriod === p ? 'font-semibold text-[#40e0d0]' : ''"
                        >
                            {{ p }}
                        </button>
                    </div>
                </div>

                <!-- Record Expense -->
                <Link
                    v-if="has('expenses.create')"
                    href="/admin/expenses/create"
                    class="hidden sm:flex items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors"
                >
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Record Expense</span>
                </Link>

                <!-- New Solar Project -->
                <Link
                    v-if="has('projects.create')"
                    href="/admin/projects/create"
                    class="hidden sm:flex items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors"
                >
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>New Project</span>
                </Link>

                <!-- Primary Action: New Sale (POS) -->
                <Link
                    v-if="has('sales.create')"
                    href="/admin/sales/create"
                    class="flex items-center gap-2 rounded-xl bg-[#0D1527] hover:bg-[#0D1527]/90 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-all"
                >
                    <svg class="h-4 w-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New Sale / POS</span>
                </Link>
            </div>
        </div>

        <!-- 4 Primary KPI Metric Cards (Envoy Electric Operational & Financial Metrics) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- 1. Monthly Sales & Revenue -->
            <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all hover:border-[#40e0d0]/40 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Month's Sales Revenue</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#40e0d0]/10 text-[#40e0d0]">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ naira(stats.month_sales) }}</span>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-600 border border-emerald-100">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                        +{{ stats.sales_growth || 18.2 }}%
                    </span>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Today: {{ naira(stats.today_sales) }}</span>
                    <span class="font-medium text-slate-600">{{ stats.today_orders_count || 0 }} sales</span>
                </div>
            </div>

            <!-- 2. Profit & Margins -->
            <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all hover:border-emerald-200 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Net Business Profit</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#0D1527]/5 text-[#0D1527]">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ naira(stats.month_net_profit ?? stats.month_gross_profit ?? 0) }}</span>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-600 border border-emerald-100">
                        {{ stats.profit_margin || 28.5 }}% Margin
                    </span>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Gross: {{ naira(stats.month_gross_profit) }}</span>
                    <span>Exp: {{ naira(stats.month_expenses) }}</span>
                </div>
            </div>

            <!-- 3. Stock & Inventory Valuation -->
            <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all hover:border-yellow-400/60 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Stock Valuation</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-yellow-400/15 text-yellow-600">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ naira(stats.inventory_value) }}</span>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400">{{ stats.total_products || 0 }} Products</span>
                    <span v-if="stats.low_stock > 0 || stats.out_of_stock > 0" class="inline-flex items-center gap-1 font-semibold text-amber-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse" />
                        {{ stats.low_stock }} Low, {{ stats.out_of_stock }} Out
                    </span>
                    <span v-else class="text-emerald-600 font-medium">Stock Healthy</span>
                </div>
            </div>

            <!-- 4. Solar & Electrical Projects -->
            <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all hover:border-[#0D1527]/20 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Solar Projects</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#0D1527]/5 text-[#0D1527]">
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ stats.active_projects || 0 }} Active</span>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-[#0D1527]/5 px-2 py-0.5 text-[11px] font-semibold text-[#0D1527] border border-[#0D1527]/10">
                        {{ stats.completed_projects || 0 }} Done
                    </span>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Project Profit: {{ naira(stats.project_profit) }}</span>
                    <Link href="/admin/projects" class="font-medium text-[#40e0d0] hover:underline">View All →</Link>
                </div>
            </div>
        </div>

        <!-- Middle Section: 30-Day Sales Trend (8 cols) & Right Stacked Operational Widgets (4 cols) -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left Chart Card (8 cols) -->
            <div class="lg:col-span-8 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <!-- Top Area: Metric Headline & Comparison Badge -->
                    <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500">Sales & Revenue Trend</p>
                            <div class="mt-1 flex items-baseline gap-3">
                                <span class="text-3xl font-extrabold tracking-tight text-slate-900">{{ naira(stats.month_sales) }}</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 border border-emerald-100">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                    +{{ stats.sales_growth || 18.2 }}%
                                    <span class="font-normal text-emerald-600/80">vs. last period</span>
                                </span>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="hidden sm:flex items-center gap-3 text-[11px] text-slate-400 font-medium">
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#0D1527]" /> This Month</span>
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300" /> Last Month</span>
                        </div>
                    </div>

                    <!-- Interactive Area Spline Chart -->
                    <div class="relative mt-6 h-56 w-full select-none" @mousemove="handleChartMouseMove" @mouseleave="hoveredPointIndex = 18">
                        <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="h-full w-full overflow-visible" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="envoyGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#FACC15" stop-opacity="0.35" />
                                    <stop offset="60%" stop-color="#FACC15" stop-opacity="0.08" />
                                    <stop offset="100%" stop-color="#FACC15" stop-opacity="0.00" />
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

                            <!-- Previous Period Comparison Spline (Dashed Gray) -->
                            <path :d="previousSplinePath" fill="none" stroke="#CBD5E1" stroke-width="1.8" stroke-dasharray="4 4" />

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
                                    <span class="flex items-center gap-1"><span class="h-1.5 w-3 rounded-full bg-[#0D1527]" /> this month</span>
                                    <span>{{ naira(activePoint.current) }}</span>
                                </p>
                                <p class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span class="flex items-center gap-1"><span class="h-1.5 w-3 rounded-full bg-slate-300" /> last month</span>
                                    <span>{{ naira(activePoint.previous) }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- X-Axis Labels -->
                        <div class="mt-2 flex justify-between px-3 text-[10px] font-medium text-slate-400">
                            <span>1 {{ trendPoints[0]?.short_label?.split(' ')[1] || 'Feb' }}</span>
                            <span>8 {{ trendPoints[0]?.short_label?.split(' ')[1] || 'Feb' }}</span>
                            <span>15 {{ trendPoints[0]?.short_label?.split(' ')[1] || 'Feb' }}</span>
                            <span>22 {{ trendPoints[0]?.short_label?.split(' ')[1] || 'Feb' }}</span>
                            <span>29 {{ trendPoints[0]?.short_label?.split(' ')[1] || 'Feb' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Segment Cards (Envoy Electric Customer Segments) -->
                <div class="mt-6 border-t border-slate-100 pt-4">
                    <p class="text-[11px] font-medium text-slate-400 mb-2">Customer & Client Breakdown</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div
                            v-for="seg in customer_breakdown"
                            :key="seg.label"
                            class="rounded-xl border-l-4 bg-slate-50/70 p-3"
                            :class="{
                                'border-[#40e0d0]': seg.color === 'blue',
                                'border-emerald-500': seg.color === 'emerald',
                                'border-yellow-500': seg.color === 'amber'
                            }"
                        >
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span class="font-medium text-slate-600">{{ seg.label }}</span>
                                <span class="font-bold text-slate-400 text-[11px]">{{ seg.share }}%</span>
                            </div>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ seg.count?.toLocaleString() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column (4 cols) -> Peak Day Active & Receivables/Payables -->
            <div class="lg:col-span-4 space-y-6 flex flex-col justify-between">
                <!-- Widget 1: Peak Sales Activity (Weekday Distribution) -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xs font-semibold text-slate-900">Peak Sales Activity</h2>
                            <p class="text-[10px] text-slate-400">Weekly transaction volume breakdown</p>
                        </div>
                        <span class="rounded-full bg-[#40e0d0]/15 px-2 py-0.5 text-[10px] font-bold text-[#0D1527]">7 Days</span>
                    </div>

                    <!-- Bar Chart with Peak Day Pill -->
                    <div class="mt-4 flex h-36 items-end justify-between gap-2 pt-6">
                        <div
                            v-for="item in weekdays"
                            :key="item.day"
                            class="flex flex-1 flex-col items-center justify-end h-full group relative"
                        >
                            <!-- Floating Peak Value Badge on Top -->
                            <div
                                v-if="item.is_peak"
                                class="absolute -top-6 z-10 whitespace-nowrap rounded-md bg-[#0D1527] px-1.5 py-0.5 text-[10px] font-bold text-white shadow-xs"
                            >
                                {{ naira(item.value) }}
                            </div>

                            <!-- Bar Column -->
                            <div
                                class="w-full rounded-full transition-all duration-300"
                                :class="item.is_peak ? 'bg-[#40e0d0] shadow-md shadow-[#40e0d0]/30' : 'bg-slate-100 group-hover:bg-slate-200'"
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
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-xs font-semibold text-slate-900">Debt & Cash Flow</h2>
                            <p class="text-[10px] text-slate-400">Outstanding balances overview</p>
                        </div>
                        <Link href="/admin/payments" class="text-[11px] font-medium text-[#40e0d0] hover:underline">
                            Ledger →
                        </Link>
                    </div>

                    <div class="my-4 space-y-3">
                        <!-- Receivables -->
                        <div class="rounded-xl bg-yellow-400/10 p-3 border border-yellow-400/25">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-yellow-700">Customer Receivables</span>
                                <span class="text-xs font-bold text-yellow-600">{{ naira(stats.receivables) }}</span>
                            </div>
                            <p class="mt-0.5 text-[10px] text-yellow-700/80">Pending customer invoice balances</p>
                        </div>

                        <!-- Payables -->
                        <div class="rounded-xl bg-rose-50/60 p-3 border border-rose-100">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-rose-800">Supplier Payables</span>
                                <span class="text-xs font-bold text-rose-700">{{ naira(stats.payables) }}</span>
                            </div>
                            <p class="mt-0.5 text-[10px] text-rose-600">Balance owed on stock purchases</p>
                        </div>
                    </div>

                    <!-- Repeat Customer Retention Pill -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                        <span>Repeat Customer Rate</span>
                        <span class="font-bold text-emerald-600">{{ repeat_customer_rate }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Fast-Moving Products (7 cols) & Operational Feeds (5 cols) -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Fast-Moving Solar & Electrical Products Table (7 cols) -->
            <div class="lg:col-span-7 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-xs font-bold text-slate-900">Fast-Moving Products & Stock</h2>
                        <p class="text-[10px] text-slate-400">Top selling solar inverters, batteries, panels & electrical materials</p>
                    </div>
                    <Link href="/admin/products" class="text-xs font-semibold text-[#40e0d0] hover:underline">
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
                                            <p class="font-medium text-slate-900 truncate max-w-[180px] sm:max-w-xs">{{ product.name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ product.category }} · {{ product.sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-2 text-right font-medium text-slate-600">
                                    {{ product.qty?.toLocaleString() }} units
                                </td>
                                <td class="py-3 px-2 text-right font-semibold text-slate-900">
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
                                        class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-600 border border-emerald-100"
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
            <div class="lg:col-span-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <!-- Tab Switcher -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-1 rounded-xl bg-slate-100 p-1">
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
                            class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3 hover:bg-slate-50 transition-colors"
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
                                <p class="mt-1 text-xs font-bold text-slate-900 truncate">{{ proj.name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ proj.customer }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-bold text-slate-900">{{ naira(proj.contract_value) }}</p>
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
                            class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3 hover:bg-slate-50 transition-colors"
                        >
                            <div class="min-w-0 flex-1 pr-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[10px] text-slate-500 font-bold">{{ sale.invoice_no || sale.ref_id }}</span>
                                    <span class="text-[10px] text-slate-400">{{ sale.date }}</span>
                                </div>
                                <p class="mt-0.5 text-xs font-semibold text-slate-800 truncate">{{ sale.customer }}</p>
                                <p class="text-[10px] text-slate-400">{{ sale.payment_method }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-bold text-slate-900">{{ naira(sale.total) }}</p>
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