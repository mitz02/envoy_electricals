<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Link, useForm, usePage, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    packages: { type: Array, default: () => [] },
});

const page = usePage();

const applianceDefs = [
    { name: 'LED Bulbs', watts: 9 },
    { name: 'Ceiling Fan', watts: 75 },
    { name: 'Standing Fan', watts: 60 },
    { name: 'LED TV (43″)', watts: 70 },
    { name: 'Refrigerator', watts: 120 },
    { name: 'Freezer', watts: 150 },
    { name: 'Microwave', watts: 900 },
    { name: 'Electric Kettle', watts: 1800 },
    { name: 'Laptop', watts: 65 },
    { name: 'Desktop / Router', watts: 150 },
    { name: 'Pressing Iron', watts: 1100 },
    { name: 'Air Conditioner (1HP)', watts: 900 },
    { name: 'Air Conditioner (1.5HP)', watts: 1400 },
    { name: 'Water Pump (0.5HP)', watts: 370 },
    { name: 'Deep Freezer (Commercial)', watts: 400 },
    { name: 'Sound System', watts: 300 },
];

function freshRow() {
    return {
        id: Math.floor(Math.random() * 1e9),
        name: applianceDefs[0].name,
        watts: applianceDefs[0].watts,
        quantity: 1,
        hours: 8,
    };
}

const appliances = reactive([
    { id: 1, name: 'LED Bulbs', watts: 9, quantity: 6, hours: 8 },
    { id: 2, name: 'LED TV (43″)', watts: 70, quantity: 1, hours: 8 },
    { id: 3, name: 'Refrigerator', watts: 120, quantity: 1, hours: 24 },
]);

// ---- System parameters ----
const panelWattage = ref(550);
const panelEfficiency = ref(0.8);
const inverterSafetyFactor = ref(1.4);
const batteryCapacity = ref(5);
const inverterSizesText = ref('1, 2.5, 3, 5, 7.5, 10, 15, 20');

const form = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    location: '',
    notes: '',
    recommended_package_id: '',
});

// ---- Helpers ----
const num = (v) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
};

function rowErrors(a) {
    const errs = [];
    if (num(a.watts) <= 0) errs.push('Watts must be greater than 0');
    if (num(a.quantity) < 1) errs.push('Qty must be at least 1');
    const h = num(a.hours);
    if (h < 0 || h > 24) errs.push('Hours must be between 0 and 24');
    return errs;
}

const rowLoadW = (a) => num(a.watts) * num(a.quantity);

const rowDailyWh = (a) => {
    const h = num(a.hours);
    return h >= 0 && h <= 24 ? rowLoadW(a) * h : 0;
};

const rowKwh = (a) => rowDailyWh(a) / 1000;

const hasInvalidRows = computed(() => appliances.some((a) => rowErrors(a).length > 0));

// ---- Load totals ----
const totalLoadW = computed(() => appliances.reduce((sum, a) => sum + rowLoadW(a), 0));
const totalLoadKw = computed(() => totalLoadW.value / 1000);

const totalDailyWh = computed(() => appliances.reduce((sum, a) => sum + rowDailyWh(a), 0));
const totalDailyKwh = computed(() => totalDailyWh.value / 1000);

// ---- Solar panel sizing ----
const panelWattageValid = computed(() => num(panelWattage.value) > 0);
const efficiencyValid = computed(() => {
    const e = num(panelEfficiency.value);
    return e > 0 && e <= 1;
});
const panelParamsValid = computed(() => panelWattageValid.value && efficiencyValid.value);

const panelsRequired = computed(() =>
    panelParamsValid.value && totalDailyWh.value > 0
        ? Math.ceil(totalDailyWh.value / (num(panelWattage.value) * num(panelEfficiency.value)))
        : 0,
);
const arrayCapacityKw = computed(() => (panelsRequired.value * num(panelWattage.value)) / 1000);

// ---- Inverter sizing ----
const safetyValid = computed(() => num(inverterSafetyFactor.value) > 0);

const inverterRequiredW = computed(() => totalLoadW.value * num(inverterSafetyFactor.value));
const inverterRequiredKw = computed(() => inverterRequiredW.value / 1000);

