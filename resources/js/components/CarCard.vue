<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { show } from '@/routes/fleet';
import { Star, Fuel, ArrowRight, ShieldCheck, Navigation } from 'lucide-vue-next';

interface Car {
    id: number;
    make: string;
    model: string;
    year: number;
    price_per_day: string | number;
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
        class="glass-card group flex h-full flex-col overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:hover:border-white/20"
    >
        <!-- 16:9 Staged Vehicle Visual -->
        <div class="relative aspect-16/10 overflow-hidden bg-slate-100 dark:bg-slate-900">
            <img
                :src="car.image_url"
                :alt="`${car.make} ${car.model}`"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
            />

            <div
                class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"
            ></div>

            <!-- Top Specs Badges -->
            <div class="absolute left-3 top-3 flex items-center gap-1.5">
                <span
                    class="rounded-full border border-white/20 bg-black/45 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur-md"
                >
                    {{ car.year }}
                </span>

                <div
                    v-if="car.average_rating && Number(car.average_rating) > 0"
                    class="inline-flex items-center gap-1 rounded-full border border-white/20 bg-black/45 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur-md"
                >
                    <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                    <span>{{ Number(car.average_rating).toFixed(1) }}</span>
                    <span v-if="car.reviews_count" class="font-normal text-white/70">
                        ({{ car.reviews_count }})
                    </span>
                </div>

                <div
                    v-else
                    class="inline-flex items-center gap-1 rounded-full border border-white/20 bg-black/45 px-2.5 py-1 text-[11px] font-medium text-white/80 backdrop-blur-md"
                >
                    <Star class="h-3 w-3 text-amber-300" />
                    <span>New</span>
                </div>
            </div>

            <!-- Frosted Price Tag -->
            <div
                class="absolute bottom-3 right-3 rounded-xl border border-black/5 bg-white/95 px-3 py-1.5 shadow-md backdrop-blur-md dark:border-white/10 dark:bg-[#070A0F]/90"
            >
                <p class="text-base font-bold tracking-tight text-slate-950 dark:text-white">
                    {{ formatPrice(car.price_per_day) }}
                </p>
                <p class="text-right text-[10px] font-medium text-slate-500 dark:text-slate-400">
                    per day
                </p>
            </div>
        </div>

        <!-- Vehicle Details -->
        <div class="flex flex-1 flex-col p-5">
            <div>
                <p
                    class="text-xs font-semibold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]"
                >
                    {{ car.make }}
                </p>

                <h3
                    class="mt-1 text-lg font-bold tracking-tight text-slate-950 transition-colors group-hover:text-[#58779D] dark:text-white dark:group-hover:text-[#7094BF]"
                >
                    {{ car.model }}
                </h3>
            </div>

            <!-- Features Pills -->
            <div class="my-3.5 flex flex-wrap items-center gap-1.5">
                <span
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-slate-100/70 px-2.5 py-1 text-xs font-medium capitalize text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                >
                    <Fuel class="h-3 w-3 text-[#58779D] dark:text-[#7094BF]" />
                    {{ car.fuel_type }}
                </span>

                <span
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-slate-100/70 px-2.5 py-1 text-xs font-medium text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                >
                    <Navigation class="h-3 w-3 text-[#58779D] dark:text-[#7094BF]" />
                    GPS
                </span>

                <span
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-slate-100/70 px-2.5 py-1 text-xs font-medium text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                >
                    <ShieldCheck class="h-3 w-3 text-[#58779D] dark:text-[#7094BF]" />
                    Insured
                </span>
            </div>

            <!-- Description -->
            <p class="mb-5 line-clamp-2 flex-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                {{ car.description || 'Pristine condition, verified mechanics, and sanitized cabin ready for dispatch.' }}
            </p>

            <!-- Reserve Action -->
            <button
                type="button"
                @click="bookCar(car.id)"
                class="apple-btn-primary flex w-full items-center justify-center gap-2 py-2.5 text-xs font-semibold"
            >
                <span>Reserve Vehicle</span>
                <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
            </button>
        </div>
    </article>
</template>
