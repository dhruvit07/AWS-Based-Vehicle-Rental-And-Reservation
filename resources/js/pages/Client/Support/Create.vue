<script setup lang="ts">
import ClientLayout from '@/layouts/ClientLayout.vue';
import { index, store } from '@/routes/client/support';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft, Send, LifeBuoy } from 'lucide-vue-next';

const form = useForm<{
    subject: string;
    message: string;
}>({
    subject: '',
    message: '',
});

const canSubmit = computed(
    () =>
        form.subject.trim().length > 0 &&
        form.message.trim().length > 0 &&
        !form.processing,
);

const submitTicket = () => {
    if (!canSubmit.value) return;

    form.post(store().url, {
        onError: (errors) => {
            console.error('Failed to create ticket:', errors);
        },
    });
};
</script>

<template>
    <Head title="New Support Inquiry | Real Rent Car" />

    <ClientLayout>
        <main class="min-h-screen flex-1 space-y-8 bg-[#FBFBFD] p-5 text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100 sm:p-8">

            <!-- Header -->
            <div class="mx-auto max-w-3xl">
                <Link
                    :href="index().url"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 transition hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    <span>Back to Support Threads</span>
                </Link>

                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                    New Concierge Inquiry
                </h1>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
                    Our team reviews messages 24/7. Provide details below to expedite resolution.
                </p>

                <!-- Form Card -->
                <div class="glass-card mt-6 p-6 sm:p-8">
                    <form @submit.prevent="submitTicket" class="space-y-5">
                        <!-- Subject Field -->
                        <div>
                            <div class="flex items-center justify-between">
                                <label
                                    for="subject"
                                    class="block text-xs font-semibold text-slate-700 dark:text-slate-300"
                                >
                                    Subject <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] text-slate-400">
                                    {{ form.subject.length }}/255
                                </span>
                            </div>

                            <input
                                id="subject"
                                v-model="form.subject"
                                type="text"
                                class="apple-input mt-1.5 w-full px-3.5 py-2.5 text-xs"
                                placeholder="Summary of question, trip change, or request"
                                required
                                maxlength="255"
                            />
                            <p
                                v-if="form.errors.subject"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ form.errors.subject }}
                            </p>
                        </div>

                        <!-- Message Field -->
                        <div>
                            <label
                                for="message"
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300"
                            >
                                Details & Context <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="6"
                                class="apple-input mt-1.5 w-full px-3.5 py-2.5 text-xs leading-relaxed"
                                placeholder="Explain your request in detail, including reservation number if relevant..."
                                required
                            ></textarea>
                            <p
                                v-if="form.errors.message"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ form.errors.message }}
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-200/80 pt-4 dark:border-white/10">
                            <Link
                                :href="index().url"
                                class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="!canSubmit"
                                class="apple-btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span v-if="form.processing" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                <Send v-else class="h-3.5 w-3.5" />
                                <span>{{ form.processing ? 'Dispatching...' : 'Submit Inquiry' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </ClientLayout>
</template>
