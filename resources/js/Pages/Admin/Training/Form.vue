<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
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
    cover_file: null,
    is_active: props.training?.is_active ?? true,
    certificate_eligible: props.training?.certificate_eligible ?? true,
    is_featured: props.training?.is_featured ?? false,
    description: props.training?.description || '',
    objectives: props.training?.objectives || '',
    learning_outcomes: props.training?.learning_outcomes || '',
    prerequisites: props.training?.prerequisites || '',
    weeks: mappedWeeks,
});

renumberWeeks();

function submit() {
    if (isEditing) {
        form.put(route('admin.training.update', props.training.id));
    } else {
        form.post(route('admin.training.store'));
    }
}

/* ---------- Curriculum builder ---------- */
const draggedWeek = ref(null);
const draggedLesson = ref(null);
const dropWeek = ref(null);
const dropLesson = ref(null);

const totalLessons = computed(() => form.weeks.reduce((n, w) => n + w.lessons.length, 0));

function renumberWeeks() {
    form.weeks.forEach((w, i) => (w.week_number = i + 1));
}

function addWeek() {
    form.weeks.push({ week_number: form.weeks.length + 1, title: '', summary: '', lessons: [] });
}

function removeWeek(index) {
    form.weeks.splice(index, 1);
    renumberWeeks();
}

function clearDrag() {
    draggedWeek.value = null;
    draggedLesson.value = null;
    dropWeek.value = null;
    dropLesson.value = null;
}

function moveInArray(arr, from, to) {
    if (from === to) return;
    const [item] = arr.splice(from, 1);
    arr.splice(to, 0, item);
}

/* ----- Module (week) drag & drop ----- */
function onWeekDragStart(index, e) {
    draggedWeek.value = index;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(index));
}

function onWeekDragOver(index, e) {
    if (draggedWeek.value === null) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const rect = e.currentTarget.getBoundingClientRect();
    dropWeek.value = { index, before: e.clientY < rect.top + rect.height / 2 };
}

function onWeekDrop(index, e) {
    if (draggedWeek.value === null) return;
    e.preventDefault();
    const target = dropWeek.value;
    let to = index;
    if (target && target.index === index) to = target.before ? index : index + 1;
    moveInArray(form.weeks, draggedWeek.value, to);
    renumberWeeks();
    clearDrag();
}

/* ----- Lesson drag & drop (within and across modules) ----- */
function onLessonDragStart(weekIndex, lessonIndex, e) {
    draggedLesson.value = { wi: weekIndex, li: lessonIndex };
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', `${weekIndex}:${lessonIndex}`);
}

function onLessonOver(weekIndex, lessonIndex, e) {
    if (!draggedLesson.value) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const rect = e.currentTarget.getBoundingClientRect();
    dropLesson.value = { wi: weekIndex, li: lessonIndex, before: e.clientY < rect.top + rect.height / 2 };
}

function onLessonAreaOver(weekIndex, e) {
    if (!draggedLesson.value) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    dropLesson.value = { wi: weekIndex, li: form.weeks[weekIndex].lessons.length, before: true };
}

function onLessonDrop(weekIndex, lessonIndex, e) {
    if (!draggedLesson.value) return;
    e.preventDefault();
    const t = dropLesson.value && dropLesson.value.wi === weekIndex
        ? dropLesson.value
        : { wi: weekIndex, li: lessonIndex, before: false };
    commitLessonMove(t.wi, t.li, t.before);
    clearDrag();
}

function onLessonAreaDrop(weekIndex, e) {
    if (!draggedLesson.value) return;
    e.preventDefault();
    const li = form.weeks[weekIndex].lessons.length;
    const t = dropLesson.value && dropLesson.value.wi === weekIndex
        ? dropLesson.value
        : { wi: weekIndex, li, before: true };
    commitLessonMove(t.wi, t.li, t.before);
    clearDrag();
}

function commitLessonMove(targetWeekIndex, targetLessonIndex, before) {
    const dl = draggedLesson.value;
    if (!dl) return;
    let to = before ? targetLessonIndex : targetLessonIndex + 1;
    const sourceWeek = form.weeks[dl.wi];
    const [lesson] = sourceWeek.lessons.splice(dl.li, 1);
    const targetWeek = form.weeks[targetWeekIndex];
    if (dl.wi === targetWeekIndex && dl.li < to) to -= 1;
    to = Math.max(0, Math.min(to, targetWeek.lessons.length));
    targetWeek.lessons.splice(to, 0, lesson);
}

function addLesson(week) {
    week.lessons.push({ title: '', description: '', objectives: '', duration_minutes: '' });
}

function removeLesson(week, index) {
    week.lessons.splice(index, 1);
}

const inputClass = 'w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20';

