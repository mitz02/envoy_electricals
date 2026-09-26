<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, onUnmounted, ref, watch } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const openModal = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const onKeydown = (event) => {
    if (event.key === 'Escape' && confirmingUserDeletion.value) {
        event.preventDefault();
        closeModal();
    }
};

// Listeners live on the document so Escape works even if focus has left the
// dialog, and the scroll lock is released as soon as the dialog closes.
watch(confirmingUserDeletion, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';

    if (open) {
        document.addEventListener('keydown', onKeydown);
    } else {
        document.removeEventListener('keydown', onKeydown);
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-red-200 bg-white shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-red-100 bg-red-50/60 px-5 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                    <i class="bi bi-exclamation-triangle"></i>
                </span>
                <div>
                    <h2 class="text-sm font-black text-red-900">Delete account</h2>
                    <p class="text-xs text-red-500">This cannot be undone.</p>
                </div>
            </div>
            <span class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-red-700">Danger zone</span>
        </div>

        <div class="p-5">
            <div class="rounded-xl bg-[#FAF8F2] p-4">
                <p class="text-xs leading-relaxed text-slate-600">
                    Deleting your account signs you out and permanently removes everything tied to it, including your
                    staff record, audit history and stock movements. Download anything you need to keep first.
                </p>
            </div>

            <button
                type="button"
                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                @click="openModal"
            >
                <i class="bi bi-trash"></i>
                Delete my account
            </button>
        </div>

        <!-- Brand confirmation dialog -->
        <div
            v-if="confirmingUserDeletion"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0D1527]/80 p-4 backdrop-blur-md"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-account-heading"
            @click.self="closeModal"
        >
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-gradient-to-r from-[#0D1527] to-[#1A365D] px-6 py-4">
                    <h3 id="delete-account-heading" class="font-extrabold text-white">Delete your account?</h3>
                    <button
                        type="button"
                        class="p-1 text-xl font-bold text-white/70 transition hover:text-white"
                        aria-label="Close"
                        @click="closeModal"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-4 p-6">
                    <p class="text-sm leading-relaxed text-slate-600">
                        Your account and everything attached to it will be permanently deleted. This cannot be undone.
                        Enter your password to confirm.
                    </p>

                    <div>
                        <label for="delete_password" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Password</label>
                        <input
                            id="delete_password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Your password"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-red-400 focus:ring-red-400/30"
                            @keyup.enter="deleteUser"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-red-600">
                            <i class="bi bi-exclamation-circle mt-px shrink-0"></i>
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            @click="closeModal"
                        >
                            Keep my account
                        </button>
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50"
                            @click="deleteUser"
                        >
                            <i v-if="form.processing" class="bi bi-arrow-clockwise animate-spin"></i>
                            <i v-else class="bi bi-trash"></i>
                            {{ form.processing ? 'Deleting…' : 'Delete permanently' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
