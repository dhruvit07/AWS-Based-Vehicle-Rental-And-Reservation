<script setup lang="ts">
import ClientLayout from '@/layouts/ClientLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    Award,
    Calendar,
    Car,
    Check,
    CheckCircle2,
    Compass,
    Crown,
    Gift,
    HelpCircle,
    Info,
    Percent,
    Shield,
    ShieldCheck,
    Sparkles,
    Star,
    TrendingUp,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface TierFeature {
    text: string;
    included: boolean;
}

interface TierCard {
    id: 'bronze' | 'silver' | 'gold' | 'platinum';
    name: string;
    badge: string;
    discount: string;
    discount_percent: number;
    requirement: string;
    description: string;
    features: TierFeature[];
}

interface CompletedTrip {
    id: number;
    reservation_number: string;
    start_date: string;
    end_date: string;
    total_amount: number;
    loyalty_discount_amount?: number;
    applied_loyalty_tier?: string;
    car?: {
        id: number;
        make: string;
        model: string;
        year: number;
        license_plate: string;
    };
}

const props = defineProps<{
    tierMeta: {
        name: 'bronze' | 'silver' | 'gold' | 'platinum';
        label: string;
        badge: string;
        icon: string;
        discount_percent: number;
        discount_rate: number;
        next_tier: string | null;
        next_tier_label: string | null;
        target_trips: number;
        target_spend: number;
        trips_to_next: number;
        spend_to_next: number;
        progress_percent: number;
        completed_trips: number;
        lifetime_spend: number;
        color: string;
        perks: string[];
    };
    stats: {
        completed_trips: number;
        lifetime_spend: number;
        lifetime_savings: number;
    };
    recentTrips: CompletedTrip[];
    allTiers: TierCard[];
}>();

const isSimulating = ref(false);

const simulateTier = (tier: string) => {
    isSimulating.value = true;
    router.post(
        '/client/loyalty/simulate',
        { tier },
        {
            preserveScroll: true,
            onFinish: () => {
                isSimulating.value = false;
            },
        },
    );
};

const getTierGradient = (tier: string) => {
    switch (tier) {
        case 'platinum':
            return 'from-purple-900/40 via-indigo-950/40 to-slate-900/60 border-purple-500/40 shadow-purple-950/20';
        case 'gold':
            return 'from-amber-950/40 via-yellow-950/30 to-slate-900/60 border-amber-500/40 shadow-amber-950/20';
        case 'silver':
            return 'from-slate-800/40 via-sky-950/30 to-slate-900/60 border-sky-400/30 shadow-sky-950/20';
        default:
            return 'from-slate-900/40 via-slate-800/30 to-slate-900/60 border-slate-700/40 shadow-slate-950/20';
    }
};

const getTierAccentColor = (tier: string) => {
    switch (tier) {
        case 'platinum':
            return 'text-purple-400 bg-purple-500/15 border-purple-500/30';
        case 'gold':
            return 'text-amber-400 bg-amber-500/15 border-amber-500/30';
        case 'silver':
            return 'text-sky-300 bg-sky-500/15 border-sky-400/30';
        default:
            return 'text-slate-400 bg-slate-500/15 border-slate-500/30';
    }
};
</script>

