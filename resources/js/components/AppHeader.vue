<script setup lang="ts">
import { computed } from 'vue';
import { InertiaLinkProps, Link, usePage } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { getInitials } from '@/composables/useInitials';
import { toUrl, urlIsActive } from '@/lib/utils';
import { index as reservationsIndex } from '@/routes/client/reservations/index';
import { index as supportIndex } from '@/routes/client/support/index';
import { home, fleet } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import {
    Menu,
    FileCheck2,
    LifeBuoy,
    Crown,
    Compass,
    ArrowUpRight,
} from 'lucide-vue-next';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => (page.props as any).auth);

const isCurrentRoute = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        urlIsActive(url, page.url),
);

const mainNavItems = [
    {
        title: 'Reservations',
        href: reservationsIndex(),
        icon: FileCheck2,
    },
    {
        title: 'VIP Loyalty',
        href: '/client/loyalty',
        icon: Crown,
    },
    {
        title: 'Support',
        href: supportIndex(),
        icon: LifeBuoy,
    },
];
</script>

<template>
    <div class="sticky top-0 z-40">
        <!-- Frosted Glass Sticky Header -->
        <header class="border-b border-black/5 bg-white/75 backdrop-blur-2xl transition-colors dark:border-white/10 dark:bg-[#06090E]/80">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                
                <div class="flex items-center gap-6">
                    <!-- Mobile Drawer Menu Trigger -->
                    <div class="lg:hidden">
                        <Sheet>
                            <SheetTrigger :as-child="true">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-9 w-9 rounded-xl border border-black/5 bg-white/50 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200"
                                >
                                    <Menu class="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" class="w-[300px] border-r border-slate-200/80 bg-white p-6 dark:border-white/10 dark:bg-[#06090E]">
                                <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                                <SheetHeader class="flex flex-row items-center gap-3 text-left pb-6 border-b border-slate-100 dark:border-white/10">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#06090E] text-white dark:bg-white dark:text-[#06090E]">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <circle cx="12" cy="12" r="9" class="opacity-20" />
                                            <path d="M12 3v4M12 17v4M3 12h4M17 12h4" />
                                            <circle cx="12" cy="12" r="3" fill="currentColor" fill-opacity="0.3" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-extrabold text-slate-950 dark:text-white">REAL<span class="text-[#58779D] dark:text-[#93B4D7]">RENT</span></p>
                                        <p class="text-[9px] font-mono tracking-widest text-slate-400">CLIENT HUB</p>
                                    </div>
                                </SheetHeader>

                                <div class="mt-6 flex flex-col gap-1.5">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold transition"
                                        :class="isCurrentRoute(item.href)
                                            ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] shadow-xs'
                                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5'"
                                    >
                                        <component :is="item.icon" class="h-4 w-4" />
                                        <span>{{ item.title }}</span>
                                    </Link>

                                    <div class="my-3 border-t border-slate-100 dark:border-white/10"></div>

                                    <Link
                                        :href="fleet()"
                                        class="flex items-center justify-between rounded-xl bg-slate-100 px-3.5 py-2.5 text-xs font-semibold text-slate-900 transition hover:bg-slate-200 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
                                    >
                                        <span class="flex items-center gap-2">
                                            <Compass class="h-4 w-4 text-[#58779D] dark:text-[#93B4D7]" />
                                            <span>Browse Full Fleet</span>
                                        </span>
                                        <ArrowUpRight class="h-3.5 w-3.5 text-slate-400" />
                                    </Link>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    <!-- Luxury Brandmark -->
                    <Link :href="home.url()" class="group flex items-center gap-3 focus:outline-none">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#06090E] text-white shadow-xs ring-1 ring-black/10 transition group-hover:scale-105 dark:bg-white dark:text-[#06090E] dark:ring-white/20">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <circle cx="12" cy="12" r="9" class="opacity-20" />
                                <path d="M12 3v4M12 17v4M3 12h4M17 12h4" />
                                <circle cx="12" cy="12" r="3" fill="currentColor" fill-opacity="0.3" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-extrabold tracking-tight text-slate-950 dark:text-white">
                                REAL<span class="text-[#58779D] dark:text-[#93B4D7]">RENT</span>
                            </p>
                            <p class="text-[8px] font-semibold tracking-[0.22em] text-slate-400">
                                CLIENT PORTAL
                            </p>
                        </div>
                    </Link>

                    <!-- Desktop Nav Items with Active Pill Indicators -->
                    <nav class="hidden items-center gap-1.5 lg:flex">
                        <Link
                            v-for="item in mainNavItems"
                            :key="item.title"
                            :href="item.href"
                            class="flex items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs font-semibold transition"
                            :class="isCurrentRoute(item.href)
                                ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] shadow-xs'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white'"
                        >
                            <component :is="item.icon" class="h-3.5 w-3.5" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </nav>
                </div>

                <!-- Right Controls: Browse Fleet Link, VIP Tier Badge, ThemeToggle, User Menu -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    
                    <!-- Quick Browse Fleet Button -->
                    <Link
                        :href="fleet()"
                        class="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white/70 px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-2xs transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                    >
                        <Compass class="h-3.5 w-3.5 text-[#58779D] dark:text-[#93B4D7]" />
                        <span>Browse Fleet</span>
                    </Link>

                    <!-- VIP Tier Pill Badge -->
                    <Link
                        v-if="auth?.user?.role === 'client'"
                        href="/client/loyalty"
                        class="hidden md:inline-flex items-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-700 transition hover:bg-amber-500/20 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-300"
                    >
                        <Crown class="h-3.5 w-3.5 text-amber-500" />
                        <span class="capitalize">{{ auth.user.loyalty_tier || 'Bronze' }} VIP</span>
                    </Link>

                    <ThemeToggle />

                    <!-- User Menu Dropdown -->
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative h-9 w-9 rounded-full ring-1 ring-slate-200 transition hover:ring-slate-300 dark:ring-white/15 dark:hover:ring-white/30"
                            >
                                <Avatar class="h-8 w-8 overflow-hidden rounded-full">
                                    <AvatarImage
                                        v-if="auth?.user?.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-full bg-[#06090E] text-[11px] font-bold text-white dark:bg-white dark:text-[#06090E]"
                                    >
                                        {{ getInitials(auth?.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56 rounded-2xl border border-slate-200/90 bg-white/95 p-1 shadow-xl backdrop-blur-xl dark:border-white/10 dark:bg-[#0A101D]">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

            </div>
        </header>

        <!-- Breadcrumbs Navigation -->
        <div
            v-if="props.breadcrumbs.length > 1"
            class="border-b border-black/5 bg-slate-50/50 dark:border-white/5 dark:bg-white/[0.01]"
        >
            <div class="mx-auto flex h-10 max-w-7xl items-center px-4 sm:px-6 lg:px-8 text-xs text-slate-500">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
