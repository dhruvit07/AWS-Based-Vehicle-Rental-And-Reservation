<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import HomeLayout from '@/layouts/HomeLayout.vue';
import BookingCalendar, { type BookedRange } from '@/components/BookingCalendar.vue';
import RentalExtras from '@/components/RentalExtras.vue';
import PromoCodeInput, { type AppliedCoupon } from '@/components/PromoCodeInput.vue';
import MockPaymentModal from '@/components/MockPaymentModal.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import StarRating from '@/components/StarRating.vue';
import { login, fleet } from '@/routes';
import { book } from '@/routes/fleet';
import {
    ArrowLeft,
    Calendar,
    CheckCircle2,
    Clock,
    Crown,
    Fuel,
    Gauge,
    Info,
    Lock,
    MapPin,
    MessageSquare,
    Radio,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    Star,
    Tag,
    ThumbsUp,
    UserCheck,
    Wrench,
    Zap,
} from 'lucide-vue-next';

interface Car {
    id: number;
    make: string;
    model: string;
    price_per_day: string | number;
    image_url: string;
    images?: { url: string; alt: string }[];
    fuel_type: string;
    transmission: string;
    year: string | number;
    description: string;
    status: string;
}

const page = usePage();
const car = computed(() => page.props.car as Car);
const bookedRanges = computed(() => (page.props.bookedRanges || []) as BookedRange[]);
const authUser = computed(() => (page.props as any).auth?.user as { id: number; name: string; role: string; kyc_status?: string; loyalty_tier?: string } | null | undefined);
const isKycVerified = computed(() => authUser.value?.kyc_status === 'verified');

