<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { formatDateTime, badgeClass } from '@/lib/format';
import { useCan } from '@/composables/permissions';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
});

const { has } = useCan();

const cards = [
    { label: 'Subscribers', value: 'subscribers', href: '/admin/marketing/subscribers', permission: 'marketing.newsletter', icon: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4' },
    { label: 'Newsletters', value: 'newsletters', href: '/admin/marketing/newsletters', permission: 'marketing.newsletter', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },
    { label: 'Testimonials', value: 'testimonials', href: '/admin/marketing/testimonials', permission: 'marketing.testimonials', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Feedback Inbox', value: 'feedback', href: '/admin/marketing/feedback', permission: 'feedback.manage', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
];

const visibleCards = cards.filter((c) => has(c.permission));
</script>

<template>
    <FlashMessages />
    <PageHeader
        title="Marketing & Website"
        subtitle="Newsletters, subscribers, testimonials and customer feedback."
    />

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Link
            v-for="c in visibleCards"
            :key="c.label"
            :href="c.href"
            class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all hover:border-amber-200 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">{{ c.label }}</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="c.icon" />
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ summary[c.value] ?? 0 }}</p>
            <p class="mt-0.5 text-[11px] text-slate-400 group-hover:text-amber-600">View →</p>
        </Link>
    </div>

    <!-- Recent Activity -->
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-xs font-bold text-slate-900">Recent Subscribers</h2>
                <Link href="/admin/marketing/subscribers" class="text-xs font-semibold text-amber-600 hover:underline">All →</Link>
            </div>
            <div class="mt-3 space-y-2">
                <div v-for="s in summary.recent_subscribers" :key="s.id" class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-2.5">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ s.name || s.email }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ s.email }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">{{ s.status }}</span>
                </div>
                <p v-if="!summary.recent_subscribers?.length" class="py-6 text-center text-sm text-slate-400">No subscribers yet.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-xs font-bold text-slate-900">Testimonials</h2>
                <Link href="/admin/marketing/testimonials" class="text-xs font-semibold text-amber-600 hover:underline">All →</Link>
            </div>
            <div class="mt-3 space-y-2">
                <div v-for="t in summary.recent_testimonials" :key="t.id" class="rounded-xl border border-slate-100 bg-slate-50/50 p-2.5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ t.author_name }}</p>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold border" :class="t.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-500 border-slate-200'">
                            {{ t.is_published ? 'Published' : 'Draft' }}
                        </span>
                    </div>
                    <p class="mt-1 line-clamp-2 text-[11px] text-slate-500">{{ t.content }}</p>
                </div>
                <p v-if="!summary.recent_testimonials?.length" class="py-6 text-center text-sm text-slate-400">No testimonials yet.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-xs font-bold text-slate-900">Feedback Inbox</h2>
                <Link href="/admin/marketing/feedback" class="text-xs font-semibold text-amber-600 hover:underline">All →</Link>
            </div>
            <div class="mt-3 space-y-2">
                <div v-for="f in summary.recent_feedback" :key="f.id" class="rounded-xl border border-slate-100 bg-slate-50/50 p-2.5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ f.customer_name || 'Anonymous' }}</p>
                        <span :class="badgeClass(f.status === 'new' ? 'bg-amber-100 text-amber-800' : f.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')">{{ f.status }}</span>
                    </div>
                    <p class="mt-1 line-clamp-2 text-[11px] text-slate-500">{{ f.comment || f.experience || '—' }}</p>
                    <p class="mt-1 text-[10px] text-slate-400">{{ formatDateTime(f.created_at) }}</p>
                </div>
                <p v-if="!summary.recent_feedback?.length" class="py-6 text-center text-sm text-slate-400">No feedback yet.</p>
            </div>
        </div>
    </div>
</template>