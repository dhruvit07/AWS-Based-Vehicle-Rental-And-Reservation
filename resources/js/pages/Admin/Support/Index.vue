<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    LifeBuoy,
    MessageSquare,
    Search,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { index, show } from '@/routes/admin/support';

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
    filters: {
        search?: string;
        status?: string;
        type?: 'customer' | 'guest';
    };
    statuses: Record<string, { label: string; color: string }>;
    statusCounts: {
        customer: Record<string, number>;
        guest: Record<string, number>;
    };
}>();

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const ticketType = ref<'customer' | 'guest'>(
    props.filters?.type || 'customer',
);

const statusColors = computed(() => {
    const colors: Record<string, { bg: string; text: string; dot: string }> = {};

    for (const [status, data] of Object.entries(props.statuses || {})) {
        const hex = data.color?.replace('#', '') || '6B7280';
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);

        colors[status] = {
            bg: `rgba(${r}, ${g}, ${b}, 0.12)`,
            text: data.color || '#6B7280',
            dot: data.color || '#6B7280',
        };
    }

    return colors;
});

const getStatusColor = (status: string) =>
    statusColors.value[status] || {
        bg: 'rgba(107, 114, 128, 0.1)',
        text: '#6B7280',
        dot: '#6B7280',
    };

const getStatusCount = (
    type: 'customer' | 'guest',
    status: string,
): number => props.statusCounts?.[type]?.[status] || 0;

const getTotalCount = (type: 'customer' | 'guest'): number =>
    props.statusCounts?.[type]?.all || 0;

