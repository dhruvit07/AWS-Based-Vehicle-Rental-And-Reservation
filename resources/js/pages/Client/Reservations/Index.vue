<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ClientLayout from '@/layouts/ClientLayout.vue';
import CancelReservationModal from '@/components/CancelReservationModal.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import { show } from '@/routes/client/reservations';
import { fleet } from '@/routes';
import {
    ArrowRight,
    Calendar,
    Compass,
    FileCheck2,
    QrCode,
    RotateCcw,
    Shield,
    ShieldCheck,
    Star,
    Ticket,
    X,
} from 'lucide-vue-next';

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

const getStatusBadge = (status: string) => {
    const s = status.toLowerCase();
    if (s === 'active') {
        return {
            label: 'Active Journey',
            dotClass: 'bg-emerald-500 animate-pulse',
            pillClass: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        };
    }
    if (['confirmed', 'approved'].includes(s)) {
        return {
            label: 'Confirmed • Handover Due',
            dotClass: 'bg-sky-500',
            pillClass: 'border-sky-500/30 bg-sky-500/10 text-sky-600 dark:text-sky-400',
        };
    }
    if (s === 'completed') {
        return {
            label: 'Trip Completed',
            dotClass: 'bg-slate-400',
            pillClass: 'border-slate-300 bg-slate-100 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300',
        };
    }
    if (['cancelled', 'canceled', 'rejected'].includes(s)) {
        return {
            label: 'Cancelled',
            dotClass: 'bg-rose-500',
            pillClass: 'border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400',
        };
    }
    return {
        label: status.toUpperCase(),
        dotClass: 'bg-amber-500',
        pillClass: 'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400',
    };
};
</script>

