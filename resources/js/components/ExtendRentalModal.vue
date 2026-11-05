<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Calendar,
    CalendarPlus,
    CheckCircle2,
    CreditCard,
    DollarSign,
    Loader2,
    QrCode,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ReservationToExtend {
    id: number;
    reservation_number: string;
    start_date: string;
    end_date: string;
    total_days: number;
    daily_rate: number | string;
    total_amount: number | string;
    car?: {
        id: number;
        make: string;
        model: string;
        year?: number;
        license_plate: string;
        price_per_day: number | string;
    } | null;
    extras?: Array<{
        id: string;
        name: string;
        rate: number;
        type: 'per_day' | 'fixed';
        total: number;
    }> | null;
}

interface ExtensionCheckResult {
    available: boolean;
    message?: string;
    extra_days?: number;
    current_end_date?: string;
    new_end_date?: string;
    daily_rate?: number;
    daily_extras_rate?: number;
    differential_base?: number;
    differential_extras?: number;
    differential_subtotal?: number;
    differential_tax?: number;
    differential_total?: number;
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        reservation: ReservationToExtend | null;
        currencySymbol?: string;
    }>(),
    {
        currencySymbol: '₹',
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'extended'): void;
}>();

const additionalDays = ref<number>(2);
const isChecking = ref<boolean>(false);
const isPaying = ref<boolean>(false);
const paymentMethod = ref<'credit_card' | 'upi' | 'cash'>('credit_card');
const checkResult = ref<ExtensionCheckResult | null>(null);
const errorMessage = ref<string | null>(null);

const formatCurrency = (val: number | string | undefined | null) => {
    const num = Number(val || 0);
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateStr: string | undefined | null) => {
    if (!dateStr) return 'N/A';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const runExtensionCheck = async (days: number) => {
    if (!props.reservation) return;
    isChecking.value = true;
    errorMessage.value = null;

    try {
        const res = await fetch(
            `/client/reservations/${props.reservation.id}/extend/check?additional_days=${encodeURIComponent(days)}`,
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            },
        );

        const data = await res.json();
        if (!res.ok) {
            errorMessage.value = data.message || 'Unable to verify availability for these dates.';
            return;
        }

        checkResult.value = data;
        if (!data.available && data.message) {
            errorMessage.value = data.message;
        }
    } catch (err) {
        console.error('Failed to check extension availability', err);
        errorMessage.value = 'Failed to check calendar availability. Please try again.';
    } finally {
        isChecking.value = false;
    }
};

watch(
    () => [props.isOpen, additionalDays.value],
    ([open]) => {
        if (open && props.reservation) {
            runExtensionCheck(additionalDays.value);
        }
    },
    { immediate: true },
);

const submitExtension = () => {
    if (!props.reservation || !checkResult.value?.available) return;

    isPaying.value = true;

    // Simulate realistic 900ms payment gateway authorization
    setTimeout(() => {
        router.post(
            `/client/reservations/${props.reservation!.id}/extend`,
            {
                additional_days: additionalDays.value,
                payment_method: paymentMethod.value,
                transaction_id: 'TXN-EXT-' + Math.random().toString(36).substring(2, 9).toUpperCase(),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isPaying.value = false;
                    emit('extended');
                    emit('close');
                },
                onError: (errors) => {
                    isPaying.value = false;
                    errorMessage.value = Object.values(errors)[0] || 'Failed to extend trip.';
                },
            },
        );
    }, 900);
};
</script>

