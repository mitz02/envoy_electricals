<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Pagination from '@/Components/Pagination.vue';
import { timeAgo } from '@/lib/format';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    notifications: { type: Object, required: true },
});

const markingAll = ref(false);

function iconFor(type) {
    const icons = {
        order: 'bi-bag-fill',
        payment: 'bi-wallet2',
        feedback: 'bi-chat-heart-fill',
        enrollment: 'bi-mortarboard-fill',
        newsletter: 'bi-envelope-fill',
        stock: 'bi-box-seam-fill',
    };
    return icons[type] || 'bi-bell';
}

const unreadCount = () => props.notifications.data.filter((n) => !n.is_read).length;

async function markAllRead() {
    markingAll.value = true;
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        await fetch('/admin/notifications/read-all', {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
        });
        props.notifications.data.forEach((n) => { n.is_read = true; });
    } finally {
        markingAll.value = false;
    }
}

function openLink(n) {
    if (!n.link) return;
    if (!n.is_read) {
        fetch(`/admin/notifications/${n.id}/read`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
        });
        n.is_read = true;
    }
    router.visit(n.link);
}
</script>

<template>
    <FlashMessages />
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Notifications</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ unreadCount() }} unread · activity across the website and admin system
                </p>
            </div>
            <button
                v-if="unreadCount() > 0"
                type="button"
                :disabled="markingAll"
                class="rounded-lg bg-[#0D1527] px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                @click="markAllRead"
            >
                Mark all as read
            </button>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div v-if="notifications.data.length" class="divide-y divide-slate-100">
                <button
                    v-for="n in notifications.data"
                    :key="n.id"
                    type="button"
                    class="flex w-full items-start gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50"
                    :class="n.is_read ? '' : 'bg-yellow-50/40'"
                    @click="openLink(n)"
                >
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0D1527]/5 text-[#0D1527]">
                        <i class="bi text-sm" :class="iconFor(n.type)"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="truncate text-sm font-semibold text-slate-900">{{ n.title }}</span>
                            <span v-if="!n.is_read" class="h-2 w-2 shrink-0 rounded-full bg-yellow-400" />
                        </span>
                        <p v-if="n.body" class="mt-0.5 text-sm text-slate-600">{{ n.body }}</p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ timeAgo(n.created_at) }}
                            <span v-if="n.link" class="font-medium text-[#40e0d0]">· View →</span>
                        </p>
                    </span>
                </button>
            </div>
            <div v-else class="px-6 py-14 text-center">
                <i class="bi bi-bell text-3xl text-slate-300"></i>
                <p class="mt-3 text-sm font-medium text-slate-600">No notifications yet</p>
                <p class="mt-1 text-xs text-slate-400">Website activity such as new orders, payments, feedback and enrollments will appear here.</p>
            </div>
        </div>

        <Pagination :meta="notifications" />
    </div>
</template>