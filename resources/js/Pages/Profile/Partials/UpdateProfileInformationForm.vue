<script setup>
import { useForm, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone ?? '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => form.defaults({ phone: form.phone }),
    });
};
</script>

<template>
    <section>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#0D1527] text-[#40e0d0]">
                    <i class="bi bi-person-badge"></i>
                </span>
                <div>
                    <h2 class="text-sm font-black text-[#0D1527]">Profile information</h2>
                    <p class="text-xs text-slate-400">How your name and contact details appear across the app.</p>
                </div>
            </div>
            <span class="rounded-full bg-[#40e0d0]/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-teal-700">
                Public
            </span>
        </div>

        <form class="p-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Full name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-[#40e0d0] focus:ring-[#40e0d0]/30"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <i class="bi bi-exclamation-circle mt-px shrink-0"></i>
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label for="phone" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">
                        Phone <span class="font-medium normal-case tracking-normal text-slate-400">(optional)</span>
                    </label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        autocomplete="tel"
                        placeholder="+234 800 123 4567"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-[#40e0d0] focus:ring-[#40e0d0]/30"
                    />
                    <p v-if="form.errors.phone" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                        <i class="bi bi-exclamation-circle mt-px shrink-0"></i>
                        {{ form.errors.phone }}
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-[#40e0d0] focus:ring-[#40e0d0]/30"
                />
                <p class="mt-1.5 text-xs text-slate-400">This is your sign-in address, so it has to be one you can reach.</p>
                <p v-if="form.errors.email" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                    <i class="bi bi-exclamation-circle mt-px shrink-0"></i>
                    {{ form.errors.email }}
                </p>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0D1527]/90 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <i v-if="form.processing" class="bi bi-arrow-clockwise animate-spin"></i>
                    <i v-else class="bi bi-check2-square"></i>
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700"
                    >
                        <i class="bi bi-check-circle"></i>
                        Profile updated.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
