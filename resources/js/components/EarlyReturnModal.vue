<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Calendar,
    CheckCircle2,
    Clock,
    DollarSign,
    Key,
    Loader2,
    RotateCcw,
    ShieldCheck,
    Sparkles,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ReservationToReturn {
    id: number;
    reservation_number: string;
    start_date: string;
    end_date: string;
    total_days: number;
    total_amount: number | string;
    car?: {
        id: number;
        make: string;
        model: string;
        year?: number;
        license_plate: string;
    } | null;
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        reservation: ReservationToReturn | null;
        currencySymbol?: string;
    }>(),
    {
        currencySymbol: '₹',
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'returned'): void;
}>();

const isProcessing = ref<boolean>(false);
const confirmBelongings = ref<boolean>(true);
const confirmKeys = ref<boolean>(true);

const formatCurrency = (val: number | string | undefined | null) => {
    const num = Number(val || 0);
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(num);
};

const daysUsed = computed(() => {
    if (!props.reservation?.start_date) return 1;
    const start = new Date(props.reservation.start_date);
    const now = new Date();
    const diffTime = Math.abs(now.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return Math.max(1, Math.min(diffDays, Number(props.reservation.total_days || 1)));
});

const unusedDays = computed(() => {
    const total = Number(props.reservation?.total_days || 0);
    return Math.max(0, total - daysUsed.value);
});

const refundAmount = computed(() => {
    const totalAmount = Number(props.reservation?.total_amount || 0);
    const totalDays = Number(props.reservation?.total_days || 1);
    const dailyCost = totalAmount / totalDays;
    return Math.round(dailyCost * unusedDays.value);
});

const submitEarlyReturn = () => {
    if (!props.reservation || unusedDays.value <= 0) return;

    isProcessing.value = true;

    router.post(
        `/client/reservations/${props.reservation.id}/early-return`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                emit('returned');
                emit('close');
            },
            onError: () => {
                isProcessing.value = false;
            },
        },
    );
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
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        <RotateCcw class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            Early Vehicle Return
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

            <!-- Prorated Refund Banner -->
            <div class="mt-4 rounded-2xl border border-emerald-500/20 bg-emerald-50/70 p-4 dark:border-emerald-500/20 dark:bg-emerald-950/20">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Prorated Refund Credit
                    </span>
                    <span class="rounded bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                        {{ unusedDays }} {{ unusedDays === 1 ? 'Unused Day' : 'Unused Days' }}
                    </span>
                </div>

                <div class="mt-2 flex items-baseline justify-between">
                    <div>
                        <p class="font-mono text-2xl font-black text-slate-900 dark:text-white">
                            {{ formatCurrency(refundAmount) }}
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Will be returned directly to your original payment method.
                        </p>
                    </div>

                    <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                        <p>Used: <strong>{{ daysUsed }}</strong> / {{ reservation.total_days }} days</p>
                    </div>
                </div>
            </div>

            <!-- Handover Checkpoints -->
            <div class="mt-4 space-y-2">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Handover Confirmation
                </label>

                <label class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 text-xs text-slate-700 transition hover:bg-slate-100/60 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                    <input
                        v-model="confirmBelongings"
                        type="checkbox"
                        class="rounded border-slate-300 text-[#58779D] focus:ring-[#58779D]"
                    />
                    <span>I have removed all personal belongings and items from the vehicle.</span>
                </label>

                <label class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 text-xs text-slate-700 transition hover:bg-slate-100/60 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                    <input
                        v-model="confirmKeys"
                        type="checkbox"
                        class="rounded border-slate-300 text-[#58779D] focus:ring-[#58779D]"
                    />
                    <span>Vehicle keys and documents are ready for fleet staff inspection.</span>
                </label>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4 dark:border-white/5">
                <button
                    type="button"
                    @click="emit('close')"
                    :disabled="isProcessing"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="submitEarlyReturn"
                    :disabled="isProcessing || !confirmBelongings || !confirmKeys || unusedDays <= 0"
                    class="apple-btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold disabled:opacity-40"
                >
                    <Loader2 v-if="isProcessing" class="h-4 w-4 animate-spin" />
                    <CheckCircle2 v-else class="h-4 w-4" />
                    <span>Authorize Early Return & Refund {{ formatCurrency(refundAmount) }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
