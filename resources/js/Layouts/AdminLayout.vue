<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useCan } from '@/composables/permissions';

const props = defineProps({
    children: { type: Object, default: null },
});

const showingSidebar = ref(false);
const searchQuery = ref('');
const isFinanceOpen = ref(true);
const isMarketingOpen = ref(false);
const { has } = useCan();

const page = usePage();
const user = computed(() => page.props.auth?.user);

const isCurrentRoute = (path) => {
    return page.url === path || (path !== '/admin' && page.url.startsWith(path));
};

const currentUser = computed(() => user.value || { name: 'Admin Staff', email: 'admin@envoyelectric.ng', role: 'Super Admin' });

function logout() {
    router.post('/logout');
}
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
            class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col justify-between border-r border-white/10 bg-[#0D1527] p-4 transition-transform duration-200 ease-in-out lg:static"
        >
            <div class="flex flex-col flex-1 overflow-y-auto pr-1">
                <!-- Brand Logo -->
                <div class="flex items-center justify-between px-2 py-1.5">
                    <Link href="/admin" class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white p-1">
                            <img src="/envoy_images/logo.png" alt="Envoy Electric" class="h-full w-full object-contain" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold tracking-tight text-white leading-tight">Envoy Electric<span class="text-yellow-400">.</span></span>
                            <span class="text-[10px] font-medium tracking-wide uppercase text-slate-400">Business Suite</span>
                        </div>
                    </Link>

                    <button
                        @click="showingSidebar = false"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden"
                    >
                        <i class="bi bi-x-lg text-lg shrink-0"></i>
                    </button>
                </div>

                <!-- Main Menu Items -->
                <nav class="mt-5 space-y-1">
                    <!-- Dashboard -->
                    <Link
                        v-if="has('dashboard.view')"
                        href="/admin"
                        :class="page.url === '/admin'
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="page.url === '/admin'" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-grid-1x2-fill text-sm shrink-0"></i>
                        <span>Dashboard</span>
                    </Link>

                    <!-- Sales / POS -->
                    <Link
                        v-if="has('sales.view')"
                        href="/admin/sales"
                        :class="isCurrentRoute('/admin/sales')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center justify-between rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/sales')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <div class="flex items-center gap-3">
                            <i class="bi bi-cart-check-fill text-sm shrink-0"></i>
                            <span>Sales & POS</span>
                        </div>
                    </Link>

                    <!-- Products -->
                    <Link
                        v-if="has('products.view')"
                        href="/admin/products"
                        :class="isCurrentRoute('/admin/products')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/products')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-box-seam-fill text-sm shrink-0"></i>
                        <span>Products & Stock</span>
                    </Link>

                    <!-- Inventory Movements -->
                    <Link
                        v-if="has('inventory.view') || has('products.view')"
                        href="/admin/stock/movements"
                        :class="isCurrentRoute('/admin/stock/movements')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/stock/movements')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-arrow-repeat text-sm shrink-0"></i>
                        <span>Stock Ledger</span>
                    </Link>

                    <!-- Solar & Electrical Projects -->
                    <Link
                        v-if="has('projects.view')"
                        href="/admin/projects"
                        :class="isCurrentRoute('/admin/projects')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/projects')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-lightning-charge-fill text-sm shrink-0"></i>
                        <span>Solar Projects</span>
                    </Link>

                    <!-- Solar Packages -->
                    <Link
                        v-if="has('solar.view')"
                        href="/admin/solar-packages"
                        :class="isCurrentRoute('/admin/solar-packages')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/solar-packages')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-sun-fill text-sm shrink-0"></i>
                        <span>Solar Packages</span>
                    </Link>

                    <!-- Solar Leads -->
                    <Link
                        v-if="has('solar.leads')"
                        href="/admin/solar-leads"
                        :class="isCurrentRoute('/admin/solar-leads')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/solar-leads')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-person-lines-fill text-sm shrink-0"></i>
                        <span>Solar Leads</span>
                    </Link>

                    <!-- Marketing & Website -->
                    <div class="pt-2 space-y-1">
                        <button
                            @click="isMarketingOpen = !isMarketingOpen"
                            class="flex w-full items-center justify-between rounded-xl px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-300"
                        >
                            <span class="flex items-center gap-2">
                                <i class="bi bi-megaphone-fill text-sm shrink-0"></i>
                                Marketing
                            </span>
                            <svg :class="isMarketingOpen ? 'rotate-180' : ''" class="h-3.5 w-3.5 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="isMarketingOpen" class="mt-1 space-y-1 pl-4">
                            <Link
                                v-if="has('marketing.newsletter')"
                                href="/admin/marketing"
                                :class="page.url === '/admin/marketing' ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="page.url === '/admin/marketing' ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Overview</span>
                            </Link>
                            <Link
                                v-if="has('marketing.newsletter')"
                                href="/admin/marketing/newsletters"
                                :class="isCurrentRoute('/admin/marketing/newsletters') ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="isCurrentRoute('/admin/marketing/newsletters') ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Newsletters</span>
                            </Link>
                            <Link
                                v-if="has('marketing.newsletter')"
                                href="/admin/marketing/subscribers"
                                :class="isCurrentRoute('/admin/marketing/subscribers') ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="isCurrentRoute('/admin/marketing/subscribers') ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Subscribers</span>
                            </Link>
                            <Link
                                v-if="has('marketing.testimonials')"
                                href="/admin/marketing/testimonials"
                                :class="isCurrentRoute('/admin/marketing/testimonials') ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="isCurrentRoute('/admin/marketing/testimonials') ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Testimonials</span>
                            </Link>
                            <Link
                                v-if="has('feedback.manage')"
                                href="/admin/marketing/feedback"
                                :class="isCurrentRoute('/admin/marketing/feedback') ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="isCurrentRoute('/admin/marketing/feedback') ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Feedback Inbox</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Customers -->
                    <Link
                        v-if="has('customers.manage')"
                        href="/admin/customers"
                        :class="isCurrentRoute('/admin/customers')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/customers')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-people-fill text-sm shrink-0"></i>
                        <span>Customers & CRM</span>
                    </Link>

                    <!-- Purchases -->
                    <Link
                        v-if="has('purchases.view')"
                        href="/admin/purchases"
                        :class="isCurrentRoute('/admin/purchases')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/purchases')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-basket-fill text-sm shrink-0"></i>
                        <span>Purchases</span>
                    </Link>

                    <!-- Suppliers -->
                    <Link
                        v-if="has('suppliers.view') || has('purchases.view')"
                        href="/admin/suppliers"
                        :class="isCurrentRoute('/admin/suppliers')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/suppliers')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-buildings-fill text-sm shrink-0"></i>
                        <span>Suppliers</span>
                    </Link>

                    <!-- Staff -->
                    <Link
                        v-if="has('staff.manage')"
                        href="/admin/staff"
                        :class="isCurrentRoute('/admin/staff')
                            ? 'bg-yellow-400/10 text-yellow-400 font-semibold'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                        class="relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition-all duration-150"
                    >
                        <span v-if="isCurrentRoute('/admin/staff')" class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-yellow-400" />
                        <i class="bi bi-person-badge-fill text-sm shrink-0"></i>
                        <span>Staff Management</span>
                    </Link>

                    <!-- Finances Accordion -->
                    <div class="pt-2">
                        <button
                            @click="isFinanceOpen = !isFinanceOpen"
                            class="flex w-full items-center justify-between rounded-xl px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-300"
                        >
                            <span class="flex items-center gap-2">
                                <i class="bi bi-wallet2 text-sm shrink-0"></i>
                                Finances
                            </span>
                            <svg :class="isFinanceOpen ? 'rotate-180' : ''" class="h-3.5 w-3.5 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="isFinanceOpen" class="mt-1 space-y-1 pl-4">
                            <Link
                                v-if="has('payments.view')"
                                href="/admin/payments"
                                :class="isCurrentRoute('/admin/payments') ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="isCurrentRoute('/admin/payments') ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Payments Ledger</span>
                            </Link>
                            <Link
                                v-if="has('expenses.view')"
                                href="/admin/expenses"
                                :class="isCurrentRoute('/admin/expenses') ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="isCurrentRoute('/admin/expenses') ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Expenses</span>
                            </Link>
                            <Link
                                v-if="has('reports.view')"
                                href="/admin/reports/sales"
                                :class="isCurrentRoute('/admin/reports') ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="isCurrentRoute('/admin/reports') ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Financial Reports</span>
                            </Link>
                            <Link
                                v-if="has('payroll.view')"
                                href="/admin/payroll"
                                :class="isCurrentRoute('/admin/payroll') ? 'text-yellow-400 font-semibold' : 'text-slate-400 hover:text-white'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="isCurrentRoute('/admin/payroll') ? 'bg-yellow-400' : 'bg-slate-600'" />
                                <span>Payroll & Salaries</span>
                            </Link>
                        </div>
                    </div>

                    <!-- System & Website -->
                    <div class="pt-2 space-y-1">
                        <Link
                            v-if="has('audit_logs.view')"
                            href="/admin/audit-logs"
                            :class="isCurrentRoute('/admin/audit-logs') ? 'bg-yellow-400/10 text-yellow-400 font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2 text-sm"
                        >
                            <i class="bi bi-shield-check text-sm shrink-0"></i>
                            <span>Audit Logs</span>
                        </Link>

                        <Link
                            v-if="has('website.content')"
                            href="/admin/website"
                            :class="isCurrentRoute('/admin/website') ? 'bg-yellow-400/10 text-yellow-400 font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2 text-sm"
                        >
                            <i class="bi bi-globe2 text-sm shrink-0"></i>
                            <span>Website Settings</span>
                        </Link>

                        <Link
                            v-if="has('website.media')"
                            href="/admin/media"
                            :class="isCurrentRoute('/admin/media') ? 'bg-yellow-400/10 text-yellow-400 font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2 text-sm"
                        >
                            <i class="bi bi-images text-sm shrink-0"></i>
                            <span>Media Library</span>
                        </Link>

                        <a
                            href="/"
                            target="_blank"
                            class="flex items-center justify-between rounded-xl px-3.5 py-2 text-sm text-slate-300 hover:bg-white/10 hover:text-white font-medium"
                        >
                            <div class="flex items-center gap-3">
                                <i class="bi bi-shop text-sm shrink-0"></i>
                                <span>Live Storefront</span>
                            </div>
                            <span class="text-[10px] text-slate-400">↗</span>
                        </a>

                        <Link
                            href="/profile"
                            :class="page.url === '/profile' ? 'bg-yellow-400/10 text-yellow-400 font-semibold' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium'"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2 text-sm"
                        >
                            <i class="bi bi-gear-fill text-sm shrink-0"></i>
                            <span>Settings</span>
                        </Link>
                    </div>
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
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur-md sm:px-6">
                <!-- Search input -->
                <div class="flex items-center gap-3 flex-1 max-w-md">
                    <button
                        class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden"
                        @click="showingSidebar = true"
                        aria-label="Toggle navigation"
                    >
                        <i class="bi bi-list text-lg shrink-0"></i>
                    </button>

                    <!-- Global Search Bar with Shortcut -->
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-search text-sm shrink-0"></i>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search products, invoices, customers, solar projects..."
                            class="w-full rounded-full border border-slate-200 bg-slate-50/70 py-1.5 pl-10 pr-12 text-xs text-slate-800 placeholder-slate-400 transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400/20"
                        />
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5">
                            <kbd class="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-400 shadow-2xs">⌘K</kbd>
                        </div>
                    </div>
                </div>

                <!-- Right Action Icons & Avatar -->
                <div class="flex items-center gap-3">
                    <!-- Quick New Sale Pill -->
                    <Link
                        v-if="has('sales.create')"
                        href="/admin/sales/create"
                        class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-[#0D1527] hover:bg-[#0D1527]/90 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs transition-colors"
                    >
                        <i class="bi bi-plus-lg text-sm shrink-0"></i>
                        <span>New Sale / POS</span>
                    </Link>

                    <!-- Notifications Bell -->
                    <button class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 transition-colors">
                        <i class="bi bi-bell text-base shrink-0"></i>
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-yellow-400 ring-2 ring-white" />
                    </button>

                    <!-- User Profile Avatar -->
                    <Link href="/profile" class="flex items-center gap-2 pl-1">
                        <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-[#0D1527] to-slate-700 text-xs font-bold text-white shadow-xs">
                            {{ (currentUser?.name || 'A').charAt(0).toUpperCase() }}
                        </div>
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