const minBookingDate = computed(() => {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const d = String(today.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
});

const form = useForm({
    start_date: '',
    end_date: '',
    pickup_location: '',
    return_location: '',
    extras: [] as string[],
    coupon_code: '',
    payment_method: 'credit_card',
    transaction_id: '',
});

const appliedCoupon = ref<AppliedCoupon | null>(null);
const isPaymentModalOpen = ref(false);

const reviewsList = computed(() => (page.props.reviews || []) as Array<{
    id: number;
    rating: number;
    title: string | null;
    comment: string;
    created_at?: string;
    formatted_date?: string;
    user: { name: string; initials: string };
}>);

const ratingDistribution = computed(() => (page.props.ratingDistribution || {}) as Record<number, { star: number; count: number; percentage: number }>);
const eligibleReservation = computed(() => (page.props as any).eligibleReservation as any);

const isReviewModalOpen = ref(false);
const selectedRatingFilter = ref<number | null>(null);

const averageRating = computed(() => {
    if (reviewsList.value.length === 0) return 0;
    const sum = reviewsList.value.reduce((acc, r) => acc + Number(r.rating), 0);
    return Math.round((sum / reviewsList.value.length) * 10) / 10;
});

const recommendationPercentage = computed(() => {
    if (reviewsList.value.length === 0) return 0;
    const positive = reviewsList.value.filter((r) => r.rating >= 4).length;
    return Math.round((positive / reviewsList.value.length) * 100);
});

const filteredReviews = computed(() => {
    if (!selectedRatingFilter.value) {
        return reviewsList.value;
    }
    return reviewsList.value.filter((r) => r.rating === selectedRatingFilter.value);
});

const extrasCatalog = [
    {
        id: 'insurance',
        name: 'Zero-Deductible Insurance',
        rate: 499,
        type: 'per_day' as const,
    },
    {
        id: 'gps',
        name: 'GPS Navigation System',
        rate: 149,
        type: 'per_day' as const,
    },
    {
        id: 'child_seat',
        name: 'Child Safety Seat',
        rate: 199,
        type: 'per_day' as const,
    },
    {
        id: 'additional_driver',
        name: 'Additional Driver',
        rate: 499,
        type: 'fixed' as const,
    },
];

const rentalDays = computed(() => {
    if (!form.start_date || !form.end_date) return 0;

    const start = new Date(`${form.start_date}T00:00:00`);
    const end = new Date(`${form.end_date}T00:00:00`);
    const difference = end.getTime() - start.getTime();

    return Math.max(0, Math.round(difference / (1000 * 60 * 60 * 24)));
});

const dateRangeHasConflict = computed(() => {
    if (!form.start_date || !form.end_date) return false;
    return bookedRanges.value.some((range) => {
        return !(form.end_date < range.from || form.start_date > range.to);
    });
});

const formatDisplayDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-').map(Number);
    const dateObj = new Date(y, m - 1, d);
    return dateObj.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const dailyRate = computed(() => Number(car.value?.price_per_day) || 0);

const carBaseCost = computed(() =>
    rentalDays.value > 0 ? rentalDays.value * dailyRate.value : 0
);

const selectedExtrasBreakdown = computed(() => {
    return extrasCatalog
        .filter((item) => form.extras.includes(item.id))
        .map((item) => {
            const cost = item.type === 'per_day' ? item.rate * rentalDays.value : item.rate;
            return {
                ...item,
                cost,
            };
        });
});

const extrasTotal = computed(() => {
    return selectedExtrasBreakdown.value.reduce((acc, item) => acc + item.cost, 0);
});

const subtotal = computed(() => {
    if (rentalDays.value <= 0) return 0;
    return carBaseCost.value + extrasTotal.value;
});

const loyaltyTier = computed(() => (page.props as any).loyaltyTier || (authUser.value as any)?.loyalty_tier_meta || null);

const loyaltyDiscountAmount = computed(() => {
    if (!loyaltyTier.value || !loyaltyTier.value.discount_rate) return 0;
    return Math.round(subtotal.value * Number(loyaltyTier.value.discount_rate) * 100) / 100;
});

const discountAmount = computed(() => {
    return appliedCoupon.value ? appliedCoupon.value.discount_amount : 0;
});

const taxableAmount = computed(() => {
    return Math.max(0, subtotal.value - loyaltyDiscountAmount.value - discountAmount.value);
});

const tax = computed(() => taxableAmount.value * 0.07);

const total = computed(() => taxableAmount.value + tax.value);

const formatPrice = (amount: number | string) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(amount) || 0);

const canSubmit = computed(() =>
    Boolean(
        form.start_date &&
        form.end_date &&
        form.pickup_location &&
        form.return_location &&
        rentalDays.value > 0 &&
        !dateRangeHasConflict.value
    )
);

const handleCouponApplied = (coupon: AppliedCoupon) => {
    appliedCoupon.value = coupon;
    form.coupon_code = coupon.code;
};

const handleCouponRemoved = () => {
    appliedCoupon.value = null;
    form.coupon_code = '';
};

const openPaymentModal = () => {
    const user = page.props.auth?.user;

    if (!user) {
        router.get(login.url());
        return;
    }

    if (user.role === 'admin') {
        alert('You cannot book as an admin.');
        return;
    }

    if (user.role !== 'client') {
        alert('Your role does not allow booking.');
        return;
    }

    if (!canSubmit.value) {
        return;
    }

    isPaymentModalOpen.value = true;
};

const handlePaymentSubmit = (data: {
    payment_method: 'credit_card' | 'upi' | 'cash';
    transaction_id: string;
}) => {
    form.payment_method = data.payment_method;
    form.transaction_id = data.transaction_id;
    form.coupon_code = appliedCoupon.value?.code || '';
    isPaymentModalOpen.value = false;
    form.post(book.url(car.value.id));
};

watch(
    () => form.pickup_location,
    (newLocation) => {
        if (newLocation && !form.return_location) {
            form.return_location = newLocation;
        }
    }
);

const images = computed(() => {
    if (car.value?.images?.length) {
        return car.value.images;
    }

    return [
        {
            url: car.value?.image_url || '/images/hero_image.png',
            alt: `${car.value?.make || 'Rental'} ${car.value?.model || 'car'}`,
        },
    ];
});

