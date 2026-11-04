<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Car,
    Clock,
    CreditCard,
    DollarSign,
    ExternalLink,
    MapPin,
    Pencil,
    Printer,
    ShieldAlert,
    User,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { edit, index, print } from '@/routes/admin/reservations';

const props = defineProps<{
    reservation: any;
    statusMeta: Array<{ value: string; label: string; color: string }>;
    paymentStatusMeta: Array<{ value: string; label: string }>;
    currency: { symbol: string; code: string };
}>();

const statusMap = computed(() => {
    const map: Record<string, { label: string; color: string }> = {};
    for (const status of props.statusMeta || []) {
        map[status.value] = { label: status.label, color: status.color };
    }
    return map;
});

function getStatusStyle(status: string) {
    const meta = statusMap.value[status];
    if (!meta) {
        return {
            bg: 'rgba(148, 163, 184, 0.12)',
            text: '#64748B',
            dot: '#64748B',
            label: status || 'Unknown',
        };
    }

    return {
        bg: `${meta.color}18`,
        text: meta.color,
        dot: meta.color,
        label: meta.label,
    };
}

function formatDate(date?: string) {
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

function formatDateTime(date?: string) {
    if (!date) return '—';
    const parsed = new Date(date);

    return Number.isNaN(parsed.getTime())
        ? date
        : new Intl.DateTimeFormat('en-US', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(parsed);
}

function formatMoney(amount?: number | string) {
    const value = Number(amount ?? 0);
    const formatted = Number.isFinite(value)
        ? value.toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
          })
        : '0.00';

    return `${props.currency.symbol}${formatted}`;
}
</script>

