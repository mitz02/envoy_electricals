<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    estimateHref: { type: String, default: null },
});

const emit = defineEmits(['estimate']);

const estimateInput = ref('');

const newsletter = useForm({
    email: '',
});

const newsletterSubscribed = ref(false);
const newsletterFailed = ref(false);
const newsletterMessage = ref('');

const newsletterFeedback = computed(() => {
    if (newsletter.errors.email) return { text: newsletter.errors.email, ok: false };
    if (newsletterSubscribed.value) return { text: newsletterMessage.value || "You're subscribed — welcome aboard!", ok: true };
    if (newsletterFailed.value) return { text: 'Something went wrong. Please try again.', ok: false };
    return null;
});

function handleEstimateAction() {
    if (!props.estimateHref) {
        emit('estimate', estimateInput.value);
    }
}

function subscribeNewsletter() {
    if (!newsletter.email.trim()) return;

    newsletterFailed.value = false;
    newsletterMessage.value = '';

    newsletter.post(route('newsletter.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newsletterSubscribed.value = true;
            newsletterMessage.value = `You're subscribed — welcome aboard, ${newsletter.email}!`;
            newsletter.reset();
            setTimeout(() => {
                newsletterSubscribed.value = false;
            }, 5000);
        },
        onError: () => {
            newsletterFailed.value = true;
        },
    });
}
</script>

