<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import HomeLayout from '@/layouts/HomeLayout.vue';
import { fleet, about, contact } from '@/routes';
import { presentation } from '@/routes/demo';
import { show as fleetShow } from '@/routes/fleet';
import {
    ArrowRight,
    ArrowUpRight,
    Calendar,
    CheckCircle2,
    Clock,
    Crown,
    Fuel,
    Gauge,
    Layers,
    Lock,
    MapPin,
    QrCode,
    RotateCcw,
    Shield,
    ShieldCheck,
    Sparkles,
    Star,
    Users,
    Wrench,
    Zap,
} from 'lucide-vue-next';

interface Car {
    id: number;
    make: string;
    model: string;
    year: number;
    price_per_day: string | number;
    description?: string;
    fuel_type?: string;
    transmission?: string;
    seats?: number;
    image_url?: string;
    image?: string;
    color?: string;
    status?: string;
    average_rating?: number | string;
    reviews_count?: number;
}

const page = usePage();
const rawCars = (page.props.homeCars as Car[] | undefined) ?? [];

// Fallback curated showcase if empty
const showcaseCars = computed<Car[]>(() => {
    if (rawCars.length > 0) return rawCars;
    return [
        {
            id: 1,
            make: 'Porsche',
            model: 'Taycan 4S Cross Turismo',
            year: 2024,
            price_per_day: 14500,
            description: 'Pure electric grand tourer with dual electric motors, active air suspension, and 800V fast-charge architecture.',
            fuel_type: 'Electric',
            transmission: 'Automatic',
            seats: 4,
            average_rating: '4.95',
            reviews_count: 24,
            image_url: '/images/hero_image.png',
        },
        {
            id: 2,
            make: 'Mercedes-Benz',
            model: 'S 580 Executive 4MATIC',
            year: 2024,
            price_per_day: 16000,
            description: 'The definitive benchmark of automotive luxury. Chauffeur package with rear massaging executive lounge.',
            fuel_type: 'Petrol',
            transmission: 'Automatic',
            seats: 5,
            average_rating: '4.98',
            reviews_count: 31,
            image_url: '/images/car-default.jpg',
        },
        {
            id: 3,
            make: 'BMW',
            model: 'X7 M60i TwinPower',
            year: 2023,
            price_per_day: 13500,
            description: 'Seven-seat commanding flagship SUV with 523 HP M TwinPower Turbo V8 and Sky Lounge panoramic glass.',
            fuel_type: 'Petrol',
            transmission: 'Automatic',
            seats: 7,
            average_rating: '4.91',
            reviews_count: 19,
            image_url: '/images/hero_image.png',
        },
    ];
});

const carImage = (car: Car) => car.image_url || car.image || '/images/hero_image.png';

const formatPrice = (price: string | number) => {
    const amount = Number(price);
    return Number.isFinite(amount)
        ? new Intl.NumberFormat('en-IN', {
              maximumFractionDigits: 0,
          }).format(amount)
        : String(price);
};

// -------------------------------------------------------------
// Direct-in-Hero Availability Search Capsule State
// -------------------------------------------------------------
const todayStr = new Date().toISOString().split('T')[0];
const threeDaysLater = new Date(Date.now() + 3 * 86400000).toISOString().split('T')[0];

const searchHub = ref<string>('Mumbai Airport (BOM) Terminal 2');
const pickupDate = ref<string>(todayStr);
const returnDate = ref<string>(threeDaysLater);
const selectedCategory = ref<string>('all');

const hubs = [
    'Mumbai Airport (BOM) Terminal 2',
    'Delhi Aerocity (DEL) Hub',
    'Bengaluru Central (BLR) Hub',
    'Goa Coastal (GOI) Hub',
];

const categoriesFilter = [
    { value: 'all', label: 'All Fleet Categories' },
    { value: 'Electric', label: 'Pure Electric (EV)' },
    { value: 'Sedan', label: 'Executive Sedan' },
    { value: 'SUV', label: 'Performance SUV' },
];

const executeHeroSearch = () => {
    const params: Record<string, string> = {};
    if (selectedCategory.value === 'Electric') {
        params.fuel_type = 'Electric';
    } else if (selectedCategory.value !== 'all') {
        params.search = selectedCategory.value;
    }
    router.visit(fleet.url({ query: params }));
};

// -------------------------------------------------------------
// Interactive Bento Card 1: Category Spectrum Tab Switcher
// -------------------------------------------------------------
const activeCategoryTab = ref<'electric' | 'sedan' | 'suv' | 'gt'>('electric');