/* ---------- Cover image upload ---------- */
const coverInput = ref(null);
const coverPreview = ref(null);
const isCoverDragOver = ref(false);

function setCoverFile(file) {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverPreview.value = URL.createObjectURL(file);
    form.cover_file = file;
}

function onCoverFile(e) {
    const file = e.target.files?.[0];
    if (file) setCoverFile(file);
}

function onCoverDrop(e) {
    const file = e.dataTransfer?.files?.[0];
    if (file) setCoverFile(file);
}

function clearCover() {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverPreview.value = null;
    form.cover_file = null;
    if (coverInput.value) coverInput.value.value = '';
}

function resetForm() {
    form.reset();
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverPreview.value = null;
    if (coverInput.value) coverInput.value.value = '';
}

onBeforeUnmount(() => {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
});
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
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Cover image <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div
                            class="relative flex h-36 w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed text-center transition sm:w-56"
                            :class="isCoverDragOver ? 'border-[#0D1527] bg-[#0D1527]/5' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                            @click="coverInput?.click()"
                            @dragover.prevent="isCoverDragOver = true"
                            @dragleave.prevent="isCoverDragOver = false"
                            @drop.prevent="onCoverDrop"
                        >
                            <img
                                v-if="coverPreview || form.image_path"
                                :src="coverPreview || form.image_path"
                                :alt="form.title || 'Cover preview'"
                                class="absolute inset-0 h-full w-full object-cover"
                            />
                            <template v-else>
                                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#0D1527]/5 text-[#0D1527]">
                                    <i class="bi bi-image text-xl"></i>
                                </span>
                                <p class="mt-2 text-xs font-medium text-slate-600">Click to upload cover</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">PNG, JPG or WEBP · up to 10MB</p>
                            </template>
                            <input
                                ref="coverInput"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="hidden"
                                @change="onCoverFile"
                            />
                        </div>
                        <div class="min-w-0 flex-1 space-y-2 pt-1">
                            <p class="truncate text-xs font-semibold text-slate-600">
                                <i :class="['bi mr-1', form.cover_file ? 'bi-paperclip text-[#40e0d0]' : form.image_path ? 'bi-check-circle text-emerald-500' : 'bi-circle text-slate-300']"></i>
                                {{ form.cover_file ? form.cover_file.name : form.image_path ? 'Cover saved — upload a new file to replace it' : 'No cover uploaded yet' }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-[#0D1527] hover:text-[#0D1527]"
                                    @click="coverInput?.click()"
                                >
                                    <i class="bi bi-upload"></i> {{ form.cover_file ? 'Replace' : 'Choose file' }}
                                </button>
                                <button
                                    v-if="form.cover_file"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:border-red-200 hover:text-red-600"
                                    @click="clearCover"
                                >
                                    <i class="bi bi-x-lg"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <InputError class="mt-1.5" :message="form.errors.cover_file" />
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
        <div v-if="activeTab === 'curriculum'" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-tight text-slate-900">Curriculum builder</h2>
                    <p class="mt-1 text-xs text-slate-500">Organize the course into modules, each with lessons, objectives and duration.</p>
                </div>
                <button type="button" @click="addWeek" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-bold text-white hover:bg-[#0D1527]/90">
                    <i class="bi bi-plus-lg"></i> Add module
                </button>
            </div>

            <div v-if="form.weeks.length" class="mb-5 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#0D1527]/5 px-3 py-1 text-xs font-bold text-[#0D1527]">
                    <i class="bi bi-collection"></i> {{ form.weeks.length }} module{{ form.weeks.length === 1 ? '' : 's' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#40e0d0]/10 px-3 py-1 text-xs font-bold text-slate-700">
                    <i class="bi bi-play-circle"></i> {{ totalLessons }} lesson{{ totalLessons === 1 ? '' : 's' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                    <i class="bi bi-arrow-down-up"></i> Drag to reorder
                </span>
            </div>

            <div v-if="!form.weeks.length" class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/60 p-12 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0D1527]/5 text-[#0D1527]"><i class="bi bi-diagram-3 text-2xl"></i></span>
                <p class="mt-4 text-sm font-black text-slate-700">No modules yet</p>
                <p class="mt-1 text-xs text-slate-400">Add your first module to start building the curriculum.</p>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(week, wi) in form.weeks"
                    :key="wi"
                    class="relative rounded-2xl border border-slate-200 bg-[#FAF8F2]/60 p-5 transition"
                    :class="draggedWeek === wi ? 'opacity-40' : ''"
                    @dragover="onWeekDragOver(wi, $event)"
                    @drop="onWeekDrop(wi, $event)"
                >
                    <span v-if="dropWeek && dropWeek.index === wi && dropWeek.before" class="absolute -top-1.5 left-4 right-4 z-10 h-0.5 rounded-full bg-[#40e0d0]"></span>
                    <span v-if="dropWeek && dropWeek.index === wi && !dropWeek.before" class="absolute -bottom-1.5 left-4 right-4 z-10 h-0.5 rounded-full bg-[#40e0d0]"></span>

                    <div class="flex items-start gap-3">
                        <span
                            draggable="true"
                            @dragstart="onWeekDragStart(wi, $event)"
                            @dragend="clearDrag"
                            class="mt-1 cursor-grab rounded-md px-1 py-0.5 text-slate-300 hover:text-slate-500 active:cursor-grabbing"
                            title="Drag to reorder module"
                        >
                            <i class="bi bi-grip-vertical text-xl"></i>
                        </span>
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#0D1527] text-sm font-black text-yellow-400">{{ String(wi + 1).padStart(2, '0') }}</span>
                        <div class="min-w-0 flex-1">
                            <input v-model="week.title" type="text" required placeholder="Module title — e.g. Solar fundamentals & safety" class="w-full border-0 bg-transparent p-0 text-base font-black text-slate-900 placeholder:font-medium placeholder:text-slate-300 focus:ring-0" />
                            <p class="mt-0.5 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">
                                <i class="bi bi-collection"></i> Module {{ wi + 1 }}<template v-if="week.lessons.length"> · {{ week.lessons.length }} lesson{{ week.lessons.length === 1 ? '' : 's' }}</template>
                            </p>
                        </div>
                        <button type="button" @click="removeWeek(wi)" class="mt-1 rounded-lg p-2 text-red-400 hover:bg-red-50 hover:text-red-600" title="Remove module"><i class="bi bi-trash3"></i></button>
                    </div>

                    <div class="mt-4">
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Module summary <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                        <textarea v-model="week.summary" rows="2" placeholder="What this module covers..." :class="inputClass"></textarea>
                    </div>

                    <div class="relative mt-4 space-y-3" @dragover="onLessonAreaOver(wi, $event)" @drop="onLessonAreaDrop(wi, $event)">
                        <span v-if="dropLesson && dropLesson.wi === wi && dropLesson.li === week.lessons.length && dropLesson.before" class="absolute -top-1.5 left-4 right-4 z-10 h-0.5 rounded-full bg-[#40e0d0]"></span>

                        <div class="mb-1 flex items-center justify-between">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Lessons ({{ week.lessons.length }})</p>
                            <button type="button" @click="addLesson(week)" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-white">
                                <i class="bi bi-plus-lg"></i> Add lesson
                            </button>
                        </div>

                        <div
                            v-for="(lesson, li) in week.lessons"
                            :key="li"
                            class="relative rounded-xl border border-slate-200 bg-white p-4 transition"
                            :class="draggedLesson && draggedLesson.wi === wi && draggedLesson.li === li ? 'opacity-40' : ''"
                            @dragover="onLessonOver(wi, li, $event)"
                            @drop="onLessonDrop(wi, li, $event)"
                        >
                            <span v-if="dropLesson && dropLesson.wi === wi && dropLesson.li === li && dropLesson.before" class="absolute -top-1.5 left-3 right-3 z-10 h-0.5 rounded-full bg-[#40e0d0]"></span>
                            <span v-if="dropLesson && dropLesson.wi === wi && dropLesson.li === li && !dropLesson.before" class="absolute -bottom-1.5 left-3 right-3 z-10 h-0.5 rounded-full bg-[#40e0d0]"></span>

                            <div class="flex items-center gap-2">
                                <span
                                    draggable="true"
                                    @dragstart="onLessonDragStart(wi, li, $event)"
                                    @dragend="clearDrag"
                                    class="cursor-grab rounded-md px-1 py-0.5 text-slate-300 hover:text-slate-500 active:cursor-grabbing"
                                    title="Drag to reorder lesson"
                                >
                                    <i class="bi bi-grip-vertical"></i>
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-xs font-black text-slate-600">
                                    <i class="bi bi-play-circle text-[#40e0d0]"></i> Lesson {{ li + 1 }}
                                </span>
                                <div class="flex-1"></div>
                                <button type="button" @click="removeLesson(week, li)" class="rounded-md p-1 text-red-400 hover:bg-red-50 hover:text-red-600" title="Remove lesson"><i class="bi bi-trash3"></i></button>
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
            </div>

            <p class="mt-5 rounded-xl bg-slate-50 p-3.5 text-xs leading-relaxed text-slate-500">
                <i class="bi bi-info-circle mr-1 text-[#40e0d0]"></i>
                Drag the <i class="bi bi-grip-vertical"></i> handles to reorganize modules and lessons — lessons can be dropped into other modules. Modules save automatically in the order shown here.
            </p>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex items-center justify-between gap-3">
            <button type="button" @click="resetForm" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
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