<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputError from '@/Components/InputError.vue';

defineOptions({ layout: PortalLayout });

const props = defineProps({
    trainee: { type: Object, default: null },
});

const form = useForm({
    type: props.trainee?.type || 'trainee',
    name: props.trainee?.name || '',
    phone: props.trainee?.phone || '',
    date_of_birth: props.trainee?.date_of_birth || '',
    gender: props.trainee?.gender || '',
    address: props.trainee?.address || '',
    city: props.trainee?.city || '',
    education: props.trainee?.education || '',
    occupation: props.trainee?.occupation || '',
    emergency_contact_name: props.trainee?.emergency_contact_name || '',
    emergency_contact_phone: props.trainee?.emergency_contact_phone || '',
});

const typeLabels = { staff: 'Staff', apprentice: 'Apprentice', trainee: 'Trainee' };

const prettyName = (name) => {
    let n = (name || '').trim();
    if (!n) n = 'Trainee';
    if (n.includes('@')) {
        n = n.split('@')[0].replace(/[._-]+/g, ' ').trim();
        if (!n) n = 'Trainee';
    }
    return n;
};

const initials = computed(() => prettyName(form.name).split(' ').map((p) => p.charAt(0)).join('').slice(0, 2).toUpperCase());

const meta = computed(() => [
    { label: 'Account type', value: typeLabels[form.type], icon: 'bi-briefcase' },
    { label: 'Email', value: props.trainee?.email || '—', icon: 'bi-envelope' },
    { label: 'Phone', value: form.phone || '—', icon: 'bi-telephone' },
    { label: 'Location', value: form.city || '—', icon: 'bi-geo-alt' },
    { label: 'Occupation', value: form.occupation || '—', icon: 'bi-tools' },
]);

function submit() {
    form.put(route('portal.profile.update'));
}

