<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useCan } from '@/composables/permissions';
import { timeAgo } from '@/lib/format';

const props = defineProps({
    children: { type: Object, default: null },
});

const showingSidebar = ref(false);
const searchQuery = ref('');
const searchGroups = ref([]);
const searchLoading = ref(false);
const searchOpen = ref(false);
const searchError = ref(false);
const searchBox = ref(null);
const highlightIndex = ref(-1);
const { has } = useCan();

// ---- Store Selector ----
const stores = computed(() => page.props.stores ?? []);
const selectedStore = computed(() => page.props.selectedStore ?? null);
const storeSelectorOpen = ref(false);

function selectStore(store) {
    router.post('/admin/store/select', { store_id: store?.id }, {
        preserveScroll: true,
        onSuccess: () => {
            storeSelectorOpen.value = false;
            router.reload();
        },
    });
}

function selectAllStores() {
    router.post('/admin/store/select', { store_id: null }, {
        preserveScroll: true,
        onSuccess: () => router.reload(),
    });
}

const page = usePage();
const user = computed(() => page.props.auth?.user);

// ---- Sidebar menu (searchable) ----
const sidebarQuery = ref('');
const openGroups = ref({ Marketing: false, Academy: true, Finances: true });
const searchMode = computed(() => sidebarQuery.value.trim().length > 0);

const canShow = (perm) =>
    Array.isArray(perm) ? perm.some((p) => has(p)) : !perm || has(perm);

const sidebarMenu = [
    { type: 'link', label: 'Dashboard', href: '/admin', icon: 'bi-grid-1x2-fill', perm: 'dashboard.view' },
    { type: 'link', label: 'Sales', href: '/admin/sales', icon: 'bi-cart-check-fill', perm: 'sales.view' },
    { type: 'link', label: 'Customers', href: '/admin/customers', icon: 'bi-people-fill', perm: 'customers.manage' },
    { type: 'link', label: 'Purchases', href: '/admin/purchases', icon: 'bi-basket-fill', perm: 'purchases.view' },
    { type: 'link', label: 'Suppliers', href: '/admin/suppliers', icon: 'bi-buildings-fill', perm: ['suppliers.view', 'purchases.view'] },
    { type: 'link', label: 'Products', href: '/admin/products', icon: 'bi-box-seam-fill', perm: 'products.view' },
    { type: 'link', label: 'Brands', href: '/admin/brands', icon: 'bi-award-fill', perm: 'products.view' },
    { type: 'link', label: 'Stock', href: '/admin/stock/movements', icon: 'bi-arrow-repeat', perm: ['inventory.view', 'products.view'] },
    {
        type: 'group',
        label: 'Inventory',
        icon: 'bi-box-seam',
        perm: 'inventory.view',
        items: [
            { label: 'Stock Adjustment', href: '/admin/stock/adjust', perm: 'inventory.adjust' },
            { label: 'Stock Movements', href: '/admin/stock/movements', perm: 'inventory.view' },
        ],
    },
    { type: 'link', label: 'Orders', href: '/admin/orders', icon: 'bi-bag-fill', perm: 'orders.view' },
    { type: 'link', label: 'Buyers', href: '/admin/buyers', icon: 'bi-person-raised-hand', perm: 'orders.view' },
    { type: 'link', label: 'Projects', href: '/admin/projects', icon: 'bi-lightning-charge-fill', perm: 'projects.view' },
    { type: 'link', label: 'Packages', href: '/admin/solar-packages', icon: 'bi-sun-fill', perm: 'solar.view' },
    { type: 'link', label: 'Leads', href: '/admin/solar-leads', icon: 'bi-person-lines-fill', perm: 'solar.leads' },
    { type: 'link', label: 'Custom Quotations', href: '/admin/custom-quotations', icon: 'bi-file-earmark-text-fill', perm: 'custom-quotations.view' },
    {
        type: 'group',
        label: 'Marketing',
        icon: 'bi-megaphone-fill',
        items: [
            { label: 'Overview', href: '/admin/marketing', perm: 'marketing.newsletter' },
            { label: 'Newsletters', href: '/admin/marketing/newsletters', perm: 'marketing.newsletter' },
            { label: 'Subscribers', href: '/admin/marketing/subscribers', perm: 'marketing.newsletter' },
            { label: 'Feedback', href: '/admin/marketing/feedback', perm: 'feedback.manage' },
        ],
    },
    { type: 'link', label: 'Staff', href: '/admin/staff', icon: 'bi-person-badge-fill', perm: 'staff.manage' },
    { type: 'link', label: 'Stores', href: '/admin/stores', icon: 'bi-shop', perm: 'stores.view' },
    {
        type: 'group',
        label: 'Academy',
        icon: 'bi-mortarboard-fill',
        perm: 'training.view',
        items: [
            { label: 'Programs', href: '/admin/training', perm: null },
            { label: 'Trainees', href: '/admin/trainees', perm: null },
            { label: 'Certificates', href: '/admin/certificates', perm: null },
        ],
    },
    {
        type: 'group',
        label: 'Finances',
        icon: 'bi-wallet2',
        items: [
            { label: 'Payments', href: '/admin/payments', perm: 'payments.view' },
            { label: 'Expenses', href: '/admin/expenses', perm: 'expenses.view' },
            { label: 'Reports', href: '/admin/reports/sales', perm: 'reports.view' },
            { label: 'Payroll', href: '/admin/payroll', perm: 'payroll.view' },
        ],
    },
    {
        type: 'section',
        label: 'System',
        items: [
            { label: 'Notifications', href: '/admin/notifications', icon: 'bi-bell', perm: 'dashboard.view' },
            { label: 'Audit Logs', href: '/admin/audit-logs', icon: 'bi-shield-check', perm: 'audit.view' },
            { label: 'Website', href: '/admin/website', icon: 'bi-globe2', perm: 'website.content' },
            { label: 'Storefront', href: '/', icon: 'bi-shop', perm: null, external: true },
            { label: 'Roles', href: '/admin/settings/roles', icon: 'bi-person-lock', perm: 'settings.roles' },
            { label: 'Settings', href: '/admin/settings', icon: 'bi-gear', perm: 'settings.manage' },
        ],
    },
];

