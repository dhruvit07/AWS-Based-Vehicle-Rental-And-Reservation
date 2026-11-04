<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, X } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

interface ToastMessage {
    type: 'success' | 'error';
    text: string;
}

const toast = ref<ToastMessage | null>(null);
let timeoutId: ReturnType<typeof setTimeout> | undefined;

const extractToast = (): ToastMessage | null => {
    const flash = (page.props.flash as Record<string, any>) || {};
    if (flash.success) {
        return { type: 'success', text: String(flash.success) };
    }
    if (flash.error) {
        return { type: 'error', text: String(flash.error) };
    }
    if (flash.status) {
        return { type: 'success', text: String(flash.status) };
    }
    return null;
};

// Initial check
toast.value = extractToast();
if (toast.value) {
    timeoutId = setTimeout(() => {
        toast.value = null;
    }, 6000);
}

watch(
    () => page.props.flash,
    () => {
        const nextToast = extractToast();
        if (nextToast) {
            toast.value = nextToast;
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
            timeoutId = setTimeout(() => {
                toast.value = null;
            }, 6000);
        }
    },
    { deep: true },
);

const dismiss = () => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    toast.value = null;
};

onBeforeUnmount(() => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
});
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toast"
                role="status"
                aria-live="polite"
                class="fixed right-4 top-4 z-50 flex max-w-[calc(100vw-2rem)] items-start gap-3 rounded-xl border bg-[#101D35] p-4 text-white shadow-2xl sm:right-6 sm:top-6 sm:max-w-md"
                :class="toast.type === 'error' ? 'border-rose-500/40 shadow-rose-950/20' : 'border-[#58779D]/40 shadow-slate-950/30'"
            >
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                    :class="toast.type === 'error' ? 'bg-rose-500/20 text-rose-300' : 'bg-emerald-500/20 text-emerald-300'"
                >
                    <CheckCircle2 v-if="toast.type === 'success'" class="h-4 w-4" />
                    <AlertCircle v-else class="h-4 w-4" />
                </div>

                <div class="min-w-0 flex-1 pt-0.5">
                    <p
                        class="text-xs font-semibold uppercase tracking-wider"
                        :class="toast.type === 'error' ? 'text-rose-300' : 'text-[#C5D0DE]'"
                    >
                        {{ toast.type === 'error' ? 'Error' : 'Success' }}
                    </p>
                    <p class="mt-0.5 text-sm font-medium leading-snug text-white/95 break-words">
                        {{ toast.text }}
                    </p>
                </div>

                <button
                    type="button"
                    class="shrink-0 rounded-md p-1 text-[#C5D0DE] opacity-80 transition hover:bg-white/10 hover:text-white hover:opacity-100 focus:outline-none"
                    aria-label="Dismiss notification"
                    @click="dismiss"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </Transition>

        <slot />
    </AppSidebarLayout>
</template>
