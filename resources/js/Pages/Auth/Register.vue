<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const registerFeatures = [
    { icon: 'M6 7h12l-1 13H7L6 7zM9 7a3 3 0 0 1 6 0', label: 'Track orders &amp; quotes in one place' },
    { icon: 'M14 3v5h5M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8l-5-5z', label: 'Digital invoices &amp; quotations' },
    { icon: 'M13 2 3 14h8l-1 8 10-12h-8l1-8z', label: 'Faster checkout every time' },
    { icon: 'M12 22s8-4 8-10a8 8 0 1 0-16 0c0 6 8 10 8 10zM9 12l2 2 4-4', label: 'Exclusive product offers' },
];
</script>

<template>
    <GuestLayout
        eyebrow="Join Envoy"
        title="Create Your"
        titleAccent="Free Account"
        subtitle="Save favourites, track quotes and check out faster — all in one place."
        :features="registerFeatures"
    >
        <Head title="Register">
            <meta name="robots" content="noindex, nofollow" />
        </Head>

        <!-- Header -->
        <div class="mb-8">
            <span class="inline-flex items-center gap-2 text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em] mb-3">
                <span class="w-8 h-px bg-[#40e0d0]"></span>
                Get Started
            </span>
            <h1 class="text-[26px] font-black text-slate-950 tracking-tight">
                Create Your
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-[#40e0d0]">Account</span>
            </h1>
            <p class="text-sm text-slate-600 mt-1.5">Fill in your details to get started</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Full Name</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#40e0d0]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Enter your full name"
                        class="w-full rounded-xl bg-white border-[1.5px] border-slate-200 pl-11 pr-4 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/20 transition-all"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

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
                        autocomplete="new-password"
                        placeholder="Create a password"
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

            <!-- Confirm password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Confirm Password</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#40e0d0]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m-5-3h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8a1 1 0 0 1 1-1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="Re-enter your password"
                        class="w-full rounded-xl bg-white border-[1.5px] border-slate-200 pl-11 pr-12 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/20 transition-all"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <!-- Submit (home-page "Work With Us" style) -->
            <button
                type="submit"
                :disabled="form.processing"
                class="group w-full inline-flex items-center justify-center gap-3 bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-7 py-3.5 rounded-full shadow-md transition-all duration-300 disabled:opacity-60 disabled:cursor-not-allowed mt-2"
            >
                <span class="text-[15px]">Create Free Account</span>
                <span class="w-7 h-7 rounded-full bg-yellow-400 text-slate-950 flex items-center justify-center group-hover:translate-x-0.5 transition-transform">
                    <svg v-if="form.processing" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 14-5m0 0h-4m4 0V3M20 12a8 8 0 0 1-14 5m0 0v4m0-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
            </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center gap-4 mt-7">
            <span class="flex-1 h-px bg-slate-200"></span>
            <span class="text-xs font-medium text-slate-400">Already registered?</span>
            <span class="flex-1 h-px bg-slate-200"></span>
        </div>

        <!-- Switch -->
        <div class="text-center mt-4 text-[13.5px] text-slate-600">
            Already have an account?
            <Link :href="route('login')" class="font-bold text-[#40e0d0] hover:text-amber-500 transition-colors">Sign in instead</Link>
        </div>

        </GuestLayout>
</template>