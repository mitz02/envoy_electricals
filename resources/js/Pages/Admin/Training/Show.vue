<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

defineProps({
    training: { type: Object, required: true },
    enrollments: { type: Object, required: true },
});

const { has } = useCan();
const canManage = has('training.manage');

const statusStyles = {
    enrolled: 'bg-slate-100 text-slate-600',
    in_progress: 'bg-sky-100 text-sky-800',
    completed: 'bg-emerald-100 text-emerald-800',
    withdrawn: 'bg-red-100 text-red-700',
};

const statusLabels = {
    enrolled: 'Enrolled',
    in_progress: 'In Progress',
    completed: 'Qualified',
    withdrawn: 'Withdrawn',
};

function statusLabel(s) {
    return statusLabels[s] || s.replace('_', ' ');
}

function complete(e) {
    if (!confirm(`Mark ${e.trainee.name} as qualified for this training? They'll be able to download their certificate.`)) return;
    router.post(route('admin.enrollments.complete', e.id));
}
</script>

<template>
    <FlashMessages />
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-black uppercase tracking-tight text-[#0D1527] sm:text-2xl">{{ training.title }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ training.ref_id }} · {{ training.level }}</p>
        </div>
        <Link href="/admin/training" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
            <i class="bi bi-arrow-left"></i> Programs
        </Link>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Program details -->
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                <div class="flex flex-wrap items-center gap-3">
                    <span :class="badgeClass(training.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">
                        {{ training.is_active ? 'Open for enrollment' : 'Inactive' }}
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-0.5 text-xs font-medium capitalize text-slate-600">{{ training.level }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-0.5 text-xs font-medium text-slate-600">{{ training.duration_weeks }} weeks</span>
                    <span v-if="training.certificate_eligible" class="rounded-full bg-yellow-100 px-3 py-0.5 text-xs font-medium text-yellow-800"><i class="bi bi-patch-check mr-1"></i>Certificate</span>
                    <span v-if="training.is_featured" class="rounded-full bg-purple-100 px-3 py-0.5 text-xs font-medium text-purple-800"><i class="bi bi-star-fill mr-1"></i>Featured</span>
                    <span v-if="training.start_date" class="rounded-full bg-slate-100 px-3 py-0.5 text-xs font-medium text-slate-600">
                        <i class="bi bi-calendar3 mr-1"></i>Starts {{ training.start_date }}
                    </span>
                </div>

                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ training.description || 'No description provided.' }}</p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div v-if="training.objectives">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Objectives</h3>
                        <ul class="mt-2 space-y-1.5">
                            <li v-for="(o, i) in training.objectives.split('\n').filter(Boolean)" :key="i" class="flex items-start gap-2 text-sm text-slate-600">
                                <i class="bi bi-check2-circle mt-0.5 text-emerald-500"></i><span>{{ o }}</span>
                            </li>
                        </ul>
                    </div>
                    <div v-if="training.learning_outcomes">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Learning outcomes</h3>
                        <ul class="mt-2 space-y-1.5">
                            <li v-for="(o, i) in training.learning_outcomes.split('\n').filter(Boolean)" :key="i" class="flex items-start gap-2 text-sm text-slate-600">
                                <i class="bi bi-flag mt-0.5 text-[#40e0d0]"></i><span>{{ o }}</span>
                            </li>
                        </ul>
                    </div>
                    <div v-if="training.prerequisites" class="sm:col-span-2">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Prerequisites</h3>
                        <ul class="mt-2 space-y-1.5">
                            <li v-for="(p, i) in training.prerequisites.split('\n').filter(Boolean)" :key="i" class="flex items-start gap-2 text-sm text-slate-600">
                                <i class="bi bi-shield-check mt-0.5 text-amber-500"></i><span>{{ p }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Structured curriculum -->
                <div v-if="training.weeks.length" class="mt-7 border-t border-slate-100 pt-6">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Curriculum</h3>
                    <div class="mt-4 space-y-3">
                        <div v-for="week in training.weeks" :key="week.id" class="overflow-hidden rounded-xl border border-slate-200">
                            <div class="flex items-center gap-3 bg-slate-50 px-4 py-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#0D1527] text-[11px] font-black text-yellow-400">W{{ week.week_number }}</span>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-800">{{ week.title }}</p>
                                    <p v-if="week.summary" class="text-xs text-slate-500">{{ week.summary }}</p>
                                </div>
                                <span class="text-xs font-semibold text-slate-400">{{ week.lessons.length }} lessons</span>
                            </div>
                            <ul v-if="week.lessons.length" class="divide-y divide-slate-100">
                                <li v-for="lesson in week.lessons" :key="lesson.id" class="flex items-start justify-between gap-3 px-4 py-2.5">
                                    <span class="flex items-start gap-2 text-sm text-slate-600">
                                        <i class="bi bi-play-circle mt-0.5 text-[#40e0d0]"></i>
                                        <span>{{ lesson.title }}</span>
                                    </span>
                                    <span v-if="lesson.duration_minutes" class="shrink-0 text-xs text-slate-400">{{ lesson.duration_minutes }} min</span>
                                </li>
                            </ul>
                            <p v-else class="px-4 py-2.5 text-xs text-slate-400">No lessons added.</p>
                        </div>
                    </div>
                </div>

                <!-- Legacy plain-text curriculum -->
                <div v-if="!training.weeks.length && training.curriculum" class="mt-6">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Curriculum outline</h3>
                    <p class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-600">{{ training.curriculum }}</p>
                </div>
            </div>

            <!-- Enrollments -->
            <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-tight text-slate-900">Enrollments</h2>
                    <span class="text-xs text-slate-400">{{ enrollments.total }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Trainee</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Progress</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Certificate</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="e in enrollments.data" :key="e.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <Link :href="`/admin/trainees/${e.trainee.id}`" class="font-medium text-slate-900 hover:text-slate-600">{{ e.trainee.name }}</Link>
                                    <p class="text-xs text-slate-400">{{ e.trainee.ref_id }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="badgeClass(statusStyles[e.status] || 'bg-slate-100 text-slate-600')" class="capitalize">{{ statusLabel(e.status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-semibold text-slate-800">{{ e.progress }}%</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-mono text-xs font-semibold" :class="e.certificate_no ? 'text-emerald-600' : 'text-slate-400'">
                                        {{ e.certificate_no || '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <button
                                            v-if="canManage && e.status !== 'completed' && e.status !== 'withdrawn'"
                                            @click="complete(e)"
                                            title="Mark as qualified and issue certificate"
                                            class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-bold text-white hover:bg-emerald-700"
                                        >
                                            <i class="bi bi-award"></i> Mark Qualified
                                        </button>
                                        <Link :href="`/admin/trainees/${e.trainee.id}`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Manage</Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!enrollments.data.length">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">No enrollments yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :meta="enrollments" />
                </div>
            </div>
        </div>

        <!-- Side actions -->
        <div class="space-y-4">
            <div v-if="canManage" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Actions</h3>
                <div class="mt-3 space-y-2">
                    <Link :href="`/admin/training/${training.id}/edit`" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <i class="bi bi-pencil-square"></i> Edit program
                    </Link>
                    <a :href="`/training/${training.id}`" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <i class="bi bi-eye"></i> View public page
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Program details</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Capacity</dt><dd class="font-semibold text-slate-800">{{ training.capacity ?? 'Unlimited' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Price</dt><dd class="font-semibold text-slate-800">₦{{ Number(training.price || 0).toLocaleString() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Curriculum</dt><dd class="font-semibold text-slate-800">{{ training.weeks.length ? `${training.weeks.length} weeks` : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Lessons</dt><dd class="font-semibold text-slate-800">{{ training.weeks.reduce((n, w) => n + w.lessons.length, 0) }}</dd></div>
                </dl>
                <p class="mt-4 text-[11px] leading-relaxed text-slate-400">
                    Use "Mark Qualified" on an enrollment once the trainee finishes the program — this issues their certificate for download.
                </p>
            </div>
        </div>
    </div>
</template>