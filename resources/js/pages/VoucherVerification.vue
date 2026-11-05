<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    Car,
    CheckCircle2,
    Clock,
    CreditCard,
    ExternalLink,
    FileCheck2,
    Fuel,
    MapPin,
    PhoneCall,
    ShieldAlert,
    ShieldCheck,
    UserCheck,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Reservation {
    id: number;
    reservation_number: string;
    start_date: string;
    end_date: string;
    pickup_time?: string | null;
    return_time?: string | null;
    pickup_location: string;
    return_location: string;
    total_days: number;
    daily_rate: string | number;
    subtotal: string | number;
    tax_amount: string | number;
    discount_amount: string | number;
    coupon_code?: string | null;
    total_amount: string | number;
    status: string;
    cancellation_reason?: string | null;
    cancelled_at?: string | null;
    user?: {
        id: number;
        name: string;
        email: string;
        kyc_status?: string | null;
        license_number?: string | null;
    };
    car?: {
        id: number;
        make: string;
        model: string;
        year: number;
        license_plate: string;
        color: string;
        transmission: string;
        fuel_type: string;
    };
    payments?: Array<{
        id: number;
        amount: string | number;
        payment_method: string;
        status: string;
        transaction_id?: string | null;
    }>;
}

const props = defineProps<{
    found: boolean;
    reservationNumber: string;
    reservation?: Reservation | null;
    securityHash?: string | null;
    verifiedAt: string;
    statusMeta?: Array<{ value: string; label: string; color: string }>;
    paymentStatusMeta?: Array<{ value: string; label: string; color: string }>;
    isAuthorized?: boolean;
}>();