<template>
    <footer id="contact" class="relative bg-[#111827] text-white overflow-hidden">
        <!-- Subtle background: gradient mesh + fine cross-hatch -->
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute inset-0 bg-gradient-to-br from-[#0D1527] via-[#111827] to-[#0F1A2E]"></div>
            <div class="absolute inset-0 opacity-[0.035]" style="background-image: repeating-linear-gradient(0deg,transparent,transparent 39px,rgba(255,255,255,.08) 39px,rgba(255,255,255,.08) 40px), repeating-linear-gradient(90deg,transparent,transparent 39px,rgba(255,255,255,.08) 39px,rgba(255,255,255,.08) 40px);"></div>
            <div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full bg-yellow-400/[0.04] blur-[100px]"></div>
            <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] rounded-full bg-[#40e0d0]/[0.04] blur-[100px]"></div>
        </div>

        <!-- CTA Section -->
        <div class="relative max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 pt-14 pb-14 z-10">
            <div class="flex flex-col lg:flex-row items-center lg:items-center justify-between gap-10">
                <div class="lg:max-w-lg text-center lg:text-left">
                    <div class="inline-flex items-center gap-2.5 mb-5">
                        <span class="w-10 h-[2px] bg-[#40e0d0]"></span>
                        <span class="text-[#40e0d0] text-[11px] font-bold uppercase tracking-[0.25em]">Free &amp; No-Obligation</span>
                    </div>
                    <h2 class="text-[26px] sm:text-[32px] lg:text-[38px] font-black uppercase leading-[1.1] tracking-tight">
                        Get Your Free<br class="hidden sm:block" /> Solar Estimate
                    </h2>
                    <p class="mt-3 text-slate-400 text-sm leading-relaxed max-w-md mx-auto lg:mx-0">Tell us about your energy needs and our engineers will design the perfect system for you — completely free.</p>
                </div>

                <div class="w-full max-w-md">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/10 rounded-2xl p-3 flex items-center gap-3">
                        <div class="flex-1 pl-3">
                            <input
                                type="text"
                                v-model="estimateInput"
                                placeholder="Enter your phone or email"
                                class="w-full bg-transparent border-0 text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-0 py-1"
                                @keyup.enter="handleEstimateAction"
                            />
                        </div>
                        <Link
                            v-if="estimateHref"
                            :href="estimateHref"
                            class="shrink-0 px-6 py-3 rounded-xl bg-gradient-to-r from-yellow-400 to-amber-400 text-slate-950 text-sm font-bold hover:brightness-110 transition-all duration-200 shadow-lg shadow-yellow-400/20"
                        >
                            Get Started
                        </Link>
                        <button
                            v-else
                            type="button"
                            @click="handleEstimateAction"
                            class="shrink-0 px-6 py-3 rounded-xl bg-gradient-to-r from-yellow-400 to-amber-400 text-slate-950 text-sm font-bold hover:brightness-110 transition-all duration-200 shadow-lg shadow-yellow-400/20"
                        >
                            Get Started
                        </button>
                    </div>
                    <p class="mt-3 text-center text-[11px] text-slate-500">Our engineers typically reply within 24 hours.</p>
                </div>
            </div>
        </div>

        <!-- Thin accent line -->
        <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
            <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
        </div>

        <!-- Main Footer Grid -->
        <div class="relative max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 pt-12 pb-12 z-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8">

                <!-- Column 1: Brand (spans 4 cols) -->
                <div class="lg:col-span-4">
                    <p class="text-slate-400 text-[13px] leading-relaxed mb-7 max-w-xs">
                        Clean, affordable, and sustainable solar energy. We design, supply, install, and maintain quality solar power systems across Nigeria.
                    </p>
                    <!-- Newsletter -->
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-2">Newsletter</label>
                    <div class="flex items-center gap-2 max-w-xs">
                        <input
                            v-model="newsletter.email"
                            type="email"
                            placeholder="Your email"
                            class="flex-1 min-w-0 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white placeholder:text-slate-600 focus:outline-none focus:border-yellow-400/50 transition"
                            @keyup.enter="subscribeNewsletter"
                            :disabled="newsletter.processing"
                        />
                        <button
                            type="button"
                            @click="subscribeNewsletter"
                            class="shrink-0 h-[38px] px-4 rounded-lg bg-yellow-400 text-slate-950 text-xs font-bold hover:bg-yellow-300 transition disabled:opacity-50"
                            title="Subscribe"
                            :disabled="newsletter.processing"
                        >
                            {{ newsletter.processing ? 'Adding…' : 'Join' }}
                        </button>
                    </div>
                    <p v-if="newsletterFeedback" class="mt-2 text-xs font-semibold" :class="newsletterFeedback.ok ? 'text-[#40e0d0]' : 'text-red-400'">
                        {{ newsletterFeedback.text }}
                    </p>
                </div>

                <!-- Column 2: Explore (spans 3 cols) -->
                <div class="lg:col-span-3">
                    <h4 class="text-xs font-bold text-white mb-5 uppercase tracking-[0.15em]">Explore</h4>
                    <ul class="space-y-3">
                        <li><Link :href="route('shop')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Shop Solar Products</Link></li>
                        <li><Link :href="route('packages')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Solar Packages</Link></li>
                        <li><Link :href="route('projects')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Our Projects</Link></li>
                        <li><Link :href="route('calculator')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Load Calculator</Link></li>
                        <li><Link :href="route('about')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">About Us</Link></li>
                        <li><Link :href="route('contact')" class="text-[13px] text-slate-400 hover:text-yellow-400 transition-colors duration-150">Contact Us</Link></li>
                    </ul>
                </div>

                <!-- Column 3: Contact (spans 3 cols) -->
                <div class="lg:col-span-3">
                    <h4 class="text-xs font-bold text-white mb-5 uppercase tracking-[0.15em]">Contact</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <span class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6 19.79 19.79 0 0 1 1.62 5.07 2 2 0 0 1 3.59 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.6a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 18z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Phone</p>
                                <p class="text-[13px] text-slate-300">+234 809 708 9259</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5 text-[#40e0d0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </span>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Email</p>
                                <p class="text-[13px] text-slate-300">hello@envoyelectricals.com</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </span>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-0.5">Address</p>
                                <p class="text-[13px] text-slate-300 leading-relaxed">Shop 1, Peace Avenue Junction,<br>Futa Southgate Road, Akure, Ondo State</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Column 4: Follow Us + Hours (spans 2 cols) -->
                <div class="lg:col-span-2">
                    <h4 class="text-xs font-bold text-white mb-5 uppercase tracking-[0.15em]">Follow Us</h4>
                    <div class="flex items-center gap-2 mb-8">
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all duration-200" title="Instagram">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all duration-200" title="X / Twitter">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all duration-200" title="Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-yellow-400 hover:text-slate-950 hover:border-yellow-400 transition-all duration-200" title="LinkedIn">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                    </div>
                    <h4 class="text-xs font-bold text-white mb-3 uppercase tracking-[0.15em]">Open Hours</h4>
                    <div class="space-y-1">
                        <p class="text-[13px] text-slate-300">Mon – Fri: <span class="text-white font-medium">8 AM – 6 PM</span></p>
                        <p class="text-[13px] text-slate-300">Saturday: <span class="text-white font-medium">9 AM – 4 PM</span></p>
                        <p class="text-[13px] text-slate-500">Sunday: Closed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="relative z-10 border-t border-white/[0.06]">
            <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-[11px] text-slate-600">&copy; {{ new Date().getFullYear() }} Envoy Electricals. All rights reserved.</p>
                <nav class="flex items-center gap-5 text-[11px] text-slate-500">
                    <a href="#hero" class="hover:text-white transition">Back to top</a>
                    <Link :href="route('about')" class="hover:text-white transition">Privacy</Link>
                    <Link :href="route('about')" class="hover:text-white transition">Terms</Link>
                </nav>
            </div>
        </div>
    </footer>
</template>