<template>
    <Head title="Reservations Passport | Real Rent Car" />

    <ClientLayout>
        <main class="min-h-screen flex-1 space-y-8 bg-[#F8F9FA] p-4 sm:p-6 lg:p-8 text-slate-900 transition-colors duration-200 dark:bg-[#06090E] dark:text-slate-100">
            
            <!-- STAGE HERO: RESERVATIONS PASSPORT -->
            <section class="relative isolate overflow-hidden rounded-3xl border border-white/10 bg-[#06090E] p-6 text-white shadow-xl sm:p-8">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_75%_50%_at_50%_-20%,rgba(56,189,248,0.15),transparent_70%)]"></div>

                <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-mono text-slate-300">
                            <Ticket class="h-3.5 w-3.5 text-sky-400" />
                            <span>Digital Boarding Pass System</span>
                        </div>
                        <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">
                            Reservations Passport
                        </h1>
                        <p class="mt-1 max-w-xl text-xs leading-relaxed text-slate-400 sm:text-sm">
                            Manage confirmed journeys, digital agreements, and contactless mobile gate passes.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link
                            :href="fleet()"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-xs font-bold text-slate-950 shadow-sm transition hover:bg-slate-100 active:scale-[0.98] dark:bg-white dark:text-[#06090E]"
                        >
                            <Compass class="h-4 w-4 text-[#58779D]" />
                            <span>Explore Fleet</span>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- BOARDING PASS RENTAL CARDS CONTAINER -->
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-slate-950 dark:text-white">
                            Your Mobility Passes
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Select any passport to access gate pass QR, inspection blueprint, and invoice details.
                        </p>
                    </div>

                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-mono font-semibold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                        {{ props.reservations.data.length }} Registered
                    </span>
                </div>

                <!-- Empty State -->
                <div
                    v-if="props.reservations.data.length === 0"
                    class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-xs dark:border-white/10 dark:bg-[#0A101D] sm:p-16"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-white/5 dark:text-slate-500">
                        <Ticket class="h-7 w-7" />
                    </div>

                    <h3 class="mt-4 text-lg font-bold text-slate-950 dark:text-white">
                        No Rental Passports Issued Yet
                    </h3>

                    <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Your confirmed itineraries will automatically generate authenticated boarding passes and contactless gate pass QR codes.
                    </p>

                    <Link
                        :href="fleet()"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#06090E] px-6 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#152238] dark:bg-white dark:text-[#06090E]"
                    >
                        <span>Reserve a Vehicle</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <!-- Boarding Pass Rental Cards List -->
                <div v-else class="space-y-4">
                    <article
                        v-for="res in props.reservations.data"
                        :key="res.id"
                        @click="navigateToReservation(res.id)"
                        class="group relative flex flex-col md:flex-row overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-xs transition-all duration-300 hover:border-slate-300 hover:shadow-lg dark:border-white/10 dark:bg-[#0A101D] dark:hover:border-white/20 cursor-pointer"
                    >
                        <!-- Left Section: Vehicle & Route Details (70%) -->
                        <div class="flex flex-1 flex-col justify-between p-6 sm:p-7">
                            <div>
                                <!-- Top Status Ribbon -->
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold border flex items-center gap-1.5" :class="getStatusBadge(res.status).pillClass">
                                            <span class="h-1.5 w-1.5 rounded-full" :class="getStatusBadge(res.status).dotClass"></span>
                                            <span>{{ getStatusBadge(res.status).label }}</span>
                                        </span>
                                        <span class="font-mono text-xs font-bold text-slate-400">
                                            #{{ res.reservation_number }}
                                        </span>
                                    </div>

                                    <span v-if="res.car?.license_plate" class="rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-mono text-[11px] font-semibold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                        {{ res.car.license_plate }}
                                    </span>
                                </div>

                                <!-- Vehicle Name -->
                                <div class="mt-4 flex items-center gap-4">
                                    <div v-if="res.car?.image_url" class="h-14 w-20 shrink-0 overflow-hidden rounded-xl border border-slate-100 bg-slate-900 dark:border-white/10">
                                        <img :src="res.car.image_url" :alt="res.car.model" class="h-full w-full object-cover" />
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-mono uppercase tracking-wider text-[#58779D] dark:text-[#93B4D7]">
                                            {{ res.car?.make || 'Luxury Rental' }}
                                        </p>
                                        <h3 class="text-xl font-extrabold tracking-tight text-slate-950 dark:text-white group-hover:text-[#58779D] dark:group-hover:text-[#93B4D7] transition-colors">
                                            {{ res.car ? `${res.car.model}` : 'Reserved Vehicle' }}
                                        </h3>
                                    </div>
                                </div>

                                <!-- Journey Itinerary Dates -->
                                <div class="mt-5 grid grid-cols-2 gap-4 rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5 dark:border-white/5 dark:bg-white/[0.02]">
                                    <div>
                                        <p class="text-[10px] font-mono uppercase tracking-wider text-slate-400">Pick-up Date</p>
                                        <p class="mt-0.5 text-xs font-bold text-slate-900 dark:text-white">
                                            {{ formatDate(res.start_date) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-mono uppercase tracking-wider text-slate-400">Return Date</p>
                                        <p class="mt-0.5 text-xs font-bold text-slate-900 dark:text-white">
                                            {{ formatDate(res.end_date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Info -->
                            <div class="mt-4 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                                <span class="font-medium">{{ res.total_days }} Rental Day{{ res.total_days === 1 ? '' : 's' }}</span>
                                <span>•</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-medium">Collision Free</span>
                            </div>
                        </div>

                        <!-- Perforated Tear-off Divider Styling -->
                        <div class="relative flex md:flex-col items-center justify-between border-t md:border-t-0 md:border-l border-dashed border-slate-200 bg-slate-50/70 p-6 dark:border-white/10 dark:bg-[#070B14] md:w-64">
                            
                            <!-- Ledger Amount -->
                            <div class="text-left md:text-center w-full">
                                <p class="text-[10px] font-mono uppercase tracking-wider text-slate-400">Total Settled</p>
                                <p class="mt-1 font-mono text-2xl font-extrabold text-slate-950 dark:text-white">
                                    {{ props.currency.symbol }}{{ formatAmount(res.total_amount) }}
                                </p>
                                <p v-if="res.refund_amount && Number(res.refund_amount) > 0" class="mt-0.5 font-mono text-xs text-emerald-600 dark:text-emerald-400">
                                    Refunded: {{ props.currency.symbol }}{{ formatAmount(res.refund_amount) }}
                                </p>
                            </div>

                            <!-- Interactive Actions Ribbon -->
                            <div class="mt-4 flex flex-col gap-2 w-full" @click.stop>
                                <Link
                                    :href="show(res.id).url"
                                    class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-[#06090E] py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#152238] active:scale-[0.98] dark:bg-white dark:text-[#06090E] dark:hover:bg-slate-200"
                                >
                                    <span>Journey Hub</span>
                                    <ArrowRight class="h-3.5 w-3.5" />
                                </Link>

                                <button
                                    v-if="res.status.toLowerCase() === 'completed' && !res.review"
                                    type="button"
                                    @click="openReviewModal(res)"
                                    class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-500/20 dark:text-amber-300"
                                >
                                    <Star class="h-3 w-3 fill-current" />
                                    <span>Rate Trip</span>
                                </button>

                                <button
                                    v-if="canCancel(res)"
                                    type="button"
                                    @click="openCancelModal(res)"
                                    class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-rose-500/20 bg-rose-500/10 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-500/20 dark:text-rose-400"
                                >
                                    <span>Cancel Trip</span>
                                </button>
                            </div>

                        </div>
                    </article>
                </div>
            </section>
        </main>

        <!-- Modals -->
        <ReviewModal
            :is-open="isReviewModalOpen"
            :reservation="selectedReservationForReview"
            @close="isReviewModalOpen = false"
            @submitted="router.reload()"
        />

        <CancelReservationModal
            :is-open="isCancelModalOpen"
            :reservation="selectedReservationForCancel"
            :currency-symbol="props.currency?.symbol || '₹'"
            @close="isCancelModalOpen = false"
            @cancelled="router.reload()"
        />
    </ClientLayout>
</template>
