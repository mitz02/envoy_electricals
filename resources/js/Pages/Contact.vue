<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const isContactModalOpen = ref(false);

const form = useForm({
    customer_name: '',
    customer_email: '',
    phone: '',
    comment: '',
    experience: '',
    suggestion: '',
});

const callbackForm = useForm({
    name: '',
    phone: '',
});

const submitted = ref(false);
const callbackSubmitted = ref(false);

function submitForm() {
    form.post('/feedback', {
        onSuccess: () => {
            submitted.value = true;
            form.reset();
        },
    });
}

function submitCallback() {
    callbackForm.post('/feedback', {
        onSuccess: () => {
            callbackSubmitted.value = true;
            callbackForm.reset();
        },
    }, {
        data: {
            comment: `Callback request: ${callbackForm.value.phone}`,
        },
    });
}

function resetForm() {
    submitted.value = false;
}

const infoCards = [
    {
        icon: 'M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11zM10 10a2 2 0 1 1 4 0 2 2 0 0 1-4 0z',
        title: 'Office Address',
        lines: ['Shop 1, Peace Avenue Junction,', 'opp Goddy Royal Hotel, Futa Southgate Road, Akure'],
        href: null,
    },
    {
        icon: 'M2 4l3 0 2 7-2.5 1.5a15 15 0 0 0 5 5L11 15l7 2 0 3a2 2 0 0 1-2 2A16 16 0 0 1 0 6a2 2 0 0 1 2-2z',
        title: 'Telephone',
        lines: [],
        href: 'tel:+2348097089259',
        linkText: '+234 809 708 9259',
    },
    {
        icon: 'M3 8 11 16 19 8M5 5h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
        title: 'Email Address',
        lines: [],
        href: 'mailto:hello@envoyelectricals.com',
        linkText: 'hello@envoyelectricals.com',
    },
];

const supportCards = [
    {
        icon: 'M12 6v6l4 2M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20z',
        title: 'Office Hours',
        body: 'Mon – Sat: 8:00am – 6:00pm\nSunday: emergency support only',
        badge: true,
        badgeText: 'Open Now',
        link: null,
        linkText: null,
    },
    {
        icon: 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm4.5 12.8c.2.1 1.4.7 1.6.8s.4.1.5.1.6-.8.7-.9a.6.6 0 0 0-.2-.7c-.1-.2-.4-.3-.7-.4s-1.3-.6-1.5-.7-.4-.1-.5.1-.6.8-.7.9-.2.2-.5.1a7.7 7.7 0 0 1-1.9-1.2 7.3 7.3 0 0 1-1.4-1.7c-.1-.2 0-.4.1-.5l.4-.5.2-.4v-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.5 4 5 5 0 0 0 2.7.7 3 3 0 0 0 1.9-1.3 2.3 2.3 0 0 0 .2-1.3z',
        title: 'Instant WhatsApp Support',
        body: 'Quick questions about products, pricing or quotes? Chat with our sales team directly.',
        badge: false,
        badgeText: null,
        link: 'https://wa.me/2348097089259',
        linkText: 'Chat with Support',
    },
    {
        icon: 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zM9 8.5v7M15 9.5a2.5 2.5 0 0 0-5 0c0 1.5 2.5 2 2.5 3.5a2 2 0 0 1-2 2M12 16.5v.5',
        title: 'Book a Free Site Survey',
        body: 'Schedule a free site assessment with our engineers and get an accurate system design.',
        badge: false,
        badgeText: null,
        link: 'tel:+2348097089259',
        linkText: 'Call Our Advisor',
    },
];

const faqs = [
    {
        q: 'How quickly can I expect a response to my message?',
        a: 'We review every message carefully and typically respond within a few business hours on weekdays. For urgent inquiries, reach us instantly via WhatsApp or phone call.',
    },
    {
        q: 'How long does a solar installation take?',
        a: 'Most residential systems are installed and fully commissioned within 1–3 days, depending on the complexity of the site and system size. We share a clear timeline after the site survey.',
    },
    {
        q: 'Do you offer warranties on products?',
        a: 'Yes. All panels, inverters and batteries we supply come with manufacturer-backed warranties, and our installations carry our workmanship warranty. Full terms are shared with every quote.',
    },
    {
        q: 'Can you train my team on solar installation?',
        a: 'Absolutely. We run hands-on training programmes for individuals and organisations — covering system design, sizing, installation safety, and fault-finding.',
    },
];

const openFaq = ref(0);

function observeReveal() {
    const els = document.querySelectorAll('.reveal');
    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal-visible');
                    io.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1 }
    );
    els.forEach((el) => io.observe(el));
}