const menuEntries = computed(() => {
    const q = sidebarQuery.value.trim().toLowerCase();
    const hits = (text) => !q || text.toLowerCase().includes(q);

    return sidebarMenu.flatMap((entry) => {
        if (entry.type === 'link') {
            return canShow(entry.perm) && hits(entry.label) ? [entry] : [];
        }

        if (entry.perm && !canShow(entry.perm)) return [];

        const allowed = (entry.items || []).filter((it) => canShow(it.perm));
        const items = allowed.filter((it) => hits(it.label) || (it.subtitle && hits(it.subtitle)));

        if (entry.type === 'group') {
            // Search mode: show the group header when the label matches, even if only
            // the header matched (all permitted items are then shown).
            if (q && hits(entry.label) && allowed.length) return [{ ...entry, items: allowed }];
            return items.length ? [{ ...entry, items }] : [];
        }

        // Section: show only when at least one permitted item is visible.
        return items.length ? [{ ...entry, items }] : [];
    });
});

function toggleGroup(label) {
    openGroups.value[label] = !openGroups.value[label];
}

function groupOpen(label) {
    return searchMode.value || openGroups.value[label];
}

function navLinkClass(href) {
    return isCurrentRoute(href)
        ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
        : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium';
}

function navSubClass(href) {
    return isCurrentRoute(href) ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white';
}

function dotClass(href) {
    return isCurrentRoute(href) ? 'bg-yellow-400' : 'bg-slate-600';
}

let searchTimer = null;
let searchRequest = 0;

const displayGroups = computed(() => {
    let gi = -1;
    return searchGroups.value.map((g) => ({
        ...g,
        items: g.items.map((it) => ({ ...it, gi: ++gi })),
    }));
});

const searchItems = computed(() => displayGroups.value.flatMap((g) => g.items));
const hasQuery = computed(() => searchQuery.value.trim().length >= 2);

