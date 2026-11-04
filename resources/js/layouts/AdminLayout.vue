<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const message = ref<string | null>(
    (page.props.flash as Record<string, any>).restricted_action ?? null,
);

let timeoutId: ReturnType<typeof setTimeout> | undefined;

watch(
    () => (page.props.flash as Record<string, any>).restricted_action,
    (val) => {
        message.value = val ?? null;

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        if (val) {
            timeoutId = setTimeout(() => {
                message.value = null;
            }, 10000);
        }
    },
);

onBeforeUnmount(() => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
});
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <div
            v-if="message"
            role="alert"
            class="fixed right-4 top-4 z-50 flex max-w-[calc(100vw-2rem)] items-start gap-3 rounded-xl border border-[#C5D0DE] bg-[#101D35] px-4 py-3 text-sm font-medium text-white shadow-2xl sm:right-6 sm:top-6 sm:max-w-md"
        >
            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#263F60]"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-[#C5D0DE]"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L4.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
            </div>

            <div class="min-w-0 flex-1 pt-1">
                <p class="font-semibold text-[#C5D0DE]">
                    Demo Mode Notice
                </p>
                <p class="mt-1 break-words leading-5 text-white/90">
                    {{ message }}
                </p>
            </div>
        </div>

        <slot />
    </AppSidebarLayout>
</template>