const categorySpectrums = {
    electric: {
        title: 'Pure Electric',
        headline: 'Instant 800V Torque. Zero Emission.',
        model: 'Porsche Taycan 4S / Polestar 4',
        accel: '2.8s',
        accelLabel: '0–100 km/h',
        range: '620 km',
        rangeLabel: 'WLTP Range',
        power: '544 HP',
        powerLabel: 'Peak Output',
        drivetrain: 'Dual Motor Intelligent AWD',
        description: 'Instantaneous power delivery meets ultra-quiet aerodynamic architecture. Calibrated with high-voltage DC fast charging to recover 80% range in 18 minutes.',
        searchParam: 'Electric',
        badge: 'Zero Emission',
    },
    sedan: {
        title: 'Executive Sedan',
        headline: 'Architectural Silence. Chauffeur Optional.',
        model: 'Mercedes-Benz S 580 4MATIC',
        accel: '4.4s',
        accelLabel: '0–100 km/h',
        range: '250 km/h',
        rangeLabel: 'Top Speed',
        power: '496 HP',
        powerLabel: 'Twin-Turbo V8',
        drivetrain: 'Airmatic Active Dampening',
        description: 'A cocoon of acoustic isolation. Equipped with rear executive recliner, Burmester 4D spatial sound, active cabin fragrance, and rear-axle steering.',
        searchParam: 'Mercedes-Benz',
        badge: 'Flagship Luxury',
    },
    suv: {
        title: 'Performance SUV',
        headline: 'Commanding Stature. Unrestricted Terrain.',
        model: 'BMW X7 M60i xDrive',
        accel: '3.8s',
        accelLabel: '0–100 km/h',
        range: '750 km',
        rangeLabel: 'Cruising Range',
        power: '523 HP',
        powerLabel: 'M TwinPower V8',
        drivetrain: 'Intelligent xDrive All-Wheel',
        description: 'Three rows of uncompromising elegance. Features sky-lounge panoramic LED roof, executive drive pro suspension, and generous 750-liter cargo capacity.',
        searchParam: 'BMW',
        badge: 'All-Terrain Prestige',
    },
    gt: {
        title: 'Grand Tourer',
        headline: 'Continental Velocity. Pure Emotion.',
        model: 'Aston Martin DB12 Super Tourer',
        accel: '3.5s',
        accelLabel: '0–100 km/h',
        range: '325 km/h',
        rangeLabel: 'Top Speed',
        power: '671 HP',
        powerLabel: 'Hand-Built Twin Turbo',
        drivetrain: 'Electronic Rear Differential',
        description: 'Sculpted muscular stance paired with bespoke Bowers & Wilkins acoustics, carbon ceramic brakes, and hand-stitched Bridge of Weir leather interior.',
        searchParam: 'Audi',
        badge: 'Super Grand Tourer',
    },
};

// -------------------------------------------------------------
// Interactive Bento Card 2: Phase 9a PDI Condition Audit Preview
// -------------------------------------------------------------
const simulatedScratch = ref<boolean>(false);
const activeZone = ref<string | null>(null);

const zones = [
    { id: 'front_bumper', label: 'Front Splitter & Bumper', x: 50, y: 12 },
    { id: 'hood', label: 'Engine Hood / Bonnet', x: 50, y: 25 },
    { id: 'windshield', label: 'Acoustic Windshield', x: 50, y: 38 },
    { id: 'left_door', label: 'Driver Door Panel', x: 26, y: 50 },
    { id: 'right_door', label: 'Passenger Door Panel', x: 74, y: 50 },
    { id: 'trunk', label: 'Rear Boot & Tailgate', x: 50, y: 84 },
];

const toggleSimulatedScratch = () => {
    simulatedScratch.value = !simulatedScratch.value;
    activeZone.value = simulatedScratch.value ? 'front_bumper' : null;
};

const selectZone = (zoneId: string) => {
    activeZone.value = zoneId;
    simulatedScratch.value = true;
};

const resetPdiPreview = () => {
    simulatedScratch.value = false;
    activeZone.value = null;
};

// -------------------------------------------------------------
// Interactive Bento Card 3: VIP Loyalty Passport Tier Engine
// -------------------------------------------------------------
const selectedTier = ref<'bronze' | 'silver' | 'gold' | 'platinum'>('platinum');

const tiers = {
    bronze: {
        name: 'Bronze Member',
        discountPct: 0,
        badgeBg: 'bg-stone-500/10 border-stone-500/30 text-stone-700 dark:text-stone-300',
        cardGradient: 'from-[#2A2421] to-[#171412] border-amber-900/30',
        perks: ['Instant booking access', 'Free cancellation up to 48h', 'A4 digital rental agreement'],
        dailyRate: 12000,
    },
    silver: {
        name: 'Silver VIP',
        discountPct: 5,
        badgeBg: 'bg-slate-400/10 border-slate-400/30 text-slate-800 dark:text-slate-200',
        cardGradient: 'from-[#1E2530] to-[#0F141C] border-slate-400/30',
        perks: ['5% automatic discount on all bookings', 'Free early hub pickup (2h buffer)', 'Priority chat support'],
        dailyRate: 12000,
    },
    gold: {
        name: 'Gold VIP',
        discountPct: 10,
        badgeBg: 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300',
        cardGradient: 'from-[#2C210E] to-[#120D05] border-amber-500/40',
        perks: ['10% automatic discount on all bookings', 'Free additional verified driver', 'Complimentary vehicle category upgrade'],
        dailyRate: 12000,
    },
    platinum: {
        name: 'Platinum VIP',
        discountPct: 15,
        badgeBg: 'bg-sky-500/10 border-sky-500/30 text-sky-700 dark:text-sky-300',
        cardGradient: 'from-[#0C1929] to-[#04080F] border-sky-400/40',
        perks: ['15% automatic lifetime discount', 'Zero security deposit lock', 'Private terminal concierge handover', 'Dedicated WhatsApp dispatch manager'],
        dailyRate: 12000,
    },
};

const currentTierData = computed(() => tiers[selectedTier.value]);
const discountAmount = computed(() => (currentTierData.value.dailyRate * currentTierData.value.discountPct) / 100);
const netRate = computed(() => currentTierData.value.dailyRate - discountAmount.value);
</script>

