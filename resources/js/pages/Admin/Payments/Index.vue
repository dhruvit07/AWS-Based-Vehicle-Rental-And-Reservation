<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CheckCircle2,
    CreditCard,
    ExternalLink,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';

type PaymentStatus = {
    label: string;
    count: number;
    color: string;
};

type Payment = {
    id: number;
    payment_number: string;
    amount: number | string;
    currency?: string;
    payment_method: string;
    status: string;
    processed_at?: string | null;
    user?: { id: number; name: string; email: string } | null;
    reservation?: { id: number; reservation_number: string } | null;
};

const props = defineProps<{
    payments: {
        data: Payment[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    statuses: Record<string, PaymentStatus>;
    currency: { symbol: string; code: string };
}>();

function fmtMoney(amount?: number | string) {
    const value = Number(amount ?? 0);

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

const statusColors = computed(() => {
    const colors: Record<string, { bg: string; text: string; dot: string }> = {};

    for (const [key, status] of Object.entries(props.statuses ?? {})) {
        const color = status.color || '#64748B';
        const hex = color.replace('#', '');

        if (/^[0-9a-fA-F]{6}$/.test(hex)) {
            const r = parseInt(hex.slice(0, 2), 16);
            const g = parseInt(hex.slice(2, 4), 16);
            const b = parseInt(hex.slice(4, 6), 16);

            colors[key] = {
                bg: `rgba(${r}, ${g}, ${b}, 0.12)`,
                text: color,
                dot: color,
            };
        } else {
            colors[key] = {
                bg: 'rgba(100, 116, 139, 0.12)',
                text: '#64748B',
                dot: '#64748B',
            };
        }
    }

    return colors;
});

function getStatusColor(status: string) {
    return statusColors.value[status] ?? {
        bg: 'rgba(100, 116, 139, 0.12)',
        text: '#64748B',
        dot: '#64748B',
    };
}

const totalPayments = computed(() => props.payments.data.length);

const successfulPayments = computed(() =>
    props.payments.data.filter((payment) =>
        ['completed', 'successful', 'succeeded', 'paid'].includes(
            payment.status.toLowerCase(),
        ),
    ).length,
);

const totalAmount = computed(() =>
    props.payments.data.reduce(
        (total, payment) => total + Number(payment.amount ?? 0),
        0,
    ),
);
</script>

<template>
    <Head title="Payments" />

    <AdminLayout>
        <main class="min-h-full space-y-7 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <header class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Payment Transactions
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Review customer payment receipts, gateway settlements, and booking balances.
                    </p>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                        <Wallet class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Batch Value</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ fmtMoney(totalAmount) }}</p>
                    </div>
                </div>
            </header>

            <!-- Summary Cards -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="glass-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">On this page</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ totalPayments }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Transactions listed in current batch</p>
                </div>

                <div class="glass-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Successful Settlements</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                        {{ successfulPayments }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Completed and captured transactions
                    </p>
                </div>

                <div class="glass-card p-5 sm:col-span-2 xl:col-span-1">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Page Gross Revenue</p>
                    <p class="mt-2 break-words text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ fmtMoney(totalAmount) }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Sum of listed transactions</p>
                </div>
            </section>

            <!-- Transaction table -->
            <section class="glass-card overflow-hidden">
                <div class="flex flex-col justify-between gap-2 border-b border-black/5 dark:border-white/10 px-6 py-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Transaction Ledger</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Payment references, customer identity, and reservation links.
                        </p>
                    </div>
                    <span class="w-fit rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400">
                        {{ totalPayments }} transactions
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[950px] text-left">
                        <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-6 py-3.5">Payment #</th>
                                <th class="px-6 py-3.5">Client</th>
                                <th class="px-6 py-3.5">Reservation</th>
                                <th class="px-6 py-3.5">Amount</th>
                                <th class="px-6 py-3.5">Method</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Processed At</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/5 dark:divide-white/5 text-sm">
                            <tr
                                v-for="p in payments.data"
                                :key="p.id"
                                class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]"
                            >
                                <td class="px-6 py-4 font-mono font-medium text-slate-900 dark:text-white text-xs">
                                    {{ p.payment_number }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900 dark:text-white">
                                        {{ p.user?.name || 'Walk-in Client' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                        {{ p.user?.email || '—' }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <Link
                                        v-if="p.reservation"
                                        :href="`/admin/reservations/${p.reservation.id}`"
                                        class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-[#58779D] dark:text-[#7094BF] hover:underline"
                                    >
                                        {{ p.reservation.reservation_number }}
                                        <ExternalLink class="h-3 w-3" />
                                    </Link>
                                    <span v-else class="text-slate-400">—</span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-900 dark:text-white">
                                        {{ fmtMoney(p.amount) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-1 text-xs font-medium capitalize text-slate-700 dark:text-slate-300">
                                        {{ p.payment_method.replace(/_/g, ' ') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                                        :style="{
                                            backgroundColor: getStatusColor(p.status).bg,
                                            color: getStatusColor(p.status).text,
                                        }"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :style="{ backgroundColor: getStatusColor(p.status).dot }"
                                        ></span>
                                        {{ p.status.replace(/_/g, ' ') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    {{ p.processed_at ? new Date(p.processed_at).toLocaleString() : '—' }}
                                </td>
                            </tr>

                            <tr v-if="payments.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <CreditCard class="mx-auto h-10 w-10 text-slate-400" />
                                    <h3 class="mt-3 font-semibold text-slate-900 dark:text-white">No payments found</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        Payment transactions will display here once recorded.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav
                    v-if="payments.links?.length > 3"
                    aria-label="Payment pagination"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-black/5 dark:border-white/10 px-4 py-4"
                >
                    <template v-for="(link, i) in payments.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="inline-flex min-w-9 items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                            :class="link.active
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10'"
                        >
                            <span v-html="link.label"></span>
                        </Link>
                        <span
                            v-else
                            class="inline-flex min-w-9 pointer-events-none items-center justify-center rounded-lg border border-black/5 dark:border-white/5 px-3 py-1.5 text-xs text-slate-400 opacity-40"
                        >
                            <span v-html="link.label"></span>
                        </span>
                    </template>
                </nav>
            </section>
        </main>
    </AdminLayout>
</template>
