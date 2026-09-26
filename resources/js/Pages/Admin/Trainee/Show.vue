<script setup>
import { reactive, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import InputError from '@/Components/InputError.vue';
import { badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    trainee: { type: Object, required: true },
    enrollments: { type: Object, required: true },
    certificates: { type: Array, default: () => [] },
    trainings: { type: Array, default: () => [] },
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

const typeLabels = { staff: 'Staff', apprentice: 'Apprentice', trainee: 'Trainee' };
const initials = (props.trainee?.name || 'T').split(' ').map((p) => p.charAt(0)).join('').slice(0, 2).toUpperCase();

const enrollForm = useForm({
    training_id: '',
    enrolled_at: new Date().toISOString().slice(0, 10),
});

function enroll() {
    enrollForm.post(route('admin.trainees.enroll', props.trainee.id));
}

const progressForms = reactive({});
const availableTrainings = computed(() => {
    const enrolledIds = new Set(props.enrollments.data.map((e) => e.training.id));
    return props.trainings.filter((t) => !enrolledIds.has(t.id));
});

function progressForm(e) {
    if (!progressForms[e.id]) {
        progressForms[e.id] = useForm({
            progress: e.progress,
            grade: e.grade ?? '',
        });
    }
    return progressForms[e.id];
}

function saveProgress(e) {
    const f = progressForms[e.id];
    f.post(route('admin.enrollments.progress', e.id));
}

function withdraw(e) {
    if (!confirm(`Withdraw ${props.trainee.name} from this program?`)) return;
    router.post(route('admin.enrollments.withdraw', e.id));
}

function complete(e) {
    if (!confirm(`Mark ${props.trainee.name} as qualified for "${e.training.title}"? They'll be able to download their certificate.`)) return;
    router.post(route('admin.enrollments.complete', e.id));
}

function removeEnrollment(e) {
    if (!confirm('Remove this enrollment record permanently?')) return;
    router.delete(route('admin.enrollments.destroy', e.id));
}

const formatDate = (d) => (d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
</script>

<template>
    <FlashMessages />
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0D1527] text-sm font-black text-yellow-400">{{ initials }}</div>
            <div>
                <h1 class="text-xl font-black uppercase tracking-tight text-[#0D1527] sm:text-2xl">{{ trainee.name }}</h1>
                <p class="text-sm text-slate-500">{{ trainee.ref_id }} · {{ typeLabels[trainee.type] }} · {{ trainee.status }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <Link v-if="canManage" :href="`/admin/trainees/${trainee.id}/edit`" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i class="bi bi-pencil-square"></i> Edit
            </Link>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Main column -->
        <div class="space-y-4 lg:col-span-2">
            <!-- Enroll form -->
            <div v-if="canManage" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h2 class="text-sm font-black uppercase tracking-tight text-slate-900">Enrol in a program</h2>
                <form @submit.prevent="enroll" class="mt-3 flex flex-col gap-3 sm:flex-row">
                    <select v-model="enrollForm.training_id" required class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20">
                        <option value="" disabled>Select a program...</option>
                        <option v-for="t in availableTrainings" :key="t.id" :value="t.id">{{ t.title }} ({{ t.ref_id }})</option>
                    </select>
                    <input v-model="enrollForm.enrolled_at" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                    <button type="submit" :disabled="enrollForm.processing || !availableTrainings.length" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-bold text-white hover:bg-[#0D1527]/90 disabled:cursor-not-allowed disabled:opacity-50">
                        <i class="bi bi-plus-lg"></i> Enrol
                    </button>
                </form>
                <InputError class="mt-2" :message="enrollForm.errors.training_id" />
                <p v-if="!availableTrainings.length" class="mt-2 text-xs text-slate-400">Trainee is enrolled in all active programs.</p>
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
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Program</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Progress</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Certificate</th>
                                <th v-if="canManage" class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="e in enrollments.data" :key="e.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ e.training.title }}</p>
                                    <p class="text-xs text-slate-400">{{ e.ref_id }} · Enrolled {{ formatDate(e.enrolled_at) }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="badgeClass(statusStyles[e.status] || 'bg-slate-100 text-slate-600')" class="capitalize">{{ statusLabel(e.status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <template v-if="canManage && e.status !== 'completed' && e.status !== 'withdrawn'">
                                        <form @submit.prevent="saveProgress(e)" class="flex items-center justify-center gap-1.5">
                                            <input v-model="progressForm(e).progress" type="number" min="0" max="100" class="w-20 rounded-lg border-slate-300 px-2 py-1 text-center text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                                            <input v-model="progressForm(e).grade" type="number" min="0" max="100" step="0.01" placeholder="grade" title="Grade %" class="w-16 rounded-lg border-slate-300 px-2 py-1 text-center text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                                            <button type="submit" title="Save progress" class="rounded-md bg-[#0D1527] px-2 py-1 text-xs font-bold text-white hover:bg-[#0D1527]/90">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <p v-if="e.progress >= 100" class="mt-1 text-[10px] font-semibold text-emerald-600">Completing issues a certificate</p>
                                    </template>
                                    <span v-else class="font-semibold text-slate-800">
                                        {{ e.progress }}%
                                        <span v-if="e.grade !== null && e.grade !== undefined" class="ml-1 text-xs text-slate-400">· {{ e.grade }}%</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="e.certificate_no" class="font-mono text-xs font-semibold text-emerald-600">{{ e.certificate_no }}</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td v-if="canManage" class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button
                                            v-if="e.status !== 'completed' && e.status !== 'withdrawn'"
                                            @click="complete(e)"
                                            title="Mark as qualified and issue certificate"
                                            class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-bold text-white hover:bg-emerald-700"
                                        >
                                            <i class="bi bi-award"></i> Mark Qualified
                                        </button>
                                        <button v-if="e.status !== 'completed' && e.status !== 'withdrawn'" @click="withdraw(e)" title="Withdraw" class="rounded-md px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                        <button @click="removeEnrollment(e)" title="Delete record" class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!enrollments.data.length">
                                <td :colspan="canManage ? 5 : 4" class="px-4 py-10 text-center text-sm text-slate-400">No enrollments yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :meta="enrollments" />
                </div>
            </div>

            <!-- Certificates -->
            <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-tight text-slate-900">Certificates</h2>
                    <span class="text-xs text-slate-400">{{ certificates.length }}</span>
                </div>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Certificate No.</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Program</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Issued</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in certificates" :key="c.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800">{{ c.certificate_no }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ c.training.title }}</td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ formatDate(c.issued_at) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="badgeClass(c.status === 'void' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800')" class="capitalize">{{ c.status }}</span>
                            </td>
                        </tr>
                        <tr v-if="!certificates.length">
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">No certificates issued yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Contact</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div><dt class="text-slate-400 text-xs">Email</dt><dd class="font-medium text-slate-800">{{ trainee.email || '—' }}</dd></div>
                    <div><dt class="text-slate-400 text-xs">Phone</dt><dd class="font-medium text-slate-800">{{ trainee.phone || '—' }}</dd></div>
                    <div><dt class="text-slate-400 text-xs">City</dt><dd class="font-medium text-slate-800">{{ trainee.city || '—' }}</dd></div>
                    <div><dt class="text-slate-400 text-xs">Address</dt><dd class="font-medium text-slate-800">{{ trainee.address || '—' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Personal details</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div><dt class="text-slate-400 text-xs">Date of birth</dt><dd class="font-medium text-slate-800">{{ formatDate(trainee.date_of_birth) }}</dd></div>
                    <div><dt class="text-slate-400 text-xs">Gender</dt><dd class="font-medium capitalize text-slate-800">{{ trainee.gender || '—' }}</dd></div>
                    <div><dt class="text-slate-400 text-xs">Education</dt><dd class="font-medium text-slate-800">{{ trainee.education || '—' }}</dd></div>
                    <div><dt class="text-slate-400 text-xs">Occupation</dt><dd class="font-medium text-slate-800">{{ trainee.occupation || '—' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Emergencies</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div><dt class="text-slate-400 text-xs">Contact</dt><dd class="font-medium text-slate-800">{{ trainee.emergency_contact_name || '—' }}</dd></div>
                    <div><dt class="text-slate-400 text-xs">Phone</dt><dd class="font-medium text-slate-800">{{ trainee.emergency_contact_phone || '—' }}</dd></div>
                </dl>
            </div>
            <div v-if="trainee.notes" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Notes</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ trainee.notes }}</p>
            </div>
        </div>
    </div>
</template>