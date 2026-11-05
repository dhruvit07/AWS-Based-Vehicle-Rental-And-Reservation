<script setup lang="ts">
import CancelReservationModal from '@/components/CancelReservationModal.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import ClientLayout from '@/layouts/ClientLayout.vue';
import { show } from '@/routes/client/reservations';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Calendar, Star } from 'lucide-vue-next';
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
            refund_amount?: number | string;
            can_be_cancelled?: boolean;
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

const selectedReservationForCancel = ref<any | null>(null);
const isCancelModalOpen = ref(false);

const openReviewModal = (res: any) => {
    selectedReservationForReview.value = res;
    isReviewModalOpen.value = true;
};

const openCancelModal = (res: any) => {
    selectedReservationForCancel.value = res;
    isCancelModalOpen.value = true;
};

const canCancel = (res: any): boolean => {
    if (typeof res.can_be_cancelled === 'boolean') {
        return res.can_be_cancelled;
    }
    const s = String(res.status || '').toLowerCase();
    if (!['pending', 'confirmed'].includes(s)) return false;
    if (!res.start_date) return false;
    const start = new Date(res.start_date.slice(0, 10) + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return start >= today;
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
        return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
    }

    if (['cancelled', 'canceled', 'rejected'].includes(value)) {
        return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20';
    }

    return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
};
</script>

