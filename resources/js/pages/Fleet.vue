
<script setup lang="ts">
import CarCard from '@/components/CarCard.vue';
import HomeLayout from '@/layouts/HomeLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage<PageProps>();

const cars = computed(() => page.props.cars);
const filters = computed(() => page.props.filters);
const makes = computed(() => page.props.makes);
const fuelTypes = computed(() => page.props.fuelTypes);
const years = computed(() => page.props.years);

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
    <HomeLayout>
        <div class="min-h-screen bg-[#F4F6FA] text-[#101D35]">

            <!-- Fleet hero -->
            <section class="relative overflow-hidden bg-[#101D35] text-white">
                <div class="absolute -right-20 -top-24 h-80 w-80 rounded-full bg-white/5 blur-3xl"></div>
                <div class="absolute -bottom-28 left-1/4 h-72 w-72 rounded-full bg-[#A8BED8]/10 blur-3xl"></div>

                <div class="relative mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-20 lg:px-8">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-[#C5D0DE]">
                            <span class="h-2 w-2 rounded-full bg-[#C5D0DE]"></span>
                            Find your perfect ride
                        </div>

                        <h1 class="mt-6 text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">
                            Explore our
                            <span class="text-[#C5D0DE]">fleet.</span>
                        </h1>

                        <p class="mt-5 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                            Discover a vehicle that fits your plans. Explore our
                            available cars, compare rental prices, and find your
                            next ride.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3 text-sm font-semibold text-slate-200">
                            <span class="rounded-xl border border-white/15 bg-white/5 px-4 py-3">Browse vehicles</span>
                            <span class="rounded-xl border border-white/15 bg-white/5 px-4 py-3">Compare prices</span>
                            <span class="rounded-xl border border-white/15 bg-white/5 px-4 py-3">Choose your journey</span>
                        </div>
                    </div>
                </div>

                <div class="h-1 bg-gradient-to-r from-[#101D35] via-[#C5D0DE] to-[#101D35]"></div>
            </section>

            <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-8 lg:py-12">

                <!-- Mobile filters button -->
                <button
                    type="button"
                    @click="showFilters = !showFilters"
                    class="mb-5 flex w-full items-center justify-between rounded-2xl border border-[#DCE3ED] bg-white p-4 font-bold shadow-sm lg:hidden"
                >
                    <span class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#E8EDF5] text-lg">☰</span>
                        Search & Filters
                        <span v-if="activeFilterCount" class="rounded-full bg-[#101D35] px-2.5 py-1 text-xs text-white">
                            {{ activeFilterCount }}
                        </span>
                    </span>
                    <span>{{ showFilters ? '−' : '+' }}</span>
                </button>

                <div class="grid items-start gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">

                    <!-- Filter sidebar -->
                    <aside
                        :class="{ 'hidden': !showFilters }"
                        class="rounded-2xl border border-[#DCE3ED] bg-white p-5 shadow-sm lg:sticky lg:top-28 lg:block lg:p-6"
                    >
                        <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                            <div>
                                <h2 class="text-xl font-black">Find a car</h2>
                                <p class="mt-1 text-sm text-slate-500">Refine your search</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#101D35] text-xl text-white">⌕</span>
                        </div>

                        <form @submit="handleSearch" class="mt-5 space-y-5">

                            <!-- Search -->
                            <div>
                                <label for="fleet-search" class="mb-2 block text-sm font-bold">Search vehicles</label>
                                <input
                                    id="fleet-search"
                                    v-model="searchQuery"
                                    type="search"
                                    placeholder="Make, model, features..."
                                    class="w-full rounded-xl border border-[#DCE3ED] bg-[#F8FAFC] px-4 py-3 text-sm text-[#101D35] outline-none transition placeholder:text-slate-400 focus:border-[#58779D] focus:ring-4 focus:ring-[#58779D]/10"
                                />
                            </div>

                            <!-- Make -->
                            <div>
                                <label for="fleet-make" class="mb-2 block text-sm font-bold">Vehicle make</label>
                                <select
                                    id="fleet-make"
                                    v-model="selectedMake"
                                    class="w-full rounded-xl border border-[#DCE3ED] bg-white px-4 py-3 text-sm outline-none focus:border-[#58779D] focus:ring-4 focus:ring-[#58779D]/10"
                                >
                                    <option value="">All makes</option>
                                    <option v-for="make in makes" :key="make" :value="make">{{ make }}</option>
                                </select>
                            </div>

                            <!-- Fuel -->
                            <div>
                                <label for="fleet-fuel" class="mb-2 block text-sm font-bold">Fuel type</label>
                                <select
                                    id="fleet-fuel"
                                    v-model="selectedFuelType"
                                    class="w-full rounded-xl border border-[#DCE3ED] bg-white px-4 py-3 text-sm outline-none focus:border-[#58779D] focus:ring-4 focus:ring-[#58779D]/10"
                                >
                                    <option value="">All fuel types</option>
                                    <option v-for="fuelType in fuelTypes" :key="fuelType" :value="fuelType">
                                        {{ String(fuelType).charAt(0).toUpperCase() + String(fuelType).slice(1) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Year -->
                            <div>
                                <label for="fleet-year" class="mb-2 block text-sm font-bold">Model year</label>
                                <select
                                    id="fleet-year"
                                    v-model="selectedYear"
                                    class="w-full rounded-xl border border-[#DCE3ED] bg-white px-4 py-3 text-sm outline-none focus:border-[#58779D] focus:ring-4 focus:ring-[#58779D]/10"
                                >
                                    <option value="">All years</option>
                                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                </select>
                            </div>

                            <!-- Price -->
                            <div>
                                <label class="mb-2 block text-sm font-bold">Daily price range (₹)</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="fleet-min-price" class="mb-1 block text-xs text-slate-500">Minimum</label>
                                        <input
                                            id="fleet-min-price"
                                            v-model="minPrice"
                                            type="number"
                                            min="0"
                                            placeholder="₹ Min"
                                            class="w-full min-w-0 rounded-xl border border-[#DCE3ED] bg-[#F8FAFC] px-3 py-3 text-sm outline-none focus:border-[#58779D] focus:ring-4 focus:ring-[#58779D]/10"
                                        />
                                    </div>
                                    <div>
                                        <label for="fleet-max-price" class="mb-1 block text-xs text-slate-500">Maximum</label>
                                        <input
                                            id="fleet-max-price"
                                            v-model="maxPrice"
                                            type="number"
                                            min="0"
                                            placeholder="₹ Max"
                                            class="w-full min-w-0 rounded-xl border border-[#DCE3ED] bg-[#F8FAFC] px-3 py-3 text-sm outline-none focus:border-[#58779D] focus:ring-4 focus:ring-[#58779D]/10"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Rating -->
                            <div>
                                <label for="fleet-rating" class="mb-2 block text-sm font-bold">Minimum rating</label>
                                <select
                                    id="fleet-rating"
                                    v-model="selectedMinRating"
                                    class="w-full rounded-xl border border-[#DCE3ED] bg-white px-4 py-3 text-sm outline-none focus:border-[#58779D] focus:ring-4 focus:ring-[#58779D]/10"
                                >
                                    <option value="">Any rating</option>
                                    <option value="4.5">4.5+ Stars (Exceptional)</option>
                                    <option value="4.0">4.0+ Stars (Great)</option>
                                    <option value="3.0">3.0+ Stars (Good)</option>
                                </select>
                            </div>

                            <!-- Sort -->
                            <div>
                                <label for="fleet-sort" class="mb-2 block text-sm font-bold">Sort vehicles</label>
                                <select
                                    id="fleet-sort"
                                    v-model="sortBy"
                                    class="w-full rounded-xl border border-[#DCE3ED] bg-white px-4 py-3 text-sm outline-none focus:border-[#58779D] focus:ring-4 focus:ring-[#58779D]/10"
                                >
                                    <option value="make_asc">Make: A to Z</option>
                                    <option value="rating_desc">Highest Rated First</option>
                                    <option value="price_asc">Price: Low to High</option>
                                    <option value="price_desc">Price: High to Low</option>
                                    <option value="year_desc">Newest First</option>
                                    <option value="year_asc">Oldest First</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                class="w-full rounded-xl bg-[#101D35] px-5 py-3.5 font-bold text-white shadow-md transition hover:bg-[#263F60] focus:ring-4 focus:ring-[#58779D]/20"
                            >
                                Apply Filters
                            </button>

                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                @click="clearFilters"
                                class="w-full rounded-xl border border-[#DCE3ED] bg-white px-5 py-3 font-semibold text-slate-600 transition hover:bg-[#F4F6FA] hover:text-[#101D35]"
                            >
                                Clear All Filters
                            </button>
                        </form>

                        <div class="mt-6 rounded-xl bg-[#F4F6FA] p-4">
                            <p class="text-sm font-bold">Need help choosing?</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Browse the available vehicles and compare their
                                details before making your choice.
                            </p>
                        </div>
                    </aside>

                    <!-- Results -->
                    <section class="min-w-0">

                        <!-- Results header -->
                        <div class="mb-6 rounded-2xl border border-[#DCE3ED] bg-white p-5 shadow-sm sm:p-6">
                            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-[#58779D]">Available selection</p>
                                    <h2 class="mt-2 text-2xl font-black tracking-tight">
                                        Our vehicles
                                    </h2>
                                    <p class="mt-2 text-sm text-slate-500">
                                        <template v-if="cars.total">
                                            Showing {{ cars.from }}–{{ cars.to }} of {{ cars.total }} vehicles
                                        </template>
                                        <template v-else>
                                            No vehicles match your current search
                                        </template>
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-xl bg-[#E8EDF5] px-4 py-2.5 text-sm font-bold text-[#101D35]">
                                        {{ cars.total }} vehicles
                                    </span>
                                    <button
                                        v-if="hasActiveFilters"
                                        type="button"
                                        @click="clearFilters"
                                        class="rounded-xl border border-[#DCE3ED] px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading state -->
                        <div v-if="isLoading" class="mb-6 flex items-center justify-center gap-3 rounded-2xl border border-[#DCE3ED] bg-white p-6">
                            <span class="h-6 w-6 animate-spin rounded-full border-4 border-[#C5D0DE] border-t-[#101D35]"></span>
                            <span class="font-semibold text-slate-600">Updating your results...</span>
                        </div>

                        <!-- Vehicle cards -->
                        <div v-if="cars.data.length > 0" class="grid gap-6 md:grid-cols-2">
                            <CarCard
                                v-for="car in cars.data"
                                :key="car.id"
                                :car="car"
                            />
                        </div>

                        <!-- Empty state -->
                        <div v-else class="rounded-2xl border border-dashed border-[#C5D0DE] bg-white px-6 py-16 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#E8EDF5] text-3xl">🚘</div>
                            <h3 class="mt-5 text-2xl font-black">No vehicles found</h3>
                            <p class="mx-auto mt-3 max-w-md leading-7 text-slate-500">
                                Try changing your search terms or filters to
                                discover other vehicles in our fleet.
                            </p>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="mt-6 rounded-xl bg-[#101D35] px-6 py-3 font-bold text-white transition hover:bg-[#263F60]"
                            >
                                Show All Vehicles
                            </button>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="cars.data.length > 0 && cars.last_page > 1"
                            class="mt-8 flex flex-col gap-5 rounded-2xl border border-[#DCE3ED] bg-white p-5 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p class="text-sm text-slate-500">
                                Page <span class="font-bold text-[#101D35]">{{ cars.current_page }}</span>
                                of <span class="font-bold text-[#101D35]">{{ cars.last_page }}</span>
                            </p>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-for="(link, index) in cars.links"
                                    :key="index"
                                    type="button"
                                    :disabled="!link.url || isLoading"
                                    @click="goToPage(link.url)"
                                    :class="[
                                        'rounded-xl border px-3 py-2 text-sm font-semibold transition',
                                        link.active
                                            ? 'border-[#101D35] bg-[#101D35] text-white'
                                            : link.url
                                                ? 'border-[#DCE3ED] bg-white text-[#101D35] hover:bg-[#E8EDF5]'
                                                : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'
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
