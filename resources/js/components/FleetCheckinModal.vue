<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Car,
    CheckCircle2,
    Fuel,
    Gauge,
    Loader2,
    RotateCcw,
    Shield,
    Sparkles,
    Wrench,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import VehicleDamageBlueprint, { type DamageItem } from '@/components/VehicleDamageBlueprint.vue';

interface ReservationItem {
    id: number;
    reservation_number: string;
    start_date?: string;
    end_date?: string;
    car?: {
        id: number;
        make: string;
        model: string;
        year?: number;
        license_plate: string;
        mileage: number;
    } | null;
    user?: {
        id: number;
        name: string;
    } | null;
}

const props = defineProps<{
    isOpen: boolean;
    reservation: ReservationItem | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'success'): void;
}>();

const currentStep = ref<1 | 2>(1);

const startingMileage = computed(() => Number(props.reservation?.car?.mileage || 0));

const form = useForm<{
    return_odometer: number;
    fuel_level: string;
    next_status: string;
    condition_notes: string;
    damages: DamageItem[];
}>({
    return_odometer: 0,
    fuel_level: 'full',
    next_status: 'cleaning',
    condition_notes: '',
    damages: [],
});

watch(
    () => props.reservation,
    (res) => {
        if (res && res.car) {
            currentStep.value = 1;
            form.return_odometer = Number(res.car.mileage) + 120;
            form.fuel_level = 'full';
            form.next_status = 'cleaning';
            form.condition_notes = 'Clean return. Exterior pre-inspected.';
            form.damages = [];
            form.clearErrors();
        }
    },
    { immediate: true },
);

const isOdometerValid = computed(() => {
    return Number(form.return_odometer) >= startingMileage.value;
});

const totalDamagesAmount = computed(() => {
    return form.damages.reduce((sum, d) => sum + (Number(d.cost) || 0), 0);
});

const netDepositRefund = computed(() => {
    return Math.max(0, 5000 - totalDamagesAmount.value);
});