const availableSizes = computed(() =>
    inverterSizesText.value
        .split(',')
        .map((s) => parseFloat(s.trim()))
        .filter((n) => Number.isFinite(n) && n > 0),
);

const recommendedInverterKw = computed(() => {
    const need = inverterRequiredKw.value;
    if (!need) return 0;
    const found = availableSizes.value.filter((s) => s >= need).sort((a, b) => a - b)[0];
    if (found) return found;
    return Math.ceil(need / 5) * 5;
});

// ---- Battery sizing ----
const batteryValid = computed(() => num(batteryCapacity.value) > 0);

const batteriesRequired = computed(() =>
    batteryValid.value && totalDailyKwh.value > 0
        ? Math.ceil(totalDailyKwh.value / num(batteryCapacity.value))
        : 0,
);
const totalStorageKwh = computed(() => batteriesRequired.value * num(batteryCapacity.value));

// ---- Pricing ----
const estimatedPrice = computed(() => {
    if (form.recommended_package_id) {
        const pkg = props.packages.find((p) => String(p.id) === String(form.recommended_package_id));
        if (pkg) return Number(pkg.package_price) + Number(pkg.installation_cost || 0);
    }
    return Math.round(panelsRequired.value * 185000 + totalDailyKwh.value * 220000);
});

// ---- Formatting ----
function naira(v) {
    return '₦' + Number(v || 0).toLocaleString('en-NG');
}

function fmtKw(v) {
    const n = Math.round(v * 100) / 100;
    return (n % 1 === 0 ? n.toFixed(0) : String(n)) + 'kW';
}

function fmtNum(v) {
    return Number(v || 0).toLocaleString('en-NG');
}

const maxRowWh = computed(() => Math.max(1, ...appliances.map(rowDailyWh)));

// ---- Actions ----
function addAppliance() {
    appliances.push(freshRow());
}

function removeAppliance(index) {
    appliances.splice(index, 1);
}

function selectDef(a, name) {
    const def = applianceDefs.find((d) => d.name === name);
    if (def) a.watts = def.watts;
}

function submit() {
    if (hasInvalidRows.value) return;
    if (!form.customer_name || !form.customer_phone) return;
    form
        .transform((data) => ({
            ...data,
            appliances: appliances.map((a) => ({
                name: a.name,
                watts: Number(a.watts),
                quantity: Number(a.quantity),
                hours: Number(a.hours),
            })),
            total_connected_load: totalLoadW.value,
            daily_consumption_kwh: totalDailyKwh.value,
            peak_load_kw: totalLoadKw.value,
            recommended_inverter: fmtKw(recommendedInverterKw.value),
            recommended_panels: panelsRequired.value,
            recommended_battery: `${batteriesRequired.value} × ${batteryCapacity.value}kWh battery`,
            recommended_package_id: form.recommended_package_id,
            estimated_price: estimatedPrice.value,
        }))
        .post('/calculator', { preserveScroll: true });
}

// ---- Scroll reveal (matches contact page) ----
function observeReveal() {
    const els = document.querySelectorAll('.reveal');
    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal-visible');
                    io.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1 }
    );
    els.forEach((el) => io.observe(el));
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const pkgId = params.get('package');
    if (pkgId) form.recommended_package_id = pkgId;
    observeReveal();
});
</script>

