<script setup lang="ts">
import HomeLayout from '@/layouts/HomeLayout.vue';
import { fleet } from '@/routes';
import { index } from '@/routes/client/reservations';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle2,
    CreditCard,
    FileCheck2,
    FileText,
    MapPin,
    Printer,
    ShieldCheck,
    Tag,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface ExtraItem {
    id: string;
    name: string;
    rate: number;
    type: 'per_day' | 'fixed';
    days?: number | null;
    total: number;
}

interface PaymentRecord {
    id: number;
    payment_number: string;
    amount: string | number;
    currency: string;
    payment_method: string;
    status: string;
    transaction_id?: string | null;
    processed_at?: string | null;
}

interface Reservation {
    id: number;
    reservation_number: string;
    start_date: string;
    end_date: string;
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
    extras?: ExtraItem[] | null;
    payments?: PaymentRecord[];
    created_at: string;
    car: {
        make: string;
        model: string;
        year: number;
        image_url: string;
        description: string;
        fuel_type: string;
    };
    user: {
        name: string;
        email: string;
        kyc_status?: string | null;
    };
}

interface PageProps {
    reservation: Reservation;
    [key: string]: any;
}

const page = usePage<PageProps>();
const reservation = computed(() => page.props.reservation);
const isVerifiedDriver = computed(() => {
    return (
        reservation.value?.user?.kyc_status === 'verified' ||
        (page.props as any).auth?.user?.kyc_status === 'verified'
    );
});
const latestPayment = computed(() => {
    if (reservation.value.payments && reservation.value.payments.length > 0) {
        return reservation.value.payments[0];
    }
    return null;
});