const submitCheckin = () => {
    if (!props.reservation || !isOdometerValid.value) return;

    form.patch(`/admin/operations/checkin/${props.reservation.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
        },
    });
};
</script>

<template>
    <div
        v-if="isOpen && reservation"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"
            @click="emit('close')"
        ></div>

        <!-- Modal Card -->
        <div
            class="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-3xl border border-slate-200/90 bg-white p-5 shadow-2xl transition-all dark:border-white/10 dark:bg-[#0c1017] dark:text-slate-100 sm:p-7"
        >
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-slate-100 pb-4 dark:border-white/5">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                        <RotateCcw class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                Process Vehicle Return & Damage Audit
                            </h2>
                            <span class="rounded bg-cyan-500/10 px-2 py-0.5 text-[10px] font-bold text-cyan-600 dark:text-cyan-400 font-mono">
                                Step {{ currentStep }} of 2
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            #{{ reservation.reservation_number }} • {{ reservation.user?.name }} • {{ reservation.car?.make }} {{ reservation.car?.model }} ({{ reservation.car?.license_plate }})
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

            <!-- Wizard Step Pills -->
            <div class="mt-4 flex items-center justify-center gap-3 text-xs font-semibold">
                <button
                    type="button"
                    @click="currentStep = 1"
                    class="flex items-center gap-2 rounded-xl px-4 py-1.5 transition"
                    :class="currentStep === 1
                        ? 'bg-[#58779D]/15 text-[#58779D] font-bold dark:bg-[#7094BF]/20 dark:text-[#7094BF]'
                        : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                >
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px]" :class="currentStep === 1 ? 'bg-[#58779D] text-white dark:bg-[#7094BF]' : 'bg-slate-200 dark:bg-white/10'">1</span>
                    <span>Telemetry & Hub Status</span>
                </button>

                <span class="text-slate-300 dark:text-white/20">➔</span>

                <button
                    type="button"
                    @click="isOdometerValid && (currentStep = 2)"
                    :disabled="!isOdometerValid"
                    class="flex items-center gap-2 rounded-xl px-4 py-1.5 transition disabled:opacity-40"
                    :class="currentStep === 2
                        ? 'bg-[#58779D]/15 text-[#58779D] font-bold dark:bg-[#7094BF]/20 dark:text-[#7094BF]'
                        : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                >
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px]" :class="currentStep === 2 ? 'bg-[#58779D] text-white dark:bg-[#7094BF]' : 'bg-slate-200 dark:bg-white/10'">2</span>
                    <span>Body Blueprint & Deposit Settlement</span>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitCheckin" class="mt-4">
                
                <!-- STEP 1: Telemetry & Hub Status -->
                <div v-show="currentStep === 1" class="space-y-4">
                    <!-- Vehicle Summary Banner -->
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-white/10 dark:bg-white/5">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">
                                {{ reservation.car?.make }} {{ reservation.car?.model }}
                            </span>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                License Plate: {{ reservation.car?.license_plate }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Initial Dispatch Mileage</span>
                            <p class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ startingMileage.toLocaleString() }} KM
                            </p>
                        </div>
                    </div>

                    <!-- Return Odometer Input -->
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                                <Gauge class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                <span>Return Odometer (KM)</span>
                            </label>
                            <span
                                v-if="!isOdometerValid"
                                class="text-[11px] font-semibold text-rose-500"
                            >
                                Cannot be less than {{ startingMileage.toLocaleString() }} KM
                            </span>
                        </div>

                        <div class="relative mt-1">
                            <input
                                v-model.number="form.return_odometer"
                                type="number"
                                :min="startingMileage"
                                step="1"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-sm font-bold text-slate-900 shadow-xs focus:border-[#58779D] focus:ring-2 focus:ring-[#58779D]/20 focus:outline-hidden dark:border-white/10 dark:bg-white/5 dark:text-white"
                                :class="{ 'border-rose-500': !isOdometerValid }"
                                required
                            />
                            <span class="pointer-events-none absolute right-3.5 top-2.5 text-xs font-bold text-slate-400">
                                KM
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                            Logged Trip Distance:
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                +{{ Math.max(0, Number(form.return_odometer) - startingMileage).toLocaleString() }} KM
                            </span>
                        </p>
                    </div>

                    <!-- Fuel Level Check -->
                    <div>
                        <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                            <Fuel class="h-3.5 w-3.5 text-emerald-500" />
                            <span>Returned Fuel Level</span>
                        </label>

                        <div class="mt-1.5 grid grid-cols-4 gap-2">
                            <button
                                v-for="opt in [
                                    { val: 'full', label: '100% Full' },
                                    { val: 'good', label: '75% Good' },
                                    { val: 'low', label: '50% Half' },
                                    { val: 'empty', label: '< 25% Low' },
                                ]"
                                :key="opt.val"
                                type="button"
                                @click="form.fuel_level = opt.val"
                                class="rounded-xl border py-2 text-center text-xs font-semibold transition"
                                :class="form.fuel_level === opt.val
                                    ? 'border-[#58779D] bg-[#58779D]/15 text-[#58779D] dark:border-[#7094BF] dark:bg-[#7094BF]/20 dark:text-[#7094BF]'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Next Vehicle Operational Status -->
                    <div>
                        <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                            <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                            <span>Route Vehicle Next To</span>
                        </label>

                        <div class="mt-1.5 grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="form.next_status = 'cleaning'"
                                class="flex flex-col items-center gap-1 rounded-xl border p-2.5 text-center transition"
                                :class="form.next_status === 'cleaning'
                                    ? 'border-violet-500 bg-violet-500/15 text-violet-600 dark:text-violet-400'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                            >
                                <Sparkles class="h-4 w-4" />
                                <span class="text-xs font-bold">Cleaning / Prep</span>
                            </button>

                            <button
                                type="button"
                                @click="form.next_status = 'available'"
                                class="flex flex-col items-center gap-1 rounded-xl border p-2.5 text-center transition"
                                :class="form.next_status === 'available'
                                    ? 'border-emerald-500 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                            >
                                <CheckCircle2 class="h-4 w-4" />
                                <span class="text-xs font-bold">Ready Available</span>
                            </button>

                            <button
                                type="button"
                                @click="form.next_status = 'maintenance'"
                                class="flex flex-col items-center gap-1 rounded-xl border p-2.5 text-center transition"
                                :class="form.next_status === 'maintenance'
                                    ? 'border-rose-500 bg-rose-500/15 text-rose-600 dark:text-rose-400'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                            >
                                <Wrench class="h-4 w-4" />
                                <span class="text-xs font-bold">Service / Maint</span>
                            </button>
                        </div>
                    </div>

                    <!-- Handover Condition Notes -->
                    <div>
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            Check-in Inspection Notes
                        </label>
                        <textarea
                            v-model="form.condition_notes"
                            rows="2"
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-xs focus:border-[#58779D] focus:ring-2 focus:ring-[#58779D]/20 focus:outline-hidden dark:border-white/10 dark:bg-white/5 dark:text-white"
                            placeholder="Log vehicle return condition, cleanliness, toll tags..."
                        ></textarea>
                    </div>

                    <!-- Step 1 Footer -->
                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 dark:border-white/5">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            @click="currentStep = 2"
                            :disabled="!isOdometerValid"
                            class="apple-btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold disabled:opacity-50"
                        >
                            <span>Next: Vehicle Condition Blueprint</span>
                            <ArrowRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Body Condition Blueprint & Deposit Settlement -->
                <div v-show="currentStep === 2" class="space-y-4">
                    <!-- Embedded Interactive Vehicle Damage Blueprint -->
                    <VehicleDamageBlueprint v-model="form.damages" />

                    <!-- Final Settlement Ledger Banner -->
                    <div class="rounded-2xl border border-slate-200/90 bg-slate-50/90 p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Shield class="h-4 w-4 text-cyan-500" />
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Final Deposit Settlement Summary
                                </span>
                            </div>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase"
                                :class="totalDamagesAmount === 0
                                    ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                    : totalDamagesAmount <= 5000
                                        ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                        : 'bg-rose-500/15 text-rose-600 dark:text-rose-400'"
                            >
                                {{ totalDamagesAmount === 0 ? 'Full Refund (Pristine)' : totalDamagesAmount <= 5000 ? 'Partial Deposit Deduction' : 'Shortfall Owed' }}
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="rounded-xl border border-slate-200 bg-white p-2 dark:border-white/10 dark:bg-white/5">
                                <span class="text-[10px] text-slate-400 uppercase font-semibold">Pre-auth Deposit</span>
                                <p class="font-mono text-sm font-bold text-slate-900 dark:text-white">₹5,000.00</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-2 dark:border-white/10 dark:bg-white/5">
                                <span class="text-[10px] text-slate-400 uppercase font-semibold">Damage Deductions</span>
                                <p class="font-mono text-sm font-bold" :class="totalDamagesAmount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'">
                                    {{ totalDamagesAmount > 0 ? `-₹${totalDamagesAmount.toLocaleString()}` : '₹0.00' }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-2 dark:border-white/10 dark:bg-white/5">
                                <span class="text-[10px] text-slate-400 uppercase font-semibold">Net Refund Initiated</span>
                                <p class="font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                    ₹{{ netDepositRefund.toLocaleString() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 Footer / Submit -->
                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 dark:border-white/5">
                        <button
                            type="button"
                            @click="currentStep = 1"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                        >
                            <ArrowLeft class="h-3.5 w-3.5" />
                            <span>Back to Telemetry</span>
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing || !isOdometerValid"
                            class="apple-btn-primary inline-flex items-center gap-2 px-6 py-2.5 text-xs font-semibold disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            <span>Confirm Check-in & Settle Deposit</span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</template>
