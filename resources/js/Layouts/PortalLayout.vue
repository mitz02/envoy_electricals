<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';

const page = usePage();
const menuOpen = ref(false);

const user = computed(() => page.props.auth?.user || { name: 'Trainee' });
const trainee = computed(() => page.props.trainee || null);

const prettyName = (name, fallback = 'Trainee') => {
    let n = (name || '').trim();
    if (!n) n = fallback;
    if (n.includes('@')) {
        n = n.split('@')[0].replace(/[._-]+/g, ' ').trim();
        if (!n) n = fallback;
    }
    return n;
};
const displayName = computed(() => prettyName(trainee.value?.name || user.value.name));

const typeLabels = { staff: 'Staff', apprentice: 'Apprentice', trainee: 'Trainee' };
const displayMeta = computed(() => {
    if (trainee.value?.ref_id) return trainee.value.ref_id;
    return trainee.value?.type ? typeLabels[trainee.value.type] : '';
});

const initials = computed(() =>
    (displayName.value || 'T')
        .replace(/\s+/g, ' ')
        .split(' ')
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase()
);

const links = [
    { label: 'Dashboard', href: '/portal/dashboard', icon: 'bi-grid-1x2-fill' },
    { label: 'My Profile', href: '/portal/profile', icon: 'bi-person-fill' },
    { label: 'My Certificates', href: '/portal/certificates', icon: 'bi-patch-check-fill' },
];

const isActive = (href) => page.url === href || (href !== '/portal/dashboard' && page.url.startsWith(href));

function logout() {
    router.post('/portal/logout');
}
</script>

