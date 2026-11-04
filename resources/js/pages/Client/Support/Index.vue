<script setup lang="ts">
import ClientLayout from '@/layouts/ClientLayout.vue';
import { create, show } from '@/routes/client/support';
import { Head, Link, router } from '@inertiajs/vue3';
import { LifeBuoy, Plus, MessageSquare, ArrowRight, Clock } from 'lucide-vue-next';

const props = defineProps<{
    tickets: {
        data: Array<{
            id: number;
            subject: string;
            message: string;
            status: string;
            user?: { id: number; name: string; email: string };
            guest_name?: string;
            guest_email?: string;
            created_at: string;
            updated_at: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

function goToTicket(id: number) {
    router.visit(show(id).url);
}

const statusBadge = (status: string) => {
    const s = status.toLowerCase();
    if (s === 'open') {
        return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20';
    }
    if (s === 'in_progress') {
        return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
    }
    return 'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-400 border border-slate-200/80 dark:border-white/10';
};
</script>

<template>
    <Head title="Concierge Support | Real Rent Car" />

    <ClientLayout>
        <main class="min-h-screen flex-1 space-y-8 bg-[#FBFBFD] p-5 text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100 sm:p-8">

            <!-- Hero Staging -->
            <section class="relative isolate overflow-hidden rounded-3xl bg-[#101D35] p-6 text-white shadow-xl dark:bg-[#0F1724] sm:p-8">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(112,148,191,0.25),transparent_60%)]"></div>

                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">
                            Concierge Support Desk
                        </h1>
                        <p class="mt-1 max-w-xl text-xs leading-relaxed text-slate-300 sm:text-sm">
                            Real-time messaging with dedicated specialists regarding trips, payments, and vehicle logistics.
                        </p>
                    </div>

                    <Link
                        :href="create().url"
                        class="apple-btn-primary inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold"
                    >
                        <Plus class="h-4 w-4" />
                        <span>New Inquiry</span>
                    </Link>
                </div>
            </section>

            <!-- Tickets Console -->
            <section class="glass-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200/80 p-5 dark:border-white/10 sm:px-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-950 dark:text-white">
                            Conversations
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Select any ticket to view message thread
                        </p>
                    </div>

                    <span class="rounded-full border border-slate-200/80 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                        {{ props.tickets.data.length }} active threads
                    </span>
                </div>

                <!-- Empty State -->
                <div
                    v-if="props.tickets.data.length === 0"
                    class="p-12 text-center"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400">
                        <MessageSquare class="h-6 w-6" />
                    </div>

                    <h3 class="mt-4 text-base font-bold text-slate-950 dark:text-white">
                        No active inquiries
                    </h3>

                    <p class="mx-auto mt-1 max-w-sm text-xs text-slate-600 dark:text-slate-400">
                        Need assistance with a reservation or custom itinerary? Open a ticket below.
                    </p>

                    <Link
                        :href="create().url"
                        class="apple-btn-primary mt-6 inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-semibold"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        <span>Start Conversation</span>
                    </Link>
                </div>

                <!-- Table View -->
                <div v-else class="divide-y divide-slate-200/80 dark:divide-white/10">
                    <div
                        v-for="ticket in props.tickets.data"
                        :key="ticket.id"
                        class="group flex cursor-pointer items-center justify-between p-5 transition hover:bg-slate-50/80 dark:hover:bg-white/5"
                        @click="goToTicket(ticket.id)"
                    >
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider" :class="statusBadge(ticket.status)">
                                    {{ ticket.status }}
                                </span>
                                <span class="font-mono text-xs text-slate-400">#{{ ticket.id }}</span>
                                <span class="text-xs text-slate-400">• {{ formatDate(ticket.created_at) }}</span>
                            </div>

                            <h3 class="truncate text-sm font-bold text-slate-950 transition-colors group-hover:text-[#58779D] dark:text-white dark:group-hover:text-[#7094BF]">
                                {{ ticket.subject }}
                            </h3>

                            <p class="line-clamp-1 text-xs text-slate-600 dark:text-slate-400">
                                {{ ticket.message }}
                            </p>
                        </div>

                        <div class="ml-4 flex items-center gap-2">
                            <span class="hidden text-xs font-semibold text-[#58779D] group-hover:underline dark:text-[#7094BF] sm:inline">
                                Open Thread
                            </span>
                            <ArrowRight class="h-4 w-4 text-slate-400 transition-transform group-hover:translate-x-1" />
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </ClientLayout>
</template>
