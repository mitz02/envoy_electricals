<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    purchases: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const has = useCan().has;

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function applyFilters() {
    const params = {};
    Object.entries(form.data()).forEach(([k, v]) => {
        if (v) {
            params[k] = v;
        }
    });
    window.location.search = new URLSearchParams(params).toString();
}
</script>

<template>
        <FlashMessages />
        <PageHeader
            title="Purchases"
            subtitle="Purchase history from suppliers."
            :action-href="has('purchases.create') ? '/admin/purchases/create' : ''"
            action-label="New Purchase"
        />

        <div class="mb-4 grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-5">
            <input v-model="form.search" type="search" placeholder="Ref, invoice, supplier…" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
            <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="void">Void</option>
            </select>
            <input v-model="form.from" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters" />
            <input v-model="form.to" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters" />
            <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Purchase Ref</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Supplier</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Total</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Paid</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Owing</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in purchases.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/purchases/${p.id}`" class="font-mono text-xs font-medium text-slate-900 hover:text-slate-600">{{ p.ref_id }}</Link>
                                <p v-if="p.invoice_no" class="text-xs text-slate-400">{{ p.invoice_no }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(p.purchase_date) }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ p.supplier?.name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ naira(p.total) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">{{ naira(p.amount_paid) }}</td>
                            <td class="px-4 py-3 text-right" :class="p.balance > 0 ? 'font-semibold text-amber-700' : 'text-slate-400'">{{ naira(p.balance) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span :class="badgeClass(p.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700')">{{ p.status }}</span>
                            </td>
                        </tr>
                        <tr v-if="!purchases.data.length">
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">No purchases yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <Pagination :meta="purchases" />
            </div>
        </div>
</template>