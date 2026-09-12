<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout
        eyebrow="Password Recovery"
        title="Forgot Your"
        titleAccent="Password?"
        subtitle="No problem. Tell us your email address and we'll send you a secure link to reset your password."
    >
        <Head title="Forgot Password" />

        <!-- Header -->
        <div class="mb-8">
            <span class="inline-flex items-center gap-2 text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em] mb-3">
                <span class="w-8 h-px bg-[#40e0d0]"></span>
                Reset Password
            </span>
            <h1 class="text-[26px] font-black text-slate-950 tracking-tight">
                Forgot Your
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-[#40e0d0]">Password?</span>
            </h1>
            <p class="text-sm text-slate-600 mt-1.5">Enter the email you registered with and we'll send you a reset link.</p>
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

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="group w-full inline-flex items-center justify-center gap-3 bg-[#0D1527] hover:bg-slate-800 text-white font-bold px-7 py-3.5 rounded-full shadow-md transition-all duration-300 disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span class="text-[15px]">Email Password Reset Link</span>
                <span class="w-7 h-7 rounded-full bg-yellow-400 text-slate-950 flex items-center justify-center group-hover:translate-x-0.5 transition-transform">
                    <svg v-if="form.processing" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 14-5m0 0h-4m4 0V3M20 12a8 8 0 0 1-14 5m0 0v4m0-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg v-else class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </span>
            </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center gap-4 mt-7">
            <span class="flex-1 h-px bg-slate-200"></span>
            <span class="text-xs font-medium text-slate-400">Remembered it?</span>
            <span class="flex-1 h-px bg-slate-200"></span>
        </div>

        <!-- Switch -->
        <div class="text-center mt-4 text-[13.5px] text-slate-600">
            <Link :href="route('login')" class="font-bold text-[#40e0d0] hover:text-amber-500 transition-colors">Back to sign in</Link>
        </div>
    </GuestLayout>
</template>