const commonLocations = [
    'Mumbai Airport (BOM) Terminal 2',
    'Delhi Aerocity (DEL) Hub',
    'Bengaluru Central (BLR) Hub',
    'Goa Coastal (GOI) Hub',
    'Downtown Executive Lounge',
    'Financial District Hub',
];
</script>

<template>
    <Head :title="`Booking Studio: ${car.make} ${car.model} | Real Rent Car`" />

    <HomeLayout>
        <div class="min-h-screen bg-[#F8F9FA] pb-24 text-slate-900 transition-colors duration-200 dark:bg-[#06090E] dark:text-slate-100">

            <!-- STUDIO TOP NAVIGATION RIBBON -->
            <div class="sticky top-[72px] z-40 border-b border-black/5 bg-white/80 backdrop-blur-xl dark:border-white/10 dark:bg-[#06090E]/85">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <Link
                        :href="fleet()"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 transition hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Return to Fleet</span>
                    </Link>

                    <div class="flex items-center gap-3">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        <span class="font-mono text-xs font-semibold tracking-wide text-emerald-600 dark:text-emerald-400">
                            Instant Handover Ready • Atomic Date Lock
                        </span>
                    </div>
                </div>
            </div>

            <!-- STUDIO MAIN CONTAINER -->
            <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">

                <!-- TWO-COLUMN STUDIO ARCHITECTURE -->
                <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">

                    <!-- ========================================================= -->
                    <!-- LEFT COLUMN: GUIDED 4-STEP JOURNEY (8 cols)              -->
                    <!-- ========================================================= -->
                    <div class="space-y-8 lg:col-span-8">

                        <!-- Vehicle Identity Banner -->
                        <div class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-[#0A101D] sm:p-8">
                            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono uppercase tracking-widest text-[#58779D] dark:text-[#93B4D7]">
                                            {{ car.make }}
                                        </span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 font-mono text-[11px] font-semibold text-slate-700 dark:bg-white/10 dark:text-slate-300">
                                            {{ car.year }}
                                        </span>
                                    </div>
                                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                                        {{ car.model }}
                                    </h1>
                                    <div class="mt-2 flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                        <div class="flex items-center gap-1 text-amber-500">
                                            <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                            <span class="font-bold text-slate-900 dark:text-white">{{ averageRating.toFixed(1) }}</span>
                                        </div>
                                        <span>•</span>
                                        <span>{{ reviewsList.length }} verified renter reviews</span>
                                        <span>•</span>
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">Phase 9a PDI Certified</span>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-right dark:border-white/10 dark:bg-white/[0.04]">
                                    <p class="font-mono text-xs uppercase tracking-wider text-slate-400">Daily Rate</p>
                                    <p class="font-mono text-2xl font-extrabold text-slate-950 dark:text-white">
                                        {{ formatPrice(dailyRate) }}
                                    </p>
                                    <p class="text-[10px] text-slate-400">+ standard deposit</p>
                                </div>
                            </div>

                            <p class="mt-4 text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ car.description || 'Pristine condition, verified mechanics, and sanitized cabin ready for immediate hub dispatch.' }}
                            </p>
                        </div>

                        <!-- STEP 1: RESERVATION DATES & STATIONS -->
                        <section class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-[#06090E] font-mono text-xs font-bold text-white dark:bg-white dark:text-[#06090E]">
                                    1
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold tracking-tight text-slate-950 dark:text-white">
                                        Reservation Dates & Hub Stations
                                    </h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Select your travel window on our tactile calendar and designate pickup/return hubs.
                                    </p>
                                </div>
                            </div>

                            <!-- Tactile 2-Month Calendar -->
                            <BookingCalendar
                                v-model:start-date="form.start_date"
                                v-model:end-date="form.end_date"
                                :booked-ranges="bookedRanges"
                                :min-date="minBookingDate"
                            />

                            <!-- Hub Selectors -->
                            <div class="grid grid-cols-1 gap-4 rounded-3xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-[#0A101D] sm:grid-cols-2 sm:p-6">
                                <div>
                                    <label for="pickup_location" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        <MapPin class="h-3.5 w-3.5 text-sky-500" />
                                        <span>Pick-up Hub *</span>
                                    </label>
                                    <select
                                        id="pickup_location"
                                        v-model="form.pickup_location"
                                        required
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-xs font-medium text-slate-900 outline-none transition focus:border-[#58779D] focus:bg-white dark:border-white/10 dark:bg-white/[0.04] dark:text-white dark:[&>option]:bg-[#0A101D] cursor-pointer"
                                        :class="{ 'border-rose-500': form.errors.pickup_location }"
                                    >
                                        <option value="">Select pick-up station</option>
                                        <option v-for="loc in commonLocations" :key="loc" :value="loc">{{ loc }}</option>
                                    </select>
                                    <p v-if="form.errors.pickup_location" class="mt-1 text-xs text-rose-500">
                                        {{ form.errors.pickup_location }}
                                    </p>
                                </div>

                                <div>
                                    <label for="return_location" class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        <MapPin class="h-3.5 w-3.5 text-amber-500" />
                                        <span>Return Hub *</span>
                                    </label>
                                    <select
                                        id="return_location"
                                        v-model="form.return_location"
                                        required
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-xs font-medium text-slate-900 outline-none transition focus:border-[#58779D] focus:bg-white dark:border-white/10 dark:bg-white/[0.04] dark:text-white dark:[&>option]:bg-[#0A101D] cursor-pointer"
                                        :class="{ 'border-rose-500': form.errors.return_location }"
                                    >
                                        <option value="">Select return station</option>
                                        <option v-for="loc in commonLocations" :key="loc" :value="loc">{{ loc }}</option>
                                    </select>
                                    <p v-if="form.errors.return_location" class="mt-1 text-xs text-rose-500">
                                        {{ form.errors.return_location }}
                                    </p>
                                </div>
                            </div>

                            <!-- Date Conflict Warning -->
                            <div
                                v-if="dateRangeHasConflict"
                                class="flex items-center gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-semibold text-rose-700 dark:text-rose-400"
                            >
                                <ShieldAlert class="h-5 w-5 shrink-0" />
                                <span>The dates you have selected overlap with an existing confirmed trip. Please select unreserved days.</span>
                            </div>
                        </section>

                        <!-- STEP 2: CURATED EXTRAS & ADD-ONS -->
                        <section class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-[#06090E] font-mono text-xs font-bold text-white dark:bg-white dark:text-[#06090E]">
                                    2
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold tracking-tight text-slate-950 dark:text-white">
                                        Curated Extras & Protection
                                    </h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Enhance your itinerary with zero-deductible insurance and certified accessories.
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-[#0A101D] sm:p-6">
                                <RentalExtras
                                    v-model="form.extras"
                                    :rental-days="rentalDays"
                                />
                            </div>
                        </section>

                        <!-- STEP 3: VIP LOYALTY & PROMO COUPONS -->
                        <section class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-[#06090E] font-mono text-xs font-bold text-white dark:bg-white dark:text-[#06090E]">
                                    3
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold tracking-tight text-slate-950 dark:text-white">
                                        VIP Loyalty Perks & Coupon Stacking
                                    </h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Member discounts stack automatically with valid promotion vouchers.
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-[#0A101D] sm:p-6 space-y-4">
                                <!-- VIP Tier Banner (if client has tier discount) -->
                                <div
                                    v-if="loyaltyTier && loyaltyTier.discount_percent > 0"
                                    class="flex items-center justify-between rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white">
                                            <Crown class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-mono font-bold uppercase tracking-wider text-amber-900 dark:text-amber-200">
                                                {{ loyaltyTier.label || 'VIP Member' }} Active
                                            </p>
                                            <p class="text-xs text-amber-800 dark:text-amber-300">
                                                {{ loyaltyTier.discount_percent }}% automatic reduction applies to your subtotal.
                                            </p>
                                        </div>
                                    </div>
                                    <span class="font-mono text-sm font-extrabold text-amber-700 dark:text-amber-300">
                                        -{{ formatPrice(loyaltyDiscountAmount) }}
                                    </span>
                                </div>

                                <div v-else class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50/70 p-3.5 text-xs dark:border-white/10 dark:bg-white/[0.03]">
                                    <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                        <Crown class="h-4 w-4 text-slate-400" />
                                        <span>Standard Member tier. Complete this trip to progress toward Silver VIP (5% off).</span>
                                    </div>
                                    <Link href="/client/loyalty" class="text-xs font-semibold text-[#58779D] hover:underline dark:text-[#93B4D7]">
                                        View Tiers
                                    </Link>
                                </div>

                                <!-- Promo Code Input -->
                                <div>
                                    <PromoCodeInput
                                        :subtotal="subtotal"
                                        :applied-coupon="appliedCoupon"
                                        @apply="handleCouponApplied"
                                        @remove="handleCouponRemoved"
                                    />
                                </div>
                            </div>
                        </section>

                        <!-- STEP 4: DRIVER VERIFICATION & KYC -->
                        <section class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-[#06090E] font-mono text-xs font-bold text-white dark:bg-white dark:text-[#06090E]">
                                    4
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold tracking-tight text-slate-950 dark:text-white">
                                        Driver Verification & Handover KYC
                                    </h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Contactless gate-pass dispatch requires authenticated driving credentials.
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200/90 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-[#0A101D] sm:p-6">
                                <!-- Verified Driver Status -->
                                <div
                                    v-if="authUser && isKycVerified"
                                    class="flex items-center justify-between rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                            <ShieldCheck class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                                Verified Driver Credentials
                                            </p>
                                            <p class="text-xs text-emerald-700 dark:text-emerald-400">
                                                Identity and driving license verified. Eligible for 60-second contactless QR handover.
                                            </p>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-emerald-500/20 px-3 py-1 font-mono text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                                        KYC VALID
                                    </span>
                                </div>

                                <!-- Unverified / Guest Driver Status -->
                                <div
                                    v-else
                                    class="flex flex-col justify-between gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 sm:flex-row sm:items-center"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-white shrink-0">
                                            <UserCheck class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-amber-950 dark:text-amber-200">
                                                {{ authUser ? 'Complete Digital KYC For Instant Handover' : 'Driver Account Required' }}
                                            </p>
                                            <p class="text-xs text-amber-800 dark:text-amber-300">
                                                You can lock dates and reserve now. License verification can be completed before hub pickup.
                                            </p>
                                        </div>
                                    </div>

                                    <Link
                                        v-if="authUser"
                                        href="/settings/kyc"
                                        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-amber-700"
                                    >
                                        Verify License &rarr;
                                    </Link>
                                    <Link
                                        v-else
                                        :href="login()"
                                        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-[#06090E] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#152238] dark:bg-white dark:text-[#06090E]"
                                    >
                                        Log In / Sign Up
                                    </Link>
                                </div>
                            </div>
                        </section>

                    </div>

                    <!-- ========================================================= -->
                    <!-- RIGHT COLUMN: STICKY GLASS ORDER VAULT (4 cols)           -->
                    <!-- ========================================================= -->
                    <aside class="space-y-6 lg:sticky lg:top-24 lg:col-span-4">
                        <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-xl dark:border-white/10 dark:bg-[#0A101D]">
                            
                            <!-- Vault Staging Header -->
                            <div class="border-b border-black/10 bg-[#06090E] p-5 text-white dark:border-white/10">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-mono uppercase tracking-widest text-slate-400">Order Vault</span>
                                    <span class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[10px] font-mono font-bold text-emerald-400">
                                        Live Calculation
                                    </span>
                                </div>
                                <h3 class="mt-2 text-lg font-bold tracking-tight">
                                    {{ car.make }} {{ car.model }}
                                </h3>
                                <p class="text-xs text-slate-400">
                                    {{ car.year }} • {{ car.transmission }} • {{ car.fuel_type }}
                                </p>
                            </div>

                            <!-- Real-Time Cost Ledger -->
                            <div class="p-5 sm:p-6 space-y-4 text-xs">

                                <!-- Rental Window Chip -->
                                <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 dark:border-white/5 dark:bg-white/[0.03]">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 dark:text-slate-400">Duration:</span>
                                        <span class="font-bold text-slate-950 dark:text-white">
                                            {{ rentalDays > 0 ? `${rentalDays} Day${rentalDays === 1 ? '' : 's'}` : 'Dates pending' }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between">
                                        <span class="text-slate-500 dark:text-slate-400">Daily Rate:</span>
                                        <span class="font-mono font-semibold text-slate-950 dark:text-white">
                                            {{ formatPrice(dailyRate) }}
                                        </span>
                                    </div>
                                    <div class="mt-2 border-t border-slate-200/60 pt-2 dark:border-white/5 flex items-center justify-between font-semibold">
                                        <span class="text-slate-800 dark:text-slate-200">Vehicle Base Cost:</span>
                                        <span class="font-mono text-slate-950 dark:text-white">
                                            {{ formatPrice(carBaseCost) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Extras Lines -->
                                <div v-if="selectedExtrasBreakdown.length > 0" class="space-y-1.5 border-t border-slate-100 pt-3 dark:border-white/5">
                                    <p class="text-[10px] font-mono uppercase tracking-wider text-[#58779D] dark:text-[#93B4D7]">
                                        Selected Add-ons
                                    </p>
                                    <div
                                        v-for="extra in selectedExtrasBreakdown"
                                        :key="extra.id"
                                        class="flex items-center justify-between text-slate-600 dark:text-slate-400"
                                    >
                                        <span>{{ extra.name }}</span>
                                        <span class="font-mono font-semibold text-slate-950 dark:text-white">
                                            +{{ formatPrice(extra.cost) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Subtotal -->
                                <div class="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-white/5 font-semibold">
                                    <span class="text-slate-700 dark:text-slate-300">Subtotal:</span>
                                    <span class="font-mono text-slate-950 dark:text-white">
                                        {{ formatPrice(subtotal) }}
                                    </span>
                                </div>

                                <!-- VIP Discount Line -->
                                <div
                                    v-if="loyaltyDiscountAmount > 0"
                                    class="flex items-center justify-between text-purple-600 dark:text-purple-400 font-semibold"
                                >
                                    <span class="flex items-center gap-1">
                                        <Crown class="h-3 w-3" />
                                        <span>VIP ({{ loyaltyTier?.discount_percent }}% Off)</span>
                                    </span>
                                    <span class="font-mono">
                                        -{{ formatPrice(loyaltyDiscountAmount) }}
                                    </span>
                                </div>

                                <!-- Coupon Deduction Line -->
                                <div
                                    v-if="appliedCoupon && discountAmount > 0"
                                    class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-semibold"
                                >
                                    <span class="flex items-center gap-1">
                                        <Tag class="h-3 w-3" />
                                        <span>Coupon ({{ appliedCoupon.code }})</span>
                                    </span>
                                    <span class="font-mono">
                                        -{{ formatPrice(discountAmount) }}
                                    </span>
                                </div>

                                <!-- GST 7% -->
                                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                    <span>GST (7%):</span>
                                    <span class="font-mono font-semibold text-slate-900 dark:text-white">
                                        {{ formatPrice(tax) }}
                                    </span>
                                </div>

                                <!-- Security Deposit Note -->
                                <div class="rounded-xl border border-sky-500/20 bg-sky-500/5 p-3 text-[11px] text-sky-800 dark:text-sky-300">
                                    <div class="flex items-center justify-between font-semibold">
                                        <span>Refundable Security Deposit:</span>
                                        <span class="font-mono">₹5,000.00</span>
                                    </div>
                                    <p class="mt-1 text-[10px] text-sky-600 dark:text-sky-400">
                                        Authorized hold returned upon verified PDI vehicle check-in.
                                    </p>
                                </div>

                                <!-- Grand Total Ledger Line -->
                                <div class="border-t border-slate-200 pt-4 dark:border-white/10">
                                    <div class="flex items-baseline justify-between">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Total Due</p>
                                            <p class="text-[10px] text-slate-400">All fees and taxes included</p>
                                        </div>
                                        <p class="font-mono text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                                            {{ formatPrice(total) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Action Button: Trigger Payment Modal -->
                                <button
                                    type="button"
                                    :disabled="!canSubmit || form.processing"
                                    @click="openPaymentModal"
                                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#06090E] py-3.5 text-xs font-bold text-white shadow-md transition hover:bg-[#152238] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-[#06090E] dark:hover:bg-slate-200 cursor-pointer"
                                >
                                    <Lock class="h-4 w-4" />
                                    <span>
                                        {{ !page.props.auth?.user ? 'Log in to Reserve' : 'Confirm & Proceed to Payment' }}
                                    </span>
                                </button>

                                <!-- Trust Guarantee Badges -->
                                <div class="space-y-2 border-t border-slate-100 pt-4 text-[11px] text-slate-500 dark:border-white/5 dark:text-slate-400">
                                    <div class="flex items-center gap-2">
                                        <ShieldCheck class="h-3.5 w-3.5 text-emerald-500" />
                                        <span>100% Refundable Security Deposit</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CheckCircle2 class="h-3.5 w-3.5 text-sky-500" />
                                        <span>Phase 9a PDI Condition Guarantee</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Clock class="h-3.5 w-3.5 text-amber-500" />
                                        <span>Free Itinerary Cancellation up to 48h</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </aside>

                </div>

                <!-- ========================================================= -->
                <!-- BELOW STUDIO: VERIFIED REVIEWS & RATING DISTRIBUTION      -->
                <!-- ========================================================= -->
                <section class="mt-16 rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-[#0A101D] sm:p-8">
                    <div class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 dark:border-white/10 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                Verified Renter Reviews & Feedback
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Genuine impressions from authenticated drivers who completed reservations with this vehicle.
                            </p>
                        </div>

                        <button
                            v-if="eligibleReservation"
                            type="button"
                            @click="isReviewModalOpen = true"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-[#06090E] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#152238] dark:bg-white dark:text-[#06090E]"
                        >
                            <Star class="h-3.5 w-3.5 fill-current" />
                            <span>Rate Your Trip</span>
                        </button>
                    </div>

                    <!-- Rating Score & Breakdown Bar Chart -->
                    <div v-if="reviewsList.length > 0" class="my-8 grid gap-8 rounded-2xl border border-slate-100 bg-slate-50/70 p-6 dark:border-white/5 dark:bg-white/[0.02] sm:grid-cols-12 sm:items-center">
                        <!-- Score Hero -->
                        <div class="text-center sm:col-span-5 sm:border-r sm:border-slate-200/80 sm:pr-6 dark:sm:border-white/10">
                            <div class="font-mono text-5xl font-black text-slate-950 dark:text-white">
                                {{ averageRating.toFixed(1) }}
                            </div>
                            <div class="mt-2 flex justify-center">
                                <StarRating :rating="averageRating" :readonly="true" size="md" />
                            </div>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Based on {{ reviewsList.length }} verified {{ reviewsList.length === 1 ? 'review' : 'reviews' }}
                            </p>
                            <div v-if="recommendationPercentage > 0" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <ThumbsUp class="h-3.5 w-3.5" />
                                <span>{{ recommendationPercentage }}% Recommendation Rate</span>
                            </div>
                        </div>

                        <!-- Star Distribution Breakdown Bars -->
                        <div class="space-y-2.5 sm:col-span-7 sm:pl-2">
                            <div
                                v-for="star in [5, 4, 3, 2, 1]"
                                :key="star"
                                class="flex items-center gap-3 text-xs"
                            >
                                <button
                                    type="button"
                                    class="flex w-12 items-center gap-1 font-semibold text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white cursor-pointer"
                                    @click="selectedRatingFilter = selectedRatingFilter === star ? null : star"
                                >
                                    <span>{{ star }}</span>
                                    <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                                </button>
                                <div class="relative h-2 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="selectedRatingFilter === star ? 'bg-[#06090E] dark:bg-white' : 'bg-amber-400'"
                                        :style="{ width: `${ratingDistribution[star]?.percentage || 0}%` }"
                                    ></div>
                                </div>
                                <span class="w-8 text-right font-mono font-medium text-slate-500 dark:text-slate-400">
                                    {{ ratingDistribution[star]?.count || 0 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Chip -->
                    <div v-if="selectedRatingFilter" class="mb-4 flex items-center justify-between rounded-xl bg-slate-100 px-3.5 py-2 text-xs dark:bg-white/5">
                        <span class="text-slate-700 dark:text-slate-300">
                            Showing verified <strong>{{ selectedRatingFilter }}★</strong> ratings only
                        </span>
                        <button type="button" @click="selectedRatingFilter = null" class="font-bold text-[#58779D] hover:underline dark:text-[#93B4D7]">
                            Reset Filter
                        </button>
                    </div>

                    <!-- Reviews Cards List -->
                    <div v-if="filteredReviews.length > 0" class="space-y-3.5">
                        <div
                            v-for="review in filteredReviews"
                            :key="review.id"
                            class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition hover:border-slate-200 dark:border-white/5 dark:bg-white/[0.02] sm:p-5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#06090E] text-xs font-bold text-white dark:bg-white dark:text-[#06090E]">
                                        {{ review.user.initials }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-950 dark:text-white">{{ review.user.name }}</span>
                                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                                <ShieldCheck class="h-3 w-3" />
                                                Verified Renter
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400">{{ review.formatted_date }}</p>
                                    </div>
                                </div>

                                <StarRating :rating="review.rating" :readonly="true" size="xs" />
                            </div>

                            <div class="mt-3">
                                <h4 v-if="review.title" class="mb-1 text-xs font-bold text-slate-950 dark:text-white">
                                    {{ review.title }}
                                </h4>
                                <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                    {{ review.comment }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-2xl border border-dashed border-slate-200 p-8 text-center dark:border-white/10">
                        <MessageSquare class="mx-auto h-6 w-6 text-slate-400" />
                        <p class="mt-2 text-xs font-bold text-slate-950 dark:text-white">No reviews recorded yet</p>
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                            Complete your reservation to be the first verified driver to review this model.
                        </p>
                    </div>
                </section>

            </div>
        </div>

        <!-- Simulated Payment Modal -->
        <MockPaymentModal
            :is-open="isPaymentModalOpen"
            :amount="total"
            @close="isPaymentModalOpen = false"
            @submit="handlePaymentSubmit"
        />

        <!-- Review Modal -->
        <ReviewModal
            :is-open="isReviewModalOpen"
            :reservation="eligibleReservation ? { id: eligibleReservation.id, reservation_number: eligibleReservation.reservation_number, start_date: eligibleReservation.start_date, end_date: eligibleReservation.end_date, car: car } : null"
            @close="isReviewModalOpen = false"
            @submitted="router.reload()"
        />
    </HomeLayout>
</template>