const inputClass = 'w-full rounded-xl border-[1.5px] border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-yellow-500 focus:outline-none focus:ring-4 focus:ring-yellow-400/20 transition-colors';
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-[#40e0d0]">
                    <span class="h-px w-6 bg-[#40e0d0]"></span> Account details
                </p>
                <h1 class="mt-2 text-2xl font-black tracking-tight text-[#0D1527]">My Profile</h1>
                <p class="mt-1 text-sm text-slate-500">Keep your details up to date so we can track your training correctly.</p>
            </div>
            <span class="hidden rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-500 sm:inline-flex">
                <i class="bi bi-person-vcard mr-1.5 text-[#40e0d0]"></i>{{ props.trainee?.ref_id || '—' }}
            </span>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-5">
            <!-- ===== Snapshot card ===== -->
            <aside class="lg:col-span-2">
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] p-6 text-white shadow-[0_18px_44px_-16px_rgba(13,21,39,0.5)] lg:sticky lg:top-6">
                    <div class="absolute inset-0" aria-hidden="true">
                        <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px); background-size: 26px 26px;"></div>
                        <div class="absolute -top-14 -right-12 h-40 w-40 rounded-full bg-yellow-400/10 blur-[60px]"></div>
                        <div class="absolute -bottom-14 -left-12 h-40 w-40 rounded-full bg-[#40e0d0]/10 blur-[60px]"></div>
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-yellow-300 to-amber-500 text-lg font-black text-[#0D1527] shadow-[0_10px_24px_-8px_rgba(245,158,11,0.7)]">
                                {{ initials }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-black">{{ prettyName(form.name) }}</p>
                                <p v-if="props.trainee" class="mt-0.5 font-mono text-[11px] text-slate-400">{{ props.trainee.ref_id }}</p>
                                <p v-else class="mt-0.5 text-[11px] text-slate-400">Pending first save</p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-2.5">
                            <span class="h-px w-7 bg-gradient-to-r from-transparent via-yellow-400/50 to-yellow-400"></span>
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-[#FDE047]">Snapshot</p>
                        </div>

                        <dl class="mt-4 space-y-3">
                            <div v-for="m in meta" :key="m.label" class="flex items-center justify-between gap-3 rounded-xl border border-white/5 bg-white/[0.05] px-3.5 py-2.5">
                                <dt class="flex items-center gap-2 text-[11px] font-semibold text-slate-400">
                                    <i :class="`bi ${m.icon} text-[#40e0d0]`"></i> {{ m.label }}
                                </dt>
                                <dd class="max-w-[60%] truncate text-right text-xs font-bold capitalize">{{ m.value }}</dd>
                            </div>
                        </dl>

                        <p class="mt-5 rounded-xl bg-white/[0.06] p-3.5 text-[11px] leading-relaxed text-slate-400">
                            <i class="bi bi-info-circle mr-1 text-yellow-400"></i>
                            Details here appear on your certificates exactly as entered.
                        </p>
                    </div>
                </div>
            </aside>

            <!-- ===== Editable form ===== -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-[0_12px_34px_rgba(13,21,39,0.06)] sm:p-8 lg:col-span-3">
                <!-- Section : identity -->
                <div class="mb-6 flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#40e0d0]/10 text-[#40e0d0]"><i class="bi bi-person-vcard text-sm"></i></span>
                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-800">Identity</h2>
                    <span class="h-px flex-1 bg-slate-100"></span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Account type</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="(label, value) in typeLabels"
                                :key="value"
                                type="button"
                                @click="form.type = value"
                                class="rounded-xl border-[1.5px] px-2 py-2.5 text-xs font-semibold transition-all"
                                :class="form.type === value ? 'border-yellow-500 bg-yellow-400/10 text-[#0D1527]' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                            >
                                {{ label }}
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Full name *</label>
                        <input v-model="form.name" type="text" required :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Phone</label>
                        <input v-model="form.phone" type="tel" :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>
                </div>

                <!-- Section : background -->
                <div class="mb-6 mt-10 flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#40e0d0]/10 text-[#40e0d0]"><i class="bi bi-mortarboard text-sm"></i></span>
                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-800">Background & education</h2>
                    <span class="h-px flex-1 bg-slate-100"></span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Date of birth</label>
                        <input v-model="form.date_of_birth" type="date" :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.date_of_birth" />
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Gender</label>
                        <select v-model="form.gender" :class="inputClass">
                            <option value="">Prefer not to say</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.gender" />
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Education</label>
                        <input v-model="form.education" type="text" placeholder="e.g. ND Electrical Engineering" :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.education" />
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Occupation</label>
                        <input v-model="form.occupation" type="text" placeholder="e.g. Solar installer" :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.occupation" />
                    </div>
                </div>

                <!-- Section : address -->
                <div class="mb-6 mt-10 flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#40e0d0]/10 text-[#40e0d0]"><i class="bi bi-geo-alt text-sm"></i></span>
                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-800">Address</h2>
                    <span class="h-px flex-1 bg-slate-100"></span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Street address</label>
                        <input v-model="form.address" type="text" :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">City / Town</label>
                        <input v-model="form.city" type="text" :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.city" />
                    </div>
                </div>

                <!-- Section : emergency contact -->
                <div class="mb-6 mt-10 flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#40e0d0]/10 text-[#40e0d0]"><i class="bi bi-shield-plus text-sm"></i></span>
                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-800">Emergency contact</h2>
                    <span class="h-px flex-1 bg-slate-100"></span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Contact name</label>
                        <input v-model="form.emergency_contact_name" type="text" :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.emergency_contact_name" />
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Contact phone</label>
                        <input v-model="form.emergency_contact_phone" type="tel" :class="inputClass" />
                        <InputError class="mt-2" :message="form.errors.emergency_contact_phone" />
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-6">
                    <p class="text-xs text-slate-400">Changes are saved to your trainee record.</p>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2.5 rounded-full bg-[#0D1527] px-6 py-3 text-sm font-black text-white shadow-md transition-all hover:bg-slate-800 hover:shadow-[0_10px_24px_-8px_rgba(13,21,39,0.5)] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <i v-if="form.processing" class="bi bi-arrow-clockwise animate-spin"></i>
                        <i v-else class="bi bi-check2-square"></i>
                        Save changes
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-400 text-[#0D1527]"><i class="bi bi-check2 text-xs font-black"></i></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>