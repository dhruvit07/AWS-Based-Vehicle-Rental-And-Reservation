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
    1: { text: 'Terrible', color: 'text-rose-600' },
    2: { text: 'Poor', color: 'text-amber-700' },
    3: { text: 'Average', color: 'text-amber-600' },
    4: { text: 'Great', color: 'text-emerald-700' },
    5: { text: 'Exceptional!', color: 'text-emerald-600' },
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
    'Whisper quiet drive',
    'Contactless key handover',
    'Great fuel efficiency',
    'Spacious boot',
];

const addQuickTag = (tag: string) => {
    if (!form.comment) {
        form.comment = tag + '. ';
    } else if (!form.comment.includes(tag)) {
        form.comment = `${form.comment.trim()} ${tag}. `;
    }
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            form.reset();
            form.rating = 5;
            form.clearErrors();
            hoveredRating.value = 0;
        }
    }
);

const formatDate = (date?: string) => {
    if (!date) return '';
    const parsed = new Date(date.slice(0, 10) + 'T00:00:00');
    return Number.isNaN(parsed.getTime())
        ? date
        : new Intl.DateTimeFormat('en-IN', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(parsed);
};

const submitReview = () => {
    if (!props.reservation) return;

    form.post(`/client/reservations/${props.reservation.id}/reviews`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('submitted');
            emit('close');
        },
    });
};
</script>

<template>
    <div
        v-if="isOpen && reservation"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-[#101D35]/70 backdrop-blur-xs transition-opacity"
            @click="emit('close')"
        ></div>

        <!-- Modal Card -->
        <div
            class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl transition-all"
            role="dialog"
            aria-modal="true"
            aria-labelledby="review-modal-title"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-200 bg-[#101D35] px-6 py-4 text-white">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-amber-400">
                        <Star class="h-5 w-5 fill-amber-400" />
                    </div>
                    <div>
                        <h2 id="review-modal-title" class="text-base font-bold text-white sm:text-lg">
                            Rate Your Trip Experience
                        </h2>
                        <p class="text-xs text-slate-300">
                            Verified Renter Review • {{ reservation.reservation_number }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-300 transition hover:bg-white/10 hover:text-white focus:outline-none"
                    @click="emit('close')"
                >
                    <X class="h-5 w-5" />
                    <span class="sr-only">Close</span>
                </button>
            </div>

            <!-- Vehicle & Trip Banner -->
            <div class="flex items-center gap-4 border-b border-slate-100 bg-[#F4F6FA] p-4 sm:px-6">
                <img
                    v-if="reservation.car?.image_url"
                    :src="reservation.car.image_url"
                    :alt="reservation.car.make + ' ' + reservation.car.model"
                    class="h-14 w-20 rounded-xl object-cover ring-1 ring-slate-200"
                />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-[#101D35]">
                        {{ reservation.car ? `${reservation.car.year || ''} ${reservation.car.make} ${reservation.car.model}` : 'Rental Vehicle' }}
                    </p>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1 font-medium text-[#58779D]">
                            <Calendar class="h-3.5 w-3.5" />
                            {{ formatDate(reservation.start_date) }} – {{ formatDate(reservation.end_date) }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100/80 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                            <ShieldCheck class="h-3 w-3" />
                            Completed
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form class="space-y-5 p-6" @submit.prevent="submitReview">
                <!-- Star Rating Selector -->
                <div class="text-center sm:text-left">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#58779D]">
                        Overall Rating *
                    </label>

                    <div class="mt-2.5 flex flex-col items-center gap-2 sm:flex-row sm:justify-between">
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
                                class="rounded-sm p-1 transition-transform hover:scale-120 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                                :aria-label="`${star} star${star > 1 ? 's' : ''}`"
                                @mouseenter="hoveredRating = star"
                                @click="form.rating = star"
                            >
                                <Star
                                    class="h-7 w-7 transition-colors duration-150"
                                    :class="[
                                        star <= activeRating
                                            ? 'fill-amber-400 text-amber-500'
                                            : 'fill-transparent text-slate-200',
                                    ]"
                                />
                            </button>
                        </div>

                        <span
                            class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-bold transition-colors"
                            :class="activeLabel.color"
                        >
                            {{ activeRating }} / 5 — {{ activeLabel.text }}
                        </span>
                    </div>

                    <p v-if="form.errors.rating" class="mt-1 text-xs text-rose-600">
                        {{ form.errors.rating }}
                    </p>
                </div>

                <!-- Review Title -->
                <div>
                    <label for="review-title" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Review Title
                        <span class="text-slate-400 font-normal lowercase">(optional)</span>
                    </label>
                    <input
                        id="review-title"
                        v-model="form.title"
                        type="text"
                        maxlength="100"
                        placeholder="e.g. Exceptional weekend road trip!"
                        class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-[#101D35] placeholder:text-slate-400 focus:border-[#58779D] focus:outline-none focus:ring-2 focus:ring-[#58779D]/20"
                        :class="{ 'border-rose-400': form.errors.title }"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">
                        {{ form.errors.title }}
                    </p>
                </div>

                <!-- Review Comment -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="review-comment" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Your Experience *
                        </label>
                        <span class="text-xs text-slate-400">
                            {{ form.comment.length }} / 1000
                        </span>
                    </div>

                    <textarea
                        id="review-comment"
                        v-model="form.comment"
                        rows="4"
                        maxlength="1000"
                        required
                        placeholder="Describe the vehicle cleanliness, handover, driving feel, and comfort..."
                        class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white p-3.5 text-sm text-[#101D35] placeholder:text-slate-400 focus:border-[#58779D] focus:outline-none focus:ring-2 focus:ring-[#58779D]/20"
                        :class="{ 'border-rose-400': form.errors.comment }"
                    ></textarea>
                    <p v-if="form.errors.comment" class="mt-1 text-xs text-rose-600">
                        {{ form.errors.comment }}
                    </p>

                    <!-- Quick Suggestions -->
                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                        <button
                            v-for="tag in quickTags"
                            :key="tag"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-100"
                            @click="addQuickTag(tag)"
                        >
                            + {{ tag }}
                        </button>
                    </div>
                </div>

                <!-- Error notice from backend -->
                <div v-if="(form.errors as any).reservation" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
                    {{ (form.errors as any).reservation }}
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing || form.comment.trim().length < 5"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#101D35] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#263F60] focus:outline-none focus:ring-2 focus:ring-[#58779D] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"
                    >
                        <CheckCircle2 v-if="!form.processing" class="h-4 w-4" />
                        <span>{{ form.processing ? 'Submitting...' : 'Post Verified Review' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
