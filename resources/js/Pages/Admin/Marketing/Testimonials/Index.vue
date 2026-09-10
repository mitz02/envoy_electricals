<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate, badgeClass } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    testimonials: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search ?? '',
    published: props.filters.published ?? '',
});

const actionForm = useForm({});

function applyFilters() {
    const p = new URLSearchParams();
    if (form.search) p.set('search', form.search);
    if (form.published !== '' && form.published !== undefined) p.set('published', form.published);
    window.location.search = p.toString();
}

function toggle(t) {
    actionForm.post(`/admin/marketing/testimonials/${t.id}/toggle`, { preserveScroll: true });
}

function remove(t) {
    if (!confirm(`Remove testimonial from ${t.author_name}?`)) {
        return;
    }
    actionForm.delete(`/admin/marketing/testimonials/${t.id}`, { preserveScroll: true });
}

function stars(rating) {
    return 'â˜…'.repeat(rating || 0) + 'â˜†'.repeat(Math.max(0, 5 - (rating || 0)));
}
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Testimonials"
        subtitle="Customer testimonials displayed on the website."
        action-href="/admin/marketing/testimonials/create"
        action-label="Add Testimonial"
    />

    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row">
        <input v-model="form.search" type="search" placeholder="Search author or contentâ€¦" class="flex-1 rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @keyup.enter="applyFilters" />
        <select v-model="form.published" class="rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20" @change="applyFilters">
            <option value="">All</option>
            <option value="1">Published</option>
            <option value="0">Hidden</option>
        </select>
        <button class="rounded-lg bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D1527]/90" @click="applyFilters">Filter</button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Author</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Testimonial</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Rating</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-600">Published</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Created</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="t in testimonials.data" :key="t.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ t.author_name }}</p>
                            <p class="text-xs text-slate-400">{{ t.author_role || 'â€”' }}</p>
                        </td>
                        <td class="px-4 py-3 max-w-xs truncate text-slate-600" :title="t.content">{{ t.content }}</td>
                        <td class="px-4 py-3 text-center text-amber-500">{{ stars(t.rating) }}</td>
                        <td class="px-4 py-3 text-center">
                            <button
                                @click="toggle(t)"
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold border transition-colors"
                                :class="t.is_published
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                                    : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'"
                            >
                                {{ t.is_published ? 'Published' : 'Hidden' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ formatDate(t.created_at) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Link :href="`/admin/marketing/testimonials/${t.id}/edit`" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</Link>
                                <button class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50" @click="remove(t)">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!testimonials.data.length">
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">No testimonials found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <Pagination :meta="testimonials" />
        </div>
    </div>
</template>