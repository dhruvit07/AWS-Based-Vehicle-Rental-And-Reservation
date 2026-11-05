<script setup lang="ts">
import {
    AlertCircle,
    AlertTriangle,
    Car,
    Check,
    CheckCircle2,
    Info,
    Plus,
    RotateCcw,
    Sparkles,
    Trash2,
    Wrench,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

export interface DamageItem {
    id: string;
    zone: string;
    zone_label: string;
    type: 'scratch' | 'dent' | 'chip' | 'crack' | 'missing';
    severity: 'minor' | 'moderate' | 'severe';
    cost: number;
    notes?: string;
    x: number; // percentage 0-100
    y: number; // percentage 0-100
}

const props = withDefaults(
    defineProps<{
        modelValue?: DamageItem[];
        readonly?: boolean;
    }>(),
    {
        modelValue: () => [],
        readonly: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: DamageItem[]): void;
}>();

const damages = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

// Zones definition for the car blueprint
interface CarZone {
    id: string;
    label: string;
    x: number; // default pin center X %
    y: number; // default pin center Y %
    polygon?: string;
}

const ZONES: CarZone[] = [
    { id: 'front_bumper', label: 'Front Bumper', x: 50, y: 10 },
    { id: 'hood', label: 'Engine Hood', x: 50, y: 22 },
    { id: 'windshield', label: 'Front Windshield', x: 50, y: 34 },
    { id: 'roof', label: 'Cabin Roof', x: 50, y: 50 },
    { id: 'left_front_door', label: 'Front Left Door', x: 26, y: 42 },
    { id: 'left_rear_door', label: 'Rear Left Door', x: 26, y: 58 },
    { id: 'right_front_door', label: 'Front Right Door', x: 74, y: 42 },
    { id: 'right_rear_door', label: 'Rear Right Door', x: 74, y: 58 },
    { id: 'rear_glass', label: 'Rear Window', x: 50, y: 68 },
    { id: 'trunk', label: 'Trunk / Boot', x: 50, y: 78 },
    { id: 'rear_bumper', label: 'Rear Bumper', x: 50, y: 90 },
    { id: 'wheel_fl', label: 'Front-Left Wheel', x: 18, y: 26 },
    { id: 'wheel_fr', label: 'Front-Right Wheel', x: 82, y: 26 },
    { id: 'wheel_rl', label: 'Rear-Left Wheel', x: 18, y: 74 },
    { id: 'wheel_rr', label: 'Rear-Right Wheel', x: 82, y: 74 },
];

const selectedZone = ref<CarZone | null>(null);
const activeDamageForm = ref<{
    type: 'scratch' | 'dent' | 'chip' | 'crack' | 'missing';
    severity: 'minor' | 'moderate' | 'severe';
    cost: number;
    notes: string;
    x: number;
    y: number;
}>({
    type: 'scratch',
    severity: 'minor',
    cost: 1000,
    notes: '',
    x: 50,
    y: 50,
});

const activeHoveredPin = ref<DamageItem | null>(null);

const SEVERITY_COST_MAP: Record<string, Record<string, number>> = {
    scratch: { minor: 800, moderate: 1500, severe: 2500 },
    dent: { minor: 1500, moderate: 3000, severe: 4500 },
    chip: { minor: 600, moderate: 1200, severe: 2000 },
    crack: { minor: 2000, moderate: 3500, severe: 6000 },
    missing: { minor: 1500, moderate: 3000, severe: 5000 },
};

const updateEstimatedCost = () => {
    const type = activeDamageForm.value.type;
    const severity = activeDamageForm.value.severity;
    activeDamageForm.value.cost = SEVERITY_COST_MAP[type]?.[severity] || 1000;
};

const handleZoneClick = (zone: CarZone) => {
    if (props.readonly) return;
    selectedZone.value = zone;
    activeDamageForm.value = {
        type: 'scratch',
        severity: 'minor',
        cost: 800,
        notes: '',
        x: zone.x,
        y: zone.y,
    };
};

const handleSvgClick = (event: MouseEvent) => {
    if (props.readonly) return;
    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const clickX = Math.round(((event.clientX - rect.left) / rect.width) * 100);
    const clickY = Math.round(((event.clientY - rect.top) / rect.height) * 100);

    // Find closest zone
    let closest = ZONES[0];
    let minDistance = 99999;
    for (const z of ZONES) {
        const d = Math.hypot(z.x - clickX, z.y - clickY);
        if (d < minDistance) {
            minDistance = d;
            closest = z;
        }
    }

    selectedZone.value = closest;
    activeDamageForm.value = {
        type: 'scratch',
        severity: 'minor',
        cost: 800,
        notes: '',
        x: clickX,
        y: clickY,
    };
};

const addDamagePin = () => {
    if (!selectedZone.value) return;

    const newPin: DamageItem = {
        id: 'dmg-' + Date.now().toString(36) + Math.random().toString(36).substring(2, 5),
        zone: selectedZone.value.id,
        zone_label: selectedZone.value.label,
        type: activeDamageForm.value.type,
        severity: activeDamageForm.value.severity,
        cost: Number(activeDamageForm.value.cost) || 0,
        notes: activeDamageForm.value.notes.trim() || undefined,
        x: activeDamageForm.value.x,
        y: activeDamageForm.value.y,
    };

    damages.value = [...damages.value, newPin];
    selectedZone.value = null;
};

const removeDamagePin = (pinId: string) => {
    if (props.readonly) return;
    damages.value = damages.value.filter((d) => d.id !== pinId);
};

const simulateVivaDemo = () => {
    const bumperZone = ZONES.find((z) => z.id === 'front_bumper') || ZONES[0];
    const demoDamage: DamageItem = {
        id: 'dmg-viva-' + Date.now().toString(36),
        zone: bumperZone.id,
        zone_label: bumperZone.label,
        type: 'scratch',
        severity: 'moderate',
        cost: 1500,
        notes: 'Simulated 12cm curb contact scratch on lower air splitter.',
        x: 48,
        y: 11,
    };

    damages.value = [...damages.value, demoDamage];
};

const clearAllDamages = () => {
    damages.value = [];
};

const totalAssessedCost = computed(() =>
    damages.value.reduce((acc, curr) => acc + (Number(curr.cost) || 0), 0),
);

const getPinColor = (severity: string) => {
    switch (severity) {
        case 'severe':
            return 'bg-rose-500 ring-rose-400 text-white';
        case 'moderate':
            return 'bg-amber-500 ring-amber-400 text-white';
        default:
            return 'bg-amber-400 ring-amber-300 text-slate-900';
    }
};
</script>

<template>
    <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-5 backdrop-blur-xl transition dark:border-white/10 dark:bg-white/[0.03]">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4 dark:border-white/10">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                        <Car class="h-4 w-4" />
                    </span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ readonly ? 'Vehicle Condition Audit Blueprint' : 'Interactive Body Condition Inspector' }}
                    </h3>
                </div>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ readonly ? 'Verified physical inspection record & damage pinpoint markers.' : 'Click any vehicle section or wheel to log physical wear, scratches, or dents.' }}
                </p>
            </div>

            <!-- Quick Viva Action (Visible only in editable mode) -->
            <div v-if="!readonly" class="flex items-center gap-2">
                <button
                    type="button"
                    @click="simulateVivaDemo"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-700 transition hover:bg-amber-500/20 dark:text-amber-400"
                    title="1-Click viva presentation simulation"
                >
                    <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                    <span>Simulate Front Scratch (₹1,500)</span>
                </button>

                <button
                    v-if="damages.length > 0"
                    type="button"
                    @click="clearAllDamages"
                    class="inline-flex items-center gap-1 rounded-xl border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:border-white/10 dark:text-slate-400 dark:hover:bg-white/10"
                >
                    <RotateCcw class="h-3 w-3" />
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <!-- Main Body Blueprint Layout -->
        <div class="mt-5 grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            
            <!-- SVG Car Blueprint Visualizer -->
            <div class="relative mx-auto flex w-full max-w-[340px] flex-col items-center justify-center rounded-2xl border border-slate-200/60 bg-gradient-to-b from-slate-50/50 to-slate-100/50 p-4 dark:border-white/5 dark:from-[#0d121d] dark:to-[#090d15] lg:col-span-5">
                
                <div class="mb-2 flex w-full items-center justify-between text-[10px] font-mono uppercase tracking-wider text-slate-400">
                    <span>Front Splitter ⬆️</span>
                    <span>Top-Down CAD Silhouette</span>
                </div>

                <!-- Interactive SVG Canvas Container -->
                <div
                    class="relative aspect-[1/2] w-full max-w-[260px] cursor-crosshair select-none"
                    @click="handleSvgClick"
                >
                    <!-- Vector Top-Down Car Silhouette -->
                    <svg
                        viewBox="0 0 200 400"
                        class="h-full w-full drop-shadow-md transition-all"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <!-- Wheels -->
                        <!-- Front-Left Wheel -->
                        <rect x="18" y="70" width="16" height="42" rx="6" fill="#1e293b" class="dark:fill-slate-600 transition hover:fill-amber-500" />
                        <!-- Front-Right Wheel -->
                        <rect x="166" y="70" width="16" height="42" rx="6" fill="#1e293b" class="dark:fill-slate-600 transition hover:fill-amber-500" />
                        <!-- Rear-Left Wheel -->
                        <rect x="18" y="270" width="16" height="42" rx="6" fill="#1e293b" class="dark:fill-slate-600 transition hover:fill-amber-500" />
                        <!-- Rear-Right Wheel -->
                        <rect x="166" y="270" width="16" height="42" rx="6" fill="#1e293b" class="dark:fill-slate-600 transition hover:fill-amber-500" />

                        <!-- Main Car Outer Chassis -->
                        <path
                            d="M 60 25 
                               C 75 15, 125 15, 140 25 
                               C 165 35, 168 80, 168 110 
                               C 168 130, 172 160, 172 200 
                               C 172 240, 168 270, 168 290 
                               C 168 330, 162 375, 140 385 
                               C 125 390, 75 390, 60 385 
                               C 38 375, 32 330, 32 290 
                               C 32 270, 28 240, 28 200 
                               C 28 160, 32 130, 32 110 
                               C 32 80, 35 35, 60 25 Z"
                            fill="currentColor"
                            class="text-slate-200 stroke-slate-300 stroke-[1.5] dark:text-[#131b29] dark:stroke-slate-700/60"
                        />

                        <!-- Front Bumper Zone -->
                        <path
                            d="M 55 30 Q 100 18 145 30 L 140 50 Q 100 38 60 50 Z"
                            class="fill-slate-300/40 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Engine Hood -->
                        <path
                            d="M 60 55 Q 100 45 140 55 L 145 125 Q 100 120 55 125 Z"
                            class="fill-slate-300/30 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Windshield -->
                        <path
                            d="M 58 132 Q 100 126 142 132 L 136 170 Q 100 166 64 170 Z"
                            class="fill-cyan-500/20 stroke-cyan-500/40 stroke-[1] hover:fill-cyan-500/40 cursor-pointer transition"
                        />

                        <!-- Roof Structure -->
                        <rect
                            x="62"
                            y="176"
                            width="76"
                            height="82"
                            rx="12"
                            class="fill-slate-300/30 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Left Front Door -->
                        <rect
                            x="36"
                            y="140"
                            width="22"
                            height="55"
                            rx="4"
                            class="fill-slate-300/20 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Left Rear Door -->
                        <rect
                            x="36"
                            y="200"
                            width="22"
                            height="55"
                            rx="4"
                            class="fill-slate-300/20 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Right Front Door -->
                        <rect
                            x="142"
                            y="140"
                            width="22"
                            height="55"
                            rx="4"
                            class="fill-slate-300/20 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Right Rear Door -->
                        <rect
                            x="142"
                            y="200"
                            width="22"
                            height="55"
                            rx="4"
                            class="fill-slate-300/20 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Rear Window -->
                        <path
                            d="M 64 264 Q 100 268 136 264 L 140 295 Q 100 300 60 295 Z"
                            class="fill-cyan-500/20 stroke-cyan-500/40 stroke-[1] hover:fill-cyan-500/40 cursor-pointer transition"
                        />

                        <!-- Trunk -->
                        <path
                            d="M 58 302 Q 100 308 142 302 L 145 350 Q 100 358 55 350 Z"
                            class="fill-slate-300/30 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Rear Bumper -->
                        <path
                            d="M 52 355 Q 100 365 148 355 L 140 378 Q 100 385 60 378 Z"
                            class="fill-slate-300/40 hover:fill-amber-500/30 dark:fill-white/5 dark:hover:fill-amber-400/20 cursor-pointer transition"
                        />

                        <!-- Centerline Axis Marker -->
                        <line x1="100" y1="25" x2="100" y2="380" stroke="currentColor" stroke-dasharray="3 3" class="text-slate-400/40" />
                    </svg>

                    <!-- Rendered Pinpoint Markers -->
                    <div
                        v-for="(pin, index) in damages"
                        :key="pin.id"
                        class="absolute -translate-x-1/2 -translate-y-1/2 cursor-pointer transition-transform hover:scale-125 z-10"
                        :style="{ left: pin.x + '%', top: pin.y + '%' }"
                        @mouseenter="activeHoveredPin = pin"
                        @mouseleave="activeHoveredPin = null"
                        @click.stop="!readonly && removeDamagePin(pin.id)"
                        :title="`${pin.zone_label}: ${pin.type} (${pin.severity}) — ₹${pin.cost}`"
                    >
                        <span class="relative flex h-5 w-5 items-center justify-center">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" :class="pin.severity === 'severe' ? 'bg-rose-400' : 'bg-amber-400'"></span>
                            <span
                                class="relative inline-flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-black shadow-md ring-2"
                                :class="getPinColor(pin.severity)"
                            >
                                {{ index + 1 }}
                            </span>
                        </span>
                    </div>

                    <!-- Temporary placement target pin if currently selecting -->
                    <div
                        v-if="selectedZone"
                        class="absolute -translate-x-1/2 -translate-y-1/2 pointer-events-none z-20"
                        :style="{ left: activeDamageForm.x + '%', top: activeDamageForm.y + '%' }"
                    >
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500 text-white shadow-lg ring-4 ring-cyan-400/50 animate-bounce">
                            <Plus class="h-3 w-3" />
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-[10px] font-mono uppercase tracking-wider text-slate-400">
                    <span>Rear Exhaust ⬇️</span>
                </div>

                <!-- Hover Tooltip preview -->
                <div
                    v-if="activeHoveredPin"
                    class="absolute bottom-2 left-2 right-2 rounded-xl border border-slate-200/90 bg-white/95 p-2 text-center text-xs shadow-lg backdrop-blur-md dark:border-white/10 dark:bg-slate-900/95 z-30"
                >
                    <p class="font-bold text-slate-900 dark:text-white">
                        {{ activeHoveredPin.zone_label }} • <span class="capitalize">{{ activeHoveredPin.type }}</span>
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Severity: <span class="capitalize font-semibold">{{ activeHoveredPin.severity }}</span> | Est: <span class="font-bold text-rose-600 dark:text-rose-400">₹{{ activeHoveredPin.cost.toLocaleString() }}</span>
                    </p>
                    <p v-if="activeHoveredPin.notes" class="text-[10px] italic text-slate-400 mt-0.5">
                        "{{ activeHoveredPin.notes }}"
                    </p>
                </div>
            </div>

            <!-- Right Column: Damage Log & Add Form -->
            <div class="lg:col-span-7 space-y-4">
                
                <!-- If Admin is currently adding a pin -->
                <div
                    v-if="selectedZone && !readonly"
                    class="rounded-2xl border border-cyan-500/30 bg-cyan-500/5 p-4 transition-all"
                >
                    <div class="flex items-center justify-between border-b border-cyan-500/20 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-cyan-500/20 text-cyan-600 dark:text-cyan-400">
                                <Plus class="h-3.5 w-3.5" />
                            </span>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                Flagging: {{ selectedZone.label }}
                            </h4>
                        </div>
                        <button
                            type="button"
                            @click="selectedZone = null"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-white"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                        <!-- Damage Type -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Damage Type</label>
                            <select
                                v-model="activeDamageForm.type"
                                @change="updateEstimatedCost"
                                class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-cyan-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            >
                                <option value="scratch">Scratch / Scuff</option>
                                <option value="dent">Body Dent</option>
                                <option value="chip">Stone Chip</option>
                                <option value="crack">Glass / Lamp Crack</option>
                                <option value="missing">Missing Component</option>
                            </select>
                        </div>

                        <!-- Severity -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Severity</label>
                            <select
                                v-model="activeDamageForm.severity"
                                @change="updateEstimatedCost"
                                class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-cyan-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            >
                                <option value="minor">Minor Wear (Surface)</option>
                                <option value="moderate">Moderate (Repaint Req.)</option>
                                <option value="severe">Severe (Panel Repair)</option>
                            </select>
                        </div>

                        <!-- Cost Estimate -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Repair Cost (₹)</label>
                            <input
                                type="number"
                                v-model="activeDamageForm.cost"
                                min="0"
                                step="100"
                                class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 font-mono text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-cyan-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Notes / Observation</label>
                            <input
                                type="text"
                                v-model="activeDamageForm.notes"
                                placeholder="e.g. 5cm scratch on lip"
                                class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-cyan-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="selectedZone = null"
                            class="rounded-xl px-3 py-1 text-xs text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/10"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="addDamagePin"
                            class="rounded-xl bg-cyan-600 px-3.5 py-1 text-xs font-bold text-white shadow-sm hover:bg-cyan-500 transition"
                        >
                            Confirm Damage Pin
                        </button>
                    </div>
                </div>

                <!-- Logged Damages List -->
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        <span>Logged Condition Flags ({{ damages.length }})</span>
                        <span v-if="damages.length > 0" class="font-mono text-rose-600 dark:text-rose-400 font-bold">
                            Total Assessed: ₹{{ totalAssessedCost.toLocaleString() }}
                        </span>
                    </div>

                    <!-- Pristine Empty State -->
                    <div
                        v-if="damages.length === 0"
                        class="rounded-2xl border border-dashed border-emerald-500/30 bg-emerald-500/5 p-6 text-center"
                    >
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <CheckCircle2 class="h-6 w-6" />
                        </div>
                        <h4 class="mt-2 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                            Pristine Asset Condition
                        </h4>
                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                            {{ readonly ? 'No new physical damages or blemishes were detected upon check-in inspection.' : 'No damages logged. Click blueprint zones above if any scratches or dents are observed.' }}
                        </p>
                    </div>

                    <!-- Populated Damages Table -->
                    <div v-else class="space-y-2 max-h-56 overflow-y-auto pr-1">
                        <div
                            v-for="(dmg, idx) in damages"
                            :key="dmg.id"
                            class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-xs transition dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-black"
                                    :class="getPinColor(dmg.severity)"
                                >
                                    {{ idx + 1 }}
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">
                                        {{ dmg.zone_label }} • <span class="capitalize">{{ dmg.type }}</span>
                                    </p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                        Severity: <span class="capitalize font-medium">{{ dmg.severity }}</span>
                                        <span v-if="dmg.notes"> — "{{ dmg.notes }}"</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xs font-bold text-rose-600 dark:text-rose-400">
                                    ₹{{ dmg.cost.toLocaleString() }}
                                </span>
                                <button
                                    v-if="!readonly"
                                    type="button"
                                    @click="removeDamagePin(dmg.id)"
                                    class="rounded-lg p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                    title="Remove damage pin"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Deposit Summary Box -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3.5 dark:border-white/10 dark:bg-white/[0.02]">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600 dark:text-slate-400">Standard Security Deposit:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">₹5,000.00</span>
                    </div>

                    <div class="mt-1.5 flex items-center justify-between text-xs">
                        <span class="text-slate-600 dark:text-slate-400">Assessed Repair Deductions:</span>
                        <span
                            class="font-mono font-bold"
                            :class="totalAssessedCost > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'"
                        >
                            {{ totalAssessedCost > 0 ? `-₹${totalAssessedCost.toLocaleString('en-IN', { minimumFractionDigits: 2 })}` : '₹0.00' }}
                        </span>
                    </div>

                    <div class="mt-2.5 flex items-center justify-between border-t border-slate-200 pt-2 text-xs font-bold dark:border-white/10">
                        <span class="text-slate-900 dark:text-white">Net Deposit Refundable to Hirer:</span>
                        <span
                            class="font-mono text-sm"
                            :class="totalAssessedCost === 0 ? 'text-emerald-600 dark:text-emerald-400' : totalAssessedCost < 5000 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'"
                        >
                            ₹{{ Math.max(0, 5000 - totalAssessedCost).toLocaleString('en-IN', { minimumFractionDigits: 2 }) }}
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
