<script setup lang="ts">
import CarCard from '@/components/CarCard.vue';
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Search, Filter, RotateCcw, Car as CarIcon, ArrowUpDown } from 'lucide-vue-next';

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

interface PaginatedCars {
    data: Car[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface PageProps {
    cars: PaginatedCars;
    filters: {
        search?: string;
        make?: string;
        fuel_type?: string;
        min_price?: string;
        max_price?: string;
        year?: string;
        min_rating?: string;
        sort?: string;
    };
    makes: string[];
    fuelTypes: string[];
    years: number[];
    [key: string]: any;
}

const page = usePage<PageProps>();

const cars = computed(() => page.props.cars);
const filters = computed(() => page.props.filters || {});
const makes = computed(() => page.props.makes || []);
const fuelTypes = computed(() => page.props.fuelTypes || []);
const years = computed(() => page.props.years || []);

const searchQuery = ref(filters.value.search || '');
const selectedMake = ref(filters.value.make || '');
const selectedFuelType = ref(filters.value.fuel_type || '');
const minPrice = ref(filters.value.min_price || '');
const maxPrice = ref(filters.value.max_price || '');
const selectedYear = ref(filters.value.year || '');
const selectedMinRating = ref(filters.value.min_rating || '');
const sortBy = ref(filters.value.sort || 'make_asc');

const showFilters = ref(false);
const isLoading = ref(false);

const activeFilterCount = computed(() => {
    return [
        searchQuery.value.trim(),
        selectedMake.value,
        selectedFuelType.value,
        minPrice.value,
        maxPrice.value,
        selectedYear.value,
        selectedMinRating.value,
    ].filter(Boolean).length;
});

const hasActiveFilters = computed(() => {
    return activeFilterCount.value > 0 || sortBy.value !== 'make_asc';
});

const applyFilters = () => {
    const params: Record<string, string> = {};

    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    if (selectedMake.value) params.make = String(selectedMake.value);
    if (selectedFuelType.value) params.fuel_type = String(selectedFuelType.value);
    if (minPrice.value !== '') params.min_price = String(minPrice.value);
    if (maxPrice.value !== '') params.max_price = String(maxPrice.value);
    if (selectedYear.value) params.year = String(selectedYear.value);
    if (selectedMinRating.value) params.min_rating = String(selectedMinRating.value);
    if (sortBy.value && sortBy.value !== 'make_asc') params.sort = sortBy.value;

    isLoading.value = true;

    router.get('/fleet', params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedMake.value = '';
    selectedFuelType.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    selectedYear.value = '';
    selectedMinRating.value = '';
    sortBy.value = 'make_asc';

    isLoading.value = true;

    router.get('/fleet', {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const handleSearch = (event?: Event) => {
    event?.preventDefault();
    applyFilters();
};

watch(sortBy, (nextValue, previousValue) => {
    if (nextValue !== previousValue) {
        applyFilters();
    }
});

const goToPage = (url: string | null) => {
    if (!url || isLoading.value) return;

    isLoading.value = true;

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};
</script>

<template>
    <Head title="Fleet Directory | Real Rent Car" />

    <HomeLayout>
        <div class="min-h-screen bg-[#FBFBFD] text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100">

            <!-- Fleet Header Staging -->
            <section class="relative isolate overflow-hidden bg-[#101D35] text-white dark:bg-[#070A0F]">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(120,160,210,0.2),rgba(255,255,255,0))]"></div>
                <div class="pointer-events-none absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full bg-[#58779D]/20 blur-3xl"></div>

                <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20 lg:px-8">
                    <div class="max-w-3xl">
                        <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                            Curated automotive fleet.
                        </h1>

                        <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg">
                            Compare models, fuel efficiency, verified driver ratings, and daily rates with instant reservation confirmation.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-2.5 text-xs font-semibold text-slate-200">
                            <span class="rounded-xl border border-white/10 bg-white/5 px-3.5 py-2 backdrop-blur-md">Full Insurance Included</span>
                            <span class="rounded-xl border border-white/10 bg-white/5 px-3.5 py-2 backdrop-blur-md">Live Conflict Checking</span>
                            <span class="rounded-xl border border-white/10 bg-white/5 px-3.5 py-2 backdrop-blur-md">Transparent Rates</span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-8 lg:py-12">

                <!-- Mobile Filters Button -->
                <button
                    type="button"
                    @click="showFilters = !showFilters"
                    class="glass-card mb-6 flex w-full items-center justify-between p-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-50 dark:text-white dark:hover:bg-white/5 lg:hidden"
                >
                    <span class="flex items-center gap-3">
                        <Filter class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                        <span>Filter & Search</span>
                        <span v-if="activeFilterCount" class="rounded-full bg-[#101D35] px-2 py-0.5 text-xs text-white dark:bg-white dark:text-slate-950">
                            {{ activeFilterCount }}
                        </span>
                    </span>
                    <span class="text-xs text-slate-500">{{ showFilters ? 'Hide' : 'Show' }}</span>
                </button>

                <div class="grid items-start gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">

                    <!-- Sticky Frosted Filter Sidebar -->
                    <aside
                        :class="{ 'hidden': !showFilters }"
                        class="glass-card p-5 lg:sticky lg:top-24 lg:block lg:p-6"
                    >
                        <div class="flex items-center justify-between border-b border-slate-200/80 pb-4 dark:border-white/10">
                            <div>
                                <h2 class="text-base font-bold tracking-tight text-slate-950 dark:text-white">Filter Fleet</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Refine selection</p>
                            </div>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-white">
                                <Search class="h-4 w-4" />
                            </div>
                        </div>

                        <form @submit="handleSearch" class="mt-5 space-y-4">

                            <!-- Search -->
                            <div>
                                <label for="fleet-search" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Keyword</label>
                                <input
                                    id="fleet-search"
                                    v-model="searchQuery"
                                    type="search"
                                    placeholder="Make, model, color..."
                                    class="apple-input w-full px-3.5 py-2.5 text-xs"
                                />
                            </div>

                            <!-- Make -->
                            <div>
                                <label for="fleet-make" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Manufacturer</label>
                                <select
                                    id="fleet-make"
                                    v-model="selectedMake"
                                    class="apple-input w-full px-3.5 py-2.5 text-xs"
                                >
                                    <option value="">All manufacturers</option>
                                    <option v-for="make in makes" :key="make" :value="make">{{ make }}</option>
                                </select>
                            </div>

                            <!-- Fuel -->
                            <div>
                                <label for="fleet-fuel" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Powertrain</label>
                                <select
                                    id="fleet-fuel"
                                    v-model="selectedFuelType"
                                    class="apple-input w-full px-3.5 py-2.5 text-xs"
                                >
                                    <option value="">All powertrains</option>
                                    <option v-for="fuelType in fuelTypes" :key="fuelType" :value="fuelType">
                                        {{ String(fuelType).charAt(0).toUpperCase() + String(fuelType).slice(1) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Year -->
                            <div>
                                <label for="fleet-year" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Model Year</label>
                                <select
                                    id="fleet-year"
                                    v-model="selectedYear"
                                    class="apple-input w-full px-3.5 py-2.5 text-xs"
                                >
                                    <option value="">All model years</option>
                                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                </select>
                            </div>

                            <!-- Price Range -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Daily Rate (₹)</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        id="fleet-min-price"
                                        v-model="minPrice"
                                        type="number"
                                        min="0"
                                        placeholder="Min ₹"
                                        class="apple-input w-full px-3 py-2 text-xs"
                                    />
                                    <input
                                        id="fleet-max-price"
                                        v-model="maxPrice"
                                        type="number"
                                        min="0"
                                        placeholder="Max ₹"
                                        class="apple-input w-full px-3 py-2 text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Rating -->
                            <div>
                                <label for="fleet-rating" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Driver Rating</label>
                                <select
                                    id="fleet-rating"
                                    v-model="selectedMinRating"
                                    class="apple-input w-full px-3.5 py-2.5 text-xs"
                                >
                                    <option value="">Any rating</option>
                                    <option value="4.5">4.5+ Stars (Top Rated)</option>
                                    <option value="4.0">4.0+ Stars (Excellent)</option>
                                    <option value="3.0">3.0+ Stars (Good)</option>
                                </select>
                            </div>

                            <!-- Sort -->
                            <div>
                                <label for="fleet-sort" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Sort By</label>
                                <select
                                    id="fleet-sort"
                                    v-model="sortBy"
                                    class="apple-input w-full px-3.5 py-2.5 text-xs"
                                >
                                    <option value="make_asc">Make: A to Z</option>
                                    <option value="rating_desc">Highest Rated</option>
                                    <option value="price_asc">Price: Low to High</option>
                                    <option value="price_desc">Price: High to Low</option>
                                    <option value="year_desc">Newest First</option>
                                    <option value="year_asc">Oldest First</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                class="apple-btn-primary w-full py-2.5 text-xs font-semibold"
                            >
                                Apply Filters
                            </button>

                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                @click="clearFilters"
                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                            >
                                <RotateCcw class="h-3 w-3" />
                                <span>Reset Filters</span>
                            </button>
                        </form>
                    </aside>

                    <!-- Results Column -->
                    <section class="min-w-0">

                        <!-- Results Header Bar -->
                        <div class="glass-card mb-6 p-5">
                            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                                <div>
                                    <h2 class="text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                        Available Vehicles
                                    </h2>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        <template v-if="cars.total">
                                            Showing {{ cars.from }}–{{ cars.to }} of {{ cars.total }} vehicles matching your criteria
                                        </template>
                                        <template v-else>
                                            No vehicles currently match your filters
                                        </template>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="rounded-xl border border-slate-200/80 bg-slate-100/70 px-3.5 py-1.5 text-xs font-semibold text-slate-800 dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
                                        {{ cars.total }} in fleet
                                    </span>
                                    <button
                                        v-if="hasActiveFilters"
                                        type="button"
                                        @click="clearFilters"
                                        class="rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="isLoading" class="glass-card mb-6 flex items-center justify-center gap-3 p-8">
                            <span class="h-5 w-5 animate-spin rounded-full border-2 border-[#58779D] border-t-transparent"></span>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Updating results...</span>
                        </div>

                        <!-- Vehicle Grid -->
                        <div v-if="cars.data.length > 0" class="grid gap-6 sm:grid-cols-2">
                            <CarCard
                                v-for="car in cars.data"
                                :key="car.id"
                                :car="car"
                            />
                        </div>

                        <!-- Empty State -->
                        <div v-else class="glass-card p-12 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-white/5 text-slate-700 dark:text-slate-300">
                                <CarIcon class="h-6 w-6" />
                            </div>
                            <h3 class="mt-4 text-lg font-bold text-slate-950 dark:text-white">No vehicles match filters</h3>
                            <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                Try widening your price range, clearing manufacturer selections, or browsing all models.
                            </p>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="apple-btn-primary mt-6 inline-flex px-5 py-2.5 text-xs font-semibold"
                            >
                                Clear All Filters
                            </button>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="cars.data.length > 0 && cars.last_page > 1"
                            class="glass-card mt-8 flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Page <span class="font-semibold text-slate-900 dark:text-white">{{ cars.current_page }}</span>
                                of <span class="font-semibold text-slate-900 dark:text-white">{{ cars.last_page }}</span>
                            </p>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <button
                                    v-for="(link, index) in cars.links"
                                    :key="index"
                                    type="button"
                                    :disabled="!link.url || isLoading"
                                    @click="goToPage(link.url)"
                                    :class="[
                                        'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                                        link.active
                                            ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold'
                                            : link.url
                                                ? 'border border-slate-200/80 bg-white text-slate-700 hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10'
                                                : 'cursor-not-allowed text-slate-300 dark:text-slate-600'
                                    ]"
                                    v-html="link.label"
                                ></button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </HomeLayout>
</template>
