
<script setup lang="ts">
import ClientLayout from '@/layouts/ClientLayout.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import StarRating from '@/components/StarRating.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ShieldCheck, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { index, print } from '@/routes/client/reservations';

const props = defineProps<{
    reservation: any;
    statusMeta: Array<{ value: string; label: string; color: string }>;
    paymentStatusMeta: Array<{ value: string; label: string }>;
    currency: { symbol: string; code: string };
}>();

const isReviewModalOpen = ref(false);

const statusMap = computed(() => {
    const map: Record<string, { label: string; color: string }> = {};

    for (const status of props.statusMeta || []) {
        map[status.value] = {
            label: status.label,
            color: status.color,
        };
    }

    return map;
});

function getStatusStyle(status: string) {
    const meta = statusMap.value[status];

    if (!meta) {
        return {
            bg: 'rgba(107,114,128,0.1)',
            text: '#6B7280',
            dot: '#6B7280',
            label: status || 'Unknown',
        };
    }

    const hex = meta.color.replace('#', '');
    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);

    return {
        bg: `rgba(${r}, ${g}, ${b}, 0.1)`,
        text: meta.color,
        dot: meta.color,
        label: meta.label,
    };
}

function fmtDate(date?: string) {
    if (!date) return '—';

    const parsed = new Date(date.slice(0, 10) + 'T00:00:00');

    if (Number.isNaN(parsed.getTime())) return date;

    return new Intl.DateTimeFormat('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(parsed);
}

function fmtDateTime(date?: string) {
    if (!date) return '—';

    const parsed = new Date(date);

    return Number.isNaN(parsed.getTime())
        ? date
        : new Intl.DateTimeFormat('en-IN', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(parsed);
}

function fmtMoney(amount?: number | string) {
    const value = Number(amount ?? 0);

    const formatted = Number.isFinite(value)
        ? value.toLocaleString('en-IN', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
          })
        : '0.00';

    return `${props.currency.symbol}${formatted}`;
}

function paymentStatusClass(status: string) {
    const value = (status || '').toLowerCase();

    if (['paid', 'completed', 'success', 'successful'].includes(value)) {
        return 'bg-emerald-50 text-emerald-700';
    }

    if (['failed', 'cancelled', 'canceled', 'rejected'].includes(value)) {
        return 'bg-rose-50 text-rose-700';
    }

    return 'bg-amber-50 text-amber-800';
}
</script>

<template>
    <Head :title="`Reservation ${props.reservation?.reservation_number || ''}`" />

    <ClientLayout>
        <main class="min-h-screen flex-1 space-y-6 bg-[#F4F6FA] p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <section
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#101D35] via-[#1B3150] to-[#263F60] p-6 text-white shadow-lg sm:p-8"
            >
                <div class="relative z-10">
                    <p class="text-xs font-semibold tracking-[0.22em] text-[#C5D0DE] uppercase">
                        Booking management
                    </p>

                    <h1 class="mt-3 break-words text-2xl font-bold sm:text-3xl">
                        Reservation Details
                    </h1>

                    <p class="mt-2 break-words text-sm text-slate-300">
                        Reference: {{ props.reservation?.reservation_number || '—' }}
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link :href="index().url">
                            <Button
                                variant="outline"
                                class="border-white/30 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                            >
                                ← Back to Reservations
                            </Button>
                        </Link>

                        <a
                            :href="print(props.reservation.id).url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button class="bg-[#C5D0DE] text-[#101D35] hover:bg-white">
                                Print Reservation
                            </Button>
                        </a>

                        <button
                            v-if="props.reservation.status === 'completed' && !props.reservation.review"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold text-[#101D35] transition hover:bg-amber-300"
                            @click="isReviewModalOpen = true"
                        >
                            <Star class="h-4 w-4 fill-[#101D35] text-[#101D35]" />
                            Rate Trip
                        </button>

                        <div
                            v-else-if="props.reservation.review"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-white/25 bg-white/10 px-3.5 py-2 text-sm font-semibold text-white"
                        >
                            <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                            Rated {{ props.reservation.review.rating }} / 5
                        </div>
                    </div>
                </div>

                <div class="pointer-events-none absolute -right-12 -top-16 h-64 w-64 rounded-full border border-white/10"></div>
                <div class="pointer-events-none absolute -right-3 -top-8 h-48 w-48 rounded-full border border-white/10"></div>
            </section>

            <!-- Status and total -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-[#DCE3ED] bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Reservation status</p>

                    <div class="mt-3">
                        <span
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold"
                            :style="{
                                backgroundColor: getStatusStyle(props.reservation.status).bg,
                                color: getStatusStyle(props.reservation.status).text,
                            }"
                        >
                            <span
                                class="h-2 w-2 rounded-full"
                                :style="{
                                    backgroundColor: getStatusStyle(props.reservation.status).dot,
                                }"
                            ></span>
                            {{ getStatusStyle(props.reservation.status).label }}
                        </span>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-slate-500">
                        This status reflects the reservation record in your system.
                    </p>
                </div>

                <div class="rounded-2xl border border-[#DCE3ED] bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Total reservation amount</p>
                    <p class="mt-2 break-words text-3xl font-bold text-[#101D35]">
                        {{ fmtMoney(props.reservation.total_amount) }}
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        Currency: {{ props.currency.code }}
                    </p>
                </div>
            </section>

            <!-- Verified Review Section (if already reviewed) -->
            <section
                v-if="props.reservation.review"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="flex items-center justify-between border-b border-[#E5EAF1] px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                            <Star class="h-5 w-5 fill-amber-400" />
                        </div>
                        <div>
                            <h2 class="font-bold text-[#101D35]">Your Verified Trip Review</h2>
                            <p class="text-xs text-slate-500">Submitted for this reservation</p>
                        </div>
                    </div>

                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                        <ShieldCheck class="h-3.5 w-3.5" />
                        Verified Renter
                    </span>
                </div>

                <div class="space-y-3 p-5 sm:p-6">
                    <div class="flex items-center gap-3">
                        <StarRating :rating="props.reservation.review.rating" :readonly="true" size="sm" />
                        <span class="text-xs font-bold text-[#101D35]">
                            {{ props.reservation.review.rating }} out of 5 stars
                        </span>
                    </div>

                    <h3 v-if="props.reservation.review.title" class="text-base font-bold text-[#101D35]">
                        {{ props.reservation.review.title }}
                    </h3>

                    <p class="text-sm leading-relaxed text-slate-600">
                        {{ props.reservation.review.comment }}
                    </p>
                </div>
            </section>

            <!-- Unreviewed completed prompt -->
            <section
                v-else-if="props.reservation.status === 'completed'"
                class="flex flex-col justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50/70 p-5 sm:flex-row sm:items-center"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                        <Star class="h-5 w-5 fill-white text-white" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-amber-950">How was your trip with this vehicle?</h3>
                        <p class="mt-0.5 text-xs text-amber-800">
                            Your feedback helps future drivers and rewards excellent fleet quality.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="shrink-0 rounded-xl bg-[#101D35] px-5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#263F60]"
                    @click="isReviewModalOpen = true"
                >
                    Write a Review
                </button>
            </section>

            <!-- Main details -->
            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Client -->
                <div class="overflow-hidden rounded-2xl border border-[#DCE3ED] bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-[#E5EAF1] px-5 py-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF0F7] text-[#263F60]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19a6 6 0 00-12 0m12 0H3m12 0h6a6 6 0 00-6-6m-3-7a4 4 0 11-8 0 4 4 0 018 0zm6 1a3 3 0 00-2-5" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-[#101D35]">Client Information</h2>
                            <p class="text-xs text-slate-500">Customer details</p>
                        </div>
                    </div>

                    <div class="space-y-5 p-5">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Full name</p>
                            <p class="mt-1 break-words font-semibold text-[#101D35]">
                                {{ props.reservation.user?.name || '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Email address</p>
                            <p class="mt-1 break-words font-medium text-[#263F60]">
                                {{ props.reservation.user?.email || '—' }}
                            </p>
                        </div>

                        <div v-if="props.reservation.phone">
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Phone</p>
                            <p class="mt-1 font-medium text-[#101D35]">
                                {{ props.reservation.phone }}
                            </p>
                        </div>

                        <div v-if="props.reservation.driver_license">
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Driver's licence</p>
                            <p class="mt-1 font-medium text-[#101D35]">
                                {{ props.reservation.driver_license }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Vehicle -->
                <div class="overflow-hidden rounded-2xl border border-[#DCE3ED] bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-[#E5EAF1] px-5 py-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF0F7] text-[#263F60]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 17h14l1-5-2-5H6l-2 5 1 5zm0 0v2m14-2v2M4 12h16M7 15h.01M17 15h.01M7 7l1-3h8l1 3" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-[#101D35]">Vehicle Information</h2>
                            <p class="text-xs text-slate-500">Reserved vehicle</p>
                        </div>
                    </div>

                    <div class="space-y-5 p-5">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Vehicle</p>
                            <p class="mt-1 break-words text-lg font-bold text-[#101D35]">
                                {{
                                    props.reservation.car
                                        ? `${props.reservation.car.year} ${props.reservation.car.make} ${props.reservation.car.model}`
                                        : 'Vehicle unavailable'
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Registration / plate</p>
                            <p class="mt-1 font-medium text-[#263F60]">
                                {{ props.reservation.car?.license_plate || '—' }}
                            </p>
                        </div>

                        <div v-if="props.reservation.car?.fuel_type">
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Fuel type</p>
                            <p class="mt-1 font-medium text-[#101D35]">
                                {{ props.reservation.car.fuel_type }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Rental details -->
                <div class="overflow-hidden rounded-2xl border border-[#DCE3ED] bg-white shadow-sm lg:col-span-2">
                    <div class="border-b border-[#E5EAF1] px-5 py-4">
                        <h2 class="font-bold text-[#101D35]">Rental Details</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Dates, duration, and pickup locations
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl bg-[#F4F6FA] p-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase">Pickup date</p>
                            <p class="mt-2 font-bold text-[#101D35]">
                                {{ fmtDate(props.reservation.start_date) }}
                            </p>
                            <p class="mt-1 text-sm text-[#58779D]">
                                {{ props.reservation.pickup_time || 'Time not specified' }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-[#F4F6FA] p-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase">Return date</p>
                            <p class="mt-2 font-bold text-[#101D35]">
                                {{ fmtDate(props.reservation.end_date) }}
                            </p>
                            <p class="mt-1 text-sm text-[#58779D]">
                                {{ props.reservation.return_time || 'Time not specified' }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-[#F4F6FA] p-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase">Rental duration</p>
                            <p class="mt-2 text-2xl font-bold text-[#101D35]">
                                {{ props.reservation.total_days ?? '—' }}
                                <span class="text-sm font-medium">days</span>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Pickup location</p>
                            <p class="mt-2 break-words font-semibold text-[#101D35]">
                                {{ props.reservation.pickup_location || '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Return location</p>
                            <p class="mt-2 break-words font-semibold text-[#101D35]">
                                {{ props.reservation.return_location || '—' }}
                            </p>
                        </div>

                        <div v-if="props.reservation.cancelled_at">
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Cancelled at</p>
                            <p class="mt-2 break-words font-semibold text-[#101D35]">
                                {{ fmtDateTime(props.reservation.cancelled_at) }}
                            </p>
                        </div>

                        <div
                            v-if="props.reservation.cancellation_reason"
                            class="sm:col-span-2 lg:col-span-3"
                        >
                            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Cancellation reason</p>
                            <p class="mt-2 rounded-xl bg-rose-50 p-4 text-sm leading-6 text-rose-800">
                                {{ props.reservation.cancellation_reason }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Amount breakdown -->
                <div class="overflow-hidden rounded-2xl border border-[#DCE3ED] bg-white shadow-sm">
                    <div class="border-b border-[#E5EAF1] px-5 py-4">
                        <h2 class="font-bold text-[#101D35]">Price Breakdown</h2>
                        <p class="mt-1 text-xs text-slate-500">Reservation charges</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-sm text-slate-600">Daily rate</span>
                            <span class="text-right font-semibold text-[#101D35]">
                                {{ fmtMoney(props.reservation.daily_rate) }}
                            </span>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <span class="text-sm text-slate-600">Subtotal</span>
                            <span class="text-right font-semibold text-[#101D35]">
                                {{ fmtMoney(props.reservation.subtotal) }}
                            </span>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <span class="text-sm text-slate-600">Tax</span>
                            <span class="text-right font-semibold text-[#101D35]">
                                {{ fmtMoney(props.reservation.tax_amount) }}
                            </span>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <span class="text-sm text-slate-600">Discount</span>
                            <span class="text-right font-semibold text-emerald-700">
                                -{{ fmtMoney(props.reservation.discount_amount) }}
                            </span>
                        </div>

                        <div class="border-t border-[#DCE3ED] pt-4">
                            <div class="flex items-start justify-between gap-4">
                                <span class="font-bold text-[#101D35]">Total amount</span>
                                <span class="text-right text-xl font-bold text-[#101D35]">
                                    {{ fmtMoney(props.reservation.total_amount) }}
                                </span>
                            </div>
                            <p class="mt-2 text-right text-xs text-slate-500">
                                {{ props.currency.code }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Payments -->
                <div class="overflow-hidden rounded-2xl border border-[#DCE3ED] bg-white shadow-sm lg:col-span-2">
                    <div class="border-b border-[#E5EAF1] px-5 py-4">
                        <h2 class="font-bold text-[#101D35]">Payment History</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Payment records linked to this reservation
                        </p>
                    </div>

                    <div
                        v-if="!props.reservation.payments || props.reservation.payments.length === 0"
                        class="p-8 text-center"
                    >
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-[#F4F6FA] text-[#58779D]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2zm2 8h4" />
                            </svg>
                        </div>
                        <p class="mt-3 font-semibold text-[#101D35]">No payment records</p>
                        <p class="mt-1 text-sm text-slate-500">
                            No payments are currently recorded for this reservation.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[650px] text-left">
                            <thead class="bg-[#F4F6FA]">
                                <tr class="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                    <th class="px-5 py-4">Payment ID</th>
                                    <th class="px-5 py-4">Amount</th>
                                    <th class="px-5 py-4">Method</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4">Processed</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-[#E8EDF4]">
                                <tr
                                    v-for="payment in props.reservation.payments"
                                    :key="payment.id"
                                    class="hover:bg-[#F8FAFD]"
                                >
                                    <td class="px-5 py-4 font-semibold text-[#101D35]">
                                        {{ payment.payment_number || payment.id }}
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-[#101D35]">
                                        {{ fmtMoney(payment.amount) }}
                                    </td>
                                    <td class="px-5 py-4 text-sm capitalize text-slate-600">
                                        {{ payment.payment_method || '—' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-3 py-1.5 text-xs font-semibold capitalize',
                                                paymentStatusClass(payment.status),
                                            ]"
                                        >
                                            {{ payment.status || 'Unknown' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-600">
                                        {{ fmtDateTime(payment.processed_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Bottom actions -->
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#DCE3ED] bg-white p-5">
                <div>
                    <p class="font-semibold text-[#101D35]">Need to review another booking?</p>
                  <p class="mt-1 text-sm text-slate-500">
                        Return to your reservation list.
                    </p>
                </div>

                <Link :href="index().url">
                    <Button class="bg-[#101D35] text-white hover:bg-[#263F60]">
                        Back to Reservations
                    </Button>
                </Link>
            </div>
        </main>

        <!-- Rate Trip Modal -->
        <ReviewModal
            :is-open="isReviewModalOpen"
            :reservation="props.reservation"
            @close="isReviewModalOpen = false"
            @submitted="router.reload()"
        />
    </ClientLayout>
</template>