function onKeydown(e) {
    if (e.key === 'Escape') isContactModalOpen.value = false;
}

onMounted(() => {
    observeReveal();
    document.addEventListener('keydown', onKeydown);
});
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <Head title="Contact Us — Envoy Electricals" />

    <!-- ===================== HERO ===================== -->
    <section class="relative overflow-hidden">
        <div class="absolute -top-20 -right-16 w-96 h-96 rounded-full bg-yellow-400/[0.10] blur-[120px] pointer-events-none"></div>
        <div class="absolute -top-24 -left-16 w-80 h-80 rounded-full bg-[#40e0d0]/[0.08] blur-[120px] pointer-events-none"></div>
        <div class="absolute inset-0" style="background-image:radial-gradient(rgba(13,21,39,0.05) 1px, transparent 1px); background-size:26px 26px;" aria-hidden="true"></div>

        <div class="relative max-w-3xl mx-auto text-center pt-20 sm:pt-28 pb-16 px-6">
            <span class="inline-flex items-center gap-2.5 mb-6">
                <span class="w-10 h-[2px] bg-[#40e0d0]"></span>
                <span class="text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em]">Contact Us</span>
                <span class="w-10 h-[2px] bg-[#40e0d0]"></span>
            </span>
            <h1 class="text-4xl sm:text-5xl lg:text-[56px] font-extrabold tracking-tight text-slate-950 leading-[1.08]">
                Let's Talk About Your<br class="hidden sm:block" />
                <span class="bg-gradient-to-r from-yellow-500 via-amber-400 to-amber-500 bg-clip-text text-transparent">Next Power Project</span>
            </h1>
            <p class="mt-6 text-slate-600 text-base sm:text-lg leading-relaxed max-w-xl mx-auto">
                Whether you need a solar system, a full installation, training, or a partnership formed — we'd love to hear from you. Reach out and we'll respond promptly.
            </p>
        </div>
    </section>

    <!-- ===================== INFO CARDS + FORM ===================== -->
    <section class="relative px-6 sm:px-12 lg:px-16 pb-20 sm:pb-24">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            <!-- Info cards -->
            <div class="lg:col-span-5 space-y-6">
                <a
                    v-for="(card, i) in infoCards"
                    :key="card.title"
                    :href="card.href || undefined"
                    :target="card.href && card.href.startsWith('http') ? '_blank' : undefined"
                    :rel="card.href && card.href.startsWith('http') ? 'noopener' : undefined"
                    class="reveal group bg-white rounded-2xl rounded-tr-none p-7 flex items-start gap-5 shadow-[0_2px_14px_rgba(0,0,0,0.06)] hover:shadow-[0_10px_30px_rgba(0,0,0,0.10)] hover:-translate-y-1 transition-all duration-300"
                    :style="{ transitionDelay: `${i * 60}ms` }"
                >
                    <span class="w-14 h-14 rounded-2xl rounded-tr-none bg-yellow-400/10 text-yellow-500 flex items-center justify-center shrink-0 group-hover:bg-yellow-400 group-hover:text-slate-950 transition-colors duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path :d="card.icon" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <h3 class="font-extrabold text-slate-950">{{ card.title }}</h3>
                        <p v-if="card.lines.length" v-for="line in card.lines" :key="line" class="text-sm text-slate-600 leading-relaxed mt-1">{{ line }}</p>
                        <p v-if="card.linkText" class="text-sm mt-1 text-slate-600 group-hover:underline underline-offset-4">{{ card.linkText }}</p>
                    </div>
                </a>

                <div class="reveal relative bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] rounded-2xl rounded-tr-none p-7 overflow-hidden shadow-xl" :style="{ transitionDelay: '180ms' }">
                    <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full bg-yellow-400/[0.10] blur-3xl pointer-events-none"></div>
                    <div class="flex items-center gap-4 relative">
                        <span class="w-12 h-12 rounded-2xl bg-white/10 text-[#40e0d0] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 5a2 2 0 0 1 2-2h2l1.5 4-2 1.2a10 10 0 0 0 5 5l1.2-2 4 1.5V17a2 2 0 0 1-2 2A16 16 0 0 1 3 5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <h3 class="text-white font-extrabold">Business Hours</h3>
                            <p class="text-slate-400 text-sm mt-0.5">Mon – Sat: 8:00am – 6:00pm</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isContactModalOpen = true"
                        class="relative mt-5 inline-flex items-center gap-2 text-sm font-bold text-yellow-400 hover:text-yellow-300 transition-colors group"
                    >
                        Request a callback
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>

            <!-- Form -->
            <div class="lg:col-span-7 reveal">
                <div class="bg-white rounded-2xl rounded-tr-none p-8 sm:p-12 shadow-[0_2px_14px_rgba(0,0,0,0.06)]">
                    <h3 class="text-2xl font-extrabold text-slate-950">Send Us A Message</h3>
                    <p class="text-slate-500 text-sm mt-1 mb-9">Fill in the form below and we'll get back to you within 24 hours.</p>

                    <form v-if="!submitted" @submit.prevent="submitForm" class="space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-[13px] font-bold text-slate-900 mb-2">Full Name *</label>
                                <input
                                    v-model="form.customer_name"
                                    type="text"
                                    required
                                    placeholder="Enter full name"
                                    class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-[13px] font-bold text-slate-900 mb-2">Email Address *</label>
                                <input
                                    v-model="form.customer_email"
                                    type="email"
                                    required
                                    placeholder="Enter email"
                                    class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[13px] font-bold text-slate-900 mb-2">Phone Number</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                placeholder="Enter phone number"
                                class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-[13px] font-bold text-slate-900 mb-2">Message *</label>
                            <textarea
                                v-model="form.comment"
                                rows="6"
                                required
                                placeholder="Tell us about your project, power needs, or questions..."
                                class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl rounded-tr-none px-4 py-3 text-[15px] placeholder:text-slate-400 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all resize-y"
                            ></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center gap-2.5 bg-gradient-to-r from-yellow-400 to-amber-400 hover:brightness-105 text-slate-950 font-bold text-[15px] px-8 py-3.5 rounded-xl rounded-tr-none shadow-[4px_4px_0_#0D1527] hover:translate-x-[1px] hover:translate-y-[1px] hover:shadow-[2px_2px_0_#0D1527] disabled:opacity-60 transition-all duration-150"
                            >
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 14-5m0 0h-4m4 0V3M20 12a8 8 0 0 1-14 5m0 0v4m0-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <template v-else>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Send Message
                                </template>
                            </button>
                            <p class="text-xs text-slate-400">We reply within 24 hours, guaranteed.</p>
                        </div>
                    </form>

                    <div v-else class="py-16 text-center">
                        <span class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-50 text-emerald-500 mb-6">
                            <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m8.5 12.5 2.5 2.5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <h3 class="text-2xl font-black text-slate-950">Message Sent!</h3>
                        <p class="mt-3 text-slate-500 text-sm max-w-sm mx-auto">
                            Thank you {{ form.customer_name.split(' ')[0] }} — your message is in. Our team will reply within 24 hours.
                        </p>
                        <button
                            type="button"
                            @click="resetForm"
                            class="mt-8 inline-flex items-center gap-2 text-slate-900 font-bold text-sm bg-yellow-400 hover:bg-yellow-300 px-7 py-3 rounded-xl transition-colors"
                        >
                            Send Another Message
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== SUPPORT CHANNELS ===================== -->
    <section class="relative px-6 sm:px-12 lg:px-16 pb-20 sm:pb-24">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12 reveal">
                <span class="inline-flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-[2px] bg-[#40e0d0]"></span>
                    <span class="text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em]">Ways We Can Help</span>
                    <span class="w-8 h-[2px] bg-[#40e0d0]"></span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-950">Choose Your Channel</h2>
                <p class="mt-3 text-slate-500 text-sm sm:text-base">Fast, dedicated assistance — however you prefer to reach us.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(card, i) in supportCards"
                    :key="card.title"
                    class="reveal group relative bg-white rounded-2xl rounded-tr-none p-8 shadow-[0_2px_14px_rgba(0,0,0,0.06)] hover:shadow-[0_10px_30px_rgba(0,0,0,0.10)] hover:-translate-y-1 transition-all duration-300 flex flex-col"
                    :style="{ transitionDelay: `${i * 60}ms` }"
                >
                    <span class="w-14 h-14 rounded-2xl rounded-tr-none bg-yellow-400/10 text-yellow-500 flex items-center justify-center mb-5 group-hover:bg-yellow-400 group-hover:text-slate-950 transition-colors duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path :d="card.icon" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <h3 class="text-lg font-extrabold text-slate-950">{{ card.title }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed mt-2 flex-1 whitespace-pre-line">{{ card.body }}</p>

                    <span v-if="card.badge" class="inline-flex items-center gap-2 self-start mt-4 text-xs font-bold text-[#1A365D] bg-[#eef0fb] rounded-full px-4 py-2">
                        <span class="w-2 h-2 rounded-full bg-[#20b858]"></span>
                        {{ card.badgeText }}
                    </span>
                    <a
                        v-if="card.link"
                        :href="card.link"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 self-start mt-4 text-sm font-bold text-yellow-500 hover:text-amber-600 transition-all duration-200 group/link"
                    >
                        {{ card.linkText }}
                        <svg class="w-4 h-4 transition-transform duration-200 group-hover/link:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== LOCATION / MAP ===================== -->
    <section class="relative px-6 sm:px-12 lg:px-16 pb-20 sm:pb-24">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12 reveal">
                <span class="inline-flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-[2px] bg-[#40e0d0]"></span>
                    <span class="text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em]">Visit Us</span>
                    <span class="w-8 h-[2px] bg-[#40e0d0]"></span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-950">Find Our Showroom</h2>
                <p class="mt-3 text-slate-500 text-sm sm:text-base">Located on Peace Avenue, Akure. Walk-ins are welcome during business hours.</p>
            </div>

            <div class="reveal grid grid-cols-1 lg:grid-cols-12 bg-white rounded-3xl overflow-hidden shadow-[0_2px_14px_rgba(0,0,0,0.06)]">
                <div class="lg:col-span-7 min-h-[320px] bg-[#0D1527]">
                    <iframe
                        src="https://maps.google.com/maps?q=Peace%20Avenue%2C%20Akure%2C%20Nigeria&z=14&output=embed"
                        class="w-full h-full min-h-[320px]"
                        style="border: 0; filter: grayscale(20%);"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Envoy Electricals location"
                    ></iframe>
                </div>
                <div class="lg:col-span-5 bg-gradient-to-br from-[#0D1527] via-[#12203C] to-[#1A365D] p-8 sm:p-10 flex flex-col justify-center relative overflow-hidden">
                    <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-yellow-400/[0.10] blur-3xl pointer-events-none"></div>
                    <h3 class="relative text-2xl font-extrabold text-white">Envoy Electricals</h3>
                    <p class="relative text-sm text-slate-400 leading-relaxed mt-3 mb-7">
                        Shop 1, Peace Avenue Junction, opp Goddy Royal Hotel, Futa Southgate Road, Akure.
                    </p>
                    <div class="relative space-y-4">
                        <div class="flex items-center gap-3 text-sm text-slate-200">
                            <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 21h18M4 17h16M6 17V9a6 6 0 0 1 12 0v8M9 17v-5h6v5M9 9h.01M15 9h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Walk-in solar showroom &amp; live demos
                        </div>
                        <div class="flex items-center gap-3 text-sm text-slate-200">
                            <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 17h-3a3 3 0 0 1-3-3v-1a1 1 0 0 1 .2-.6L19 5l5.8 7.4a1 1 0 0 1 .2.6v1a3 3 0 0 1-3 3M8 17H5a3 3 0 0 1-3-3v-1a1 1 0 0 1 .2-.6L8 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Free client parking available
                        </div>
                        <div class="flex items-center gap-3 text-sm text-slate-200">
                            <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 9s4-5 11-5 11 5 11 5-4 5-11 5S1 9 1 9zM17 17 15 9M21 13l-4-8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            High-speed guest Wi-Fi on site
                        </div>
                    </div>
                    <a
                        href="https://maps.google.com/?q=Peace+Avenue+Akure+Nigeria"
                        target="_blank"
                        rel="noopener"
                        class="relative mt-8 inline-flex items-center justify-center gap-2 bg-gradient-to-r from-yellow-400 to-amber-400 hover:brightness-105 text-slate-950 font-bold text-sm px-6 py-3 rounded-xl transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11zM10 10a2 2 0 1 1 4 0 2 2 0 0 1-4 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Get Directions
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== FAQ ===================== -->
    <section class="relative px-6 sm:px-12 lg:px-16 pb-24 sm:pb-32">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center gap-2.5 mb-4">
                    <span class="w-8 h-[2px] bg-[#40e0d0]"></span>
                    <span class="text-[#40e0d0] text-xs font-bold uppercase tracking-[0.3em]">Got Questions?</span>
                    <span class="w-8 h-[2px] bg-[#40e0d0]"></span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-950">Frequently Asked Questions</h2>
                <p class="mt-3 text-slate-500 text-sm sm:text-base">Quick answers to the questions we hear most often.</p>
            </div>

            <div class="space-y-5">
                <div
                    v-for="(f, i) in faqs"
                    :key="f.q"
                    class="reveal bg-white rounded-2xl rounded-tr-none shadow-[0_2px_14px_rgba(0,0,0,0.06)] overflow-hidden hover:shadow-[0_8px_24px_rgba(0,0,0,0.09)] transition-shadow"
                    :style="{ transitionDelay: `${i * 60}ms` }"
                >
                    <button
                        type="button"
                        @click="openFaq = openFaq === i ? -1 : i"
                        class="w-full flex items-center justify-between gap-4 px-6 sm:px-7 py-5 text-left"
                    >
                        <span class="font-bold text-slate-900 text-sm sm:text-base">{{ f.q }}</span>
                        <span class="shrink-0 w-8 h-8 rounded-lg rounded-tr-none bg-yellow-400/10 text-yellow-500 flex items-center justify-center transition-all duration-300" :class="{ 'rotate-180': openFaq === i }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                    <div v-if="openFaq === i" class="px-6 sm:px-7 pb-6">
                        <p class="text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4">{{ f.a }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== WHATSAPP FLOAT ===================== -->
    <a
        href="https://wa.me/2348097089259"
        target="_blank"
        rel="noopener"
        title="Chat on WhatsApp"
        class="fixed bottom-6 right-6 z-50 w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#25D366] text-white flex items-center justify-center shadow-[0_6px_18px_rgba(37,211,102,0.4)] hover:bg-[#1fb858] hover:-translate-y-1 active:translate-y-0 transition-all duration-200"
    >
        <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm4.5 13c-.2.2-1.4.7-1.7.8s-.4.1-.7 0-.9-.3-1.4-.7a5.6 5.6 0 0 1-1.9-2.5c-.1-.2.2-.4.4-.6l.4-.6a.8.8 0 0 0 .1-.2.5.5 0 0 0 0-.4c-.1-.2-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a.8.8 0 0 0-.6.3 2.6 2.6 0 0 0-.8 1.9 4.5 4.5 0 0 0 .9 2.4 10 10 0 0 0 3.9 3.4c1.2.6 1.7.6 2.1.6a2.6 2.6 0 0 0 1.7-1.2 2 2 0 0 0 .1-1.2z"/></svg>
        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#E4312B] border-2 border-white"></span>
    </a>

    <!-- ===================== CALLBACK MODAL ===================== -->
    <div
        v-if="isContactModalOpen"
        class="fixed inset-0 z-[60] bg-[#0D1527]/80 backdrop-blur-md flex items-center justify-center p-4"
        @click.self="isContactModalOpen = false"
    >
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-br from-[#0D1527] to-[#12203C] px-7 py-6 flex items-center justify-between relative overflow-hidden">
                <div class="absolute -top-10 right-10 w-32 h-32 rounded-full bg-yellow-400/[0.10] blur-3xl pointer-events-none"></div>
                <div>
                    <h3 class="text-white font-extrabold text-xl">Request A Callback</h3>
                    <p class="text-slate-400 text-xs mt-0.5">Leave your number and we'll call you back.</p>
                </div>
                <button type="button" @click="isContactModalOpen = false" class="text-white/70 hover:text-white text-2xl font-bold p-1">✕</button>
            </div>

            <form v-if="!callbackSubmitted" @submit.prevent="submitCallback" class="px-7 py-7 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input
                        v-model="callbackForm.name"
                        type="text"
                        required
                        placeholder="Your name"
                        class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                    />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number *</label>
                    <input
                        v-model="callbackForm.phone"
                        type="tel"
                        required
                        placeholder="+234 ..."
                        class="w-full border-[1.5px] border-[#d9d7d0] bg-[#fbfbf9] rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-400/15 focus:bg-white transition-all"
                    />
                </div>
                <button
                    type="submit"
                    :disabled="callbackForm.processing"
                    class="w-full inline-flex items-center justify-center gap-2.5 bg-gradient-to-r from-yellow-400 to-amber-400 hover:brightness-105 disabled:opacity-60 text-slate-950 font-bold px-7 py-3.5 rounded-xl transition-all"
                >
                    <svg v-if="callbackForm.processing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 14-5m0 0h-4m4 0V3M20 12a8 8 0 0 1-14 5m0 0v4m0-4h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>{{ callbackForm.processing ? 'Sending...' : 'Request Callback' }}</span>
                </button>
            </form>

            <div v-else class="px-7 py-14 text-center">
                <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 text-emerald-500 mb-5">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m8.5 12.5 2.5 2.5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <h3 class="text-xl font-black text-slate-950">All Set!</h3>
                <p class="mt-2 text-sm text-slate-500">We'll call you back shortly — keep your phone close.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.reveal {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.7s ease, transform 0.7s ease;
}
.reveal-visible {
    opacity: 1;
    transform: none;
}
</style>