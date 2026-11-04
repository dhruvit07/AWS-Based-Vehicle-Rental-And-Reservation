<script setup lang="ts">
import HomeLayout from '@/layouts/HomeLayout.vue';
import { fleet } from '@/routes';
import { index } from '@/routes/client/reservations';
import { usePage } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle2,
    CreditCard,
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
    };
}

interface PageProps {
    reservation: Reservation;
}

const page = usePage<PageProps>();
const reservation = computed(() => page.props.reservation);
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

const formatPrice = (amount: string | number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(amount) || 0);

const formatPaymentMethod = (method?: string) => {
    if (!method) return 'Credit Card';
    switch (method.toLowerCase()) {
        case 'credit_card':
            return 'Credit / Debit Card';
        case 'upi':
            return 'UPI / QR Code';
        case 'cash':
            return 'Cash on Pickup';
        default:
            return method.replace('_', ' ').toUpperCase();
    }
};

const statusClass = computed(() => {
    const status = reservation.value.status.toLowerCase();

    if (status === 'confirmed' || status === 'approved') {
        return 'bg-emerald-100 text-emerald-800';
    }

    if (status === 'cancelled' || status === 'rejected') {
        return 'bg-rose-100 text-rose-800';
    }

    return 'bg-amber-100 text-amber-800';
});

const printVoucher = () => {
    window.print();
};
</script>

