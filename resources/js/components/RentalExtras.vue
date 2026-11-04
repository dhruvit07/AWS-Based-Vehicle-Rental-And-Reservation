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
        description: 'Complete peace of mind with zero customer liability on accidental damage and glass.',
    },
    {
        id: 'gps',
        name: 'GPS Navigation System',
        rate: 149,
        type: 'per_day',
        description: 'High-precision standalone satellite navigation with offline route guidance.',
    },
    {
        id: 'child_seat',
        name: 'Child Safety Seat',
        rate: 199,
        type: 'per_day',
        description: 'Certified multi-age child restraint booster seat, sanitized and inspected.',
    },
    {
        id: 'additional_driver',
        name: 'Additional Driver',
        rate: 499,
        type: 'fixed',
        description: 'Authorize a second verified driver for the entire rental journey.',
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
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="flex items-center gap-2 font-semibold text-[#101D35]">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-[#58779D]">
                    3
                </span>
                Rental add-ons & protection
            </h3>
            <span class="text-xs font-medium text-slate-500">Optional upgrades</span>
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
                class="group relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition focus:outline-none focus:ring-2 focus:ring-[#58779D] focus:ring-offset-1"
                :class="[
                    isSelected(extra.id)
                        ? 'border-[#101D35] bg-slate-50/70 shadow-xs'
                        : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/40',
                ]"
            >
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition"
                                :class="[
                                    isSelected(extra.id)
                                        ? 'bg-[#101D35] text-white'
                                        : 'bg-slate-100 text-[#58779D] group-hover:bg-slate-200',
                                ]"
                            >
                                <ShieldCheck v-if="extra.id === 'insurance'" class="h-5 w-5" />
                                <Navigation v-else-if="extra.id === 'gps'" class="h-5 w-5" />
                                <Baby v-else-if="extra.id === 'child_seat'" class="h-5 w-5" />
                                <UserCheck v-else class="h-5 w-5" />
                            </div>

                            <div>
                                <h4 class="text-sm font-bold text-[#101D35]">
                                    {{ extra.name }}
                                </h4>
                                <span
                                    class="inline-block text-xs font-semibold"
                                    :class="isSelected(extra.id) ? 'text-[#101D35]' : 'text-[#58779D]'"
                                >
                                    {{ extra.type === 'per_day' ? `+${formatPrice(extra.rate)} / day` : `Flat ${formatPrice(extra.rate)}` }}
                                </span>
                            </div>
                        </div>

                        <!-- Checkbox indicator -->
                        <div
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition"
                            :class="[
                                isSelected(extra.id)
                                    ? 'border-[#101D35] bg-[#101D35] text-white'
                                    : 'border-slate-300 bg-white group-hover:border-slate-400',
                            ]"
                        >
                            <Check v-if="isSelected(extra.id)" class="h-3.5 w-3.5 stroke-[3]" />
                        </div>
                    </div>

                    <p class="mt-2.5 text-xs leading-5 text-slate-600">
                        {{ extra.description }}
                    </p>
                </div>

                <div class="mt-3.5 flex items-center justify-between border-t border-slate-100 pt-2.5 text-xs">
                    <span class="text-slate-500">
                        {{ extra.type === 'per_day' ? `${props.rentalDays} day${props.rentalDays === 1 ? '' : 's'}` : 'Single charge' }}
                    </span>
                    <span class="font-bold" :class="isSelected(extra.id) ? 'text-[#101D35]' : 'text-slate-700'">
                        {{ formatPrice(calculateCost(extra)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
