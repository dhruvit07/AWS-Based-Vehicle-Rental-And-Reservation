<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { login, register, home, fleet, about, contact } from '@/routes';
import { presentation } from '@/routes/demo';
import { index as adminCarsIndex } from '@/routes/admin/cars/index';
import { index as clientReservationsIndex } from '@/routes/client/reservations/index';
import ThemeToggle from '@/components/ThemeToggle.vue';
import DemoPresenterBar from '@/components/DemoPresenterBar.vue';
import {
    LayoutDashboard,
    ArrowRight,
    ArrowUpRight,
    Crown,
    Radio,
    Sparkles,
} from 'lucide-vue-next';

const page = usePage();

const role = computed(() => page.props.auth?.user?.role);

const dashboardLink = computed(() =>
    role.value === 'admin'
        ? adminCarsIndex()
        : clientReservationsIndex()
);

const isActive = (path: string) => {
    if (path === '/') return page.url === '/';
    return page.url === path || page.url.startsWith(path + '/') || page.url.startsWith(path + '?');
};
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FA] text-slate-900 antialiased transition-colors duration-200 dark:bg-[#06090E] dark:text-slate-100">
        <!-- Nordic Frosted Glass Sticky Header -->
        <header class="sticky top-0 z-50 border-b border-black/5 bg-white/75 backdrop-blur-2xl transition-colors dark:border-white/10 dark:bg-[#06090E]/80">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <nav class="flex min-h-[72px] items-center justify-between gap-4">

                    <!-- Luxury Brandmark (Nordic Precision / Polestar Aesthetic) -->
                    <Link :href="home()" class="group flex shrink-0 items-center gap-3 focus:outline-none">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#06090E] text-white shadow-sm ring-1 ring-black/10 transition-transform duration-300 group-hover:scale-105 dark:bg-white dark:text-[#06090E] dark:ring-white/20">
                            <!-- Geometric Crosshair Emblem -->
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <circle cx="12" cy="12" r="9" class="opacity-20" />
                                <path d="M12 3v4M12 17v4M3 12h4M17 12h4" />
                                <circle cx="12" cy="12" r="3" fill="currentColor" fill-opacity="0.3" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-base font-extrabold tracking-tight text-slate-950 dark:text-white">
                                REAL<span class="text-[#58779D] dark:text-[#93B4D7]">RENT</span>
                            </p>
                            <p class="text-[9px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-400">
                                NORDIC MOBILITY
                            </p>
                        </div>
                    </Link>

                    <!-- Desktop Navigation Links with Active Indicators -->
                    <div class="hidden items-center gap-1.5 lg:flex">
                        <Link
                            :href="home()"
                            :class="isActive('/')
                                ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="relative rounded-xl px-3.5 py-2 text-sm transition-all"
                        >
                            Home
                        </Link>

                        <Link
                            :href="fleet()"
                            :class="isActive('/fleet')
                                ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="relative rounded-xl px-3.5 py-2 text-sm transition-all"
                        >
                            Fleet
                        </Link>

                        <Link
                            :href="about()"
                            :class="isActive('/about')
                                ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="relative rounded-xl px-3.5 py-2 text-sm transition-all"
                        >
                            About
                        </Link>

                        <Link
                            :href="contact()"
                            :class="isActive('/contact')
                                ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="relative rounded-xl px-3.5 py-2 text-sm transition-all"
                        >
                            Contact
                        </Link>

                        <Link
                            href="/client/loyalty"
                            :class="isActive('/client/loyalty')
                                ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="relative flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm transition-all"
                        >
                            <Crown class="h-3.5 w-3.5 text-amber-500" />
                            <span>VIP Loyalty</span>
                        </Link>

                        <Link
                            :href="presentation()"
                            :class="isActive('/presentation')
                                ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="relative flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm transition-all"
                        >
                            <Sparkles class="h-3.5 w-3.5 text-[#58779D] dark:text-[#93B4D7]" />
                            <span>Presentation</span>
                        </Link>
                    </div>

                    <!-- Right Controls: Theme Toggle & Authentication -->
                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <ThemeToggle />

                        <Link
                            v-if="page.props.auth?.user?.role === 'client'"
                            href="/client/loyalty"
                            class="hidden items-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-700 transition hover:bg-amber-500/20 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-300 sm:inline-flex"
                        >
                            <Crown class="h-3.5 w-3.5 text-amber-500" />
                            <span class="capitalize">{{ page.props.auth.user.loyalty_tier || 'Bronze' }} VIP</span>
                        </Link>

                        <Link
                            v-if="page.props.auth?.user"
                            :href="dashboardLink"
                            class="inline-flex items-center gap-2 rounded-xl border border-black/10 bg-white/90 px-3.5 py-2 text-xs font-semibold text-slate-900 shadow-xs transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15 sm:text-sm"
                        >
                            <LayoutDashboard class="h-4 w-4" />
                            <span>Dashboard</span>
                        </Link>

                        <template v-else>
                            <Link
                                :href="login()"
                                class="rounded-xl px-3.5 py-2 text-xs font-medium text-slate-700 transition hover:bg-black/5 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white sm:text-sm"
                            >
                                Sign In
                            </Link>

                            <Link
                                :href="register()"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-[#06090E] px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#152238] active:scale-[0.98] dark:bg-white dark:text-[#06090E] dark:hover:bg-slate-200 sm:text-sm"
                            >
                                <span>Get Started</span>
                                <ArrowRight class="h-3.5 w-3.5" />
                            </Link>
                        </template>
                    </div>
                </nav>

                <!-- Mobile Navigation Bar -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-3 pt-1 scrollbar-none lg:hidden">
                    <Link
                        :href="home()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/') ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        Home
                    </Link>
                    <Link
                        :href="fleet()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/fleet') ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        Fleet
                    </Link>
                    <Link
                        :href="about()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/about') ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        About
                    </Link>
                    <Link
                        :href="contact()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/contact') ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        Contact
                    </Link>
                    <Link
                        href="/client/loyalty"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/client/loyalty') ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        VIP Loyalty
                    </Link>
                    <Link
                        :href="presentation()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/presentation') ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-semibold' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        Presentation
                    </Link>
                </div>
            </div>
        </header>

        <!-- Current Page Content -->
        <main class="min-h-[70vh]">
            <slot />
        </main>

        <!-- Luxury Dark Obsidian Footer -->
        <footer class="border-t border-white/10 bg-[#06090E] text-slate-300">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">

                <!-- Operational Status Pill (Fleet Radar) -->
                <div class="mb-14 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4.5 backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                        <div class="flex flex-wrap items-center gap-2 text-xs font-medium tracking-wide text-slate-200">
                            <span class="text-white font-semibold">Fleet Radar:</span>
                            <span class="text-emerald-400 font-semibold">100% Operational</span>
                            <span class="text-slate-500">•</span>
                            <span class="text-slate-300">All Hubs Live</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-5 text-xs font-mono text-slate-400">
                        <span class="hidden md:inline">Hub Network: BOM • DEL • BLR • GOI</span>
                        <Link :href="presentation()" class="inline-flex items-center gap-1.5 text-[#93B4D7] hover:text-white transition">
                            <span>Evaluator Presentation</span>
                            <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Columnar Sitemap Grid -->
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">

                    <!-- Column 1: Brandmark & Philosophy -->
                    <div class="space-y-4">
                        <Link :href="home()" class="inline-flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <circle cx="12" cy="12" r="9" class="opacity-20" />
                                    <path d="M12 3v4M12 17v4M3 12h4M17 12h4" />
                                    <circle cx="12" cy="12" r="3" fill="currentColor" fill-opacity="0.3" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold tracking-tight text-white">
                                    REAL<span class="text-[#93B4D7]">RENT</span>
                                </h2>
                                <p class="text-[9px] font-semibold tracking-[0.22em] text-slate-400">NORDIC PRECISION</p>
                            </div>
                        </Link>

                        <p class="max-w-xs text-xs leading-relaxed text-slate-400">
                            Next-generation automotive rental platform engineered with collision-free atomic locking, interactive PDI condition blueprints, and verified digital handovers.
                        </p>

                        <div class="pt-2 text-[11px] font-mono text-slate-400">
                            Engineered with Laravel 12 & Inertia.js 2
                        </div>
                    </div>

                    <!-- Column 2: Fleet & Categories -->
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-200">
                            Fleet Directory
                        </h3>
                        <ul class="mt-4 space-y-2.5 text-xs text-slate-400">
                            <li><Link :href="fleet()" class="transition hover:text-white">All Vehicles</Link></li>
                            <li><Link :href="fleet({ query: { fuel_type: 'Electric' } })" class="transition hover:text-white">Pure Electric (EV)</Link></li>
                            <li><Link :href="fleet({ query: { sort: 'rating_desc' } })" class="transition hover:text-white">Top-Rated Sedans</Link></li>
                            <li><Link :href="fleet({ query: { sort: 'price_desc' } })" class="transition hover:text-white">Performance SUVs</Link></li>
                            <li><Link :href="fleet({ query: { fuel_type: 'Petrol' } })" class="transition hover:text-white">Grand Tourers</Link></li>
                        </ul>
                    </div>

                    <!-- Column 3: Platform Architecture -->
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-200">
                            Architecture
                        </h3>
                        <ul class="mt-4 space-y-2.5 text-xs text-slate-400">
                            <li><span class="text-slate-300">Phase 3:</span> Atomic Date Locking</li>
                            <li><span class="text-slate-300">Phase 9a:</span> PDI SVG Blueprint Audit</li>
                            <li><Link href="/client/loyalty" class="transition hover:text-white"><span class="text-slate-300">Phase 10a:</span> VIP Tier Stacking</Link></li>
                            <li><span class="text-slate-300">Phase 8:</span> QR Mobile Gate Pass</li>
                            <li><Link :href="presentation()" class="transition hover:text-white"><span class="text-slate-300">Viva Mode:</span> Academic Presenter</Link></li>
                        </ul>
                    </div>

                    <!-- Column 4: Concierge & Support -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-200">
                            Concierge Desk
                        </h3>
                        <p class="text-xs leading-relaxed text-slate-400">
                            Airport hub handovers, bespoke corporate itineraries, and live trip extensions handled seamlessly.
                        </p>
                        <div class="flex flex-col gap-2 pt-1">
                            <Link
                                :href="contact()"
                                class="inline-flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-xs font-semibold text-white transition hover:border-white/20 hover:bg-white/10"
                            >
                                <span>Contact Concierge Desk</span>
                                <ArrowRight class="h-3.5 w-3.5" />
                            </Link>
                            <Link
                                :href="presentation()"
                                class="inline-flex items-center justify-between rounded-xl border border-[#93B4D7]/20 bg-[#93B4D7]/10 px-3.5 py-2.5 text-xs font-semibold text-[#93B4D7] transition hover:border-[#93B4D7]/40 hover:bg-[#93B4D7]/20"
                            >
                                <span>Evaluator Presentation</span>
                                <Radio class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="mt-14 flex flex-col gap-4 border-t border-white/10 pt-8 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ new Date().getFullYear() }} Real Rent Car. Nordic Precision Mobility Systems.</p>
                    <div class="flex items-center gap-6">
                        <Link :href="fleet()" class="hover:text-slate-300">Fleet Terms</Link>
                        <Link :href="about()" class="hover:text-slate-300">Security & KYC</Link>
                        <Link :href="presentation()" class="hover:text-slate-300">Evaluator Radar</Link>
                    </div>
                </div>
            </div>
        </footer>

        <DemoPresenterBar />
    </div>
</template>
