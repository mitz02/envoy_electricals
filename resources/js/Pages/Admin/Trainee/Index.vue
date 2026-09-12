<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    trainees: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('training.manage');

const form = useForm({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.type) p.set('type', form.type);
    if (form.status) p.set('status', form.status);
    window.location.search = p.toString();
}

const typeStyles = { staff: 'bg-sky-100 text-sky-800', apprentice: 'bg-violet-100 text-violet-800', trainee: 'bg-emerald-100 text-emerald-800' };
const statusStyles = { pending: 'bg-amber-100 text-amber-800', active: 'bg-emerald-100 text-emerald-800', graduated: 'bg-sky-100 text-sky-800', withdrawn: 'bg-red-100 text-red-700' };
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Trainees"
        subtitle="Academy attendees, enrollments and progress."
        :action-href="canManage ? '/admin/trainees/create' : ''"
        action-label="Add Trainee"
    />

    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Trainees</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.total }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ summary.active }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Apprentices</p>
            <p class="mt-2 text-2xl font-bold text-violet-700">{{ summary.apprentices }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Certificates Issued</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ summary.certificates }}</p>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs lg:flex-row">
        <input v-model="form.search" type="search" placeholder="Search name, ref, phone, email..." class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.type" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All types</option>
            <option value="staff">Staff</option>
            <option value="apprentice">Apprentice</option>
            <option value="trainee">Trainee</option>
        </select>
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="graduated">Graduated</option>
            <option value="withdrawn">Withdrawn</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Trainee</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Enrollments</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Certificates</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="t in trainees.data" :key="t.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="`/admin/trainees/${t.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ t.name }}</Link>
                            <p class="text-xs text-slate-400">
                                {{ t.ref_id }} · {{ t.email || t.phone || 'No contact' }}
                                <span v-if="t.has_login" class="ml-1 inline-flex items-center gap-0.5 text-emerald-600"><i class="bi bi-person-check"></i> portal</span>
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="badgeClass(typeStyles[t.type] || 'bg-slate-100 text-slate-600')" class="capitalize">{{ t.type }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ t.enrollments_count }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ t.certificates_count }}</td>
                        <td class="px-4 py-3">
                            <span :class="badgeClass(statusStyles[t.status] || 'bg-slate-100 text-slate-600')" class="capitalize">{{ t.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Link :href="`/admin/trainees/${t.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                <Link v-if="canManage" :href="`/admin/trainees/${t.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!trainees.data.length">
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No trainees found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="trainees" />
        </div>
    </div>
</template>