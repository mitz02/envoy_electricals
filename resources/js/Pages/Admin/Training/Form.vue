<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import InputError from '@/Components/InputError.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    training: { type: Object, default: null },
    weeks: { type: Array, default: () => [] },
});

const isEditing = !!props.training;

const tabs = [
    { key: 'overview', label: 'Overview', icon: 'bi-sliders2' },
    { key: 'description', label: 'Program content', icon: 'bi-file-earmark-text' },
    { key: 'curriculum', label: 'Curriculum', icon: 'bi-diagram-3' },
];
const activeTab = ref('overview');

const mappedWeeks = props.weeks.length
    ? props.weeks.map((w) => ({
          week_number: w.week_number,
          title: w.title,
          summary: w.summary || '',
          lessons: (w.lessons || []).map((l) => ({
              title: l.title,
              description: l.description || '',
              objectives: l.objectives || '',
              duration_minutes: l.duration_minutes || '',
          })),
      }))
    : [];

const form = useForm({
    title: props.training?.title || '',
    level: props.training?.level || 'beginner',
    duration_weeks: props.training?.duration_weeks ?? 4,
    price: props.training?.price ?? 0,
    capacity: props.training?.capacity ?? '',
    start_date: props.training?.start_date || '',
    image_path: props.training?.image_path || '',
    is_active: props.training?.is_active ?? true,
    certificate_eligible: props.training?.certificate_eligible ?? true,
    is_featured: props.training?.is_featured ?? false,
    description: props.training?.description || '',
    objectives: props.training?.objectives || '',
    learning_outcomes: props.training?.learning_outcomes || '',
    prerequisites: props.training?.prerequisites || '',
    weeks: mappedWeeks,
});

function submit() {
    if (isEditing) {
        form.put(route('admin.training.update', props.training.id));
    } else {
        form.post(route('admin.training.store'));
    }
}

/* ---------- Curriculum builder ---------- */
function addWeek() {
    form.weeks.push({ week_number: form.weeks.length + 1, title: '', summary: '', lessons: [] });
}

function removeWeek(index) {
    form.weeks.splice(index, 1);
    form.weeks.forEach((w, i) => (w.week_number = i + 1));
}

function moveWeek(index, dir) {
    const target = index + dir;
    if (target < 0 || target >= form.weeks.length) return;
    const [w] = form.weeks.splice(index, 1);
    form.weeks.splice(target, 0, w);
    form.weeks.forEach((week, i) => (week.week_number = i + 1));
}

function addLesson(week) {
    week.lessons.push({ title: '', description: '', objectives: '', duration_minutes: '' });
}

function removeLesson(week, index) {
    week.lessons.splice(index, 1);
}

function moveLesson(week, index, dir) {
    const target = index + dir;
    if (target < 0 || target >= week.lessons.length) return;
    const [l] = week.lessons.splice(index, 1);
    week.lessons.splice(target, 0, l);
}

const inputClass = 'w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20';
</script>

