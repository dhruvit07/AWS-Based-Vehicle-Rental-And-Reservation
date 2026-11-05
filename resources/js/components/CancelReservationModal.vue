<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Loader2, ShieldCheck, X } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface ReservationToCancel {
    id: number;
    reservation_number: string;
    start_date?: string;
    end_date?: string;
    total_amount: number | string;
    refund_amount?: number | string;
    car?: {
        id: number;
        make: string;
        model: string;
        year?: number;
        image_url?: string;
    } | null;
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        reservation: ReservationToCancel | null;
        currencySymbol?: string;
    }>(),
    {
        currencySymbol: '₹',
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'cancelled'): void;
}>();

const cancellationReasons = [
    'Change of travel plans',
    'Found alternative transportation',
    'Trip postponed',
    'Personal or medical emergency',
    'Inclement weather / flight disruption',
    'Other',
];

const form = useForm({
    reason: 'Change of travel plans',
    notes: '',
});

const totalPaid = computed(() => {
    return Number(props.reservation?.total_amount ?? 0);
});

const refundAmount = computed(() => {
    return Number(
        props.reservation?.refund_amount ??
            props.reservation?.total_amount ??
            0,
    );
});

const formatMoney = (val: number) => {
    return val.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatDate = (dateStr?: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr.slice(0, 10) + 'T00:00:00');
    return date.toLocaleDateString('en-IN', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const submitCancellation = () => {
    if (!props.reservation) return;

    form.post(`/client/reservations/${props.reservation.id}/cancel`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('cancelled');
            emit('close');
        },
    });
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            form.reset();
            form.reason = 'Change of travel plans';
            form.notes = '';
            form.clearErrors();
        }
    },
);
</script>

<template>
    <div
        v-if="isOpen && reservation"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Frosted Backdrop -->
        <div
            class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity"
            @click="emit('close')"
        ></div>

        <!-- Dialog Container -->
        <div
            class="relative z-10 w-full max-w-xl overflow-hidden rounded-3xl border border-black/10 bg-white/95 p-6 shadow-2xl backdrop-blur-xl sm:p-7 dark:border-white/10 dark:bg-[#0F1724]/95"
        >
            <!-- Close Button -->
            <button
                type="button"
                @click="emit('close')"
                class="absolute top-5 right-5 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-slate-200"
                aria-label="Close modal"
            >
                <X class="h-4 w-4" />
            </button>

            <!-- Header -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                >
                    <AlertTriangle class="h-5 w-5" />
                </div>
                <div>
                    <h3
                        class="text-xl font-bold tracking-tight text-slate-950 dark:text-white"
                    >
                        Cancel Reservation
                    </h3>
                    <p
                        class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                    >
                        Voucher #{{ reservation.reservation_number }} · Full
                        refund processed automatically
                    </p>
                </div>
            </div>

            <!-- Vehicle & Dates Chip -->
            <div
                class="mt-5 flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-white/10 dark:bg-white/5"
            >
                <div class="flex items-center gap-3">
                    <div
                        v-if="reservation.car?.image_url"
                        class="h-10 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-200 dark:bg-slate-800"
                    >
                        <img
                            :src="reservation.car.image_url"
                            :alt="reservation.car.model"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-slate-950 dark:text-white"
                        >
                            {{
                                reservation.car
                                    ? `${reservation.car.year ?? ''} ${reservation.car.make} ${reservation.car.model}`.trim()
                                    : 'Reserved Vehicle'
                            }}
                        </p>
                        <p
                            v-if="
                                reservation.start_date && reservation.end_date
                            "
                            class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            {{ formatDate(reservation.start_date) }} –
                            {{ formatDate(reservation.end_date) }}
                        </p>
                    </div>
                </div>
                <span
                    class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    Eligible for Refund
                </span>
            </div>

            <!-- Itemized Refund Summary Card -->
            <div
                class="mt-5 rounded-2xl border border-emerald-500/20 bg-emerald-500/[0.04] p-4 dark:border-emerald-500/30 dark:bg-emerald-500/[0.06]"
            >
                <div
                    class="flex items-center justify-between border-b border-emerald-500/10 pb-3 text-xs"
                >
                    <span class="text-slate-600 dark:text-slate-400"
                        >Total Paid</span
                    >
                    <span
                        class="font-mono font-semibold text-slate-900 dark:text-white"
                    >
                        {{ currencySymbol }}{{ formatMoney(totalPaid) }}
                    </span>
                </div>

                <div
                    class="flex items-center justify-between border-b border-emerald-500/10 py-2 text-xs"
                >
                    <span class="text-slate-600 dark:text-slate-400"
                        >Cancellation Fee</span
                    >
                    <span
                        class="font-mono font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        {{ currencySymbol }}0.00 (100% Free)
                    </span>
                </div>

                <div class="flex items-baseline justify-between pt-3">
                    <div>
                        <p
                            class="text-xs font-bold text-slate-950 dark:text-white"
                        >
                            Total Refund Amount
                        </p>
                        <p
                            class="text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            Immediate reversal initiation
                        </p>
                    </div>
                    <div class="text-right">
                        <span
                            class="font-mono text-2xl font-black text-emerald-600 dark:text-emerald-400"
                        >
                            {{ currencySymbol }}{{ formatMoney(refundAmount) }}
                        </span>
                    </div>
                </div>

                <div
                    class="mt-3 flex items-center gap-2 rounded-xl bg-white/70 px-3 py-2 text-[11px] text-slate-600 dark:bg-white/5 dark:text-slate-300"
                >
                    <ShieldCheck
                        class="h-3.5 w-3.5 shrink-0 text-emerald-500"
                    />
                    <span
                        >Refund credited to your original payment method
                        automatically.</span
                    >
                </div>
            </div>

            <!-- Cancellation Form -->
            <form @submit.prevent="submitCancellation" class="mt-5 space-y-4">
                <div>
                    <label
                        class="block text-xs font-semibold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                    >
                        Reason for Cancellation
                        <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="form.reason"
                        class="apple-input mt-1.5 w-full bg-white dark:bg-slate-900/60"
                        required
                    >
                        <option
                            v-for="r in cancellationReasons"
                            :key="r"
                            :value="r"
                        >
                            {{ r }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.reason"
                        class="mt-1 text-xs text-rose-500"
                    >
                        {{ form.errors.reason }}
                    </p>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label
                            class="block text-xs font-semibold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                        >
                            Additional Details
                            <span class="font-normal text-slate-400 normal-case"
                                >(Optional)</span
                            >
                        </label>
                        <span class="text-[10px] text-slate-400">
                            {{ form.notes.length }}/500
                        </span>
                    </div>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        maxlength="500"
                        placeholder="Tell us what changed or share feedback to help us improve..."
                        class="apple-input mt-1.5 w-full resize-none bg-white text-xs dark:bg-slate-900/60"
                    ></textarea>
                    <p
                        v-if="form.errors.notes"
                        class="mt-1 text-xs text-rose-500"
                    >
                        {{ form.errors.notes }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div
                    class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end"
                >
                    <button
                        type="button"
                        @click="emit('close')"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                    >
                        Keep Booking
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-rose-700 focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50 dark:bg-rose-600 dark:hover:bg-rose-700"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <span>Confirm Cancellation & Refund</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
