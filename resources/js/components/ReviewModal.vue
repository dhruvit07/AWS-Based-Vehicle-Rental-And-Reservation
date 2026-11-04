<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Calendar, CheckCircle2, ShieldCheck, Star, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ReservationProp {
    id: number;
    reservation_number: string;
    start_date?: string;
    end_date?: string;
    car?: {
        id: number;
        make: string;
        model: string;
        year?: number;
        image_url?: string;
    } | null;
}

const props = defineProps<{
    isOpen: boolean;
    reservation: ReservationProp | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submitted'): void;
}>();

const ratingLabels: Record<number, { text: string; color: string }> = {
    1: { text: 'Needs Improvement', color: 'text-rose-500' },
    2: { text: 'Below Expectations', color: 'text-amber-500' },
    3: { text: 'Standard Experience', color: 'text-amber-500' },
    4: { text: 'Great Drive', color: 'text-emerald-500' },
    5: { text: 'Exceptional Journey', color: 'text-emerald-500' },
};

const form = useForm({
    rating: 5,
    title: '',
    comment: '',
});

const hoveredRating = ref(0);

const activeRating = computed(() => {
    return hoveredRating.value > 0 ? hoveredRating.value : form.rating;
});

const activeLabel = computed(() => {
    return ratingLabels[activeRating.value] || { text: '', color: 'text-slate-500' };
});

const quickTags = [
    'Pristine cleanliness',
    'Quiet cabin',
    'Contactless handover',
    'Superb fuel efficiency',
    'Smooth acceleration',
];

const appendTag = (tag: string) => {
    if (!form.comment.includes(tag)) {
        form.comment = form.comment ? `${form.comment.trim()}. ${tag}` : tag;
    }
};

const formatDate = (dateStr?: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const submitReview = () => {
    if (!props.reservation) return;

    form.post(`/reservations/${props.reservation.id}/reviews`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('submitted');
            emit('close');
        },
    });
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            form.reset();
            form.rating = 5;
            hoveredRating.value = 0;
        }
    }
);
</script>

<template>
    <div
        v-if="isOpen && reservation"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-slate-950/70 backdrop-blur-md transition-opacity"
            @click="!form.processing && emit('close')"
        ></div>

        <!-- Modal Dialog -->
        <div
            class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-black/10 bg-white text-slate-900 shadow-2xl transition-all dark:border-white/10 dark:bg-[#0F1724] dark:text-white"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-black/10 bg-[#101D35] px-6 py-5 text-white dark:border-white/10 dark:bg-[#070A0F]">
                <div>
                    <h3 class="text-base font-bold text-white">Rate Your Journey</h3>
                    <p class="text-xs text-slate-300">Share your impressions with future drivers</p>
                </div>

                <button
                    type="button"
                    :disabled="form.processing"
                    @click="emit('close')"
                    class="rounded-lg p-1.5 text-slate-300 transition hover:bg-white/10 hover:text-white disabled:opacity-50"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Vehicle & Trip Banner -->
            <div class="flex items-center gap-4 border-b border-slate-200/80 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5 sm:px-6">
                <img
                    v-if="reservation.car?.image_url"
                    :src="reservation.car.image_url"
                    :alt="reservation.car.make + ' ' + reservation.car.model"
                    class="h-12 w-16 rounded-xl object-cover ring-1 ring-black/5 dark:ring-white/10"
                />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-bold text-slate-950 dark:text-white">
                        {{ reservation.car ? `${reservation.car.year || ''} ${reservation.car.make} ${reservation.car.model}` : 'Rental Vehicle' }}
                    </p>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1 font-medium text-[#58779D] dark:text-[#7094BF]">
                            <Calendar class="h-3 w-3" />
                            {{ formatDate(reservation.start_date) }} – {{ formatDate(reservation.end_date) }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                            <ShieldCheck class="h-3 w-3" />
                            Completed
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form class="space-y-4 p-6" @submit.prevent="submitReview">
                <!-- Star Rating Selector -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Rating *
                    </label>

                    <div class="mt-2 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div
                            class="flex items-center gap-1.5"
                            role="radiogroup"
                            aria-label="Star Rating"
                            @mouseleave="hoveredRating = 0"
                        >
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                class="rounded-sm p-1 transition-transform hover:scale-110 focus:outline-none"
                                :aria-label="`${star} star${star > 1 ? 's' : ''}`"
                                @mouseenter="hoveredRating = star"
                                @click="form.rating = star"
                            >
                                <Star
                                    class="h-6 w-6 transition-colors duration-150"
                                    :class="[
                                        star <= activeRating
                                            ? 'fill-amber-400 text-amber-400'
                                            : 'fill-transparent text-slate-300 dark:text-slate-700',
                                    ]"
                                />
                            </button>
                        </div>

                        <span
                            class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-white/5"
                            :class="activeLabel.color"
                        >
                            {{ activeRating }} / 5 — {{ activeLabel.text }}
                        </span>
                    </div>

                    <p v-if="form.errors.rating" class="mt-1 text-xs text-rose-500">
                        {{ form.errors.rating }}
                    </p>
                </div>

                <!-- Review Title -->
                <div>
                    <label for="review-title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Title (Optional)
                    </label>
                    <input
                        id="review-title"
                        v-model="form.title"
                        type="text"
                        maxlength="100"
                        placeholder="e.g. Flawless weekend road trip"
                        class="apple-input mt-1.5 w-full px-3.5 py-2 text-xs"
                    />
                </div>

                <!-- Review Comment -->
                <div>
                    <label for="review-comment" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Feedback *
                    </label>
                    <textarea
                        id="review-comment"
                        v-model="form.comment"
                        rows="3"
                        required
                        minlength="5"
                        maxlength="1000"
                        placeholder="Detail the vehicle handover, cabin condition, ride comfort, and overall impressions..."
                        class="apple-input mt-1.5 w-full px-3.5 py-2 text-xs leading-relaxed"
                    ></textarea>
                    <p v-if="form.errors.comment" class="mt-1 text-xs text-rose-500">
                        {{ form.errors.comment }}
                    </p>
                </div>

                <!-- Quick Tags -->
                <div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Quick highlights:</span>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <button
                            v-for="tag in quickTags"
                            :key="tag"
                            type="button"
                            @click="appendTag(tag)"
                            class="rounded-lg border border-slate-200/80 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                        >
                            + {{ tag }}
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="emit('close')"
                        class="rounded-xl px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || form.comment.trim().length < 5"
                        class="apple-btn-primary px-5 py-2 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="form.processing" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                        <span v-else>Publish Review</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