<template>
    <div class="min-h-screen bg-[#FAF8F2] text-slate-800 antialiased">
        <!-- ===== Desktop sidebar (mirrors /login brand panel) ===== -->
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] shadow-2xl lg:flex">
            <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
                <div
                    class="absolute inset-0"
                    style="background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px); background-size: 28px 28px;"
                ></div>
                <div class="absolute -top-20 -right-16 h-64 w-64 rounded-full bg-yellow-400/[0.10] blur-[90px]"></div>
                <div class="absolute bottom-0 -left-16 h-64 w-64 rounded-full bg-[#40e0d0]/[0.08] blur-[90px]"></div>
            </div>

            <!-- Brand -->
            <div class="relative z-10 px-6 pt-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white p-1 shadow-lg ring-1 ring-white/10">
                        <img src="/envoy_images/logo.png" alt="Envoy Electric" class="h-full w-full object-contain" />
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-[15px] font-black tracking-tight text-white">Envoy Academy<span class="text-yellow-400">.</span></span>
                        <span class="text-[9px] font-semibold uppercase tracking-[0.22em] text-slate-400">Trainee Portal</span>
                    </div>
                </div>

                <!-- Sun ornament (echoes /login) -->
                <div class="mt-6 flex items-center gap-2.5">
                    <span class="h-px w-8 bg-gradient-to-r from-transparent via-[#FACC15]/50 to-[#FACC15]"></span>
                    <svg class="h-5 w-5 text-[#FDE047] sun-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3" fill="#FDE047" stroke="none" />
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" stroke-width="2.2" />
                    </svg>
                    <span class="h-px w-8 bg-gradient-to-l from-transparent via-[#40e0d0]/60 to-[#40e0d0]"></span>
                </div>
            </div>

            <!-- Nav -->
            <nav class="relative z-10 mt-7 flex-1 space-y-1.5 overflow-y-auto px-4">
                <Link
                    v-for="l in links"
                    :key="l.href"
                    :href="l.href"
                    class="group relative flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold transition-all duration-200"
                    :class="isActive(l.href) ? 'bg-white/[0.06] text-white ring-1 ring-white/10' : 'text-slate-300/80 hover:bg-white/5 hover:text-white'"
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                        :class="isActive(l.href) ? 'bg-[#40e0d0]/15 text-[#40e0d0]' : 'bg-white/5 text-slate-300/80 group-hover:bg-white/10 group-hover:text-white'"
                    >
                        <i :class="`bi ${l.icon} text-sm`"></i>
                    </span>
                    <span>{{ l.label }}</span>
                    <span v-if="isActive(l.href)" class="absolute right-3 h-1.5 w-1.5 rounded-full bg-yellow-400"></span>
                </Link>

                <div class="pt-5">
                    <p class="px-3.5 pb-2 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">General</p>
                    <a
                        href="/"
                        class="group flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold text-slate-300/80 transition-all duration-200 hover:bg-white/5 hover:text-white"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/5 text-slate-300/80 transition-colors group-hover:bg-white/10 group-hover:text-white">
                            <i class="bi bi-globe2 text-sm"></i>
                        </span>
                        <span>Back to website</span>
                    </a>
                </div>
            </nav>

            <!-- User + logout -->
            <div class="relative z-10 px-4 pb-6">
                <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.06] p-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-yellow-300 to-amber-500 text-xs font-black text-[#0D1527] shadow-md">
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-bold text-white">{{ displayName }}</p>
                        <p v-if="displayMeta" class="truncate font-mono text-[10px] text-slate-400">{{ displayMeta }}</p>
                        <p v-else class="text-[10px] text-slate-400">Signed in as trainee</p>
                    </div>
                    <button
                        @click="logout"
                        title="Sign out"
                        class="shrink-0 rounded-lg p-2 text-slate-400 transition-colors hover:bg-white/10 hover:text-white"
                    >
                        <i class="bi bi-box-arrow-right text-base"></i>
                    </button>
                </div>
                <p class="mt-4 px-2 text-center text-[10px] leading-relaxed text-slate-500">© 2026 Envoy Electricals Ltd.</p>
            </div>
        </aside>

        <!-- ===== Mobile top bar ===== -->
        <header class="sticky top-0 z-40 bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] shadow-md lg:hidden">
            <div class="flex h-14 items-center justify-between px-4">
                <div class="flex items-center gap-2.5">
                    <button
                        class="rounded-lg p-2 text-slate-300 transition-colors hover:bg-white/10 hover:text-white"
                        aria-label="Toggle navigation"
                        @click="menuOpen = !menuOpen"
                    >
                        <i :class="menuOpen ? 'bi bi-x-lg' : 'bi bi-list'" class="text-xl"></i>
                    </button>
                    <Link href="/portal/dashboard" class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white p-1">
                            <img src="/envoy_images/logo.png" alt="Envoy Electric" class="h-full w-full object-contain" />
                        </div>
                        <div class="flex flex-col leading-tight">
                            <span class="text-sm font-black text-white">Envoy Academy<span class="text-yellow-400">.</span></span>
                            <span class="text-[8px] font-semibold uppercase tracking-widest text-slate-400">Trainee Portal</span>
                        </div>
                    </Link>
                </div>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-yellow-300 to-amber-500 text-xs font-black text-[#0D1527] shadow-md">
                    {{ initials }}
                </div>
            </div>
        </header>

        <!-- ===== Mobile drawer ===== -->
        <transition name="drawer">
            <div
                v-if="menuOpen"
                class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm lg:hidden"
                @click.self="menuOpen = false"
            >
                <aside class="aside-inner relative flex h-full w-72 flex-col bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] shadow-2xl">
                    <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
                        <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px); background-size: 28px 28px;"></div>
                        <div class="absolute -top-20 -right-16 h-56 w-56 rounded-full bg-yellow-400/[0.10] blur-[80px]"></div>
                    </div>

                    <div class="relative z-10 flex items-center justify-between px-6 py-5">
                        <span class="text-[15px] font-black tracking-tight text-white">Envoy Academy<span class="text-yellow-400">.</span></span>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-white/10 hover:text-white"
                            @click="menuOpen = false"
                            aria-label="Close menu"
                        >
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <nav class="relative z-10 flex-1 space-y-1.5 overflow-y-auto px-4">
                        <Link
                            v-for="l in links"
                            :key="l.href"
                            :href="l.href"
                            @click="menuOpen = false"
                            class="group flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold transition-all duration-200"
                            :class="isActive(l.href) ? 'bg-white/[0.06] text-white ring-1 ring-white/10' : 'text-slate-300/80 hover:bg-white/5 hover:text-white'"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                                :class="isActive(l.href) ? 'bg-[#40e0d0]/15 text-[#40e0d0]' : 'bg-white/5 text-slate-300/80 group-hover:bg-white/10 group-hover:text-white'"
                            >
                                <i :class="`bi ${l.icon} text-sm`"></i>
                            </span>
                            <span>{{ l.label }}</span>
                        </Link>

                        <a
                            href="/"
                            class="group flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold text-slate-300/80 transition-all duration-200 hover:bg-white/5 hover:text-white"
                        >
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/5 text-slate-300/80 transition-colors group-hover:bg-white/10 group-hover:text-white">
                                <i class="bi bi-globe2 text-sm"></i>
                            </span>
                            <span>Back to website</span>
                        </a>
                    </nav>

                    <div class="relative z-10 border-t border-white/10 px-6 py-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-yellow-300 to-amber-500 text-xs font-black text-[#0D1527]">
                                    {{ initials }}
                                </div>
                                <div class="min-w-0">
                                    <p class="max-w-[140px] truncate text-[13px] font-bold text-white">{{ displayName }}</p>
                                    <p v-if="displayMeta" class="truncate font-mono text-[10px] text-slate-400">{{ displayMeta }}</p>
                                </div>
                            </div>
                            <button
                                @click="logout"
                                title="Sign out"
                                class="shrink-0 rounded-lg p-2 text-slate-400 transition-colors hover:bg-white/10 hover:text-white"
                            >
                                <i class="bi bi-box-arrow-right text-base"></i>
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </transition>

        <!-- ===== Content ===== -->
        <div class="lg:pl-64">
            <FlashMessages />

            <main class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
                <slot />
            </main>

            <footer class="mx-auto max-w-5xl px-4 pb-8 sm:px-6">
                <div class="hidden items-center justify-between border-t border-slate-200 pt-4 text-xs text-slate-400 lg:flex">
                    <p>© 2026 Envoy Electricals Ltd. All rights reserved.</p>
                    <p class="text-slate-500">Powered by the sun.</p>
                </div>
            </footer>
        </div>
    </div>
</template>

<style scoped>
@keyframes sunSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.sun-spin {
    animation: sunSpin 24s linear infinite;
}

.drawer-enter-active,
.drawer-leave-active {
    transition: opacity 0.25s ease;
}
.drawer-enter-active .aside-inner,
.drawer-leave-active .aside-inner {
    transition: transform 0.25s ease;
}
.drawer-enter-from .aside-inner,
.drawer-leave-to .aside-inner {
    transform: translateX(-100%);
}
.drawer-enter-from,
.drawer-leave-to {
    opacity: 0;
}
</style>