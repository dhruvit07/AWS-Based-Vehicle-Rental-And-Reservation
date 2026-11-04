<script setup lang="ts">
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { fleet, about, contact } from '@/routes';
import {
    ArrowRight,
    ShieldCheck,
    Clock,
    Zap,
    KeyRound,
    Fuel,
    Calendar,
    ChevronRight,
    CheckCircle2,
    Sparkles,
} from 'lucide-vue-next';

interface Car {
    id: number;
    make: string;
    model: string;
    year: number;
    price_per_day: string | number;
    description?: string;
    fuel_type?: string;
    image_url?: string;
    image?: string;
    color?: string;
    status?: string;
}

const page = usePage();
const homeCars = (page.props.homeCars as Car[] | undefined) ?? [];

const carImage = (car: Car) =>
    car.image_url || car.image || '/images/hero_image.png';

const formatPrice = (price: string | number) => {
    const amount = Number(price);
    return Number.isFinite(amount)
        ? new Intl.NumberFormat('en-IN', {
              maximumFractionDigits: 0,
          }).format(amount)
        : String(price);
};
</script>

<template>
    <Head title="Real Rent Car | Effortless Luxury Mobility" />

    <HomeLayout>
        <div class="overflow-hidden bg-[#FBFBFD] text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100">

            <!-- CINEMATIC HERO SECTION -->
            <section class="relative isolate overflow-hidden bg-[#101D35] text-white dark:bg-[#070A0F]">
                <!-- Ambient Stage Lighting -->
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(120,160,210,0.25),rgba(255,255,255,0))]"></div>
                <div class="pointer-events-none absolute -right-24 -top-24 -z-10 h-96 w-96 rounded-full bg-[#58779D]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 left-1/4 -z-10 h-96 w-96 rounded-full bg-[#7094BF]/15 blur-3xl"></div>

                <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 sm:px-8 sm:py-24 lg:grid-cols-12 lg:gap-8 lg:px-8 lg:py-32">
                    <!-- Left Hero Content -->
                    <div class="lg:col-span-7">
                        <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl lg:leading-[1.1]">
                            Precision engineering.<br />
                            <span class="text-slate-300 dark:text-slate-300">Effortless travel.</span>
                        </h1>

                        <p class="mt-6 max-w-xl text-base leading-relaxed text-slate-300 sm:text-lg sm:leading-8">
                            Experience automotive excellence on your own schedule. Hand-selected premium vehicles, instant date validation, and transparent pricing with zero friction.
                        </p>

                        <div class="mt-8 flex flex-col gap-3.5 sm:flex-row sm:items-center">
                            <Link
                                :href="fleet()"
                                class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-950 shadow-md transition-all hover:bg-slate-100 hover:shadow-lg active:scale-98 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                            >
                                <span>Browse Our Fleet</span>
                                <ArrowRight class="h-4 w-4" />
                            </Link>

                            <Link
                                :href="about()"
                                class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-md transition-all hover:bg-white/10 hover:border-white/30"
                            >
                                How RealRent Works
                            </Link>
                        </div>

                        <!-- Precision Metrics Bar -->
                        <div class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/15 pt-8">
                            <div>
                                <p class="text-2xl font-bold tracking-tight text-white sm:text-3xl">100%</p>
                                <p class="mt-1 text-xs text-slate-400 sm:text-sm">Verified fleet</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Instant</p>
                                <p class="mt-1 text-xs text-slate-400 sm:text-sm">Date locking</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold tracking-tight text-white sm:text-3xl">24 / 7</p>
                                <p class="mt-1 text-xs text-slate-400 sm:text-sm">Road concierge</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Cinematic Hero Stage -->
                    <div class="relative mx-auto w-full max-w-xl lg:col-span-5">
                        <div class="relative overflow-hidden rounded-3xl border border-white/15 bg-white/5 p-3.5 shadow-2xl backdrop-blur-xl sm:p-5">
                            <div class="relative aspect-16/10 overflow-hidden rounded-2xl bg-slate-950">
                                <img
                                    src="/images/hero_image.png"
                                    alt="Luxury automotive vehicle"
                                    class="h-full w-full object-cover object-center transition-transform duration-700 hover:scale-105"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                                <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-300">Curated Fleet</p>
                                        <p class="text-sm font-bold sm:text-base">Ready for immediate handover</p>
                                    </div>
                                    <Link
                                        :href="fleet()"
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-950 transition-transform duration-200 hover:scale-110"
                                        aria-label="View fleet"
                                    >
                                        <ArrowRight class="h-4 w-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ASYMMETRIC BENTO SHOWCASE (No Emojis, No AI Slop) -->
            <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-8 lg:py-28">
                <div class="max-w-3xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                        Designed around the driver.
                    </h2>
                    <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-400 sm:text-lg">
                        Every touchpoint is calibrated for calm precision—from interactive live calendar checks to transparent handover protocols.
                    </p>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    <!-- Bento Card 1: Double Width Hero Feature -->
                    <div class="glass-card flex flex-col justify-between p-8 md:col-span-2">
                        <div class="space-y-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#101D35] text-white dark:bg-white dark:text-slate-950 shadow-sm">
                                <Clock class="h-6 w-6" stroke-width="2" />
                            </div>
                            <h3 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                                Tactile 2-Month Conflict Prevention
                            </h3>
                            <p class="max-w-xl text-base leading-relaxed text-slate-600 dark:text-slate-400">
                                Real-time server-enforced booking windows prevent double-booking before you ever enter card details. Reserved dates are visibly striped and disabled instantly.
                            </p>
                        </div>
                        <div class="mt-8 flex items-center gap-4 border-t border-slate-200/80 pt-6 text-xs text-slate-500 dark:border-white/10 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1.5 font-medium text-emerald-600 dark:text-emerald-400">
                                <CheckCircle2 class="h-4 w-4" /> Zero overlap collisions
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium">
                                <Zap class="h-4 w-4" /> Instant lock verification
                            </span>
                        </div>
                    </div>

                    <!-- Bento Card 2: Transparent Terms -->
                    <div class="glass-card flex flex-col justify-between p-8">
                        <div class="space-y-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#101D35] text-white dark:bg-white dark:text-slate-950 shadow-sm">
                                <ShieldCheck class="h-6 w-6" stroke-width="2" />
                            </div>
                            <h3 class="text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                Uncompromised Transparency
                            </h3>
                            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                What you see is what you pay. No hidden service charges, fuel penalties, or last-minute surprise deposits at pickup.
                            </p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/80 pt-4 text-xs font-semibold text-[#58779D] dark:text-[#7094BF] dark:border-white/10">
                            Clear, upfront invoices
                        </div>
                    </div>

                    <!-- Bento Card 3: Curated Fleet Condition -->
                    <div class="glass-card flex flex-col justify-between p-8">
                        <div class="space-y-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#101D35] text-white dark:bg-white dark:text-slate-950 shadow-sm">
                                <KeyRound class="h-6 w-6" stroke-width="2" />
                            </div>
                            <h3 class="text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                Strict Multi-Point Inspection
                            </h3>
                            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                Every chassis undergoes mechanical safety inspection, sanitized cabin detailing, and complete fluid checks between rentals.
                            </p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/80 pt-4 text-xs font-semibold text-[#58779D] dark:text-[#7094BF] dark:border-white/10">
                            Pristine interior & mechanical health
                        </div>
                    </div>

                    <!-- Bento Card 4: Double Width Concierge -->
                    <div class="glass-card flex flex-col justify-between p-8 md:col-span-2">
                        <div class="space-y-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#101D35] text-white dark:bg-white dark:text-slate-950 shadow-sm">
                                <Sparkles class="h-6 w-6" stroke-width="2" />
                            </div>
                            <h3 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                                Dedicated Concierge & Support Desk
                            </h3>
                            <p class="max-w-xl text-base leading-relaxed text-slate-600 dark:text-slate-400">
                                Message directly through your client dashboard for quick itinerary adjustments, roadside guidance, or flexible drop-off extensions.
                            </p>
                        </div>
                        <div class="mt-8 flex items-center justify-between border-t border-slate-200/80 pt-6 dark:border-white/10">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Average response time under 15 minutes</span>
                            <Link :href="contact()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#58779D] hover:underline dark:text-[#7094BF]">
                                <span>Get in touch</span>
                                <ChevronRight class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FEATURED VEHICLES SHOWCASE -->
            <section class="border-t border-slate-200/80 bg-slate-100/50 py-20 transition-colors duration-200 dark:border-white/10 dark:bg-[#070A0F]/50 sm:py-24">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-8">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                        <div>
                            <h2 class="text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                                Featured automobiles.
                            </h2>
                            <p class="mt-2 text-base text-slate-600 dark:text-slate-400">
                                High-performance sedans, SUVs, and grand tourers prepared for your journey.
                            </p>
                        </div>

                        <Link
                            :href="fleet()"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-xs transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
                        >
                            <span>Explore Entire Fleet</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <!-- Vehicle Grid -->
                    <div v-if="homeCars.length" class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="car in homeCars"
                            :key="car.id"
                            class="glass-card group flex flex-col overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:hover:border-white/20"
                        >
                            <!-- 16:9 Staged Vehicle Visual -->
                            <div class="relative aspect-16/10 overflow-hidden bg-slate-100 dark:bg-slate-900">
                                <img
                                    :src="carImage(car)"
                                    :alt="`${car.make} ${car.model}`"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>

                                <!-- Pill Spec Badges -->
                                <div class="absolute left-3 top-3 flex items-center gap-1.5">
                                    <span class="rounded-full border border-white/20 bg-black/40 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur-md">
                                        {{ car.year }}
                                    </span>
                                    <span v-if="car.fuel_type" class="flex items-center gap-1 rounded-full border border-white/20 bg-black/40 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur-md">
                                        <Fuel class="h-3 w-3" />
                                        {{ car.fuel_type }}
                                    </span>
                                </div>
                            </div>

                            <!-- Vehicle Details -->
                            <div class="flex flex-1 flex-col justify-between p-6">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                                        {{ car.make }}
                                    </p>
                                    <h3 class="mt-1 text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                        {{ car.model }}
                                    </h3>
                                    <p v-if="car.description" class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                        {{ car.description }}
                                    </p>
                                </div>

                                <div class="mt-6 flex items-center justify-between border-t border-slate-200/80 pt-5 dark:border-white/10">
                                    <div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Daily rate</p>
                                        <p class="text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                            ₹{{ formatPrice(car.price_per_day) }}
                                        </p>
                                    </div>
                                    <Link
                                        :href="fleet()"
                                        class="apple-btn-primary px-4 py-2 text-xs font-semibold"
                                    >
                                        View Details
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- Empty Fleet State -->
                    <div v-else class="glass-card mt-12 p-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-white/5 text-slate-900 dark:text-white">
                            <Calendar class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-slate-950 dark:text-white">Fleet undergoing dispatch calibration</h3>
                        <p class="mx-auto mt-2 max-w-sm text-sm text-slate-600 dark:text-slate-400">
                            Our complete collection is being refreshed. View all available models in the fleet directory.
                        </p>
                        <Link
                            :href="fleet()"
                            class="apple-btn-primary mt-6 inline-flex px-5 py-2.5 text-xs font-semibold"
                        >
                            Browse All Vehicles
                        </Link>
                    </div>
                </div>
            </section>

            <!-- FINAL CALL TO ACTION (Apple Clean Obsidian Card) -->
            <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-8 lg:py-28">
                <div class="relative isolate overflow-hidden rounded-3xl border border-black/10 bg-[#101D35] px-6 py-16 text-center text-white shadow-xl dark:border-white/10 dark:bg-[#0F1724] sm:px-16 sm:py-20">
                    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(112,148,191,0.25),transparent_60%)]"></div>

                    <h2 class="mx-auto max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        Your key is waiting.
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-slate-300">
                        Reserve in under two minutes with live calendar confirmation and immediate booking confirmation.
                    </p>

                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <Link
                            :href="fleet()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-950 shadow-md transition hover:bg-slate-100 active:scale-98 dark:bg-white dark:text-slate-950"
                        >
                            <span>Browse Fleet & Reserve</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            :href="contact()"
                            class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-md transition hover:bg-white/10"
                        >
                            Speak to Concierge
                        </Link>
                    </div>
                </div>
            </section>

        </div>
    </HomeLayout>
</template>
