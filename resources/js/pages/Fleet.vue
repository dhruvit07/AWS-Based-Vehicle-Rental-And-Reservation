<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import HomeLayout from '@/layouts/HomeLayout.vue';
import CarCard from '@/components/CarCard.vue';
import {
    Search,
    Filter,
    RotateCcw,
    Car as CarIcon,
    SlidersHorizontal,
    X,
    ChevronDown,
    Sparkles,
    ShieldCheck,
    Lock,
    Zap,
    Fuel,
    Star,
    Layers,
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

const searchInputRef = ref<HTMLInputElement | null>(null);
const searchQuery = ref(filters.value.search || '');
const selectedMake = ref(filters.value.make || '');
const selectedFuelType = ref(filters.value.fuel_type || '');
const minPrice = ref(filters.value.min_price || '');
const maxPrice = ref(filters.value.max_price || '');
const selectedYear = ref(filters.value.year || '');
const selectedMinRating = ref(filters.value.min_rating || '');
const sortBy = ref(filters.value.sort || 'make_asc');

const showAdvancedFilters = ref(false);
const isLoading = ref(false);

// Keyboard shortcut '/' to focus search
onMounted(() => {
    const handleKeydown = (e: KeyboardEvent) => {
        if (
            e.key === '/' &&
            document.activeElement?.tagName !== 'INPUT' &&
            document.activeElement?.tagName !== 'TEXTAREA' &&
            document.activeElement?.tagName !== 'SELECT'
        ) {
            e.preventDefault();
            searchInputRef.value?.focus();
        }
    };
    window.addEventListener('keydown', handleKeydown);
    onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
});

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

// 1-Click Removal of Specific Active Filter Chips
const removeFilter = (filterKey: string) => {
    if (filterKey === 'search') searchQuery.value = '';
    if (filterKey === 'make') selectedMake.value = '';
    if (filterKey === 'fuel_type') selectedFuelType.value = '';
    if (filterKey === 'price') {
        minPrice.value = '';
        maxPrice.value = '';
    }
    if (filterKey === 'year') selectedYear.value = '';
    if (filterKey === 'rating') selectedMinRating.value = '';
    if (filterKey === 'sort') sortBy.value = 'make_asc';
    applyFilters();
};

const selectQuickPowertrain = (type: string) => {
    selectedFuelType.value = selectedFuelType.value === type ? '' : type;
    applyFilters();
};

const selectQuickMake = (make: string) => {
    selectedMake.value = selectedMake.value === make ? '' : make;
    applyFilters();
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
    <Head title="The Fleet | Nordic Precision Luxury Vehicles" />

    <HomeLayout>
        <div class="min-h-screen bg-[#F8F9FA] text-slate-900 transition-colors duration-200 dark:bg-[#06090E] dark:text-slate-100">

            <!-- AUTOMOTIVE STAGE HEADER (Nordic Precision / Polestar Aesthetic) -->
            <section class="relative isolate overflow-hidden bg-[#06090E] px-4 py-16 text-white sm:px-6 sm:py-20 lg:px-8">
                <!-- Ambient Rim & Stage Glow -->
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_75%_50%_at_50%_-15%,rgba(56,189,248,0.16),transparent_70%)]"></div>
                <div class="pointer-events-none absolute right-0 top-0 -z-10 h-72 w-72 rounded-full bg-[#58779D]/15 blur-3xl"></div>

                <div class="mx-auto max-w-7xl">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-3.5 py-1 text-xs font-semibold tracking-wide text-slate-300">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                            <span>Nordic Fleet Directory</span>
                            <span class="text-slate-600">•</span>
                            <span class="font-mono text-slate-400">{{ cars.total }} Operational Vehicles</span>
                        </div>

                        <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.03em] sm:text-5xl lg:text-6xl">
                            The Fleet.
                        </h1>

                        <p class="mt-3 max-w-2xl text-base leading-relaxed text-slate-400 sm:text-lg">
                            Precision-engineered vehicles curated for discerning drivers. Instant collision-free calendar reservations, full digital KYC, and contactless hub dispatch.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-2 text-xs font-mono text-slate-300">
                            <span class="rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 backdrop-blur-md">Phase 9a PDI Audit Certified</span>
                            <span class="rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 backdrop-blur-md">100% Date Locking</span>
                            <span class="rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 backdrop-blur-md">VIP Discounts to 15%</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- MAIN FLEET CATALOG STAGE -->
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">

                <!-- FLOATING FROSTED FILTER CONTROL CAPSULE -->
                <div class="mb-8 rounded-3xl border border-slate-200/90 bg-white/95 p-4 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-[#0A101D]/90 sm:p-6">
                    
                    <!-- Search Input & Sort & Advanced Toggle Row -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 sm:items-center">
                        
                        <!-- Search Input with Keyboard '/' Shortcut -->
                        <div class="relative sm:col-span-6 lg:col-span-5">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <Search class="h-4 w-4" />
                            </div>
                            <input
                                ref="searchInputRef"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search make, model, or keywords..."
                                @keydown.enter="handleSearch"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 py-2.5 pl-10 pr-20 text-xs font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#58779D] focus:bg-white focus:ring-2 focus:ring-[#58779D]/20 dark:border-white/10 dark:bg-white/[0.04] dark:text-white dark:focus:bg-black/30"
                            />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 gap-1.5">
                                <button
                                    v-if="searchQuery"
                                    type="button"
                                    @click="searchQuery = ''; applyFilters()"
                                    class="rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:hover:text-white"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                                <kbd class="hidden rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-mono font-semibold text-slate-400 shadow-xs dark:border-white/10 dark:bg-white/5 sm:inline-block">
                                    /
                                </kbd>
                            </div>
                        </div>

                        <!-- Sort Dropdown -->
                        <div class="sm:col-span-4 lg:col-span-4">
                            <div class="relative">
                                <select
                                    v-model="sortBy"
                                    class="w-full appearance-none rounded-2xl border border-slate-200 bg-slate-50/70 py-2.5 pl-3.5 pr-8 text-xs font-semibold text-slate-900 outline-none transition focus:border-[#58779D] focus:ring-2 focus:ring-[#58779D]/20 dark:border-white/10 dark:bg-white/[0.04] dark:text-white [&>option]:bg-white dark:[&>option]:bg-[#0A101D] dark:[&>option]:text-white cursor-pointer"
                                >
                                    <option value="make_asc">Sort: Make (A to Z)</option>
                                    <option value="rating_desc">Sort: Highest Rated</option>
                                    <option value="price_asc">Sort: Price (Low to High)</option>
                                    <option value="price_desc">Sort: Price (High to Low)</option>
                                    <option value="year_desc">Sort: Newest Model Year</option>
                                    <option value="year_asc">Sort: Oldest Model Year</option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            </div>
                        </div>

                        <!-- Filter Toggle & Search Action -->
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3">
                            <button
                                type="button"
                                @click="showAdvancedFilters = !showAdvancedFilters"
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-2xl border border-slate-200 bg-slate-50/70 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-300 dark:hover:bg-white/10 cursor-pointer"
                            >
                                <SlidersHorizontal class="h-3.5 w-3.5" />
                                <span class="hidden lg:inline">Filters</span>
                                <span v-if="activeFilterCount" class="rounded-full bg-[#06090E] px-1.5 py-0.2 text-[10px] font-bold text-white dark:bg-white dark:text-[#06090E]">
                                    {{ activeFilterCount }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="applyFilters"
                                class="rounded-2xl bg-[#06090E] px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#152238] active:scale-[0.98] dark:bg-white dark:text-[#06090E] dark:hover:bg-slate-200 cursor-pointer"
                            >
                                Search
                            </button>
                        </div>

                    </div>

                    <!-- Category & Make Filter Pill Rails -->
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 dark:border-white/5">
                        
                        <!-- Powertrain Pills -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 mr-1">Powertrain:</span>
                            
                            <button
                                type="button"
                                @click="selectQuickPowertrain('')"
                                :class="!selectedFuelType
                                    ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-bold shadow-xs'
                                    : 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10'"
                                class="rounded-xl px-3 py-1.5 text-xs transition cursor-pointer"
                            >
                                All Types
                            </button>

                            <button
                                v-for="ft in fuelTypes"
                                :key="ft"
                                type="button"
                                @click="selectQuickPowertrain(ft)"
                                :class="selectedFuelType === ft
                                    ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-bold shadow-xs'
                                    : 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10'"
                                class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs transition capitalize cursor-pointer"
                            >
                                <Zap v-if="ft === 'Electric'" class="h-3 w-3 text-sky-400" />
                                <Fuel v-else class="h-3 w-3 text-amber-500" />
                                <span>{{ ft }}</span>
                            </button>
                        </div>

                        <!-- Top Makes Pills -->
                        <div class="hidden xl:flex items-center gap-1.5 ml-auto">
                            <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 mr-1">Make:</span>
                            
                            <button
                                v-for="make in makes.slice(0, 5)"
                                :key="make"
                                type="button"
                                @click="selectQuickMake(make)"
                                :class="selectedMake === make
                                    ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-bold shadow-xs'
                                    : 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10'"
                                class="rounded-xl px-3 py-1.5 text-xs transition cursor-pointer"
                            >
                                {{ make }}
                            </button>
                        </div>

                    </div>

                    <!-- Expandable Advanced Filter Panel -->
                    <div
                        v-if="showAdvancedFilters"
                        class="mt-5 grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-slate-50/50 p-4 dark:border-white/10 dark:bg-white/[0.02] sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <!-- Manufacturer Selector -->
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Manufacturer</label>
                            <select
                                v-model="selectedMake"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none dark:border-white/10 dark:bg-[#0A101D] dark:text-white"
                            >
                                <option value="">All Manufacturers</option>
                                <option v-for="m in makes" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>

                        <!-- Model Year -->
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Model Year</label>
                            <select
                                v-model="selectedYear"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none dark:border-white/10 dark:bg-[#0A101D] dark:text-white"
                            >
                                <option value="">All Years</option>
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>

                        <!-- Price Range Min/Max -->
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Daily Rate (₹)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    v-model="minPrice"
                                    type="number"
                                    min="0"
                                    placeholder="Min ₹"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none dark:border-white/10 dark:bg-[#0A101D] dark:text-white"
                                />
                                <input
                                    v-model="maxPrice"
                                    type="number"
                                    min="0"
                                    placeholder="Max ₹"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none dark:border-white/10 dark:bg-[#0A101D] dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Minimum Rating -->
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Driver Rating</label>
                            <select
                                v-model="selectedMinRating"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none dark:border-white/10 dark:bg-[#0A101D] dark:text-white"
                            >
                                <option value="">Any Rating</option>
                                <option value="4.5">4.5+ Stars (Top Rated)</option>
                                <option value="4.0">4.0+ Stars (Excellent)</option>
                                <option value="3.0">3.0+ Stars (Good)</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                            >
                                Reset All
                            </button>
                            <button
                                type="button"
                                @click="applyFilters"
                                class="rounded-xl bg-[#06090E] px-5 py-2 text-xs font-bold text-white hover:bg-[#152238] dark:bg-white dark:text-[#06090E]"
                            >
                                Apply Advanced Filters
                            </button>
                        </div>
                    </div>

                    <!-- ACTIVE FILTER CHIPS WITH 1-CLICK REMOVAL -->
                    <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 dark:border-white/5">
                        <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400">Active Filters:</span>

                        <!-- Search Chip -->
                        <span
                            v-if="searchQuery"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800 dark:border-white/10 dark:bg-white/10 dark:text-slate-200"
                        >
                            <span>Keyword: "{{ searchQuery }}"</span>
                            <button type="button" @click="removeFilter('search')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">
                                <X class="h-3 w-3" />
                            </button>
                        </span>

                        <!-- Make Chip -->
                        <span
                            v-if="selectedMake"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800 dark:border-white/10 dark:bg-white/10 dark:text-slate-200"
                        >
                            <span>Make: {{ selectedMake }}</span>
                            <button type="button" @click="removeFilter('make')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">
                                <X class="h-3 w-3" />
                            </button>
                        </span>

                        <!-- Fuel Type Chip -->
                        <span
                            v-if="selectedFuelType"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800 dark:border-white/10 dark:bg-white/10 dark:text-slate-200"
                        >
                            <span>Powertrain: {{ selectedFuelType }}</span>
                            <button type="button" @click="removeFilter('fuel_type')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">
                                <X class="h-3 w-3" />
                            </button>
                        </span>

                        <!-- Price Chip -->
                        <span
                            v-if="minPrice || maxPrice"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800 dark:border-white/10 dark:bg-white/10 dark:text-slate-200"
                        >
                            <span>Price: ₹{{ minPrice || '0' }} – ₹{{ maxPrice || '∞' }}</span>
                            <button type="button" @click="removeFilter('price')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">
                                <X class="h-3 w-3" />
                            </button>
                        </span>

                        <!-- Year Chip -->
                        <span
                            v-if="selectedYear"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800 dark:border-white/10 dark:bg-white/10 dark:text-slate-200"
                        >
                            <span>Year: {{ selectedYear }}</span>
                            <button type="button" @click="removeFilter('year')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">
                                <X class="h-3 w-3" />
                            </button>
                        </span>

                        <!-- Rating Chip -->
                        <span
                            v-if="selectedMinRating"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800 dark:border-white/10 dark:bg-white/10 dark:text-slate-200"
                        >
                            <span>Min Rating: {{ selectedMinRating }}★</span>
                            <button type="button" @click="removeFilter('rating')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">
                                <X class="h-3 w-3" />
                            </button>
                        </span>

                        <button
                            type="button"
                            @click="clearFilters"
                            class="ml-auto text-xs font-semibold text-[#58779D] hover:underline dark:text-[#93B4D7]"
                        >
                            Reset All Filters
                        </button>
                    </div>

                </div>

                <!-- Results Counter & Status Bar -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <template v-if="cars.total">
                                Showing <span class="font-bold text-slate-900 dark:text-white">{{ cars.from }}–{{ cars.to }}</span> of <span class="font-bold text-slate-900 dark:text-white">{{ cars.total }}</span> calibrated vehicles
                            </template>
                            <template v-else>
                                0 vehicles matching current criteria
                            </template>
                        </p>
                    </div>

                    <!-- Loading Indicator -->
                    <div v-if="isLoading" class="flex items-center gap-2 text-xs font-mono text-slate-500">
                        <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-[#58779D] border-t-transparent"></span>
                        <span>Filtering radar...</span>
                    </div>
                </div>

                <!-- 16:9 VEHICLE GRID (3 Columns on Desktop) -->
                <div v-if="cars.data.length > 0" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <CarCard
                        v-for="car in cars.data"
                        :key="car.id"
                        :car="car"
                    />
                </div>

                <!-- HIGH-CRAFT EMPTY STATE -->
                <div
                    v-else
                    class="rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-xs dark:border-white/10 dark:bg-[#0A101D] sm:p-16"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300">
                        <CarIcon class="h-6 w-6" />
                    </div>
                    
                    <h3 class="mt-4 text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                        No vehicles match your criteria
                    </h3>
                    
                    <p class="mx-auto mt-2 max-w-md text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                        We could not find any available models matching your current filter filters. Try broadening your price bracket or selecting all manufacturers.
                    </p>

                    <button
                        type="button"
                        @click="clearFilters"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#06090E] px-6 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#152238] active:scale-[0.98] dark:bg-white dark:text-[#06090E] dark:hover:bg-slate-200"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Clear All Filters</span>
                    </button>
                </div>

                <!-- CLEAN APPLE-STYLE PAGINATION -->
                <div
                    v-if="cars.data.length > 0 && cars.last_page > 1"
                    class="mt-12 flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-xs backdrop-blur-md dark:border-white/10 dark:bg-[#0A101D]/80 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Page <span class="font-bold text-slate-900 dark:text-white">{{ cars.current_page }}</span> of <span class="font-bold text-slate-900 dark:text-white">{{ cars.last_page }}</span>
                    </p>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            v-for="(link, index) in cars.links"
                            :key="index"
                            type="button"
                            :disabled="!link.url || isLoading"
                            @click="goToPage(link.url)"
                            :class="[
                                'rounded-xl px-3.5 py-1.5 text-xs font-medium transition cursor-pointer',
                                link.active
                                    ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-bold shadow-xs'
                                    : link.url
                                        ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10'
                                        : 'cursor-not-allowed text-slate-300 dark:text-slate-700'
                            ]"
                            v-html="link.label"
                        ></button>
                    </div>
                </div>

            </div>
        </div>
    </HomeLayout>
</template>