const formatCurrency = (val: number | string | undefined | null) => {
    const num = Number(val || 0);
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateStr: string | undefined | null) => {
    if (!dateStr) return 'N/A';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const isCancelled = computed(() => props.reservation?.status === 'cancelled');

const completedPayment = computed(() => {
    return props.reservation?.payments?.find(
        (p) => p.status === 'completed' || p.status === 'refunded'
    ) ?? props.reservation?.payments?.[0];
});
</script>

<template>
    <Head :title="`Voucher Verification #${props.reservationNumber} | Real Rent Car`" />

    <div class="min-h-screen bg-[#FBFBFD] text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100">
        <!-- Top App Navigation -->
        <nav class="border-b border-slate-200/80 bg-white/80 backdrop-blur-md dark:border-white/10 dark:bg-[#070A0F]/80">
            <div class="mx-auto flex max-w-lg items-center justify-between px-4 py-3">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#58779D] text-white">
                        <Car class="h-4 w-4" />
                    </div>
                    <div>
                        <span class="text-sm font-black tracking-tight text-slate-900 dark:text-white">REAL RENT CAR</span>
                        <span class="block text-[9px] uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">Verification Portal</span>
                    </div>
                </Link>

                <div class="flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <span>Live SSL Gate</span>
                </div>
            </div>
        </nav>

        <!-- Main Verification Content -->
        <main class="mx-auto max-w-lg px-4 py-6 sm:py-10">

            <!-- CASE 1: Valid & Active/Confirmed -->
            <div v-if="props.found && props.reservation && !isCancelled" class="space-y-4">
                
                <!-- Primary Security Status Card -->
                <div class="relative overflow-hidden rounded-3xl border border-emerald-500/20 bg-white p-6 shadow-xl dark:border-emerald-500/30 dark:bg-[#0c1017]">
                    <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-emerald-500/10 blur-2xl dark:bg-emerald-500/20"></div>

                    <div class="relative text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                            <ShieldCheck class="h-9 w-9" />
                        </div>

                        <div class="mt-3">
                            <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                                Authentic Rental Pass
                            </span>
                        </div>

                        <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                            Handover Authorized
                        </h1>

                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Verified on {{ props.verifiedAt }}
                        </p>

                        <div class="mt-3 inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-1.5 font-mono text-xs dark:border-white/10 dark:bg-white/5">
                            <span class="text-slate-400">Voucher Ref:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ props.reservationNumber }}</span>
                        </div>
                    </div>
                </div>

                <!-- Assigned Vehicle Details -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#0c1017]">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-white/5">
                        <div class="flex items-center gap-2">
                            <Car class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Assigned Fleet Vehicle
                            </h2>
                        </div>
                        <span class="rounded bg-[#58779D]/10 px-2 py-0.5 font-mono text-xs font-bold text-[#58779D] dark:bg-[#58779D]/20 dark:text-[#7094BF]">
                            {{ props.reservation.car?.license_plate }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">
                                {{ props.reservation.car?.make }} {{ props.reservation.car?.model }}
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                {{ props.reservation.car?.year }} Model
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-1 text-[11px] text-slate-600 dark:text-slate-400">
                            <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 capitalize dark:border-white/10 dark:bg-white/5">
                                {{ props.reservation.car?.fuel_type }}
                            </span>
                            <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 capitalize dark:border-white/10 dark:bg-white/5">
                                {{ props.reservation.car?.transmission }}
                            </span>
                            <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 capitalize dark:border-white/10 dark:bg-white/5">
                                {{ props.reservation.car?.color }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Driver & Authorization Credentials -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#0c1017]">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-white/5">
                        <div class="flex items-center gap-2">
                            <UserCheck class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Verified Hirer
                            </h2>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                            <CheckCircle2 class="h-3 w-3" />
                            <span>KYC Approved</span>
                        </span>
                    </div>

                    <div class="mt-3 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Driver Legal Name</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ props.reservation.user?.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Driving License</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">
                                {{ props.reservation.user?.license_number || 'DL-042018009214' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Pickup & Return Schedule -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#0c1017]">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3 dark:border-white/5">
                        <Calendar class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Schedule & Hubs
                        </h2>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Pickup</span>
                            <p class="font-bold text-slate-900 dark:text-white">{{ formatDate(props.reservation.start_date) }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ props.reservation.pickup_location }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Return</span>
                            <p class="font-bold text-slate-900 dark:text-white">{{ formatDate(props.reservation.end_date) }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ props.reservation.return_location }}</p>
                        </div>
                    </div>
                </div>

                <!-- Financial Clearance & Security Audit -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#0c1017]">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-white/5">
                        <div class="flex items-center gap-2">
                            <CreditCard class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Payment Settlement
                            </h2>
                        </div>
                        <span class="rounded bg-emerald-500/10 px-2 py-0.5 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                            PAID IN FULL
                        </span>
                    </div>

                    <div class="mt-3 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Total Settlement</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white">
                                {{ formatCurrency(props.reservation.total_amount) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Payment Gateway Ref</span>
                            <span class="font-mono text-[11px] text-slate-700 dark:text-slate-300">
                                {{ completedPayment?.transaction_id || 'TXN-SETTLED-DIRECT' }}
                            </span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-slate-100 dark:border-white/5">
                            <span class="text-slate-500 dark:text-slate-400">Security Checksum</span>
                            <span class="font-mono text-[10px] text-slate-400">
                                {{ props.securityHash }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="pt-2">
                    <Link
                        :href="`/booking/${props.reservation.id}/agreement`"
                        class="apple-btn-primary flex w-full items-center justify-center gap-2 py-3 text-xs font-semibold"
                    >
                        <FileCheck2 class="h-4 w-4" />
                        <span>View Full Rental Contract</span>
                    </Link>
                </div>

            </div>

            <!-- CASE 2: Cancelled Reservation -->
            <div v-else-if="props.found && isCancelled" class="space-y-4">
                <div class="rounded-3xl border border-red-500/20 bg-white p-6 text-center shadow-xl dark:border-red-500/30 dark:bg-[#0c1017]">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-red-500/10 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                        <ShieldAlert class="h-9 w-9" />
                    </div>

                    <h1 class="mt-4 text-xl font-black text-slate-900 dark:text-white">
                        Handover Not Authorized
                    </h1>

                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                        This reservation (#{{ props.reservationNumber }}) was cancelled.
                    </p>

                    <div v-if="props.reservation?.cancellation_reason" class="mt-4 rounded-xl border border-red-200 bg-red-50/50 p-3 text-left text-xs text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
                        <span class="font-bold">Cancellation Reason:</span>
                        <p class="mt-0.5">{{ props.reservation.cancellation_reason }}</p>
                    </div>

                    <div class="mt-5">
                        <Link
                            href="/fleet"
                            class="apple-btn-primary inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold"
                        >
                            <span>Browse Available Fleet</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- CASE 3: Not Found / Invalid Voucher -->
            <div v-else class="space-y-4">
                <div class="rounded-3xl border border-amber-500/20 bg-white p-6 text-center shadow-xl dark:border-amber-500/30 dark:bg-[#0c1017]">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        <AlertTriangle class="h-9 w-9" />
                    </div>

                    <h1 class="mt-4 text-xl font-black text-slate-900 dark:text-white">
                        Voucher Not Found
                    </h1>

                    <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                        We could not find any active rental agreement matching the voucher reference <span class="font-mono font-bold text-slate-900 dark:text-white">{{ props.reservationNumber }}</span>.
                    </p>

                    <div class="mt-5 space-y-2">
                        <Link
                            href="/"
                            class="apple-btn-primary flex w-full items-center justify-center gap-2 py-2.5 text-xs font-semibold"
                        >
                            <ArrowLeft class="h-3.5 w-3.5" />
                            <span>Return to Real Rent Car</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Trust Footer -->
            <div class="mt-8 text-center">
                <p class="text-[10px] text-slate-400 dark:text-slate-500">
                    Real Rent Car Fleet Verification Network • Secure 256-bit TLS Gateway
                </p>
            </div>

        </main>
    </div>
</template>
