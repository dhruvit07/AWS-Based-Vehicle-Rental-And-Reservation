<script setup lang="ts">
import BookingCalendar, { type BookedRange } from '@/components/BookingCalendar.vue';
import MockPaymentModal from '@/components/MockPaymentModal.vue';
import PromoCodeInput, { type AppliedCoupon } from '@/components/PromoCodeInput.vue';
import RentalExtras from '@/components/RentalExtras.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import StarRating from '@/components/StarRating.vue';
import HomeLayout from '@/layouts/HomeLayout.vue';
import { login, fleet } from '@/routes';
import { book } from '@/routes/fleet';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Clock, Lock, MessageSquare, ShieldAlert, ShieldCheck, Star, ThumbsUp, ArrowLeft, Fuel, Gauge, Calendar } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Car {
    id: number;
    make: string;
    model: string;
    price_per_day: string | number;
    image_url: string;
    images: { url: string; alt: string }[];
    fuel_type: string;
    transmission: string;
    year: string | number;
    description: string;
    status: string;
}

const page = usePage();
const car = computed(() => page.props.car as Car);
const bookedRanges = computed(() => (page.props.bookedRanges || []) as BookedRange[]);
const authUser = computed(() => (page.props as any).auth?.user as { id: number; name: string; role: string; kyc_status?: string } | null | undefined);
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

const discountAmount = computed(() => {
    return appliedCoupon.value ? appliedCoupon.value.discount_amount : 0;
});

const taxableAmount = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value);
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
            url: car.value?.image_url || '',
            alt: `${car.value?.make || 'Rental'} ${car.value?.model || 'car'}`,
        },
    ];
});

const commonLocations = [
    'Downtown Office',
    'Airport Terminal 1',
    'Airport Terminal 2',
    'Central Station',
    'Mall Plaza',
    'Hotel District',
    'Business District',
];
</script>

