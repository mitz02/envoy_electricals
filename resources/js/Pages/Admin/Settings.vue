<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    categories: { type: Array, default: () => [] },
    calculatorSettings: { type: Object, default: () => ({}) },
    businessSettings: { type: Object, default: () => ({}) },
    bankSettings: { type: Object, default: () => ({}) },
});

const form = useForm({
    name: '',
});

function parseJsonSetting(key, fallback = []) {
    const val = props.calculatorSettings[`calculator.${key}`];
    if (val == null || val === '') {
        return fallback;
    }
    if (typeof val === 'object') {
        return val;
    }
    try {
        return JSON.parse(val);
    } catch {
        return fallback;
    }
}

function getPrice(key, size, fallback = 0) {
    const prices = parseJsonSetting(key, {});
    return prices[size] ?? fallback;
}

const calcForm = useForm({
    panel_wattage: props.calculatorSettings['calculator.panel_wattage'] || 620,
    panel_efficiency: props.calculatorSettings['calculator.panel_efficiency'] || 0.8,
    peak_sun_hours: props.calculatorSettings['calculator.peak_sun_hours'] || 4.5,
    battery_depth_of_discharge: props.calculatorSettings['calculator.battery_depth_of_discharge'] || 0.8,
    inverter_safety_factor: props.calculatorSettings['calculator.inverter_safety_factor'] || 1.4,
    battery_capacities: props.calculatorSettings['calculator.battery_capacities'] || '1.2, 2.56, 5.12, 10.24, 16, 20, 25.6, 32',
    inverter_sizes: props.calculatorSettings['calculator.inverter_sizes'] || '1.5, 3, 5, 8, 10, 12',
    price_per_panel: props.calculatorSettings['calculator.price_per_panel'] || 185000,
    price_per_kwh_daily: props.calculatorSettings['calculator.price_per_kwh_daily'] || 220000,
    inverter_prices: parseJsonSetting('inverter_prices', {}),
    battery_prices: parseJsonSetting('battery_prices', {}),
    installation_costs: parseJsonSetting('installation_costs', {}),
});

const businessForm = useForm({
    'business.name': props.businessSettings['business.name'] || 'Envoy Electric',
    'business.email': props.businessSettings['business.email'] || 'hello@envoyelectric.com',
    'business.phone': props.businessSettings['business.phone'] || '+234 809 708 9259',
    'business.address': props.businessSettings['business.address'] || 'Shop 1, Peace Avenue Junction, opp Goddy Royal Hotel, Futa Southgate Road, Akure',
    'business.footer': props.businessSettings['business.footer'] || 'powering your world with innovative solutions',
    'currency.symbol': props.businessSettings['currency.symbol'] || '₦',
});

const bankForm = useForm({
    'bank.bank_name': props.bankSettings['bank.bank_name'] || 'Wema Bank',
    'bank.account_name': props.bankSettings['bank.account_name'] || 'Envoy Electricals',
    'bank.account_number': props.bankSettings['bank.account_number'] || '0126278482',
    'bank.instructions': props.bankSettings['bank.instructions'] || 'Transfer the exact order amount and quote your order reference (ORD-...) as the narration. Our team verifies bank transfers before confirming your order.',
});

function saveBusinessSettings() {
    businessForm.post('/admin/settings/business', {
        preserveScroll: true,
    });
}

function saveBankSettings() {
    bankForm.post('/admin/settings/bank', {
        preserveScroll: true,
    });
}

const inverterSizesArray = computed(() => 
    calcForm.inverter_sizes
        .split(',')
        .map(s => parseFloat(s.trim()))
        .filter(n => Number.isFinite(n) && n > 0)
);

const batteryCapacitiesArray = computed(() => {
    const caps = calcForm.battery_capacities || '1.2, 2.56, 5.12, 10.24, 16, 20, 25.6, 32';
    return caps
        .split(',')
        .map(s => parseFloat(s.trim()))
        .filter(n => Number.isFinite(n) && n > 0);
});

function addCategory() {
    if (!form.name.trim()) return;
    form.post('/admin/settings/categories', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name');
        },
    });
}

function confirmDelete(category) {
    const linked = category.product_count > 0
        ? ` It has ${category.product_count} product${category.product_count === 1 ? '' : 's'} linked — they will be left uncategorised.`
        : '';
    if (window.confirm(`Delete category "${category.name}"?${linked} This can't be undone.`)) {
        router.delete(`/admin/settings/categories/${category.id}`, { preserveScroll: true });
    }
}