<template>
    <Head :title="`Reservation ${reservation?.reservation_number || ''}`" />

    <AdminLayout>
        <main class="min-h-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <section class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                            Reservation {{ reservation?.reservation_number }}
                        </h1>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :style="{
                                backgroundColor: getStatusStyle(reservation.status).bg,
                                color: getStatusStyle(reservation.status).text,
                            }"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: getStatusStyle(reservation.status).dot }"></span>
                            {{ getStatusStyle(reservation.status).label }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Detailed contract parameters, billing breakdown, and fulfillment schedule.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="index().url"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-black/10 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 transition hover:bg-slate-200/60 dark:hover:bg-white/10"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back
                    </Link>

                    <Link
                        :href="edit(reservation.id).url"
                        class="apple-btn-primary inline-flex items-center gap-1.5 h-10 px-4 text-xs font-medium"
                    >
                        <Pencil class="h-3.5 w-3.5" />
                        Edit Reservation
                    </Link>

                    <a
                        :href="print(reservation.id).url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-black/10 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 transition hover:bg-slate-200/60 dark:hover:bg-white/10"
                    >
                        <Printer class="h-3.5 w-3.5" />
                        Print Voucher
                    </a>
                </div>
            </section>

            <!-- Actions and total summary -->
            <section class="glass-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Total Contract Value
                    </p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        {{ formatMoney(reservation.total_amount) }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ currency.code }} · Guaranteed Rate</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="rounded-xl border border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-2.5 text-center">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Duration</p>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ reservation.total_days ?? 0 }} Days</p>
                    </div>
                    <div class="rounded-xl border border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-2.5 text-center">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Booking ID</p>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">#{{ reservation.id }}</p>
                    </div>
                </div>
            </section>

            <!-- Client and vehicle -->
            <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="glass-card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-black/5 dark:border-white/10 px-6 py-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <User class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900 dark:text-white">Client Information</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Primary driver registered on file</p>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Full Name</p>
                            <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">{{ reservation.user?.name || 'Walk-in Guest' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Email Address</p>
                            <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-300">{{ reservation.user?.email || '—' }}</p>
                        </div>
                        <div v-if="reservation.user?.id">
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Client ID</p>
                            <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-300">#{{ reservation.user.id }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-black/5 dark:border-white/10 px-6 py-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <Car class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900 dark:text-white">Vehicle Information</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Allocated fleet vehicle profile</p>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Vehicle</p>
                            <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">
                                {{ reservation.car ? `${reservation.car.year} ${reservation.car.make} ${reservation.car.model}` : 'Vehicle unassigned' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">License Plate</p>
                            <p class="mt-1 inline-flex rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-1 font-mono text-xs font-semibold text-slate-800 dark:text-slate-200">
                                {{ reservation.car?.license_plate || '—' }}
                            </p>
                        </div>
                        <div v-if="reservation.car?.id">
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Vehicle ID</p>
                            <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-300">#{{ reservation.car.id }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Rental details -->
            <section class="glass-card overflow-hidden">
                <div class="border-b border-black/5 dark:border-white/10 px-6 py-4">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Rental Itinerary</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pickup and return stations, timing schedules, and notes.</p>
                </div>

                <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.02] p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Pickup Schedule</p>
                        <p class="mt-1.5 font-semibold text-slate-900 dark:text-white">{{ formatDate(reservation.start_date) }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ reservation.pickup_time || '09:00' }}</p>
                    </div>

                    <div class="rounded-xl border border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.02] p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Return Schedule</p>
                        <p class="mt-1.5 font-semibold text-slate-900 dark:text-white">{{ formatDate(reservation.end_date) }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ reservation.return_time || '18:00' }}</p>
                    </div>

                    <div class="rounded-xl border border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.02] p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Duration</p>
                        <p class="mt-1.5 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ reservation.total_days ?? 0 }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Calendar Days</p>
                    </div>

                    <div class="rounded-xl border border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.02] p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Pickup Station</p>
                        <p class="mt-1.5 font-semibold text-slate-900 dark:text-white break-words">{{ reservation.pickup_location || 'Main Office' }}</p>
                    </div>

                    <div class="rounded-xl border border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.02] p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Return Station</p>
                        <p class="mt-1.5 font-semibold text-slate-900 dark:text-white break-words">{{ reservation.return_location || 'Main Office' }}</p>
                    </div>

                    <div v-if="reservation.status === 'cancelled'" class="rounded-xl border border-rose-500/20 bg-rose-500/10 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-rose-900 dark:text-rose-300">Cancellation Record</p>
                        <p class="mt-1.5 text-xs font-semibold text-rose-800 dark:text-rose-200">{{ formatDateTime(reservation.cancelled_at) }}</p>
                        <p class="mt-2 text-xs font-medium text-rose-900 dark:text-rose-300">Reason</p>
                        <p class="mt-0.5 text-xs text-rose-800 dark:text-rose-200 break-words">{{ reservation.cancellation_reason || 'No reason documented' }}</p>
                    </div>
                </div>
            </section>

            <!-- Price breakdown -->
            <section class="glass-card overflow-hidden">
                <div class="border-b border-black/5 dark:border-white/10 px-6 py-4">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Price Statement</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Itemized contract line items and fees.</p>
                </div>

                <div class="space-y-3 p-6">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600 dark:text-slate-400">Daily Base Rate</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ formatMoney(reservation.daily_rate) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600 dark:text-slate-400">Subtotal ({{ reservation.total_days }} Days)</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ formatMoney(reservation.subtotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600 dark:text-slate-400">Taxes & Logistics Fees</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ formatMoney(reservation.tax_amount) }}</span>
                    </div>
                    <div v-if="Number(reservation.discount_amount) > 0" class="flex items-center justify-between text-sm">
                        <span class="text-emerald-600 dark:text-emerald-400">Applied Discount</span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">−{{ formatMoney(reservation.discount_amount) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-black/5 dark:border-white/10 pt-4">
                        <div>
                            <p class="text-base font-bold text-slate-900 dark:text-white">Total Amount Due</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ currency.code }} · Net Billed</p>
                        </div>
                        <p class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ formatMoney(reservation.total_amount) }}</p>
                    </div>
                </div>
            </section>

            <!-- Payments -->
            <section class="glass-card overflow-hidden">
                <div class="border-b border-black/5 dark:border-white/10 px-6 py-4">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Payment Ledger</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Transactions associated with this booking.</p>
                </div>

                <div v-if="!reservation.payments || reservation.payments.length === 0" class="px-6 py-12 text-center">
                    <CreditCard class="mx-auto h-10 w-10 text-slate-400" />
                    <h3 class="mt-3 font-semibold text-slate-900 dark:text-white">No payments recorded</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Payment receipts will display here once captured.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left">
                        <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-6 py-3.5">Payment</th>
                                <th class="px-6 py-3.5">Amount</th>
                                <th class="px-6 py-3.5">Method</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Processed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5 text-sm">
                            <tr v-for="payment in reservation.payments" :key="payment.id" class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]">
                                <td class="px-6 py-4 font-mono font-medium text-slate-900 dark:text-white">
                                    {{ payment.payment_number || `Payment #${payment.id}` }}
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ formatMoney(payment.amount) }}
                                </td>
                                <td class="px-6 py-4 capitalize text-slate-700 dark:text-slate-300">
                                    {{ payment.payment_method || '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-0.5 text-xs font-semibold capitalize text-slate-700 dark:text-slate-300">
                                        {{ payment.status || 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatDateTime(payment.processed_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </AdminLayout>
</template>
