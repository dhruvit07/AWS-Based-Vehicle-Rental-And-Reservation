
<script setup lang="ts">
import ClientLayout from '@/layouts/ClientLayout.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { show } from '@/routes/client/reservations';
import { Star } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    reservations: {
        data: Array<{
            id: number;
            reservation_number: string;
            car: {
                id: number;
                make: string;
                model: string;
                year: number;
                license_plate: string;
                image_url?: string;
            } | null;
            start_date: string;
            end_date: string;
            total_days: number;
            total_amount: number | string;
            status: string;
            review?: {
                id: number;
                rating: number;
                title: string | null;
            } | null;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    currency: { symbol: string; code: string };
}>();

const selectedReservationForReview = ref<any | null>(null);
const isReviewModalOpen = ref(false);

const openReviewModal = (res: any) => {
    selectedReservationForReview.value = res;
    isReviewModalOpen.value = true;
};

const navigateToReservation = (id: number) => {
    router.visit(show(id).url);
};

const formatDate = (date: string) => {
    if (!date) return '—';

    const parsed = new Date(date.slice(0, 10) + 'T00:00:00');

    return Number.isNaN(parsed.getTime())
        ? date
        : new Intl.DateTimeFormat('en-IN', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          }).format(parsed);
};

const formatAmount = (amount: number | string) => {
    const value = Number(amount);

    return Number.isFinite(value)
        ? value.toLocaleString('en-IN', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
          })
        : '0.00';
};

const statusClass = (status: string) => {
    const value = status.toLowerCase();

    if (['confirmed', 'approved', 'completed'].includes(value)) {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    }

    if (['cancelled', 'canceled', 'rejected'].includes(value)) {
        return 'bg-rose-50 text-rose-700 ring-rose-200';
    }

    return 'bg-amber-50 text-amber-800 ring-amber-200';
};
</script>

