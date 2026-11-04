<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Search,
    UserCheck,
    Users,
    UserX,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { index, show } from '@/routes/admin/clients';

const props = defineProps<{
    clients: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            is_active: boolean;
            reservations_count: number;
            payments_count: number;
            created_at?: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        status?: string;
    };
    statuses: Record<string, { label: string; count: number; color: string }>;
}>();

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');

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

const totalClients = computed(() =>
    Object.values(props.statuses || {}).reduce(
        (total, status) => total + status.count,
        0,
    ),
);

const activeClients = computed(
    () => props.clients.data.filter((client) => client.is_active).length,
);

const suspendedClients = computed(
    () => props.clients.data.filter((client) => !client.is_active).length,
);

const doSearch = () => {
    router.get(
        index().url,
        {
            search: search.value || null,
            status: statusFilter.value === 'all' ? null : statusFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(search, (newValue, oldValue) => {
    if (newValue === '' && oldValue !== '') doSearch();
});

const navigateToClient = (id: number) => {
    router.visit(show(id).url);
};
</script>

<template>
    <Head title="Clients" />

    <AdminLayout>
        <main class="min-h-full space-y-7 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <section class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Client Directory
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Manage customer accounts, driving credentials, and rental histories.
                    </p>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                        <Users class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Accounts</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ totalClients }}</p>
                    </div>
                </div>
            </section>

            <!-- Summary cards -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <article class="glass-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">On this page</p>
                            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ props.clients.data.length }}
                            </p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300">
                            <Users class="h-5 w-5" />
                        </span>
                    </div>
                </article>

                <article class="glass-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Active accounts</p>
                            <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ activeClients }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <UserCheck class="h-5 w-5" />
                        </span>
                    </div>
                </article>

                <article class="glass-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Suspended accounts</p>
                            <p class="mt-2 text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">{{ suspendedClients }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
                            <UserX class="h-5 w-5" />
                        </span>
                    </div>
                </article>
            </section>

            <!-- Search and filters -->
            <section class="glass-card p-5 sm:p-6">
                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="doSearch">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search by client name or email..."
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

                <div class="mt-5 border-t border-black/5 dark:border-white/10 pt-4">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Account Status
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
                            All ({{ totalClients }})
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
                            <span
                                class="h-2 w-2 rounded-full"
                                :style="{ backgroundColor: status.color }"
                            />
                            {{ status.label }} ({{ status.count }})
                        </button>
                    </div>
                </div>
            </section>

            <!-- Clients table -->
            <section class="glass-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-black/5 dark:border-white/10 px-6 py-4">
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Registered Clients</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Member accounts, identity verifications, and historical reservation counts.
                        </p>
                    </div>
                    <span class="rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400">
                        {{ props.clients.data.length }} shown
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[760px] w-full text-left">
                        <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-6 py-3.5">Client Profile</th>
                                <th class="px-6 py-3.5">Reservations</th>
                                <th class="px-6 py-3.5">Payments</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/5 dark:divide-white/5 text-sm">
                            <tr
                                v-for="client in props.clients.data"
                                :key="client.id"
                                class="cursor-pointer transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]"
                                @click="navigateToClient(client.id)"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-white/10 font-bold text-slate-800 dark:text-slate-200">
                                            {{ client.name.trim().charAt(0).toUpperCase() }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ client.name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ client.email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex min-w-8 justify-center rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        {{ client.reservations_count }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex min-w-8 justify-center rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        {{ client.payments_count }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :style="{
                                            backgroundColor: getStatusColor(client.is_active ? 'active' : 'suspended').bg,
                                            color: getStatusColor(client.is_active ? 'active' : 'suspended').text,
                                        }"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :style="{ backgroundColor: getStatusColor(client.is_active ? 'active' : 'suspended').dot }"
                                        />
                                        {{ client.is_active ? 'Active' : 'Suspended' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="show(client.id).url"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-[#58779D] dark:text-[#7094BF] hover:underline"
                                        @click.stop
                                    >
                                        Inspect
                                        <ArrowRight class="h-3.5 w-3.5" />
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="props.clients.data.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <Users class="mx-auto h-10 w-10 text-slate-400" />
                                    <p class="mt-3 font-semibold text-slate-900 dark:text-white">No clients found</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        Try adjusting your search query or status filter.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="props.clients.links?.length"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-black/5 dark:border-white/10 px-4 py-4"
                    aria-label="Client pagination"
                >
                    <Link
                        v-for="(link, i) in props.clients.links"
                        :key="i"
                        :href="link.url || ''"
                        preserve-scroll
                        class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                        :class="[
                            link.active
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10',
                            !link.url && 'pointer-events-none opacity-40',
                        ]"
                    >
                        <span v-html="link.label" />
                    </Link>
                </nav>
            </section>
        </main>
    </AdminLayout>
</template>
