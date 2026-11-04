<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

interface Props {
    compact?: boolean;
}

defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light' as const, icon: Sun, label: 'Light mode' },
    { value: 'dark' as const, icon: Moon, label: 'Dark mode' },
    { value: 'system' as const, icon: Monitor, label: 'System theme' },
];
</script>

<template>
    <div
        class="inline-flex items-center rounded-full border border-black/5 bg-slate-200/60 p-0.5 backdrop-blur-md transition-colors dark:border-white/10 dark:bg-white/5"
        role="group"
        aria-label="Theme preference"
    >
        <button
            v-for="opt in options"
            :key="opt.value"
            type="button"
            :aria-label="opt.label"
            :title="opt.label"
            @click="updateAppearance(opt.value)"
            class="relative flex items-center justify-center rounded-full p-1.5 transition-all duration-200 active:scale-95"
            :class="[
                appearance === opt.value
                    ? 'bg-white text-slate-900 shadow-xs dark:bg-white/20 dark:text-white'
                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            ]"
        >
            <component :is="opt.icon" class="h-3.5 w-3.5" stroke-width="2.2" />
        </button>
    </div>
</template>
