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
    testimonials: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({ all: 0, pending: 0, approved: 0, rejected: 0 }) },
});

const form = useForm({
    search: props.filters.search ?? '',
    published: props.filters.published ?? '',
    status: props.filters.status ?? '',
});

const actionForm = useForm({});
const selectedTestimonial = ref(null);

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.published !== '' && form.published !== undefined) p.set('published', form.published);
    if (form.status !== '' && form.status !== undefined) p.set('status', form.status);
    window.location.search = p.toString();
}

function setStatusFilter(status) {
    form.status = status;
    applyFilters();
}

function approve(t) {
    actionForm.post(`/admin/marketing/testimonials/${t.id}/approve`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedTestimonial.value?.id === t.id) {
                selectedTestimonial.value.status = 'approved';
                selectedTestimonial.value.is_published = true;
            }
        },
    });
}

function reject(t) {
    if (!confirm(`Reject testimonial from ${t.author_name}?`)) {
        return;
    }
    actionForm.post(`/admin/marketing/testimonials/${t.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedTestimonial.value?.id === t.id) {
                selectedTestimonial.value.status = 'rejected';
                selectedTestimonial.value.is_published = false;
            }
        },
    });
}

function toggle(t) {
    actionForm.post(`/admin/marketing/testimonials/${t.id}/toggle`, { preserveScroll: true });
}

function remove(t) {
    if (!confirm(`Remove testimonial from ${t.author_name}?`)) {
        return;
    }
    actionForm.delete(`/admin/marketing/testimonials/${t.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedTestimonial.value?.id === t.id) {
                selectedTestimonial.value = null;
            }
        },
    });
}

function copySubmissionLink() {
    const url = window.location.origin + '/testimonial/submit';
    navigator.clipboard.writeText(url);
    alert('Public testimonial submission link copied to clipboard!\n\n' + url);
}

function stars(rating) {
    return '★'.repeat(rating || 0) + '☆'.repeat(Math.max(0, 5 - (rating || 0)));
}

function statusBadge(status) {
    const badges = {
        pending: 'bg-amber-100 text-amber-800 border-amber-200',
        approved: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        rejected: 'bg-rose-100 text-rose-800 border-rose-200',
    };
    return badges[status] || 'bg-slate-100 text-slate-500 border-slate-200';
}

function openDetail(t) {
    selectedTestimonial.value = t;
}

