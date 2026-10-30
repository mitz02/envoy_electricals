<script setup>
import { ref, computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ReceiptVoucher from '@/Components/ReceiptVoucher.vue';
import Dropdown from '@/Components/Dropdown.vue';
import EnvoySignatureModal from '@/Components/EnvoySignatureModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    receipts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    defaultEnvoySignature: { type: String, default: '' },
});

const page = usePage();
const flashReceipt = computed(() => page.props.flash?.created_receipt);

// Envoy Signature Modal
const envoySigModalOpen = ref(false);
const currentEnvoySig = ref(props.defaultEnvoySignature || '');

function openEnvoySigModal() {
    envoySigModalOpen.value = true;
}

function onEnvoySigSaved(sig) {
    currentEnvoySig.value = sig;
}

// Filter state
const search = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const paymentMethod = ref(props.filters.payment_method || '');

// Preview Modal state
const previewReceipt = ref(null);
const showPreviewModal = ref(false);

// Email sending modal state
const emailModalOpen = ref(false);
const emailReceipt = ref(null);
const emailInput = ref('');
const isSendingEmail = ref(false);

// WhatsApp selector modal state
const whatsappModalOpen = ref(false);
const whatsappReceipt = ref(null);

// Signing link modal state
const signingModalOpen = ref(false);
const signingReceipt = ref(null);
const signingLinkCopied = ref(false);

