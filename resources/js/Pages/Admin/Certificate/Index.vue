<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    certificates: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('training.manage');

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.status) p.set('status', form.status);
    window.location.search = p.toString();
}

function voidCert(c) {
    if (!confirm(`Void certificate ${c.certificate_no}? It will remain on file but be marked invalid.`)) return;
    router.post(route('admin.certificates.void', c.id));
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Certificates"
        subtitle="All issued and voided completion certificates."
    />

    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Issued</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ summary.issued }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Voided</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ summary.void }}</p>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input v-model="form.search" type="search" placeholder="Search certificate no, trainee, program..." class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All statuses</option>
            <option value="issued">Issued</option>
            <option value="void">Voided</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Certificate No.</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Trainee</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Program</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Issued</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th v-if="canManage" class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="c in certificates.data" :key="c.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800">{{ c.certificate_no }}</td>
                        <td class="px-4 py-3">
                            <Link :href="`/admin/trainees/${c.trainee.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ c.trainee.name }}</Link>
                            <p class="text-xs text-slate-400">{{ c.trainee.ref_id }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ c.training.title }}</td>
                        <td class="px-4 py-3 text-center text-slate-500">{{ new Date(c.issued_at + 'T00:00:00').toLocaleDateString('en-GB') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="badgeClass(c.status === 'void' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800')" class="capitalize">{{ c.status }}</span>
                        </td>
                        <td v-if="canManage" class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Link :href="route('portal.certificates.show', c.id)" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                <button v-if="c.status === 'issued'" @click="voidCert(c)" class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">Void</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!certificates.data.length">
                        <td :colspan="canManage ? 6 : 5" class="px-4 py-12 text-center text-sm text-slate-400">No certificates found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="certificates" />
        </div>
    </div>
</template>