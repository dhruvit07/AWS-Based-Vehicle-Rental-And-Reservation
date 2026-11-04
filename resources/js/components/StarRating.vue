<script setup lang="ts">
import { Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    modelValue?: number;
    rating?: number;
    max?: number;
    readonly?: boolean;
    size?: 'xs' | 'sm' | 'md' | 'lg';
    showValue?: boolean;
    reviewsCount?: number;
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: 0,
    rating: 0,
    max: 5,
    readonly: false,
    size: 'md',
    showValue: false,
    reviewsCount: undefined,
    compact: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
    (e: 'change', value: number): void;
}>();

const hoverRating = ref(0);

const currentRating = computed(() => {
    return props.modelValue || props.rating || 0;
});

const activeRating = computed(() => {
    if (!props.readonly && hoverRating.value > 0) {
        return hoverRating.value;
    }
    return currentRating.value;
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'h-3.5 w-3.5';
        case 'sm':
            return 'h-4 w-4';
        case 'lg':
            return 'h-6 w-6';
        case 'md':
        default:
            return 'h-5 w-5';
    }
});

const handleMouseEnter = (star: number) => {
    if (!props.readonly) {
        hoverRating.value = star;
    }
};

const handleMouseLeave = () => {
    if (!props.readonly) {
        hoverRating.value = 0;
    }
};

const handleClick = (star: number) => {
    if (!props.readonly) {
        emit('update:modelValue', star);
        emit('change', star);
    }
};

const isStarFilled = (starIndex: number) => {
    return starIndex <= Math.round(activeRating.value);
};
</script>

<template>
    <!-- Compact badge mode: e.g. ★ 4.8 (12) -->
    <div
        v-if="compact"
        class="inline-flex items-center gap-1.5 font-medium"
        :class="size === 'sm' || size === 'xs' ? 'text-xs' : 'text-sm'"
    >
        <Star
            class="fill-amber-400 text-amber-500"
            :class="sizeClasses"
        />
        <span class="font-bold text-[#101D35] dark:text-slate-100">
            {{ currentRating > 0 ? currentRating.toFixed(1) : 'New' }}
        </span>
        <span
            v-if="reviewsCount !== undefined && reviewsCount > 0"
            class="text-slate-500 dark:text-slate-400"
        >
            ({{ reviewsCount }})
        </span>
    </div>

    <!-- Standard multi-star view / interactive picker -->
    <div
        v-else
        class="inline-flex items-center gap-1"
        :role="readonly ? 'img' : 'radiogroup'"
        :aria-label="`${currentRating} out of ${max} stars`"
    >
        <div class="flex items-center gap-1" @mouseleave="handleMouseLeave">
            <button
                v-for="star in max"
                :key="star"
                type="button"
                :disabled="readonly"
                :tabindex="readonly ? -1 : 0"
                class="transition-transform focus:outline-none"
                :class="[
                    readonly ? 'cursor-default' : 'cursor-pointer hover:scale-110 focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1 rounded-xs',
                ]"
                :aria-label="`${star} star${star > 1 ? 's' : ''}`"
                @mouseenter="handleMouseEnter(star)"
                @click="handleClick(star)"
                @keydown.enter.prevent="handleClick(star)"
                @keydown.space.prevent="handleClick(star)"
            >
                <Star
                    :class="[
                        sizeClasses,
                        'transition-colors duration-150',
                        isStarFilled(star)
                            ? 'fill-amber-400 text-amber-500'
                            : 'fill-transparent text-slate-300 dark:text-slate-600',
                    ]"
                />
            </button>
        </div>

        <span
            v-if="showValue && currentRating > 0"
            class="ml-1 text-sm font-semibold text-[#101D35] dark:text-slate-100"
        >
            {{ currentRating.toFixed(1) }}
        </span>

        <span
            v-if="reviewsCount !== undefined && reviewsCount > 0"
            class="ml-1 text-xs text-slate-500 dark:text-slate-400"
        >
            ({{ reviewsCount }} {{ reviewsCount === 1 ? 'review' : 'reviews' }})
        </span>
    </div>
</template>