<template>
    <Head title="Solar Load Calculator — Envoy Electricals" />

    <!-- ============================ HERO ============================ -->
    <section class="relative overflow-hidden">
        <div class="absolute -top-20 -right-16 w-96 h-96 rounded-full bg-yellow-400/[0.10] blur-[120px] pointer-events-none"></div>
        <div class="absolute -top-24 -left-16 w-80 h-80 rounded-full bg-[#40e0d0]/[0.08] blur-[120px] pointer-events-none"></div>
        <div class="absolute inset-0" style="background-image:radial-gradient(rgba(13,21,39,0.05) 1px, transparent 1px); background-size:26px 26px;" aria-hidden="true"></div>

        <div class="relative max-w-3xl mx-auto text-center pt-20 sm:pt-28 pb-14 px-6">
            <span class="inline-flex items-center gap-2.5 mb-6">
                <span class="w-10 h-[2px] bg-[#40e0d0]"></span>
                <span class="text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em]">Free Solar Load Calculator</span>
                <span class="w-10 h-[2px] bg-[#40e0d0]"></span>
            </span>
            <h1 class="text-4xl sm:text-5xl lg:text-[56px] font-extrabold tracking-tight text-slate-950 leading-[1.08]">
                Size Your
                <span class="bg-gradient-to-r from-yellow-500 via-amber-400 to-amber-500 bg-clip-text text-transparent">Solar System</span>
            </h1>
            <p class="mt-5 text-slate-600 text-base sm:text-lg leading-relaxed max-w-xl mx-auto">
                Add the appliances you run every day and we'll instantly size your solar panels, inverter and battery bank — accurate to the exact watt, every time.
            </p>

            <!-- live stat chips -->
            <div class="mt-8 flex flex-wrap justify-center gap-3 sm:gap-4">
                <div class="reveal bg-white rounded-2xl rounded-tr-none px-5 py-4 shadow-[0_2px_14px_rgba(0,0,0,0.06)] text-left min-w-[150px]">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400">Connected Load</p>
                    <p class="text-xl font-black text-yellow-600 mt-0.5">{{ totalLoadKw.toFixed(2) }} <span class="text-xs font-bold text-slate-400">kW</span></p>
                </div>
                <div class="reveal bg-white rounded-2xl rounded-tr-none px-5 py-4 shadow-[0_2px_14px_rgba(0,0,0,0.06)] text-left min-w-[150px]" style="transition-delay: 60ms">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400">Daily Energy</p>
                    <p class="text-xl font-black text-[#40e0d0] mt-0.5">{{ totalDailyKwh.toFixed(2) }} <span class="text-xs font-bold text-slate-400">kWh/day</span></p>
                </div>
                <div class="reveal bg-white rounded-2xl rounded-tr-none px-5 py-4 shadow-[0_2px_14px_rgba(0,0,0,0.06)] text-left min-w-[150px]" style="transition-delay: 120ms">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400">Panels Needed</p>
                    <p class="text-xl font-black text-slate-950 mt-0.5">{{ panelsRequired }} <span class="text-xs font-bold text-slate-400">× {{ panelWattage }}W</span></p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================ BODY ============================ -->
    <div class="bg-[#FAF8F2]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">
            <!-- ======================= LEFT: INPUTS ======================= -->
            <div class="lg:col-span-3 space-y-8">
                <!-- STEP 1 — APPLIANCES -->
                <section id="appliances" class="reveal bg-white rounded-2xl rounded-tr-none shadow-[0_2px_14px_rgba(0,0,0,0.06)] overflow-hidden hover:shadow-[0_10px_30px_rgba(0,0,0,0.10)] transition-shadow">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-4">
                            <span class="grid h-11 w-11 place-items-center rounded-2xl rounded-tr-none bg-yellow-400/10 text-yellow-500 text-lg font-black">1</span>
                            <div>
                                <h2 class="text-base sm:text-lg font-extrabold text-slate-950 leading-tight">Your Appliances</h2>
                                <p class="text-xs text-slate-400">Every device you power, with hours of daily use.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl rounded-tr-none bg-yellow-400 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-yellow-300 hover:-translate-y-0.5 transition-all duration-200 shadow-sm"
                            @click="addAppliance"
                        >
                            <i class="bi bi-plus-lg"></i> Add appliance
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] border-collapse">
                            <thead>
                                <tr class="bg-[#0D1527] text-left text-[10.5px] font-bold uppercase tracking-widest text-slate-300">
                                    <th class="px-3 py-3 rounded-tl-xl whitespace-nowrap">Appliance</th>
                                    <th class="px-3 py-3">Watts (W)</th>
                                    <th class="px-3 py-3">Qty</th>
                                    <th class="px-3 py-3">Hrs/Day</th>
                                    <th class="px-3 py-3">Total Load</th>
                                    <th class="px-3 py-3">Daily Energy</th>
                                    <th class="px-3 py-3 rounded-tr-xl"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(a, i) in appliances"
                                    :key="a.id"
                                    :class="rowErrors(a).length ? 'bg-red-50/60' : 'bg-white'"
                                    class="border-b border-slate-100 transition-colors"
                                >
                                    <td class="px-3 py-3">
                                        <select
                                            v-model="a.name"
                                            @change="selectDef(a, a.name)"
                                            class="w-full min-w-[130px] rounded-lg rounded-tr-none border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] px-2.5 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                        >
                                            <option v-for="d in applianceDefs" :key="d.name" :value="d.name">{{ d.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-3">
                                        <input
                                            v-model.number="a.watts"
                                            type="number"
                                            min="1"
                                            :class="num(a.watts) <= 0 ? 'border-red-400 ring-4 ring-red-400/20' : 'border-[#d9d7d0]'"
                                            class="w-20 rounded-lg rounded-tr-none border-[1.5px] bg-[#fbfbf9] px-2.5 py-2 text-sm text-right font-semibold text-slate-800 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                        />
                                        <p v-if="num(a.watts) <= 0" class="mt-1 text-[10px] font-semibold text-red-500">> 0</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <input
                                            v-model.number="a.quantity"
                                            type="number"
                                            min="1"
                                            :class="num(a.quantity) < 1 ? 'border-red-400 ring-4 ring-red-400/20' : 'border-[#d9d7d0]'"
                                            class="w-16 rounded-lg rounded-tr-none border-[1.5px] bg-[#fbfbf9] px-3 py-2 text-sm text-center font-semibold text-slate-800 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                        />
                                        <p v-if="num(a.quantity) < 1" class="mt-1 text-[10px] font-semibold text-red-500">≥ 1</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <input
                                            v-model.number="a.hours"
                                            type="number"
                                            min="0"
                                            max="24"
                                            :class="num(a.hours) < 0 || num(a.hours) > 24 ? 'border-red-400 ring-4 ring-red-400/20' : 'border-[#d9d7d0]'"
                                            class="w-16 rounded-lg rounded-tr-none border-[1.5px] bg-[#fbfbf9] px-3 py-2 text-sm text-center font-semibold text-slate-800 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                        />
                                        <p v-if="num(a.hours) < 0 || num(a.hours) > 24" class="mt-1 text-[10px] font-semibold text-red-500">0–24</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-sm font-bold text-slate-900">{{ fmtNum(rowLoadW(a)) }} W</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-sm font-bold text-[#40e0d0]">{{ fmtNum(rowDailyWh(a)) }} Wh</span>
                                        <span class="block text-[10.5px] font-semibold text-slate-400">{{ rowKwh(a).toFixed(2) }} kWh</span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <button
                                            type="button"
                                            :disabled="appliances.length === 1"
                                            class="grid h-8 w-8 place-items-center rounded-lg rounded-tr-none border border-slate-200 text-slate-400 hover:border-red-300 hover:text-red-500 disabled:opacity-30 transition-colors"
                                            title="Remove appliance"
                                            @click="removeAppliance(i)"
                                        >
                                            <i class="bi bi-trash3 text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-[#FAF8F2]">
                                    <td class="px-3 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 whitespace-nowrap">Totals</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="px-3 py-4 text-sm font-black text-slate-950">{{ fmtNum(totalLoadW) }} W</td>
                                    <td class="px-3 py-4 text-sm font-black text-[#40e0d0]">
                                        {{ fmtNum(totalDailyWh) }} Wh
                                        <span class="block text-[10.5px] font-semibold text-slate-400">{{ totalDailyKwh.toFixed(2) }} kWh</span>
                                    </td>
                                    <td></td>
                                </tr>
                                </tfoot>
                        </table>
                    </div>

                    <div v-if="appliances.length === 0" class="py-12 text-center">
                        <p class="text-sm text-slate-400">No appliances yet — tap “Add appliance” to build your load list.</p>
                    </div>
                    <p v-if="hasInvalidRows" class="border-t border-red-100 bg-red-50 px-5 sm:px-7 py-3 text-xs font-semibold text-red-600">
                        Fix the highlighted fields above before saving your design.
                    </p>
                </section>

                <!-- STEP 2 — SYSTEM PARAMETERS -->
                <section id="parameters" class="reveal bg-white rounded-2xl rounded-tr-none shadow-[0_2px_14px_rgba(0,0,0,0.06)] overflow-hidden hover:shadow-[0_10px_30px_rgba(0,0,0,0.10)] transition-shadow" style="transition-delay: 60ms">
                    <div class="flex items-center gap-4 border-b border-slate-100 px-5 sm:px-7 py-5">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl rounded-tr-none bg-yellow-400/10 text-yellow-500 text-lg font-black">2</span>
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-950 leading-tight">System Parameters</h2>
                            <p class="text-xs text-slate-400">Tune panel, inverter and battery assumptions to match your gear.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 px-5 sm:px-7 py-6">
                        <!-- Panel wattage -->
                        <div class="rounded-2xl rounded-tr-none border border-slate-200 bg-white p-4 shadow-[0_2px_14px_rgba(0,0,0,0.04)]">
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-500">Solar Panel Wattage</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input
                                    v-model.number="panelWattage"
                                    type="number"
                                    min="1"
                                    :class="panelWattageValid ? 'border-[#d9d7d0]' : 'border-red-400 ring-4 ring-red-400/20'"
                                    class="w-24 rounded-lg rounded-tr-none border-[1.5px] bg-[#fbfbf9] px-3 py-2 text-sm font-bold text-right text-slate-900 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                />
                                <span class="text-sm font-semibold text-slate-400">W</span>
                            </div>
                            <p v-if="!panelWattageValid" class="mt-1 text-[10.5px] font-semibold text-red-500">Must be greater than 0</p>
                        </div>

                        <!-- Panel efficiency -->
                        <div class="rounded-2xl rounded-tr-none border border-slate-200 bg-white p-4 shadow-[0_2px_14px_rgba(0,0,0,0.04)]">
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-500">Panel Efficiency Factor</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input
                                    v-model.number="panelEfficiency"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="1"
                                    :class="efficiencyValid ? 'border-[#d9d7d0]' : 'border-red-400 ring-4 ring-red-400/20'"
                                    class="w-24 rounded-lg rounded-tr-none border-[1.5px] bg-[#fbfbf9] px-3 py-2 text-sm font-bold text-right text-slate-900 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                />
                                <span class="text-sm font-semibold text-slate-400">×</span>
                            </div>
                            <p v-if="!efficiencyValid" class="mt-1 text-[10.5px] font-semibold text-red-500">Between 0 and 1</p>
                        </div>

                        <!-- Inverter safety factor -->
                        <div class="rounded-2xl rounded-tr-none border border-slate-200 bg-white p-4 shadow-[0_2px_14px_rgba(0,0,0,0.04)]">
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-500">Inverter Safety Factor</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input
                                    v-model.number="inverterSafetyFactor"
                                    type="number"
                                    step="0.05"
                                    min="0.01"
                                    :class="safetyValid ? 'border-[#d9d7d0]' : 'border-red-400 ring-4 ring-red-400/20'"
                                    class="w-24 rounded-lg rounded-tr-none border-[1.5px] bg-[#fbfbf9] px-3 py-2 text-sm font-bold text-right text-slate-900 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                />
                                <span class="text-sm font-semibold text-slate-400">×</span>
                            </div>
                            <p v-if="!safetyValid" class="mt-1 text-[10.5px] font-semibold text-red-500">Must be greater than 0</p>
                        </div>

                        <!-- Battery capacity -->
                        <div class="rounded-2xl rounded-tr-none border border-slate-200 bg-white p-4 shadow-[0_2px_14px_rgba(0,0,0,0.04)]">
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-500">Battery Capacity</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input
                                    v-model.number="batteryCapacity"
                                    type="number"
                                    step="0.5"
                                    min="0.1"
                                    :class="batteryValid ? 'border-[#d9d7d0]' : 'border-red-400 ring-4 ring-red-400/20'"
                                    class="w-24 rounded-lg rounded-tr-none border-[1.5px] bg-[#fbfbf9] px-3 py-2 text-sm font-bold text-right text-slate-900 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                />
                                <span class="text-sm font-semibold text-slate-400">kWh</span>
                            </div>
                            <p v-if="!batteryValid" class="mt-1 text-[10.5px] font-semibold text-red-500">Must be greater than 0</p>
                        </div>

                        <!-- Inverter sizes -->
                        <div class="rounded-2xl rounded-tr-none border border-slate-200 bg-white p-4 shadow-[0_2px_14px_rgba(0,0,0,0.04)] sm:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-500">Available Inverter Sizes (kW)</label>
                            <input
                                v-model="inverterSizesText"
                                type="text"
                                placeholder="e.g. 1, 2.5, 3, 5, 7.5, 10, 15, 20"
                                class="mt-2 w-full rounded-lg rounded-tr-none border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] px-3 py-2.5 text-sm font-semibold text-slate-800 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                            />
                            <p class="mt-1.5 text-[11px] text-slate-400">
                                The nearest size <span class="font-bold text-slate-600">equal to or greater</span> than the requirement is recommended automatically.
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    v-for="s in availableSizes"
                                    :key="s"
                                    class="rounded-full px-2.5 py-1 text-[10.5px] font-bold"
                                    :class="s >= inverterRequiredKw && s === recommendedInverterKw ? 'bg-[#40e0d0] text-slate-950 shadow-sm' : 'bg-slate-200 text-slate-600'"
                                >
                                    {{ s.toLocaleString() }}kW
                                </span>
                                <span v-if="!availableSizes.length" class="text-[11px] font-semibold text-red-500">Enter at least one valid size.</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- STEP 3 — SAVE YOUR DESIGN -->
                <section id="save" class="reveal bg-white rounded-2xl rounded-tr-none shadow-[0_2px_14px_rgba(0,0,0,0.06)] overflow-hidden hover:shadow-[0_10px_30px_rgba(0,0,0,0.10)] transition-shadow" style="transition-delay: 120ms">
                    <div class="flex items-center gap-4 border-b border-slate-100 px-5 sm:px-7 py-5">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl rounded-tr-none bg-yellow-400/10 text-yellow-500 text-lg font-black">3</span>
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-950 leading-tight">Save Your Design</h2>
                            <p class="text-xs text-slate-400">Leave your details and we'll send the full quotation within 24 hours.</p>
                        </div>
                    </div>

                    <div class="px-5 sm:px-7 py-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <input
                                    v-model="form.customer_name"
                                    type="text"
                                    placeholder="Full name *"
                                    required
                                    class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                />
                                <p v-if="form.errors.customer_name" class="mt-1 text-xs font-semibold text-red-600">{{ form.errors.customer_name }}</p>
                            </div>
                            <div>
                                <input
                                    v-model="form.customer_phone"
                                    type="tel"
                                    placeholder="Phone number *"
                                    required
                                    class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                />
                                <p v-if="form.errors.customer_phone" class="mt-1 text-xs font-semibold text-red-600">{{ form.errors.customer_phone }}</p>
                            </div>
                            <input
                                v-model="form.customer_email"
                                type="email"
                                placeholder="Email"
                                class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                            />
                            <input
                                v-model="form.location"
                                type="text"
                                placeholder="Location (e.g. Lekki Phase 1)"
                                class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                            />
                        </div>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            placeholder="Anything else we should know? (optional)"
                            class="mt-4 w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all resize-y"
                        ></textarea>
                    </div>
                </section>
            </div>

            <!-- ======================= RIGHT: RESULTS ======================= -->
            <div class="lg:col-span-2 space-y-6 lg:sticky lg:top-24">
                <!-- Live dashboard -->
                <section id="results" class="reveal rounded-3xl rounded-tr-none bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] text-white p-5 sm:p-6 shadow-2xl overflow-hidden relative">
                    <div class="absolute -top-24 -right-20 w-64 h-64 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xs font-bold uppercase tracking-widest text-slate-300">Live System Design</h2>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 border border-emerald-400/30 px-2.5 py-1 text-[10px] font-bold text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Updating live
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl rounded-tr-none border border-white/10 bg-white/5 p-3.5">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Connected Load</p>
                                <p class="mt-1 text-lg font-black text-yellow-400">{{ totalLoadKw.toFixed(2) }} <span class="text-xs font-bold text-slate-300">kW</span></p>
                                <p class="text-[10.5px] font-semibold text-slate-400">{{ fmtNum(totalLoadW) }} W</p>
                            </div>
                            <div class="rounded-2xl rounded-tr-none border border-white/10 bg-white/5 p-3.5">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Daily Energy</p>
                                <p class="mt-1 text-lg font-black text-[#40e0d0]">{{ totalDailyKwh.toFixed(2) }} <span class="text-xs font-bold text-slate-300">kWh</span></p>
                                <p class="text-[10.5px] font-semibold text-slate-400">{{ fmtNum(totalDailyWh) }} Wh / day</p>
                            </div>
                            <div class="rounded-2xl rounded-tr-none border border-white/10 bg-white/5 p-3.5">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Solar Panels</p>
                                <p class="mt-1 text-lg font-black text-white">{{ panelsRequired }} <span class="text-xs font-bold text-slate-300">pcs</span></p>
                                <p class="text-[10.5px] font-semibold text-slate-400">× {{ panelWattage }}W @ {{ panelEfficiency }} eff.</p>
                            </div>
                            <div class="rounded-2xl rounded-tr-none border border-white/10 bg-white/5 p-3.5">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Array Capacity</p>
                                <p class="mt-1 text-lg font-black text-white">{{ arrayCapacityKw.toFixed(2) }} <span class="text-xs font-bold text-slate-300">kW</span></p>
                                <p class="text-[10.5px] font-semibold text-slate-400">{{ panelsRequired }} × {{ panelWattage }}W</p>
                            </div>
                            <div class="rounded-2xl rounded-tr-none border border-white/10 bg-white/5 p-3.5">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Inverter Required</p>
                                <p class="mt-1 text-lg font-black text-yellow-400">{{ inverterRequiredKw.toFixed(2) }} <span class="text-xs font-bold text-slate-300">kW</span></p>
                                <p class="text-[10.5px] font-semibold text-slate-400">{{ fmtNum(inverterRequiredW) }} W × {{ inverterSafetyFactor }}</p>
                            </div>
                            <div class="rounded-2xl rounded-tr-none border border-[#40e0d0]/30 bg-[#40e0d0]/10 p-3.5">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-[#40e0d0]">Recommended Inverter</p>
                                <p class="mt-1 text-lg font-black text-white">{{ fmtKw(recommendedInverterKw) }}</p>
                                <p class="text-[10.5px] font-semibold text-slate-400">nearest size ≥ required</p>
                            </div>
                            <div class="rounded-2xl rounded-tr-none border border-white/10 bg-white/5 p-3.5">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Batteries Required</p>
                                <p class="mt-1 text-lg font-black text-white">{{ batteriesRequired }} <span class="text-xs font-bold text-slate-300">pcs</span></p>
                                <p class="text-[10.5px] font-semibold text-slate-400">× {{ batteryCapacity }}kWh</p>
                            </div>
                            <div class="rounded-2xl rounded-tr-none border border-white/10 bg-white/5 p-3.5">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Battery Storage</p>
                                <p class="mt-1 text-lg font-black text-white">{{ totalStorageKwh.toFixed(2) }} <span class="text-xs font-bold text-slate-300">kWh</span></p>
                                <p class="text-[10.5px] font-semibold text-slate-400">{{ batteriesRequired }} × {{ batteryCapacity }}kWh</p>
                            </div>
                        </div>

                        <!-- package + price -->
                        <div class="mt-5">
                            <label class="text-[11px] font-bold uppercase tracking-wide text-slate-300">Choose a package (optional)</label>
                            <select v-model="form.recommended_package_id" class="mt-2 w-full rounded-xl rounded-tr-none border-white/20 bg-white/10 text-white text-sm [&>option]:text-slate-900 focus:outline-none focus:border-yellow-400 focus:ring-4 focus:ring-yellow-400/20">
                                <option value="">— No package selected —</option>
                                <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }} — {{ naira(p.package_price) }}</option>
                            </select>
                        </div>

                        <div class="mt-5 border-t border-white/10 pt-4 flex items-end justify-between">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-300">Est. price</span>
                            <span class="text-2xl font-black text-yellow-400">{{ naira(estimatedPrice) }}</span>
                        </div>

                        <button
                            type="button"
                            :disabled="form.processing || hasInvalidRows || !form.customer_name || !form.customer_phone"
                            class="mt-5 w-full rounded-xl rounded-tr-none bg-gradient-to-r from-yellow-400 to-amber-400 hover:brightness-105 text-slate-950 font-black text-sm py-4 shadow-[4px_4px_0_#060b16] hover:translate-x-[1px] hover:translate-y-[1px] hover:shadow-[2px_2px_0_#060b16] disabled:opacity-40 disabled:transform-none disabled:shadow-none transition-all duration-150"
                            @click="submit"
                        >{{ form.processing ? 'Saving…' : 'Send My Free Quote Request' }}</button>

                        <p v-if="hasInvalidRows" class="mt-2 text-center text-[11px] font-semibold text-amber-300">Fix the highlighted appliance fields to enable the button.</p>

                        <p v-if="page.props.flash?.success" class="mt-3 rounded-2xl rounded-tr-none border border-emerald-400/30 bg-emerald-400/10 p-3.5 text-xs font-semibold text-emerald-300">
                            {{ page.props.flash.success }}
                        </p>
                    </div>
                </section>

                <!-- Load breakdown -->
                <section class="reveal bg-white rounded-2xl rounded-tr-none shadow-[0_2px_14px_rgba(0,0,0,0.06)] overflow-hidden hover:shadow-[0_10px_30px_rgba(0,0,0,0.10)] transition-shadow" style="transition-delay: 60ms">
                    <div class="flex items-center justify-between px-5 sm:px-6 py-5 border-b border-slate-100">
                        <h2 class="text-sm font-extrabold text-slate-950">Load Breakdown</h2>
                        <span class="text-[11px] font-bold text-slate-400">Daily energy per appliance</span>
                    </div>
                    <div class="space-y-4 px-5 sm:px-6 py-5">
                        <div v-for="a in appliances" :key="a.id" class="group">
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span class="font-semibold text-slate-700 truncate">{{ a.name }}</span>
                                <span class="shrink-0 font-black text-slate-900">{{ rowKwh(a).toFixed(2) }} <span class="text-[10px] font-bold text-slate-400">kWh</span></span>
                            </div>
                            <div class="mt-1.5 h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-yellow-400 to-[#40e0d0] transition-all duration-500"
                                    :style="{ width: `${(rowDailyWh(a) / maxRowWh) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                        <div v-if="appliances.length === 0" class="py-6 text-center text-sm text-slate-400">Add appliances to see the breakdown.</div>
                        <div class="border-t border-slate-100 pt-4 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-widest text-slate-500">Total / day</span>
                            <span class="text-lg font-black text-[#40e0d0]">{{ totalDailyKwh.toFixed(2) }} <span class="text-xs text-slate-400">kWh</span></span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- ===================== WHATSAPP FLOAT ===================== -->
    <a
        href="https://wa.me/2348000000000"
        target="_blank"
        rel="noopener"
        title="Chat on WhatsApp"
        class="fixed bottom-6 right-6 z-50 w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#25D366] text-white flex items-center justify-center shadow-[0_6px_18px_rgba(37,211,102,0.4)] hover:bg-[#1fb858] hover:-translate-y-1 active:translate-y-0 transition-all duration-200"
    >
        <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm4.5 13c-.2.2-1.4.7-1.7.8s-.4.1-.7 0-.9-.3-1.4-.7a5.6 5.6 0 0 1-1.9-2.5c-.1-.2.2-.4.4-.6l.4-.6a.8.8 0 0 0 .1-.2.5.5 0 0 0 0-.4c-.1-.2-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a.8.8 0 0 0-.6.3 2.6 2.6 0 0 0-.8 1.9 4.5 4.5 0 0 0 .9 2.4 10 10 0 0 0 3.9 3.4c1.2.6 1.7.6 2.1.6a2.6 2.6 0 0 0 1.7-1.2 2 2 0 0 0 .1-1.2z"/></svg>
        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#E4312B] border-2 border-white"></span>
    </a>
</template>

<style scoped>
.reveal {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.7s ease, transform 0.7s ease;
}
.reveal-visible {
    opacity: 1;
    transform: none;
}
</style>