<template>
    <Head title="VIP Loyalty & Rewards Club | Real Rent Car" />

    <ClientLayout :breadcrumbs="[{ title: 'Reservations', href: '/client/reservations' }, { title: 'VIP Loyalty Rewards', href: '/client/loyalty' }]">
        <div class="space-y-8 pb-16">
            
            <!-- Hero Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                            <Crown class="h-4 w-4" />
                        </span>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                            VIP Loyalty & Rewards Club
                        </h1>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Tier-based discounts on every trip, priority hub dispatch, and complimentary VIP protections.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        href="/fleet"
                        class="apple-btn-primary inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold"
                    >
                        <Car class="h-3.5 w-3.5" />
                        <span>Book with {{ tierMeta.discount_percent }}% Discount</span>
                    </Link>
                </div>
            </div>

            <!-- Viva Evaluator Quick Tier Simulator Card -->
            <div class="rounded-3xl border border-cyan-500/30 bg-cyan-500/5 p-5 backdrop-blur-xl transition dark:border-cyan-500/20 dark:bg-cyan-950/15">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-500/20 text-cyan-600 dark:text-cyan-400">
                            <Sparkles class="h-4 w-4" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Viva Evaluator Mode: Instant Tier Simulator
                                </h3>
                                <span class="rounded bg-cyan-500/20 px-1.5 py-0.2 text-[9px] font-mono font-bold text-cyan-700 dark:text-cyan-300">
                                    Exam Tool
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Instantly switch your loyalty tier to test automatic checkout discounts & VIP radar beacons.
                            </p>
                        </div>
                    </div>

                    <!-- 4 Instant Simulator Buttons -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            @click="simulateTier('bronze')"
                            :disabled="isSimulating"
                            class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                            :class="tierMeta.name === 'bronze'
                                ? 'border-slate-400 bg-slate-200 text-slate-900 dark:border-white/20 dark:bg-white/20 dark:text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                        >
                            Bronze (0%)
                        </button>

                        <button
                            type="button"
                            @click="simulateTier('silver')"
                            :disabled="isSimulating"
                            class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                            :class="tierMeta.name === 'silver'
                                ? 'border-sky-400 bg-sky-500/25 text-sky-700 dark:text-sky-300'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                        >
                            Silver (5%)
                        </button>

                        <button
                            type="button"
                            @click="simulateTier('gold')"
                            :disabled="isSimulating"
                            class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                            :class="tierMeta.name === 'gold'
                                ? 'border-amber-400 bg-amber-500/25 text-amber-700 dark:text-amber-300'
                                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                        >
                            Gold (10%)
                        </button>

                        <button
                            type="button"
                            @click="simulateTier('platinum')"
                            :disabled="isSimulating"
                            class="rounded-xl border px-3.5 py-1.5 text-xs font-bold transition shadow-sm"
                            :class="tierMeta.name === 'platinum'
                                ? 'border-purple-400 bg-purple-500/30 text-purple-700 dark:text-purple-300 ring-2 ring-purple-400/30'
                                : 'border-purple-500/30 bg-purple-500/10 text-purple-700 hover:bg-purple-500/20 dark:text-purple-300'"
                        >
                            👑 Platinum VIP (15%)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hero VIP Passport Card -->
            <div
                class="relative overflow-hidden rounded-3xl border bg-gradient-to-br p-6 sm:p-8 backdrop-blur-2xl transition-all shadow-xl"
                :class="getTierGradient(tierMeta.name)"
            >
                <!-- Subtle Radial Watermark -->
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

                <div class="relative z-10">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-3.5">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl border text-xl shadow-lg"
                                :class="getTierAccentColor(tierMeta.name)"
                            >
                                <Crown v-if="tierMeta.name === 'platinum'" class="h-7 w-7" />
                                <Sparkles v-else-if="tierMeta.name === 'gold'" class="h-7 w-7" />
                                <Award v-else-if="tierMeta.name === 'silver'" class="h-7 w-7" />
                                <Shield v-else class="h-7 w-7" />
                            </div>

                            <div>
                                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">
                                    Official Loyalty Passport
                                </span>
                                <div class="flex items-center gap-2.5">
                                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                                        {{ tierMeta.label }}
                                    </h2>
                                    <span
                                        class="rounded-full px-3 py-0.5 text-xs font-bold uppercase tracking-wider"
                                        :class="getTierAccentColor(tierMeta.name)"
                                    >
                                        {{ tierMeta.badge }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Active Checkout Discount Pill -->
                        <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-md dark:bg-white/5">
                            <Percent class="h-5 w-5 text-cyan-400" />
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    Automatic Checkout Discount
                                </p>
                                <p class="text-base font-black text-slate-900 dark:text-white">
                                    {{ tierMeta.discount_percent }}% OFF Every Rental
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar to Next Tier -->
                    <div class="mt-7 rounded-2xl border border-slate-200/60 bg-white/60 p-4 backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">
                                <template v-if="tierMeta.next_tier_label">
                                    Progress to {{ tierMeta.next_tier_label }}
                                </template>
                                <template v-else>
                                    Highest VIP Tier Unlocked 👑
                                </template>
                            </span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white">
                                {{ tierMeta.progress_percent }}% Complete
                            </span>
                        </div>

                        <!-- Track -->
                        <div class="mt-2.5 h-2.5 w-full overflow-hidden rounded-full bg-slate-200/80 dark:bg-white/10">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :class="tierMeta.name === 'platinum' ? 'bg-gradient-to-r from-purple-500 to-cyan-400' : 'bg-gradient-to-r from-amber-500 to-emerald-400'"
                                :style="{ width: `${tierMeta.progress_percent}%` }"
                            ></div>
                        </div>

                        <!-- Remaining requirements -->
                        <div v-if="tierMeta.next_tier_label" class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                            <span>
                                Complete <strong class="text-slate-900 dark:text-white">{{ tierMeta.trips_to_next }} more trips</strong> or spend <strong class="text-slate-900 dark:text-white">₹{{ tierMeta.spend_to_next.toLocaleString() }}</strong> to level up.
                            </span>
                            <span class="font-mono text-[10px]">
                                Target: {{ tierMeta.target_trips }} trips / ₹{{ tierMeta.target_spend.toLocaleString() }}
                            </span>
                        </div>
                        <div v-else class="mt-2 text-[11px] text-purple-600 dark:text-purple-300 font-medium">
                            You have achieved maximum Platinum VIP standing. You receive 15% automatic checkout discounts and top priority radar handover.
                        </div>
                    </div>

                    <!-- 3 Lifetime Metrics Grid -->
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200/60 bg-white/60 p-4 backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Completed Trips
                            </span>
                            <p class="mt-1 font-mono text-2xl font-black text-slate-900 dark:text-white">
                                {{ stats.completed_trips }}
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                Verified completed bookings
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200/60 bg-white/60 p-4 backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Lifetime Fleet Spend
                            </span>
                            <p class="mt-1 font-mono text-2xl font-black text-slate-900 dark:text-white">
                                ₹{{ Number(stats.lifetime_spend || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                Total net rental volume
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200/60 bg-white/60 p-4 backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Total VIP Savings Earned
                            </span>
                            <p class="mt-1 font-mono text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                ₹{{ Number(stats.lifetime_savings || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                Cumulative loyalty discounts
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 4 Tiers Comparison Grid -->
            <div>
                <div class="mb-4">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">
                        Loyalty Tiers & Benefits Matrix
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Compare exclusive perks, discounts, and dispatch privileges unlocked at each tier.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="tier in allTiers"
                        :key="tier.id"
                        class="relative flex flex-col justify-between rounded-3xl border p-5 backdrop-blur-xl transition duration-200"
                        :class="tier.id === tierMeta.name
                            ? 'border-purple-500/60 bg-purple-500/[0.04] shadow-lg dark:border-purple-400/50 dark:bg-purple-950/20 ring-2 ring-purple-500/30'
                            : 'border-slate-200/80 bg-white/70 dark:border-white/10 dark:bg-white/[0.02]'"
                    >
                        <!-- Active Badge -->
                        <div
                            v-if="tier.id === tierMeta.name"
                            class="absolute -top-3 right-4 rounded-full bg-purple-600 px-3 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-white shadow-md"
                        >
                            Your Active Tier
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ tier.name }}
                                </h3>
                                <span
                                    class="rounded-lg px-2 py-0.5 text-xs font-bold"
                                    :class="getTierAccentColor(tier.id)"
                                >
                                    {{ tier.discount }}
                                </span>
                            </div>

                            <p class="mt-1 font-mono text-[11px] font-semibold text-slate-400">
                                {{ tier.requirement }}
                            </p>

                            <p class="mt-2 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                {{ tier.description }}
                            </p>

                            <!-- Features List -->
                            <ul class="mt-4 space-y-2.5 border-t border-slate-100 pt-4 text-xs dark:border-white/10">
                                <li
                                    v-for="(feat, fIdx) in tier.features"
                                    :key="fIdx"
                                    class="flex items-start gap-2"
                                    :class="feat.included ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400 line-through opacity-50 dark:text-slate-500'"
                                >
                                    <Check v-if="feat.included" class="h-3.5 w-3.5 shrink-0 text-emerald-500 mt-0.5" />
                                    <span v-else class="h-3.5 w-3.5 shrink-0 text-slate-400 mt-0.5 text-center leading-none">✕</span>
                                    <span>{{ feat.text }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-5 border-t border-slate-100 pt-3 dark:border-white/5">
                            <button
                                v-if="tier.id !== tierMeta.name"
                                type="button"
                                @click="simulateTier(tier.id)"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                            >
                                Test This Tier
                            </button>
                            <div v-else class="text-center py-2 text-xs font-bold text-purple-600 dark:text-purple-400">
                                Current Active Standing
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Completed Trips & Savings History -->
            <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.02]">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-white/10">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            Completed Rental Savings Ledger
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Itemized record of completed journeys with applied loyalty savings.
                        </p>
                    </div>

                    <Link
                        href="/client/reservations"
                        class="text-xs font-bold text-[#58779D] hover:underline dark:text-[#7094BF]"
                    >
                        View All Trips ➔
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="recentTrips.length === 0" class="py-10 text-center">
                    <Car class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600" />
                    <p class="mt-2 text-xs font-bold text-slate-700 dark:text-slate-300">
                        No Completed Journeys Yet
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Complete your first rental to start accumulating lifetime spend and unlock Silver Explorer status!
                    </p>
                    <Link
                        href="/fleet"
                        class="apple-btn-primary mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold"
                    >
                        Browse Available Vehicles
                    </Link>
                </div>

                <!-- Trips Table -->
                <div v-else class="mt-4 divide-y divide-slate-100 dark:divide-white/5">
                    <div
                        v-for="trip in recentTrips"
                        :key="trip.id"
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-3.5 gap-2"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300">
                                <Car class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ trip.car?.make }} {{ trip.car?.model }} ({{ trip.car?.license_plate }})
                                </p>
                                <p class="text-[10px] text-slate-400">
                                    #{{ trip.reservation_number }} • {{ trip.start_date }} to {{ trip.end_date }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-right">
                            <div v-if="Number(trip.loyalty_discount_amount || 0) > 0">
                                <span class="rounded bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                    Saved ₹{{ Number(trip.loyalty_discount_amount).toLocaleString() }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold">Total Paid</span>
                                <p class="font-mono text-xs font-bold text-slate-900 dark:text-white">
                                    ₹{{ Number(trip.total_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </ClientLayout>
</template>
