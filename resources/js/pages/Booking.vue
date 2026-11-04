<script setup lang="ts">
import BookingCalendar, { type BookedRange } from '@/components/BookingCalendar.vue';
import MockPaymentModal from '@/components/MockPaymentModal.vue';
import PromoCodeInput, { type AppliedCoupon } from '@/components/PromoCodeInput.vue';
import RentalExtras from '@/components/RentalExtras.vue';
import ReviewModal from '@/components/ReviewModal.vue';
import StarRating from '@/components/StarRating.vue';
import HomeLayout from '@/layouts/HomeLayout.vue';
import { login } from '@/routes';
import { book } from '@/routes/fleet';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Clock, Lock, MessageSquare, ShieldAlert, ShieldCheck, Star, ThumbsUp } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Car {
    id: number;
    make: string;
    model: string;
    price_per_day: string;
    image_url: string;
    images: { url: string; alt: string }[];
    fuel_type: string;
    transmission: string;
    year: string;
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
    <HomeLayout>
        <div class="min-h-screen bg-[#F4F6FA] pb-16">
            <!-- Page heading -->
            <section class="bg-[#101D35] px-4 py-10 text-white sm:py-14">
                <div class="mx-auto max-w-7xl">
                    <nav class="mb-5 flex items-center gap-2 text-sm text-slate-300">
                        <a href="/fleet" class="transition hover:text-white">
                            Fleet
                        </a>
                        <span>/</span>
                        <span class="text-white">Book your car</span>
                    </nav>

                    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-[#C5D0DE]">
                        Complete Checkout
                    </p>

                    <h1 class="text-3xl font-bold sm:text-4xl">
                        Reserve your vehicle
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Customize your journey with flexible add-ons, apply exclusive coupons, and finalize your booking securely.
                    </p>
                </div>
            </section>

            <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <div class="grid items-start gap-8 lg:grid-cols-3">
                    <!-- Main column -->
                    <div class="space-y-7 lg:col-span-2">
                        <!-- Vehicle details -->
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                            <div class="relative h-64 bg-slate-200 sm:h-96">
                                <img
                                    v-if="images[0]?.url"
                                    :src="images[0].url"
                                    :alt="images[0].alt"
                                    class="h-full w-full object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-full items-center justify-center text-slate-500"
                                >
                                    Vehicle image unavailable
                                </div>

                                <div class="absolute bottom-4 left-4 rounded-lg bg-[#101D35]/90 px-4 py-2 text-sm font-semibold text-white backdrop-blur-xs">
                                    {{ car.year }} model
                                </div>
                            </div>

                            <div class="p-5 sm:p-8">
                                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                                    <div>
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#58779D]">
                                            Selected vehicle
                                        </p>

                                        <h2 class="text-2xl font-bold text-[#101D35] sm:text-3xl">
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
                                            <span v-if="reviewsList.length > 0" class="text-xs text-slate-500">
                                                • {{ reviewsList.length }} verified {{ reviewsList.length === 1 ? 'renter review' : 'renter reviews' }}
                                            </span>
                                        </div>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm capitalize text-slate-700">
                                                {{ car.fuel_type }}
                                            </span>

                                            <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm capitalize text-slate-700">
                                                {{ car.transmission }}
                                            </span>

                                            <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">
                                                {{ car.year }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="shrink-0 rounded-xl bg-[#101D35] px-5 py-4 text-white sm:text-right">
                                        <p class="text-2xl font-bold">
                                            {{ formatPrice(dailyRate) }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-300">
                                            per day
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-6 leading-7 text-slate-600">
                                    {{ car.description || 'Enjoy a comfortable and convenient journey with this rental vehicle.' }}
                                </p>
                            </div>
                        </section>

                        <!-- Booking form -->
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-8">
                            <div class="mb-8 flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#101D35] text-white">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="text-xl font-bold text-[#101D35] sm:text-2xl">
                                        Reservation Details
                                    </h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Select your schedule, pickup stations, and optional vehicle upgrades.
                                    </p>
                                </div>
                            </div>

                            <!-- Driver Verification Banner -->
                            <div
                                v-if="authUser && isKycVerified"
                                class="mb-8 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3.5 text-emerald-900"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                                        <ShieldCheck class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">
                                                Verified Driver
                                            </p>
                                            <span class="rounded-full bg-emerald-200/80 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">
                                                Fast-Track Pickup
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-emerald-700">
                                            Your driving credentials are authenticated. Keys will be ready for immediate handover.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else-if="authUser"
                                class="mb-8 flex flex-col justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-amber-900 sm:flex-row sm:items-center"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
                                        <Clock v-if="authUser?.kyc_status === 'pending'" class="h-4 w-4" />
                                        <ShieldAlert v-else-if="authUser?.kyc_status === 'rejected'" class="h-4 w-4" />
                                        <ShieldCheck v-else class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-amber-950">
                                            {{ authUser?.kyc_status === 'pending'
                                                ? 'KYC Verification In Progress'
                                                : (authUser?.kyc_status === 'rejected' ? 'KYC Action Required' : 'Driver License Verification Advisory') }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-amber-800">
                                            {{ authUser?.kyc_status === 'pending'
                                                ? 'Your documents are currently under review. You can complete this reservation now and drive once verified.'
                                                : 'Verify your driving license online for contactless key pickup, or present physical documents at the counter.' }}
                                        </p>
                                    </div>
                                </div>
                                <a
                                    href="/settings/kyc"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 shadow-2xs transition hover:bg-amber-100"
                                >
                                    <span>{{ authUser?.kyc_status === 'pending' ? 'View Review Status' : 'Verify License' }}</span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>

                            <form class="space-y-8" @submit.prevent="openPaymentModal">
                                <!-- 1. Rental period -->
                                <div class="space-y-5">
                                    <h3 class="flex items-center gap-2 font-semibold text-[#101D35]">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-[#58779D]">
                                            1
                                        </span>
                                        Rental period *
                                    </h3>

                                    <BookingCalendar
                                        v-model:start-date="form.start_date"
                                        v-model:end-date="form.end_date"
                                        :booked-ranges="bookedRanges"
                                        :min-date="minBookingDate"
                                    />

                                    <!-- Quick selection summary -->
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="rounded-xl border border-slate-200 bg-[#F4F6FA] p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-[#58779D]">
                                                Pickup date
                                            </p>
                                            <p class="mt-1.5 text-base font-bold text-[#101D35]">
                                                {{ formatDisplayDate(form.start_date) || 'Select on calendar' }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl border border-slate-200 bg-[#F4F6FA] p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wider text-[#58779D]">
                                                Return date
                                            </p>
                                            <p class="mt-1.5 text-base font-bold text-[#101D35]">
                                                {{ formatDisplayDate(form.end_date) || 'Select on calendar' }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Error / validation notices -->
                                    <div v-if="form.errors.start_date || form.errors.end_date || dateRangeHasConflict" class="rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800">
                                        <p v-if="form.errors.start_date" class="font-medium">
                                            {{ form.errors.start_date }}
                                        </p>
                                        <p v-if="form.errors.end_date" class="font-medium">
                                            {{ form.errors.end_date }}
                                        </p>
                                        <p v-if="dateRangeHasConflict" class="font-medium">
                                            The selected rental period overlaps with an existing reservation. Please select available dates.
                                        </p>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200"></div>

                                <!-- 2. Locations -->
                                <div>
                                    <h3 class="mb-4 flex items-center gap-2 font-semibold text-[#101D35]">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-[#58779D]">
                                            2
                                        </span>
                                        Pickup and return locations
                                    </h3>

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div>
                                            <label for="pickup_location" class="mb-2 block text-sm font-semibold text-slate-700">
                                                Pickup location *
                                            </label>

                                            <select
                                                id="pickup_location"
                                                v-model="form.pickup_location"
                                                required
                                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-slate-800 outline-none transition focus:border-[#58779D] focus:ring-2 focus:ring-[#58779D]/20"
                                                :class="{ 'border-red-500': form.errors.pickup_location }"
                                            >
                                                <option value="">Select pickup location</option>
                                                <option v-for="location in commonLocations" :key="location" :value="location">
                                                    {{ location }}
                                                </option>
                                            </select>

                                            <p v-if="form.errors.pickup_location" class="mt-2 text-sm text-red-600">
                                                {{ form.errors.pickup_location }}
                                            </p>
                                        </div>

                                        <div>
                                            <label for="return_location" class="mb-2 block text-sm font-semibold text-slate-700">
                                                Return location *
                                            </label>

                                            <select
                                                id="return_location"
                                                v-model="form.return_location"
                                                required
                                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-slate-800 outline-none transition focus:border-[#58779D] focus:ring-2 focus:ring-[#58779D]/20"
                                                :class="{ 'border-red-500': form.errors.return_location }"
                                            >
                                                <option value="">Select return location</option>
                                                <option v-for="location in commonLocations" :key="location" :value="location">
                                                    {{ location }}
                                                </option>
                                            </select>

                                            <p v-if="form.errors.return_location" class="mt-2 text-sm text-red-600">
                                                {{ form.errors.return_location }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200"></div>

                                <!-- 3. Rental Add-ons -->
                                <RentalExtras
                                    v-model="form.extras"
                                    :rental-days="rentalDays"
                                />

                                <div class="border-t border-slate-200"></div>

                                <!-- Submit CTA -->
                                <div>
                                    <button
                                        type="submit"
                                        :disabled="!canSubmit || form.processing"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D35] px-6 py-4 font-bold text-white transition hover:bg-[#263F60] focus:outline-none focus:ring-2 focus:ring-[#58779D] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500"
                                    >
                                        <Lock class="h-4 w-4" />
                                        <span>
                                            {{ form.processing ? 'Finalizing booking...' : (!page.props.auth?.user ? 'Log in to continue' : `Proceed to Payment • ${formatPrice(total)}`) }}
                                        </span>
                                    </button>

                                    <div class="mt-3 flex items-center justify-center gap-4 text-xs text-slate-500">
                                        <span class="inline-flex items-center gap-1">
                                            <ShieldCheck class="h-3.5 w-3.5 text-emerald-600" />
                                            Instant confirmation
                                        </span>
                                        <span>•</span>
                                        <span>Free cancellation up to 24h</span>
                                    </div>
                                </div>
                            </form>
                        </section>

                        <!-- Customer Reviews Section -->
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-8">
                            <div class="mb-6 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-500">
                                            <Star class="h-4 w-4 fill-amber-400" />
                                        </div>
                                        <h2 class="text-xl font-bold text-[#101D35] sm:text-2xl">
                                            Customer Reviews & Ratings
                                        </h2>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Authentic feedback from verified renters who booked this car.
                                    </p>
                                </div>

                                <button
                                    v-if="eligibleReservation"
                                    type="button"
                                    @click="isReviewModalOpen = true"
                                    class="inline-flex items-center gap-2 rounded-xl bg-[#101D35] px-4 py-2.5 text-xs font-bold text-white transition hover:bg-[#263F60]"
                                >
                                    <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                    <span>Rate Your Trip</span>
                                </button>
                            </div>

                            <!-- Eligible Reservation Prompt Banner -->
                            <div
                                v-if="eligibleReservation"
                                class="mb-6 flex flex-col justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50/80 p-4 sm:flex-row sm:items-center"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                                        <Star class="h-5 w-5 fill-white text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-amber-950">
                                            Trip Completed • Share Your Feedback
                                        </p>
                                        <p class="mt-0.5 text-xs text-amber-800">
                                            You completed reservation {{ eligibleReservation.reservation_number }}. Review your experience to help future renters!
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="isReviewModalOpen = true"
                                    class="shrink-0 rounded-xl bg-[#101D35] px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-[#263F60]"
                                >
                                    Write a Review
                                </button>
                            </div>

                            <!-- Rating Summary Hero & Distribution -->
                            <div v-if="reviewsList.length > 0" class="mb-8 grid gap-6 rounded-xl bg-[#F4F6FA] p-6 sm:grid-cols-12 sm:items-center">
                                <!-- Score Hero -->
                                <div class="text-center sm:col-span-5 sm:border-r sm:border-slate-200 sm:pr-6">
                                    <div class="text-5xl font-black text-[#101D35]">
                                        {{ averageRating.toFixed(1) }}
                                    </div>
                                    <div class="mt-2 flex justify-center">
                                        <StarRating :rating="averageRating" :readonly="true" size="md" />
                                    </div>
                                    <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Based on {{ reviewsList.length }} verified {{ reviewsList.length === 1 ? 'review' : 'reviews' }}
                                    </p>
                                    <div v-if="recommendationPercentage > 0" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-100/90 px-3 py-1 text-xs font-semibold text-emerald-800">
                                        <ThumbsUp class="h-3.5 w-3.5" />
                                        <span>{{ recommendationPercentage }}% of renters recommend</span>
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
                                            class="flex w-14 items-center gap-1 font-semibold text-slate-600 transition hover:text-[#101D35]"
                                            @click="selectedRatingFilter = selectedRatingFilter === star ? null : star"
                                        >
                                            <span>{{ star }}</span>
                                            <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-500" />
                                        </button>
                                        <div class="relative h-2.5 flex-1 overflow-hidden rounded-full bg-slate-200">
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="[
                                                    selectedRatingFilter === star ? 'bg-[#101D35]' : 'bg-amber-400',
                                                ]"
                                                :style="{ width: `${ratingDistribution[star]?.percentage || 0}%` }"
                                            ></div>
                                        </div>
                                        <span class="w-10 text-right font-medium text-slate-500">
                                            {{ ratingDistribution[star]?.count || 0 }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Rating filter chip -->
                            <div v-if="selectedRatingFilter" class="mb-4 flex items-center justify-between rounded-lg bg-slate-100 px-3 py-2 text-xs">
                                <span class="text-slate-600">
                                    Showing only <strong>{{ selectedRatingFilter }}★</strong> reviews
                                </span>
                                <button type="button" @click="selectedRatingFilter = null" class="font-bold text-[#58779D] hover:underline">
                                    Show All
                                </button>
                            </div>

                            <!-- Reviews List -->
                            <div v-if="filteredReviews.length > 0" class="space-y-4">
                                <div
                                    v-for="review in filteredReviews"
                                    :key="review.id"
                                    class="rounded-xl border border-slate-100 bg-[#FAFBFD] p-4 transition hover:border-slate-200 hover:shadow-2xs sm:p-5"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#101D35] text-xs font-bold uppercase text-white">
                                                {{ review.user.initials }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-[#101D35]">{{ review.user.name }}</span>
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200/60">
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
                                        <h4 v-if="review.title" class="mb-1 text-sm font-bold text-[#101D35]">
                                            {{ review.title }}
                                        </h4>
                                        <p class="text-xs leading-relaxed text-slate-600 sm:text-sm">
                                            {{ review.comment }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-else class="rounded-xl border border-dashed border-slate-200 p-8 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                    <MessageSquare class="h-6 w-6" />
                                </div>
                                <p class="mt-3 text-sm font-bold text-[#101D35]">No reviews yet</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    No reviews yet — be the first to review after your completed trip!
                                </p>
                            </div>
                        </section>
                    </div>

                    <!-- Price summary sidebar -->
                    <aside class="space-y-6 lg:sticky lg:top-6">
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                            <div class="bg-[#101D35] p-6 text-white">
                                <p class="text-sm font-medium text-slate-300">
                                    Your reservation
                                </p>
                                <h2 class="mt-1 text-2xl font-bold">
                                    Order summary
                                </h2>
                                <p class="mt-2 text-sm text-slate-300">
                                    {{ car.make }} {{ car.model }}
                                </p>
                            </div>

                            <div class="space-y-5 p-6">
                                <!-- Vehicle duration breakdown -->
                                <div class="rounded-xl bg-[#F4F6FA] p-4 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-600">Rental duration</span>
                                        <span class="font-bold text-[#101D35]">
                                            {{ rentalDays > 0 ? `${rentalDays} day${rentalDays === 1 ? '' : 's'}` : '—' }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        <span class="text-slate-600">Daily vehicle rate</span>
                                        <span class="font-semibold text-[#101D35]">
                                            {{ formatPrice(dailyRate) }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-200 pt-3">
                                        <span class="font-medium text-slate-700">Vehicle rental cost</span>
                                        <span class="font-bold text-[#101D35]">
                                            {{ formatPrice(carBaseCost) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Add-ons Breakdown -->
                                <div v-if="selectedExtrasBreakdown.length > 0" class="space-y-2 border-t border-slate-100 pt-4">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-[#58779D]">
                                        Selected Add-ons ({{ selectedExtrasBreakdown.length }})
                                    </p>
                                    <div
                                        v-for="extra in selectedExtrasBreakdown"
                                        :key="extra.id"
                                        class="flex items-center justify-between gap-3 text-xs"
                                    >
                                        <span class="text-slate-600">{{ extra.name }}</span>
                                        <span class="font-semibold text-[#101D35]">
                                            +{{ formatPrice(extra.cost) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Subtotal -->
                                <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                                    <span class="text-sm font-medium text-slate-700">Subtotal</span>
                                    <span class="text-sm font-bold text-[#101D35]">
                                        {{ formatPrice(subtotal) }}
                                    </span>
                                </div>

                                <!-- Promo Code Input -->
                                <div class="border-t border-slate-100 pt-4">
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
                                    class="flex items-center justify-between gap-3 text-sm text-emerald-700"
                                >
                                    <span class="font-medium">Promo Discount ({{ appliedCoupon.code }})</span>
                                    <span class="font-bold">
                                        -{{ formatPrice(discountAmount) }}
                                    </span>
                                </div>

                                <!-- Taxes -->
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="text-slate-600">Goods & Service Tax (7%)</span>
                                    <span class="font-semibold text-[#101D35]">
                                        {{ formatPrice(tax) }}
                                    </span>
                                </div>

                                <!-- Grand Total -->
                                <div class="border-t border-dashed border-slate-300 pt-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-base font-bold text-[#101D35]">
                                                Grand Total
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                All taxes and add-ons included
                                            </p>
                                        </div>

                                        <p class="font-mono text-2xl font-bold text-[#101D35]">
                                            {{ formatPrice(total) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Action button in sidebar -->
                                <button
                                    type="button"
                                    :disabled="!canSubmit || form.processing"
                                    @click="openPaymentModal"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D35] px-5 py-3.5 text-sm font-bold text-white transition hover:bg-[#263F60] focus:outline-none focus:ring-2 focus:ring-[#58779D] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500"
                                >
                                    <Lock class="h-4 w-4" />
                                    <span>
                                        {{ !page.props.auth?.user ? 'Log in to continue' : 'Proceed to Payment' }}
                                    </span>
                                </button>

                                <a href="/fleet" class="block text-center text-sm font-semibold text-[#58779D] transition hover:text-[#101D35]">
                                    ← Back to fleet
                                </a>
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
