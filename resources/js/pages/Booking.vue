
<script setup lang="ts">
import BookingCalendar, { type BookedRange } from '@/components/BookingCalendar.vue';
import HomeLayout from '@/layouts/HomeLayout.vue';
import { book } from '@/routes/fleet';
import { login } from '@/routes';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

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
});

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

const subtotal = computed(() =>
    rentalDays.value > 0 ? rentalDays.value * dailyRate.value : 0
);

const tax = computed(() => subtotal.value * 0.07);

const total = computed(() => subtotal.value + tax.value);

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

const submitBooking = () => {
    const user = page.props.auth?.user;

    if (!user) {
        router.get(login.url());
        return;
    }

    if (user.role === 'client') {
        form.post(book.url(car.value.id));
        return;
    }

    if (user.role === 'admin') {
        alert('You cannot book as an admin.');
        return;
    }

    alert('Your role does not allow booking.');
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
                        Your journey starts here
                    </p>

                    <h1 class="text-3xl font-bold sm:text-4xl">
                        Reserve your vehicle
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Choose your dates, select your locations, and review your rental total.
                    </p>
                </div>
            </section>

            <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <div class="grid items-start gap-8 lg:grid-cols-3">
                    <!-- Main column -->
                    <div class="space-y-7 lg:col-span-2">
                        <!-- Vehicle details -->
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
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

                                <div class="absolute bottom-4 left-4 rounded-lg bg-[#101D35]/90 px-4 py-2 text-sm font-semibold text-white backdrop-blur">
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
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                            <div class="mb-8 flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#101D35] text-white">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="text-xl font-bold text-[#101D35] sm:text-2xl">
                                        Booking details
                                    </h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Fields marked * are required.
                                    </p>
                                </div>
                            </div>

                            <form class="space-y-8" @submit.prevent="submitBooking">
                                <!-- Dates -->
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

                                <!-- Locations -->
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

                                <div class="rounded-xl border border-slate-200 bg-[#F4F6FA] p-4">
                                    <p class="text-sm leading-6 text-slate-600">
                                        Review your dates and locations before confirming your reservation. Your booking will be submitted to the server for validation.
                                    </p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="!canSubmit || form.processing"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D35] px-6 py-4 font-bold text-white transition hover:bg-[#263F60] focus:outline-none focus:ring-2 focus:ring-[#58779D] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500"
                                >
                                    <svg v-if="form.processing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>

                                    <span>
                                        {{ form.processing ? 'Processing booking...' : (!page.props.auth?.user ? 'Log in to continue' : 'Confirm booking') }}
                                    </span>

                                    <svg v-if="!form.processing" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7 7 7-7 7" />
                                    </svg>
                                </button>
                            </form>
                        </section>
                    </div>

                    <!-- Price summary -->
                    <aside class="lg:sticky lg:top-6">
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="bg-[#101D35] p-6 text-white">
                                <p class="text-sm font-medium text-slate-300">
                                    Your reservation
                                </p>
                                <h2 class="mt-1 text-2xl font-bold">
                                    Price summary
                                </h2>
                                <p class="mt-2 text-sm text-slate-300">
                                    {{ car.make }} {{ car.model }}
                                </p>
                            </div>

                            <div class="space-y-5 p-6">
                                <div class="rounded-xl bg-[#F4F6FA] p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm text-slate-600">Rental duration</span>
                                        <span class="font-bold text-[#101D35]">
                                            {{ rentalDays > 0 ? `${rentalDays} day${rentalDays === 1 ? '' : 's'}` : '—' }}
                                        </span>
                                    </div>

                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <span class="text-sm text-slate-600">Daily rate</span>
                                        <span class="font-semibold text-[#101D35]">
                                            {{ formatPrice(dailyRate) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-slate-600">Subtotal</span>
                                    <span class="font-semibold text-[#101D35]">
                                        {{ formatPrice(subtotal) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-slate-600">Tax (7%)</span>
                                    <span class="font-semibold text-[#101D35]">
                                        {{ formatPrice(tax) }}
                                    </span>
                                </div>

                                <div class="border-t border-dashed border-slate-300 pt-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-base font-bold text-[#101D35]">
                                                Estimated total
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                For the selected rental period
                                            </p>
                                        </div>

                                        <p class="text-2xl font-bold text-[#101D35]">
                                            {{ formatPrice(total) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-200 p-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#58779D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 3a11.955 11.955 0 01-8.618 2.984A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9c0-1.042-.133-2.053-.382-3.016z" />
                                        </svg>

                                        <div>
                                            <p class="text-sm font-semibold text-[#101D35]">
                                                Reservation details
                                            </p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                Final booking acceptance and availability depend on server-side validation.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <a href="/fleet" class="block text-center text-sm font-semibold text-[#58779D] transition hover:text-[#101D35]">
                                    ← Back to fleet
                                </a>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </HomeLayout>
</template>