<template>
    <HomeLayout>
        <div class="min-h-screen bg-[#F4F6FA] pb-16 print:bg-white print:pb-0">
            <!-- Confirmation banner -->
            <section class="relative overflow-hidden bg-[#101D35] px-4 py-12 text-white sm:py-16 print:bg-transparent print:p-0 print:text-[#101D35]">
                <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full border border-white/10 print:hidden"></div>
                <div class="pointer-events-none absolute -right-10 -top-14 h-52 w-52 rounded-full border border-white/10 print:hidden"></div>

                <div class="relative mx-auto max-w-5xl text-center print:text-left">
                    <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full border border-white/20 bg-white/10 print:hidden">
                        <CheckCircle2 class="h-10 w-10 text-emerald-400" />
                    </div>

                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.25em] text-[#C5D0DE] print:text-[#58779D]">
                        Booking Confirmation & Receipt
                    </p>

                    <h1 class="text-3xl font-bold sm:text-4xl print:text-2xl">
                        Reservation Confirmed
                    </h1>

                    <p class="mx-auto mt-3 max-w-2xl leading-7 text-slate-300 print:text-slate-600">
                        Thank you, {{ reservation.user.name }}. Your vehicle is booked and your payment record has been generated.
                    </p>

                    <div class="mx-auto mt-6 inline-flex flex-wrap items-center justify-center gap-3 rounded-xl border border-white/15 bg-white/10 px-5 py-3 print:border-slate-300 print:bg-slate-50 print:text-[#101D35]">
                        <span class="text-xs uppercase tracking-wider text-slate-300 print:text-slate-600">Reference:</span>
                        <span class="font-mono text-base font-bold tracking-wider text-white print:text-[#101D35]">
                            {{ reservation.reservation_number }}
                        </span>
                    </div>
                </div>
            </section>

            <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <div class="grid items-start gap-7 lg:grid-cols-3">
                    <!-- Main details -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Vehicle card -->
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-7">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-[#58779D]">
                                        Reserved vehicle
                                    </p>
                                    <h2 class="mt-0.5 text-xl font-bold text-[#101D35]">
                                        {{ reservation.car.make }} {{ reservation.car.model }}
                                    </h2>
                                </div>
                                <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-[#101D35]">
                                    {{ reservation.car.year }}
                                </span>
                            </div>

                            <div class="grid sm:grid-cols-5">
                                <div class="h-52 bg-slate-100 sm:col-span-2 sm:h-full">
                                    <img
                                        v-if="reservation.car.image_url"
                                        :src="reservation.car.image_url"
                                        :alt="`${reservation.car.make} ${reservation.car.model}`"
                                        class="h-full min-h-52 w-full object-cover"
                                    />
                                    <div v-else class="flex h-full min-h-52 items-center justify-center text-sm text-slate-500">
                                        Vehicle image unavailable
                                    </div>
                                </div>

                                <div class="p-5 sm:col-span-3 sm:p-7">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700">
                                            {{ reservation.car.fuel_type }}
                                        </span>
                                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ reservation.total_days }} day{{ reservation.total_days === 1 ? '' : 's' }}
                                        </span>
                                    </div>

                                    <p class="mt-4 text-sm leading-6 text-slate-600">
                                        {{ reservation.car.description || 'Full comprehensive verification completed.' }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Rental dates & stations -->
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-7">
                            <div class="mb-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.15em] text-[#58779D]">
                                    Schedule & Logistics
                                </p>
                                <h2 class="mt-0.5 text-xl font-bold text-[#101D35]">
                                    Pickup & Return Information
                                </h2>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div class="rounded-xl bg-[#F4F6FA] p-5">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#101D35] text-white">
                                            <Calendar class="h-4 w-4" />
                                        </div>
                                        <h3 class="font-bold text-[#101D35]">Rental Schedule</h3>
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-xs text-slate-500">Pickup date</p>
                                            <p class="mt-0.5 text-sm font-bold text-[#101D35]">
                                                {{ formatDate(reservation.start_date) }}
                                            </p>
                                        </div>
                                        <div class="border-t border-slate-200 pt-3">
                                            <p class="text-xs text-slate-500">Return date</p>
                                            <p class="mt-0.5 text-sm font-bold text-[#101D35]">
                                                {{ formatDate(reservation.end_date) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-xl bg-[#F4F6FA] p-5">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#101D35] text-white">
                                            <MapPin class="h-4 w-4" />
                                        </div>
                                        <h3 class="font-bold text-[#101D35]">Station Locations</h3>
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <p class="text-xs text-slate-500">Pickup station</p>
                                            <p class="mt-0.5 text-sm font-bold text-[#101D35]">
                                                {{ reservation.pickup_location }}
                                            </p>
                                        </div>
                                        <div class="border-t border-slate-200 pt-3">
                                            <p class="text-xs text-slate-500">Return station</p>
                                            <p class="mt-0.5 text-sm font-bold text-[#101D35]">
                                                {{ reservation.return_location }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Full Itemized Invoice Table -->
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-7">
                            <div class="mb-5 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-[#58779D]">
                                        Billing & Breakdown
                                    </p>
                                    <h2 class="mt-0.5 text-xl font-bold text-[#101D35]">
                                        Itemized Statement
                                    </h2>
                                </div>

                                <button
                                    type="button"
                                    @click="printVoucher"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-[#101D35] shadow-2xs transition hover:bg-slate-50 print:hidden"
                                >
                                    <Printer class="h-4 w-4 text-[#58779D]" />
                                    <span>Print Voucher</span>
                                </button>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-600">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Description</th>
                                            <th class="px-4 py-3 text-right font-semibold">Rate</th>
                                            <th class="px-4 py-3 text-right font-semibold">Qty / Days</th>
                                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <!-- Base Car -->
                                        <tr>
                                            <td class="px-4 py-3.5">
                                                <p class="font-bold text-[#101D35]">
                                                    {{ reservation.car.make }} {{ reservation.car.model }} (Base Rental)
                                                </p>
                                                <p class="text-xs text-slate-500">Standard vehicle reservation fee</p>
                                            </td>
                                            <td class="px-4 py-3.5 text-right font-mono">
                                                {{ formatPrice(reservation.daily_rate) }}/day
                                            </td>
                                            <td class="px-4 py-3.5 text-right font-mono">
                                                {{ reservation.total_days }} day{{ reservation.total_days === 1 ? '' : 's' }}
                                            </td>
                                            <td class="px-4 py-3.5 text-right font-mono font-bold text-[#101D35]">
                                                {{ formatPrice(Number(reservation.daily_rate) * reservation.total_days) }}
                                            </td>
                                        </tr>

                                        <!-- Selected Extras -->
                                        <tr
                                            v-for="extra in reservation.extras || []"
                                            :key="extra.id"
                                            class="bg-slate-50/40"
                                        >
                                            <td class="px-4 py-3.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-[#101D35] text-[10px] text-white">
                                                        +
                                                    </span>
                                                    <div>
                                                        <p class="font-bold text-[#101D35]">{{ extra.name }}</p>
                                                        <p class="text-xs text-slate-500">{{ extra.type === 'per_day' ? 'Daily coverage / equipment' : 'Fixed trip service' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 text-right font-mono text-xs">
                                                {{ extra.type === 'per_day' ? `${formatPrice(extra.rate)}/day` : 'Flat' }}
                                            </td>
                                            <td class="px-4 py-3.5 text-right font-mono text-xs">
                                                {{ extra.type === 'per_day' ? `${extra.days || reservation.total_days} days` : '1' }}
                                            </td>
                                            <td class="px-4 py-3.5 text-right font-mono font-bold text-[#101D35]">
                                                {{ formatPrice(extra.total) }}
                                            </td>
                                        </tr>

                                        <!-- Subtotal row -->
                                        <tr class="bg-slate-50 font-medium">
                                            <td colspan="3" class="px-4 py-3 text-right text-slate-600">
                                                Subtotal
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-[#101D35]">
                                                {{ formatPrice(reservation.subtotal) }}
                                            </td>
                                        </tr>

                                        <!-- Promo Discount row (if any) -->
                                        <tr v-if="Number(reservation.discount_amount) > 0" class="bg-emerald-50/60 text-emerald-900">
                                            <td colspan="3" class="px-4 py-3 text-right font-semibold">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <Tag class="h-3.5 w-3.5" />
                                                    Promo Discount ({{ reservation.coupon_code || 'APPLIED' }})
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-emerald-900">
                                                -{{ formatPrice(reservation.discount_amount) }}
                                            </td>
                                        </tr>

                                        <!-- GST / Tax row -->
                                        <tr>
                                            <td colspan="3" class="px-4 py-3 text-right text-slate-600">
                                                Goods & Services Tax (7%)
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-semibold text-slate-800">
                                                {{ formatPrice(reservation.tax_amount) }}
                                            </td>
                                        </tr>

                                        <!-- Grand Total row -->
                                        <tr class="border-t-2 border-slate-300 bg-[#101D35] text-white">
                                            <td colspan="3" class="px-4 py-4 text-right text-base font-bold">
                                                Total Paid
                                            </td>
                                            <td class="px-4 py-4 text-right font-mono text-xl font-black">
                                                {{ formatPrice(reservation.total_amount) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <!-- Sidebar -->
                    <aside class="space-y-6 lg:sticky lg:top-6">
                        <!-- Payment details summary -->
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                            <div class="bg-[#101D35] p-6 text-white">
                                <p class="text-xs uppercase tracking-wider text-slate-300">Transaction Status</p>
                                <h2 class="mt-1 text-2xl font-bold">Payment Summary</h2>
                            </div>

                            <div class="space-y-4 p-6">
                                <div>
                                    <p class="text-xs text-slate-500">Booking Status</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide" :class="statusClass">
                                            {{ reservation.status }}
                                        </span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200 pt-4">
                                    <p class="text-xs text-slate-500">Payment Status</p>
                                    <div class="mt-1 flex items-center gap-1.5 text-sm font-bold text-emerald-700">
                                        <ShieldCheck class="h-4 w-4" />
                                        <span>
                                            {{ latestPayment?.status ? latestPayment.status.toUpperCase() : 'COMPLETED' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200 pt-4">
                                    <p class="text-xs text-slate-500">Payment Method</p>
                                    <div class="mt-1 flex items-center gap-2 text-sm font-semibold text-[#101D35]">
                                        <CreditCard class="h-4 w-4 text-[#58779D]" />
                                        <span>{{ formatPaymentMethod(latestPayment?.payment_method) }}</span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200 pt-4">
                                    <p class="text-xs text-slate-500">Transaction Reference</p>
                                    <p class="mt-1 font-mono text-xs font-bold text-[#101D35]">
                                        {{ latestPayment?.transaction_id || reservation.reservation_number }}
                                    </p>
                                </div>

                                <div class="border-t border-slate-200 pt-4">
                                    <p class="text-xs text-slate-500">Grand Total Paid</p>
                                    <p class="mt-1 font-mono text-2xl font-bold text-[#101D35]">
                                        {{ formatPrice(reservation.total_amount) }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Customer Details -->
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-[#58779D]">
                                Customer Details
                            </h3>
                            <div class="mt-3 space-y-2 text-sm">
                                <div>
                                    <span class="text-xs text-slate-500">Name</span>
                                    <p class="font-semibold text-[#101D35]">{{ reservation.user.name }}</p>
                                </div>
                                <div class="border-t border-slate-100 pt-2">
                                    <span class="text-xs text-slate-500">Email</span>
                                    <p class="break-all font-semibold text-[#101D35]">{{ reservation.user.email }}</p>
                                </div>
                            </div>
                        </section>

                        <!-- Next Steps & Navigation -->
                        <div class="space-y-3 print:hidden">
                            <a
                                :href="index.url()"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D35] px-5 py-3.5 text-center font-bold text-white transition hover:bg-[#263F60]"
                            >
                                <FileText class="h-4 w-4" />
                                <span>View My Bookings</span>
                            </a>

                            <a
                                :href="fleet.url()"
                                class="flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-center font-bold text-[#101D35] transition hover:bg-slate-100"
                            >
                                Browse More Cars
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </HomeLayout>
</template>