<template>
    <Head title="My Reservations" />

    <ClientLayout>
        <main class="min-h-screen flex-1 space-y-7 bg-[#F4F6FA] p-4 sm:p-6 lg:p-8">
            <!-- Page heading -->
            <section
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#101D35] via-[#1B3150] to-[#263F60] p-6 text-white shadow-lg sm:p-8"
            >
                <div class="relative z-10">
                    <p class="mb-2 text-xs font-semibold tracking-[0.22em] text-[#C5D0DE] uppercase">
                        Your account
                    </p>

                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        My Reservations
                    </h1>

                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">
                        View your vehicle bookings, rental dates, payment totals,
                        and reservation status in one place.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                            <p class="text-xs text-slate-300">Total reservations</p>
                            <p class="mt-1 text-2xl font-bold">
                                {{ props.reservations.data.length }}
                                <span class="text-sm font-normal text-slate-300">on this page</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="pointer-events-none absolute -right-10 -top-20 h-64 w-64 rounded-full border border-white/10"
                ></div>
                <div
                    class="pointer-events-none absolute -right-2 -top-12 h-48 w-48 rounded-full border border-white/10"
                ></div>
            </section>

            <!-- Reservations table -->
            <section class="overflow-hidden rounded-2xl border border-[#DCE3ED] bg-white shadow-sm">
                <div class="flex flex-col gap-2 border-b border-[#E5EAF1] px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="text-lg font-bold text-[#101D35]">
                            Booking history
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Select a reservation to view its details.
                        </p>
                    </div>

                    <span class="inline-flex w-fit items-center rounded-full bg-[#F4F6FA] px-3 py-1.5 text-xs font-semibold text-[#263F60] ring-1 ring-[#DCE3ED]">
                        {{ props.reservations.data.length }} shown
                    </span>
                </div>

                <!-- Empty state -->
                <div
                    v-if="props.reservations.data.length === 0"
                    class="px-6 py-16 text-center"
                >
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#F4F6FA] text-[#58779D]">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-8 w-8"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"
                            />
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-[#101D35]">
                        No reservations yet
                    </h3>

                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">
                        Your bookings will appear here after you make a reservation.
                    </p>

                    <Link
                        href="/fleet"
                        class="mt-6 inline-flex items-center justify-center rounded-xl bg-[#101D35] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#263F60]"
                    >
                        Explore vehicles
                        <span class="ml-2">→</span>
                    </Link>
                </div>

                <!-- Desktop table -->
                <div v-else class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[800px] text-left">
                        <thead class="bg-[#F4F6FA]">
                            <tr class="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                <th class="px-6 py-4">Reservation</th>
                                <th class="px-6 py-4">Vehicle</th>
                                <th class="px-6 py-4">Rental period</th>
                                <th class="px-6 py-4">Total amount</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Details</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[#E8EDF4]">
                            <tr
                                v-for="res in props.reservations.data"
                                :key="res.id"
                                class="cursor-pointer transition-colors hover:bg-[#F8FAFD]"
                                @click="navigateToReservation(res.id)"
                            >
                                <td class="px-6 py-5">
                                    <p class="font-semibold text-[#101D35]">
                                        {{ res.reservation_number }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Booking #{{ res.id }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-semibold text-[#263F60]">
                                        {{
                                            res.car
                                                ? `${res.car.year} ${res.car.make} ${res.car.model}`
                                                : 'Vehicle unavailable'
                                        }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ res.car?.license_plate || '—' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="text-sm font-medium text-[#101D35]">
                                        {{ formatDate(res.start_date) }}
                                    </p>
                                    <p class="my-1 text-xs text-slate-400">
                                        to
                                    </p>
                                    <p class="text-sm font-medium text-[#101D35]">
                                        {{ formatDate(res.end_date) }}
                                    </p>
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ res.total_days }} rental days
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <p class="font-bold text-[#101D35]">
                                        {{ props.currency.symbol }}{{ formatAmount(res.total_amount) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ props.currency.code }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="space-y-1.5">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-3 py-1.5 text-xs font-semibold capitalize ring-1 ring-inset',
                                                statusClass(res.status),
                                            ]"
                                        >
                                            {{ res.status }}
                                        </span>

                                        <div
                                            v-if="res.review"
                                            class="flex items-center gap-1 text-xs font-bold text-amber-700"
                                        >
                                            <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-500" />
                                            <span>Rated {{ res.review.rating }}★</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="res.status.toLowerCase() === 'completed' && !res.review"
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-lg bg-[#101D35] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#263F60]"
                                            @click.stop="openReviewModal(res)"
                                        >
                                            <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                                            Rate Trip
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-lg border border-[#DCE3ED] px-3 py-2 text-sm font-semibold text-[#263F60] transition hover:border-[#263F60] hover:bg-[#F4F6FA]"
                                            @click.stop="navigateToReservation(res.id)"
                                        >
                                            View →
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile reservation cards -->
                <div v-if="props.reservations.data.length > 0" class="space-y-3 p-4 md:hidden">
                    <button
                        v-for="res in props.reservations.data"
                        :key="res.id"
                        type="button"
                        class="block w-full rounded-xl border border-[#DCE3ED] bg-white p-4 text-left transition hover:border-[#58779D] hover:bg-[#F8FAFD]"
                        @click="navigateToReservation(res.id)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-words text-sm font-bold text-[#101D35]">
                                    {{ res.reservation_number }}
                                </p>
                                <p class="mt-1 text-sm text-[#58779D]">
                                    {{
                                        res.car
                                            ? `${res.car.year} ${res.car.make} ${res.car.model}`
                                            : 'Vehicle unavailable'
                                    }}
                                </p>
                            </div>

                            <span
                                :class="[
                                    'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset',
                                    statusClass(res.status),
                                ]"
                            >
                                {{ res.status }}
                            </span>
                        </div>

                        <div class="my-4 grid grid-cols-2 gap-3 border-y border-[#E8EDF4] py-3">
                            <div>
                                <p class="text-xs text-slate-500">Pickup date</p>
                                <p class="mt-1 text-sm font-medium text-[#101D35]">
                                    {{ formatDate(res.start_date) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Return date</p>
                                <p class="mt-1 text-sm font-medium text-[#101D35]">
                                    {{ formatDate(res.end_date) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Duration</p>
                                <p class="mt-1 text-sm font-medium text-[#101D35]">
                                    {{ res.total_days }} days
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Total amount</p>
                                <p class="mt-1 text-sm font-bold text-[#101D35]">
                                    {{ props.currency.symbol }}{{ formatAmount(res.total_amount) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-[#263F60]">
                                View reservation details →
                            </p>

                            <div
                                v-if="res.review"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-200"
                            >
                                <Star class="h-3 w-3 fill-amber-400 text-amber-500" />
                                <span>Rated {{ res.review.rating }}★</span>
                            </div>

                            <button
                                v-else-if="res.status.toLowerCase() === 'completed'"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-lg bg-[#101D35] px-3 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#263F60]"
                                @click.stop="openReviewModal(res)"
                            >
                                <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                                Rate Trip
                            </button>
                        </div>
                    </button>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="props.reservations.links?.length && props.reservations.data.length > 0"
                    aria-label="Reservation pages"
                    class="flex flex-wrap items-center gap-2 border-t border-[#E5EAF1] px-4 py-4 sm:px-6"
                >
                    <Link
                        v-for="(link, i) in props.reservations.links"
                        :key="i"
                        :href="link.url || '#'"
                        :preserve-scroll="true"
                        :class="[
                            'rounded-lg px-3 py-2 text-sm font-medium transition',
                            link.active
                                ? 'bg-[#101D35] text-white shadow-sm'
                                : 'border border-[#DCE3ED] bg-white text-[#263F60] hover:bg-[#F4F6FA]',
                            !link.url && 'pointer-events-none opacity-40',
                        ]"
                    >
                        <span v-html="link.label" />
                    </Link>
                </nav>
            </section>
        </main>

        <!-- Rate Trip Modal -->
        <ReviewModal
            :is-open="isReviewModalOpen"
            :reservation="selectedReservationForReview"
            @close="isReviewModalOpen = false"
            @submitted="router.reload()"
        />
    </ClientLayout>
</template>
