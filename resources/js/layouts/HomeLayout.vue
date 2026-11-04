<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { login, register } from '@/routes';
import { index as adminCarsIndex } from '@/routes/admin/cars/index';
import { index as clientReservationsIndex } from '@/routes/client/reservations/index';
import { home, fleet, about, contact } from '@/routes';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Car, LayoutDashboard, ArrowRight, Phone, Mail, Compass } from 'lucide-vue-next';

const page = usePage();

const role = computed(() => page.props.auth?.user?.role);

const dashboardLink = computed(() =>
    role.value === 'admin'
        ? adminCarsIndex()
        : clientReservationsIndex()
);

const isActive = (path: string) => {
    if (path === '/') return page.url === '/';
    return page.url === path || page.url.startsWith(path + '/');
};
</script>

<template>
    <div class="min-h-screen bg-[#FBFBFD] text-slate-900 antialiased transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100">
        <!-- Navigation -->
        <header class="glass-nav">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <nav class="flex min-h-[72px] items-center justify-between gap-4">

                    <!-- Logo -->
                    <Link :href="home()" class="group flex shrink-0 items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#101D35] text-white shadow-sm transition-transform duration-200 group-hover:scale-105 dark:bg-white dark:text-slate-950">
                            <Car class="h-5 w-5" stroke-width="2" />
                        </div>
                        <div>
                            <p class="text-base font-bold tracking-tight text-slate-950 dark:text-white">
                                REAL<span class="text-[#58779D] dark:text-[#7094BF]">RENT</span>
                            </p>
                            <p class="text-[9px] font-semibold tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                PREMIUM MOBILITY
                            </p>
                        </div>
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden items-center gap-1.5 lg:flex">
                        <Link
                            :href="home()"
                            :class="isActive('/') ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="rounded-xl px-4 py-2 text-sm transition-all"
                        >
                            Home
                        </Link>

                        <Link
                            :href="fleet()"
                            :class="isActive('/fleet') ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="rounded-xl px-4 py-2 text-sm transition-all"
                        >
                            Our Fleet
                        </Link>

                        <Link
                            :href="about()"
                            :class="isActive('/about') ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="rounded-xl px-4 py-2 text-sm transition-all"
                        >
                            About Us
                        </Link>

                        <Link
                            :href="contact()"
                            :class="isActive('/contact') ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5 font-medium'"
                            class="rounded-xl px-4 py-2 text-sm transition-all"
                        >
                            Contact
                        </Link>
                    </div>

                    <!-- Right Controls: Theme Toggle & Authentication -->
                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <ThemeToggle />

                        <Link
                            v-if="page.props.auth?.user"
                            :href="dashboardLink"
                            class="inline-flex items-center gap-2 rounded-xl border border-black/10 bg-white/80 px-3 py-2 text-xs font-semibold text-slate-900 shadow-xs transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 sm:px-4 sm:text-sm"
                        >
                            <LayoutDashboard class="h-4 w-4" />
                            <span class="hidden sm:inline">Dashboard</span>
                        </Link>

                        <template v-else>
                            <Link
                                :href="login()"
                                class="rounded-xl px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-black/5 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white sm:px-4 sm:text-sm"
                            >
                                Sign In
                            </Link>

                            <Link
                                :href="register()"
                                class="apple-btn-primary px-3.5 py-2 text-xs font-semibold sm:px-4 sm:text-sm"
                            >
                                <span>Get Started</span>
                            </Link>
                        </template>
                    </div>
                </nav>

                <!-- Mobile Navigation Bar -->
                <div class="flex items-center gap-2 overflow-x-auto pb-3 pt-1 lg:hidden">
                    <Link
                        :href="home()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/') ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        Home
                    </Link>
                    <Link
                        :href="fleet()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/fleet') ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        Our Fleet
                    </Link>
                    <Link
                        :href="about()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/about') ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        About
                    </Link>
                    <Link
                        :href="contact()"
                        class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="isActive('/contact') ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950' : 'bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300'"
                    >
                        Contact
                    </Link>
                </div>
            </div>
        </header>

        <!-- Current Page Content -->
        <main class="min-h-[60vh]">
            <slot />
        </main>

        <!-- Obsidian Dark Footer -->
        <footer class="border-t border-white/10 bg-[#070A0F] text-slate-300">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid gap-12 sm:grid-cols-2 lg:grid-cols-4">

                    <!-- Brand Column -->
                    <div class="space-y-4">
                        <Link :href="home()" class="inline-flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white">
                                <Car class="h-5 w-5" stroke-width="2" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold tracking-tight text-white">
                                    REAL<span class="text-[#7094BF]">RENT</span>
                                </h2>
                                <p class="text-[9px] font-semibold tracking-[0.24em] text-slate-400">PREMIUM MOBILITY</p>
                            </div>
                        </Link>

                        <p class="max-w-xs text-sm leading-relaxed text-slate-400">
                            Pioneering luxury automotive rental with transparent terms, frictionless booking, and concierge-level service.
                        </p>
                    </div>

                    <!-- Navigation Links -->
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-200">
                            Explore
                        </h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-400">
                            <li><Link :href="home()" class="transition hover:text-white">Home</Link></li>
                            <li><Link :href="fleet()" class="transition hover:text-white">Browse Fleet</Link></li>
                            <li><Link :href="about()" class="transition hover:text-white">About Us</Link></li>
                            <li><Link :href="contact()" class="transition hover:text-white">Contact & Support</Link></li>
                        </ul>
                    </div>

                    <!-- Services -->
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-200">
                            Experience
                        </h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-400">
                            <li>Pre-Inspected Fleet</li>
                            <li>Zero Hidden Fees</li>
                            <li>Instant Overlap Verification</li>
                            <li>24/7 Roadside Concierge</li>
                        </ul>
                    </div>

                    <!-- Direct Contact -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-200">
                            Assistance
                        </h3>
                        <p class="text-sm leading-relaxed text-slate-400">
                            Have questions or need bespoke booking arrangements? Our team is available 24/7.
                        </p>
                        <Link
                            :href="contact()"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-white/10 hover:border-white/20"
                        >
                            <span>Speak to Concierge</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="mt-14 flex flex-col gap-4 border-t border-white/10 pt-8 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ new Date().getFullYear() }} Real Rent Car. Calibrated for effortless mobility.</p>
                    <div class="flex items-center gap-6">
                        <Link :href="fleet()" class="hover:text-slate-300">Fleet Terms</Link>
                        <Link :href="about()" class="hover:text-slate-300">Security & Privacy</Link>
                        <Link :href="contact()" class="hover:text-slate-300">Help Desk</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
