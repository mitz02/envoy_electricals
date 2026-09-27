<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    packages: { type: Array, default: () => [] },
});

const form = useForm({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    location: '',
    solar_package_id: '',
    estimated_price: '',
    notes: '',
    additional_logistics: 0,
});

const selectedPackage = computed(() => {
    if (!form.solar_package_id) return null;
    return props.packages.find(p => p.id == form.solar_package_id);
});

const totalWithLogistics = computed(() => {
    const base = Number(form.estimated_price) || (selectedPackage.value ? (Number(selectedPackage.value.package_price) + Number(selectedPackage.value.installation_cost || 0)) : 0);
    return base + Number(form.additional_logistics || 0);
});

function submit() {
    form.post('/admin/solar-leads/quotations', {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect handled by backend
        },
    });
}

function updateEstimatedPrice() {
    if (selectedPackage.value) {
        form.estimated_price = Number(selectedPackage.value.package_price) + Number(selectedPackage.value.installation_cost || 0);
    }
}
</script>

<template>
    <FlashMessages />
    <PageHeader title="Create Manual Quotation" subtitle="Generate a professional quotation for a customer without a calculator lead." />

    <div class="max-w-3xl mx-auto space-y-6">
        <form id="quotation-form" @submit.prevent="submit" class="space-y-6">
            <!-- Customer Information -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Customer Information</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Full Name *</label>
                            <input
                                v-model="form.customer_name"
                                type="text"
                                required
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="form.errors.customer_name" class="mt-1 text-xs text-red-600">{{ form.errors.customer_name }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Phone Number *</label>
                            <input
                                v-model="form.customer_phone"
                                type="tel"
                                required
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="form.errors.customer_phone" class="mt-1 text-xs text-red-600">{{ form.errors.customer_phone }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Email</label>
                            <input
                                v-model="form.customer_email"
                                type="email"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                            <p v-if="form.errors.customer_email" class="mt-1 text-xs text-red-600">{{ form.errors.customer_email }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Location</label>
                            <input
                                v-model="form.location"
                                type="text"
                                placeholder="e.g. Lekki Phase 1, Lagos"
                                class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Package Selection -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Solar Package Selection</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Select Package *</label>
                        <select
                            v-model="form.solar_package_id"
                            @change="updateEstimatedPrice"
                            required
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        >
                            <option value="">Select a solar package</option>
                            <option
                                v-for="pkg in packages"
                                :key="pkg.id"
                                :value="pkg.id"
                            >
                                {{ pkg.name }} — {{ naira(pkg.package_price) }} (Installation: {{ naira(pkg.installation_cost) }})
                            </option>
                        </select>
                        <p v-if="form.errors.solar_package_id" class="mt-1 text-xs text-red-600">{{ form.errors.solar_package_id }}</p>
                    </div>

                    <div v-if="selectedPackage" class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-700">{{ selectedPackage.name }}</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">Inverter Capacity</span><span class="font-medium text-slate-900">{{ selectedPackage.inverter_capacity || '—' }}kW</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Load Capacity</span><span class="font-medium text-slate-900">{{ selectedPackage.estimated_load_capacity || '—' }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Package Price</span><span class="font-bold text-emerald-700">{{ naira(selectedPackage.package_price) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Installation Cost</span><span class="font-bold text-slate-900">{{ naira(selectedPackage.installation_cost) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Pricing</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Estimated Price (₦)</label>
                        <input
                            v-model="form.estimated_price"
                            type="number"
                            min="0"
                            step="1000"
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p class="mt-1.5 text-[11px] text-slate-400">Auto-filled from package selection. Adjust if needed.</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Additional Logistics (₦)</label>
                        <input
                            v-model.number="form.additional_logistics"
                            type="number"
                            min="0"
                            step="1000"
                            class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        />
                        <p class="mt-1.5 text-[11px] text-slate-400">For locations outside standard service area (e.g., outside Akure). Hidden from public calculator.</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-emerald-50 p-4">
                        <div class="flex justify-between text-lg">
                            <span class="font-semibold text-emerald-800">Total with Logistics</span>
                            <span class="font-bold text-emerald-700">{{ naira(totalWithLogistics) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Terms & Notes</h2>
                <textarea
                    v-model="form.notes"
                    rows="4"
                    placeholder="Enter terms, warranty details, validity period, etc."
                    class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                />
                <p class="mt-2 text-xs text-slate-400">These notes will appear on the quotation document sent to the customer.</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link href="/admin/solar-leads" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </Link>
                <button
                    type="submit"
                    form="quotation-form"
                    :disabled="form.processing"
                    class="rounded-lg bg-[#0D1527] px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating…' : 'Create Quotation' }}
                </button>
            </div>
        </form>
    </div>
</template>