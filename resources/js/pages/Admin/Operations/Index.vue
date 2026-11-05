<script setup lang="ts">
import FleetCheckinModal from '@/components/FleetCheckinModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowUpRight,
    BatteryCharging,
    Car,
    CheckCircle2,
    Clock,
    Compass,
    Crown,
    ExternalLink,
    FileCheck2,
    Fuel,
    Gauge,
    Layers,
    MapPin,
    Radio,
    RefreshCw,
    RotateCcw,
    Search,
    Send,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    UserCheck,
    Wrench,
    Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Hub {
    id: string;
    code: string;
    name: string;
    city: string;
    capacity: number;
    x: number;
    y: number;
    type: string;
}

interface Telemetry {
    fuel_level: number;
    is_electric: boolean;
    battery_health: number;
    tire_pressure_psi: number;
    engine_health: string;
    speed_kmh: number;
    is_moving: boolean;
    last_ping: string;
    hub_name: string;
    hub_code: string;
    map_x: number;
    map_y: number;
}

interface ActiveReservation {
    id: number;
    reservation_number: string;
    status: string;
    start_date: string;
    end_date: string;
    user_name: string;
    user_license: string;
}

interface FleetVehicle {
    id: number;
    make: string;
    model: string;
    year: number;
    license_plate: string;
    color: string;
    status: string;
    status_label: string;
    status_color: string;
    mileage: number;
    fuel_type: string;
    transmission: string;
    seats: number;
    price_per_day: number;
    telemetry: Telemetry;
    active_reservation?: ActiveReservation | null;
}

interface QueueReservation {
    id: number;
    reservation_number: string;
    start_date: string;
    end_date: string;
    pickup_time?: string | null;
    return_time?: string | null;
    total_days: number;
    total_amount: number | string;
    status: string;
    user?: {
        id: number;
        name: string;
        email: string;
        license_number?: string | null;
        loyalty_tier?: string | null;
    } | null;
    car?: {
        id: number;
        make: string;
        model: string;
        license_plate: string;
        mileage: number;
    } | null;
}

const props = defineProps<{
    kpis: {
        total_fleet: number;
        on_road: number;
        available: number;
        maintenance: number;
        utilization_rate: number;
        pickups_due_count: number;
        returns_due_count: number;
    };
    hubs: Hub[];
    fleet: FleetVehicle[];
    pickupsDue: QueueReservation[];
    returnsDue: QueueReservation[];
    carStatusOptions: Array<{ value: string; label: string; color: string }>;
}>();

// Selected vehicle in telemetry inspector
const selectedCarId = ref<number>(props.fleet[0]?.id || 0);

// Hub filter selection ('all' or hub.code)
const selectedHubCode = ref<string>('all');

// Search filter in fleet list
const fleetSearchQuery = ref<string>('');

// Local simulated fleet copy for dynamic live updates
const localFleet = ref<FleetVehicle[]>(JSON.parse(JSON.stringify(props.fleet)));
const isSimulating = ref<boolean>(false);
const simulationToast = ref<string | null>(null);
const activeQueueTab = ref<'pickups' | 'returns'>('pickups');

// Check-in return modal state
const isCheckinModalOpen = ref<boolean>(false);
const activeReturnReservation = ref<QueueReservation | null>(null);

// Radar sweep animation degree
const radarAngle = ref<number>(0);
let radarTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    radarTimer = setInterval(() => {
        radarAngle.value = (radarAngle.value + 3) % 360;
    }, 40);
});

onUnmounted(() => {
    if (radarTimer) clearInterval(radarTimer);
});

// Selected car computed
const selectedCar = computed(() => {
    return localFleet.value.find((c) => c.id === selectedCarId.value) || localFleet.value[0];
});

