<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import InputError from '@/Components/InputError.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    trainee: { type: Object, default: null },
});

const isEditing = !!props.trainee;

const form = useForm({
    type: props.trainee?.type || 'trainee',
    name: props.trainee?.name || '',
    phone: props.trainee?.phone || '',
    email: props.trainee?.email || '',
    date_of_birth: props.trainee?.date_of_birth || '',
    gender: props.trainee?.gender || '',
    address: props.trainee?.address || '',
    city: props.trainee?.city || '',
    education: props.trainee?.education || '',
    occupation: props.trainee?.occupation || '',
    emergency_contact_name: props.trainee?.emergency_contact_name || '',
    emergency_contact_phone: props.trainee?.emergency_contact_phone || '',
    notes: props.trainee?.notes || '',
    status: props.trainee?.status || 'active',
    create_login: false,
    password: '',
});

function submit() {
    if (isEditing) {
        form.put(route('admin.trainees.update', props.trainee.id));
    } else {
        form.post(route('admin.trainees.store'));
    }
}

const inputClass = 'w-full rounded-lg border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400/20';
</script>

<template>
    <FlashMessages />
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-black uppercase tracking-tight text-[#0D1527] sm:text-2xl">{{ isEditing ? 'Edit Trainee' : 'Add Trainee' }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ isEditing ? trainee.ref_id : 'Register a trainee, apprentice or staff member.' }}</p>
        </div>
        <Link href="/admin/trainees" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
            <i class="bi bi-arrow-left"></i> Back
        </Link>
    </div>

    <form @submit.prevent="submit" class="max-w-3xl">
        <div class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Account type</label>
                <div class="grid grid-cols-3 gap-2">
                    <button v-for="(label, value) in { trainee: 'Trainee', apprentice: 'Apprentice', staff: 'Staff' }" :key="value" type="button"
                        @click="form.type = value"
                        class="rounded-xl border-[1.5px] px-2 py-2.5 text-xs font-semibold transition-all"
                        :class="form.type === value ? 'border-yellow-500 bg-yellow-400/10 text-[#0D1527]' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                        {{ label }}
                    </button>
                </div>
                <InputError class="mt-1.5" :message="form.errors.type" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Full name *</label>
                    <input v-model="form.name" type="text" required :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.name" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Phone</label>
                    <input v-model="form.phone" type="tel" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.phone" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Email</label>
                    <input v-model="form.email" type="email" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.email" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Date of birth</label>
                    <input v-model="form.date_of_birth" type="date" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.date_of_birth" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Gender</label>
                    <select v-model="form.gender" :class="inputClass">
                        <option value="">—</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                    <InputError class="mt-1.5" :message="form.errors.gender" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">City</label>
                    <input v-model="form.city" type="text" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.city" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Address</label>
                    <input v-model="form.address" type="text" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.address" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Education</label>
                    <input v-model="form.education" type="text" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.education" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Occupation</label>
                    <input v-model="form.occupation" type="text" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.occupation" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Emergency contact name</label>
                    <input v-model="form.emergency_contact_name" type="text" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.emergency_contact_name" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Emergency contact phone</label>
                    <input v-model="form.emergency_contact_phone" type="tel" :class="inputClass" />
                    <InputError class="mt-1.5" :message="form.errors.emergency_contact_phone" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Status</label>
                    <select v-model="form.status" :class="inputClass">
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="graduated">Graduated</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                    <InputError class="mt-1.5" :message="form.errors.status" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Notes</label>
                    <textarea v-model="form.notes" rows="3" :class="inputClass"></textarea>
                    <InputError class="mt-1.5" :message="form.errors.notes" />
                </div>
            </div>

            <!-- Login access (create only) -->
            <div v-if="!isEditing" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="flex items-start gap-2.5">
                    <input v-model="form.create_login" type="checkbox" class="mt-0.5 h-4 w-4 rounded accent-yellow-500" />
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Create portal login access</span>
                        <span class="block text-xs text-slate-500">If the email already belongs to a registered portal user, we link them instead of creating a duplicate.</span>
                    </span>
                </label>
                <div v-if="form.create_login" class="mt-4">
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700">Set an initial password</label>
                            <input v-model="form.password" type="text" placeholder="Leave blank for a random password" :class="inputClass" />
                            <InputError class="mt-1.5" :message="form.errors.password" />
                        </div>
                        <button type="button" @click="form.password = Math.random().toString(36).slice(2, 10)" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white">Random</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" @click="form.reset()" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</button>
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-xl bg-[#0D1527] px-6 py-2.5 text-sm font-bold text-white hover:bg-[#0D1527]/90 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <i v-if="form.processing" class="bi bi-arrow-clockwise animate-spin"></i>
                <i v-else :class="isEditing ? 'bi bi-check2-square' : 'bi bi-plus-lg'"></i>
                {{ isEditing ? 'Save Changes' : 'Add Trainee' }}
            </button>
        </div>
    </form>
</template>