<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Car, CheckCircle2, User } from 'lucide-vue-next';
import { computed } from 'vue';
import { index, update } from '@/routes/admin/reservations';

const props = defineProps<{
    reservation: any;
    enums: {
        statuses: Array<{ value: string; label: string; color: string }>;
    };
}>();

const statuses = computed(() => props.enums.statuses || []);

const formatDateForInput = (
    dateString: string | null | undefined,
): string => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
};

const form = useForm({
    start_date: formatDateForInput(props.reservation?.start_date) || '',
    end_date: formatDateForInput(props.reservation?.end_date) || '',
    pickup_time: props.reservation?.pickup_time || '09:00',
    return_time: props.reservation?.return_time || '18:00',
    pickup_location: props.reservation?.pickup_location || '',
    return_location: props.reservation?.return_location || '',
    discount_amount: props.reservation?.discount_amount || 0,
    notes: props.reservation?.notes || '',
    status: props.reservation?.status || 'pending',
    cancellation_reason: props.reservation?.cancellation_reason || '',
});

function submit() {
    form.put(update(props.reservation.id).url);
}
</script>

<template>
    <Head :title="`Edit Reservation ${reservation?.reservation_number || ''}`" />

    <AdminLayout>
        <main class="min-h-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Edit Reservation
                    </h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                        Update booking dates, pickup schedule, contract status, or applied concession discounts.
                    </p>
                </div>

                <Link
                    :href="index().url"
                    class="inline-flex w-fit items-center justify-center gap-1.5 rounded-xl border border-black/10 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 transition hover:bg-slate-200/60 dark:hover:bg-white/10"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Reservations
                </Link>
            </div>

            <!-- Reservation Summary Cards -->
            <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="glass-card p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <Calendar class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Booking Code
                            </p>
                            <p class="mt-0.5 font-mono font-bold text-slate-900 dark:text-white truncate">
                                {{ reservation.reservation_number || '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <User class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Client
                            </p>
                            <p class="mt-0.5 truncate font-bold text-slate-900 dark:text-white">
                                {{ reservation.user?.name || 'Walk-in Guest' }}
                            </p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ reservation.user?.email || '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <Car class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Reserved Vehicle
                            </p>
                            <p class="mt-0.5 truncate font-bold text-slate-900 dark:text-white">
                                {{
                                    reservation.car
                                        ? `${reservation.car.year} ${reservation.car.make} ${reservation.car.model}`
                                        : 'Unassigned'
                                }}
                            </p>
                            <p class="truncate text-xs font-mono text-slate-500 dark:text-slate-400">
                                {{ reservation.car?.license_plate || '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Edit Form -->
            <form
                class="glass-card overflow-hidden"
                @submit.prevent="submit"
            >
                <div class="border-b border-black/5 dark:border-white/10 px-6 py-4">
                    <h2 class="font-bold text-slate-900 dark:text-white">
                        Contract Parameters
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Make schedule or rate adjustments and persist modifications to the reservation ledger.
                    </p>
                </div>

                <div class="space-y-8 p-6">
                    <!-- Rental Schedule -->
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            Rental Schedule
                        </h3>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="start_date" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Start Date
                                </Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    required
                                    class="apple-input"
                                />
                                <InputError :message="form.errors.start_date" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="end_date" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    End Date
                                </Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    required
                                    class="apple-input"
                                />
                                <InputError :message="form.errors.end_date" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="pickup_time" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Pickup Time
                                </Label>
                                <Input
                                    id="pickup_time"
                                    v-model="form.pickup_time"
                                    type="time"
                                    required
                                    class="apple-input"
                                />
                                <InputError :message="form.errors.pickup_time" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="return_time" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Return Time
                                </Label>
                                <Input
                                    id="return_time"
                                    v-model="form.return_time"
                                    type="time"
                                    required
                                    class="apple-input"
                                />
                                <InputError :message="form.errors.return_time" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-black/5 dark:border-white/10"></div>

                    <!-- Locations and Pricing -->
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            Locations & Concessions
                        </h3>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="pickup_location" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Pickup Station
                                </Label>
                                <Input
                                    id="pickup_location"
                                    v-model="form.pickup_location"
                                    placeholder="Main City Station"
                                    class="apple-input"
                                />
                                <InputError :message="form.errors.pickup_location" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="return_location" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Return Station
                                </Label>
                                <Input
                                    id="return_location"
                                    v-model="form.return_location"
                                    placeholder="Airport Terminal 2"
                                    class="apple-input"
                                />
                                <InputError :message="form.errors.return_location" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="discount_amount" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Discount / Concession Amount
                                </Label>
                                <Input
                                    id="discount_amount"
                                    v-model="form.discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="apple-input"
                                />
                                <InputError :message="form.errors.discount_amount" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="status" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Booking Status
                                </Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    required
                                    class="apple-input cursor-pointer py-2.5 text-sm"
                                >
                                    <option
                                        v-for="s in statuses"
                                        :key="s.value"
                                        :value="s.value"
                                    >
                                        {{ s.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-black/5 dark:border-white/10"></div>

                    <!-- Notes -->
                    <section class="space-y-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            Internal Operations Notes
                        </h3>

                        <div class="space-y-1.5">
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Add key instructions for handover concierge, luggage handling, or return inspection..."
                                class="apple-input w-full p-3 text-sm"
                            ></textarea>
                            <InputError :message="form.errors.notes" />
                        </div>
                    </section>

                    <!-- Cancellation Reason -->
                    <section
                        v-if="form.status === 'cancelled'"
                        class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-5"
                    >
                        <h3 class="text-sm font-bold text-rose-950 dark:text-rose-200">
                            Cancellation Information
                        </h3>

                        <div class="mt-3 space-y-1.5">
                            <Label for="cancellation_reason" class="text-xs font-medium text-rose-900 dark:text-rose-300">
                                Reason for Cancellation
                            </Label>
                            <textarea
                                id="cancellation_reason"
                                v-model="form.cancellation_reason"
                                rows="3"
                                placeholder="State reason for customer or operational cancellation..."
                                class="apple-input w-full p-3 text-sm"
                            ></textarea>
                            <InputError :message="form.errors.cancellation_reason" />
                        </div>
                    </section>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col-reverse justify-between gap-3 border-t border-black/5 dark:border-white/10 bg-slate-50/50 dark:bg-white/[0.02] p-4 sm:flex-row sm:items-center sm:px-6">
                    <Link :href="index().url">
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full rounded-xl border-black/10 dark:border-white/10 text-xs font-medium hover:bg-slate-100 dark:hover:bg-white/10 sm:w-auto"
                        >
                            Cancel
                        </Button>
                    </Link>

                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="apple-btn-primary h-11 px-6 text-sm font-medium w-full sm:w-auto"
                    >
                        <CheckCircle2 class="h-4 w-4 mr-2" />
                        {{ form.processing ? 'Saving Changes...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </main>
    </AdminLayout>
</template>