<template>
    <FlashMessages />
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-black uppercase tracking-tight text-[#0D1527] sm:text-2xl">{{ isEditing ? 'Edit Program' : 'New Training Program' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ isEditing ? training.ref_id : 'Create a program trainees can enrol in — with full details and curriculum.' }}</p>
        </div>
        <Link href="/admin/training" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
            <i class="bi bi-arrow-left"></i> Back
        </Link>
    </div>

    <form @submit.prevent="submit" class="max-w-4xl">
        <!-- Tabs -->
        <div class="mb-5 flex flex-wrap gap-2">
            <button
                v-for="t in tabs"
                :key="t.key"
                type="button"
                @click="activeTab = t.key"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition-all"
                :class="activeTab === t.key ? 'bg-[#0D1527] text-white shadow-md' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            >
                <i :class="`bi ${t.icon}`"></i> {{ t.label }}
                <span v-if="t.key === 'curriculum' && form.weeks.length" class="rounded-full px-1.5 text-[10px]" :class="activeTab === 'curriculum' ? 'bg-yellow-400 text-[#0D1527]' : 'bg-slate-100 text-slate-500'">
                    {{ form.weeks.length }}
                </span>
            </button>
        </div>

        <!-- ===== Overview ===== -->
        <div v-if="activeTab === 'overview'" class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Program title *</label>
                <input v-model="form.title" type="text" required placeholder="e.g. Solar PV Installation Masterclass" :class="inputClass" />
                <InputError class="mt-1.5" :message="form.errors.title" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Level</label>
                    <select v-model="form.level" :class="inputClass">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <InputError class="mt-1.5" :message="form.errors.level" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Duration (weeks)</label>
                    <input v-model="form.duration_weeks" type="number" min="1" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.duration_weeks" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Price (₦)</label>
                    <input v-model="form.price" type="number" min="0" step="0.01" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.price" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Capacity</label>
                    <input v-model="form.capacity" type="number" min="1" placeholder="Unlimited" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.capacity" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Start date <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                    <input v-model="form.start_date" type="date" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.start_date" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Cover image URL <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                    <input v-model="form.image_path" type="url" placeholder="https://..." :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.image_path" />
                </div>
            </div>

            <div class="grid gap-4 rounded-xl bg-slate-50 p-4 sm:grid-cols-3">
                <label class="flex items-center gap-2.5">
                    <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded accent-yellow-500" />
                    <span class="text-sm font-medium text-slate-700">Open for enrollment</span>
                </label>
                <label class="flex items-center gap-2.5">
                    <input v-model="form.is_featured" type="checkbox" class="h-4 w-4 rounded accent-yellow-500" />
                    <span class="text-sm font-medium text-slate-700">Featured on website</span>
                </label>
                <label class="flex items-center gap-2.5">
                    <input v-model="form.certificate_eligible" type="checkbox" class="h-4 w-4 rounded accent-yellow-500" />
                    <span class="text-sm font-medium text-slate-700">Issues certificate</span>
                </label>
            </div>
        </div>

        <!-- ===== Program content ===== -->
        <div v-if="activeTab === 'description'" class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Short description</label>
                <textarea v-model="form.description" rows="3" placeholder="What does this program cover? Shown on cards and the public page." :class="inputClass"></textarea>
                <InputError class="mt-1.5" :message="form.errors.description" />
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Objectives <span class="font-normal normal-case text-slate-400">(one per line)</span></label>
                <textarea v-model="form.objectives" rows="4" placeholder="e.g.&#10;Understand solar PV fundamentals&#10;Size a residential system safely" :class="inputClass"></textarea>
                <InputError class="mt-1.5" :message="form.errors.objectives" />
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Learning outcomes <span class="font-normal normal-case text-slate-400">(one per line)</span></label>
                <textarea v-model="form.learning_outcomes" rows="4" placeholder="e.g.&#10;Design and install a grid-tied solar system&#10;Troubleshoot common inverter faults" :class="inputClass"></textarea>
                <InputError class="mt-1.5" :message="form.errors.learning_outcomes" />
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Prerequisites <span class="font-normal normal-case text-slate-400">(one per line)</span></label>
                <textarea v-model="form.prerequisites" rows="3" placeholder="e.g.&#10;Basic understanding of electricity&#10;Interest in renewable energy" :class="inputClass"></textarea>
                <InputError class="mt-1.5" :message="form.errors.prerequisites" />
            </div>
        </div>

        <!-- ===== Curriculum builder ===== -->
        <div v-if="activeTab === 'curriculum'" class="space-y-5 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-tight text-slate-900">Curriculum by week</h2>
                    <p class="mt-1 text-xs text-slate-500">Break the program into weekly modules, each with lessons, objectives and duration.</p>
                </div>
                <button type="button" @click="addWeek" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-bold text-white hover:bg-[#0D1527]/90">
                    <i class="bi bi-plus-lg"></i> Add week
                </button>
            </div>

            <div v-if="!form.weeks.length" class="rounded-xl border-2 border-dashed border-slate-200 p-10 text-center">
                <i class="bi bi-diagram-3 text-3xl text-slate-300"></i>
                <p class="mt-2 text-sm font-semibold text-slate-500">No weeks yet</p>
                <p class="mt-1 text-xs text-slate-400">Add your first week to start building the curriculum.</p>
            </div>

            <div v-for="(week, wi) in form.weeks" :key="wi" class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0D1527] text-xs font-black text-yellow-400">W{{ week.week_number || wi + 1 }}</span>
                        <h3 class="text-sm font-black text-slate-800">{{ week.title || `Week ${wi + 1}` }}</h3>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="moveWeek(wi, -1)" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700" title="Move up"><i class="bi bi-chevron-up"></i></button>
                        <button type="button" @click="moveWeek(wi, 1)" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700" title="Move down"><i class="bi bi-chevron-down"></i></button>
                        <button type="button" @click="removeWeek(wi)" class="rounded-md p-1.5 text-red-400 hover:bg-red-50 hover:text-red-600" title="Remove week"><i class="bi bi-trash3"></i></button>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-[80px_1fr]">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Week no.</label>
                        <input v-model="week.week_number" type="number" min="1" :class="inputClass" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Week title</label>
                        <input v-model="week.title" type="text" required placeholder="e.g. Solar fundamentals & safety" :class="inputClass" />
                    </div>
                </div>

                <div class="mt-3">
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Week summary <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                    <textarea v-model="week.summary" rows="2" placeholder="What this week covers..." :class="inputClass"></textarea>
                </div>

                <!-- Lessons -->
                <div class="mt-5 space-y-3">
                    <div class="mb-1 flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Lessons ({{ week.lessons.length }})</p>
                        <button type="button" @click="addLesson(week)" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-white">
                            <i class="bi bi-plus-lg"></i> Add lesson
                        </button>
                    </div>

                    <div v-for="(lesson, li) in week.lessons" :key="li" class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="inline-flex items-center gap-1.5 text-xs font-black text-slate-600">
                                <i class="bi bi-play-circle text-[#40e0d0]"></i> Lesson {{ li + 1 }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="moveLesson(week, li, -1)" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-chevron-up"></i></button>
                                <button type="button" @click="moveLesson(week, li, 1)" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-chevron-down"></i></button>
                                <button type="button" @click="removeLesson(week, li)" class="rounded-md p-1 text-red-400 hover:bg-red-50 hover:text-red-600"><i class="bi bi-trash3"></i></button>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_150px]">
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Lesson title *</label>
                                <input v-model="lesson.title" type="text" required placeholder="e.g. Reading single-line diagrams" :class="inputClass" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Duration (mins)</label>
                                <input v-model="lesson.duration_minutes" type="number" min="1" :class="inputClass" />
                            </div>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">What's covered <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                                <textarea v-model="lesson.description" rows="2" placeholder="Topics, demonstrations, exercises..." :class="inputClass"></textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Learning objectives <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                                <textarea v-model="lesson.objectives" rows="2" placeholder="e.g. Identify cable sizing requirements" :class="inputClass"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="rounded-xl bg-slate-50 p-3.5 text-xs leading-relaxed text-slate-500">
                <i class="bi bi-info-circle mr-1 text-[#40e0d0]"></i>
                This curriculum appears on the public program page and the admin program detail. Weeks are ordered automatically by week number.
            </p>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex items-center justify-between gap-3">
            <button type="button" @click="form.reset()" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Reset
            </button>
            <div class="flex items-center gap-3">
                <span v-if="activeTab !== 'curriculum'" class="text-xs text-slate-400">Tip: add the curriculum on the next tab.</span>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-6 py-2.5 text-sm font-bold text-white hover:bg-[#0D1527]/90 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <i v-if="form.processing" class="bi bi-arrow-clockwise animate-spin"></i>
                    <i v-else :class="isEditing ? 'bi bi-check2-square' : 'bi bi-plus-lg'"></i>
                    {{ isEditing ? 'Save Changes' : 'Create Program' }}
                </button>
            </div>
        </div>
    </form>
</template>