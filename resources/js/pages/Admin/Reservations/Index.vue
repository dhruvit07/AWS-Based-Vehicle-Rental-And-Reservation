<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    Car,
    ChevronRight,
    Clock,
    DollarSign,
    Search,
    User,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { index, show } from '@/routes/admin/reservations';

const props = defineProps<{
    reservations: {
        data: Array<{
            id: number;
            reservation_number: string;
            user: { id: number; name: string; email: string } | null;
            car: {
                id: number;
                make: string;
                model: string;
                year: number;
                license_plate: string;
            } | null;
            start_date: string;
            end_date: string;
            total_days: number;
            total_amount: number | string;
            status: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { search?: string; status?: string };
    statuses: Record<string, { label: string; count: number; color: string }>;
    currency: { symbol: string; code: string };
}>();

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');

const totalReservations = computed(() =>
    Object.values(props.statuses || {}).reduce(
        (sum, status) => sum + status.count,
        0,
    ),
);

function doSearch() {
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
}

watch(search, (value, oldValue) => {
    if (value === '' && oldValue !== '') doSearch();
});

function navigateToReservation(id: number) {
    router.visit(show(id).url);
}

function formatDate(date: string) {
    if (!date) return '—';

    const parsed = new Date(date.slice(0, 10) + 'T00:00:00');

    return Number.isNaN(parsed.getTime())
        ? date
        : new Intl.DateTimeFormat('en-US', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          }).format(parsed);
}

function formatAmount(amount: number | string) {
    const value = Number(amount);

    return Number.isFinite(value)
        ? value.toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
          })
        : '0.00';
}
</script>

<template>
    <Head title="Admin Reservations" />

    <AdminLayout>
        <main class="min-h-full space-y-7 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <section class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Reservations
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Manage customer bookings, rental schedules, and payment balances.
                    </p>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                        <CalendarDays class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Bookings</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ totalReservations }}</p>
                    </div>
                </div>
            </section>

            <!-- Search and filters -->
            <section class="glass-card p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search by booking code, client name, or vehicle..."
                            class="apple-input w-full pl-10"
                            @keyup.enter="doSearch"
                        />
                    </div>
                    <button
                        type="button"
                        class="apple-btn-primary h-11 px-6 text-sm font-medium cursor-pointer"
                        @click="doSearch"
                    >
                        Search
                    </button>
                </div>

                <div class="mt-5 border-t border-black/5 dark:border-white/10 pt-4">
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
                            All ({{ totalReservations }})
                        </button>

                        <button
                            v-for="(status, key) in props.statuses"
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
                            ></span>
                            {{ status.label }} ({{ status.count }})
                        </button>
                    </div>
                </div>
            </section>

            <!-- Reservation table -->
            <section class="glass-card overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-black/5 dark:border-white/10 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">All Bookings</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Select any reservation row to inspect full contract statement and itinerary.
                        </p>
                    </div>
                    <span class="w-fit rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400">
                        {{ reservations.data.length }} bookings on page
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[950px] w-full text-left">
                        <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-6 py-3.5">Booking Code</th>
                                <th class="px-6 py-3.5">Client</th>
                                <th class="px-6 py-3.5">Vehicle</th>
                                <th class="px-6 py-3.5">Schedule</th>
                                <th class="px-6 py-3.5">Total Amount</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/5 dark:divide-white/5 text-sm">
                            <tr
                                v-for="res in reservations.data"
                                :key="res.id"
                                class="cursor-pointer transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]"
                                @click="navigateToReservation(res.id)"
                            >
                                <td class="px-6 py-4">
                                    <span class="rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-1 font-mono text-xs font-medium text-slate-800 dark:text-slate-200">
                                        {{ res.reservation_number }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300">
                                            <User class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white truncate">
                                                {{ res.user?.name || 'Walk-in Guest' }}
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                {{ res.user?.email || '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300">
                                            <Car class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                {{ res.car ? `${res.car.year} ${res.car.make} ${res.car.model}` : 'Unassigned' }}
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                                {{ res.car?.license_plate || '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-xs">
                                    <div class="flex items-center gap-1.5 font-medium text-slate-800 dark:text-slate-200">
                                        <Clock class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ formatDate(res.start_date) }} – {{ formatDate(res.end_date) }}</span>
                                    </div>
                                    <p class="mt-0.5 text-slate-500 dark:text-slate-400">
                                        {{ res.total_days }} {{ res.total_days === 1 ? 'day' : 'days' }}
                                    </p>
                                </td>

                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ currency.symbol }}{{ formatAmount(res.total_amount) }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :style="{
                                            backgroundColor: `${props.statuses?.[res.status]?.color || '#64748B'}18`,
                                            color: props.statuses?.[res.status]?.color || '#64748B',
                                        }"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :style="{ backgroundColor: props.statuses?.[res.status]?.color || '#64748B' }"
                                        ></span>
                                        {{ props.statuses?.[res.status]?.label || res.status }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="show(res.id).url"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-[#58779D] dark:text-[#7094BF] hover:underline"
                                        @click.stop
                                    >
                                        Inspect
                                        <ChevronRight class="h-3.5 w-3.5" />
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="reservations.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <CalendarDays class="mx-auto h-10 w-10 text-slate-400" />
                                    <p class="mt-3 font-semibold text-slate-900 dark:text-white">No reservations found</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try adjusting your filters or search query.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="reservations.links?.length"
                    aria-label="Pagination"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-black/5 dark:border-white/10 px-4 py-4"
                >
                    <template v-for="(link, i) in reservations.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                            :class="link.active
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10'"
                        >
                            <span v-html="link.label"></span>
                        </Link>
                        <span
                            v-else
                            class="pointer-events-none rounded-lg border border-black/5 dark:border-white/5 px-3 py-1.5 text-xs text-slate-400 opacity-60"
                        >
                            <span v-html="link.label"></span>
                        </span>
                    </template>
                </nav>
            </section>
        </main>
    </AdminLayout>
</template>
