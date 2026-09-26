<script setup>
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    rows: { type: Array, default: () => [] },
    by_staff: { type: Array, default: () => [] },
    by_method: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    can_view_profit: { type: Boolean, default: false },
});

const groupLink = computed(() => (group) => {
    const p = new URLSearchParams();
    if (props.filters.from) p.set('from', props.filters.from);
    if (props.filters.to) p.set('to', props.filters.to);
    p.set('group', group);
    return `/admin/reports/sales?${p.toString()}`;
});

const exportLink = computed(() => {
    const p = new URLSearchParams();
    if (props.filters.from) p.set('from', props.filters.from);
    if (props.filters.to) p.set('to', props.filters.to);
    return `/admin/reports/export?type=sales&${p.toString()}`;
});

const resetLink = computed(() => (props.filters.from || props.filters.to ? '/admin/reports/sales' : ''));

const revenue = computed(() => Number(props.totals.revenue ?? 0));
const collected = computed(() => Number(props.totals.collected ?? 0));
const outstanding = computed(() => Math.max(revenue.value - collected.value, 0));
const count = computed(() => Number(props.totals.count ?? 0));
const avgInvoice = computed(() => (count.value ? revenue.value / count.value : 0));
const collectRate = computed(() => (revenue.value ? Math.round((collected.value / revenue.value) * 100) : 0));

const maxTotal = computed(() => props.rows.reduce((m, r) => Math.max(m, Number(r.total) || 0), 0));
const tickEvery = computed(() => (props.rows.length > 12 ? Math.ceil(props.rows.length / 8) : 1));

const trend = computed(() => {
    if (props.rows.length < 2) return null;
    const half = Math.ceil(props.rows.length / 2);
    let first = 0;
    let second = 0;
    props.rows.forEach((r, i) => {
        const v = Number(r.total) || 0;
        i < half ? (first += v) : (second += v);
    });
    return first ? Math.round(((second - first) / first) * 100) : null;
});

const bestPeriod = computed(() => (props.rows.length ? props.rows.reduce((best, r) => (Number(r.total) > Number(best.total) ? r : best), props.rows[0]) : null));

const topSeller = computed(() => props.by_staff[0] ?? null);
const topMethod = computed(() => props.by_method[0] ?? null);
const sellerShare = computed(() => (revenue.value && topSeller.value ? Math.round((Number(topSeller.value.total) / revenue.value) * 100) : 0));
const methodShare = computed(() => (revenue.value && topMethod.value ? Math.round((Number(topMethod.value.total) / revenue.value) * 100) : 0));

const DONUT_C = 2 * Math.PI * 26;
const donutOffset = computed(() => DONUT_C * (1 - (collectRate.value || 0) / 100));

const maxStaff = computed(() => props.by_staff.reduce((m, s) => Math.max(m, Number(s.total) || 0), 0));
const maxMethod = computed(() => props.by_method.reduce((m, x) => Math.max(m, Number(x.total) || 0), 0));

