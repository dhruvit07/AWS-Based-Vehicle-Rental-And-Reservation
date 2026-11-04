<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { toUrl, urlIsActive } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editPassword } from '@/routes/password';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { KeyRound, Lock, ShieldCheck, Sparkles, User } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const isClient = computed(() => page.props.auth?.user?.role === 'client');

const sidebarNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Profile',
            href: editProfile(),
            icon: User,
        },
    ];

    if (isClient.value) {
        items.push({
            title: 'Driver License',
            href: '/settings/kyc',
            icon: ShieldCheck,
        });
    }

    items.push(
        {
            title: 'Password',
            href: editPassword(),
            icon: KeyRound,
        },
        {
            title: 'Two-Factor Auth',
            href: show(),
            icon: Lock,
        },
        {
            title: 'Appearance',
            href: editAppearance(),
            icon: Sparkles,
        },
    );

    return items;
});

const currentPath = computed(() => page.url);
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted font-medium text-foreground': urlIsActive(item.href, currentPath) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href" class="flex items-center gap-2">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