const formatDate = (date: string) => {
    if (!date) return '—';
    const parsed = new Date(`${date.slice(0, 10)}T00:00:00`);

    if (Number.isNaN(parsed.getTime())) return date;

    return new Intl.DateTimeFormat('en-IN', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(parsed);
};

const formatPrice = (amount: number | string) => {
    const val = Number(amount) || 0;
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(val);
};

const formatPaymentMethod = (method?: string | null) => {
    if (!method) return 'Card / Online';
    if (method === 'credit_card') return 'Credit / Debit Card';
    if (method === 'upi') return 'UPI / Instant QR';
    if (method === 'cash') return 'Pay at Pickup (Cash / Card POS)';
    return method;
};

const statusClass = computed(() => {
    switch (reservation.value.status) {
        case 'confirmed':
        case 'active':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
        case 'pending':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
        case 'completed':
            return 'bg-[#58779D]/15 text-[#58779D] dark:text-[#7094BF] border border-[#58779D]/20';
        default:
            return 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300';
    }
});

const printVoucher = () => {
    window.print();
};
</script>

<template>
    <Head title="Booking Confirmation | Real Rent Car" />

    <HomeLayout>
        <div class="min-h-screen bg-[#FBFBFD] pb-20 text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100">

            <!-- Hero Staging -->
            <section class="relative isolate overflow-hidden bg-[#101D35] px-5 py-14 text-white dark:bg-[#070A0F] sm:py-20 print:bg-white print:p-0 print:text-slate-950">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(120,160,210,0.25),rgba(255,255,255,0))] print:hidden"></div>

                <div class="relative mx-auto max-w-4xl text-center print:text-left">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-md print:hidden">
                        <CheckCircle2 class="h-8 w-8 text-emerald-400" />
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl print:text-2xl">
                        Reservation confirmed.
                    </h1>

                    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-slate-300 print:text-slate-600">
                        Thank you, {{ reservation.user.name }}. Your reservation voucher has been created and your vehicle is secured.
                    </p>

                    <div class="mt-6 inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2 backdrop-blur-md print:border-slate-300 print:bg-slate-100 print:text-slate-950">
                        <span class="text-xs uppercase tracking-wider text-slate-300 print:text-slate-600">Confirmation Number:</span>
                        <span class="font-mono text-sm font-bold text-white print:text-slate-950">
                            {{ reservation.reservation_number }}
                        </span>
                    </div>
                </div>
            </section>

            <div class="mx-auto max-w-7xl px-5 pt-8 sm:px-8">
                <div class="grid items-start gap-8 lg:grid-cols-12">

                    <!-- Left Main Details Column -->
                    <div class="space-y-6 lg:col-span-8">

                        <!-- Vehicle Summary Card -->
                        <section class="glass-card overflow-hidden">
                            <div class="flex items-center justify-between border-b border-slate-200/80 p-5 dark:border-white/10 sm:px-6">
                                <h2 class="text-lg font-bold text-slate-950 dark:text-white">
                                    {{ reservation.car.make }} {{ reservation.car.model }}
                                </h2>
                                <span class="rounded-lg border border-slate-200/80 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-800 dark:border-white/10 dark:bg-white/10 dark:text-slate-200">
                                    {{ reservation.car.year }}
                                </span>
                            </div>

                            <div class="grid sm:grid-cols-5">
                                <div class="aspect-16/10 bg-slate-100 sm:col-span-2 sm:aspect-auto dark:bg-slate-900">
                                    <img
                                        v-if="reservation.car.image_url"
                                        :src="reservation.car.image_url"
                                        :alt="`${reservation.car.make} ${reservation.car.model}`"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full items-center justify-center text-xs text-slate-400">
                                        Vehicle image preview
                                    </div>
                                </div>

                                <div class="p-5 sm:col-span-3 sm:p-6">
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-lg border border-slate-200/80 bg-slate-100 px-2.5 py-1 font-semibold capitalize text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                            {{ reservation.car.fuel_type }}
                                        </span>
                                        <span class="rounded-lg border border-slate-200/80 bg-slate-100 px-2.5 py-1 font-semibold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                            {{ reservation.total_days }} day{{ reservation.total_days === 1 ? '' : 's' }}
                                        </span>
                                    </div>

                                    <p class="mt-4 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                        {{ reservation.car.description || 'Pre-inspected chassis, sanitized cabin, and full fuel tank ready at handover.' }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Logistics / Stations Card -->
                        <section class="glass-card p-5 sm:p-6">
                            <h3 class="text-base font-bold text-slate-950 dark:text-white">
                                Journey Itinerary
                            </h3>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                                    <div class="mb-3 flex items-center gap-2">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#101D35] text-white dark:bg-white dark:text-slate-950">
                                            <Calendar class="h-3.5 w-3.5" />
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-950 dark:text-white">Dates</h4>
                                    </div>

                                    <div class="space-y-2.5 text-xs">
                                        <div>
                                            <span class="text-slate-500 dark:text-slate-400">Pickup</span>
                                            <p class="font-bold text-slate-950 dark:text-white">{{ formatDate(reservation.start_date) }}</p>
                                        </div>
                                        <div class="border-t border-slate-200/80 pt-2 dark:border-white/10">
                                            <span class="text-slate-500 dark:text-slate-400">Return</span>
                                            <p class="font-bold text-slate-950 dark:text-white">{{ formatDate(reservation.end_date) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                                    <div class="mb-3 flex items-center gap-2">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#101D35] text-white dark:bg-white dark:text-slate-950">
                                            <MapPin class="h-3.5 w-3.5" />
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-950 dark:text-white">Stations</h4>
                                    </div>

                                    <div class="space-y-2.5 text-xs">
                                        <div>
                                            <span class="text-slate-500 dark:text-slate-400">Pickup Hub</span>
                                            <p class="font-bold text-slate-950 dark:text-white">{{ reservation.pickup_location }}</p>
                                        </div>
                                        <div class="border-t border-slate-200/80 pt-2 dark:border-white/10">
                                            <span class="text-slate-500 dark:text-slate-400">Return Hub</span>
                                            <p class="font-bold text-slate-950 dark:text-white">{{ reservation.return_location }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Itemized Invoice Table -->
                        <section class="glass-card p-5 sm:p-6">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-950 dark:text-white">
                                    Itemized Statement
                                </h3>

                                <Link
                                    :href="`/booking/${reservation.id}/agreement`"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 shadow-xs transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-white print:hidden"
                                >
                                    <FileCheck2 class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span>Agreement & Voucher</span>
                                </Link>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-slate-200/80 dark:border-white/10">
                                <table class="w-full text-left text-xs">
                                    <thead class="border-b border-slate-200/80 bg-slate-50 uppercase tracking-wider text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Description</th>
                                            <th class="px-4 py-3 text-right font-semibold">Daily Rate</th>
                                            <th class="px-4 py-3 text-right font-semibold">Duration</th>
                                            <th class="px-4 py-3 text-right font-semibold">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200/80 dark:divide-white/10">
                                        <!-- Base Vehicle -->
                                        <tr>
                                            <td class="px-4 py-3">
                                                <p class="font-bold text-slate-950 dark:text-white">{{ reservation.car.make }} {{ reservation.car.model }}</p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Standard chassis reservation</p>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono">{{ formatPrice(reservation.daily_rate) }}/day</td>
                                            <td class="px-4 py-3 text-right font-mono">{{ reservation.total_days }} day{{ reservation.total_days === 1 ? '' : 's' }}</td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-950 dark:text-white">
                                                {{ formatPrice(Number(reservation.daily_rate) * reservation.total_days) }}
                                            </td>
                                        </tr>

                                        <!-- Extras -->
                                        <tr v-for="extra in reservation.extras || []" :key="extra.id" class="bg-slate-50/40 dark:bg-white/5">
                                            <td class="px-4 py-3">
                                                <p class="font-bold text-slate-950 dark:text-white">+ {{ extra.name }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono">
                                                {{ extra.type === 'per_day' ? `${formatPrice(extra.rate)}/day` : 'Flat' }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono">
                                                {{ extra.type === 'per_day' ? `${extra.days || reservation.total_days} days` : '1' }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-950 dark:text-white">
                                                {{ formatPrice(extra.total) }}
                                            </td>
                                        </tr>

                                        <!-- Subtotal -->
                                        <tr class="bg-slate-50 dark:bg-white/5">
                                            <td colspan="3" class="px-4 py-2.5 text-right font-medium text-slate-600 dark:text-slate-400">Subtotal</td>
                                            <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-950 dark:text-white">
                                                {{ formatPrice(reservation.subtotal) }}
                                            </td>
                                        </tr>

                                        <!-- Discount -->
                                        <tr v-if="Number(reservation.discount_amount) > 0" class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            <td colspan="3" class="px-4 py-2.5 text-right font-semibold">
                                                Promo Code ({{ reservation.coupon_code || 'APPLIED' }})
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-mono font-bold">
                                                -{{ formatPrice(reservation.discount_amount) }}
                                            </td>
                                        </tr>

                                        <!-- GST -->
                                        <tr>
                                            <td colspan="3" class="px-4 py-2.5 text-right text-slate-600 dark:text-slate-400">GST (7%)</td>
                                            <td class="px-4 py-2.5 text-right font-mono font-semibold text-slate-950 dark:text-white">
                                                {{ formatPrice(reservation.tax_amount) }}
                                            </td>
                                        </tr>

                                        <!-- Total -->
                                        <tr class="bg-[#101D35] text-white dark:bg-white dark:text-slate-950">
                                            <td colspan="3" class="px-4 py-3.5 text-right text-sm font-bold">Total Paid</td>
                                            <td class="px-4 py-3.5 text-right font-mono text-base font-black">
                                                {{ formatPrice(reservation.total_amount) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <!-- Right Sidebar -->
                    <aside class="space-y-6 lg:sticky lg:top-24 lg:col-span-4">
                        <section class="glass-card overflow-hidden">
                            <div class="border-b border-black/10 bg-[#101D35] p-5 text-white dark:border-white/10 dark:bg-[#070A0F]">
                                <h3 class="text-base font-bold">Transaction Voucher</h3>
                            </div>

                            <div class="space-y-4 p-5 text-xs">
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400">Status</span>
                                    <div class="mt-1">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider" :class="statusClass">
                                            {{ reservation.status }}
                                        </span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200/80 pt-3 dark:border-white/10">
                                    <span class="text-slate-500 dark:text-slate-400">Payment Channel</span>
                                    <div class="mt-1 flex items-center gap-1.5 font-semibold text-slate-950 dark:text-white">
                                        <CreditCard class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                        <span>{{ formatPaymentMethod(latestPayment?.payment_method) }}</span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200/80 pt-3 dark:border-white/10">
                                    <span class="text-slate-500 dark:text-slate-400">Transaction ID</span>
                                    <p class="mt-0.5 font-mono text-xs font-bold text-slate-950 dark:text-white">
                                        {{ latestPayment?.transaction_id || reservation.reservation_number }}
                                    </p>
                                </div>

                                <div class="border-t border-slate-200/80 pt-3 dark:border-white/10">
                                    <span class="text-slate-500 dark:text-slate-400">Amount Settled</span>
                                    <p class="mt-0.5 font-mono text-xl font-bold text-slate-950 dark:text-white">
                                        {{ formatPrice(reservation.total_amount) }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Driver Info Card -->
                        <section class="glass-card p-5">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                                Driver Record
                            </h3>
                            <div class="mt-3 space-y-2 text-xs">
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400">Driver Name</span>
                                    <div class="mt-0.5 flex items-center gap-1.5">
                                        <p class="font-bold text-slate-950 dark:text-white">{{ reservation.user.name }}</p>
                                        <span v-if="isVerifiedDriver" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                            <ShieldCheck class="h-3 w-3" />
                                            Verified
                                        </span>
                                    </div>
                                </div>
                                <div class="border-t border-slate-200/80 pt-2 dark:border-white/10">
                                    <span class="text-slate-500 dark:text-slate-400">Contact Email</span>
                                    <p class="break-all font-semibold text-slate-950 dark:text-white">{{ reservation.user.email }}</p>
                                </div>
                            </div>
                        </section>

                        <!-- Action Buttons -->
                        <div class="space-y-2.5 print:hidden">
                            <Link
                                :href="`/booking/${reservation.id}/agreement`"
                                class="apple-btn-primary flex w-full items-center justify-center gap-2 py-3 text-xs font-semibold"
                            >
                                <FileCheck2 class="h-4 w-4" />
                                <span>Official Agreement & QR Voucher</span>
                            </Link>

                            <Link
                                :href="index.url()"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-xs font-semibold text-slate-900 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
                            >
                                <FileText class="h-4 w-4" />
                                <span>View In Client Portal</span>
                            </Link>

                            <Link
                                :href="fleet()"
                                class="flex w-full items-center justify-center rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-xs font-semibold text-slate-900 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
                            >
                                <span>Browse More Vehicles</span>
                            </Link>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </HomeLayout>
</template>
