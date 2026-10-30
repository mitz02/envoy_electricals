<script setup>
import { computed } from 'vue';

const props = defineProps({
    receipt: {
        type: Object,
        required: true,
    },
    // Optional scaling mode: 'normal', 'modal', or 'responsive'
    mode: {
        type: String,
        default: 'normal',
    },
    // Allow clicking on Envoy signature spot to edit
    canEditEnvoySig: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['edit-envoy-signature']);

const formattedDate = computed(() => {
    if (!props.receipt.receipt_date) return '';
    try {
        const d = new Date(props.receipt.receipt_date);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch (e) {
        return props.receipt.receipt_date;
    }
});

const nairaAmount = computed(() => {
    const val = parseFloat(props.receipt.amount || 0);
    return Math.floor(val).toLocaleString('en-NG');
});

const koboAmount = computed(() => {
    const val = parseFloat(props.receipt.amount || 0);
    const kobo = Math.round((val - Math.floor(val)) * 100);
    return String(kobo).padStart(2, '0');
});

// Break long sum of words into two lines if needed
const sumLines = computed(() => {
    const text = props.receipt.amount_in_words || '';
    if (text.length <= 65) return { line1: text, line2: '' };
    
    const words = text.split(' ');
    let line1 = [];
    let len = 0;
    while (words.length > 0 && (len + words[0].length + 1) <= 65) {
        const w = words.shift();
        line1.push(w);
        len += w.length + 1;
    }
    return { line1: line1.join(' '), line2: words.join(' ') };
});

// Break long payment_for into two lines if needed
const paymentLines = computed(() => {
    const text = props.receipt.payment_for || '';
    if (text.length <= 60) return { line1: text, line2: '' };
    
    const words = text.split(' ');
    let line1 = [];
    let len = 0;
    while (words.length > 0 && (len + words[0].length + 1) <= 60) {
        const w = words.shift();
        line1.push(w);
        len += w.length + 1;
    }
    return { line1: line1.join(' '), line2: words.join(' ') };
});

function isImageSignature(val) {
    if (!val || typeof val !== 'string') return false;
    return val.startsWith('data:image') || val.startsWith('/') || val.startsWith('http') || val.endsWith('.png') || val.endsWith('.jpg');
}
</script>

<template>
    <div class="receipt-outer-container">
        <!-- Exact Receipt Voucher -->
        <div class="receipt-card" id="printable-receipt">
            <!-- Background Template Graphic (contains exact header, lines & watermark) -->
            <img 
                src="/images/receipt_template.png" 
                alt="Envoy Cash Receipt" 
                class="receipt-bg-image" 
            />

            <!-- Overlaid Dynamic Fields -->
            <div class="receipt-fields-layer">
                <!-- Date -->
                <div class="r-field r-date" title="Date">
                    {{ formattedDate }}
                </div>

                <!-- Received From (Customer Name) -->
                <div class="r-field r-from-1" :title="receipt.customer_name">
                    {{ receipt.customer_name }}
                </div>

                <!-- The Sum of (Line 1) -->
                <div class="r-field r-sum-1" :title="receipt.amount_in_words">
                    {{ sumLines.line1 }}
                </div>
                <!-- The Sum of (Line 2 if overflow) -->
                <div v-if="sumLines.line2" class="r-field r-sum-2">
                    {{ sumLines.line2 }}
                </div>

                <!-- Amount in Figures (Naira box) -->
                <div class="r-field r-amount" title="Amount in figures">
                    {{ nairaAmount }}
                </div>
                <!-- Kobo (K box) -->
                <div class="r-field r-kobo">
                    {{ koboAmount }}
                </div>

                <!-- Being Payment For (Line 1) -->
                <div class="r-field r-for-1" :title="receipt.payment_for">
                    {{ paymentLines.line1 }}
                </div>
                <!-- Being Payment For (Line 2 if overflow) -->
                <div v-if="paymentLines.line2" class="r-field r-for-2">
                    {{ paymentLines.line2 }}
                </div>

                <!-- Customer's Signature -->
                <div class="r-sig-customer-wrap">
                    <img 
                        v-if="isImageSignature(receipt.customer_signature)" 
                        :src="receipt.customer_signature" 
                        alt="Customer Signature" 
                        class="r-sig-img"
                    />
                    <span v-else class="r-sig-text">{{ receipt.customer_signature || '' }}</span>
                </div>

                <!-- For Envoy Electricals Signature -->
                <div
                    class="r-sig-envoy-wrap"
                    :class="{ 'r-sig-clickable': canEditEnvoySig }"
                    @click="canEditEnvoySig && $emit('edit-envoy-signature')"
                    :title="canEditEnvoySig ? 'Click to set or change Envoy signature' : ''"
                >
                    <img 
                        v-if="isImageSignature(receipt.envoy_signature)" 
                        :src="receipt.envoy_signature" 
                        alt="Envoy Signature" 
                        class="r-sig-img"
                    />
                    <span v-else-if="receipt.envoy_signature" class="r-sig-text">{{ receipt.envoy_signature }}</span>
                    <span v-else-if="canEditEnvoySig" class="r-sig-placeholder">
                        <i class="bi bi-pen-fill"></i> Click to sign
                    </span>
                </div>

                <!-- For Envoy Electricals Title -->
                <div v-if="!receipt.envoy_signature" class="r-field r-sig-envoy">
                    {{ receipt.signatory_title || 'Authorized Signatory' }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.receipt-outer-container {
    width: 100%;
    overflow-x: auto;
    display: flex;
    justify-content: center;
    padding: 8px 0;
}

.receipt-card {
    position: relative;
    width: 1012px;
    min-width: 1012px;
    height: 428px;
    background: #ffffff;
    border-radius: 4px;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.15), 0 2px 6px -1px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    user-select: none;
}

.receipt-bg-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    pointer-events: none;
    z-index: 1;
}

.receipt-fields-layer {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
}

.r-field {
    position: absolute;
    color: #072b56;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-weight: 700;
    letter-spacing: 0.2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Date position - placed comfortably on the line after 'Date:' (which ends at x=801) */
.r-date {
    top: 91px;
    left: 812px;
    width: 160px;
    font-size: 13.5px;
}

/* Received From position */
.r-from-1 {
    top: 137px;
    left: 172px;
    width: 790px;
    font-size: 15px;
}

/* The Sum of positions */
.r-sum-1 {
    top: 211px;
    left: 142px;
    width: 820px;
    font-size: 13.5px;
}

.r-sum-2 {
    top: 245px;
    left: 22px;
    width: 660px;
    font-size: 13px;
}

/* Amount box - placed on the line strictly between 'N.' (ends x=740) and 'K' (starts x=975) */
.r-amount {
    top: 244px;
    left: 746px;
    width: 188px;
    font-size: 15px;
    font-weight: 800;
    text-align: right;
}

.r-kobo {
    top: 244px;
    left: 940px;
    width: 30px;
    font-size: 13.5px;
    font-weight: 700;
    text-align: center;
}

/* Being payment for positions */
.r-for-1 {
    top: 285px;
    left: 204px;
    width: 760px;
    font-size: 14px;
}

.r-for-2 {
    top: 323px;
    left: 22px;
    width: 940px;
    font-size: 14px;
}

/* Signatures */
.r-sig-customer-wrap {
    position: absolute;
    top: 345px;
    left: 274px;
    width: 245px;
    height: 40px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    pointer-events: none;
    z-index: 5;
}

.r-sig-img {
    max-width: 220px;
    max-height: 40px;
    object-fit: contain;
    margin-bottom: -1px;
}

.r-sig-text {
    font-size: 14px;
    font-style: italic;
    color: #1e3a8a;
    font-family: 'Dancing Script', 'Caveat', 'Segoe Script', cursive, sans-serif;
    font-weight: 700;
    margin-bottom: 2px;
}

.r-sig-envoy-wrap {
    position: absolute;
    top: 345px;
    left: 749px;
    width: 220px;
    height: 40px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    z-index: 5;
}

.r-sig-clickable {
    cursor: pointer;
    border: 1px dashed rgba(14, 165, 233, 0.6);
    border-radius: 6px;
    background: rgba(240, 249, 255, 0.6);
    transition: all 0.2s ease;
}

.r-sig-clickable:hover {
    border-color: #0284c7;
    background: rgba(224, 242, 254, 0.9);
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
}

.r-sig-placeholder {
    font-size: 11px;
    color: #0284c7;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 4px;
    padding-bottom: 4px;
    user-select: none;
}

.r-sig-envoy {
    top: 363px;
    left: 749px;
    width: 220px;
    font-size: 12px;
    font-weight: 700;
    color: #072b56;
    text-align: center;
}

/* Print CSS */
@media print {
    body * {
        visibility: hidden;
    }
    #printable-receipt, #printable-receipt * {
        visibility: visible;
    }
    #printable-receipt {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        margin: 0;
    }
}
</style>
