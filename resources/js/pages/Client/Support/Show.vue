<script setup lang="ts">
import ClientLayout from '@/layouts/ClientLayout.vue';
import { index, reply } from '@/routes/client/support';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { ArrowLeft, Send, ShieldCheck, User, LifeBuoy } from 'lucide-vue-next';

interface Message {
    id: number;
    message: string;
    is_admin: boolean;
    created_at: string;
}

interface Ticket {
    id: number;
    subject: string;
    status: string;
    created_at: string;
    messages: Message[];
    user?: {
        name: string;
    };
}

const props = defineProps<{
    ticket: Ticket;
}>();

const form = useForm<{
    message: string;
}>({
    message: '',
});

const canSend = computed(
    () => form.message.trim().length > 0 && !form.processing,
);

const messagesEndRef = ref<HTMLElement | null>(null);
const scrollToBottom = async () => {
    await nextTick();
    messagesEndRef.value?.scrollIntoView({ behavior: 'smooth', block: 'end' });
};

const submitReply = () => {
    if (!canSend.value) return;

    form.post(reply(props.ticket.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message');
            router.reload({ only: ['ticket'] });
            scrollToBottom();
        },
    });
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

onMounted(() => {
    scrollToBottom();
});

watch(
    () => props.ticket.messages?.length,
    () => scrollToBottom(),
);

const statusClass = computed(() => {
    const s = String(props.ticket.status).toLowerCase();
    if (s === 'open') {
        return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20';
    }
    if (s === 'in_progress') {
        return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
    }
    return 'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-400 border border-slate-200/80 dark:border-white/10';
});
</script>

<template>
    <Head :title="`Inquiry #${ticket.id}: ${ticket.subject} | Real Rent Car`" />

    <ClientLayout>
        <main class="min-h-screen flex-1 space-y-6 bg-[#FBFBFD] p-5 text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100 sm:p-8">

            <div class="mx-auto max-w-4xl space-y-6">
                <!-- Navigation & Title Header -->
                <div class="glass-card p-5 sm:p-6">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider" :class="statusClass">
                                    {{ ticket.status }}
                                </span>
                                <span class="font-mono text-xs text-slate-400">#{{ ticket.id }}</span>
                                <span class="text-xs text-slate-400">• {{ formatDate(ticket.created_at) }}</span>
                            </div>

                            <h1 class="mt-2 text-xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                                {{ ticket.subject }}
                            </h1>
                        </div>

                        <Link
                            :href="index().url"
                            class="inline-flex items-center gap-1.5 self-start rounded-xl border border-slate-200/80 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 sm:self-auto"
                        >
                            <ArrowLeft class="h-3.5 w-3.5" />
                            <span>All Threads</span>
                        </Link>
                    </div>
                </div>

                <!-- Apple Messages-Inspired Chat Window -->
                <div class="glass-card flex flex-col overflow-hidden">
                    <!-- Message Stream -->
                    <div class="max-h-[550px] min-h-[350px] space-y-4 overflow-y-auto p-5 sm:p-6">
                        <div
                            v-if="!ticket.messages || ticket.messages.length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-500 dark:border-white/10 dark:text-slate-400"
                        >
                            Conversation started. Our mobility concierge will join shortly.
                        </div>

                        <div
                            v-for="message in ticket.messages"
                            :key="message.id"
                            class="flex"
                            :class="message.is_admin ? 'justify-start' : 'justify-end'"
                        >
                            <div class="max-w-xl space-y-1">
                                <!-- Sender Label -->
                                <div
                                    class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400"
                                    :class="message.is_admin ? 'justify-start' : 'justify-end'"
                                >
                                    <span v-if="message.is_admin" class="flex items-center gap-1 text-[#58779D] dark:text-[#7094BF]">
                                        <ShieldCheck class="h-3 w-3" />
                                        <span>Concierge Desk</span>
                                    </span>
                                    <span v-else>You</span>
                                    <span>•</span>
                                    <span class="text-[10px]">{{ formatDate(message.created_at) }}</span>
                                </div>

                                <!-- Message Bubble -->
                                <div
                                    class="p-4 text-xs leading-relaxed shadow-xs"
                                    :class="[
                                        message.is_admin
                                            ? 'rounded-2xl rounded-tl-xs border border-slate-200/80 bg-slate-100/90 text-slate-900 dark:border-white/10 dark:bg-white/10 dark:text-slate-100'
                                            : 'rounded-2xl rounded-tr-xs bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-medium',
                                    ]"
                                >
                                    <p class="whitespace-pre-line">{{ message.message }}</p>
                                </div>
                            </div>
                        </div>

                        <div ref="messagesEndRef"></div>
                    </div>

                    <!-- Apple Composer Form -->
                    <div
                        v-if="ticket.status !== 'closed'"
                        class="border-t border-slate-200/80 bg-slate-50/50 p-4 dark:border-white/10 dark:bg-white/5"
                    >
                        <form @submit.prevent="submitReply" class="space-y-3">
                            <textarea
                                id="reply-message"
                                v-model="form.message"
                                rows="3"
                                class="apple-input w-full px-3.5 py-2.5 text-xs leading-relaxed"
                                placeholder="Type your reply to the concierge... (Ctrl + Enter to send)"
                                required
                                @keydown.ctrl.enter.prevent="submitReply"
                                @keydown.meta.enter.prevent="submitReply"
                            ></textarea>

                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-slate-400">
                                    Press <kbd class="rounded border border-slate-300 bg-white px-1 dark:border-white/20 dark:bg-white/10">⌘ / Ctrl + Enter</kbd> to send
                                </span>

                                <button
                                    type="submit"
                                    :disabled="!canSend"
                                    class="apple-btn-primary inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span v-if="form.processing" class="h-3 w-3 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                    <Send v-else class="h-3.5 w-3.5" />
                                    <span>Reply</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div
                        v-else
                        class="border-t border-slate-200/80 bg-slate-100/50 p-4 text-center text-xs text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400"
                    >
                        This conversation has been closed. You can open a new inquiry if further assistance is needed.
                    </div>
                </div>
            </div>
        </main>
    </ClientLayout>
</template>
