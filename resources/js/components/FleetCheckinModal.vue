<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Car,
    CheckCircle2,
    Fuel,
    Gauge,
    Loader2,
    RotateCcw,
    Sparkles,
    Wrench,
    X,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';

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

const startingMileage = computed(() => Number(props.reservation?.car?.mileage || 0));

const form = useForm({
    return_odometer: 0,
    fuel_level: 'full',
    next_status: 'cleaning',
    condition_notes: '',
});

watch(
    () => props.reservation,
    (res) => {
        if (res && res.car) {
            // Suggest +120 km by default
            form.return_odometer = Number(res.car.mileage) + 120;
            form.fuel_level = 'full';
            form.next_status = 'cleaning';
            form.condition_notes = 'Clean return. No new cosmetic scratches observed.';
            form.clearErrors();
        }
    },
    { immediate: true },
);

const isOdometerValid = computed(() => {
    return Number(form.return_odometer) >= startingMileage.value;
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
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
            @click="emit('close')"
        ></div>

        <!-- Modal Card -->
        <div
            class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-2xl transition-all dark:border-white/10 dark:bg-[#0c1017] dark:text-slate-100 sm:p-7"
        >
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-slate-100 pb-4 dark:border-white/5">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                        <RotateCcw class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            Process Vehicle Return
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            #{{ reservation.reservation_number }} • {{ reservation.user?.name }}
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

            <!-- Vehicle Summary Banner -->
            <div class="mt-4 flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-white/10 dark:bg-white/5">
                <div>
                    <span class="text-xs font-bold text-slate-900 dark:text-white">
                        {{ reservation.car?.make }} {{ reservation.car?.model }}
                    </span>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        License Plate: {{ reservation.car?.license_plate }}
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Initial Mileage</span>
                    <p class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ startingMileage.toLocaleString() }} KM
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitCheckin" class="mt-4 space-y-4">
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
                        Trip Distance:
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

                <!-- Actions -->
                <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4 dark:border-white/5">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing || !isOdometerValid"
                        class="apple-btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <CheckCircle2 v-else class="h-4 w-4" />
                        <span>Confirm Return & Complete</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