function closeDetail() {
    selectedTestimonial.value = null;
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Testimonials"
        subtitle="Manage, review, approve, and publish customer testimonials on the website."
        action-href="/admin/marketing/testimonials/create"
        action-label="Add Testimonial"
    />

    <!-- Status Tabs & Submission Link -->
    <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <!-- Status filter tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200/80 overflow-x-auto max-w-full">
            <button
                type="button"
                @click="setStatusFilter('')"
                :class="[
                    'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5',
                    form.status === '' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                ]"
            >
                All
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700 font-bold">
                    {{ counts.all ?? testimonials.total ?? 0 }}
                </span>
            </button>

            <button
                type="button"
                @click="setStatusFilter('pending')"
                :class="[
                    'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5',
                    form.status === 'pending' ? 'bg-amber-400 text-slate-950 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'
                ]"
            >
                Pending Review
                <span
                    :class="[
                        'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                        (counts.pending ?? 0) > 0 ? 'bg-amber-600 text-white' : 'bg-slate-200 text-slate-700'
                    ]"
                >
                    {{ counts.pending ?? 0 }}
                </span>
            </button>

            <button
                type="button"
                @click="setStatusFilter('approved')"
                :class="[
                    'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5',
                    form.status === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
                ]"
            >
                Approved
                <span :class="['px-1.5 py-0.2 rounded-full text-[10px] font-bold', form.status === 'approved' ? 'bg-emerald-800 text-white' : 'bg-slate-200 text-slate-700']">
                    {{ counts.approved ?? 0 }}
                </span>
            </button>

            <button
                type="button"
                @click="setStatusFilter('rejected')"
                :class="[
                    'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5',
                    form.status === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
                ]"
            >
                Rejected
                <span :class="['px-1.5 py-0.2 rounded-full text-[10px] font-bold', form.status === 'rejected' ? 'bg-rose-800 text-white' : 'bg-slate-200 text-slate-700']">
                    {{ counts.rejected ?? 0 }}
                </span>
            </button>
        </div>

        <!-- Copy Submission Link Button -->
        <button
            type="button"
            @click="copySubmissionLink"
            class="rounded-lg bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2 border border-slate-200 shadow-2xs transition-colors shrink-0"
            title="Copy public testimonial submission link to share with customers"
        >
            <i class="bi bi-link-45deg text-base text-amber-500" />
            Copy Public Submit Link
        </button>
    </div>

    <!-- Filters Bar -->
    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input
            v-model="form.search"
            type="search"
            placeholder="Search author, role, or content…"
            class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20"
            @keyup.enter="applyFilters"
        />
        <select v-model="form.published" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
            <option value="">All Visibility</option>
            <option value="1">Published</option>
            <option value="0">Hidden</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors" @click="applyFilters">
            Filter
        </button>
    </div>

    <!-- Pending Banner if any are pending -->
    <div v-if="(counts.pending ?? 0) > 0 && form.status !== 'pending'" class="mb-4 rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 flex items-center justify-between gap-3 text-amber-900">
        <div class="flex items-center gap-2.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-400 text-slate-950 font-bold text-xs">!</span>
            <p class="text-xs sm:text-sm font-medium">
                You have <span class="font-bold">{{ counts.pending }}</span> pending customer testimonial{{ counts.pending > 1 ? 's' : '' }} waiting for review.
            </p>
        </div>
        <button
            type="button"
            @click="setStatusFilter('pending')"
            class="px-3 py-1 rounded-lg bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold transition-colors shrink-0 shadow-xs"
        >
            Review Now →
        </button>
    </div>

    <!-- Testimonials Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Author</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Testimonial</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Rating</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Status & Approval</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Visibility</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Submitted</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="t in testimonials.data" :key="t.id" :class="['transition-colors hover:bg-slate-50/80', t.status === 'pending' ? 'bg-amber-50/30' : '']">
                        <!-- Author with Avatar -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="relative w-10 h-10 rounded-full overflow-hidden border border-slate-200 shrink-0 bg-slate-100 flex items-center justify-center">
                                    <img v-if="t.photo_path" :src="`/storage/${t.photo_path}`" :alt="t.author_name" class="w-full h-full object-cover" />
                                    <span v-else class="text-xs font-bold text-slate-500 uppercase">
                                        {{ t.author_name ? t.author_name.charAt(0) : '?' }}
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 truncate">{{ t.author_name }}</p>
                                    <p class="text-xs text-slate-400 truncate">{{ t.author_role || 'Customer' }}</p>
                                    <p v-if="t.email" class="text-[11px] text-slate-400 truncate">{{ t.email }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Testimonial Content (Clickable) -->
                        <td class="px-4 py-3 max-w-xs">
                            <p
                                @click="openDetail(t)"
                                class="truncate text-slate-700 hover:text-amber-700 cursor-pointer font-normal"
                                :title="t.content"
                            >
                                "{{ t.content }}"
                            </p>
                            <button
                                type="button"
                                @click="openDetail(t)"
                                class="text-[11px] text-slate-400 hover:text-slate-600 font-medium inline-block mt-0.5"
                            >
                                Read full →
                            </button>
                        </td>

                        <!-- Rating Stars -->
                        <td class="px-4 py-3 text-center">
                            <span class="text-amber-500 tracking-wider text-xs">{{ stars(t.rating) }}</span>
                            <span class="text-[11px] text-slate-400 block font-medium">{{ t.rating }}/5</span>
                        </td>

                        <!-- Status & Quick Approval Buttons -->
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex flex-col items-center gap-1.5">
                                <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-bold border uppercase tracking-wider', statusBadge(t.status)]">
                                    {{ t.status }}
                                </span>

                                <!-- Quick Approval Buttons for Pending -->
                                <div v-if="t.status === 'pending'" class="flex items-center gap-1 mt-0.5">
                                    <button
                                        type="button"
                                        @click="approve(t)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-md shadow-2xs transition-colors"
                                        title="Approve and publish to website"
                                    >
                                        <i class="bi bi-check-lg" />
                                        Approve
                                    </button>
                                    <button
                                        type="button"
                                        @click="reject(t)"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 bg-rose-50 border border-rose-200 rounded-md transition-colors"
                                        title="Reject testimonial"
                                    >
                                        <i class="bi bi-x-lg" />
                                        Reject
                                    </button>
                                </div>

                                <!-- If already approved, allow reject if needed -->
                                <div v-else-if="t.status === 'approved'" class="text-[10px]">
                                    <button
                                        type="button"
                                        @click="reject(t)"
                                        class="text-slate-400 hover:text-rose-600 transition-colors"
                                        title="Revoke approval and reject"
                                    >
                                        Reject
                                    </button>
                                </div>

                                <!-- If rejected, allow approval -->
                                <div v-else-if="t.status === 'rejected'" class="text-[10px]">
                                    <button
                                        type="button"
                                        @click="approve(t)"
                                        class="text-slate-400 hover:text-emerald-600 font-semibold transition-colors"
                                        title="Reconsider and approve"
                                    >
                                        Approve & Publish
                                    </button>
                                </div>
                            </div>
                        </td>

                        <!-- Published Toggle -->
                        <td class="px-4 py-3 text-center">
                            <button
                                @click="toggle(t)"
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold border transition-all cursor-pointer"
                                :class="t.is_published
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100 shadow-2xs'
                                    : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'"
                                :title="t.is_published ? 'Click to unpublish' : 'Click to publish'"
                            >
                                <span class="inline-block w-1.5 h-1.5 rounded-full mr-1" :class="t.is_published ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                {{ t.is_published ? 'Published' : 'Hidden' }}
                            </button>
                        </td>

                        <!-- Date -->
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ formatDate(t.created_at) }}</td>

                        <!-- Actions -->
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end items-center gap-1">
                                <button
                                    type="button"
                                    @click="openDetail(t)"
                                    class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors"
                                    title="View full testimonial details"
                                >
                                    View
                                </button>
                                <Link
                                    :href="`/admin/marketing/testimonials/${t.id}/edit`"
                                    class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors"
                                >
                                    Edit
                                </Link>
                                <button
                                    class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors"
                                    @click="remove(t)"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!testimonials.data.length">
                        <td colspan="7" class="px-4 py-16 text-center text-sm text-slate-400">
                            <i class="bi bi-chat-square-quote text-3xl text-slate-300 block mb-2" />
                            No testimonials found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="testimonials" />
        </div>
    </div>

    <!-- Testimonial Detail / Review Modal -->
    <div
        v-if="selectedTestimonial"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4 animate-fadeIn"
        role="dialog"
        aria-modal="true"
    >
        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
                <div class="flex items-center gap-2">
                    <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-bold border uppercase tracking-wider', statusBadge(selectedTestimonial.status)]">
                        {{ selectedTestimonial.status }}
                    </span>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-bold border"
                        :class="selectedTestimonial.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                    >
                        {{ selectedTestimonial.is_published ? 'Published' : 'Hidden' }}
                    </span>
                </div>
                <button
                    type="button"
                    @click="closeDetail"
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                >
                    <i class="bi bi-x-lg" />
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 space-y-4">
                <!-- Author Header -->
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-amber-400/80 shadow shrink-0 bg-slate-100 flex items-center justify-center">
                        <img v-if="selectedTestimonial.photo_path" :src="`/storage/${selectedTestimonial.photo_path}`" :alt="selectedTestimonial.author_name" class="w-full h-full object-cover" />
                        <span v-else class="text-xl font-black text-slate-400 uppercase">
                            {{ selectedTestimonial.author_name ? selectedTestimonial.author_name.charAt(0) : '?' }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ selectedTestimonial.author_name }}</h3>
                        <p class="text-xs text-slate-500">{{ selectedTestimonial.author_role || 'Customer' }}</p>
                        <div class="mt-1 flex items-center gap-3 text-xs text-slate-500">
                            <span v-if="selectedTestimonial.email"><i class="bi bi-envelope mr-1" />{{ selectedTestimonial.email }}</span>
                            <span v-if="selectedTestimonial.phone"><i class="bi bi-telephone mr-1" />{{ selectedTestimonial.phone }}</span>
                        </div>
                    </div>
                </div>

                <!-- Rating & Date -->
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                    <div>
                        <span class="font-medium text-slate-500">Rating: </span>
                        <span class="text-amber-500 font-bold tracking-wide">{{ stars(selectedTestimonial.rating) }}</span>
                        <span class="font-bold text-slate-700 ml-1">({{ selectedTestimonial.rating }}/5)</span>
                    </div>
                    <div class="text-slate-400">
                        {{ formatDate(selectedTestimonial.created_at) }}
                    </div>
                </div>

                <!-- Testimonial Quote -->
                <div class="p-4 rounded-xl bg-amber-50/40 border border-amber-100/80">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Customer Testimonial</p>
                    <p class="text-sm text-slate-800 leading-relaxed italic whitespace-pre-line">
                        "{{ selectedTestimonial.content }}"
                    </p>
                </div>

                <!-- Admin notes if any -->
                <div v-if="selectedTestimonial.admin_notes" class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                    <p class="font-semibold text-slate-600 mb-1">Admin Notes:</p>
                    <p class="text-slate-600">{{ selectedTestimonial.admin_notes }}</p>
                </div>
            </div>

            <!-- Modal Actions -->
            <div class="flex items-center justify-between gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                <Link
                    :href="`/admin/marketing/testimonials/${selectedTestimonial.id}/edit`"
                    class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors"
                >
                    Edit Testimonial
                </Link>

                <div class="flex items-center gap-2">
                    <button
                        v-if="selectedTestimonial.status !== 'rejected'"
                        type="button"
                        @click="reject(selectedTestimonial)"
                        class="px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors"
                    >
                        Reject
                    </button>
                    <button
                        v-if="selectedTestimonial.status !== 'approved'"
                        type="button"
                        @click="approve(selectedTestimonial)"
                        class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors flex items-center gap-1.5"
                    >
                        <i class="bi bi-check-lg text-sm" />
                        Approve & Publish
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="toggle(selectedTestimonial)"
                        class="px-3.5 py-2 text-xs font-semibold rounded-lg border transition-colors"
                        :class="selectedTestimonial.is_published ? 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100' : 'bg-emerald-600 text-white border-transparent hover:bg-emerald-700'"
                    >
                        {{ selectedTestimonial.is_published ? 'Unpublish' : 'Publish' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
