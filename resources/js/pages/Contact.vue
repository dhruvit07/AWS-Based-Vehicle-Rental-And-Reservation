<script setup lang="ts">
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { guestContact } from '@/routes/contact';
import { ref, onBeforeUnmount } from 'vue';
import { Mail, Phone, MapPin, Clock, Send, CheckCircle2, AlertCircle } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
});

const showNotification = ref(false);
const notificationMessage = ref('');
const notificationType = ref<'success' | 'error'>('success');

let notificationTimer: ReturnType<typeof setTimeout> | undefined;

const notify = (message: string, type: 'success' | 'error') => {
    notificationMessage.value = message;
    notificationType.value = type;
    showNotification.value = true;

    if (notificationTimer) clearTimeout(notificationTimer);

    notificationTimer = setTimeout(() => {
        showNotification.value = false;
    }, 4000);
};

onBeforeUnmount(() => {
    if (notificationTimer) clearTimeout(notificationTimer);
});

const sendTicket = () => {
    form.post(guestContact().url, {
        onSuccess() {
            form.reset();
            notify('Your message was received. Our concierge will follow up promptly.', 'success');
        },
        onError() {
            notify('Unable to send message. Please review the highlighted fields.', 'error');
        },
    });
};
</script>

<template>
    <Head title="Contact Concierge | Real Rent Car" />

    <HomeLayout>
        <div class="min-h-screen bg-[#FBFBFD] text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100">

            <!-- Toast Notification -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-[-12px] opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showNotification"
                    role="status"
                    aria-live="polite"
                    :class="[
                        'fixed right-5 top-24 z-50 flex items-center gap-2.5 rounded-xl px-5 py-3.5 text-xs font-semibold shadow-xl backdrop-blur-xl',
                        notificationType === 'success'
                            ? 'bg-emerald-600 text-white'
                            : 'bg-rose-600 text-white',
                    ]"
                >
                    <CheckCircle2 v-if="notificationType === 'success'" class="h-4 w-4" />
                    <AlertCircle v-else class="h-4 w-4" />
                    <span>{{ notificationMessage }}</span>
                </div>
            </Transition>

            <!-- Hero Staging -->
            <section class="relative isolate overflow-hidden bg-[#101D35] px-5 py-16 text-white dark:bg-[#070A0F] sm:py-24">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(120,160,210,0.25),rgba(255,255,255,0))]"></div>
                <div class="pointer-events-none absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full bg-[#58779D]/20 blur-3xl"></div>

                <div class="relative mx-auto max-w-4xl text-center">
                    <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                        Direct concierge assistance.
                    </h1>

                    <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg">
                        Have inquiries regarding bespoke rentals, extended itineraries, or corporate billing? Our specialists respond within business hours.
                    </p>
                </div>
            </section>

            <!-- Main Console Section -->
            <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16 lg:px-8">
                <div class="grid items-start gap-8 lg:grid-cols-5 lg:gap-10">

                    <!-- Contact Form Card -->
                    <div class="lg:col-span-3">
                        <div class="glass-card overflow-hidden p-6 sm:p-8">
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                                    Send an Inquiry
                                </h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Provide your journey details and contact preference.
                                </p>
                            </div>

                            <form class="mt-6 space-y-5" @submit.prevent="sendTicket">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <!-- Name -->
                                    <div>
                                        <label for="contact-name" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Full Name <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            id="contact-name"
                                            v-model="form.name"
                                            type="text"
                                            name="name"
                                            autocomplete="name"
                                            required
                                            placeholder="Sarah Jenkins"
                                            class="apple-input w-full px-3.5 py-2.5 text-xs"
                                            :aria-invalid="!!form.errors.name"
                                        />
                                        <p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-500">
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="contact-email" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Email Address <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            id="contact-email"
                                            v-model="form.email"
                                            type="email"
                                            name="email"
                                            autocomplete="email"
                                            required
                                            placeholder="sarah@example.com"
                                            class="apple-input w-full px-3.5 py-2.5 text-xs"
                                            :aria-invalid="!!form.errors.email"
                                        />
                                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500">
                                            {{ form.errors.email }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Subject -->
                                <div>
                                    <label for="contact-subject" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        Subject <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="contact-subject"
                                        v-model="form.subject"
                                        type="text"
                                        name="subject"
                                        required
                                        placeholder="Airport pickup, luxury SUV inquiry, etc."
                                        class="apple-input w-full px-3.5 py-2.5 text-xs"
                                        :aria-invalid="!!form.errors.subject"
                                    />
                                    <p v-if="form.errors.subject" class="mt-1.5 text-xs text-rose-500">
                                        {{ form.errors.subject }}
                                    </p>
                                </div>

                                <!-- Message -->
                                <div>
                                    <label for="contact-message" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        Message <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        id="contact-message"
                                        v-model="form.message"
                                        name="message"
                                        rows="5"
                                        required
                                        placeholder="Share your planned rental dates, vehicle preferences, or custom requirements..."
                                        class="apple-input w-full px-3.5 py-2.5 text-xs leading-relaxed"
                                        :aria-invalid="!!form.errors.message"
                                    ></textarea>
                                    <p v-if="form.errors.message" class="mt-1.5 text-xs text-rose-500">
                                        {{ form.errors.message }}
                                    </p>
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="apple-btn-primary flex w-full items-center justify-center gap-2 py-3 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                    <Send v-else class="h-4 w-4" />
                                    <span>{{ form.processing ? 'Dispatching Message...' : 'Submit Message' }}</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Direct Channels Sidebar -->
                    <aside class="space-y-6 lg:col-span-2">
                        <div class="glass-card p-6 sm:p-7">
                            <h3 class="text-base font-bold text-slate-950 dark:text-white">
                                Direct Contact Channels
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Reach our desk directly for urgent itinerary changes.
                            </p>

                            <div class="mt-6 space-y-4">
                                <div class="flex items-start gap-3.5 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <Phone class="mt-0.5 h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                                    <div>
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white">Priority Concierge</p>
                                        <p class="text-xs text-slate-600 dark:text-slate-400">+91 (800) 555-REAL</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <Mail class="mt-0.5 h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                                    <div>
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white">Support Email</p>
                                        <p class="text-xs text-slate-600 dark:text-slate-400">concierge@realrentcar.com</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <Clock class="mt-0.5 h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                                    <div>
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white">Operating Window</p>
                                        <p class="text-xs text-slate-600 dark:text-slate-400">24 Hours / 7 Days a Week</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <MapPin class="mt-0.5 h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                                    <div>
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white">Terminal Hub</p>
                                        <p class="text-xs text-slate-600 dark:text-slate-400">Hub 4, Airport Gateway Complex</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

        </div>
    </HomeLayout>
</template>
