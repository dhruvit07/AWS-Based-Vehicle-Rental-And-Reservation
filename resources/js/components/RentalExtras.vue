<script setup lang="ts">
import { Baby, Check, Navigation, ShieldCheck, UserCheck } from 'lucide-vue-next';

export interface ExtraOption {
    id: string;
    name: string;
    rate: number;
    type: 'per_day' | 'fixed';
    description: string;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string[];
        rentalDays?: number;
    }>(),
    {
        modelValue: () => [],
        rentalDays: 1,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const extrasCatalog: ExtraOption[] = [
    {
        id: 'insurance',
        name: 'Zero-Deductible Insurance',
        rate: 499,
        type: 'per_day',
        description: 'Comprehensive damage waiver covering glass, tyres, and accidental chassis damage with zero liability.',
    },
    {
        id: 'gps',
        name: 'GPS Navigation System',
        rate: 149,
        type: 'per_day',
        description: 'Dedicated in-car satellite navigation with pre-loaded offline maps and live traffic alerts.',
    },
    {
        id: 'child_seat',
        name: 'Child Safety Seat',
        rate: 199,
        type: 'per_day',
        description: 'Multi-stage certified infant and child booster seat, sanitized prior to dispatch.',
    },
    {
        id: 'additional_driver',
        name: 'Additional Driver',
        rate: 499,
        type: 'fixed',
        description: 'Register a second verified driver on the insurance policy for the entire rental duration.',
    },
];

const isSelected = (id: string): boolean => props.modelValue.includes(id);

const toggleExtra = (id: string) => {
    const current = [...props.modelValue];
    const index = current.indexOf(id);
    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(id);
    }
    emit('update:modelValue', current);
};

const formatPrice = (amount: number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(amount);

const calculateCost = (extra: ExtraOption): number => {
    if (extra.type === 'per_day') {
        const days = Math.max(1, props.rentalDays);
        return extra.rate * days;
    }
    return extra.rate;
};
</script>

<template>
    <div class="space-y-3.5">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold tracking-tight text-slate-950 dark:text-white">
                Optional Upgrades & Protection
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400">Select any add-ons</span>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div
                v-for="extra in extrasCatalog"
                :key="extra.id"
                role="checkbox"
                :aria-checked="isSelected(extra.id)"
                tabindex="0"
                @click="toggleExtra(extra.id)"
                @keydown.space.prevent="toggleExtra(extra.id)"
                @keydown.enter.prevent="toggleExtra(extra.id)"
                class="group relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition focus:outline-none focus:ring-2 focus:ring-[#58779D]"
                :class="[
                    isSelected(extra.id)
                        ? 'border-[#101D35] bg-slate-100/90 shadow-xs dark:border-white dark:bg-white/10'
                        : 'border-slate-200/80 bg-white/70 hover:border-slate-300 dark:border-white/10 dark:bg-white/5 dark:hover:border-white/20',
                ]"
            >
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition"
                                :class="[
                                    isSelected(extra.id)
                                        ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950'
                                        : 'bg-slate-100 text-[#58779D] dark:bg-white/10 dark:text-[#7094BF]',
                                ]"
                            >
                                <ShieldCheck v-if="extra.id === 'insurance'" class="h-4 w-4" />
                                <Navigation v-else-if="extra.id === 'gps'" class="h-4 w-4" />
                                <Baby v-else-if="extra.id === 'child_seat'" class="h-4 w-4" />
                                <UserCheck v-else class="h-4 w-4" />
                            </div>

                            <div>
                                <h4 class="text-xs font-bold text-slate-950 dark:text-white">
                                    {{ extra.name }}
                                </h4>
                                <span class="text-[11px] font-semibold text-[#58779D] dark:text-[#7094BF]">
                                    {{ extra.type === 'per_day' ? `+${formatPrice(extra.rate)} / day` : `Flat ${formatPrice(extra.rate)}` }}
                                </span>
                            </div>
                        </div>

                        <!-- Checkbox indicator -->
                        <div
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition"
                            :class="[
                                isSelected(extra.id)
                                    ? 'border-[#101D35] bg-[#101D35] text-white dark:border-white dark:bg-white dark:text-slate-950'
                                    : 'border-slate-300 bg-white dark:border-white/20 dark:bg-white/5',
                            ]"
                        >
                            <Check v-if="isSelected(extra.id)" class="h-3.5 w-3.5 stroke-[3]" />
                        </div>
                    </div>

                    <p class="mt-2.5 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ extra.description }}
                    </p>
                </div>

                <div class="mt-3.5 flex items-center justify-between border-t border-slate-200/80 pt-2.5 text-xs dark:border-white/10">
                    <span class="text-slate-500 dark:text-slate-400">
                        {{ extra.type === 'per_day' ? `${props.rentalDays} day${props.rentalDays === 1 ? '' : 's'}` : 'Single charge' }}
                    </span>
                    <span class="font-bold text-slate-950 dark:text-white">
                        {{ formatPrice(calculateCost(extra)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