<template>
    <Head :title="`Reserve ${car.make} ${car.model} | Real Rent Car`" />

    <HomeLayout>
        <div class="min-h-screen bg-[#FBFBFD] pb-20 text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100">

            <!-- Checkout Hero Staging -->
            <section class="relative isolate overflow-hidden bg-[#101D35] px-5 py-12 text-white dark:bg-[#070A0F] sm:py-16">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(120,160,210,0.25),rgba(255,255,255,0))]"></div>
                <div class="pointer-events-none absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full bg-[#58779D]/20 blur-3xl"></div>

                <div class="mx-auto max-w-7xl">
                    <nav class="mb-4 flex items-center gap-2 text-xs text-slate-300">
                        <Link :href="fleet()" class="inline-flex items-center gap-1 transition hover:text-white">
                            <ArrowLeft class="h-3 w-3" />
                            <span>Fleet Directory</span>
                        </Link>
                        <span>/</span>
                        <span class="text-white">{{ car.make }} {{ car.model }}</span>
                    </nav>

                    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        Reserve {{ car.make }} {{ car.model }}
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base">
                        Select your rental duration, customize protection, and complete your reservation with instant vehicle locking.
                    </p>
                </div>
            </section>

            <div class="mx-auto max-w-7xl px-5 pt-10 sm:px-8">
                <!-- 2-Column Checkout Layout -->
                <div class="grid items-start gap-8 lg:grid-cols-12">

                    <!-- Left Main Column (Details, Calendar, Options, Reviews) -->
                    <div class="space-y-8 lg:col-span-8">

                        <!-- Vehicle Overview Showcase Card -->
                        <section class="glass-card overflow-hidden">
                            <div class="relative aspect-16/9 overflow-hidden bg-slate-950 sm:aspect-21/9">
                                <img
                                    v-if="images[0]?.url"
                                    :src="images[0].url"
                                    :alt="images[0].alt"
                                    class="h-full w-full object-cover"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>

                                <div class="absolute bottom-4 left-4 flex items-center gap-2">
                                    <span class="rounded-full border border-white/20 bg-black/40 px-3 py-1 text-xs font-semibold text-white backdrop-blur-md">
                                        {{ car.year }} Model
                                    </span>
                                    <span class="rounded-full border border-white/20 bg-black/40 px-3 py-1 text-xs font-semibold text-white backdrop-blur-md">
                                        {{ car.transmission }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-6 sm:p-8">
                                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                                    <div>
                                        <h2 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                                            {{ car.make }} {{ car.model }}
                                        </h2>

                                        <div class="mt-2 flex items-center gap-2">
                                            <StarRating
                                                :rating="averageRating"
                                                :readonly="true"
                                                size="sm"
                                                :compact="true"
                                                :reviews-count="reviewsList.length"
                                            />
                                            <span v-if="reviewsList.length > 0" class="text-xs text-slate-500 dark:text-slate-400">
                                                • {{ reviewsList.length }} verified {{ reviewsList.length === 1 ? 'renter review' : 'renter reviews' }}
                                            </span>
                                        </div>

                                        <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                            <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-slate-100/70 px-3 py-1.5 font-medium capitalize text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                                <Fuel class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                                {{ car.fuel_type }}
                                            </span>

                                            <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-slate-100/70 px-3 py-1.5 font-medium capitalize text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                                <Gauge class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                                {{ car.transmission }}
                                            </span>

                                            <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-slate-100/70 px-3 py-1.5 font-medium text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                                <Calendar class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                                {{ car.year }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="shrink-0 rounded-2xl border border-black/5 bg-[#101D35] px-5 py-4 text-white shadow-xs dark:border-white/10 dark:bg-white/5 sm:text-right">
                                        <p class="text-2xl font-bold tracking-tight">
                                            {{ formatPrice(dailyRate) }}
                                        </p>
                                        <p class="text-xs text-slate-300 dark:text-slate-400">
                                            daily rate
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-6 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                    {{ car.description || 'Pristine condition, verified mechanics, and sanitized cabin ready for dispatch.' }}
                                </p>
                            </div>
                        </section>

                        <!-- Reservation Configuration Console -->
                        <section class="glass-card p-6 sm:p-8">
                            <div class="mb-6">
                                <h3 class="text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                    Schedule & Itinerary
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Select dates using the 2-month tactile range picker below.
                                </p>
                            </div>

                            <!-- Driver Verification Banner -->
                            <div
                                v-if="authUser && isKycVerified"
                                class="mb-6 flex items-center justify-between rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-900 dark:text-emerald-100"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                                        <ShieldCheck class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Verified Driver</p>
                                        <p class="text-emerald-700 dark:text-emerald-400">Your documents are authenticated. Rapid key pickup enabled.</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else-if="authUser"
                                class="mb-6 flex flex-col justify-between gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-900 dark:text-amber-100 sm:flex-row sm:items-center"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
                                        <Clock v-if="authUser?.kyc_status === 'pending'" class="h-4 w-4" />
                                        <ShieldAlert v-else-if="authUser?.kyc_status === 'rejected'" class="h-4 w-4" />
                                        <ShieldCheck v-else class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-amber-950 dark:text-amber-200">
                                            {{ authUser?.kyc_status === 'pending'
                                                ? 'License Review In Progress'
                                                : (authUser?.kyc_status === 'rejected' ? 'KYC Action Required' : 'Driver License Advisory') }}
                                        </p>
                                        <p class="text-amber-800 dark:text-amber-300">
                                            {{ authUser?.kyc_status === 'pending'
                                                ? 'You can complete reservation now; drive once verification is completed.'
                                                : 'Verify driving license online for contactless key pickup, or present physical license at desk.' }}
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    href="/settings/kyc"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-amber-400/40 bg-white/80 px-3 py-1.5 text-xs font-semibold text-amber-950 transition hover:bg-white dark:bg-white/10 dark:text-white"
                                >
                                    <span>{{ authUser?.kyc_status === 'pending' ? 'Status' : 'Verify License' }}</span>
                                    <span>&rarr;</span>
                                </Link>
                            </div>

                            <form class="space-y-8" @submit.prevent="openPaymentModal">
                                <!-- Calendar Section -->
                                <div class="space-y-4">
                                    <BookingCalendar
                                        v-model:start-date="form.start_date"
                                        v-model:end-date="form.end_date"
                                        :booked-ranges="bookedRanges"
                                        :min-date="minBookingDate"
                                    />

                                    <!-- Date summary pills -->
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                                                Pickup Date
                                            </p>
                                            <p class="mt-1 text-sm font-bold text-slate-950 dark:text-white">
                                                {{ formatDisplayDate(form.start_date) || 'Select on calendar' }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 dark:border-white/10 dark:bg-white/5">
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                                                Return Date
                                            </p>
                                            <p class="mt-1 text-sm font-bold text-slate-950 dark:text-white">
                                                {{ formatDisplayDate(form.end_date) || 'Select on calendar' }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Conflict or error notices -->
                                    <div v-if="form.errors.start_date || form.errors.end_date || dateRangeHasConflict" class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-xs text-rose-600 dark:text-rose-400">
                                        <p v-if="form.errors.start_date" class="font-medium">
                                            {{ form.errors.start_date }}
                                        </p>
                                        <p v-if="form.errors.end_date" class="font-medium">
                                            {{ form.errors.end_date }}
                                        </p>
                                        <p v-if="dateRangeHasConflict" class="font-medium">
                                            The selected rental window conflicts with an existing reservation. Please choose alternate dates.
                                        </p>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200/80 dark:border-white/10"></div>

                                <!-- Locations -->
                                <div>
                                    <h4 class="mb-3 text-sm font-bold text-slate-950 dark:text-white">
                                        Pickup & Return Stations
                                    </h4>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="pickup_location" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                                Pickup Station *
                                            </label>

                                            <select
                                                id="pickup_location"
                                                v-model="form.pickup_location"
                                                required
                                                class="apple-input w-full px-3.5 py-2.5 text-xs"
                                                :class="{ 'border-rose-500': form.errors.pickup_location }"
                                            >
                                                <option value="">Select pickup station</option>
                                                <option v-for="location in commonLocations" :key="location" :value="location">
                                                    {{ location }}
                                                </option>
                                            </select>

                                            <p v-if="form.errors.pickup_location" class="mt-1 text-xs text-rose-500">
                                                {{ form.errors.pickup_location }}
                                            </p>
                                        </div>

                                        <div>
                                            <label for="return_location" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                                Return Station *
                                            </label>

                                            <select
                                                id="return_location"
                                                v-model="form.return_location"
                                                required
                                                class="apple-input w-full px-3.5 py-2.5 text-xs"
                                                :class="{ 'border-rose-500': form.errors.return_location }"
                                            >
                                                <option value="">Select return station</option>
                                                <option v-for="location in commonLocations" :key="location" :value="location">
                                                    {{ location }}
                                                </option>
                                            </select>

                                            <p v-if="form.errors.return_location" class="mt-1 text-xs text-rose-500">
                                                {{ form.errors.return_location }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200/80 dark:border-white/10"></div>

                                <!-- Rental Add-ons -->
                                <RentalExtras
                                    v-model="form.extras"
                                    :rental-days="rentalDays"
                                />

                                <div class="border-t border-slate-200/80 dark:border-white/10"></div>

                                <!-- Mobile/Inline Submit Button -->
                                <div class="lg:hidden">
                                    <button
                                        type="submit"
                                        :disabled="!canSubmit || form.processing"
                                        class="apple-btn-primary flex w-full items-center justify-center gap-2 py-3.5 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Lock class="h-4 w-4" />
                                        <span>
                                            {{ form.processing ? 'Finalizing booking...' : (!page.props.auth?.user ? 'Log in to continue' : `Proceed to Payment • ${formatPrice(total)}`) }}
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </section>

                        <!-- Customer Reviews Section -->
                        <section class="glass-card p-6 sm:p-8">
                            <div class="mb-6 flex flex-col justify-between gap-4 border-b border-slate-200/80 pb-6 dark:border-white/10 sm:flex-row sm:items-center">
                                <div>
                                    <h3 class="text-xl font-bold tracking-tight text-slate-950 dark:text-white">
                                        Driver Reviews & Feedback
                                    </h3>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Feedback from verified renters who previously booked this model.
                                    </p>
                                </div>

                                <button
                                    v-if="eligibleReservation"
                                    type="button"
                                    @click="isReviewModalOpen = true"
                                    class="apple-btn-primary inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold"
                                >
                                    <Star class="h-3.5 w-3.5 fill-current" />
                                    <span>Rate Your Trip</span>
                                </button>
                            </div>

                            <!-- Rating Summary Hero & Distribution -->
                            <div v-if="reviewsList.length > 0" class="mb-8 grid gap-6 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-6 dark:border-white/10 dark:bg-white/5 sm:grid-cols-12 sm:items-center">
                                <!-- Score Hero -->
                                <div class="text-center sm:col-span-5 sm:border-r sm:border-slate-200/80 sm:pr-6 dark:sm:border-white/10">
                                    <div class="text-5xl font-black text-slate-950 dark:text-white">
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
                                        <span>{{ recommendationPercentage }}% recommendation rate</span>
                                    </div>
                                </div>

                                <!-- Star Distribution Bars -->
                                <div class="space-y-2.5 sm:col-span-7 sm:pl-2">
                                    <div
                                        v-for="star in [5, 4, 3, 2, 1]"
                                        :key="star"
                                        class="flex items-center gap-3 text-xs"
                                    >
                                        <button
                                            type="button"
                                            class="flex w-12 items-center gap-1 font-semibold text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
                                            @click="selectedRatingFilter = selectedRatingFilter === star ? null : star"
                                        >
                                            <span>{{ star }}</span>
                                            <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                                        </button>
                                        <div class="relative h-2 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="[
                                                    selectedRatingFilter === star ? 'bg-[#101D35] dark:bg-white' : 'bg-amber-400',
                                                ]"
                                                :style="{ width: `${ratingDistribution[star]?.percentage || 0}%` }"
                                            ></div>
                                        </div>
                                        <span class="w-8 text-right font-medium text-slate-500 dark:text-slate-400">
                                            {{ ratingDistribution[star]?.count || 0 }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter chip -->
                            <div v-if="selectedRatingFilter" class="mb-4 flex items-center justify-between rounded-lg bg-slate-100 px-3 py-2 text-xs dark:bg-white/5">
                                <span class="text-slate-600 dark:text-slate-300">
                                    Filtering by <strong>{{ selectedRatingFilter }}★</strong> ratings
                                </span>
                                <button type="button" @click="selectedRatingFilter = null" class="font-bold text-[#58779D] hover:underline dark:text-[#7094BF]">
                                    Clear filter
                                </button>
                            </div>

                            <!-- Reviews List -->
                            <div v-if="filteredReviews.length > 0" class="space-y-3.5">
                                <div
                                    v-for="review in filteredReviews"
                                    :key="review.id"
                                    class="rounded-xl border border-slate-200/80 bg-white/60 p-4 transition hover:border-slate-300 dark:border-white/10 dark:bg-white/5 sm:p-5"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#101D35] text-xs font-bold text-white dark:bg-white dark:text-slate-950">
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

                            <div v-else class="rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-white/10">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-white/5">
                                    <MessageSquare class="h-5 w-5" />
                                </div>
                                <p class="mt-2 text-xs font-bold text-slate-950 dark:text-white">No reviews recorded yet</p>
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                    Complete your booking to leave the inaugural review for this vehicle.
                                </p>
                            </div>
                        </section>
                    </div>

                    <!-- Right Sticky Summary Invoice Card -->
                    <aside class="space-y-6 lg:sticky lg:top-24 lg:col-span-4">
                        <section class="glass-card overflow-hidden">
                            <!-- Invoice Header -->
                            <div class="border-b border-black/10 bg-[#101D35] p-6 text-white dark:border-white/10 dark:bg-[#070A0F]">
                                <h3 class="text-lg font-bold tracking-tight">
                                    Order Summary
                                </h3>
                                <p class="text-xs text-slate-300">
                                    {{ car.make }} {{ car.model }}
                                </p>
                            </div>

                            <div class="space-y-5 p-6">
                                <!-- Vehicle Duration Breakdown -->
                                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 text-xs dark:border-white/10 dark:bg-white/5">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-600 dark:text-slate-400">Rental duration</span>
                                        <span class="font-bold text-slate-950 dark:text-white">
                                            {{ rentalDays > 0 ? `${rentalDays} day${rentalDays === 1 ? '' : 's'}` : '—' }}
                                        </span>
                                    </div>

                                    <div class="mt-2.5 flex items-center justify-between gap-3">
                                        <span class="text-slate-600 dark:text-slate-400">Daily vehicle rate</span>
                                        <span class="font-semibold text-slate-950 dark:text-white">
                                            {{ formatPrice(dailyRate) }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-200/80 pt-3 dark:border-white/10">
                                        <span class="font-medium text-slate-800 dark:text-slate-200">Vehicle rental cost</span>
                                        <span class="font-bold text-slate-950 dark:text-white">
                                            {{ formatPrice(carBaseCost) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Add-ons Breakdown -->
                                <div v-if="selectedExtrasBreakdown.length > 0" class="space-y-2 border-t border-slate-200/80 pt-4 dark:border-white/10">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                                        Selected Add-ons ({{ selectedExtrasBreakdown.length }})
                                    </p>
                                    <div
                                        v-for="extra in selectedExtrasBreakdown"
                                        :key="extra.id"
                                        class="flex items-center justify-between gap-3 text-xs"
                                    >
                                        <span class="text-slate-600 dark:text-slate-400">{{ extra.name }}</span>
                                        <span class="font-semibold text-slate-950 dark:text-white">
                                            +{{ formatPrice(extra.cost) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Subtotal -->
                                <div class="flex items-center justify-between gap-3 border-t border-slate-200/80 pt-4 text-xs dark:border-white/10">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">Subtotal</span>
                                    <span class="font-bold text-slate-950 dark:text-white">
                                        {{ formatPrice(subtotal) }}
                                    </span>
                                </div>

                                <!-- Promo Code Input -->
                                <div class="border-t border-slate-200/80 pt-4 dark:border-white/10">
                                    <PromoCodeInput
                                        :subtotal="subtotal"
                                        :applied-coupon="appliedCoupon"
                                        @apply="handleCouponApplied"
                                        @remove="handleCouponRemoved"
                                    />
                                </div>

                                <!-- Discount Row (if applied) -->
                                <div
                                    v-if="appliedCoupon && discountAmount > 0"
                                    class="flex items-center justify-between gap-3 text-xs text-emerald-600 dark:text-emerald-400"
                                >
                                    <span class="font-medium">Discount ({{ appliedCoupon.code }})</span>
                                    <span class="font-bold">
                                        -{{ formatPrice(discountAmount) }}
                                    </span>
                                </div>

                                <!-- Taxes -->
                                <div class="flex items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400">
                                    <span>GST (7%)</span>
                                    <span class="font-semibold text-slate-950 dark:text-white">
                                        {{ formatPrice(tax) }}
                                    </span>
                                </div>

                                <!-- Grand Total -->
                                <div class="border-t border-dashed border-slate-300 pt-4 dark:border-white/20">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-slate-950 dark:text-white">
                                                Total Due
                                            </p>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                                All taxes and selected options included
                                            </p>
                                        </div>

                                        <p class="font-mono text-xl font-bold text-slate-950 dark:text-white">
                                            {{ formatPrice(total) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Primary Submit Action -->
                                <button
                                    type="button"
                                    :disabled="!canSubmit || form.processing"
                                    @click="openPaymentModal"
                                    class="apple-btn-primary flex w-full items-center justify-center gap-2 py-3 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <Lock class="h-4 w-4" />
                                    <span>
                                        {{ !page.props.auth?.user ? 'Log in to continue' : 'Proceed to Payment' }}
                                    </span>
                                </button>

                                <Link :href="fleet()" class="block text-center text-xs font-semibold text-[#58779D] transition hover:underline dark:text-[#7094BF]">
                                    ← Return to fleet
                                </Link>
                            </div>
                        </section>
                    </aside>
                </div>
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