const doSearch = () => {
    router.get(
        index().url,
        {
            search: search.value || null,
            status: statusFilter.value === 'all' ? null : statusFilter.value,
            type: ticketType.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(search, (newVal, oldVal) => {
    if (newVal === '' && oldVal !== '') doSearch();
});

watch(ticketType, () => {
    doSearch();
});

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

const goToTicket = (id: number) => {
    if (ticketType.value === 'customer') {
        router.visit(show(id).url);
    }
};
</script>

<template>
    <Head title="Support Tickets" />

    <AdminLayout>
        <main class="min-h-full space-y-7 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <section class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Support Concierge
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Manage member inquiries, assistance dispatches, and guest messages.
                    </p>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                        <LifeBuoy class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Inquiries</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white">
                            {{ getTotalCount(ticketType) }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Ticket type selector -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <label class="cursor-pointer">
                    <input
                        v-model="ticketType"
                        type="radio"
                        value="customer"
                        class="peer sr-only"
                    />
                    <div
                        class="glass-card flex items-center justify-between p-5 transition hover:-translate-y-0.5"
                        :class="ticketType === 'customer'
                            ? 'ring-2 ring-[#58779D] dark:ring-[#7094BF]'
                            : ''"
                    >
                        <div class="flex items-center gap-3.5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300">
                                <UserCheck class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white">Customer Inquiries</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Registered member support threads</p>
                            </div>
                        </div>
                        <span class="rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1 text-xs font-bold text-slate-900 dark:text-white">
                            {{ getTotalCount('customer') }}
                        </span>
                    </div>
                </label>

                <label class="cursor-pointer">
                    <input
                        v-model="ticketType"
                        type="radio"
                        value="guest"
                        class="peer sr-only"
                    />
                    <div
                        class="glass-card flex items-center justify-between p-5 transition hover:-translate-y-0.5"
                        :class="ticketType === 'guest'
                            ? 'ring-2 ring-[#58779D] dark:ring-[#7094BF]'
                            : ''"
                    >
                        <div class="flex items-center gap-3.5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300">
                                <Users class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white">Guest Inquiries</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Public storefront contact submissions</p>
                            </div>
                        </div>
                        <span class="rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1 text-xs font-bold text-slate-900 dark:text-white">
                            {{ getTotalCount('guest') }}
                        </span>
                    </div>
                </label>
            </section>

            <!-- Search and filters -->
            <section class="glass-card p-5 sm:p-6">
                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="doSearch">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            :placeholder="`Search ${ticketType} inquiries by name, email, or subject...`"
                            class="apple-input w-full pl-10"
                        />
                    </div>

                    <button
                        type="submit"
                        class="apple-btn-primary h-11 px-6 text-sm font-medium cursor-pointer"
                    >
                        Search
                    </button>
                </form>

                <div v-if="ticketType === 'customer'" class="mt-5 border-t border-black/5 dark:border-white/10 pt-4">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Filter by Status
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-xl border px-3.5 py-1.5 text-xs font-medium transition cursor-pointer"
                            :class="statusFilter === 'all'
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-white/10'"
                            @click="statusFilter = 'all'; doSearch()"
                        >
                            All ({{ getTotalCount(ticketType) }})
                        </button>

                        <button
                            v-for="(status, key) in statuses"
                            :key="key"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-1.5 text-xs font-medium transition cursor-pointer"
                            :class="statusFilter === key
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-white/10'"
                            @click="statusFilter = key; doSearch()"
                        >
                            <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: status.color }"></span>
                            {{ status.label }} ({{ getStatusCount(ticketType, key) }})
                        </button>
                    </div>
                </div>
            </section>

            <!-- Tickets table -->
            <section class="glass-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-black/5 dark:border-white/10 px-6 py-4">
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Tickets Queue</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ ticketType === 'customer' ? 'Active registered member conversations' : 'Guest contact submissions' }}
                        </p>
                    </div>
                    <span class="rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400">
                        {{ props.tickets.data.length }} shown
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[760px] w-full text-left">
                        <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-6 py-3.5">Ticket</th>
                                <th class="px-6 py-3.5">{{ ticketType === 'customer' ? 'Customer' : 'Guest' }}</th>
                                <th class="px-6 py-3.5">Subject & Message</th>
                                <th v-if="ticketType === 'customer'" class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Created</th>
                                <th class="px-6 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/5 dark:divide-white/5 text-sm">
                            <tr
                                v-for="ticket in props.tickets.data"
                                :key="ticket.id"
                                class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]"
                                :class="ticketType === 'customer' ? 'cursor-pointer' : ''"
                                @click="goToTicket(ticket.id)"
                            >
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white text-xs">
                                    #{{ ticket.id }}
                                </td>

                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ ticketType === 'customer' ? ticket.user?.name : ticket.guest_name }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                        {{ ticketType === 'customer' ? ticket.user?.email : ticket.guest_email }}
                                    </p>
                                </td>

                                <td class="max-w-xs px-6 py-4">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ ticket.subject }}</p>
                                    <p class="mt-0.5 line-clamp-1 text-xs text-slate-500 dark:text-slate-400">{{ ticket.message }}</p>
                                </td>

                                <td v-if="ticketType === 'customer'" class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :style="{
                                            backgroundColor: getStatusColor(ticket.status).bg,
                                            color: getStatusColor(ticket.status).text,
                                        }"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: getStatusColor(ticket.status).dot }"></span>
                                        {{ statuses[ticket.status]?.label || ticket.status }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatDate(ticket.created_at) }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="ticketType === 'customer'"
                                        type="button"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-[#58779D] dark:text-[#7094BF] hover:underline cursor-pointer"
                                        @click.stop="goToTicket(ticket.id)"
                                    >
                                        Inspect
                                        <ArrowRight class="h-3.5 w-3.5" />
                                    </button>
                                    <span v-else class="text-xs text-slate-400">—</span>
                                </td>
                            </tr>

                            <tr v-if="props.tickets.data.length === 0">
                                <td :colspan="ticketType === 'customer' ? 6 : 5" class="px-6 py-16 text-center">
                                    <MessageSquare class="mx-auto h-10 w-10 text-slate-400" />
                                    <p class="mt-3 font-semibold text-slate-900 dark:text-white">No tickets found</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try adjusting your search query or status filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="props.tickets.links?.length > 3"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-black/5 dark:border-white/10 px-4 py-4"
                    aria-label="Pagination"
                >
                    <template v-for="(link, i) in props.tickets.links" :key="i">
                        <button
                            v-if="link.url"
                            type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition cursor-pointer"
                            :class="link.active
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10'"
                            @click="router.visit(link.url, { preserveScroll: true, preserveState: true })"
                            v-html="link.label"
                        ></button>
                        <span
                            v-else
                            class="pointer-events-none rounded-lg border border-black/5 dark:border-white/5 px-3 py-1.5 text-xs text-slate-400 opacity-40"
                            v-html="link.label"
                        ></span>
                    </template>
                </nav>
            </section>
        </main>
    </AdminLayout>
</template>
