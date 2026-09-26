<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const loginFeatures = [
    { icon: 'M6 7h12l-1 13H7L6 7zM9 7a3 3 0 0 1 6 0', label: 'Track your orders &amp; quotes' },
    { icon: 'M14 3v5h5M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8l-5-5z', label: 'Download invoices &amp; quotations' },
    { icon: 'M13 2 3 14h8l-1 8 10-12h-8l1-8z', label: 'Quick checkout — zero fuss' },
    { icon: 'M12 22s8-4 8-10a8 8 0 1 0-16 0c0 6 8 10 8 10zM9 12l2 2 4-4', label: 'Order updates &amp; support' },
];
</script>

<template>
    <GuestLayout
        eyebrow="Secure Login"
        title="Welcome"
        titleAccent="Back!"
        subtitle="Continue your solar journey — access your quotes, orders and account dashboard."
        :features="loginFeatures"
    >
        <Head title="Log in">
            <meta name="robots" content="noindex, nofollow" />
        </Head>

        <!-- Header -->
        <div class="mb-8">
            <span class="inline-flex items-center gap-2 text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em] mb-3">
                <span class="w-8 h-px bg-[#40e0d0]"></span>
                Welcome Back
            </span>
            <h1 class="text-[26px] font-black text-slate-950 tracking-tight">
                Sign
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-[#40e0d0]">In</span>
            </h1>
            <p class="text-sm text-slate-600 mt-1.5">Enter your credentials to access your account</p>
        </div>

        <!-- Status alert -->
        <div v-if="status" class="flex items-center gap-2.5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 mb-6 text-sm font-medium text-emerald-700">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m8.5 12.5 2.5 2.5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#40e0d0]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 8 11 16 19 8M5 5h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="you@example.com"
                        class="w-full rounded-xl bg-white border-[1.5px] border-slate-200 pl-11 pr-4 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/20 transition-all"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#40e0d0]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M5 11h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1zM7 11V8a5 5 0 0 1 10 0v3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="Your password"
                        class="w-full rounded-xl bg-white border-[1.5px] border-slate-200 pl-11 pr-12 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/20 transition-all"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-yellow-500 transition-colors"
                        aria-label="Show or hide password"
                    >
                        <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 6.2A9.7 9.7 0 0 1 12 6c4.5 0 8 6 8 6a16.8 16.8 0 0 1-3.4 3.7M6.6 6.6A16 16 0 0 0 4 12s3.5 6 8 6a9.3 9.3 0 0 0 3.5-.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 6c4.5 0 8 6 8 6s-3.5 6-8 6-8-6-8-6 3.5-6 8-6zM12 14.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Remember / Forgot -->
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-[13px] text-slate-600 cursor-pointer select-none">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="w-4 h-4 rounded accent-yellow-500 cursor-pointer"
                    />
                    Remember me for 30 days
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-[13px] font-bold text-[#40e0d0] hover:text-amber-500 transition-colors"
                >
                    Forgot password?
                </Link>
            </div>

            <!-- Submit (home-page "Work With Us" style) -->
            <button
                type="submit"
                :disabled="form.processing"
                class="group w-full inline-flex items-center justify-center gap-3 bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-7 py-3.5 rounded-full shadow-md transition-all duration-300 disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span class="text-[15px]">Sign In to Dashboard</span>
                <span class="w-7 h-7 rounded-full bg-yellow-400 text-slate-950 flex items-center justify-center group-hover:translate-x-0.5 transition-transform">
                    <svg v-if="form.processing" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 14-5m0 0h-4m4 0V3M20 12a8 8 0 0 1-14 5m0 0v4m0-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                </span>
            </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center gap-4 mt-7">
            <span class="flex-1 h-px bg-slate-200"></span>
            <span class="text-xs font-medium text-slate-400">New here?</span>
            <span class="flex-1 h-px bg-slate-200"></span>
        </div>

        <!-- Switch -->
        <div class="text-center mt-4 text-[13.5px] text-slate-600">
            Don't have an account?
            <Link :href="route('register')" class="font-bold text-[#40e0d0] hover:text-amber-500 transition-colors">Create one — it's free</Link>
        </div>

        </GuestLayout>
</template>