<template>
    <div
        v-if="isOpen && reservation"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
            @click="emit('close')"
        ></div>

        <!-- Modal Dialog -->
        <div
            class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-2xl transition-all dark:border-white/10 dark:bg-[#0c1017] dark:text-slate-100 sm:p-7"
        >
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-slate-100 pb-4 dark:border-white/5">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#58779D]/15 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                        <CalendarPlus class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            Extend Your Rental Trip
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            #{{ reservation.reservation_number }} • {{ reservation.car?.make }} {{ reservation.car?.model }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-xl p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-white"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Error Banner -->
            <div
                v-if="errorMessage"
                class="mt-4 flex items-start gap-2.5 rounded-2xl border border-rose-500/20 bg-rose-50/80 p-3.5 text-xs text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <AlertCircle class="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
                <span>{{ errorMessage }}</span>
            </div>

            <!-- Additional Days Stepper -->
            <div class="mt-4">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Additional Rental Days
                </label>

                <div class="mt-2 flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50/80 p-2 dark:border-white/10 dark:bg-white/5">
                    <button
                        type="button"
                        @click="additionalDays = Math.max(1, additionalDays - 1)"
                        :disabled="additionalDays <= 1 || isChecking || isPaying"
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-white font-bold text-slate-800 shadow-xs transition hover:bg-slate-100 disabled:opacity-30 dark:bg-white/10 dark:text-white"
                    >
                        -
                    </button>

                    <div class="text-center">
                        <span class="font-mono text-xl font-black text-slate-900 dark:text-white">
                            +{{ additionalDays }} {{ additionalDays === 1 ? 'Day' : 'Days' }}
                        </span>
                        <p class="text-[10px] text-slate-400">
                            New return: {{ checkResult?.new_end_date ? formatDate(checkResult.new_end_date) : '...' }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="additionalDays = Math.min(14, additionalDays + 1)"
                        :disabled="additionalDays >= 14 || isChecking || isPaying"
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-white font-bold text-slate-800 shadow-xs transition hover:bg-slate-100 disabled:opacity-30 dark:bg-white/10 dark:text-white"
                    >
                        +
                    </button>
                </div>
            </div>

            <!-- Availability & Differential Price Ledger -->
            <div class="mt-4 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/5">
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5 dark:border-white/10">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Calendar Availability</span>
                    <div v-if="isChecking" class="flex items-center gap-1.5 text-xs text-slate-400">
                        <Loader2 class="h-3.5 w-3.5 animate-spin" />
                        <span>Checking dates...</span>
                    </div>
                    <span
                        v-else-if="checkResult?.available"
                        class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-bold text-emerald-600 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="h-3 w-3" />
                        <span>Confirmed Available</span>
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-bold text-rose-600 dark:text-rose-400"
                    >
                        <ShieldAlert class="h-3 w-3" />
                        <span>Conflict Detected</span>
                    </span>
                </div>

                <div v-if="checkResult && checkResult.available" class="mt-3 space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-600 dark:text-slate-300">
                        <span>Base Rental (+{{ additionalDays }} days x {{ formatCurrency(checkResult.daily_rate) }})</span>
                        <span class="font-mono">{{ formatCurrency(checkResult.differential_base) }}</span>
                    </div>

                    <div v-if="Number(checkResult.differential_extras || 0) > 0" class="flex justify-between text-slate-600 dark:text-slate-300">
                        <span>Daily Add-ons (+{{ additionalDays }} days)</span>
                        <span class="font-mono">{{ formatCurrency(checkResult.differential_extras) }}</span>
                    </div>

                    <div class="flex justify-between text-slate-600 dark:text-slate-300">
                        <span>GST / Taxes (18%)</span>
                        <span class="font-mono">{{ formatCurrency(checkResult.differential_tax) }}</span>
                    </div>

                    <div class="mt-2 flex items-center justify-between border-t border-slate-200/80 pt-2 font-bold text-slate-900 dark:border-white/10 dark:text-white">
                        <span>Differential Payment Due</span>
                        <span class="font-mono text-base font-extrabold text-[#58779D] dark:text-[#7094BF]">
                            {{ formatCurrency(checkResult.differential_total) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Method Tabs -->
            <div class="mt-4">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Settlement Method
                </label>

                <div class="mt-1.5 grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        @click="paymentMethod = 'credit_card'"
                        class="flex items-center justify-center gap-1.5 rounded-xl border p-2 text-xs font-semibold transition"
                        :class="paymentMethod === 'credit_card'
                            ? 'border-[#58779D] bg-[#58779D]/15 text-[#58779D] dark:border-[#7094BF] dark:bg-[#7094BF]/20 dark:text-[#7094BF]'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                    >
                        <CreditCard class="h-3.5 w-3.5" />
                        <span>Card</span>
                    </button>

                    <button
                        type="button"
                        @click="paymentMethod = 'upi'"
                        class="flex items-center justify-center gap-1.5 rounded-xl border p-2 text-xs font-semibold transition"
                        :class="paymentMethod === 'upi'
                            ? 'border-[#58779D] bg-[#58779D]/15 text-[#58779D] dark:border-[#7094BF] dark:bg-[#7094BF]/20 dark:text-[#7094BF]'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                    >
                        <QrCode class="h-3.5 w-3.5" />
                        <span>UPI QR</span>
                    </button>

                    <button
                        type="button"
                        @click="paymentMethod = 'cash'"
                        class="flex items-center justify-center gap-1.5 rounded-xl border p-2 text-xs font-semibold transition"
                        :class="paymentMethod === 'cash'
                            ? 'border-[#58779D] bg-[#58779D]/15 text-[#58779D] dark:border-[#7094BF] dark:bg-[#7094BF]/20 dark:text-[#7094BF]'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                    >
                        <DollarSign class="h-3.5 w-3.5" />
                        <span>Pay on Return</span>
                    </button>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4 dark:border-white/5">
                <button
                    type="button"
                    @click="emit('close')"
                    :disabled="isPaying"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="submitExtension"
                    :disabled="!checkResult?.available || isChecking || isPaying"
                    class="apple-btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold disabled:opacity-40"
                >
                    <Loader2 v-if="isPaying" class="h-4 w-4 animate-spin" />
                    <CheckCircle2 v-else class="h-4 w-4" />
                    <span>Confirm Extension & Pay {{ checkResult?.differential_total ? formatCurrency(checkResult.differential_total) : '' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
