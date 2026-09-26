<script setup>
import { computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    expenses: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    month_total: { type: Number, default: 0 },
    result_count: { type: Number, default: 0 },
    selectedStoreId: { type: [Number, String, null], default: null },
});

const page = usePage();
const { has } = useCan();

const form = useForm({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const selectedStoreName = computed(() => {
    if (page.props.selectedStore?.name) {
        return page.props.selectedStore.name;
    }

    return props.selectedStoreId ? `Store #${props.selectedStoreId}` : 'All stores';
});

const hasActiveFilters = computed(() => Object.values(form.data()).some((value) => value !== '' && value !== null));

function applyFilters() {
    form.search = form.search.trim();

    const params = Object.fromEntries(
        Object.entries(form.data()).filter(([, value]) => value !== '' && value !== null),
    );

    router.get('/admin/expenses', params, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    Object.keys(form.data()).forEach((key) => {
        form[key] = '';
    });

    router.get('/admin/expenses', {}, { preserveState: true, replace: true });
}

function statusClass(status) {
    return status === 'void'
        ? 'bg-slate-100 text-slate-600'
        : 'bg-emerald-100 text-emerald-700';
}
</script>

<template>
    <div class="space-y-4">
        <FlashMessages />
        <PageHeader
            title="Expenses"
            :subtitle="`Record and track business expenses for ${selectedStoreName.toLowerCase()}.`"
            :action-href="has('expenses.create') ? '/admin/expenses/create' : ''"
            action-label="Add Expense"
        />

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Expenses this month</p>
                <p class="mt-1 text-2xl font-bold text-red-700">{{ naira(month_total) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Matching results</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ result_count }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Store scope</p>
                <p class="mt-1 flex items-center gap-2 truncate text-lg font-bold text-slate-900">
                    <i class="bi bi-shop text-teal-600" />
                    {{ selectedStoreName }}
                </p>
            </div>
        </div>

        <form class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs" @submit.prevent="applyFilters">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Search and filter</h2>
                    <p class="text-xs text-slate-500">Results always follow the store selected in the header.</p>
                </div>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="text-xs font-semibold text-amber-700 hover:text-amber-900"
                    @click="clearFilters"
                >
                    Clear filters
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <label class="sm:col-span-2">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Search</span>
                    <input
                        v-model="form.search"
                        type="search"
                        placeholder="Description, reference, vendor…"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                    />
                </label>
                <label>
                    <span class="mb-1 block text-xs font-medium text-slate-600">Category</span>
                    <select
                        v-model="form.category_id"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        @change="applyFilters"
                    >
                        <option value="">All categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-xs font-medium text-slate-600">Status</span>
                    <select
                        v-model="form.status"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        @change="applyFilters"
                    >
                        <option value="">All statuses</option>
                        <option value="recorded">Recorded</option>
                        <option value="void">Void</option>
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-xs font-medium text-slate-600">From</span>
                    <input
                        v-model="form.from"
                        type="date"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        @change="applyFilters"
                    />
                </label>
                <label>
                    <span class="mb-1 block text-xs font-medium text-slate-600">To</span>
                    <input
                        v-model="form.to"
                        type="date"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
                        @change="applyFilters"
                    />
                </label>
            </div>

            <div class="mt-3 flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0D1527]/90"
                >
                    <i class="bi bi-funnel" />
                    Apply filters
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Expense</th>
                            <th v-if="!selectedStoreId" class="px-4 py-3 text-left font-semibold text-slate-600">Store</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Ref</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Amount</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="expense in expenses.data" :key="expense.id" class="transition hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ expense.description }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ expense.paid_to || '—' }}<template v-if="expense.payment_method"> · {{ expense.payment_method }}</template>
                                </p>
                            </td>
                            <td v-if="!selectedStoreId" class="px-4 py-3 text-slate-600">{{ expense.store?.name || 'Unassigned' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ expense.ref_id }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(expense.expense_date) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ expense.category?.name || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium capitalize" :class="statusClass(expense.status)">
                                    {{ expense.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-red-700">-{{ naira(expense.amount) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    v-if="has('expenses.edit')"
                                    :href="`/admin/expenses/${expense.id}/edit`"
                                    class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!expenses.data.length">
                            <td :colspan="selectedStoreId ? 7 : 8" class="px-4 py-12 text-center text-sm text-slate-400">
                                No expenses match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="expenses" />
            </div>
        </div>
    </div>
</template>