// Filtered fleet by hub & search query
const filteredFleet = computed(() => {
    return localFleet.value.filter((car) => {
        const matchesHub =
            selectedHubCode.value === 'all' ||
            car.telemetry.hub_code === selectedHubCode.value;
        const q = fleetSearchQuery.value.trim().toLowerCase();
        const matchesSearch =
            !q ||
            car.make.toLowerCase().includes(q) ||
            car.model.toLowerCase().includes(q) ||
            car.license_plate.toLowerCase().includes(q);
        return matchesHub && matchesSearch;
    });
});

// 1-Click Simulation of live fleet telemetry
const triggerFleetSimulation = () => {
    isSimulating.value = true;
    simulationToast.value = 'GPS Polling Dispatched • Synchronizing Satellite Telemetry...';

    setTimeout(() => {
        localFleet.value = localFleet.value.map((car) => {
            if (car.status === 'rented') {
                const newSpeed = Math.floor(Math.random() * 45) + 35;
                const newFuel = Math.max(15, car.telemetry.fuel_level - 1);
                const deltaX = Math.random() * 4 - 2;
                const deltaY = Math.random() * 4 - 2;
                return {
                    ...car,
                    mileage: car.mileage + 3,
                    telemetry: {
                        ...car.telemetry,
                        speed_kmh: newSpeed,
                        fuel_level: newFuel,
                        map_x: Math.min(88, Math.max(12, car.telemetry.map_x + deltaX)),
                        map_y: Math.min(88, Math.max(12, car.telemetry.map_y + deltaY)),
                        last_ping: 'Just now',
                    },
                };
            } else {
                return {
                    ...car,
                    telemetry: {
                        ...car.telemetry,
                        last_ping: '1s ago',
                    },
                };
            }
        });

        isSimulating.value = false;
        simulationToast.value = 'Telemetry Synchronized • All Active Transponders Acknowledged';

        setTimeout(() => {
            simulationToast.value = null;
        }, 3200);
    }, 650);
};

// Dispatch a pickup (Handover)
const dispatchReservation = (res: QueueReservation) => {
    if (
        !confirm(
            `Confirm vehicle handover for ${res.car?.make} ${res.car?.model} to ${res.user?.name}?`,
        )
    ) {
        return;
    }

    router.patch(
        `/admin/operations/dispatch/${res.id}`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                const target = localFleet.value.find((c) => c.id === res.car?.id);
                if (target) {
                    target.status = 'rented';
                    target.status_label = 'Rented';
                    target.telemetry.speed_kmh = 42;
                    target.telemetry.is_moving = true;
                }
            },
        },
    );
};

// Open check-in modal
const openCheckinModal = (res: QueueReservation) => {
    activeReturnReservation.value = res;
    isCheckinModalOpen.value = true;
};

// Quick status override for any vehicle
const quickChangeStatus = (carId: number, newStatus: string) => {
    router.patch(
        `/admin/operations/cars/${carId}/status`,
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => {
                const target = localFleet.value.find((c) => c.id === carId);
                if (target) {
                    target.status = newStatus;
                    target.status_label =
                        newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    if (newStatus !== 'rented') {
                        target.telemetry.speed_kmh = 0;
                        target.telemetry.is_moving = false;
                    }
                }
            },
        },
    );
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'available':
            return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 dark:text-emerald-400';
        case 'rented':
            return 'bg-sky-500/10 text-sky-600 border-sky-500/20 dark:text-sky-400';
        case 'cleaning':
            return 'bg-amber-500/10 text-amber-600 border-amber-500/20 dark:text-amber-400';
        case 'maintenance':
            return 'bg-rose-500/10 text-rose-600 border-rose-500/20 dark:text-rose-400';
        default:
            return 'bg-slate-500/10 text-slate-600 border-slate-500/20 dark:text-slate-400';
    }
};

