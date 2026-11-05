<script setup lang="ts">
import CancelReservationModal from '@/components/CancelReservationModal.vue';
import EarlyReturnModal from '@/components/EarlyReturnModal.vue';
import ExtendRentalModal from '@/components/ExtendRentalModal.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import StarRating from '@/components/StarRating.vue';
import VehicleDamageBlueprint from '@/components/VehicleDamageBlueprint.vue';
import ClientLayout from '@/layouts/ClientLayout.vue';
import { index, print } from '@/routes/client/reservations';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    CalendarPlus,
    Car,
    CheckCircle2,
    Clock,
    CreditCard,
    FileCheck2,
    Fuel,
    Gauge,
    Headphones,
    HelpCircle,
    Info,
    MapPin,
    Printer,
    QrCode,
    RotateCcw,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    Star,
    Users,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    reservation: any;
    statusMeta: Array<{ value: string; label: string; color: string }>;
    paymentStatusMeta: Array<{ value: string; label: string }>;
    currency: { symbol: string; code: string };
}>();

const isReviewModalOpen = ref(false);
const isCancelModalOpen = ref(false);
const isExtendModalOpen = ref(false);
const isEarlyReturnModalOpen = ref(false);

const isExtendable = computed(() => {
    const s = String(props.reservation?.status || '').toLowerCase();
    return ['confirmed', 'active'].includes(s);
});

const isEarlyReturnable = computed(() => {
    const s = String(props.reservation?.status || '').toLowerCase();
    return s === 'active';
});

const isCancellable = computed(() => {
    if (typeof props.reservation?.can_be_cancelled === 'boolean') {
        return props.reservation.can_be_cancelled;
    }
    const s = String(props.reservation?.status || '').toLowerCase();
    if (!['pending', 'confirmed'].includes(s)) return false;
    if (!props.reservation?.start_date) return false;
    const start = new Date(
        props.reservation.start_date.slice(0, 10) + 'T00:00:00',
    );
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return start >= today;
});

const isCancelled = computed(() => {
    return (
        String(props.reservation?.status || '').toLowerCase() === 'cancelled'
    );
});

const refundTransactionId = computed(() => {
    if (props.reservation?.payments?.length) {
        for (const payment of props.reservation.payments) {
            if (
                payment.transaction_id &&
                payment.transaction_id.includes('REF-')
            ) {
                const match = payment.transaction_id.match(/REF-[A-Z0-9]+/);
                if (match) return match[0];
            }
        }
    }
    return null;
});

const refundAmount = computed(() => {
    if (props.reservation?.payments?.length) {
        const refunded = props.reservation.payments
            .filter(
                (p: any) =>
                    p.status === 'refunded' || Number(p.refunded_amount) > 0,
            )
            .reduce(
                (sum: number, p: any) =>
                    sum + Number(p.refunded_amount || p.amount || 0),
                0,
            );
        if (refunded > 0) return refunded;
    }
    return Number(
        props.reservation?.refund_amount ??
            props.reservation?.total_amount ??
            0,
    );
});

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

