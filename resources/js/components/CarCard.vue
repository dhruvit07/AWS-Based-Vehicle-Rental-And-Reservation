<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { show } from '@/routes/fleet';
import {
    Star,
    Fuel,
    Zap,
    ArrowRight,
    ShieldCheck,
    Gauge,
    Users,
    Briefcase,
} from 'lucide-vue-next';

interface Car {
    id: number;
    make: string;
    model: string;
    year: number;
    price_per_day: string | number;
    description: string;
    fuel_type: string;
    image_url: string;
    transmission?: string;
    seats?: number;
    average_rating?: number;
    reviews_count?: number;
}

interface Props {
    car: Car;
}

const props = defineProps<Props>();

const bookCar = (carId: number) => {
    router.get(show(carId).url);
};

const formatPrice = (price: string | number) => {
    const amount = Number(price);
    return Number.isFinite(amount)
        ? new Intl.NumberFormat('en-IN', {
              maximumFractionDigits: 0,
          }).format(amount)
        : String(price);
};
</script>

<template>
    <article
        class="group relative flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl dark:border-white/10 dark:bg-[#0A101D] dark:hover:border-white/25"
    >
        <!-- 16:9 Widescreen Vehicle Staging with Subtle Ambient Rim Light -->
        <div class="relative aspect-16/10 overflow-hidden bg-slate-950">
            <img
                :src="car.image_url"
                :alt="`${car.make} ${car.model}`"
                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                loading="lazy"
            />

            <!-- Subtle dark vignette -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

            <!-- Top Badges Ribbon -->
            <div class="absolute left-3.5 top-3.5 right-3.5 flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                    <span class="rounded-full border border-white/20 bg-black/60 px-2.5 py-1 text-[11px] font-mono font-semibold text-white backdrop-blur-md">
                        {{ car.year }}
                    </span>

                    <div
                        v-if="car.average_rating && Number(car.average_rating) > 0"
                        class="inline-flex items-center gap-1 rounded-full border border-white/20 bg-black/60 px-2.5 py-1 text-[11px] font-semibold text-amber-400 backdrop-blur-md"
                    >
                        <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                        <span>{{ Number(car.average_rating).toFixed(1) }}</span>
                        <span v-if="car.reviews_count" class="font-normal text-white/70">
                            ({{ car.reviews_count }})
                        </span>
                    </div>

                    <div
                        v-else
                        class="inline-flex items-center gap-1 rounded-full border border-white/20 bg-black/60 px-2.5 py-1 text-[11px] font-medium text-slate-300 backdrop-blur-md"
                    >
                        <Star class="h-3 w-3 text-amber-400" />
                        <span>New</span>
                    </div>
                </div>

                <!-- PDI Verified Badge -->
                <div class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-950/70 px-2.5 py-1 text-[10px] font-semibold tracking-wide text-emerald-300 backdrop-blur-md">
                    <ShieldCheck class="h-3 w-3" />
                    <span>PDI Verified</span>
                </div>
            </div>

            <!-- Floating Monospace Price Tag (In visual stage) -->
            <div class="absolute bottom-3 right-3.5 rounded-xl border border-white/15 bg-black/75 px-3 py-1.5 text-right backdrop-blur-md">
                <p class="font-mono text-base font-extrabold tracking-tight text-white">
                    ₹{{ formatPrice(car.price_per_day) }}
                </p>
                <p class="text-[9px] font-mono uppercase tracking-wider text-slate-300">
                    / day
                </p>
            </div>
        </div>

        <!-- Vehicle Details -->
        <div class="flex flex-1 flex-col p-5 sm:p-6">
            <div>
                <p class="text-[11px] font-mono uppercase tracking-widest text-[#58779D] dark:text-[#93B4D7]">
                    {{ car.make }}
                </p>

                <h3 class="mt-1 text-lg font-bold tracking-tight text-slate-950 transition-colors group-hover:text-[#58779D] dark:text-white dark:group-hover:text-[#93B4D7]">
                    {{ car.model }}
                </h3>
            </div>

            <!-- Specs Bar (Transmission, Fuel/Battery, Seats, Luggage) -->
            <div class="my-4 grid grid-cols-4 gap-1 rounded-xl border border-slate-100 bg-slate-50/80 p-2.5 text-center dark:border-white/5 dark:bg-white/[0.03]">
                <div class="flex flex-col items-center gap-1 text-[11px] text-slate-600 dark:text-slate-300">
                    <Gauge class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500" />
                    <span class="truncate max-w-full font-medium">{{ car.transmission ? (car.transmission.length > 5 ? car.transmission.slice(0, 4) + '.' : car.transmission) : 'Auto' }}</span>
                </div>

                <div class="flex flex-col items-center gap-1 text-[11px] text-slate-600 dark:text-slate-300">
                    <Zap v-if="car.fuel_type === 'Electric'" class="h-3.5 w-3.5 text-sky-400" />
                    <Fuel v-else class="h-3.5 w-3.5 text-amber-500" />
                    <span class="truncate max-w-full font-medium">{{ car.fuel_type || 'Petrol' }}</span>
                </div>

                <div class="flex flex-col items-center gap-1 text-[11px] text-slate-600 dark:text-slate-300">
                    <Users class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500" />
                    <span class="truncate max-w-full font-medium">{{ car.seats || 5 }} Seats</span>
                </div>

                <div class="flex flex-col items-center gap-1 text-[11px] text-slate-600 dark:text-slate-300">
                    <Briefcase class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500" />
                    <span class="truncate max-w-full font-medium">2 Cases</span>
                </div>
            </div>

            <!-- Description -->
            <p class="mb-5 line-clamp-2 flex-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                {{ car.description || 'Pristine condition, verified mechanics, and sanitized cabin ready for immediate hub dispatch.' }}
            </p>

            <!-- Price Breakdown & Configure / Reserve Action -->
            <div class="border-t border-slate-100 pt-4 dark:border-white/10">
                <div class="mb-3 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Deposit & Tax:</span>
                    <span class="font-mono text-[11px] font-medium">+ 7% GST & Deposit</span>
                </div>

                <button
                    type="button"
                    @click="bookCar(car.id)"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#06090E] py-3 text-xs font-bold text-white shadow-sm transition hover:bg-[#152238] active:scale-[0.98] dark:bg-white dark:text-[#06090E] dark:hover:bg-slate-200 cursor-pointer"
                >
                    <span>Configure & Reserve</span>
                    <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                </button>
            </div>
        </div>
    </article>
</template>