const getStatusDotClass = (status: string) => {
    switch (status) {
        case 'available':
            return 'bg-emerald-500';
        case 'rented':
            return 'bg-sky-500';
        case 'cleaning':
            return 'bg-amber-500';
        case 'maintenance':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
};

const breadcrumbs = [
    { title: 'Operations', href: '/admin/operations' },
    { title: 'Fleet Radar Mission Control', href: '/admin/operations' },
];
</script>

<template>
    <Head title="Operations Mission Control | Real Rent Car" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 pb-12">
            <!-- Command Center Header Banner -->
            <div
                class="flex flex-col gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-[#0A101D]"
            >
                <div class="flex items-center gap-3.5">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#58779D]/15 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]"
                    >
                        <Compass class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-black tracking-tight text-slate-900 dark:text-white">
                                Fleet Operations Mission Control
                            </h1>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                    ></span>
                                    <span
                                        class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                                    ></span>
                                </span>
                                <span>Radar 25 Hz Live</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Real-time GPS telemetry, regional mobility hubs, and 1-click dispatch operations.
                        </p>
                    </div>
                </div>

                <!-- Simulation & Hub Filter Ribbon -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <div
                        v-if="simulationToast"
                        class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        {{ simulationToast }}
                    </div>

                    <!-- Hub Quick Filter Pills -->
                    <div
                        class="flex items-center rounded-xl border border-slate-200/80 bg-slate-50 p-1 dark:border-white/10 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            @click="selectedHubCode = 'all'"
                            class="rounded-lg px-2.5 py-1 text-xs font-semibold transition"
                            :class="
                                selectedHubCode === 'all'
                                    ? 'bg-white text-slate-900 shadow-xs dark:bg-white/10 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400'
                            "
                        >
                            All Hubs
                        </button>
                        <button
                            v-for="hub in props.hubs"
                            :key="hub.id"
                            type="button"
                            @click="selectedHubCode = hub.code"
                            class="rounded-lg px-2.5 py-1 font-mono text-xs font-semibold transition"
                            :class="
                                selectedHubCode === hub.code
                                    ? 'bg-white text-slate-900 shadow-xs dark:bg-white/10 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400'
                            "
                        >
                            {{ hub.code }}
                        </button>
                    </div>

                    <!-- Simulate Movement Action -->
                    <button
                        type="button"
                        @click="triggerFleetSimulation"
                        :disabled="isSimulating"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#101D35] px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-[#1a2d52] disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                    >
                        <RefreshCw
                            class="h-3.5 w-3.5"
                            :class="{ 'animate-spin': isSimulating }"
                        />
                        <span>Simulate Movement</span>
                    </button>
                </div>
            </div>

            <!-- Operational KPI Metrics Ribbon (5 Telemetry Cards) -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <!-- Fleet Utilization Rate -->
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
                >
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Utilization</span>
                        <Activity class="h-4 w-4 text-sky-500" />
                    </div>
                    <p class="mt-2 font-mono text-2xl font-black text-slate-900 dark:text-white">
                        {{ props.kpis.utilization_rate }}%
                    </p>
                    <div class="mt-1 h-1 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                        <div
                            class="h-full bg-sky-500"
                            :style="{ width: `${props.kpis.utilization_rate}%` }"
                        ></div>
                    </div>
                    <p class="mt-1.5 text-[10px] text-slate-400">
                        {{ props.kpis.on_road }} of {{ props.kpis.total_fleet }} assets deployed
                    </p>
                </div>

                <!-- Active on Road -->
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
                >
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span class="text-[11px] font-bold uppercase tracking-wider">In Service</span>
                        <Car class="h-4 w-4 text-sky-500" />
                    </div>
                    <p class="mt-2 font-mono text-2xl font-black text-sky-600 dark:text-sky-400">
                        {{ props.kpis.on_road }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-400">
                        Active customer journeys
                    </p>
                </div>

                <!-- Ready & Available -->
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
                >
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Available</span>
                        <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                    </div>
                    <p class="mt-2 font-mono text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        {{ props.kpis.available }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-400">
                        PDI inspected & ready
                    </p>
                </div>

                <!-- Pickups Due Today -->
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
                >
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Pickups Due</span>
                        <Send class="h-4 w-4 text-amber-500" />
                    </div>
                    <p class="mt-2 font-mono text-2xl font-black text-amber-600 dark:text-amber-400">
                        {{ props.kpis.pickups_due_count }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-400">
                        Awaiting client handover
                    </p>
                </div>

                <!-- Returns Due Today -->
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs col-span-2 sm:col-span-1 dark:border-white/10 dark:bg-[#0A101D]"
                >
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span class="text-[11px] font-bold uppercase tracking-wider">Returns Due</span>
                        <RotateCcw class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                    </div>
                    <p class="mt-2 font-mono text-2xl font-black text-slate-900 dark:text-white">
                        {{ props.kpis.returns_due_count }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-400">
                        Checking in for inspection
                    </p>
                </div>
            </div>

            <!-- Main Spatial Radar & Telemetry Split Screen (12 cols) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left Section: Interactive Hub Radar & Map (7 cols) -->
                <div class="space-y-4 lg:col-span-7">
                    <div
                        class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
                    >
                        <!-- Radar Top Bar -->
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Radio class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Regional Mobility Radar • Northern Corridor
                                </h2>
                            </div>

                            <!-- Map Legend -->
                            <div class="flex items-center gap-3 text-[10px] font-medium text-slate-500 dark:text-slate-400">
                                <span class="flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Available
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-sky-500"></span> On Road
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span> Prep
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span> Service
                                </span>
                            </div>
                        </div>

                        <!-- Vector Radar Viewport -->
                        <div
                            class="relative aspect-4/3 w-full overflow-hidden rounded-2xl border border-slate-200/90 bg-[#04070D] p-4 shadow-inner dark:border-white/10"
                        >
                            <!-- Polar Grid Rings Background -->
                            <svg class="pointer-events-none absolute inset-0 h-full w-full stroke-white/10" fill="none">
                                <circle cx="50%" cy="50%" r="18%" stroke-dasharray="3 3" />
                                <circle cx="50%" cy="50%" r="34%" stroke-dasharray="3 3" />
                                <circle cx="50%" cy="50%" r="48%" stroke-dasharray="3 3" />
                                <line x1="50%" y1="0%" x2="50%" y2="100%" />
                                <line x1="0%" y1="50%" x2="100%" y2="50%" />
                            </svg>

                            <!-- Rotating Radar Sweep Line -->
                            <div
                                class="pointer-events-none absolute left-1/2 top-1/2 h-[96%] w-[96%] -translate-x-1/2 -translate-y-1/2 rounded-full"
                                :style="{
                                    background: `conic-gradient(from ${radarAngle}deg at 50% 50%, rgba(88,119,157,0.25) 0deg, rgba(88,119,157,0) 60deg, transparent 60deg)`,
                                }"
                            ></div>

                            <!-- Hub Landmarks -->
                            <div
                                v-for="hub in props.hubs"
                                :key="hub.id"
                                class="absolute -translate-x-1/2 -translate-y-1/2 transform text-center transition-opacity"
                                :class="{
                                    'opacity-40':
                                        selectedHubCode !== 'all' &&
                                        selectedHubCode !== hub.code,
                                }"
                                :style="{ left: `${hub.x}%`, top: `${hub.y}%` }"
                            >
                                <div
                                    class="flex items-center gap-1 rounded-lg border border-white/20 bg-slate-900/90 px-2 py-0.5 shadow-lg backdrop-blur-md"
                                >
                                    <MapPin class="h-3 w-3 text-cyan-400" />
                                    <span class="font-mono text-[9px] font-bold text-white">{{ hub.code }}</span>
                                </div>
                                <span class="mt-0.5 block text-[8px] font-semibold text-slate-400">
                                    {{ hub.name.split(' ')[0] }}
                                </span>
                            </div>

                            <!-- Fleet Vehicle GPS Nodes -->
                            <button
                                v-for="car in filteredFleet"
                                :key="car.id"
                                type="button"
                                @click="selectedCarId = car.id"
                                class="group absolute -translate-x-1/2 -translate-y-1/2 transform transition-transform duration-300 hover:scale-125 focus:outline-hidden"
                                :style="{ left: `${car.telemetry.map_x}%`, top: `${car.telemetry.map_y}%` }"
                            >
                                <!-- Ping aura if moving/rented -->
                                <span
                                    v-if="car.telemetry.is_moving"
                                    class="absolute -inset-1.5 animate-ping rounded-full opacity-75"
                                    :class="getStatusDotClass(car.status)"
                                ></span>

                                <!-- Node Dot -->
                                <div
                                    class="relative flex h-5 w-5 items-center justify-center rounded-full border-2 border-white shadow-md transition"
                                    :class="[
                                        getStatusDotClass(car.status),
                                        selectedCarId === car.id ? 'ring-4 ring-cyan-400' : '',
                                    ]"
                                >
                                    <Car class="h-2.5 w-2.5 text-white" />
                                </div>

                                <!-- Tooltip on Hover -->
                                <div
                                    class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md border border-white/20 bg-slate-900/95 px-2 py-1 text-[9px] font-bold text-white shadow-xl group-hover:block"
                                >
                                    {{ car.make }} {{ car.model }} ({{ car.license_plate }})
                                </div>
                            </button>
                        </div>

                        <!-- Radar Status Bar -->
                        <div
                            class="mt-3 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            <span>Click any vehicle node to inspect real-time telemetry.</span>
                            <span class="font-mono text-[10px]">Active Transponders: {{ filteredFleet.length }} Nodes</span>
                        </div>
                    </div>
                </div>

                <!-- Right Section: Real-Time Telemetry Inspector (5 cols) -->
                <div class="space-y-4 lg:col-span-5">
                    <div
                        v-if="selectedCar"
                        class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
                    >
                        <!-- Inspector Header -->
                        <div class="flex items-start justify-between border-b border-slate-100 pb-4 dark:border-white/5">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-700 dark:bg-white/10 dark:text-slate-300"
                                    >
                                        {{ selectedCar.license_plate }}
                                    </span>
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                        :class="getStatusBadgeClass(selectedCar.status)"
                                    >
                                        {{ selectedCar.status_label }}
                                    </span>
                                </div>
                                <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                                    {{ selectedCar.make }} {{ selectedCar.model }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ selectedCar.year }} • {{ selectedCar.color }} • {{ selectedCar.transmission }}
                                </p>
                            </div>

                            <!-- Live Heartbeat Ping -->
                            <div class="text-right">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Transponder</span>
                                <p class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ selectedCar.telemetry.last_ping }}
                                </p>
                            </div>
                        </div>

                        <!-- Telemetry Dials & Readings -->
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <!-- Fuel / Battery Gauge -->
                            <div
                                class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-white/10 dark:bg-white/5"
                            >
                                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                    <span class="text-[10px] font-bold uppercase tracking-wider">
                                        {{ selectedCar.telemetry.is_electric ? 'Battery SoC' : 'Fuel Level' }}
                                    </span>
                                    <Fuel v-if="!selectedCar.telemetry.is_electric" class="h-3.5 w-3.5 text-emerald-500" />
                                    <BatteryCharging v-else class="h-3.5 w-3.5 text-cyan-400" />
                                </div>

                                <div class="mt-2 flex items-baseline gap-2">
                                    <span class="font-mono text-2xl font-black text-slate-900 dark:text-white">
                                        {{ selectedCar.telemetry.fuel_level }}%
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        ~{{ Math.round((selectedCar.telemetry.fuel_level / 100) * 550) }} km range
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="selectedCar.telemetry.fuel_level > 30 ? 'bg-emerald-500' : 'bg-rose-500'"
                                        :style="{ width: `${selectedCar.telemetry.fuel_level}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Speedometer / Transit -->
                            <div
                                class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-white/10 dark:bg-white/5"
                            >
                                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Live Speed</span>
                                    <Gauge class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                </div>

                                <div class="mt-2 flex items-baseline gap-2">
                                    <span class="font-mono text-2xl font-black text-slate-900 dark:text-white">
                                        {{ selectedCar.telemetry.speed_kmh }}
                                    </span>
                                    <span class="font-mono text-[10px] font-bold uppercase text-slate-400">KM/H</span>
                                </div>

                                <span
                                    class="mt-1 inline-block text-[10px] font-semibold"
                                    :class="selectedCar.telemetry.is_moving ? 'text-sky-500' : 'text-slate-400'"
                                >
                                    {{ selectedCar.telemetry.is_moving ? '• In Transit on Road' : '• Stationary at Hub' }}
                                </span>
                            </div>

                            <!-- Odometer -->
                            <div
                                class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-white/10 dark:bg-white/5"
                            >
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Odometer</span>
                                <p class="mt-1 font-mono text-base font-bold text-slate-900 dark:text-white">
                                    {{ selectedCar.mileage.toLocaleString() }} KM
                                </p>
                                <span class="text-[10px] text-slate-400">Verified at check-in</span>
                            </div>

                            <!-- Health & Diagnostics -->
                            <div
                                class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-white/10 dark:bg-white/5"
                            >
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tire Pressure</span>
                                <p class="mt-1 font-mono text-base font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ selectedCar.telemetry.tire_pressure_psi }} PSI • Normal
                                </p>
                                <span class="text-[10px] text-slate-400">TPMS sensors active</span>
                            </div>
                        </div>

                        <!-- Active Hirer if Rented -->
                        <div
                            v-if="selectedCar.active_reservation"
                            class="mt-4 rounded-2xl border border-sky-500/20 bg-sky-50/60 p-3.5 dark:border-sky-500/20 dark:bg-sky-950/20"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400">Active Hirer</span>
                                <span class="font-mono text-[10px] text-slate-500 dark:text-slate-400">#{{ selectedCar.active_reservation.reservation_number }}</span>
                            </div>
                            <p class="mt-1 text-xs font-bold text-slate-900 dark:text-white">
                                {{ selectedCar.active_reservation.user_name }}
                            </p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                License: {{ selectedCar.active_reservation.user_license }}
                            </p>
                        </div>

                        <!-- Quick Status Switcher Actions -->
                        <div class="mt-4 border-t border-slate-100 pt-4 dark:border-white/5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Operational Status Override
                            </span>

                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="quickChangeStatus(selectedCar.id, 'available')"
                                    class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-2 py-1.5 text-center text-xs font-bold text-emerald-600 transition hover:bg-emerald-500/20 dark:text-emerald-400"
                                >
                                    Available
                                </button>
                                <button
                                    type="button"
                                    @click="quickChangeStatus(selectedCar.id, 'cleaning')"
                                    class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-2 py-1.5 text-center text-xs font-bold text-amber-600 transition hover:bg-amber-500/20 dark:text-amber-400"
                                >
                                    Cleaning
                                </button>
                                <button
                                    type="button"
                                    @click="quickChangeStatus(selectedCar.id, 'maintenance')"
                                    class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-2 py-1.5 text-center text-xs font-bold text-rose-600 transition hover:bg-rose-500/20 dark:text-rose-400"
                                >
                                    Service
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Handover Queue (Pickups & Returns) -->
            <div
                class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
            >
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/5"
                >
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                            Operational Handover & Check-in Queue
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Execute instant customer dispatches and drop-off returns.
                        </p>
                    </div>

                    <!-- Queue Tabs -->
                    <div
                        class="flex items-center gap-1 rounded-xl border border-slate-200/80 bg-slate-50 p-1 dark:border-white/10 dark:bg-white/5"
                    >
                        <button
                            type="button"
                            @click="activeQueueTab = 'pickups'"
                            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition"
                            :class="
                                activeQueueTab === 'pickups'
                                    ? 'bg-white text-slate-900 shadow-xs dark:bg-white/10 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400'
                            "
                        >
                            <Send class="h-3 w-3 text-emerald-500" />
                            <span>Pickups Due</span>
                            <span
                                class="rounded-full bg-emerald-500/15 px-1.5 py-0.2 font-mono text-[10px] text-emerald-600 dark:text-emerald-400"
                            >
                                {{ props.pickupsDue.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="activeQueueTab = 'returns'"
                            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition"
                            :class="
                                activeQueueTab === 'returns'
                                    ? 'bg-white text-slate-900 shadow-xs dark:bg-white/10 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400'
                            "
                        >
                            <RotateCcw class="h-3 w-3 text-[#58779D] dark:text-[#7094BF]" />
                            <span>Returns Due</span>
                            <span
                                class="rounded-full bg-[#58779D]/15 px-1.5 py-0.2 font-mono text-[10px] text-[#58779D] dark:text-[#7094BF]"
                            >
                                {{ props.returnsDue.length }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Tab 1: Pickups Due -->
                <div v-if="activeQueueTab === 'pickups'" class="mt-4">
                    <div
                        v-if="props.pickupsDue.length === 0"
                        class="py-8 text-center text-xs text-slate-400"
                    >
                        No pending customer pickups awaiting handover today.
                    </div>

                    <div v-else class="divide-y divide-slate-100 dark:divide-white/5">
                        <div
                            v-for="res in props.pickupsDue"
                            :key="res.id"
                            class="flex flex-col gap-3 py-3.5 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                                >
                                    <Car class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                                            {{ res.car?.make }} {{ res.car?.model }}
                                        </span>
                                        <span class="font-mono text-[10px] text-slate-400">
                                            ({{ res.car?.license_plate }})
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs text-slate-600 dark:text-slate-300">
                                            Hirer: <strong class="text-slate-900 dark:text-white">{{ res.user?.name }}</strong>
                                        </p>
                                        <span
                                            v-if="res.user?.loyalty_tier === 'platinum' || res.user?.loyalty_tier === 'gold'"
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.2 text-[9px] font-bold uppercase tracking-wider"
                                            :class="
                                                res.user?.loyalty_tier === 'platinum'
                                                    ? 'bg-slate-100 text-slate-800 border border-slate-300 dark:bg-white/10 dark:text-white dark:border-white/20'
                                                    : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                            "
                                        >
                                            <Crown class="h-2.5 w-2.5" />
                                            <span>{{ res.user?.loyalty_tier === 'platinum' ? 'Platinum VIP' : 'Gold VIP' }}</span>
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">
                                        Scheduled from {{ res.start_date }} ({{ res.total_days }} days) • Ref: <span class="font-mono">{{ res.reservation_number }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <Link
                                    :href="`/booking/${res.id}/agreement`"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                                >
                                    <FileCheck2 class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span>Voucher</span>
                                </Link>

                                <button
                                    type="button"
                                    @click="dispatchReservation(res)"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#101D35] px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-[#1a2d52] dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                                >
                                    <Send class="h-3.5 w-3.5" />
                                    <span>Dispatch & Handover</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Returns Due -->
                <div v-if="activeQueueTab === 'returns'" class="mt-4">
                    <div
                        v-if="props.returnsDue.length === 0"
                        class="py-8 text-center text-xs text-slate-400"
                    >
                        No active vehicles currently due for check-in return.
                    </div>

                    <div v-else class="divide-y divide-slate-100 dark:divide-white/5">
                        <div
                            v-for="res in props.returnsDue"
                            :key="res.id"
                            class="flex flex-col gap-3 py-3.5 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400"
                                >
                                    <Activity class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                                            {{ res.car?.make }} {{ res.car?.model }}
                                        </span>
                                        <span class="font-mono text-[10px] text-slate-400">
                                            ({{ res.car?.license_plate }})
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs text-slate-600 dark:text-slate-300">
                                            Active Hirer: <strong class="text-slate-900 dark:text-white">{{ res.user?.name }}</strong>
                                        </p>
                                        <span
                                            v-if="res.user?.loyalty_tier === 'platinum' || res.user?.loyalty_tier === 'gold'"
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.2 text-[9px] font-bold uppercase tracking-wider"
                                            :class="
                                                res.user?.loyalty_tier === 'platinum'
                                                    ? 'bg-slate-100 text-slate-800 border border-slate-300 dark:bg-white/10 dark:text-white dark:border-white/20'
                                                    : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                            "
                                        >
                                            <Crown class="h-2.5 w-2.5" />
                                            <span>{{ res.user?.loyalty_tier === 'platinum' ? 'Platinum VIP' : 'Gold VIP' }}</span>
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">
                                        Scheduled Return: {{ res.end_date }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openCheckinModal(res)"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#101D35] px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-[#1a2d52] dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                                >
                                    <RotateCcw class="h-3.5 w-3.5" />
                                    <span>Process Check-in</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Fleet Inventory & Telemetry Table -->
            <div
                class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-white/10 dark:bg-[#0A101D]"
            >
                <div class="flex flex-col gap-4 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/5">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                            Fleet Inventory & Transponder Telemetry
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Active registry of all registered vehicles, fuel/battery levels, and hub locations.
                        </p>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <Search class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="fleetSearchQuery"
                            type="text"
                            placeholder="Search make, model, plate..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 transition focus:border-slate-400 focus:bg-white focus:outline-hidden dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-white/30"
                        />
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 font-semibold uppercase tracking-wider text-slate-400 dark:border-white/5">
                            <tr>
                                <th class="pb-3 pr-4">Vehicle</th>
                                <th class="pb-3 pr-4">Hub Station</th>
                                <th class="pb-3 pr-4">Status</th>
                                <th class="pb-3 pr-4">SoC / Fuel</th>
                                <th class="pb-3 pr-4">Odometer</th>
                                <th class="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            <tr
                                v-for="car in filteredFleet"
                                :key="car.id"
                                class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]"
                                :class="{ 'bg-slate-50/80 dark:bg-white/[0.03]': selectedCarId === car.id }"
                            >
                                <td class="py-3 pr-4">
                                    <button
                                        type="button"
                                        @click="selectedCarId = car.id"
                                        class="flex items-center gap-2.5 text-left focus:outline-hidden"
                                    >
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-white/5"
                                        >
                                            <Car class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white">
                                                {{ car.make }} {{ car.model }}
                                            </p>
                                            <p class="font-mono text-[10px] text-slate-400">
                                                {{ car.license_plate }} • {{ car.year }}
                                            </p>
                                        </div>
                                    </button>
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        <MapPin class="h-3 w-3 text-cyan-500" />
                                        {{ car.telemetry.hub_code }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                        :class="getStatusBadgeClass(car.status)"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="getStatusDotClass(car.status)"></span>
                                        {{ car.status_label }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4 font-mono font-bold text-slate-700 dark:text-slate-300">
                                    <div class="flex items-center gap-2">
                                        <span>{{ car.telemetry.fuel_level }}%</span>
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                                            <div
                                                class="h-full rounded-full"
                                                :class="car.telemetry.fuel_level > 30 ? 'bg-emerald-500' : 'bg-rose-500'"
                                                :style="{ width: `${car.telemetry.fuel_level}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 pr-4 font-mono text-slate-600 dark:text-slate-400">
                                    {{ car.mileage.toLocaleString() }} KM
                                </td>
                                <td class="py-3 text-right">
                                    <button
                                        type="button"
                                        @click="selectedCarId = car.id"
                                        class="rounded-lg border border-slate-200/80 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                                    >
                                        Inspect
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Checkin Return Modal -->
        <FleetCheckinModal
            :is-open="isCheckinModalOpen"
            :reservation="activeReturnReservation"
            @close="isCheckinModalOpen = false"
            @success="isCheckinModalOpen = false"
        />
    </AdminLayout>
</template>
