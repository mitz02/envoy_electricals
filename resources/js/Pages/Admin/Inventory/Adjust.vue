<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, badgeClass, formatDateTime } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: { type: Array, default: () => [] },
    movements: { type: Object, required: true },
    stores: { type: Array, default: () => [] },
    activeStoreId: { type: Number, default: null },
});

const search = ref('');
const showResults = ref(false);
const highlighted = ref(-1);
const searchInputRef = ref(null);

const form = useForm({
    product_id: '',
    quantity: 1,
    type: 'adjustment',
    direction: 'increase',
    reason: '',
    store_id: props.activeStoreId,
});

/**
 * Plain-language options. `direction` is locked for damage and return because
 * those can only ever move stock one way.
 */
const reasons = [
    {
        value: 'adjustment',
        label: 'The count was wrong',
        help: 'You counted the shelf and the number did not match the system.',
        icon: 'bi-sliders',
        direction: 'both',
        tone: 'slate',
    },
    {
        value: 'damage',
        label: 'Stock was damaged or expired',
        help: 'Broken, spoiled or written off. This always removes stock.',
        icon: 'bi-exclamation-triangle',
        direction: 'out',
        tone: 'red',
    },
    {
        value: 'return',
        label: 'Stock came back to us',
        help: 'A customer or supplier returned it. This always adds stock.',
        icon: 'bi-arrow-return-left',
        direction: 'in',
        tone: 'green',
    },
];

const selectedReason = computed(() => reasons.find((r) => r.value === form.type) ?? reasons[0]);

const selectedProduct = computed(
    () => props.products.find((p) => p.id === form.product_id) ?? null,
);

const activeStore = computed(() => props.stores.find((s) => s.id === props.activeStoreId) ?? null);

/** Only a plain correction can go up or down; damage and return are fixed. */
const effectiveDirection = computed(() => {
    if (selectedReason.value.direction === 'out') return 'decrease';
    if (selectedReason.value.direction === 'in') return 'increase';
    return form.direction;
});

const canDecrease = computed(() => effectiveDirection.value === 'decrease');

const resultingQuantity = computed(() => {
    if (!selectedProduct.value) return null;

    const qty = Number(form.quantity) || 0;
    const current = Number(selectedProduct.value.current_quantity) || 0;

    return canDecrease.value ? current - qty : current + qty;
});

/** Warn before the server rejects it, so the user is not surprised. */
const insufficientStock = computed(
    () => canDecrease.value && resultingQuantity.value !== null && resultingQuantity.value < 0,
);

const toneClasses = {
    slate: { idle: 'border-slate-200 hover:border-slate-400', active: 'border-[#0D1527] bg-[#0D1527]/5', icon: 'bg-slate-100 text-slate-500 group-hover:text-[#0D1527]' },
    red: { idle: 'border-slate-200 hover:border-red-300', active: 'border-red-500 bg-red-50', icon: 'bg-red-100 text-red-600' },
    green: { idle: 'border-slate-200 hover:border-emerald-300', active: 'border-emerald-500 bg-emerald-50', icon: 'bg-emerald-100 text-emerald-600' },
};

const reasonChips = [
    'Cycle count correction',
    'Found extra stock',
    'Stock could not be found',
    'Damaged in storage',
    'Expired stock write-off',
    'Customer return',
    'Supplier return',
    'Counted wrong previously',
];

const matches = computed(() => {
    const q = search.value.trim().toLowerCase();
    const pool = props.products;

    if (!q) return pool.slice(0, 8);

    return pool
        .filter(
            (p) =>
                p.name.toLowerCase().includes(q) ||
                p.sku.toLowerCase().includes(q) ||
                String(p.id) === q,
        )
        .slice(0, 8);
});

const typeColors = {
    damage: 'bg-red-100 text-red-700',
    return: 'bg-green-100 text-green-800',
    adjustment: 'bg-slate-100 text-slate-700',
};

function chooseReason(reason) {
    form.type = reason.value;
    // Only a damage write-off is forced down; a correction and a return both
    // default to adding stock back.
    form.direction = reason.direction === 'out' ? 'decrease' : 'increase';
}