<template>
    <Head title="My Reservations | Real Rent Car" />

    <ClientLayout>
        <main
            class="min-h-screen flex-1 space-y-8 bg-[#FBFBFD] p-5 text-slate-900 transition-colors duration-200 sm:p-8 dark:bg-[#070A0F] dark:text-slate-100"
        >
            <!-- Header Staging Banner -->
            <section
                class="relative isolate overflow-hidden rounded-3xl bg-[#101D35] p-6 text-white shadow-xl sm:p-8 dark:bg-[#0F1724]"
            >
                <div
                    class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(112,148,191,0.25),transparent_60%)]"
                ></div>

                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                >
                    <div>
                        <h1
                            class="text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl"
                        >
                            Reservations Passport
                        </h1>
                        <p
                            class="mt-1 max-w-xl text-xs leading-relaxed text-slate-300 sm:text-sm"
                        >
                            Manage confirmed journeys, review detailed receipts,
                            and share verified feedback.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            href="/fleet"
                            class="apple-btn-primary inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold"
                        >
                            <span>Browse Fleet</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Reservations List / Table -->
            <section class="glass-card overflow-hidden">
                <div
                    class="flex items-center justify-between border-b border-slate-200/80 p-5 sm:px-6 dark:border-white/10"
                >
                    <div>
                        <h2
                            class="text-base font-bold tracking-tight text-slate-950 dark:text-white"
                        >
                            Booking Ledger
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Select any trip to inspect voucher or settlement
                            status
                        </p>
                    </div>

                    <span
                        class="rounded-full border border-slate-200/80 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                    >
                        {{ props.reservations.data.length }} recorded
                    </span>
                </div>

                <!-- Empty State -->
                <div
                    v-if="props.reservations.data.length === 0"
                    class="p-12 text-center"
                >
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400"
                    >
                        <Calendar class="h-6 w-6" />
                    </div>

                    <h3
                        class="mt-4 text-base font-bold text-slate-950 dark:text-white"
                    >
                        No reservations recorded yet
                    </h3>

                    <p
                        class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                    >
                        Your future bookings will automatically populate this
                        ledger once confirmed.
                    </p>

                    <Link
                        href="/fleet"
                        class="apple-btn-primary mt-6 inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-semibold"
                    >
                        <span>Reserve a Vehicle</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <!-- Ledger Content -->
                <template v-else>
                    <!-- Desktop Table View -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="border-b border-slate-200/80 bg-slate-50 tracking-wider text-slate-500 uppercase dark:border-white/10 dark:bg-white/5 dark:text-slate-400"
                            >
                                <tr>
                                    <th class="px-6 py-3.5 font-semibold">
                                        Voucher
                                    </th>
                                    <th class="px-6 py-3.5 font-semibold">
                                        Automobile
                                    </th>
                                    <th class="px-6 py-3.5 font-semibold">
                                        Rental Window
                                    </th>
                                    <th class="px-6 py-3.5 font-semibold">
                                        Total Settle
                                    </th>
                                    <th class="px-6 py-3.5 font-semibold">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3.5 text-right font-semibold"
                                    >
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-slate-200/80 dark:divide-white/10"
                            >
                                <tr
                                    v-for="res in props.reservations.data"
                                    :key="res.id"
                                    class="cursor-pointer transition hover:bg-slate-50/80 dark:hover:bg-white/5"
                                    @click="navigateToReservation(res.id)"
                                >
                                    <td class="px-6 py-4">
                                        <p
                                            class="font-mono font-bold text-slate-950 dark:text-white"
                                        >
                                            {{ res.reservation_number }}
                                        </p>
                                        <p class="text-[11px] text-slate-400">
                                            ID #{{ res.id }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-4">
                                        <p
                                            class="font-bold text-slate-950 dark:text-white"
                                        >
                                            {{
                                                res.car
                                                    ? `${res.car.year} ${res.car.make} ${res.car.model}`
                                                    : 'Vehicle pending'
                                            }}
                                        </p>
                                        <p
                                            class="text-[11px] text-slate-500 dark:text-slate-400"
                                        >
                                            {{ res.car?.license_plate || '—' }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-4">
                                        <p
                                            class="font-medium text-slate-950 dark:text-white"
                                        >
                                            {{ formatDate(res.start_date) }} →
                                            {{ formatDate(res.end_date) }}
                                        </p>
                                        <p
                                            class="text-[11px] text-slate-500 dark:text-slate-400"
                                        >
                                            {{ res.total_days }} day{{
                                                res.total_days === 1 ? '' : 's'
                                            }}
                                        </p>
                                    </td>

                                    <td
                                        class="px-6 py-4 font-mono font-bold text-slate-950 dark:text-white"
                                    >
                                        {{ props.currency.symbol
                                        }}{{ formatAmount(res.total_amount) }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold tracking-wider uppercase"
                                            :class="statusClass(res.status)"
                                        >
                                            {{ res.status }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                            @click.stop
                                        >
                                            <button
                                                v-if="canCancel(res)"
                                                type="button"
                                                @click="openCancelModal(res)"
                                                class="inline-flex items-center gap-1 rounded-lg border border-rose-500/20 bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-500/20 dark:text-rose-400"
                                            >
                                                <span>Cancel</span>
                                            </button>

                                            <button
                                                v-if="
                                                    res.status.toLowerCase() ===
                                                        'completed' &&
                                                    !res.review
                                                "
                                                type="button"
                                                @click="openReviewModal(res)"
                                                class="inline-flex items-center gap-1 rounded-lg border border-amber-400/40 bg-amber-400/10 px-2.5 py-1 text-xs font-semibold text-amber-600 transition hover:bg-amber-400/20 dark:text-amber-400"
                                            >
                                                <Star
                                                    class="h-3 w-3 fill-current"
                                                />
                                                <span>Rate</span>
                                            </button>

                                            <span
                                                v-else-if="res.review"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-amber-500"
                                            >
                                                <Star
                                                    class="h-3 w-3 fill-amber-400"
                                                />
                                                <span
                                                    >{{
                                                        res.review.rating
                                                    }}★</span
                                                >
                                            </span>

                                            <Link
                                                :href="show(res.id).url"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                                            >
                                                <span>Voucher</span>
                                                <ArrowRight class="h-3 w-3" />
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card List View -->
                    <div
                        class="divide-y divide-slate-200/80 md:hidden dark:divide-white/10"
                    >
                        <div
                            v-for="res in props.reservations.data"
                            :key="res.id"
                            class="p-4"
                            @click="navigateToReservation(res.id)"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="font-mono text-xs font-bold text-slate-950 dark:text-white"
                                >
                                    {{ res.reservation_number }}
                                </span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase"
                                    :class="statusClass(res.status)"
                                >
                                    {{ res.status }}
                                </span>
                            </div>

                            <p
                                class="mt-2 text-sm font-bold text-slate-950 dark:text-white"
                            >
                                {{
                                    res.car
                                        ? `${res.car.year} ${res.car.make} ${res.car.model}`
                                        : 'Vehicle pending'
                                }}
                            </p>

                            <div
                                class="mt-2 flex items-center justify-between text-xs text-slate-600 dark:text-slate-400"
                            >
                                <span
                                    >{{ formatDate(res.start_date) }} –
                                    {{ formatDate(res.end_date) }}</span
                                >
                                <span
                                    class="font-mono font-bold text-slate-950 dark:text-white"
                                    >{{ props.currency.symbol
                                    }}{{ formatAmount(res.total_amount) }}</span
                                >
                            </div>

                            <div
                                v-if="canCancel(res)"
                                class="mt-3 flex items-center justify-end border-t border-slate-200/60 pt-2.5 dark:border-white/10"
                                @click.stop
                            >
                                <button
                                    type="button"
                                    @click="openCancelModal(res)"
                                    class="inline-flex items-center gap-1 rounded-lg border border-rose-500/20 bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-500/20 dark:text-rose-400"
                                >
                                    <span>Cancel Booking</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </section>
        </main>

        <!-- Review Modal -->
        <ReviewModal
            :is-open="isReviewModalOpen"
            :reservation="selectedReservationForReview"
            @close="isReviewModalOpen = false"
            @submitted="router.reload()"
        />

        <!-- Cancel Reservation Modal -->
        <CancelReservationModal
            :is-open="isCancelModalOpen"
            :reservation="selectedReservationForCancel"
            :currency-symbol="props.currency?.symbol || '₹'"
            @close="isCancelModalOpen = false"
            @cancelled="router.reload()"
        />
    </ClientLayout>
</template>