function applyFilters() {
    router.get('/admin/receipts', {
        search: search.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        payment_method: paymentMethod.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    paymentMethod.value = '';
    router.get('/admin/receipts');
}

function openPreview(receipt) {
    previewReceipt.value = receipt;
    showPreviewModal.value = true;
}

function closePreview() {
    showPreviewModal.value = false;
    previewReceipt.value = null;
}

function openEmailModal(receipt) {
    emailReceipt.value = receipt;
    emailInput.value = receipt.customer_email || '';
    emailModalOpen.value = true;
}

function sendEmail() {
    if (!emailInput.value) return;
    isSendingEmail.value = true;
    router.post(`/admin/receipts/${emailReceipt.value.id}/send-email`, {
        email: emailInput.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSendingEmail.value = false;
            emailModalOpen.value = false;
        },
    });
}

function openWhatsAppModal(receipt) {
    whatsappReceipt.value = receipt;
    whatsappModalOpen.value = true;
}

function getWhatsAppUrl(receipt, directToPhone = false) {
    const amount = Number(receipt.amount || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 });
    const date = new Date(receipt.receipt_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const publicUrl = window.location.origin + '/receipt/' + receipt.token;

    const message = `Dear ${receipt.customer_name},\n\n`
        + `Thank you for your payment to *Envoy Electricals*.\n\n`
        + `🧾 *OFFICIAL CASH RECEIPT*\n`
        + `━━━━━━━━━━━━━━━━━━━━\n`
        + `• *Receipt No:* ${receipt.ref_id}\n`
        + `• *Date:* ${date}\n`
        + `• *Amount:* ₦${amount}\n`
        + `• *In Words:* ${receipt.amount_in_words}\n`
        + `• *Payment For:* ${receipt.payment_for}\n`
        + `• *Method:* ${receipt.payment_method}\n`
        + `━━━━━━━━━━━━━━━━━━━━\n\n`
        + `🔗 *View / Download Your Official E-Receipt:*\n`
        + `${publicUrl}\n\n`
        + `*Envoy Electricals*\n`
        + `_Powering your World with Innovative Solutions_\n`
        + `📞 0809 708 9259, 0706 057 2282`;

    const encoded = encodeURIComponent(message);

    if (directToPhone && receipt.customer_phone) {
        let cleanPhone = receipt.customer_phone.replace(/\D/g, '');
        if (cleanPhone.startsWith('0')) cleanPhone = '234' + cleanPhone.slice(1);
        return `https://wa.me/${cleanPhone}?text=${encoded}`;
    }

    // Opens WhatsApp contact chooser (user can select ANY contact or chat)
    return `https://api.whatsapp.com/send?text=${encoded}`;
}

function launchWhatsApp(receipt, directToPhone = false) {
    // Record WhatsApp share in background
    router.post(`/admin/receipts/${receipt.id}/record-whatsapp`, {}, { preserveScroll: true, preserveState: true });
    
    const url = getWhatsAppUrl(receipt, directToPhone);
    window.open(url, '_blank');
    whatsappModalOpen.value = false;
}

function printReceipt() {
    window.print();
}

function deleteReceipt(receipt) {
    if (confirm(`Are you sure you want to delete receipt ${receipt.ref_id}?`)) {
        router.delete(`/admin/receipts/${receipt.id}`);
    }
}

const copiedReceiptId = ref(null);

function copyReceiptLink(receipt) {
    const url = receipt.signed_at
        ? `${window.location.origin}/receipt/${receipt.token}`
        : `${window.location.origin}/receipt/${receipt.token}/sign`;
    navigator.clipboard.writeText(url).then(() => {
        copiedReceiptId.value = receipt.id;
        setTimeout(() => { copiedReceiptId.value = null; }, 2000);
    });
}

function getSigningUrl(receipt) {
    return window.location.origin + '/receipt/' + receipt.token + '/sign';
}

function openSigningModal(receipt) {
    signingReceipt.value = receipt;
    signingLinkCopied.value = false;
    signingModalOpen.value = true;
}

function copySigningLink(receipt) {
    const url = getSigningUrl(receipt);
    navigator.clipboard.writeText(url).then(() => {
        signingLinkCopied.value = true;
        setTimeout(() => { signingLinkCopied.value = false; }, 2500);
    });
}

function shareSigningViaWhatsApp(receipt) {
    const url = getSigningUrl(receipt);
    const msg = `Dear ${receipt.customer_name}, please tap the link below to sign your official cash receipt from Envoy Electricals:\n\n✍️ ${url}`;
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`, '_blank');
    // Optionally log to backend
    router.post(`/admin/receipts/${receipt.id}/signing-link`, {}, { preserveScroll: true, preserveState: true });
}

function formatCurrency(val) {
    return '₦' + Number(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 });
}

function formatDate(val) {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-6">
        <FlashMessages />

        <!-- Success notification for newly created receipt with immediate share actions -->
        <div v-if="flashReceipt" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-emerald-900">Receipt {{ flashReceipt.ref_id }} Created!</h4>
                    <p class="text-xs text-emerald-700">For {{ flashReceipt.customer_name }}. Ready to send to customer via WhatsApp or Email.</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a 
                    v-if="!flashReceipt.has_signature && flashReceipt.whatsapp_signing_text"
                    :href="`https://api.whatsapp.com/send?text=${encodeURIComponent(flashReceipt.whatsapp_signing_text)}`" 
                    target="_blank"
                    class="px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 shadow-sm transition"
                    title="Send link for customer to sign"
                >
                    <i class="bi bi-pen-fill text-sm"></i>
                    Send Signing Link (WhatsApp)
                </a>
                <a 
                    :href="`https://api.whatsapp.com/send?text=${encodeURIComponent(flashReceipt.whatsapp_text)}`" 
                    target="_blank"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 shadow-sm transition"
                >
                    <i class="bi bi-whatsapp text-sm"></i>
                    Send Receipt (WhatsApp)
                </a>
                <a 
                    :href="`/admin/receipts/${flashReceipt.id}/pdf`" 
                    class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
                >
                    <i class="bi bi-file-earmark-pdf text-red-500"></i>
                    Download PDF
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="bi bi-receipt-cutoff text-sky-600"></i>
                    Cash Receipts
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Generate, print, and send branded e-receipts to customers via WhatsApp or Email.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="openEnvoySigModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl transition"
                    title="Create or edit default company signature"
                >
                    <i class="bi bi-pen-fill text-sky-600"></i>
                    <span>Envoy Signature</span>
                </button>

                <Link
                    href="/admin/receipts/create"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-xl shadow-sm transition"
                >
                    <i class="bi bi-plus-lg"></i>
                    Issue New Receipt
                </Link>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Receipts</span>
                    <span class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-sm">
                        <i class="bi bi-receipt"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-slate-900 mt-2">{{ stats.total_count ?? 0 }}</div>
                <p class="text-xs text-slate-400 mt-1">All time issued</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Revenue</span>
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                        <i class="bi bi-cash-coin"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-emerald-700 mt-2">{{ formatCurrency(stats.total_amount) }}</div>
                <p class="text-xs text-slate-400 mt-1">Receipted revenue</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">This Month</span>
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                        <i class="bi bi-calendar-check"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-blue-700 mt-2">{{ formatCurrency(stats.this_month_amount) }}</div>
                <p class="text-xs text-slate-400 mt-1">Current month receipts</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today</span>
                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                        <i class="bi bi-clock-history"></i>
                    </span>
                </div>
                <div class="text-2xl font-bold text-amber-700 mt-2">{{ formatCurrency(stats.today_amount) }}</div>
                <p class="text-xs text-slate-400 mt-1">Received today</p>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <div class="relative">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search receipt no, customer, description..."
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white"
                    />
                </div>
            </div>

            <div class="w-36">
                <input
                    v-model="dateFrom"
                    @change="applyFilters"
                    type="date"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                    title="From Date"
                />
            </div>

            <div class="w-36">
                <input
                    v-model="dateTo"
                    @change="applyFilters"
                    type="date"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                    title="To Date"
                />
            </div>

            <div class="w-40">
                <select
                    v-model="paymentMethod"
                    @change="applyFilters"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                >
                    <option value="">All Payment Methods</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="POS">POS</option>
                    <option value="Online">Online</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>

            <button
                @click="applyFilters"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-xl transition"
            >
                Filter
            </button>

            <button
                v-if="search || dateFrom || dateTo || paymentMethod"
                @click="clearFilters"
                class="px-3 py-2 text-sm text-slate-500 hover:text-slate-800 hover:underline"
            >
                Clear
            </button>
        </div>

        <!-- Receipts Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Receipt No</th>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Payment For</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">Method</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr v-if="!receipts.data || receipts.data.length === 0">
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-xl mb-3 text-slate-400">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <p class="font-medium text-slate-600">No receipts found</p>
                                <p class="text-xs text-slate-400 mt-1">Issue a new cash receipt to get started.</p>
                                <Link
                                    href="/admin/receipts/create"
                                    class="inline-flex items-center gap-1.5 mt-3 px-4 py-2 bg-sky-600 text-white text-xs font-semibold rounded-lg hover:bg-sky-700 transition"
                                >
                                    <i class="bi bi-plus-lg"></i>
                                    Create First Receipt
                                </Link>
                            </td>
                        </tr>

                        <tr
                            v-for="item in receipts.data"
                            :key="item.id"
                            class="hover:bg-slate-50/80 transition"
                        >
                            <!-- Receipt No -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <button
                                    @click="openPreview(item)"
                                    class="font-mono font-bold text-sky-700 hover:text-sky-900 hover:underline flex items-center gap-1"
                                >
                                    <i class="bi bi-file-earmark-text text-slate-400"></i>
                                    {{ item.ref_id }}
                                </button>
                            </td>

                            <!-- Date -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 text-xs">
                                {{ formatDate(item.receipt_date) }}
                            </td>

                            <!-- Customer -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ item.customer_name }}</div>
                                <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                                    <span v-if="item.customer_phone"><i class="bi bi-telephone"></i> {{ item.customer_phone }}</span>
                                    <span v-if="item.customer_email"><i class="bi bi-envelope"></i> {{ item.customer_email }}</span>
                                </div>
                            </td>

                            <!-- Payment For -->
                            <td class="py-3.5 px-4 max-w-xs truncate text-slate-600" :title="item.payment_for">
                                {{ item.payment_for }}
                            </td>

                            <!-- Amount -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-bold text-slate-900">
                                {{ formatCurrency(item.amount) }}
                            </td>

                            <!-- Method -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ item.payment_method }}
                                </span>
                            </td>

                            <!-- Actions: Download (PDF/Image), Copy, Send via Email, Edit, Delete -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- 1. Download (PDF or Image) -->
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                class="p-1.5 text-slate-500 hover:text-sky-600 rounded-lg hover:bg-slate-100 transition flex items-center gap-0.5"
                                                title="Download (PDF or Image)"
                                            >
                                                <i class="bi bi-download text-sm"></i>
                                                <i class="bi bi-chevron-down text-[8px] text-slate-400"></i>
                                            </button>
                                        </template>
                                        <template #content>
                                            <div class="py-1">
                                                <a
                                                    :href="`/admin/receipts/${item.id}/pdf`"
                                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition"
                                                >
                                                    <i class="bi bi-file-earmark-pdf text-red-500 text-sm"></i>
                                                    <span>Download PDF</span>
                                                </a>
                                                <a
                                                    :href="`/admin/receipts/${item.id}/image`"
                                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition"
                                                >
                                                    <i class="bi bi-image text-sky-500 text-sm"></i>
                                                    <span>Download Image</span>
                                                </a>
                                            </div>
                                        </template>
                                    </Dropdown>

                                    <!-- 2. Copy Link -->
                                    <button
                                        type="button"
                                        @click="copyReceiptLink(item)"
                                        class="p-1.5 rounded-lg transition"
                                        :class="copiedReceiptId === item.id ? 'bg-emerald-100 text-emerald-700' : 'text-slate-500 hover:text-violet-600 hover:bg-slate-100'"
                                        :title="copiedReceiptId === item.id ? 'Link copied!' : 'Copy Link'"
                                    >
                                        <i :class="copiedReceiptId === item.id ? 'bi bi-check2 text-emerald-600' : 'bi bi-copy text-sm'"></i>
                                    </button>

                                    <!-- 3. Send via Email -->
                                    <button
                                        type="button"
                                        @click="openEmailModal(item)"
                                        class="p-1.5 text-slate-500 hover:text-sky-600 rounded-lg hover:bg-slate-100 transition"
                                        title="Send via Email"
                                    >
                                        <i class="bi bi-envelope text-sm"></i>
                                    </button>

                                    <!-- 4. Edit -->
                                    <Link
                                        :href="`/admin/receipts/${item.id}/edit`"
                                        class="p-1.5 text-slate-500 hover:text-amber-600 rounded-lg hover:bg-slate-100 transition"
                                        title="Edit Receipt"
                                    >
                                        <i class="bi bi-pencil text-sm"></i>
                                    </Link>

                                    <!-- 5. Delete -->
                                    <button
                                        type="button"
                                        @click="deleteReceipt(item)"
                                        class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-slate-100 transition"
                                        title="Delete Receipt"
                                    >
                                        <i class="bi bi-trash text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="receipts.links && receipts.links.length > 3"
                class="px-4 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500"
            >
                <div>
                    Showing {{ receipts.from || 0 }} to {{ receipts.to || 0 }} of {{ receipts.total }} receipts
                </div>
                <div class="flex gap-1">
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, i) in receipts.links"
                        :key="i"
                        :href="link.url"
                        v-html="link.label"
                        class="px-3 py-1.5 rounded-lg border transition"
                        :class="[
                            link.active ? 'bg-sky-600 text-white border-sky-600 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50',
                            !link.url ? 'opacity-40 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- ====================================================================== -->
        <!-- MODAL: EXACT RECEIPT PREVIEW                                           -->
        <!-- ====================================================================== -->
        <div
            v-if="showPreviewModal && previewReceipt"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs overflow-y-auto"
            @click.self="closePreview"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full p-6 space-y-5 animate-in fade-in duration-150">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-receipt-cutoff text-sky-600"></i>
                            Cash Receipt Voucher &mdash; {{ previewReceipt.ref_id }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Customer: <strong class="text-slate-700">{{ previewReceipt.customer_name }}</strong> &bull;
                            Amount: <strong class="text-slate-700">{{ formatCurrency(previewReceipt.amount) }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Print -->
                        <button
                            @click="printReceipt"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
                        >
                            <i class="bi bi-printer"></i>
                            Print
                        </button>

                        <!-- Download PDF -->
                        <a
                            :href="`/admin/receipts/${previewReceipt.id}/pdf`"
                            class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
                        >
                            <i class="bi bi-file-earmark-pdf"></i>
                            PDF
                        </a>

                        <!-- Download PNG -->
                        <a
                            :href="`/admin/receipts/${previewReceipt.id}/image`"
                            class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition"
                        >
                            <i class="bi bi-image"></i>
                            Image
                        </a>

                        <!-- WhatsApp Share -->
                        <button
                            @click="openWhatsAppModal(previewReceipt)"
                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-xs"
                        >
                            <i class="bi bi-whatsapp"></i>
                            WhatsApp
                        </button>

                        <!-- Email Send -->
                        <button
                            @click="openEmailModal(previewReceipt)"
                            class="px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-xs"
                        >
                            <i class="bi bi-envelope"></i>
                            Email
                        </button>

                        <!-- Signing Link -->
                        <button
                            @click="openSigningModal(previewReceipt)"
                            class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-xs"
                        >
                            <i class="bi bi-pen-fill"></i>
                            Sign Link
                        </button>

                        <button
                            @click="closePreview"
                            class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg"
                        >
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Exact Receipt Voucher Display -->
                <div class="bg-slate-100/80 p-4 rounded-xl flex justify-center overflow-x-auto border border-slate-200">
                    <ReceiptVoucher 
                        :receipt="previewReceipt" 
                        :can-edit-envoy-sig="true"
                        @edit-envoy-signature="openEnvoySigModal"
                    />
                </div>

                <!-- Footer with public link -->
                <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                    <div class="flex items-center gap-2">
                        <span>Customer E-Receipt Link:</span>
                        <a
                            :href="`/receipt/${previewReceipt.token}`"
                            target="_blank"
                            class="text-sky-600 font-mono hover:underline flex items-center gap-1"
                        >
                            {{ `/receipt/${previewReceipt.token}` }}
                            <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                        </a>
                    </div>
                    <button
                        @click="closePreview"
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-lg font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- ====================================================================== -->
        <!-- MODAL: WHATSAPP SHARE CHOOSER                                          -->
        <!-- ====================================================================== -->
        <div
            v-if="whatsappModalOpen && whatsappReceipt"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="whatsappModalOpen = false"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-whatsapp text-emerald-600 text-xl"></i>
                        Send E-Receipt via WhatsApp
                    </h3>
                    <button @click="whatsappModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <p class="text-xs text-slate-500">
                    Choose how you want to send receipt <strong class="text-slate-700">{{ whatsappReceipt.ref_id }}</strong>:
                </p>

                <div class="space-y-3">
                    <!-- Option 1: Open WhatsApp to pick customer -->
                    <button
                        @click="launchWhatsApp(whatsappReceipt, false)"
                        class="w-full text-left p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/60 hover:bg-emerald-100/60 transition flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center text-lg">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-emerald-950">Choose Customer in WhatsApp</div>
                                <div class="text-xs text-emerald-700">Opens WhatsApp contact picker to select any customer or chat</div>
                            </div>
                        </div>
                        <i class="bi bi-arrow-right text-emerald-600 group-hover:translate-x-1 transition-transform"></i>
                    </button>

                    <!-- Option 2: Direct to customer's phone if available -->
                    <button
                        v-if="whatsappReceipt.customer_phone"
                        @click="launchWhatsApp(whatsappReceipt, true)"
                        class="w-full text-left p-3.5 rounded-xl border border-slate-200 hover:border-slate-300 bg-slate-50 hover:bg-slate-100 transition flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-lg">
                                <i class="bi bi-person-check-fill"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Direct to {{ whatsappReceipt.customer_name }}</div>
                                <div class="text-xs text-slate-500">{{ whatsappReceipt.customer_phone }}</div>
                            </div>
                        </div>
                        <i class="bi bi-arrow-right text-slate-600 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>

                <div class="pt-2 text-right">
                    <button
                        @click="whatsappModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- ====================================================================== -->
        <!-- MODAL: SIGNING LINK SHARE                                              -->
        <!-- ====================================================================== -->
        <div
            v-if="signingModalOpen && signingReceipt"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="signingModalOpen = false"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-pen-fill text-violet-600 text-xl"></i>
                        Share Signing Link
                    </h3>
                    <button @click="signingModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Signed badge -->
                <div v-if="signingReceipt.signed_at" class="flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800 font-medium">
                    <i class="bi bi-patch-check-fill text-emerald-600 text-lg"></i>
                    <span>Signed by <strong>{{ signingReceipt.customer_name }}</strong> on {{ formatDate(signingReceipt.signed_at) }}</span>
                </div>

                <p class="text-xs text-slate-500">
                    Share this secure link with <strong class="text-slate-700">{{ signingReceipt.customer_name }}</strong> to collect their electronic signature on receipt <strong>{{ signingReceipt.ref_id }}</strong>.
                </p>

                <!-- Link display box -->
                <div class="flex items-center gap-2 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <span class="flex-1 text-xs font-mono text-slate-700 break-all select-all">
                        {{ getSigningUrl(signingReceipt) }}
                    </span>
                    <button
                        @click="copySigningLink(signingReceipt)"
                        class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :class="signingLinkCopied ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 hover:bg-slate-300 text-slate-700'"
                    >
                        <i :class="signingLinkCopied ? 'bi bi-check2' : 'bi bi-clipboard'"></i>
                        {{ signingLinkCopied ? 'Copied!' : 'Copy' }}
                    </button>
                </div>

                <!-- Share options -->
                <div class="space-y-2">
                    <!-- Share via WhatsApp -->
                    <button
                        @click="shareSigningViaWhatsApp(signingReceipt)"
                        class="w-full text-left p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/60 hover:bg-emerald-100/60 transition flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center text-lg">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-emerald-950">Share via WhatsApp</div>
                                <div class="text-xs text-emerald-700">Opens WhatsApp to choose customer or send directly</div>
                            </div>
                        </div>
                        <i class="bi bi-arrow-right text-emerald-600 group-hover:translate-x-1 transition-transform"></i>
                    </button>

                    <!-- Open signing page in new tab -->
                    <a
                        :href="getSigningUrl(signingReceipt)"
                        target="_blank"
                        class="w-full text-left p-3.5 rounded-xl border border-slate-200 hover:border-slate-300 bg-slate-50 hover:bg-slate-100 transition flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-lg">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Preview Signing Page</div>
                                <div class="text-xs text-slate-500">Open signing page in a new tab to verify layout</div>
                            </div>
                        </div>
                        <i class="bi bi-arrow-right text-slate-600 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <div class="pt-1 text-right">
                    <button
                        @click="signingModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- ====================================================================== -->
        <!-- MODAL: EMAIL SENDER                                                    -->
        <!-- ====================================================================== -->
        <div
            v-if="emailModalOpen && emailReceipt"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="emailModalOpen = false"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-envelope-fill text-sky-600 text-xl"></i>
                        Send E-Receipt via Email
                    </h3>
                    <button @click="emailModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <p class="text-xs text-slate-500">
                    The official e-receipt and printable PDF will be sent to the customer's email address:
                </p>

                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Customer Email Address</label>
                    <input
                        v-model="emailInput"
                        type="email"
                        placeholder="customer@example.com"
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"
                    />
                </div>

                <div class="p-3 bg-sky-50 rounded-xl text-xs text-sky-800 space-y-1">
                    <div><strong>Receipt:</strong> {{ emailReceipt.ref_id }}</div>
                    <div><strong>Customer:</strong> {{ emailReceipt.customer_name }}</div>
                    <div><strong>Amount:</strong> {{ formatCurrency(emailReceipt.amount) }}</div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        @click="emailModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        @click="sendEmail"
                        :disabled="!emailInput || isSendingEmail"
                        class="px-4 py-2 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition"
                    >
                        <i v-if="isSendingEmail" class="bi bi-arrow-repeat animate-spin"></i>
                        <i v-else class="bi bi-send-fill"></i>
                        {{ isSendingEmail ? 'Sending...' : 'Send E-Receipt Now' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Envoy Signature Modal -->
        <EnvoySignatureModal
            v-model="envoySigModalOpen"
            :current-signature="currentEnvoySig"
            @saved="onEnvoySigSaved"
        />

    </div>
</template>