function periodLabel(period) {
    const g = props.filters.group;
    if (g === 'month') {
        const [y, m] = period.split('-');
        const names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${names[Number(m) - 1]} ${y}`;
    }
    if (g === 'week') return `Wk ${period.slice(-2)}·${period.slice(0, 4)}`;
    return formatDate(period);
}

const AVATAR_COLORS = ['bg-teal-100 text-teal-700', 'bg-amber-100 text-amber-700', 'bg-sky-100 text-sky-700', 'bg-rose-100 text-rose-700', 'bg-violet-100 text-violet-700', 'bg-emerald-100 text-emerald-700'];

function initials(name) {
    return String(name ?? '—').split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('') || '?';
}

function avatarColor(name) {
    const n = String(name ?? '');
    let h = 0;
    for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) % 997;
    return AVATAR_COLORS[h % AVATAR_COLORS.length];
}

const METHOD_META = {
    cash: { label: 'Cash', icon: 'bi-cash-coin', solid: 'bg-emerald-500', bar: 'bg-emerald-500' },
    transfer: { label: 'Transfer', icon: 'bi-bank', solid: 'bg-sky-500', bar: 'bg-sky-500' },
    bank_transfer: { label: 'Bank Transfer', icon: 'bi-bank', solid: 'bg-sky-500', bar: 'bg-sky-500' },
    card: { label: 'Card', icon: 'bi-credit-card', solid: 'bg-violet-500', bar: 'bg-violet-500' },
    pos: { label: 'POS', icon: 'bi-credit-card-2-front', solid: 'bg-rose-500', bar: 'bg-rose-500' },
    cheque: { label: 'Cheque', icon: 'bi-receipt', solid: 'bg-amber-500', bar: 'bg-amber-500' },
    paystack: { label: 'Paystack', icon: 'bi-wifi', solid: 'bg-indigo-500', bar: 'bg-indigo-500' },
};

function methodMeta(method) {
    return (
        METHOD_META[String(method ?? '').toLowerCase()] ?? {
            label: String(method ?? 'Other'),
            icon: 'bi-three-dots',
            solid: 'bg-slate-400',
            bar: 'bg-slate-400',
        }
    );
}

const cap = (value) => String(value ?? '').charAt(0).toUpperCase() + String(value ?? '').slice(1);
const pc = (v, max) => (max && max > 0 ? Math.max(4, (Number(v) / max) * 100) : 0);
</script>

<template>
        <FlashMessages />

        <div class="relative overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-[#fdfefd] to-[#eef9f6] px-6 py-8 shadow-sm sm:px-8">
            <div class="pointer-events-none absolute -top-20 -right-14 h-64 w-64 rounded-full bg-[#40e0d0]/15 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-8 h-64 w-64 rounded-full bg-[#FACC15]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 1px 1px, rgba(13, 21, 39, 0.08) 1px, transparent 0); background-size: 22px 22px"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-[#0D1527]/10 bg-white/80 px-3 py-1 shadow-sm backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-[#FACC15] ring-2 ring-[#FACC15]/40"></span>
                        <span class="text-[11px] font-bold uppercase tracking-widest text-[#0D1527]">
                            <i class="bi bi-bar-chart-fill mr-1 text-[#40e0d0]"></i> Reports · Sales
                        </span>
                    </div>
                    <h1 class="text-3xl font-black tracking-tight text-[#0D1527] sm:text-4xl">Sales Report</h1>
                    <p class="mt-2 max-w-xl text-sm text-slate-500">
                        Revenue, collection performance and what's selling — {{ props.filters.from || props.filters.to ? 'for the selected range.' : 'across all time.' }}
                    </p>
                    <div v-if="props.filters.from || props.filters.to" class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-[#0D1527]/10 bg-white px-3 py-1 font-semibold text-[#0D1527] shadow-sm">
                            <i class="bi bi-calendar3 text-[#40e0d0]"></i>
                            {{ props.filters.from || 'Start' }} → {{ props.filters.to || 'Today' }}
                        </span>
                        <a :href="resetLink" class="inline-flex items-center gap-1 rounded-full px-2 py-1 font-semibold text-slate-400 hover:text-[#0D1527]">
                            <i class="bi bi-x-lg"></i> Clear
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex rounded-xl border border-[#0D1527]/10 bg-white p-1 shadow-sm">
                        <a
                            v-for="g in ['day', 'week', 'month']"
                            :key="g"
                            :href="groupLink(g)"
                            class="rounded-lg px-4 py-1.5 text-sm font-bold transition-colors"
                            :class="props.filters.group === g ? 'bg-[#0D1527] text-white shadow-sm' : 'text-slate-500 hover:text-[#0D1527]'"
                        >
                            {{ { day: 'Day', week: 'Week', month: 'Month' }[g] }}
                        </a>
                    </div>
                    <a :href="exportLink" class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0D1527]/85">
                        <i class="bi bi-download text-[#FACC15]"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <form class="mt-5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:mt-6 sm:p-5">
            <div class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                    <input :default-value="props.filters.from" name="from" type="date"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-[#40e0d0] focus:ring-2 focus:ring-[#40e0d0]/30 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                    <input :default-value="props.filters.to" name="to" type="date"
                        class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-[#40e0d0] focus:ring-2 focus:ring-[#40e0d0]/30 focus:outline-none" />
                </div>
                <input type="hidden" name="group" :value="props.filters.group" />
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#0D1527]/15 bg-white px-5 py-2 text-sm font-bold text-[#0D1527] shadow-sm transition-colors hover:bg-slate-50">
                    <i class="bi bi-funnel text-[#40e0d0]"></i> Apply Range
                </button>
            </div>
        </form>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="absolute inset-x-0 top-0 h-1 bg-[#FACC15]"></div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FACC15]/20 text-lg text-[#b8860b]"><i class="bi bi-graph-up-arrow"></i></span>
                </div>
                <p class="mt-3 truncate text-2xl font-black tracking-tight text-[#0D1527]">{{ naira(revenue) }}</p>
                <p class="mt-1 flex items-center gap-1 text-xs">
                    <template v-if="trend !== null">
                        <span class="inline-flex items-center gap-0.5 font-bold" :class="trend >= 0 ? 'text-emerald-600' : 'text-red-600'">
                            <i class="bi" :class="trend >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'"></i>{{ Math.abs(trend) }}%
                        </span>
                        <span class="text-slate-400">vs first half · {{ count }} invoice{{ count === 1 ? '' : 's' }}</span>
                    </template>
                    <span v-else class="text-slate-400">{{ count }} invoice{{ count === 1 ? '' : 's' }} in range</span>
                </p>
            </div>

            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="absolute inset-x-0 top-0 h-1 bg-emerald-500"></div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Collected</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-600"><i class="bi bi-cash-stack"></i></span>
                </div>
                <p class="mt-3 truncate text-2xl font-black tracking-tight text-emerald-700">{{ naira(collected) }}</p>
                <p class="mt-1 text-xs text-emerald-600/70">{{ collectRate }}% of revenue collected</p>
            </div>

            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="absolute inset-x-0 top-0 h-1 bg-amber-500"></div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-lg text-amber-600"><i class="bi bi-hourglass-split"></i></span>
                </div>
                <p class="mt-3 truncate text-2xl font-black tracking-tight text-amber-700">{{ naira(outstanding) }}</p>
                <p class="mt-1 text-xs text-amber-600/70">Still owed in range</p>
            </div>

            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="absolute inset-x-0 top-0 h-1 bg-sky-500"></div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Avg / Invoice</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-lg text-sky-600"><i class="bi bi-receipt"></i></span>
                </div>
                <p class="mt-3 truncate text-2xl font-black tracking-tight text-[#0D1527]">{{ naira(avgInvoice) }}</p>
                <p class="mt-1 text-xs text-slate-400">Revenue ÷ invoices</p>
            </div>
        </div>

        <!-- START_INSIGHTS -->
        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-[#0D1527]/[0.02] px-5 py-4">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#FACC15] text-sm text-[#0D1527]"><i class="bi bi-stars"></i></span>
                <div>
                    <h2 class="text-sm font-bold text-[#0D1527]">Key Insights</h2>
                    <p class="text-xs text-slate-400">A quick read of the sales picture</p>
                </div>
            </div>

            <div class="grid gap-px bg-slate-100 sm:grid-cols-2 xl:grid-cols-4">
                <div class="bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Best Period</p>
                        <i class="bi bi-trophy-fill text-amber-500"></i>
                    </div>
                    <template v-if="bestPeriod">
                        <p class="mt-2 text-lg font-black leading-tight text-[#0D1527]">{{ naira(bestPeriod.total) }}</p>
                        <p class="mt-1 text-xs font-bold text-[#40e0d0]">{{ periodLabel(bestPeriod.period) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ bestPeriod.count }} sale{{ bestPeriod.count === 1 ? '' : 's' }} that period</p>
                    </template>
                    <p v-else class="mt-3 text-sm text-slate-400">No data yet.</p>
                </div>

                <div class="flex flex-col justify-between bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Collection Rate</p>
                        <i class="bi bi-pie-chart-fill text-emerald-500"></i>
                    </div>
                    <div class="mt-3 flex items-center gap-4">
                        <div class="relative h-16 w-16 shrink-0">
                            <svg viewBox="0 0 64 64" class="h-16 w-16 -rotate-90">
                                <circle cx="32" cy="32" r="26" fill="none" stroke="#eef2f7" stroke-width="8" />
                                <circle cx="32" cy="32" r="26" fill="none" :stroke="collectRate >= 75 ? '#10b981' : collectRate >= 40 ? '#f59e0b' : '#ef4444'" stroke-width="8" stroke-linecap="round" :stroke-dasharray="DONUT_C" :stroke-dashoffset="donutOffset" />
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-xs font-black text-[#0D1527]">{{ collectRate }}%</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">{{ naira(collected) }}</p>
                            <p class="text-xs text-slate-400">of {{ naira(revenue) }}</p>
                            <p class="mt-0.5 text-xs font-semibold" :class="outstanding > 0 ? 'text-amber-600' : 'text-emerald-600'">
                                {{ outstanding > 0 ? naira(outstanding) + ' outstanding' : 'Fully collected' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Top Salesperson</p>
                        <i class="bi bi-person-arms-up text-sky-500"></i>
                    </div>
                    <template v-if="topSeller">
                        <div class="mt-2 flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border bg-slate-50 text-xs font-black" :class="avatarColor(topSeller.salesperson?.name)">{{ initials(topSeller.salesperson?.name ?? 'Unassigned') }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-[#0D1527]">{{ topSeller.salesperson?.name ?? 'Unassigned' }}</p>
                                <p class="text-xs text-slate-400">{{ topSeller.count }} sales</p>
                            </div>
                        </div>
                        <p class="mt-3 text-lg font-black text-[#0D1527]">{{ naira(topSeller.total) }}</p>
                        <p class="text-xs font-bold text-[#40e0d0]">{{ sellerShare }}% of revenue</p>
                    </template>
                    <p v-else class="mt-3 text-sm text-slate-400">No staff data.</p>
                </div>

                <div class="bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Top Method</p>
                        <i class="bi bi-wallet2 text-violet-500"></i>
                    </div>
                    <template v-if="topMethod">
                        <div class="mt-2 flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl text-base text-white" :class="methodMeta(topMethod.method).solid"><i class="bi" :class="methodMeta(topMethod.method).icon"></i></span>
                            <p class="truncate text-sm font-bold capitalize text-[#0D1527]">{{ methodMeta(topMethod.method).label }}</p>
                        </div>
                        <p class="mt-3 text-lg font-black text-[#0D1527]">{{ naira(topMethod.total) }}</p>
                        <p class="text-xs font-bold text-[#40e0d0]">{{ methodShare }}% of revenue · {{ topMethod.count }} sales</p>
                    </template>
                    <p v-else class="mt-3 text-sm text-slate-400">No payment data.</p>
                </div>
            </div>
        </div>
        <!-- END_INSIGHTS -->

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-[#0D1527]">Revenue Trend</h2>
                    <p class="text-xs text-slate-400">
                        {{ props.rows.length ? `Sales grouped by ${cap(props.filters.group)} — ${props.rows.length} period${props.rows.length === 1 ? '' : 's'}` : 'Nothing in this range yet' }}
                    </p>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#0D1527]"></span> Revenue</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#40e0d0]"></span> Best period</span>
                </div>
            </div>

            <div v-if="props.rows.length" class="px-5 pt-6 pb-4">
                <div class="flex h-52 items-end gap-2">
                    <div v-for="(r, i) in props.rows" :key="r.period" class="group relative flex h-full flex-1 items-end">
                        <div
                            class="relative w-full rounded-t-lg transition-all duration-300"
                            :class="Number(r.total) === maxTotal ? 'bg-gradient-to-t from-[#0D8f7a] to-[#40e0d0]' : 'bg-[#0D1527] opacity-70 group-hover:opacity-100'"
                            :style="{ height: Math.max(4, (Number(r.total) / maxTotal) * 190) + 'px' }"
                        ></div>
                        <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded-lg bg-[#0D1527] px-3 py-1.5 text-xs font-medium text-white shadow-lg group-hover:block">
                            <p>{{ periodLabel(r.period) }}</p>
                            <p class="font-black text-[#FACC15]">{{ naira(r.total) }}</p>
                            <p class="text-slate-300">{{ r.count }} sale{{ r.count === 1 ? '' : 's' }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex h-6 items-end gap-2">
                    <p v-for="(r, i) in props.rows" :key="'x-' + r.period" class="flex-1 truncate text-center text-[10px] font-medium text-slate-400" :class="i % tickEvery === 0 || i === props.rows.length - 1 ? '' : 'opacity-0'" :title="periodLabel(r.period)">
                        {{ periodLabel(r.period) }}
                    </p>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center gap-3 px-5 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-300"><i class="bi bi-bar-chart"></i></span>
                <p class="text-sm font-medium text-slate-500">No sales data for this period</p>
                <p class="text-xs text-slate-400">Adjust the date range or record a sale to see trends here.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-[#0D1527]">By Salesperson</h2>
                        <p class="text-xs text-slate-400">Who brought in the most revenue</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-sm text-white"><i class="bi bi-people-fill"></i></span>
                </div>

                <div v-if="by_staff.length" class="space-y-4">
                    <div v-for="(s, idx) in by_staff" :key="s.salesperson_id" class="group">
                        <div class="flex items-center gap-3">
                            <span class="w-5 text-xs font-black text-slate-300">{{ idx + 1 }}</span>
                            <span :class="idx === 0 ? 'bg-[#FACC15] text-[#0D1527]' : 'bg-slate-100 text-slate-500'" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold">{{ initials(s.salesperson?.name ?? 'Unassigned') }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ s.salesperson?.name ?? 'Unassigned' }}</p>
                                    <p class="text-sm font-black text-[#0D1527]">{{ naira(s.total) }}</p>
                                </div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-[#0D1527] to-[#40e0d0] transition-all duration-500" :style="{ width: pc(s.total, maxStaff) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 pl-8 text-[11px] text-slate-400">{{ s.count }} sale{{ s.count === 1 ? '' : 's' }}</p>
                    </div>
                </div>
                <p v-else class="py-10 text-center text-sm text-slate-400">No staff sales data.</p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-[#0D1527]">By Payment Method</h2>
                        <p class="text-xs text-slate-400">How customers paid</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#FACC15] text-sm text-[#0D1527]"><i class="bi bi-wallet2"></i></span>
                </div>

                <div v-if="by_method.length" class="space-y-4">
                    <div v-for="m in by_method" :key="m.method" class="group">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-base text-white shadow-sm" :class="methodMeta(m.method).solid"><i class="bi" :class="methodMeta(m.method).icon"></i></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="truncate text-sm font-semibold capitalize text-slate-800">{{ methodMeta(m.method).label }}</p>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-[#0D1527]">{{ naira(m.total) }}</p>
                                        <p class="text-[11px] text-slate-400">{{ m.count }} sale{{ m.count === 1 ? '' : 's' }}</p>
                                    </div>
                                </div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full transition-all duration-500" :class="methodMeta(m.method).bar" :style="{ width: pc(m.total, maxMethod) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="py-10 text-center text-sm text-slate-400">No payment data.</p>
            </div>
        </div>
</template>