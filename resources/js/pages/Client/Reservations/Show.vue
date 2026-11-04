<script setup lang="ts">
import ClientLayout from '@/layouts/ClientLayout.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import StarRating from '@/components/StarRating.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ShieldCheck, Star, Calendar, MapPin, Printer, ArrowLeft, Fuel, Gauge, CreditCard, Clock } from 'lucide-vue-next';
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
            label: status || 'Unknown',
        };
    }

    const hex = meta.color.replace('#', '');
    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);

    return {
        bg: `rgba(${r}, ${g}, ${b}, 0.12)`,
        text: meta.color,
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

const statusClass = computed(() => {
    const s = String(props.reservation?.status || '').toLowerCase();
    if (['confirmed', 'active', 'completed'].includes(s)) {
        return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
    }
    if (['cancelled', 'rejected'].includes(s)) {
        return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20';
    }
    return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
});
</script>

<template>
    <Head :title="`Voucher ${props.reservation.reservation_number} | Real Rent Car`" />

    <ClientLayout>
        <main class="min-h-screen flex-1 space-y-8 bg-[#FBFBFD] p-5 text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100 sm:p-8">

            <!-- Hero Staging -->
            <section class="relative isolate overflow-hidden rounded-3xl bg-[#101D35] p-6 text-white shadow-xl dark:bg-[#0F1724] sm:p-8">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(112,148,191,0.25),transparent_60%)]"></div>

                <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider" :class="statusClass">
                                {{ props.reservation.status }}
                            </span>
                            <span class="font-mono text-xs text-slate-400">
                                #{{ props.reservation.reservation_number }}
                            </span>
                        </div>

                        <h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">
                            {{ props.reservation.car ? `${props.reservation.car.year} ${props.reservation.car.make} ${props.reservation.car.model}` : 'Vehicle Reservation' }}
                        </h1>

                        <p class="mt-1 text-xs text-slate-300">
                            {{ fmtDate(props.reservation.start_date) }} to {{ fmtDate(props.reservation.end_date) }} ({{ props.reservation.total_days }} rental days)
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <Link
                            :href="index().url"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-white/20 bg-white/5 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-white/10"
                        >
                            <ArrowLeft class="h-3.5 w-3.5" />
                            <span>Ledger</span>
                        </Link>

                        <a
                            :href="print(props.reservation.id).url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-white/20 bg-white/5 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-white/10"
                        >
                            <Printer class="h-3.5 w-3.5" />
                            <span>Print Voucher</span>
                        </a>

                        <button
                            v-if="props.reservation.status === 'completed' && !props.reservation.review"
                            type="button"
                            @click="isReviewModalOpen = true"
                            class="apple-btn-primary inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold"
                        >
                            <Star class="h-3.5 w-3.5 fill-current" />
                            <span>Rate Trip</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Verified Review Banner (if already reviewed) -->
            <section
                v-if="props.reservation.review"
                class="glass-card overflow-hidden p-5 sm:p-6"
            >
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-4 dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                        <h3 class="text-sm font-bold text-slate-950 dark:text-white">Your Review on this Vehicle</h3>
                    </div>
                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                        Verified
                    </span>
                </div>

                <div class="mt-4 space-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <StarRating :rating="props.reservation.review.rating" :readonly="true" size="xs" />
                        <span class="font-bold text-slate-950 dark:text-white">{{ props.reservation.review.rating }} / 5</span>
                    </div>
                    <p v-if="props.reservation.review.title" class="font-bold text-slate-950 dark:text-white">
                        {{ props.reservation.review.title }}
                    </p>
                    <p class="text-slate-600 dark:text-slate-400">
                        {{ props.reservation.review.comment }}
                    </p>
                </div>
            </section>

            <!-- 2-Column Summary Grid -->
            <div class="grid gap-8 lg:grid-cols-12">
                <!-- Left Details -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Itinerary / Trip Timeline -->
                    <section class="glass-card p-5 sm:p-6">
                        <h3 class="text-base font-bold text-slate-950 dark:text-white">
                            Itinerary & Stations
                        </h3>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="mb-2 flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                    <Calendar class="h-3.5 w-3.5" />
                                    <span class="text-xs font-semibold">Pickup Schedule</span>
                                </div>
                                <p class="text-sm font-bold text-slate-950 dark:text-white">{{ fmtDate(props.reservation.start_date) }}</p>
                                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ props.reservation.pickup_location }}</p>
                            </div>

                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="mb-2 flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                    <Calendar class="h-3.5 w-3.5" />
                                    <span class="text-xs font-semibold">Return Schedule</span>
                                </div>
                                <p class="text-sm font-bold text-slate-950 dark:text-white">{{ fmtDate(props.reservation.end_date) }}</p>
                                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ props.reservation.return_location }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Financial Statement -->
                    <section class="glass-card p-5 sm:p-6">
                        <h3 class="text-base font-bold text-slate-950 dark:text-white">
                            Financial Statement
                        </h3>

                        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200/80 dark:border-white/10">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-slate-200/80 bg-slate-50 uppercase tracking-wider text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Item</th>
                                        <th class="px-4 py-3 text-right font-semibold">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/80 dark:divide-white/10">
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-950 dark:text-white">
                                            Base Rental ({{ props.reservation.total_days }} days × {{ fmtMoney(props.reservation.daily_rate) }})
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-slate-950 dark:text-white">
                                            {{ fmtMoney(Number(props.reservation.daily_rate) * Number(props.reservation.total_days)) }}
                                        </td>
                                    </tr>
                                    <tr v-for="extra in props.reservation.extras || []" :key="extra.id" class="bg-slate-50/40 dark:bg-white/5">
                                        <td class="px-4 py-2.5 text-slate-700 dark:text-slate-300">
                                            + {{ extra.name }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-950 dark:text-white">
                                            {{ fmtMoney(extra.total) }}
                                        </td>
                                    </tr>
                                    <tr v-if="Number(props.reservation.discount_amount) > 0" class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                        <td class="px-4 py-2.5 font-semibold">
                                            Discount ({{ props.reservation.coupon_code || 'COUPON' }})
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold">
                                            -{{ fmtMoney(props.reservation.discount_amount) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400">GST (7%)</td>
                                        <td class="px-4 py-2.5 text-right font-mono font-semibold text-slate-950 dark:text-white">
                                            {{ fmtMoney(props.reservation.tax_amount) }}
                                        </td>
                                    </tr>
                                    <tr class="bg-[#101D35] text-white dark:bg-white dark:text-slate-950">
                                        <td class="px-4 py-3 font-bold">Total Paid</td>
                                        <td class="px-4 py-3 text-right font-mono font-black text-sm">
                                            {{ fmtMoney(props.reservation.total_amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- Right Sidebar -->
                <div class="space-y-6 lg:col-span-4">
                    <section class="glass-card p-5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                            Vehicle Specifications
                        </h3>

                        <div class="mt-4 space-y-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">License Plate</span>
                                <span class="font-mono font-bold text-slate-950 dark:text-white">{{ props.reservation.car?.license_plate || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200/80 pt-2 dark:border-white/10">
                                <span class="text-slate-500 dark:text-slate-400">Powertrain</span>
                                <span class="font-semibold capitalize text-slate-950 dark:text-white">{{ props.reservation.car?.fuel_type || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200/80 pt-2 dark:border-white/10">
                                <span class="text-slate-500 dark:text-slate-400">Transmission</span>
                                <span class="font-semibold capitalize text-slate-950 dark:text-white">{{ props.reservation.car?.transmission || 'Automatic' }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="glass-card p-5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                            Concierge Assistance
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                            Need to adjust pickup time or request drop-off extension? Contact customer support directly.
                        </p>
                        <Link
                            href="/support/create"
                            class="apple-btn-primary mt-4 flex w-full items-center justify-center gap-1.5 py-2.5 text-xs font-semibold"
                        >
                            <span>Open Support Ticket</span>
                        </Link>
                    </section>
                </div>
            </div>
        </main>

        <!-- Review Modal -->
        <ReviewModal
            :is-open="isReviewModalOpen"
            :reservation="{ id: props.reservation.id, reservation_number: props.reservation.reservation_number, start_date: props.reservation.start_date, end_date: props.reservation.end_date, car: props.reservation.car }"
            @close="isReviewModalOpen = false"
            @submitted="router.reload()"
        />
    </ClientLayout>
</template>
