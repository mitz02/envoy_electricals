<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    media: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search ?? '',
    category: props.filters.category ?? '',
});

const uploadForm = useForm({ file: null, category: '' });
const fileInput = ref(null);
const editingMedia = ref(null);
const detailForm = useForm({ name: '', caption: '', alt: '', category: '' });

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.category) p.set('category', form.category);
    window.location.search = p.toString();
}

function onFileChange(e) {
    uploadForm.file = e.target.files[0] || null;
    if (uploadForm.file) {
        uploadForm.post('/admin/media', {
            preserveScroll: true,
            onSuccess: () => {
                uploadForm.reset();
                if (fileInput.value) fileInput.value.value = '';
            },
        });
    }
}

function chooseFile() {
    fileInput.value?.click();
}

function openDetail(m) {
    editingMedia.value = m;
    detailForm.name = m.name;
    detailForm.caption = m.caption ?? '';
    detailForm.alt = m.alt ?? '';
    detailForm.category = m.category ?? '';
}

function saveDetail() {
    detailForm.put(`/admin/media/${editingMedia.value.id}`, {
        preserveScroll: true,
        onSuccess: () => (editingMedia.value = null),
    });
}

function remove(m) {
    if (!confirm(`Remove "${m.name}" from the media library?`)) {
        return;
    }
    uploadForm.delete(`/admin/media/${m.id}`, { preserveScroll: true });
}

function mediaUrl(m) {
    const base = window.location.origin;
    return `${base}/storage/${m.path}`;
}

function isImage(m) {
    return m.type === 'image';
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Media Library"
        subtitle="Images and files used across the website."
    />

    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input v-model="form.search" type="search" placeholder="Search media…" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.category" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
            <option value="">All categories</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <input ref="fileInput" type="file" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx" class="hidden" @change="onFileChange" />
    <button class="mb-4 rounded-2xl border-2 border-dashed border-slate-300 bg-white px-4 py-6 text-sm font-semibold text-slate-500 hover:border-amber-400 hover:text-amber-600 transition-colors w-full flex items-center justify-center gap-2" @click="chooseFile">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Upload a file (image, video, PDF, spreadsheet)
    </button>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <div
            v-for="m in media.data"
            :key="m.id"
            class="group overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-all hover:border-amber-200 hover:shadow-md"
        >
            <div class="flex h-32 items-center justify-center overflow-hidden bg-slate-50">
                <img v-if="isImage(m)" :src="mediaUrl(m)" :alt="m.name" class="h-full w-full object-cover" />
                <div v-else class="flex flex-col items-center gap-1 text-slate-400">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <span class="text-[10px] font-medium uppercase">{{ m.type }}</span>
                </div>
            </div>
            <div class="p-2.5">
                <p class="truncate text-xs font-semibold text-slate-800">{{ m.name }}</p>
                <p class="text-[10px] text-slate-400">{{ formatDate(m.created_at) }}<span v-if="m.uploader"> · {{ m.uploader.name }}</span></p>
                <div class="mt-1.5 flex gap-1">
                    <button class="rounded-md px-1.5 py-0.5 text-[11px] font-medium text-slate-600 hover:bg-slate-100" @click="openDetail(m)">Edit</button>
                    <a :href="mediaUrl(m)" target="_blank" class="rounded-md px-1.5 py-0.5 text-[11px] font-medium text-slate-600 hover:bg-slate-100">View</a>
                    <button class="rounded-md px-1.5 py-0.5 text-[11px] font-medium text-red-600 hover:bg-red-50" @click="remove(m)">Delete</button>
                </div>
            </div>
        </div>

        <div v-if="!media.data.length" class="col-span-full py-16 text-center text-sm text-slate-400">No media files found.</div>
    </div>

    <div class="mt-4">
        <Pagination :meta="media" />
    </div>

    <!-- Edit detail modal -->
    <div v-if="editingMedia" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" @click.self="editingMedia = null">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <div v-if="isImage(editingMedia)" class="mb-4 flex h-40 items-center justify-center overflow-hidden rounded-xl bg-slate-50">
                <img :src="mediaUrl(editingMedia)" :alt="editingMedia.name" class="h-full w-full object-cover" />
            </div>
            <h2 class="text-lg font-bold text-slate-900">Edit Media</h2>
            <form class="mt-4 space-y-3" @submit.prevent="saveDetail">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Name</label>
                    <input v-model="detailForm.name" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Caption</label>
                    <input v-model="detailForm.caption" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Alt text</label>
                    <input v-model="detailForm.alt" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Category</label>
                    <input v-model="detailForm.category" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" placeholder="e.g. Gallery, Hero, Products" />
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="detailForm.processing" class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">Save</button>
                    <button type="button" class="text-sm text-slate-500 hover:text-slate-900" @click="editingMedia = null">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</template>