<script setup lang="ts">
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { fleet, contact } from '@/routes';
import {
    ShieldCheck,
    Clock,
    Award,
    Sparkles,
    CheckCircle2,
    Users,
    ArrowRight,
    MapPin,
    Car,
} from 'lucide-vue-next';

const milestones = [
    {
        year: '2022',
        title: 'Platform Foundation',
        desc: 'Established with a focus on friction-free automotive mobility and 100% upfront pricing.',
    },
    {
        year: '2023',
        title: 'Fleet Expansion',
        desc: 'Onboarded top-tier premium models with standardized multi-point safety verification.',
    },
    {
        year: '2024',
        title: 'Real-Time Conflict Prevention',
        desc: 'Engineered server-enforced overlapping date validation to completely eliminate double bookings.',
    },
    {
        year: '2025',
        title: 'Concierge Desk & Instant Handover',
        desc: 'Launched in-app direct messaging and rapid digital check-in across all major hubs.',
    },
];

const team = [
    {
        name: 'Sarah Johnson',
        role: 'Chief Executive Officer',
        desc: 'Steering product vision and operational precision across our mobility network.',
        image: '/images/team/sara.webp',
    },
    {
        name: 'Michael Chen',
        role: 'Head of Operations',
        desc: 'Managing fleet readiness, strict maintenance schedules, and depot logistics.',
        image: '/images/team/michael.webp',
    },
    {
        name: 'Emily Rodriguez',
        role: 'Head of Client Concierge',
        desc: 'Dedicated to driver experience, transparent terms, and 24/7 journey support.',
        image: '/images/team/emily.webp',
    },
];
</script>

<template>
    <Head title="About Us | Real Rent Car" />

    <HomeLayout>
        <div class="min-h-screen bg-[#FBFBFD] text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100">

            <!-- Editorial Hero -->
            <section class="relative isolate overflow-hidden bg-[#101D35] py-20 text-white dark:bg-[#070A0F] md:py-28">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(120,160,210,0.25),rgba(255,255,255,0))]"></div>
                <div class="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-[#58779D]/20 blur-3xl"></div>

                <div class="relative mx-auto max-w-5xl px-5 text-center sm:px-8">
                    <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                        Calibrated for the discerning driver.
                    </h1>

                    <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg sm:leading-8">
                        Real Rent Car was built to strip friction, ambiguity, and surprise fees from premium car hire. We pair a meticulously maintained fleet with instant digital reservations.
                    </p>

                    <div class="mt-8 flex flex-col justify-center gap-3.5 sm:flex-row">
                        <Link
                            :href="fleet()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-slate-950 shadow-md transition hover:bg-slate-100 dark:bg-white dark:text-slate-950"
                        >
                            <span>Browse Available Fleet</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            :href="contact()"
                            class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-md transition hover:bg-white/10"
                        >
                            Speak With Our Team
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Brand Philosophy & Story Grid -->
            <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-8 lg:py-28">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div class="space-y-6">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                            A rental experience built on quiet confidence.
                        </h2>

                        <p class="text-base leading-relaxed text-slate-600 dark:text-slate-400">
                            Automotive rental should be an extension of your own lifestyle: seamless, reliable, and impeccably presented. We treat every handover with exacting attention to detail—from mechanical diagnostics to pristine interior detailing.
                        </p>

                        <p class="text-base leading-relaxed text-slate-600 dark:text-slate-400">
                            Our proprietary reservation platform verifies availability in real-time, locking out conflicting dates instantly. When you arrive, your vehicle is fueled, detailed, and ready to drive within minutes.
                        </p>

                        <div class="pt-2">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white/60 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <ShieldCheck class="h-5 w-5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white">Comprehensive Coverage</span>
                                </div>
                                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white/60 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <Clock class="h-5 w-5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white">Zero Conflict Booking</span>
                                </div>
                                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white/60 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <Sparkles class="h-5 w-5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white">Pristine Cabin Sanitization</span>
                                </div>
                                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white/60 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <Users class="h-5 w-5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span class="text-xs font-semibold text-slate-900 dark:text-white">Direct Concierge Chat</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Accent Card -->
                    <div class="glass-card relative overflow-hidden p-8 sm:p-10">
                        <div class="space-y-6">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#101D35] text-white dark:bg-white dark:text-slate-950">
                                <Car class="h-6 w-6" stroke-width="2" />
                            </div>

                            <h3 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                                The RealRent Standard
                            </h3>

                            <div class="space-y-4">
                                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Upfront Line-Item Transparency</p>
                                    <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                        Rates display all applicable taxes, insurances, and mileage allowances with zero surprises.
                                    </p>
                                </div>

                                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Verified Identity & Swift Handoff</p>
                                    <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                        Upload driving credentials once through your client console for contactless key handoff.
                                    </p>
                                </div>

                                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">24/7 Roadside Assistance</p>
                                    <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                        Round-the-clock priority dispatch team standing by for route changes or mechanical aid.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Milestones Timeline -->
            <section class="border-y border-slate-200/80 bg-slate-100/50 py-20 transition-colors duration-200 dark:border-white/10 dark:bg-[#070A0F]/50 sm:py-24">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                            Milestones in mobility.
                        </h2>
                        <p class="mt-2 text-base text-slate-600 dark:text-slate-400">
                            Our trajectory from a focused boutique garage to an engineering-first car rental system.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="item in milestones"
                            :key="item.year"
                            class="glass-card flex flex-col justify-between p-6"
                        >
                            <div>
                                <span class="text-xs font-bold tracking-wider text-[#58779D] dark:text-[#7094BF]">
                                    {{ item.year }}
                                </span>
                                <h3 class="mt-2 text-base font-bold text-slate-950 dark:text-white">
                                    {{ item.title }}
                                </h3>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                    {{ item.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Leadership Team -->
            <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-8 lg:py-28">
                <div class="max-w-2xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                        Executive leadership.
                    </h2>
                    <p class="mt-2 text-base text-slate-600 dark:text-slate-400">
                        Passionate automotive professionals and software engineers committed to seamless travel.
                    </p>
                </div>

                <div class="mt-12 grid gap-8 md:grid-cols-3">
                    <article
                        v-for="person in team"
                        :key="person.name"
                        class="glass-card overflow-hidden transition hover:-translate-y-1 hover:shadow-lg"
                    >
                        <div class="aspect-4/3 overflow-hidden bg-slate-100 dark:bg-slate-900">
                            <img
                                :src="person.image"
                                :alt="person.name"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-slate-950 dark:text-white">{{ person.name }}</h3>
                            <p class="text-xs font-semibold text-[#58779D] dark:text-[#7094BF]">{{ person.role }}</p>
                            <p class="mt-3 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                {{ person.desc }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

        </div>
    </HomeLayout>
</template>