<template>
    <Head title="Real Rent Car | Nordic Precision Luxury Mobility" />

    <HomeLayout>
        <div class="overflow-hidden bg-[#F8F9FA] text-slate-900 transition-colors duration-200 dark:bg-[#06090E] dark:text-slate-100">

            <!-- ========================================================= -->
            <!-- 1. AUTOMOTIVE STAGE HERO (Nordic Precision / Polestar Lux) -->
            <!-- ========================================================= -->
            <section class="relative isolate overflow-hidden bg-[#06090E] text-white">
                <!-- Ambient Rim & Stage Lighting -->
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_50%_at_50%_-15%,rgba(56,189,248,0.18),transparent_70%)]"></div>
                <div class="pointer-events-none absolute -right-32 top-10 -z-10 h-[500px] w-[500px] rounded-full bg-[#58779D]/15 blur-3xl"></div>
                <div class="pointer-events-none absolute -left-32 bottom-0 -z-10 h-[500px] w-[500px] rounded-full bg-[#38BDF8]/10 blur-3xl"></div>

                <!-- Subtle Grid Background -->
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>

                <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-28">

                    <!-- Architectural Headline Stage -->
                    <div class="mx-auto max-w-4xl text-center">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 py-1.5 text-xs font-semibold tracking-wide text-slate-300 backdrop-blur-md">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                            <span>Nordic Precision Mobility</span>
                            <span class="text-slate-600">•</span>
                            <span class="text-slate-400 font-mono">Real-Time Collision Prevention Engine</span>
                        </div>

                        <h1 class="mt-6 text-4xl font-extrabold tracking-[-0.03em] sm:text-6xl lg:text-7xl lg:leading-[1.08]">
                            Automotive Artistry.<br />
                            <span class="text-slate-300 dark:text-slate-300">
                                Uncompromised Freedom.
                            </span>
                        </h1>

                        <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-400 sm:text-lg sm:leading-8">
                            Curated high-performance machinery calibrated for effortless mobility. Atomic date locking prevents collisions, while digital gate passes ensure 60-second hub handovers.
                        </p>
                    </div>

                    <!-- DIRECT-IN-HERO AVAILABILITY SEARCH CAPSULE -->
                    <div class="mx-auto mt-10 max-w-5xl">
                        <div class="rounded-3xl border border-white/15 bg-white/[0.06] p-3 shadow-2xl backdrop-blur-2xl transition-all sm:p-4">
                            <form @submit.prevent="executeHeroSearch" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12 lg:items-center">

                                <!-- Hub / Location -->
                                <div class="relative rounded-2xl border border-white/10 bg-white/[0.04] p-3 transition hover:border-white/20 lg:col-span-4">
                                    <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                        <MapPin class="h-3.5 w-3.5 text-sky-400" />
                                        <span>Pick-up Hub</span>
                                    </div>
                                    <select
                                        v-model="searchHub"
                                        class="mt-1.5 w-full bg-transparent text-xs font-semibold text-white outline-none cursor-pointer [&>option]:bg-[#06090E] [&>option]:text-white"
                                    >
                                        <option v-for="hub in hubs" :key="hub" :value="hub">{{ hub }}</option>
                                    </select>
                                </div>

                                <!-- Pick-up Date -->
                                <div class="relative rounded-2xl border border-white/10 bg-white/[0.04] p-3 transition hover:border-white/20 lg:col-span-2 sm:col-span-1">
                                    <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                        <Calendar class="h-3.5 w-3.5 text-sky-400" />
                                        <span>Pick-up</span>
                                    </div>
                                    <input
                                        v-model="pickupDate"
                                        type="date"
                                        :min="todayStr"
                                        class="mt-1.5 w-full bg-transparent text-xs font-semibold text-white outline-none cursor-pointer [color-scheme:dark]"
                                    />
                                </div>

                                <!-- Return Date -->
                                <div class="relative rounded-2xl border border-white/10 bg-white/[0.04] p-3 transition hover:border-white/20 lg:col-span-2 sm:col-span-1">
                                    <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                        <Clock class="h-3.5 w-3.5 text-sky-400" />
                                        <span>Return</span>
                                    </div>
                                    <input
                                        v-model="returnDate"
                                        type="date"
                                        :min="pickupDate || todayStr"
                                        class="mt-1.5 w-full bg-transparent text-xs font-semibold text-white outline-none cursor-pointer [color-scheme:dark]"
                                    />
                                </div>

                                <!-- Vehicle Category -->
                                <div class="relative rounded-2xl border border-white/10 bg-white/[0.04] p-3 transition hover:border-white/20 lg:col-span-2">
                                    <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                        <Layers class="h-3.5 w-3.5 text-sky-400" />
                                        <span>Category</span>
                                    </div>
                                    <select
                                        v-model="selectedCategory"
                                        class="mt-1.5 w-full bg-transparent text-xs font-semibold text-white outline-none cursor-pointer [&>option]:bg-[#06090E] [&>option]:text-white"
                                    >
                                        <option v-for="cat in categoriesFilter" :key="cat.value" :value="cat.value">
                                            {{ cat.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Action Button -->
                                <div class="lg:col-span-2">
                                    <button
                                        type="submit"
                                        class="flex h-full min-h-[58px] w-full items-center justify-center gap-2 rounded-2xl bg-white px-6 text-sm font-bold text-slate-950 shadow-lg transition-all duration-200 hover:bg-slate-100 active:scale-[0.98] dark:bg-white dark:text-[#06090E]"
                                    >
                                        <span>Search Fleet</span>
                                        <ArrowRight class="h-4 w-4" />
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>

                    <!-- CINEMATIC VEHICLE STAGING WITH FLOATING SPEC CHIPS -->
                    <div class="relative mx-auto mt-16 max-w-5xl">
                        <!-- Floor Reflection Backdrop -->
                        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-white/[0.06] to-transparent p-4 sm:p-8">
                            <div class="relative aspect-16/9 overflow-hidden rounded-2xl bg-slate-950/80">
                                <img
                                    src="/images/hero_image.png"
                                    alt="Nordic precision flagship automobile"
                                    class="h-full w-full object-cover object-center transition-transform duration-700 hover:scale-105"
                                />

                                <!-- Vignette overlays -->
                                <div class="absolute inset-0 bg-gradient-to-t from-[#06090E] via-transparent to-black/30"></div>

                                <!-- Floating Spec Chip 1: 0–100 km/h (Top Left) -->
                                <div class="absolute left-4 top-4 sm:left-6 sm:top-6 flex items-center gap-3 rounded-2xl border border-white/15 bg-black/60 px-4 py-2.5 backdrop-blur-xl shadow-xl">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10 text-sky-400">
                                        <Zap class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-mono font-bold tracking-tight text-white">0–100 km/h in 2.8s</p>
                                        <p class="text-[10px] text-slate-400">Launch Control Dual-Motor</p>
                                    </div>
                                </div>

                                <!-- Floating Spec Chip 2: 620 km Range (Top Right) -->
                                <div class="absolute right-4 top-4 sm:right-6 sm:top-6 flex items-center gap-3 rounded-2xl border border-white/15 bg-black/60 px-4 py-2.5 backdrop-blur-xl shadow-xl">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10 text-emerald-400">
                                        <Gauge class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-mono font-bold tracking-tight text-white">620 km Range</p>
                                        <p class="text-[10px] text-slate-400">800V Fast Charging</p>
                                    </div>
                                </div>

                                <!-- Floating Spec Chip 3: PDI Verified (Bottom Left) -->
                                <div class="absolute bottom-4 left-4 sm:bottom-6 sm:left-6 flex items-center gap-3 rounded-2xl border border-white/15 bg-black/60 px-4 py-2.5 backdrop-blur-xl shadow-xl">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10 text-amber-400">
                                        <ShieldCheck class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-mono font-bold tracking-tight text-white">PDI Verified</p>
                                        <p class="text-[10px] text-slate-400">Zero Undocumented Defects</p>
                                    </div>
                                </div>

                                <!-- Floating Spec Chip 4: Atomic Lock (Bottom Right) -->
                                <div class="hidden sm:flex absolute bottom-6 right-6 items-center gap-3 rounded-2xl border border-white/15 bg-black/60 px-4 py-2.5 backdrop-blur-xl shadow-xl">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10 text-white">
                                        <Lock class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-mono font-bold tracking-tight text-white">Atomic Lock</p>
                                        <p class="text-[10px] text-slate-400">Collision-Free Guarantee</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TELEMETRY TRUST STRIP -->
                    <div class="mx-auto mt-12 grid max-w-5xl grid-cols-2 gap-4 border-t border-white/10 pt-8 sm:grid-cols-4 sm:gap-6">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-sky-400">
                                <ShieldCheck class="h-4 w-4" />
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-300">100% Verified</span>
                            </div>
                            <p class="text-xs text-slate-400">Multi-point mechanical audit before each handover</p>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-emerald-400">
                                <Lock class="h-4 w-4" />
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-300">Instant Date Lock</span>
                            </div>
                            <p class="text-xs text-slate-400">Atomic server booking prevents double reservations</p>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-amber-400">
                                <Wrench class="h-4 w-4" />
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-300">PDI Guarantee</span>
                            </div>
                            <p class="text-xs text-slate-400">SVG CAD condition blueprints protect your deposit</p>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-purple-400">
                                <Crown class="h-4 w-4" />
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-300">VIP Rewards</span>
                            </div>
                            <p class="text-xs text-slate-400">Automatic discounts stacking up to 15% off</p>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 2. INTERACTIVE EXPERIENCE BENTO (World-Class Automotive Stage) -->
            <!-- ========================================================= -->
            <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-[#58779D] dark:text-[#93B4D7]">
                        Bespoke Mobility Architecture
                    </p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        Designed around precision engineering.
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-400">
                        Explore our dynamic category spectrum, interactive PDI condition audit blueprint, and real-time VIP loyalty engine.
                    </p>
                </div>

                <!-- Bento Grid Layout -->
                <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-12">

                    <!-- CARD 1 (WIDE, 12 cols): Dynamic Category Spectrum Switcher -->
                    <div class="relative flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm transition dark:border-white/10 dark:bg-[#0A101D] sm:p-8 lg:col-span-12">
                        
                        <!-- Header with Category Tabs -->
                        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-500/20 bg-sky-500/10 px-3 py-1 text-xs font-semibold text-sky-700 dark:text-sky-300">
                                    <Sparkles class="h-3.5 w-3.5" />
                                    <span>Interactive Category Spectrum</span>
                                </span>
                                <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                                    {{ categorySpectrums[activeCategoryTab].headline }}
                                </h3>
                            </div>

                            <!-- Tabs Pill Track -->
                            <div class="flex flex-wrap items-center gap-1.5 rounded-2xl border border-slate-200 bg-slate-100/80 p-1.5 dark:border-white/10 dark:bg-white/5">
                                <button
                                    v-for="(cat, key) in categorySpectrums"
                                    :key="key"
                                    type="button"
                                    @click="activeCategoryTab = key"
                                    :class="activeCategoryTab === key
                                        ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-bold shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white font-medium'"
                                    class="rounded-xl px-3.5 py-1.5 text-xs transition-all cursor-pointer"
                                >
                                    {{ cat.title }}
                                </button>
                            </div>
                        </div>

                        <!-- Active Category Dynamic Showcase Content -->
                        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-center">
                            
                            <!-- Left: Specs and Evocative Copy -->
                            <div class="space-y-6 lg:col-span-7">
                                <div>
                                    <span class="text-xs font-mono uppercase tracking-wider text-[#58779D] dark:text-[#93B4D7]">
                                        {{ categorySpectrums[activeCategoryTab].badge }}
                                    </span>
                                    <h4 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                                        {{ categorySpectrums[activeCategoryTab].model }}
                                    </h4>
                                    <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                        {{ categorySpectrums[activeCategoryTab].description }}
                                    </p>
                                </div>

                                <!-- Dynamic Telemetry Micro-Pills -->
                                <div class="grid grid-cols-3 gap-4 border-y border-slate-200 py-4 dark:border-white/10">
                                    <div>
                                        <p class="text-xs text-slate-400">{{ categorySpectrums[activeCategoryTab].accelLabel }}</p>
                                        <p class="mt-1 text-xl font-mono font-bold text-slate-950 dark:text-white">
                                            {{ categorySpectrums[activeCategoryTab].accel }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">{{ categorySpectrums[activeCategoryTab].rangeLabel }}</p>
                                        <p class="mt-1 text-xl font-mono font-bold text-slate-950 dark:text-white">
                                            {{ categorySpectrums[activeCategoryTab].range }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">{{ categorySpectrums[activeCategoryTab].powerLabel }}</p>
                                        <p class="mt-1 text-xl font-mono font-bold text-slate-950 dark:text-white">
                                            {{ categorySpectrums[activeCategoryTab].power }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-4">
                                    <Link
                                        :href="fleet({ query: { search: categorySpectrums[activeCategoryTab].searchParam } })"
                                        class="inline-flex items-center gap-2 rounded-xl bg-[#06090E] px-5 py-3 text-xs font-semibold text-white shadow-sm transition hover:bg-[#152238] dark:bg-white dark:text-[#06090E] dark:hover:bg-slate-200"
                                    >
                                        <span>View {{ categorySpectrums[activeCategoryTab].title }} in Fleet</span>
                                        <ArrowRight class="h-4 w-4" />
                                    </Link>
                                    <span class="text-xs font-mono text-slate-400">
                                        Drivetrain: {{ categorySpectrums[activeCategoryTab].drivetrain }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right: Vehicle Staged Visual -->
                            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 p-2 shadow-xl dark:border-white/10 lg:col-span-5">
                                <div class="relative aspect-16/10 overflow-hidden rounded-xl">
                                    <img
                                        src="/images/hero_image.png"
                                        :alt="categorySpectrums[activeCategoryTab].model"
                                        class="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                                    <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-white text-xs">
                                        <span class="font-mono font-semibold">Real Rent Hub Staging</span>
                                        <span class="rounded-full bg-emerald-500/80 px-2 py-0.5 text-[10px] font-bold">100% Calibrated</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- CARD 2 (6 cols): Phase 9a PDI Interactive Condition Audit Blueprint -->
                    <div class="relative flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm transition dark:border-white/10 dark:bg-[#0A101D] sm:p-8 lg:col-span-6">
                        
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                    <Wrench class="h-3.5 w-3.5" />
                                    <span>Phase 9a PDI Condition Engine</span>
                                </span>
                                <button
                                    type="button"
                                    @click="resetPdiPreview"
                                    v-if="simulatedScratch"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                                >
                                    <RotateCcw class="h-3 w-3" />
                                    <span>Reset</span>
                                </button>
                            </div>

                            <h3 class="mt-3 text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                Interactive Top-Down CAD Blueprint
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Click vehicle zones or test our simulation to preview how damage items are mapped to your security deposit.
                            </p>
                        </div>

                        <!-- Mini Interactive SVG Car Blueprint -->
                        <div class="my-6 grid grid-cols-1 items-center gap-6 sm:grid-cols-12">
                            
                            <!-- The SVG Car Silhouette -->
                            <div class="relative mx-auto flex w-full max-w-[200px] flex-col items-center rounded-2xl border border-slate-200/80 bg-slate-50 p-4 dark:border-white/10 dark:bg-black/30 sm:col-span-6">
                                <div class="relative aspect-[1/2] w-full max-w-[160px] select-none">
                                    <svg viewBox="0 0 200 400" class="h-full w-full drop-shadow-sm" xmlns="http://www.w3.org/2000/svg">
                                        <!-- Wheels -->
                                        <rect x="18" y="70" width="16" height="42" rx="6" class="fill-slate-800 dark:fill-slate-600" />
                                        <rect x="166" y="70" width="16" height="42" rx="6" class="fill-slate-800 dark:fill-slate-600" />
                                        <rect x="18" y="270" width="16" height="42" rx="6" class="fill-slate-800 dark:fill-slate-600" />
                                        <rect x="166" y="270" width="16" height="42" rx="6" class="fill-slate-800 dark:fill-slate-600" />

                                        <!-- Outer Chassis Body -->
                                        <path
                                            d="M 60 25 C 75 15, 125 15, 140 25 C 165 35, 168 80, 168 110 C 168 130, 172 160, 172 200 C 172 240, 168 270, 168 290 C 168 330, 162 375, 140 385 C 125 390, 75 390, 60 385 C 38 375, 32 330, 32 290 C 32 270, 28 240, 28 200 C 28 160, 32 130, 32 110 C 32 80, 35 35, 60 25 Z"
                                            class="fill-slate-200 stroke-slate-400 stroke-1 dark:fill-[#121A29] dark:stroke-slate-700"
                                        />

                                        <!-- Front Bumper Zone -->
                                        <path
                                            d="M 55 30 Q 100 18 145 30 L 140 50 Q 100 38 60 50 Z"
                                            @click="selectZone('front_bumper')"
                                            class="fill-slate-300/60 hover:fill-amber-500/40 cursor-pointer transition dark:fill-white/10"
                                        />

                                        <!-- Engine Hood -->
                                        <path
                                            d="M 60 55 Q 100 45 140 55 L 145 125 Q 100 120 55 125 Z"
                                            @click="selectZone('hood')"
                                            class="fill-slate-300/40 hover:fill-amber-500/40 cursor-pointer transition dark:fill-white/5"
                                        />

                                        <!-- Windshield -->
                                        <path
                                            d="M 58 132 Q 100 126 142 132 L 136 170 Q 100 166 64 170 Z"
                                            @click="selectZone('windshield')"
                                            class="fill-sky-400/20 stroke-sky-400/50 hover:fill-sky-400/40 cursor-pointer transition"
                                        />

                                        <!-- Roof Structure -->
                                        <rect
                                            x="62" y="176" width="76" height="82" rx="12"
                                            class="fill-slate-300/30 hover:fill-amber-500/30 dark:fill-white/5 cursor-pointer transition"
                                        />

                                        <!-- Rear Boot -->
                                        <path
                                            d="M 60 320 Q 100 324 140 320 L 145 365 Q 100 375 55 365 Z"
                                            @click="selectZone('trunk')"
                                            class="fill-slate-300/40 hover:fill-amber-500/40 cursor-pointer transition dark:fill-white/5"
                                        />

                                        <!-- Active Simulated Damage Pin -->
                                        <g v-if="simulatedScratch">
                                            <circle cx="100" cy="38" r="10" class="fill-amber-500/30 animate-ping" />
                                            <circle cx="100" cy="38" r="6" class="fill-amber-500 stroke-white stroke-2" />
                                        </g>
                                    </svg>
                                </div>
                                <span class="mt-2 text-[10px] font-mono uppercase tracking-wider text-slate-400">Top-Down CAD</span>
                            </div>

                            <!-- Right: Real-Time Deposit Calculation -->
                            <div class="space-y-4 sm:col-span-6">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Security Deposit Ledger</p>
                                    <div class="mt-2 flex items-center justify-between text-xs">
                                        <span class="text-slate-600 dark:text-slate-400">Total Pre-Auth Hold:</span>
                                        <span class="font-mono font-bold text-slate-900 dark:text-white">₹5,000</span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between text-xs">
                                        <span class="text-amber-600 dark:text-amber-400">
                                            {{ simulatedScratch ? 'Front Scuff Deduction:' : 'Undocumented Damages:' }}
                                        </span>
                                        <span class="font-mono font-bold" :class="simulatedScratch ? 'text-rose-500' : 'text-slate-400'">
                                            {{ simulatedScratch ? '- ₹1,500' : '₹0' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 border-t border-slate-200 pt-2 dark:border-white/10 flex items-center justify-between text-xs font-semibold">
                                        <span class="text-slate-900 dark:text-white">Net Auto-Refund:</span>
                                        <span class="font-mono text-emerald-600 dark:text-emerald-400">
                                            {{ simulatedScratch ? '₹3,500' : '₹5,000 (100%)' }}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="toggleSimulatedScratch"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-900 shadow-xs transition hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
                                >
                                    {{ simulatedScratch ? 'Clear Simulation' : 'Simulate Bumper Scuff (₹1,500)' }}
                                </button>
                            </div>

                        </div>

                        <div class="border-t border-slate-200/80 pt-4 dark:border-white/10 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-1.5 font-medium text-emerald-600 dark:text-emerald-400">
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                Deductions strictly transparent
                            </span>
                            <span class="font-mono text-[11px]">Phase 9a PDI</span>
                        </div>

                    </div>

                    <!-- CARD 3 (6 cols): Phase 10a VIP Loyalty Passport Engine -->
                    <div class="relative flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm transition dark:border-white/10 dark:bg-[#0A101D] sm:p-8 lg:col-span-6">
                        
                        <div>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-purple-500/20 bg-purple-500/10 px-3 py-1 text-xs font-semibold text-purple-700 dark:text-purple-300">
                                <Crown class="h-3.5 w-3.5" />
                                <span>Phase 10a VIP Loyalty Engine</span>
                            </span>

                            <h3 class="mt-3 text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                Interactive VIP Tier Passport
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Real Rent rewards repeat travelers with progressive automatic rate discounts and zero deposit holds.
                            </p>
                        </div>

                        <!-- Interactive Tier Switcher Tabs -->
                        <div class="my-5 flex items-center justify-between gap-1 rounded-2xl border border-slate-200 bg-slate-100 p-1 dark:border-white/10 dark:bg-white/5">
                            <button
                                v-for="(tier, key) in tiers"
                                :key="key"
                                type="button"
                                @click="selectedTier = key"
                                :class="selectedTier === key
                                    ? 'bg-[#06090E] text-white dark:bg-white dark:text-[#06090E] font-bold shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white font-medium'"
                                class="flex-1 rounded-xl py-1.5 text-center text-xs transition-all cursor-pointer"
                            >
                                <span class="capitalize">{{ key }}</span>
                                <span class="text-[10px] opacity-70 block">{{ tier.discountPct }}%</span>
                            </button>
                        </div>

                        <!-- Dynamic Metallic Card Preview -->
                        <div
                            class="relative overflow-hidden rounded-2xl border p-5 shadow-xl transition-all duration-300 bg-gradient-to-br"
                            :class="currentTierData.cardGradient"
                        >
                            <div class="flex items-center justify-between text-white">
                                <div class="flex items-center gap-2">
                                    <Crown class="h-5 w-5 text-amber-400" />
                                    <span class="text-sm font-extrabold tracking-wider uppercase font-mono">{{ currentTierData.name }}</span>
                                </div>
                                <span class="text-xs font-mono rounded-full bg-white/10 px-2.5 py-0.5 border border-white/20">
                                    {{ currentTierData.discountPct }}% DISCOUNT
                                </span>
                            </div>

                            <!-- Live Price Deduction Example -->
                            <div class="mt-5 border-t border-white/10 pt-4 flex items-end justify-between text-white">
                                <div>
                                    <p class="text-[10px] text-slate-400">Daily Baseline: ₹{{ formatPrice(currentTierData.dailyRate) }}</p>
                                    <p class="text-xs text-emerald-400 font-mono">You Save: ₹{{ formatPrice(discountAmount) }} / day</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-slate-400 uppercase">Your Net Fare</p>
                                    <p class="text-xl font-mono font-bold text-white">₹{{ formatPrice(netRate) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Staged Perks List -->
                        <ul class="mt-4 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                            <li v-for="(perk, idx) in currentTierData.perks" :key="idx" class="flex items-center gap-2">
                                <CheckCircle2 class="h-3.5 w-3.5 shrink-0 text-emerald-500" />
                                <span>{{ perk }}</span>
                            </li>
                        </ul>

                        <div class="mt-6 border-t border-slate-200/80 pt-4 dark:border-white/10 flex items-center justify-between">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Zero subscription fees</span>
                            <Link href="/client/loyalty" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#58779D] dark:text-[#93B4D7] hover:underline">
                                <span>Explore VIP Passport Hub</span>
                                <ArrowRight class="h-3.5 w-3.5" />
                            </Link>
                        </div>

                    </div>

                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 3. FEATURED FLEET SHOWCASE (Widescreen 16:9 Staged Cards)  -->
            <!-- ========================================================= -->
            <section class="border-t border-slate-200 bg-slate-100/60 py-20 transition-colors duration-200 dark:border-white/10 dark:bg-[#070B12] sm:py-28">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-[#58779D] dark:text-[#93B4D7]">
                                Curated Fleet Spotlight
                            </p>
                            <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                                Precision automobiles.
                            </h2>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                Pre-inspected, detailed, and ready for instant atomic reservation.
                            </p>
                        </div>

                        <Link
                            :href="fleet()"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-xs font-bold text-slate-950 shadow-sm transition hover:bg-slate-50 dark:border dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
                        >
                            <span>Browse Complete Fleet</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <!-- Widescreen 16:9 Vehicle Cards Grid -->
                    <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="car in showcaseCars"
                            :key="car.id"
                            class="group relative flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-white/10 dark:bg-[#0A101D] dark:hover:border-white/20"
                        >
                            <!-- 16:9 Vehicle Visual -->
                            <div class="relative aspect-16/10 overflow-hidden bg-slate-950">
                                <img
                                    :src="carImage(car)"
                                    :alt="`${car.make} ${car.model}`"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

                                <!-- Overlay Badges -->
                                <div class="absolute left-3.5 top-3.5 flex items-center gap-1.5">
                                    <span class="rounded-full border border-white/20 bg-black/50 px-2.5 py-1 text-[11px] font-mono font-semibold text-white backdrop-blur-md">
                                        {{ car.year }}
                                    </span>
                                    <span v-if="car.fuel_type" class="flex items-center gap-1 rounded-full border border-white/20 bg-black/50 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur-md">
                                        <Zap v-if="car.fuel_type === 'Electric'" class="h-3 w-3 text-sky-400" />
                                        <Fuel v-else class="h-3 w-3 text-amber-400" />
                                        <span>{{ car.fuel_type }}</span>
                                    </span>
                                </div>

                                <!-- Star Rating Badge -->
                                <div class="absolute right-3.5 top-3.5 flex items-center gap-1 rounded-full border border-white/20 bg-black/50 px-2.5 py-1 text-[11px] font-semibold text-amber-400 backdrop-blur-md">
                                    <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                                    <span>{{ car.average_rating ? Number(car.average_rating).toFixed(1) : '4.9' }}</span>
                                </div>
                            </div>

                            <!-- Vehicle Details -->
                            <div class="flex flex-1 flex-col justify-between p-6">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-[#58779D] dark:text-[#93B4D7]">
                                        {{ car.make }}
                                    </p>
                                    <h3 class="mt-1 text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                        {{ car.model }}
                                    </h3>
                                    <p v-if="car.description" class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                        {{ car.description }}
                                    </p>
                                </div>

                                <!-- Specs Bar (Seats, Fuel, Transmission) -->
                                <div class="mt-5 grid grid-cols-3 gap-2 border-t border-slate-100 py-3 text-center dark:border-white/5">
                                    <div class="flex flex-col items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                                        <Users class="h-3.5 w-3.5" />
                                        <span>{{ car.seats || 5 }} Seats</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                                        <Gauge class="h-3.5 w-3.5" />
                                        <span>{{ car.transmission || 'Auto' }}</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                                        <ShieldCheck class="h-3.5 w-3.5 text-emerald-500" />
                                        <span>PDI Clear</span>
                                    </div>
                                </div>

                                <!-- Price & Booking CTA -->
                                <div class="mt-4 flex items-center justify-between border-t border-slate-200/80 pt-4 dark:border-white/10">
                                    <div>
                                        <p class="text-[10px] text-slate-400 uppercase font-mono tracking-wider">Daily Rate</p>
                                        <p class="text-xl font-mono font-extrabold tracking-tight text-slate-950 dark:text-white">
                                            ₹{{ formatPrice(car.price_per_day) }}
                                        </p>
                                    </div>

                                    <Link
                                        :href="fleetShow({ car: car.id })"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-[#06090E] px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-[#152238] active:scale-[0.98] dark:bg-white dark:text-[#06090E] dark:hover:bg-slate-200"
                                    >
                                        <span>Reserve</span>
                                        <ArrowRight class="h-3.5 w-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>

                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 4. 3-STEP CONCIERGE TIMELINE (Friction-Free Handover)       -->
            <!-- ========================================================= -->
            <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-[#58779D] dark:text-[#93B4D7]">
                        Friction-Free Handover Protocol
                    </p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        Drive within 60 seconds.
                    </h2>
                    <p class="mt-4 text-base text-slate-600 dark:text-slate-400">
                        Our entire lifecycle from booking to hub collection is calibrated to eliminate counter queues and paperwork friction.
                    </p>
                </div>

                <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                    
                    <!-- Step 01 -->
                    <div class="relative rounded-3xl border border-slate-200/80 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-[#0A101D]">
                        <div class="flex items-center justify-between">
                            <span class="text-4xl font-extrabold font-mono text-slate-200 dark:text-white/10">01</span>
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#06090E] text-white dark:bg-white dark:text-[#06090E]">
                                <Calendar class="h-5 w-5" />
                            </div>
                        </div>
                        <h3 class="mt-6 text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                            Reserve & Lock Dates
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            Select your travel window on our 2-month range calendar. Atomic server locks instantly guarantee zero overlapping collisions before checkout.
                        </p>
                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <CheckCircle2 class="h-4 w-4" />
                            <span>100% Collision-free locking</span>
                        </div>
                    </div>

                    <!-- Step 02 -->
                    <div class="relative rounded-3xl border border-slate-200/80 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-[#0A101D]">
                        <div class="flex items-center justify-between">
                            <span class="text-4xl font-extrabold font-mono text-slate-200 dark:text-white/10">02</span>
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#06090E] text-white dark:bg-white dark:text-[#06090E]">
                                <ShieldCheck class="h-5 w-5" />
                            </div>
                        </div>
                        <h3 class="mt-6 text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                            Digital KYC & VIP Perk Stacking
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            Upload your driver credentials once. Your VIP loyalty tier automatically stacks with promo coupons for instant mathematical savings.
                        </p>
                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 text-xs font-semibold text-purple-600 dark:text-purple-400 flex items-center gap-1.5">
                            <Crown class="h-4 w-4" />
                            <span>Up to 15% VIP rate reduction</span>
                        </div>
                    </div>

                    <!-- Step 03 -->
                    <div class="relative rounded-3xl border border-slate-200/80 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-[#0A101D]">
                        <div class="flex items-center justify-between">
                            <span class="text-4xl font-extrabold font-mono text-slate-200 dark:text-white/10">03</span>
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#06090E] text-white dark:bg-white dark:text-[#06090E]">
                                <QrCode class="h-5 w-5" />
                            </div>
                        </div>
                        <h3 class="mt-6 text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                            Contactless QR Handover & Drive
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            Arrive at any airport terminal hub, present your digital gate pass QR code, review the timestamped PDI condition blueprint, and take the wheel.
                        </p>
                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 text-xs font-semibold text-sky-600 dark:text-sky-400 flex items-center gap-1.5">
                            <Zap class="h-4 w-4" />
                            <span>Average 60-second dispatch</span>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 5. CLOSING CTA BANNER (Architectural Obsidian Void)        -->
            <!-- ========================================================= -->
            <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                <div class="relative isolate overflow-hidden rounded-3xl border border-white/15 bg-gradient-to-b from-[#0F1726] to-[#06090E] px-6 py-16 text-center text-white shadow-2xl sm:px-16 sm:py-24">
                    <!-- Subtle Luminous Rim Light -->
                    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_70%_50%_at_50%_0%,rgba(56,189,248,0.2),transparent_70%)]"></div>

                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-semibold tracking-wide text-slate-300">
                        <Sparkles class="h-3.5 w-3.5 text-sky-400" />
                        <span>Ready For Immediate Handover</span>
                    </div>

                    <h2 class="mx-auto mt-6 max-w-3xl text-3xl font-extrabold tracking-[-0.03em] sm:text-5xl lg:text-6xl">
                        Command Extraordinary Machinery.
                    </h2>
                    
                    <p class="mx-auto mt-6 max-w-xl text-base leading-relaxed text-slate-300 sm:text-lg">
                        Operational hubs live in Mumbai, Delhi, Bengaluru, and Goa. Reserve today with guaranteed dates and immediate confirmation.
                    </p>

                    <div class="mt-10 flex flex-col justify-center gap-4 sm:flex-row sm:items-center">
                        <Link
                            :href="fleet()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-slate-950 shadow-md transition hover:bg-slate-100 active:scale-[0.98] dark:bg-white dark:text-[#06090E]"
                        >
                            <span>Explore Full Fleet</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>

                        <Link
                            :href="presentation()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-8 py-3.5 text-sm font-semibold text-white backdrop-blur-md transition hover:bg-white/15 hover:border-white/30"
                        >
                            <span>Launch Evaluator Presentation</span>
                            <ArrowUpRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <div class="mt-12 flex flex-wrap items-center justify-center gap-6 border-t border-white/10 pt-8 text-xs font-mono text-slate-400">
                        <span>Zero Hidden Charges</span>
                        <span>•</span>
                        <span>Full Refund Within 48h</span>
                        <span>•</span>
                        <span>A4 Digital Legal Contract</span>
                        <span>•</span>
                        <span>24/7 Road Concierge</span>
                    </div>
                </div>
            </section>

        </div>
    </HomeLayout>
</template>
