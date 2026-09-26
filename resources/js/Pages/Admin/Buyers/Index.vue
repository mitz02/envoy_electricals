<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { naira, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    buyers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search ?? '',
});

const actions = useForm({});

function applyFilters() {
    const params = {};
    if (form.search) params.search = form.search;
    window.location.search = new URLSearchParams(params).toString();
}

function clearFilters() {
    form.search = '';
    window.location.search = '';
}

function impersonate(buyer) {
    if (window.confirm(`Sign in as ${buyer.name} to see their account exactly as they do?`)) {
        actions.post(route('admin.buyers.impersonate', buyer.id));
    }
}

function removeBuyer(buyer) {
    if (window.confirm(`Delete the login account for ${buyer.name}? Their order history and linked customer record are kept for admin bookkeeping. This cannot be undone.`)) {
        actions.delete(route('admin.buyers.destroy', buyer.id));
    }
}
</script>

<template>
    <div class="w-full space-y-5">
        <FlashMessages />
        <PageHeader title="Buyers" subtitle="Registered website buyers and their online order history." />

        <div class="flex w-full flex-col gap-3 rounded-3xl border border-slate-200/80 bg-white p-3 shadow-[0_10px_30px_rgba(13,21,39,0.05)] lg:flex-row lg:items-center">
            <div class="relative min-w-0 flex-1">
                <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input
                    v-model="form.search"
                    type="search"
                    placeholder="Search buyers by name, email or phone…"
                    aria-label="Search buyers"
                    class="h-11 w-full rounded-2xl border-slate-200 bg-slate-50/80 pl-10 pr-4 text-sm placeholder:text-slate-400 focus:border-[#40e0d0] focus:bg-white focus:ring-2 focus:ring-[#40e0d0]/20"
                    @keyup.enter="applyFilters"
                />
            </div>
            <button type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-[#40e0d0] px-5 text-sm font-bold text-[#0D1527] shadow-sm transition hover:-translate-y-0.5 hover:bg-[#35cab9] hover:shadow-md" @click="applyFilters">
                <i class="bi bi-search"></i>
                Search
            </button>
        </div>

        <section class="w-full">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-tight text-[#0D1527]">{{ form.search ? 'Matching buyers' : 'All buyers' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">Accounts created by customers registering on the website.</p>
                </div>
                <span class="w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-xs">
                    {{ buyers.total }} {{ buyers.total === 1 ? 'result' : 'results' }}
                </span>
            </div>

            <div v-if="!buyers.data.length" class="relative overflow-hidden rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-14 text-center">
                <div class="absolute -left-10 -top-10 h-32 w-32 rounded-full bg-[#40e0d0]/10"></div>
                <div class="absolute -bottom-12 -right-8 h-36 w-36 rounded-full bg-[#FACC15]/20"></div>
                <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-3xl border border-[#40e0d0]/30 bg-[#40e0d0] text-2xl text-[#0D1527] shadow-lg shadow-[#40e0d0]/20">
                    <i class="bi bi-person-raised-hand"></i>
                </div>
                <h3 class="relative mt-5 text-lg font-bold text-[#0D1527]">No buyers found</h3>
                <p class="relative mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">When customers register on the shop they'll appear here.</p>
                <button v-if="form.search" type="button" class="relative mt-6 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="clearFilters">
                    Clear filters
                </button>
            </div>

            <div v-else class="w-full overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(13,21,39,0.05)]">
                <div class="h-1.5 bg-gradient-to-r from-[#40e0d0] via-[#FACC15] to-[#40e0d0]"></div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full min-w-[1060px] divide-y divide-slate-200 text-sm">
                        <thead class="bg-[#FAF8F2]">
                            <tr>
                                <th class="px-5 py-4 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Buyer</th>
                                <th class="px-4 py-4 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Phone</th>
                                <th class="px-4 py-4 text-left text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Joined</th>
                                <th class="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Orders</th>
                                <th class="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Order value</th>
                                <th class="px-4 py-4 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Last order</th>
                                <th class="px-5 py-4 text-right text-[10px] font-bold uppercase tracking-[0.14em] text-[#0D1527]/60">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="b in buyers.data" :key="b.id" class="group transition hover:bg-[#40e0d0]/[0.05]">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[#40e0d0]/25 bg-[#40e0d0]/15 text-xs font-black text-[#0D1527]">{{ (b.name || 'B').trim().charAt(0).toUpperCase() }}</span>
                                        <div class="min-w-0">
                                            <Link :href="route('admin.buyers.show', b.id)" class="max-w-56 truncate font-bold text-slate-900 transition hover:text-teal-700">{{ b.name }}</Link>
                                            <p v-if="b.email" class="mt-0.5 max-w-56 truncate text-xs text-slate-400">{{ b.email }}</p>
                                            <p v-if="!b.is_active" class="mt-0.5 w-fit rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600">Inactive</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ b.phone || '—' }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ formatDate(b.created_at) }}</td>
                                <td class="px-4 py-4 text-right font-bold text-slate-900">{{ b.orders_count }}</td>
                                <td class="px-4 py-4 text-right font-bold text-slate-900">{{ naira(b.orders_total) }}</td>
                                <td class="px-4 py-4 text-right text-slate-600">{{ b.last_order_at ? formatDate(b.last_order_at) : '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="route('admin.buyers.show', b.id)" title="View buyer" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#40e0d0] hover:text-[#0D1527]">
                                            <i class="bi bi-eye text-sm"></i>
                                        </Link>
                                        <Link :href="route('admin.buyers.edit', b.id)" title="Edit buyer" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#FACC15] hover:text-[#0D1527]">
                                            <i class="bi bi-pencil-square text-sm"></i>
                                        </Link>
                                        <button type="button" title="Impersonate buyer" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-[#0D1527] hover:text-[#0D1527]" @click="impersonate(b)">
                                            <i class="bi bi-incognito text-sm"></i>
                                        </button>
                                        <button type="button" title="Delete buyer" class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600" @click="removeBuyer(b)">
                                            <i class="bi bi-trash3 text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="buyers.data.length && buyers.last_page > 1" class="mt-4 w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
                <Pagination :meta="buyers" />
            </div>
        </section>

        <p v-if="actions.processing" class="text-center text-xs text-slate-400">Working…</p>
    </div>
</template>