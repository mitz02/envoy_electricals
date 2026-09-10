<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { naira, formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    supplier: { type: Object, required: true },
});
</script>

<template>
        <FlashMessages />
        <PageHeader :title="supplier.name" :subtitle="supplier.ref_id" />

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Details</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="text-slate-800">{{ supplier.phone || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="text-slate-800">{{ supplier.email || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Contact</dt><dd class="text-slate-800">{{ supplier.contact_person || '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Address</dt><dd class="text-slate-800">{{ supplier.address || '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs lg:col-span-2">
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Purchase History</h2>
                <div v-if="supplier.purchases?.length" class="space-y-3">
                    <div v-for="p in supplier.purchases" :key="p.id" class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-0">
                        <div>
                            <p class="font-mono text-xs font-medium text-slate-700">{{ p.ref_id }}</p>
                            <p class="text-xs text-slate-400">{{ formatDate(p.purchase_date) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-900">{{ naira(p.total) }}</p>
                            <p class="text-xs" :class="p.balance > 0 ? 'text-amber-700' : 'text-slate-400'">
                                {{ p.balance > 0 ? `Owing: ${naira(p.balance)}` : 'Settled' }}
                            </p>
                        </div>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-slate-400">No purchases recorded from this supplier.</p>
            </div>
        </div>
</template>