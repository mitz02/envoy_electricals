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
    trainings: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const { has } = useCan();
const canManage = has('training.manage');

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    level: props.filters.level ?? '',
});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.status) p.set('status', form.status);
    if (form.level) p.set('level', form.level);
    window.location.search = p.toString();
}

const levelStyles = {
    beginner: 'bg-emerald-100 text-emerald-800',
    intermediate: 'bg-amber-100 text-amber-800',
    advanced: 'bg-red-100 text-red-700',
};
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Training Programs"
        subtitle="Manage academy programs, enrollments and certificates."
        :action-href="canManage ? '/admin/training/create' : ''"
        action-label="Add Program"
    />

    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Programs</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.total }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ summary.active_programs }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Enrollments</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ summary.enrollments }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Completed</p>
            <p class="mt-2 text-2xl font-bold text-sky-700">{{ summary.completed }}</p>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs lg:flex-row">
        <input v-model="form.search" type="search" placeholder="Search title, ref, description..." class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.level" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All levels</option>
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
        </select>
        <select v-model="form.status" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Program</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Level</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Duration</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Price</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Enrolled</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Capacity</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="t in trainings.data" :key="t.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="`/admin/training/${t.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ t.title }}</Link>
                            <p class=" text-xs text-slate-400">{{ t.ref_id }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="badgeClass(levelStyles[t.level] || 'bg-slate-100 text-slate-600')" class="capitalize">{{ t.level }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ t.duration_weeks }} {{ t.duration_weeks === 1 ? 'wk' : 'wks' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ t.price > 0 ? '₦' + Number(t.price).toLocaleString() : 'Free' }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ t.enrolled }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ t.capacity ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="badgeClass(t.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">
                                {{ t.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Link :href="`/admin/training/${t.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">View</Link>
                                <Link v-if="canManage" :href="`/admin/training/${t.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!trainings.data.length">
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">No training programs found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="trainings" />
        </div>
    </div>
</template>