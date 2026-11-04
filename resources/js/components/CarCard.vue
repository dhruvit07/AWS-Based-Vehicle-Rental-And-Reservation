
<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { show } from '@/routes/fleet';
import { Star } from 'lucide-vue-next';

interface Car {
    id: number;
    make: string;
    model: string;
    year: number;
    price_per_day: string;
    description: string;
    fuel_type: string;
    image_url: string;
    average_rating?: number;
    reviews_count?: number;
}

interface Props {
    car: Car;
}

defineProps<Props>();

const bookCar = (carId: number) => {
    router.get(show(carId).url);
};

const formatPrice = (price: string | number) => {
    const amount = Number(price);

    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <article
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl"
    >
        <!-- Car image -->
        <div class="relative h-56 overflow-hidden bg-slate-100">
            <img
                :src="car.image_url"
                :alt="`${car.make} ${car.model}`"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
            />

            <div
                class="absolute inset-0 bg-gradient-to-t from-[#101D35]/50 via-transparent to-transparent"
            ></div>

            <div class="absolute left-4 top-4 flex items-center gap-2">
                <div
                    class="rounded-lg border border-white/30 bg-[#101D35]/90 px-3 py-1.5 text-xs font-semibold tracking-wide text-white backdrop-blur-sm"
                >
                    {{ car.year }} MODEL
                </div>

                <div
                    v-if="car.average_rating && Number(car.average_rating) > 0"
                    class="inline-flex items-center gap-1 rounded-lg border border-white/20 bg-black/60 px-2.5 py-1.5 text-xs font-bold text-white shadow-xs backdrop-blur-sm"
                >
                    <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                    <span>{{ Number(car.average_rating).toFixed(1) }}</span>
                    <span v-if="car.reviews_count" class="font-normal text-white/75">
                        ({{ car.reviews_count }})
                    </span>
                </div>

                <div
                    v-else
                    class="inline-flex items-center gap-1 rounded-lg border border-white/20 bg-black/60 px-2.5 py-1.5 text-xs font-medium text-white/90 backdrop-blur-sm"
                >
                    <Star class="h-3.5 w-3.5 text-amber-300" />
                    <span>New</span>
                </div>
            </div>

            <div
                class="absolute bottom-4 right-4 rounded-xl bg-white px-4 py-2.5 shadow-lg"
            >
                <p class="text-lg font-bold text-[#101D35]">
                    {{ formatPrice(car.price_per_day) }}
                </p>
                <p class="text-right text-xs font-medium text-slate-500">
                    per day
                </p>
            </div>
        </div>

        <!-- Car details -->
        <div class="flex flex-1 flex-col p-5">
            <div class="mb-3">
                <p
                    class="mb-1 text-xs font-semibold uppercase tracking-[0.15em] text-[#58779D]"
                >
                    Premium rental
                </p>

                <h3
                    class="text-xl font-bold text-[#101D35] transition-colors group-hover:text-[#58779D]"
                >
                    {{ car.make }} {{ car.model }}
                </h3>
            </div>

            <!-- Fuel type -->
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium capitalize text-slate-700"
                >
                    <svg
                        class="h-4 w-4 text-[#58779D]"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"
                        />
                    </svg>
                    {{ car.fuel_type }}
                </span>

                <span
                    class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600"
                >
                    GPS included
                </span>

                <span
                    class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600"
                >
                    Insurance included
                </span>
            </div>

            <!-- Description -->
            <p class="mb-5 line-clamp-2 flex-1 text-sm leading-6 text-slate-600">
                {{ car.description || 'Enjoy a comfortable and convenient journey with this rental vehicle.' }}
            </p>

            <!-- Booking button -->
            <button
                type="button"
                @click="bookCar(car.id)"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D35] px-5 py-3.5 font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[#263F60] focus:outline-none focus:ring-2 focus:ring-[#58779D] focus:ring-offset-2"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
                Book This Car
                <svg
                    class="h-4 w-4 transition-transform group-hover:translate-x-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 12h14m-7-7 7 7-7 7"
                    />
                </svg>
            </button>
        </div>
    </article>
</template>