function saveCalculatorSettings() {
    calcForm.transform((data) => ({
        ...data,
        inverter_prices: JSON.parse(JSON.stringify(calcForm.inverter_prices)),
        battery_prices: JSON.parse(JSON.stringify(calcForm.battery_prices)),
        installation_costs: JSON.parse(JSON.stringify(calcForm.installation_costs)),
    })).post('/admin/settings/calculator', {
        preserveScroll: true,
        onSuccess: () => {
            // Settings will be refreshed via cache
        },
    });
}

function naira(v) {
    return '₦' + Number(v || 0).toLocaleString('en-NG');
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="Settings" subtitle="Manage system settings and product categories." />

    <div class="space-y-6">
        <!-- Calculator Settings -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Calculator System Parameters</h2>
                <span class="rounded-full bg-[#40e0d0]/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#40e0d0]">
                    Admin Only
                </span>
            </div>
            <p class="mb-4 text-sm text-slate-500">These values are used as defaults in the public load calculator. Only administrators can modify them.</p>

            <form @submit.prevent="saveCalculatorSettings" class="space-y-6" novalidate>
                <!-- Basic Parameters -->
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700">Basic Parameters</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Solar Panel Wattage (W)</label>
                            <input
                                v-model.number="calcForm.panel_wattage"
                                type="number"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.panel_wattage" class="mt-1 text-xs text-red-600">{{ calcForm.errors.panel_wattage }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Panel Efficiency Factor</label>
                            <input
                                v-model.number="calcForm.panel_efficiency"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max="1"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.panel_efficiency" class="mt-1 text-xs text-red-600">{{ calcForm.errors.panel_efficiency }}</p>
                            <p class="mt-1.5 text-[11px] text-slate-400">Decimal between 0 and 1, e.g. 0.8 for 80%.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Peak Sun Hours (hrs/day)</label>
                            <input
                                v-model.number="calcForm.peak_sun_hours"
                                type="number"
                                step="0.1"
                                min="0.1"
                                max="24"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.peak_sun_hours" class="mt-1 text-xs text-red-600">{{ calcForm.errors.peak_sun_hours }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Battery Depth of Discharge</label>
                            <input
                                v-model.number="calcForm.battery_depth_of_discharge"
                                type="number"
                                step="0.05"
                                min="0.1"
                                max="1"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.battery_depth_of_discharge" class="mt-1 text-xs text-red-600">{{ calcForm.errors.battery_depth_of_discharge }}</p>
                            <p class="mt-1.5 text-[11px] text-slate-400">Decimal between 0.1 and 1, e.g. 0.8 = 80% usable capacity.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Inverter Safety Factor</label>
                            <input
                                v-model.number="calcForm.inverter_safety_factor"
                                type="number"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.inverter_safety_factor" class="mt-1 text-xs text-red-600">{{ calcForm.errors.inverter_safety_factor }}</p>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Available Battery Capacities (kWh)</label>
                            <input
                                v-model="calcForm.battery_capacities"
                                type="text"
                                placeholder="e.g. 1.2, 2.56, 5.12, 10.24, 16, 20, 25.6, 32"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.battery_capacities" class="mt-1 text-xs text-red-600">{{ calcForm.errors.battery_capacities }}</p>
                            <p class="mt-1.5 text-[11px] text-slate-400">Comma-separated list. Calculator uses these for battery quantity calculations.</p>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Available Inverter Sizes (kW)</label>
                            <input
                                v-model="calcForm.inverter_sizes"
                                type="text"
                                placeholder="e.g. 1.5, 3, 5, 8, 10, 12"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.inverter_sizes" class="mt-1 text-xs text-red-600">{{ calcForm.errors.inverter_sizes }}</p>
                            <p class="mt-1.5 text-[11px] text-slate-400">Comma-separated list. The nearest size ≥ required is recommended automatically.</p>
                        </div>
                    </div>
                </div>

                <!-- Panel Pricing -->
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700">Solar Panel Pricing</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Price Per Panel (₦)</label>
                            <input
                                v-model.number="calcForm.price_per_panel"
                                type="number"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.price_per_panel" class="mt-1 text-xs text-red-600">{{ calcForm.errors.price_per_panel }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Price Per kWh Daily (₦)</label>
                            <input
                                v-model.number="calcForm.price_per_kwh_daily"
                                type="number"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="calcForm.errors.price_per_kwh_daily" class="mt-1 text-xs text-red-600">{{ calcForm.errors.price_per_kwh_daily }}</p>
                        </div>
                    </div>
                </div>

                <!-- Inverter Pricing (per size) -->
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700">Inverter Pricing (per size)</h3>
                    <p class="mb-3 text-sm text-slate-500">Enter price for each inverter size. These will be multiplied by quantity if multiple inverters are needed.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="size in inverterSizesArray" :key="size" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-white">
                            <span class="shrink-0 w-20 font-medium text-slate-700">{{ size }}kW</span>
                            <input
                                v-model.number="calcForm.inverter_prices[String(size)]"
                                type="number"
                                placeholder="₦"
                                class="flex-1 rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                        </div>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-400">Prices are stored per inverter size (kW). Add new sizes to the list above to configure their prices.</p>
                </div>

                <!-- Battery Pricing (per capacity) -->
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700">Battery Pricing (per capacity)</h3>
                    <p class="mb-3 text-sm text-slate-500">Enter price for each battery capacity. These will be multiplied by quantity if multiple batteries are needed.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div v-for="cap in batteryCapacitiesArray" :key="cap" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-white">
                            <span class="shrink-0 w-24 font-medium text-slate-700">{{ cap }}kWh</span>
                            <input
                                v-model.number="calcForm.battery_prices[String(cap)]"
                                type="number"
                                placeholder="₦"
                                class="flex-1 rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                        </div>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-400">Configure available battery capacities in the Basic Parameters section above to add/remove options here.</p>
                </div>

                <!-- Installation/Materials/Logistics Costs (per inverter size) -->
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700">Installation + Materials + Standard Logistics (per inverter size)</h3>
                    <p class="mb-3 text-sm text-slate-500">Enter cost for each inverter size. This will be multiplied by the number of inverters required.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="size in inverterSizesArray" :key="'install-' + size" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-white">
                            <span class="shrink-0 w-20 font-medium text-slate-700">{{ size }}kW</span>
                            <input
                                v-model.number="calcForm.installation_costs[String(size)]"
                                type="number"
                                placeholder="₦"
                                class="flex-1 rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                    :disabled="calcForm.processing"
                >
                    <i :class="calcForm.processing ? 'bi bi-hourglass-split animate-spin' : 'bi bi-save'"></i>
                    {{ calcForm.processing ? 'Saving…' : 'Save Calculator Settings' }}
                </button>
            </form>
        </div>

        <!-- Business Settings -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Business Information</h2>
                <span class="rounded-full bg-[#40e0d0]/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#40e0d0]">
                    Admin Only
                </span>
            </div>
            <p class="mb-4 text-sm text-slate-500">These values appear on the public website (contact page, footer, etc.) and in emails/documents.</p>

            <form @submit.prevent="saveBusinessSettings" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Business Name *</label>
                        <input
                            v-model="businessForm['business.name']"
                            type="text"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p v-if="businessForm.errors['business.name']" class="mt-1 text-xs text-red-600">{{ businessForm.errors['business.name'] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Email *</label>
                        <input
                            v-model="businessForm['business.email']"
                            type="email"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p v-if="businessForm.errors['business.email']" class="mt-1 text-xs text-red-600">{{ businessForm.errors['business.email'] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone *</label>
                        <input
                            v-model="businessForm['business.phone']"
                            type="tel"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p v-if="businessForm.errors['business.phone']" class="mt-1 text-xs text-red-600">{{ businessForm.errors['business.phone'] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Currency Symbol *</label>
                        <input
                            v-model="businessForm['currency.symbol']"
                            type="text"
                            required
                            maxlength="10"
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p v-if="businessForm.errors['currency.symbol']" class="mt-1 text-xs text-red-600">{{ businessForm.errors['currency.symbol'] }}</p>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Address *</label>
                    <textarea
                        v-model="businessForm['business.address']"
                        rows="3"
                        required
                        class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20 resize-y"
                        placeholder="Full address for contact page and Google Maps"
                    ></textarea>
                    <p v-if="businessForm.errors['business.address']" class="mt-1 text-xs text-red-600">{{ businessForm.errors['business.address'] }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Footer Tagline</label>
                    <input
                        v-model="businessForm['business.footer']"
                        type="text"
                        class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        placeholder="Short tagline for website footer"
                    />
                    <p v-if="businessForm.errors['business.footer']" class="mt-1 text-xs text-red-600">{{ businessForm.errors['business.footer'] }}</p>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                    :disabled="businessForm.processing"
                >
                    <i :class="businessForm.processing ? 'bi bi-hourglass-split animate-spin' : 'bi bi-save'"></i>
                    {{ businessForm.processing ? 'Saving…' : 'Save Business Settings' }}
                </button>
            </form>
        </div>

        <!-- Bank Settings -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Bank Details</h2>
                <span class="rounded-full bg-[#40e0d0]/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#40e0d0]">
                    Admin Only
                </span>
            </div>
            <p class="mb-4 text-sm text-slate-500">Bank account details shown on checkout for bank transfer payments.</p>

            <form @submit.prevent="saveBankSettings" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Bank Name *</label>
                        <input
                            v-model="bankForm['bank.bank_name']"
                            type="text"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p v-if="bankForm.errors['bank.bank_name']" class="mt-1 text-xs text-red-600">{{ bankForm.errors['bank.bank_name'] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Account Name *</label>
                        <input
                            v-model="bankForm['bank.account_name']"
                            type="text"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p v-if="bankForm.errors['bank.account_name']" class="mt-1 text-xs text-red-600">{{ bankForm.errors['bank.account_name'] }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Account Number *</label>
                        <input
                            v-model="bankForm['bank.account_number']"
                            type="text"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p v-if="bankForm.errors['bank.account_number']" class="mt-1 text-xs text-red-600">{{ bankForm.errors['bank.account_number'] }}</p>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Transfer Instructions</label>
                    <textarea
                        v-model="bankForm['bank.instructions']"
                        rows="3"
                        class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20 resize-y"
                        placeholder="Instructions shown to customers during bank transfer checkout"
                    ></textarea>
                    <p v-if="bankForm.errors['bank.instructions']" class="mt-1 text-xs text-red-600">{{ bankForm.errors['bank.instructions'] }}</p>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                    :disabled="bankForm.processing"
                >
                    <i :class="bankForm.processing ? 'bi bi-hourglass-split animate-spin' : 'bi bi-save'"></i>
                    {{ bankForm.processing ? 'Saving…' : 'Save Bank Settings' }}
                </button>
            </form>
        </div>

        <!-- Product Categories -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Categories list -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Product Categories</h2>
                    <span class="rounded-full bg-[#0D1527]/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#0D1527]">
                        {{ categories.length }} categor{{ categories.length === 1 ? 'y' : 'ies' }}
                    </span>
                </div>

                <div v-if="!categories.length" class="rounded-xl border border-dashed border-slate-200 py-10 text-center text-sm text-slate-400">
                    No categories yet. Add your first one below.
                </div>

                <div v-else class="space-y-2">
                    <div v-for="c in categories" :key="c.id" class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#0D1527] text-white">
                            <i class="bi bi-folder2-open text-sm"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ c.name }}</p>
                            <p class="text-[11px] text-slate-400">{{ c.slug }}</p>
                        </div>
                        <span
                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                            :class="c.product_count > 0 ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-500'"
                        >
                            {{ c.product_count }} product{{ c.product_count === 1 ? '' : 's' }}
                        </span>
                        <button
                            type="button"
                            title="Delete category"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                            @click="confirmDelete(c)"
                        >
                            <i class="bi bi-trash3 text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Add category -->
            <div class="space-y-5">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Add Category</h2>
                    <form @submit.prevent="addCategory">
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" placeholder="e.g. Micro Inverters" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                                <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                            </div>
                            <button
                                type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#0D1527] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:opacity-50"
                                :disabled="form.processing || !form.name.trim()"
                            >
                                <i class="bi bi-plus-lg text-sm"></i>
                                {{ form.processing ? 'Adding…' : 'Add Category' }}
                            </button>
                        </div>
                    </form>
                    <p class="mt-3 text-xs text-slate-400">Categories are used on the product form and the online shop filters.</p>
                </div>
            </div>
        </div>
    </div>
</template>