function pickProduct(product) {
    form.product_id = product.id;
    search.value = '';
    showResults.value = false;
    highlighted.value = -1;
    nextTick(() => searchInputRef.value?.focus());
}

function changeStore(event) {
    router.get(
        '/admin/stock/adjust',
        { store_id: event.target.value },
        { preserveState: true, replace: true },
    );
}

function submit() {
    form.store_id = props.activeStoreId;

    form.post('/admin/stock/adjust', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.store_id = props.activeStoreId;
            form.type = 'adjustment';
            form.direction = 'increase';
            search.value = '';
            showResults.value = false;
        },
    });
}

function onSearchKeydown(event) {
    const results = matches.value;
    if (!results.length) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlighted.value = Math.min(highlighted.value + 1, results.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        if (results[highlighted.value]) pickProduct(results[highlighted.value]);
    } else if (event.key === 'Escape') {
        showResults.value = false;
        highlighted.value = -1;
    }
}

function fallbackImg(event) {
    if (event.target.dataset.fallbackApplied) return;
    event.target.dataset.fallbackApplied = '1';
    event.target.src = '/images/landing/solar_panels_sky.jpg';
}
</script>

<template>
    <FlashMessages />

    <!-- Explains the purpose of the page before anything else -->
    <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <h1 class="text-xl font-bold text-slate-900">Correct a stock count</h1>
        <p class="mt-1 max-w-3xl text-sm text-slate-600">
            Use this page when the number in the system does not match the number physically on the shelf. It only
            fixes the quantity &mdash; it does not create a bill, a supplier debt or a sale.
            <strong class="font-semibold text-slate-800">To bring stock in from a supplier, record a Purchase instead.</strong>
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <Link href="/admin/purchases/create" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">
                <i class="bi bi-bag-plus me-1" />Record a Purchase
            </Link>
            <Link href="/admin/stock/movements" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">
                <i class="bi bi-arrow-repeat me-1" />See the full stock history
            </Link>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
        <!-- The form -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <!-- Step 1: which branch -->
            <div class="border-b border-slate-100 p-5">
                <label class="block text-sm font-semibold text-slate-900" for="branch">1. Which branch?</label>
                <p class="mt-0.5 text-sm text-slate-500">Stock is counted per branch, so pick the one you are standing in.</p>

                <select
                    id="branch"
                    class="mt-3 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    :value="activeStoreId ?? ''"
                    @change="changeStore"
                >
                    <option v-if="!stores.length" value="">No branch available</option>
                    <option v-for="store in stores" :key="store.id" :value="store.id">
                        {{ store.name }} ({{ store.code }}){{ store.is_active ? '' : ' — inactive' }}
                    </option>
                </select>

                <p v-if="form.errors.store_id" class="mt-2 text-sm text-red-600">{{ form.errors.store_id }}</p>
                <p v-if="!stores.length" class="mt-2 text-sm text-red-600">You have no branch assigned. Ask a super admin to grant you one.</p>
            </div>

            <!-- Step 2: which product -->
            <div class="border-b border-slate-100 p-5">
                <p class="text-sm font-semibold text-slate-900">2. Which product?</p>
                <p class="mt-0.5 text-sm text-slate-500">Search by name or SKU.</p>

                <div v-if="selectedProduct" class="mt-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <img
                        :src="selectedProduct.image"
                        :alt="selectedProduct.name"
                        class="h-12 w-12 shrink-0 rounded-lg object-cover ring-1 ring-slate-200"
                        loading="lazy"
                        @error="fallbackImg"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-slate-900">{{ selectedProduct.name }}</p>
                        <p class="font-mono text-xs text-slate-500">
                            {{ selectedProduct.sku }} · {{ naira(selectedProduct.selling_price) }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs text-slate-500">On hand</p>
                        <p class="text-lg font-bold text-slate-900">{{ selectedProduct.current_quantity }}</p>
                    </div>
                    <button type="button" class="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Choose a different product" @click="form.product_id = ''">
                        <i class="bi bi-x-lg" />
                    </button>
                </div>

                <div v-else class="relative mt-3" @click.outside="showResults = false">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" />
                    <input
                        ref="searchInputRef"
                        v-model="search"
                        type="search"
                        placeholder="e.g. 550W panel or EV-0004"
                        class="w-full rounded-lg border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        autocomplete="off"
                        @keydown="onSearchKeydown"
                        @focus="showResults = true"
                    />

                    <div
                        v-if="showResults && matches.length"
                        class="absolute z-20 mt-2 max-h-80 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"
                    >
                        <button
                            v-for="(product, index) in matches"
                            :key="product.id"
                            type="button"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-slate-50"
                            :class="index === highlighted ? 'bg-[#0D1527]/5' : ''"
                            @click="pickProduct(product)"
                            @mouseenter="highlighted = index"
                        >
                            <img
                                :src="product.image"
                                :alt="product.name"
                                class="h-9 w-9 shrink-0 rounded-md object-cover ring-1 ring-slate-100"
                                loading="lazy"
                                @error="fallbackImg"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-slate-900">{{ product.name }}</span>
                                <span class="block font-mono text-[11px] text-slate-400">{{ product.sku }}</span>
                            </span>
                            <span
                                :class="badgeClass(
                                    product.current_quantity <= 0
                                        ? 'bg-red-100 text-red-700'
                                        : product.current_quantity <= (product.reorder_level || 5)
                                          ? 'bg-amber-100 text-amber-800'
                                          : 'bg-slate-100 text-slate-600',
                                )"
                            >
                                {{ product.current_quantity }} on hand
                            </span>
                        </button>
                    </div>

                    <p v-else-if="search && !matches.length" class="mt-2 text-sm text-slate-500">
                        No product in this branch matches "{{ search }}".
                    </p>
                </div>

                <p v-if="form.errors.product_id" class="mt-2 text-sm text-red-600">{{ form.errors.product_id }}</p>
            </div>

            <!-- Step 3: what happened -->
            <div v-if="selectedProduct" class="border-b border-slate-100 p-5">
                <p class="text-sm font-semibold text-slate-900">3. What happened?</p>
                <p class="mt-0.5 text-sm text-slate-500">This decides the reason shown in the stock history.</p>

                <div class="mt-3 grid gap-2 sm:grid-cols-3">
                    <button
                        v-for="reason in reasons"
                        :key="reason.value"
                        type="button"
                        class="group rounded-xl border-2 p-3 text-left transition"
                        :class="form.type === reason.value ? toneClasses[reason.tone].active : toneClasses[reason.tone].idle"
                        @click="chooseReason(reason)"
                    >
                        <i :class="[reason.icon, 'text-lg', toneClasses[reason.tone].icon]" />
                        <span class="mt-1.5 block text-sm font-semibold text-slate-900">{{ reason.label }}</span>
                        <span class="mt-0.5 block text-xs leading-snug text-slate-500">{{ reason.help }}</span>
                    </button>
                </div>
            </div>

            <!-- Step 4: how much -->
            <div v-if="selectedProduct" class="p-5">
                <p class="text-sm font-semibold text-slate-900">4. How many units?</p>

                <!-- Direction, only meaningful for a plain correction -->
                <div v-if="selectedReason.direction === 'both'" class="mt-3">
                    <span class="block text-xs font-medium text-slate-600">Did the count go up or down?</span>
                    <div class="mt-1.5 inline-flex rounded-lg border border-slate-200 p-0.5">
                        <button
                            v-for="option in [
                                { value: 'increase', label: 'Goes up', sign: '+', active: 'bg-emerald-600 text-white' },
                                { value: 'decrease', label: 'Goes down', sign: '−', active: 'bg-red-600 text-white' },
                            ]"
                            :key="option.value"
                            type="button"
                            class="rounded-md px-4 py-1.5 text-sm font-medium transition"
                            :class="form.direction === option.value ? option.active : 'text-slate-600 hover:bg-slate-50'"
                            @click="form.direction = option.value"
                        >
                            {{ option.sign }} {{ option.label }}
                        </button>
                    </div>
                </div>
                <p v-else class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">
                    <i :class="[selectedReason.icon, 'me-1']" />{{ selectedReason.help }}
                </p>

                <div class="mt-3">
                    <label class="block text-xs font-medium text-slate-600" for="qty">Quantity</label>
                    <input
                        id="qty"
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        class="mt-1 w-full rounded-lg border-slate-300 text-base focus:border-amber-400 focus:ring-amber-400/20 sm:max-w-[200px]"
                        :class="form.errors.quantity ? 'border-red-400' : ''"
                    />
                    <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
                </div>

                <!-- Before / after preview -->
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Effect on this branch</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-lg">
                        <span class="font-semibold text-slate-500">{{ selectedProduct.current_quantity }}</span>
                        <i class="bi bi-arrow-right text-slate-400" />
                        <span
                            class="font-bold"
                            :class="resultingQuantity < 0 ? 'text-red-600' : canDecrease ? 'text-red-600' : 'text-emerald-600'"
                        >
                            {{ resultingQuantity }}
                        </span>
                        <span class="text-xs text-slate-400">
                            {{ canDecrease ? 'units will be removed' : 'units will be added' }}
                        </span>
                    </div>
                    <p v-if="insufficientStock" class="mt-2 text-sm font-medium text-red-600">
                        <i class="bi bi-exclamation-circle me-1" />There are only {{ selectedProduct.current_quantity }} on
                        hand here, so you cannot remove {{ form.quantity }}. The count would go negative.
                    </p>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-600" for="why">
                        Why are you making this change? <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="why"
                        v-model="form.reason"
                        rows="2"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        :class="form.errors.reason ? 'border-red-400' : ''"
                        placeholder="This is stored in the audit trail, so be specific."
                    />
                    <p v-if="form.errors.reason" class="mt-1 text-sm text-red-600">{{ form.errors.reason }}</p>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button
                            v-for="chip in reasonChips"
                            :key="chip"
                            type="button"
                            class="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-slate-400 hover:text-slate-900"
                            @click="form.reason = chip"
                        >
                            {{ chip }}
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#0D1527] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="form.processing || insufficientStock"
                    @click="submit"
                >
                    <i v-if="form.processing" class="bi bi-hourglass-split animate-spin" />
                    <i v-else class="bi bi-check-lg" />
                    {{ form.processing ? 'Saving…' : `Save correction to ${activeStore?.name ?? 'this branch'}` }}
                </button>
            </div>
        </div>

        <!-- Sidebar: recent corrections -->
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="text-sm font-semibold text-slate-900">What each option does</h2>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div v-for="reason in reasons" :key="reason.value" class="flex gap-2">
                        <dt class="shrink-0"><i :class="[reason.icon, 'text-sm', reason.tone === 'red' ? 'text-red-500' : reason.tone === 'green' ? 'text-emerald-500' : 'text-slate-500']" /></dt>
                        <dd class="text-slate-600">
                            <span class="font-medium text-slate-900">{{ reason.label }}.</span> {{ reason.help }}
                        </dd>
                    </div>
                </dl>
                <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">
                    <i class="bi bi-info-circle me-1" />
                    Corrections never change the cost of your stock or create a payable. Use a Purchase for that.
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <h2 class="border-b border-slate-100 px-5 py-4 text-sm font-semibold text-slate-900">
                    Recent corrections here
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Product</th>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Reason</th>
                                <th class="px-4 py-2 text-right font-semibold text-slate-600">Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="movement in movements.data.slice(0, 8)" :key="movement.id">
                                <td class="px-4 py-2.5">
                                    <p class="max-w-[130px] truncate font-medium text-slate-900">{{ movement.product?.name ?? 'Deleted product' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ formatDateTime(movement.movement_date) }}</p>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span :class="badgeClass(typeColors[movement.type] ?? 'bg-slate-100 text-slate-600')">
                                        {{ movement.type.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right font-semibold"
                                    :class="movement.quantity_change >= 0 ? 'text-emerald-700' : 'text-red-700'"
                                >
                                    {{ movement.quantity_change > 0 ? '+' : '' }}{{ movement.quantity_change }}
                                </td>
                            </tr>
                            <tr v-if="!movements.data.length">
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-400">
                                    Nothing corrected on this branch yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="movements.data.length" class="border-t border-slate-100 px-4 py-2">
                    <Pagination :meta="movements" />
                </div>
            </div>
        </div>
    </div>
</template>
