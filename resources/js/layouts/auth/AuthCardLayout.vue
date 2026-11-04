<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
            >
                <div class="flex h-9 w-9 items-center justify-center">
                    <AppLogoIcon
                        class="size-9 fill-current text-[#101D35] dark:text-white"
                    />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <div class="glass-card rounded-3xl p-8 sm:p-10 shadow-xl">
                    <div class="mb-6 text-center">
                        <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ title }}</h1>
                        <p v-if="description" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ description }}
                        </p>
                    </div>
                    <div>
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
