<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { naira, formatDate, formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    quotation: { type: Object, required: true },
});

const page = usePage();

const { has } = useCan();

const canEdit = has('custom-quotations.edit');

const subtotal = computed(() => props.quotation.items.reduce((sum, item) => sum + Number(item.total), 0));

function printInvoice() {
    window.print();
}

function downloadPdf() {
    window.open(`/admin/custom-quotations/${props.quotation.id}/pdf`, '_blank');
}

function goBack() {
    router.get(`/admin/custom-quotations/${props.quotation.id}`, { preserveScroll: true });
}
</script>

<template>
    <div class="print-full grid gap-6 lg:grid-cols-3">
        <div class="print:hidden lg:col-span-3">
            <Link href="/admin/custom-quotations" class="text-sm text-slate-500 hover:text-slate-900">← Back to quotations</Link>
        </div>

        <!-- Quotation -->
        <div class="lg:col-span-2">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_24px_70px_-30px_rgba(13,21,39,0.35)] print:rounded-none print:border-0 print:shadow-none">
                <!-- Logo watermark background -->
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center print:opacity-100">
                    <img src="/envoy_images/logo.png" alt="" class="h-80 w-80 object-contain opacity-[0.05]" />
                </div>

                <!-- Company + Quotation header -->
                <div class="relative flex flex-wrap items-start justify-between gap-6 px-6 py-7 sm:px-9">
                    <div class="flex items-start gap-3">
                        <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-28 w-28 shrink-0 object-contain" />
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-black uppercase tracking-[0.2em] text-[#0D1527]">QUOTATION</p>
                        <p class="mt-1 text-xs text-slate-400">No. <span class="font-mono text-sm font-semibold text-slate-800">{{ quotation.ref_id }}</span></p>
                        <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold uppercase ring-1 ring-inset"
                              :class="quotation.status === 'accepted'
                                  ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                  : quotation.status === 'rejected'
                                      ? 'bg-red-50 text-red-700 ring-red-200'
                                      : 'bg-slate-100 text-slate-700 ring-slate-200'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="quotation.status === 'accepted' ? 'bg-emerald-500' : 'bg-slate-400'" />
                            {{ quotation.status }}
                        </span>
                    </div>
                </div>

                <!-- Quotation details strip -->
                <div class="relative mx-6 grid grid-cols-2 gap-y-3 border-y border-slate-200 py-3 sm:mx-9 sm:grid-cols-4 sm:gap-0">
                    <div class="sm:border-r sm:border-slate-200 sm:pr-4">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Quotation Date</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ formatDate(quotation.quotation_date) }}</p>
                    </div>
                    <div class="sm:border-r sm:border-slate-200 sm:px-4">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Valid Until</p>
                        <p class="mt-0.5 font-mono text-sm font-semibold text-slate-800">{{ quotation.valid_until ? formatDate(quotation.valid_until) : 'Not specified' }}</p>
                    </div>
                    <div class="sm:border-r sm:border-slate-200 sm:px-4">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Job Type</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ quotation.job_type }}</p>
                    </div>
                    <div class="sm:pl-4">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Grand Total</p>
                        <p class="mt-0.5 text-sm font-bold" :class="quotation.grand_total > 0 ? 'text-red-600' : 'text-emerald-600'">{{ naira(quotation.grand_total) }}</p>
                    </div>
                </div>

                <!-- Bill To -->
                <div class="relative mx-6 mt-6 sm:mx-9">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Bill To</p>
                    <div class="mt-1.5 max-w-sm rounded-lg border border-slate-200 bg-slate-50/60 px-4 py-3">
                        <p class="text-sm font-bold text-slate-900">{{ quotation.customer_name }}</p>
                        <p v-if="quotation.customer_company" class="mt-0.5 text-xs text-slate-600">{{ quotation.customer_company }}</p>
                        <p v-if="quotation.customer_phone" class="mt-0.5 text-xs text-slate-600">{{ quotation.customer_phone }}</p>
                        <p v-if="quotation.customer_email" class="text-xs text-slate-600">{{ quotation.customer_email }}</p>
                        <p v-if="quotation.customer_address" class="text-xs text-slate-600">{{ quotation.customer_address }}</p>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Job Type: <span class="font-semibold text-slate-700">{{ quotation.job_type }}</span></p>
                    <p class="mt-1 text-xs text-slate-500">Reference: <span class="font-semibold text-slate-700">{{ quotation.title }}</span></p>
                </div>

                <!-- Items table -->
                <div class="relative mx-6 mt-6 overflow-hidden rounded-lg border border-slate-200 sm:mx-9">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-100/70 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="px-3 py-2.5">#</th>
                                <th class="px-3 py-2.5">Item</th>
                                <th class="px-3 py-2.5 text-center">Qty</th>
                                <th class="px-3 py-2.5 text-right">Unit Price</th>
                                <th class="px-3 py-2.5 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in quotation.items" :key="item.id">
                                <td class="px-3 py-2.5 text-slate-400">{{ idx + 1 }}</td>
                                <td class="px-3 py-2.5 font-medium text-slate-800">{{ item.item }}</td>
                                <td class="px-3 py-2.5 text-center text-slate-600">{{ item.quantity }} {{ item.unit || 'pcs' }}</td>
                                <td class="px-3 py-2.5 text-right text-slate-700">{{ naira(item.unit_price) }}</td>
                                <td class="px-3 py-2.5 text-right font-semibold text-slate-900">{{ naira(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Notes + Totals -->
                <div class="relative mx-6 mt-6 grid gap-6 sm:mx-9 sm:grid-cols-[1fr_280px]">
                    <div class="space-y-4 text-xs text-slate-500">
                        <div v-if="quotation.description">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Description</p>
                            <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2.5 text-slate-600">{{ quotation.description }}</p>
                        </div>
                        <div v-if="quotation.notes" class="border-t border-slate-100 pt-4">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Notes</p>
                            <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2.5 text-slate-600">{{ quotation.notes }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <span class="text-sm font-semibold text-slate-600">Subtotal</span>
                            <span class="font-bold text-slate-900">{{ naira(subtotal) }}</span>
                        </div>
                        <div v-if="quotation.discount > 0" class="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4">
                            <span class="text-sm font-semibold text-red-700">Discount</span>
                            <span class="font-bold text-red-600">- {{ naira(quotation.discount) }}</span>
                        </div>
                        <div v-if="quotation.tax > 0" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <span class="text-sm font-semibold text-slate-600">Tax / VAT</span>
                            <span class="font-bold text-slate-900">+ {{ naira(quotation.tax) }}</span>
                        </div>
                        <div v-if="quotation.other_charges > 0" class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <span class="text-sm font-semibold text-amber-700">Other Charges</span>
                            <span class="font-bold text-amber-800">+ {{ naira(quotation.other_charges) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border-2 border-[#0D1527] bg-[#0D1527] p-4">
                            <span class="text-sm font-bold uppercase tracking-wide text-white">Grand Total</span>
                            <span class="text-xl font-black text-[#FACC15]">{{ naira(quotation.grand_total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="relative mx-6 mt-6 sm:mx-9 print:hidden">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400 mb-3">Payment Details</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bank</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ bankName }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Account Name</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ bankAccountName }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Account Number</p>
                            <p class="mt-1 font-mono font-semibold text-slate-900">{{ bankAccountNumber }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer for print -->
                <div class="print:mt-10 mt-8 text-center text-xs text-slate-400 px-6 pb-6 sm:px-9">
                    <p><strong>{{ businessName }}</strong> | Powering your world with innovative solutions</p>
                    <p>{{ businessEmail }} | {{ businessPhone }}</p>
                </div>
            </div>
        </div>

        <!-- Right Column: Actions -->
        <div class="space-y-4 print:hidden">
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
                    <button @click="downloadPdf" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <i class="bi bi-download"></i> Download PDF
                    </button>
                    <button v-if="quotation.status !== 'converted'" @click="convertToProject" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                        <i class="bi bi-arrow-right-circle"></i> Convert to Project
                    </button>
                    <button @click="goBack" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom bar for print -->
    <div class="print:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 px-4 py-3 sm:px-6">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <button @click="printInvoice" class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0D1527]/90">
                <i class="bi bi-printer text-sm"></i> Print / Save as PDF
            </button>
            <a :href="`/admin/custom-quotations/${quotation.id}/pdf`" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                <i class="bi bi-file-earmark-pdf text-sm"></i> Download PDF
            </a>
        </div>
    </div>
</template>

<script>
    import { computed } from 'vue';
    import { Link, router, usePage } from '@inertiajs/vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';
    import FlashMessages from '@/Components/FlashMessages.vue';
    import { naira, formatDate, badgeClass } from '@/lib/format';
    import { useCan } from '@/composables/permissions';

    defineOptions({ layout: AdminLayout });

    const props = defineProps({
        quotation: { type: Object, required: true },
    });

    const page = usePage();

    const { has } = useCan();

    const canEdit = has('custom-quotations.edit');

    const subtotal = computed(() => props.quotation.items.reduce((sum, item) => sum + Number(item.total), 0));

    const pageProps = usePage().props;

    const businessName = computed(() => pageProps.settings?.business?.name ?? 'Envoy Electricals');
    const businessEmail = computed(() => pageProps.settings?.business?.email ?? 'envoyelectricals@gmail.com');
    const businessPhone = computed(() => pageProps.settings?.business?.phone ?? '+234 809 708 9259');
    const businessAddress = computed(() => pageProps.settings?.business?.address ?? 'Shop 1, Peace Avenue Junction, Futa Southgate Rd, Akure');

    const bankName = 'Wema Bank';
    const bankAccountName = 'Envoy Electricals';
    const bankAccountNumber = '0126278482';

    function printInvoice() {
        window.print();
    }

    function downloadPdf() {
        window.open(`/admin/custom-quotations/${props.quotation.id}/pdf`, '_blank');
    }

    function goBack() {
        router.get(`/admin/custom-quotations/${props.quotation.id}`, { preserveScroll: true });
    }
</script>