async function runSearch() {
    const q = searchQuery.value.trim();
    const request = ++searchRequest;
    searchError.value = false;

    if (!hasQuery.value) {
        searchGroups.value = [];
        searchLoading.value = false;
        searchOpen.value = false;
        return;
    }

    searchLoading.value = true;
    searchOpen.value = true;

    try {
        const res = await fetch(`/admin/search?q=${encodeURIComponent(q)}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (request !== searchRequest) return;
        searchGroups.value = data.groups || [];
        if (highlightIndex.value >= searchItems.value.length) highlightIndex.value = -1;
    } catch {
        if (request !== searchRequest) return;
        searchError.value = true;
        searchGroups.value = [];
    } finally {
        if (request === searchRequest) searchLoading.value = false;
    }
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, 350);
}

function setHighlight(i) {
    highlightIndex.value = i;
    nextTick(() => {
        document.querySelector('[data-search-active="true"]')?.scrollIntoView({ block: 'nearest' });
    });
}

function goto(href) {
    searchOpen.value = false;
    highlightIndex.value = -1;
    searchBox.value?.blur();
    router.visit(href);
}

function onSearchKeydown(e) {
    if (!['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(e.key)) return;

    if (e.key === 'Escape') {
        searchOpen.value = false;
        searchBox.value?.blur();
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!searchOpen.value) {
            searchOpen.value = true;
            setHighlight(searchItems.value.length ? 0 : -1);
            return;
        }
        setHighlight((highlightIndex.value + 1) % searchItems.value.length);
        return;
    }

    if (e.key === 'ArrowUp') {
        if (!searchItems.value.length) return;
        e.preventDefault();
        const prev = highlightIndex.value <= 0 ? searchItems.value.length - 1 : highlightIndex.value - 1;
        setHighlight(prev);
        return;
    }

    if (e.key === 'Enter') {
        const item = searchItems.value[highlightIndex.value] || searchItems.value[0];
        if (item) {
            e.preventDefault();
            goto(item.href);
        }
    }
}

function onSearchFocus() {
    if (hasQuery.value && !searchOpen.value) runSearch();
}

function onGlobalKeydown(e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchBox.value?.focus();
        searchBox.value?.select();
    }
    if (e.key === 'Escape' && searchOpen.value) {
        searchOpen.value = false;
    }
}

onMounted(() => window.addEventListener('keydown', onGlobalKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onGlobalKeydown));

const isCurrentRoute = (path) => {
    return page.url === path || (path !== '/admin' && page.url.startsWith(path));
};

const currentUser = computed(() => user.value || { name: 'Admin Staff', email: 'admin@envoyelectric.ng', role: 'Super Admin' });

// ---- Notifications (bell dropdown) ----
const notifications = ref(page.props.notifications?.items ?? []);
const notificationsOpen = ref(false);
let notificationPoll = null;

const unreadCount = computed(() => notifications.value.filter((n) => !n.is_read).length);

function notificationIcon(type) {
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

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function refreshNotifications() {
    try {
        const res = await fetch('/admin/notifications/data', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return;
        const data = await res.json();
        notifications.value = data.items ?? [];
    } catch {
        // Swallow polling failures; the next shared prop refresh will catch up.
    }
}

async function markAllNotificationsRead() {
    try {
        await fetch('/admin/notifications/read-all', {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
        });
        notifications.value = notifications.value.map((n) => ({ ...n, is_read: true }));
    } catch {
        // Ignore.
    }
}

async function openNotificationLink(n) {
    if (n.link) {
        if (!n.is_read) {
            fetch(`/admin/notifications/${n.id}/read`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
            });
            n.is_read = true;
        }
        notificationsOpen.value = false;
        router.visit(n.link);
    }
}

function logout() {
    router.post('/logout');
}

onMounted(() => {
    notifications.value = page.props.notifications?.items ?? [];
    notificationPoll = setInterval(refreshNotifications, 30000);
});

onBeforeUnmount(() => {
    if (notificationPoll) clearInterval(notificationPoll);
});
</script>

<template>
    <div class="min-h-screen bg-[#FAF8F2] text-slate-800 antialiased selection:bg-yellow-400 selection:text-slate-950 flex flex-row">
        <!-- Mobile Sidebar Backdrop -->
        <div
            v-if="showingSidebar"
            class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs transition-opacity lg:hidden"
            @click="showingSidebar = false"
        />

        <!-- Single Clean Sidebar Navigation -->
        <aside
            :class="showingSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col justify-between border-r border-white/10 bg-[#0D1527] p-4 transition-transform duration-200 ease-in-out print:hidden lg:static"
        >
            <div class="flex flex-col flex-1 overflow-y-auto pr-1">
                <!-- Brand Logo -->
                <div class="flex items-center justify-between px-2 pt-2">
                    <Link href="/admin" class="mx-auto block">
                        <div class="flex h-20 w-20 items-center justify-center">
                            <img src="/envoy_images/logo.png" alt="Envoy Electricals" class="h-full w-full object-contain" />
                        </div>
                    </Link>

                    <button
                        @click="showingSidebar = false"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden"
                    >
                        <i class="bi bi-x-lg text-lg shrink-0"></i>
                    </button>
                </div>

                <!-- Sidebar menu search -->
                <div class="mt-4 px-2">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <i class="bi bi-search text-xs shrink-0"></i>
                        </div>
                        <input
                            v-model="sidebarQuery"
                            type="search"
                            placeholder="Search menu…"
                            class="w-full rounded-xl border border-white/10 bg-white/5 py-2 pl-8 pr-8 text-xs text-slate-200 placeholder-slate-500 transition-all focus:border-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400/20"
                        />
                        <button
                            v-if="sidebarQuery"
                            @click="sidebarQuery = ''"
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-500 transition hover:text-slate-200"
                            aria-label="Clear menu search"
                        >
                            <i class="bi bi-x-lg text-xs shrink-0"></i>
                        </button>
                    </div>
                </div>

                <!-- Main Menu Items -->
                <nav class="mt-3 space-y-1">
                    <template v-for="entry in menuEntries" :key="entry.type + ':' + entry.label">
                        <!-- Top-level link -->
                        <Link
                            v-if="entry.type === 'link'"
                            :href="entry.href"
                            :class="navLinkClass(entry.href)"
                            class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                            @click="showingSidebar = false"
                        >
                            <span v-if="isCurrentRoute(entry.href)" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                            <i :class="`bi ${entry.icon} text-sm shrink-0`"></i>
                            <span class="truncate">{{ entry.label }}</span>
                        </Link>

                        <!-- Collapsible group -->
                        <div v-else-if="entry.type === 'group'" class="pt-2">
                            <button
                                @click="toggleGroup(entry.label)"
                                class="flex w-full items-center justify-between rounded-xl px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-300"
                            >
                                <span class="flex items-center gap-2">
                                    <i :class="`bi ${entry.icon} text-sm shrink-0`"></i>
                                    {{ entry.label }}
                                </span>
                                <svg :class="groupOpen(entry.label) ? 'rotate-180' : ''" class="h-3.5 w-3.5 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-show="groupOpen(entry.label)" class="mt-1 space-y-1 pl-4">
                                <Link
                                    v-for="it in entry.items"
                                    :key="it.label"
                                    :href="it.href"
                                    :class="navSubClass(it.href)"
                                    class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                                    @click="showingSidebar = false"
                                >
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="dotClass(it.href)" />
                                    <span class="truncate">{{ it.label }}</span>
                                </Link>
                                <p v-if="searchMode && !entry.items.length" class="px-3 py-1.5 text-xs text-slate-500">No matches.</p>
                            </div>
                        </div>

                        <!-- Section (System & Website) -->
                        <div v-else class="space-y-1 pt-2">
                            <template v-for="it in entry.items" :key="it.label">
                                <Link
                                    v-if="!it.external"
                                    :href="it.href"
                                    :class="navLinkClass(it.href)"
                                    class="flex items-center gap-3 rounded-xl px-3.5 py-2 text-sm"
                                    @click="showingSidebar = false"
                                >
                                    <i :class="`bi ${it.icon} text-sm shrink-0`"></i>
                                    <span class="truncate">{{ it.label }}</span>
                                </Link>
                                <a
                                    v-else
                                    :href="it.href"
                                    target="_blank"
                                    class="flex items-center justify-between rounded-xl px-3.5 py-2 text-sm font-medium text-slate-300 hover:bg-white/10 hover:text-white"
                                    @click="showingSidebar = false"
                                >
                                    <div class="flex items-center gap-3">
                                        <i :class="`bi ${it.icon} text-sm shrink-0`"></i>
                                        <span>{{ it.label }}</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400">↗</span>
                                </a>
                            </template>
                            <p v-if="searchMode && !entry.items.length" class="px-3 py-1.5 text-xs text-slate-500">No matches.</p>
                        </div>
                    </template>

                    <p v-if="searchMode && !menuEntries.length" class="px-3 py-2 text-xs text-slate-500">
                        No menu matches “{{ sidebarQuery.trim() }}”.
                    </p>
                </nav>
            </div>

            <!-- Bottom User Profile Card (Clean Authenticated Staff Footer) -->
            <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-3 shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-yellow-400 text-xs font-bold text-[#0D1527] shadow-xs">
                            {{ (currentUser?.name || 'A').charAt(0).toUpperCase() }}
                            <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-[#0D1527]" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-slate-800">{{ currentUser?.name }}</p>
                            <p class="truncate text-[10px] text-slate-400 font-medium capitalize">{{ currentUser?.role || 'Staff Member' }}</p>
                        </div>
                    </div>

                    <!-- Logout Button -->
                    <button
                        @click="logout"
                        title="Sign Out"
                        class="rounded-lg p-1.5 text-slate-500 hover:bg-white/10 hover:text-white transition-colors"
                    >
                        <i class="bi bi-box-arrow-right text-base shrink-0"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex flex-1 flex-col min-w-0">
            <!-- Top Header Bar -->
            <header class="sticky top-0 z-30 grid min-h-16 shrink-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 gap-y-2.5 border-b border-slate-200/80 bg-white/95 px-4 py-3 shadow-sm shadow-slate-900/5 backdrop-blur-xl print:hidden sm:px-6 lg:flex lg:h-20 lg:gap-4 lg:py-0 xl:px-8">
                <!-- Search input -->
                <div class="contents lg:order-first lg:block lg:max-w-2xl lg:min-w-0 lg:flex-1">
                    <button
                        class="col-start-1 row-start-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 lg:hidden"
                        @click="showingSidebar = true"
                        aria-label="Toggle navigation"
                    >
                        <i class="bi bi-list text-lg shrink-0"></i>
                    </button>

                    <!-- Global Search Bar -->
                    <div class="relative z-50 col-span-2 row-start-2 w-full lg:col-auto lg:row-auto">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-search text-sm shrink-0"></i>
                        </div>
                        <input
                            ref="searchBox"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search products, invoices, customers..."
                            aria-label="Search admin records"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/80 pl-10 pr-9 text-xs text-slate-800 shadow-sm transition placeholder:text-slate-400 hover:border-slate-300 hover:bg-white focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400/20 sm:pr-16 sm:text-sm"
                            @focus="onSearchFocus"
                            @input="onSearchInput"
                            @keydown="onSearchKeydown"
                        />
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <i v-if="searchLoading" class="bi bi-arrow-repeat animate-spin text-sm text-slate-400"></i>
                            <kbd v-else class="hidden rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-400 shadow-xs sm:block">⌘K</kbd>
                        </div>

                        <!-- Results dropdown -->
                        <transition
                            enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <div
                                v-if="searchOpen"
                                class="absolute left-0 right-0 top-full mt-2 max-h-[70vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
                            >
                                <template v-if="displayGroups.length">
                                    <div v-for="g in displayGroups" :key="g.key" class="mb-1 last:mb-0">
                                        <p class="flex items-center gap-2 px-3 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                            <i :class="`bi ${g.icon} text-xs`"></i>
                                            {{ g.label }}
                                        </p>
                                        <a
                                            v-for="it in g.items"
                                            :key="`${g.key}-${it.label}`"
                                            :href="it.href"
                                            :data-search-active="it.gi === highlightIndex ? 'true' : 'false'"
                                            class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 transition-colors"
                                            :class="it.gi === highlightIndex ? 'bg-[#0D1527] text-white' : 'hover:bg-slate-100'"
                                            @click.prevent="goto(it.href)"
                                        >
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold" :class="it.gi === highlightIndex ? 'text-white' : 'text-slate-900'">{{ it.label }}</span>
                                                <span v-if="it.subtitle" class="block truncate text-xs" :class="it.gi === highlightIndex ? 'text-slate-300' : 'text-slate-400'">{{ it.subtitle }}</span>
                                            </span>
                                            <i class="bi bi-arrow-right shrink-0 text-xs opacity-50"></i>
                                        </a>
                                    </div>
                                </template>
                                <p v-else-if="searchError" class="px-3 py-6 text-center text-sm text-slate-400">Search failed. Please try again.</p>
                                <p v-else-if="!searchLoading && hasQuery" class="px-3 py-6 text-center text-sm text-slate-400">No matches for “{{ searchQuery.trim() }}”.</p>
                                <p v-else class="px-3 py-6 text-center text-sm text-slate-400">Searching…</p>
                            </div>
                        </transition>

                        <!-- Click-outside overlay -->
                        <div v-if="searchOpen" class="fixed inset-0 z-40" @click="searchOpen = false" />
                    </div>
                </div>

                <!-- Right Action Icons & Avatar -->
                <div class="col-start-2 row-start-1 ml-auto flex items-center justify-self-end gap-1.5 sm:gap-2 lg:order-last lg:gap-3">
                    <!-- Store Selector -->
                    <div v-if="stores.length > 1" class="relative">
                        <button
                            @click="storeSelectorOpen = !storeSelectorOpen"
                            aria-label="Select store"
                            class="flex h-10 shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 sm:px-3 sm:text-sm"
                        >
                            <i class="bi bi-shop text-lg text-[#40e0d0] shrink-0"></i>
                            <span class="hidden max-w-[120px] truncate sm:inline xl:max-w-[160px]">
                                {{ selectedStore ? selectedStore.name : 'All Stores' }}
                            </span>
                            <svg class="hidden h-4 w-4 shrink-0 text-slate-400 transition-transform sm:block" :class="storeSelectorOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <transition
                            enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <div
                                v-if="storeSelectorOpen"
                                class="absolute right-0 top-full z-50 mt-2 w-56 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                            >
                                <button
                                    @click="selectAllStores"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm transition-colors hover:bg-slate-50"
                                    :class="!selectedStore ? 'bg-[#40e0d0]/10 text-[#40e0d0] font-semibold' : 'text-slate-700'"
                                >
                                    <i class="bi bi-grid-1x2-fill text-lg" />
                                    <span>All Stores</span>
                                    <i v-if="!selectedStore" class="bi bi-check ml-auto text-[#40e0d0]" />
                                </button>
                                <hr class="my-1 border-slate-100" />
                                <button
                                    v-for="store in stores"
                                    :key="store.id"
                                    @click="selectStore(store)"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm transition-colors hover:bg-slate-50"
                                    :class="selectedStore?.id === store.id ? 'bg-[#40e0d0]/10 text-[#40e0d0] font-semibold' : 'text-slate-700'"
                                >
                                    <i :class="store.is_active ? 'bi bi-shop-fill text-lg text-emerald-600' : 'bi bi-shop text-lg text-slate-400'" />
                                    <span class="truncate">{{ store.name }}</span>
                                    <span class="text-[10px] text-slate-400">{{ store.code }}</span>
                                    <i v-if="selectedStore?.id === store.id" class="bi bi-check ml-auto text-[#40e0d0]" />
                                </button>
                            </div>
                        </transition>

                        <div v-if="storeSelectorOpen" class="fixed inset-0 z-40" @click="storeSelectorOpen = false" />
                    </div>

                    <!-- Quick New Sale Pill -->
                    <Link
                        v-if="has('sales.create')"
                        href="/admin/sales/create"
                        class="hidden h-10 items-center gap-2 rounded-xl bg-[#0D1527] px-4 text-xs font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800 hover:shadow-md xl:inline-flex"
                    >
                        <i class="bi bi-plus-lg text-sm shrink-0"></i>
                        <span>New Sale / POS</span>
                    </Link>

                    <!-- Notifications Bell -->
                    <div class="relative">
                        <button
                            type="button"
                            class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                            aria-label="Notifications"
                            @click="notificationsOpen = !notificationsOpen"
                        >
                            <i class="bi bi-bell text-base shrink-0"></i>
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-yellow-400 px-1 text-[9px] font-bold text-[#0D1527] ring-2 ring-white"
                            >
                                {{ unreadCount > 99 ? '99+' : unreadCount }}
                            </span>
                            <span v-else class="absolute right-2 top-2 h-2 w-2 rounded-full bg-slate-200 ring-2 ring-white" />
                        </button>

                        <transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 translate-y-1"
                            leave-active-class="transition duration-100 ease-in"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <div
                                v-if="notificationsOpen"
                                class="absolute right-0 top-full z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                            >
                                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                    <p class="text-sm font-bold text-slate-900">Notifications</p>
                                    <button
                                        v-if="unreadCount > 0"
                                        type="button"
                                        class="text-xs font-medium text-[#40e0d0] hover:underline"
                                        @click="markAllNotificationsRead"
                                    >
                                        Mark all read
                                    </button>
                                </div>

                                <div class="max-h-80 overflow-y-auto">
                                    <template v-if="notifications.length">
                                        <button
                                            v-for="n in notifications"
                                            :key="n.id"
                                            type="button"
                                            class="flex w-full items-start gap-3 border-b border-slate-50 px-4 py-3 text-left transition-colors hover:bg-slate-50"
                                            :class="n.is_read ? '' : 'bg-yellow-50/40'"
                                            @click="openNotificationLink(n)"
                                        >
                                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#0D1527]/5 text-[#0D1527]">
                                                <i class="bi text-xs" :class="notificationIcon(n.type)"></i>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-semibold text-slate-900">{{ n.title }}</span>
                                                <span v-if="n.body" class="line-clamp-2 block text-xs text-slate-500">{{ n.body }}</span>
                                                <span class="block text-[10px] text-slate-400">{{ timeAgo(n.created_at) }}</span>
                                            </span>
                                            <span v-if="!n.is_read" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-yellow-400" />
                                        </button>
                                    </template>
                                    <p v-else class="px-4 py-8 text-center text-sm text-slate-400">You're all caught up.</p>
                                </div>

                                <div class="border-t border-slate-100 px-4 py-2.5">
                                    <Link
                                        href="/admin/notifications"
                                        class="block text-center text-xs font-semibold text-[#0D1527] hover:text-[#40e0d0]"
                                        @click="notificationsOpen = false"
                                    >
                                        View all notifications
                                    </Link>
                                </div>
                            </div>
                        </transition>

                        <div
                            v-if="notificationsOpen"
                            class="fixed inset-0 z-40"
                            @click="notificationsOpen = false"
                        />
                    </div>

                    <!-- User Profile Avatar -->
                    <span class="mx-0.5 hidden h-7 w-px bg-slate-200 xl:block" />
                    <Link
                        href="/profile"
                        :aria-label="`Open ${currentUser?.name || 'user'} profile`"
                        class="flex shrink-0 items-center gap-2.5 rounded-xl p-0.5 transition hover:bg-slate-50 sm:pr-2"
                    >
                        <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#0D1527] to-slate-700 text-xs font-bold text-white shadow-sm ring-2 ring-white">
                            {{ (currentUser?.name || 'A').charAt(0).toUpperCase() }}
                        </div>
                        <span class="hidden min-w-0 text-left xl:block">
                            <span class="block max-w-32 truncate text-xs font-bold text-slate-800">{{ currentUser?.name }}</span>
                            <span class="block max-w-32 truncate text-[10px] font-medium capitalize text-slate-500">{{ currentUser?.role || 'Staff Member' }}</span>
                        </span>
                    </Link>
                </div>
            </header>

            <!-- Main Content Slot -->
            <main class="flex-1 p-4 sm:p-6 lg:p-7 max-w-[1440px] w-full mx-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
nav .bi {
    color: #40e0d0;
}
</style>