function fmtDateTime(date?: string) {
    if (!date) return '—';
    const parsed = new Date(date);

    if (Number.isNaN(parsed.getTime())) return date;

    return new Intl.DateTimeFormat('en-IN', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(parsed);
}

const statusBadgeClasses = computed(() => {
    const s = String(props.reservation?.status || '').toLowerCase();
    switch (s) {
        case 'confirmed':
        case 'active':
            return {
                badge: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                dot: 'bg-emerald-500 animate-pulse',
            };
        case 'completed':
            return {
                badge: 'border-sky-500/20 bg-sky-500/10 text-sky-600 dark:text-sky-400',
                dot: 'bg-sky-500',
            };
        case 'cancelled':
        case 'rejected':
            return {
                badge: 'border-rose-500/20 bg-rose-500/10 text-rose-600 dark:text-rose-400',
                dot: 'bg-rose-500',
            };
        default:
            return {
                badge: 'border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400',
                dot: 'bg-amber-500 animate-pulse',
            };
    }
});
</script>

<template>
    <Head
        :title="`Journey Hub #${props.reservation.reservation_number} | Real Rent Car`"
    />

    <ClientLayout>
        <main
            class="min-h-screen flex-1 space-y-6 bg-[#F8F9FA] p-4 text-slate-900 transition-colors duration-200 sm:p-6 lg:p-8 dark:bg-[#06090E] dark:text-slate-100"
        >
            <!-- Top Navigation & Passport Header Bar -->
            <div
                class="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-[#0A101D]"
            >
                <div class="flex items-center gap-3">
                    <Link
                        :href="index().url"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>Back to Ledger</span>
                    </Link>

                    <div class="h-4 w-px bg-slate-200 dark:bg-white/10"></div>

                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs font-bold text-slate-400">
                            #{{ props.reservation.reservation_number }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider"
                            :class="statusBadgeClasses.badge"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="statusBadgeClasses.dot"
                            ></span>
                            <span>{{ props.reservation.status }}</span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <Clock class="h-3.5 w-3.5" />
                    <span>Booked on {{ fmtDateTime(props.reservation.created_at) }}</span>
                </div>
            </div>

            <!-- Vehicle Staging Spotlight Card -->
            <section
                class="relative isolate overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm lg:p-8 dark:border-white/10 dark:bg-[#0A101D]"
            >
                <!-- Subtle Nordic Rim Glow in Dark Mode -->
                <div
                    class="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-[#58779D]/10 blur-3xl dark:bg-[#58779D]/15"
                ></div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-center">
                    <!-- Left: Vehicle Photo / Staging Stage (5 cols) -->
                    <div class="lg:col-span-5">
                        <div
                            class="relative aspect-16/10 w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-100 shadow-inner dark:border-white/10 dark:bg-black/40"
                        >
                            <img
                                v-if="props.reservation.car?.image_url"
                                :src="props.reservation.car.image_url"
                                :alt="`${props.reservation.car?.make} ${props.reservation.car?.model}`"
                                class="h-full w-full object-cover transition-transform duration-700 hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-600"
                            >
                                <Car class="h-16 w-16 stroke-1 opacity-60" />
                            </div>

                            <!-- Overlaid Telemetry Chips -->
                            <div class="absolute left-3 top-3 flex items-center gap-1.5">
                                <span
                                    v-if="props.reservation.car?.year"
                                    class="rounded-md border border-white/20 bg-black/60 px-2 py-0.5 font-mono text-[10px] font-bold text-white backdrop-blur-md"
                                >
                                    {{ props.reservation.car.year }}
                                </span>
                                <span
                                    v-if="props.reservation.car?.license_plate"
                                    class="rounded-md border border-white/20 bg-black/60 px-2 py-0.5 font-mono text-[10px] font-bold text-white backdrop-blur-md"
                                >
                                    {{ props.reservation.car.license_plate }}
                                </span>
                            </div>

                            <div class="absolute bottom-3 right-3">
                                <span
                                    class="inline-flex items-center gap-1 rounded-md border border-emerald-500/30 bg-emerald-950/80 px-2 py-0.5 text-[10px] font-bold text-emerald-300 backdrop-blur-md"
                                >
                                    <ShieldCheck class="h-3 w-3" />
                                    <span>PDI Verified</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Vehicle Details & Trip Route (7 cols) -->
                    <div class="space-y-5 lg:col-span-7">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                                    Precision Asset
                                </span>
                            </div>

                            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl dark:text-white">
                                {{
                                    props.reservation.car
                                        ? `${props.reservation.car.make} ${props.reservation.car.model}`
                                        : 'Vehicle Reservation'
                                }}
                            </h1>
                        </div>

                        <!-- 4-Spec Telemetry Strip -->
                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 text-xs">
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-white/5 dark:bg-white/5">
                                <div class="flex items-center gap-1.5 text-slate-400">
                                    <Gauge class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Gearbox</span>
                                </div>
                                <p class="mt-1 font-semibold text-slate-900 capitalize dark:text-white">
                                    {{ props.reservation.car?.transmission || 'Automatic' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-white/5 dark:bg-white/5">
                                <div class="flex items-center gap-1.5 text-slate-400">
                                    <Zap v-if="props.reservation.car?.fuel_type === 'electric'" class="h-3.5 w-3.5 text-cyan-400" />
                                    <Fuel v-else class="h-3.5 w-3.5 text-emerald-500" />
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Powertrain</span>
                                </div>
                                <p class="mt-1 font-semibold text-slate-900 capitalize dark:text-white">
                                    {{ props.reservation.car?.fuel_type || 'Gasoline' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-white/5 dark:bg-white/5">
                                <div class="flex items-center gap-1.5 text-slate-400">
                                    <Users class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Capacity</span>
                                </div>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-white">
                                    {{ props.reservation.car?.seats || 5 }} Seats
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-white/5 dark:bg-white/5">
                                <div class="flex items-center gap-1.5 text-slate-400">
                                    <Shield class="h-3.5 w-3.5 text-emerald-500" />
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Deposit</span>
                                </div>
                                <p class="mt-1 font-mono font-semibold text-slate-900 dark:text-white">
                                    ₹5,000 Refundable
                                </p>
                            </div>
                        </div>

                        <!-- Trip Route Schedule Box -->
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-white/5 dark:bg-white/[0.03]">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                        <MapPin class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pick-up Station</span>
                                        <p class="text-xs font-bold text-slate-950 dark:text-white">
                                            {{ props.reservation.pickup_location || 'Designated Regional Hub' }}
                                        </p>
                                        <p class="font-mono text-xs text-slate-500 dark:text-slate-400">
                                            {{ fmtDate(props.reservation.start_date) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="hidden sm:block text-center">
                                    <span class="rounded-full border border-slate-200/80 bg-white px-2.5 py-1 font-mono text-[11px] font-bold text-slate-700 shadow-xs dark:border-white/10 dark:bg-white/10 dark:text-slate-300">
                                        {{ props.reservation.total_days }} Days
                                    </span>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                        <MapPin class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Return Station</span>
                                        <p class="text-xs font-bold text-slate-950 dark:text-white">
                                            {{ props.reservation.return_location || 'Designated Regional Hub' }}
                                        </p>
                                        <p class="font-mono text-xs text-slate-500 dark:text-slate-400">
                                            {{ fmtDate(props.reservation.end_date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Digital Action Ribbon -->
            <section
                class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            Digital Passports & Operations:
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Rental Agreement & PDI Checklist -->
                        <Link
                            :href="`/booking/${props.reservation.id}/agreement`"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3.5 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-500/20 dark:text-emerald-300"
                        >
                            <FileCheck2 class="h-3.5 w-3.5 text-emerald-500" />
                            <span>Agreement & PDI</span>
                        </Link>

                        <!-- Mobile Gate Pass QR -->
                        <a
                            :href="`/verify/voucher/${props.reservation.reservation_number}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-[#58779D]/30 bg-[#58779D]/10 px-3.5 py-2 text-xs font-semibold text-[#58779D] transition hover:bg-[#58779D]/20 dark:text-[#7094BF]"
                        >
                            <QrCode class="h-3.5 w-3.5" />
                            <span>Gate Pass QR</span>
                        </a>

                        <!-- Print Voucher -->
                        <a
                            :href="print(props.reservation.id).url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
                        >
                            <Printer class="h-3.5 w-3.5" />
                            <span>Print Voucher</span>
                        </a>

                        <!-- Extend Trip -->
                        <button
                            v-if="isExtendable"
                            type="button"
                            @click="isExtendModalOpen = true"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-sky-500/30 bg-sky-500/10 px-3.5 py-2 text-xs font-semibold text-sky-700 transition hover:bg-sky-500/20 dark:text-sky-300"
                        >
                            <CalendarPlus class="h-3.5 w-3.5 text-sky-500" />
                            <span>Extend Trip</span>
                        </button>

                        <!-- Early Return -->
                        <button
                            v-if="isEarlyReturnable"
                            type="button"
                            @click="isEarlyReturnModalOpen = true"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3.5 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-500/20 dark:text-amber-300"
                        >
                            <RotateCcw class="h-3.5 w-3.5 text-amber-500" />
                            <span>Early Return</span>
                        </button>

                        <!-- Rate Trip -->
                        <button
                            v-if="props.reservation.status === 'completed' && !props.reservation.review"
                            type="button"
                            @click="isReviewModalOpen = true"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-[#101D35] px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-[#1a2d52] dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                        >
                            <Star class="h-3.5 w-3.5 fill-current" />
                            <span>Rate Trip</span>
                        </button>

                        <!-- Cancel Reservation -->
                        <button
                            v-if="isCancellable"
                            type="button"
                            @click="isCancelModalOpen = true"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3.5 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-500/20 dark:text-rose-400"
                        >
                            <AlertTriangle class="h-3.5 w-3.5 text-rose-500" />
                            <span>Cancel Reservation</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Booking Cancelled & Refunded Passport Card -->
            <section
                v-if="isCancelled"
                class="overflow-hidden rounded-3xl border border-rose-500/20 bg-rose-500/[0.03] p-6 shadow-sm dark:border-rose-500/20 dark:bg-rose-500/[0.04]"
            >
                <div
                    class="flex flex-col justify-between gap-4 border-b border-rose-500/15 pb-5 sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                        >
                            <RotateCcw class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-950 dark:text-white">
                                    Reservation Cancelled & Refund Initiated
                                </h2>
                                <span
                                    class="rounded-full bg-rose-500/15 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400"
                                >
                                    Cancelled
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                This reservation has been cancelled and unblocked on our visual fleet calendar.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-2.5 text-right"
                    >
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                                Refund Credited
                            </p>
                            <p class="font-mono text-lg font-black text-emerald-600 dark:text-emerald-400">
                                {{ fmtMoney(refundAmount) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 text-xs sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200/80 bg-white/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                        <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Cancellation Time
                        </p>
                        <p class="mt-1 font-semibold text-slate-950 dark:text-white">
                            {{ fmtDateTime(props.reservation.cancelled_at) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-white/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                        <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Reason Documented
                        </p>
                        <p class="mt-1 font-semibold break-words text-slate-950 dark:text-white">
                            {{ props.reservation.cancellation_reason || 'Change of travel plans' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-white/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                        <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Refund Reference
                        </p>
                        <p class="mt-1 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            {{ refundTransactionId || 'REF-AUTOMATED' }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Vehicle Return Inspection & Deposit Settlement Receipt (Phase 9a) -->
            <section
                v-if="props.reservation.inspection"
                class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#0A101D]"
            >
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-center dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                            :class="props.reservation.inspection.settlement_status === 'refunded_full'
                                ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                : props.reservation.inspection.settlement_status === 'deducted_partial'
                                    ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                    : 'bg-rose-500/15 text-rose-600 dark:text-rose-400'"
                        >
                            <ShieldCheck v-if="props.reservation.inspection.settlement_status === 'refunded_full'" class="h-6 w-6" />
                            <AlertTriangle v-else class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-950 dark:text-white">
                                    Return Inspection & Deposit Settlement
                                </h2>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                    :class="props.reservation.inspection.settlement_status === 'refunded_full'
                                        ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                        : props.reservation.inspection.settlement_status === 'deducted_partial'
                                            ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                            : 'bg-rose-500/15 text-rose-600 dark:text-rose-400'"
                                >
                                    {{ props.reservation.inspection.settlement_status === 'refunded_full'
                                        ? 'Certified Pristine'
                                        : props.reservation.inspection.settlement_status === 'deducted_partial'
                                            ? 'Deductions Applied'
                                            : 'Shortfall Logged' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Verified by Hub Inspector • Audit Ref: INS-{{ props.reservation.inspection.id }}
                            </p>
                        </div>
                    </div>

                    <!-- Deposit Settlement Quick Numbers -->
                    <div class="flex items-center gap-4 text-right">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Net Refund Credited</span>
                            <p class="font-mono text-lg font-black text-emerald-600 dark:text-emerald-400">
                                ₹{{ Number(props.reservation.inspection.deposit_refunded || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Inspection Telemetry Strip -->
                <div class="mt-4 grid gap-3 sm:grid-cols-3 text-xs">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-white/10 dark:bg-white/5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Return Odometer</span>
                        <p class="mt-0.5 font-mono font-bold text-slate-900 dark:text-white">
                            {{ Number(props.reservation.inspection.odometer || 0).toLocaleString() }} KM
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-white/10 dark:bg-white/5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Fuel Level Logged</span>
                        <p class="mt-0.5 font-semibold text-slate-900 capitalize dark:text-white">
                            {{ props.reservation.inspection.fuel_level }} Fuel Tank
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-white/10 dark:bg-white/5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Inspector Sign-off</span>
                        <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">
                            {{ props.reservation.inspection.inspector?.name || 'Operations Fleet Desk' }}
                        </p>
                    </div>
                </div>

                <!-- Deposit Reconciliation Card -->
                <div class="mt-4 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-xs">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Deposit Held</span>
                            <p class="mt-0.5 font-mono font-semibold text-slate-900 dark:text-white">
                                ₹{{ Number(props.reservation.inspection.standard_deposit || 5000).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-500">Damage Deductions</span>
                            <p class="mt-0.5 font-mono font-semibold text-rose-600 dark:text-rose-400">
                                -₹{{ Number(props.reservation.inspection.deposit_deducted || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Net Refund Credited</span>
                            <p class="mt-0.5 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                ₹{{ Number(props.reservation.inspection.deposit_refunded || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Embedded Blueprint in Readonly Mode -->
                <div class="mt-5">
                    <VehicleDamageBlueprint
                        :model-value="props.reservation.inspection.damages || []"
                        :readonly="true"
                    />
                </div>

                <!-- Audit Settlement Statement -->
                <div class="mt-4 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 text-xs text-slate-600 dark:border-white/10 dark:bg-white/[0.02] dark:text-slate-300">
                    <p class="font-bold text-slate-900 dark:text-white">Settlement Audit Record:</p>
                    <p class="mt-0.5 leading-relaxed">{{ props.reservation.inspection.settlement_notes }}</p>
                </div>
            </section>

            <!-- Verified Review Banner (if already reviewed) -->
            <section
                v-if="props.reservation.review"
                class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-sm dark:border-white/10 dark:bg-[#0A101D]"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-200/80 pb-4 dark:border-white/10"
                >
                    <div class="flex items-center gap-2">
                        <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                        <h3 class="text-sm font-bold text-slate-950 dark:text-white">
                            Your Verified Review
                        </h3>
                    </div>
                    <span
                        class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        Verified Renter
                    </span>
                </div>

                <div class="mt-4 space-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <StarRating
                            :rating="props.reservation.review.rating"
                            :readonly="true"
                            size="xs"
                        />
                        <span class="font-bold text-slate-950 dark:text-white">
                            {{ props.reservation.review.rating }} / 5
                        </span>
                    </div>
                    <p
                        v-if="props.reservation.review.title"
                        class="font-bold text-slate-950 dark:text-white"
                    >
                        {{ props.reservation.review.title }}
                    </p>
                    <p class="text-slate-600 dark:text-slate-400">
                        {{ props.reservation.review.comment }}
                    </p>
                </div>
            </section>

            <!-- 2-Column Summary Grid -->
            <div class="grid gap-6 lg:grid-cols-12">
                <!-- Left Details: Financial Statement (8 cols) -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Financial Statement -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-sm dark:border-white/10 dark:bg-[#0A101D]">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-white/5">
                            <h3 class="text-base font-bold text-slate-950 dark:text-white">
                                Financial Settlement Statement
                            </h3>
                            <span class="font-mono text-xs text-slate-400">
                                Currency: {{ props.currency.code }}
                            </span>
                        </div>

                        <div
                            class="mt-4 overflow-hidden rounded-2xl border border-slate-200/80 dark:border-white/10"
                        >
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="border-b border-slate-200/80 bg-slate-50 tracking-wider text-slate-500 uppercase dark:border-white/10 dark:bg-white/5 dark:text-slate-400"
                                >
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">
                                            Item Description
                                        </th>
                                        <th class="px-4 py-3 text-right font-semibold">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/80 dark:divide-white/10">
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-950 dark:text-white">
                                            Base Rate ({{ props.reservation.total_days }} days × {{ fmtMoney(props.reservation.daily_rate) }})
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-slate-950 dark:text-white">
                                            {{
                                                fmtMoney(
                                                    Number(props.reservation.daily_rate) *
                                                        Number(props.reservation.total_days),
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="extra in props.reservation.extras || []"
                                        :key="extra.id"
                                        class="bg-slate-50/40 dark:bg-white/5"
                                    >
                                        <td class="px-4 py-2.5 text-slate-700 dark:text-slate-300">
                                            + {{ extra.name }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-950 dark:text-white">
                                            {{ fmtMoney(extra.total) }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="Number(props.reservation.discount_amount) > 0"
                                        class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                                    >
                                        <td class="px-4 py-2.5 font-semibold">
                                            Discount Applied ({{ props.reservation.coupon_code || 'VIP REWARDS' }})
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold">
                                            -{{ fmtMoney(props.reservation.discount_amount) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400">
                                            Goods & Services Tax (GST 7%)
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-semibold text-slate-950 dark:text-white">
                                            {{ fmtMoney(props.reservation.tax_amount) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400">
                                            Refundable Security Deposit (Held on Card)
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-semibold text-slate-600 dark:text-slate-400">
                                            ₹5,000.00
                                        </td>
                                    </tr>
                                    <tr class="bg-[#101D35] text-white dark:bg-white dark:text-slate-950">
                                        <td class="px-4 py-3.5 font-bold">
                                            Total Amount Paid
                                        </td>
                                        <td class="px-4 py-3.5 text-right font-mono text-base font-black">
                                            {{ fmtMoney(props.reservation.total_amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <!-- Right Sidebar: Spec Sheet & Concierge (4 cols) -->
                <div class="space-y-6 lg:col-span-4">
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#0A101D]">
                        <h3 class="text-xs font-bold tracking-wider text-[#58779D] uppercase dark:text-[#7094BF]">
                            Vehicle Telemetry
                        </h3>

                        <div class="mt-4 space-y-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">License Plate</span>
                                <span class="font-mono font-bold text-slate-950 dark:text-white">
                                    {{ props.reservation.car?.license_plate || '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200/80 pt-2 dark:border-white/10">
                                <span class="text-slate-500 dark:text-slate-400">Powertrain</span>
                                <span class="font-semibold text-slate-950 capitalize dark:text-white">
                                    {{ props.reservation.car?.fuel_type || 'Gasoline' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200/80 pt-2 dark:border-white/10">
                                <span class="text-slate-500 dark:text-slate-400">Transmission</span>
                                <span class="font-semibold text-slate-950 capitalize dark:text-white">
                                    {{ props.reservation.car?.transmission || 'Automatic' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200/80 pt-2 dark:border-white/10">
                                <span class="text-slate-500 dark:text-slate-400">Total Seating</span>
                                <span class="font-semibold text-slate-950 dark:text-white">
                                    {{ props.reservation.car?.seats || 5 }} Occupants
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#0A101D]">
                        <div class="flex items-center gap-2 text-[#58779D] dark:text-[#7094BF]">
                            <Headphones class="h-4 w-4" />
                            <h3 class="text-xs font-bold tracking-wider uppercase">
                                24/7 Concierge Support
                            </h3>
                        </div>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                            Need to adjust pickup location, request roadside emergency assistance, or report road delays?
                        </p>
                        <Link
                            href="/support/create"
                            class="mt-4 flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50 py-2.5 text-xs font-semibold text-slate-800 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
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
            :reservation="{
                id: props.reservation.id,
                reservation_number: props.reservation.reservation_number,
                start_date: props.reservation.start_date,
                end_date: props.reservation.end_date,
                car: props.reservation.car,
            }"
            @close="isReviewModalOpen = false"
            @submitted="router.reload()"
        />

        <!-- Cancel Reservation Modal -->
        <CancelReservationModal
            :is-open="isCancelModalOpen"
            :reservation="{
                id: props.reservation.id,
                reservation_number: props.reservation.reservation_number,
                start_date: props.reservation.start_date,
                end_date: props.reservation.end_date,
                total_amount: props.reservation.total_amount,
                refund_amount: refundAmount,
                car: props.reservation.car,
            }"
            :currency-symbol="props.currency?.symbol || '₹'"
            @close="isCancelModalOpen = false"
            @cancelled="router.reload()"
        />

        <!-- Extend Rental Modal -->
        <ExtendRentalModal
            :is-open="isExtendModalOpen"
            :reservation="props.reservation"
            :currency-symbol="props.currency?.symbol || '₹'"
            @close="isExtendModalOpen = false"
            @extended="router.reload()"
        />

        <!-- Early Return Modal -->
        <EarlyReturnModal
            :is-open="isEarlyReturnModalOpen"
            :reservation="props.reservation"
            :currency-symbol="props.currency?.symbol || '₹'"
            @close="isEarlyReturnModalOpen = false"
            @returned="router.reload()"
        />
    </ClientLayout>
</template>
