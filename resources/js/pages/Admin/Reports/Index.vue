<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import {
    ArrowDown,
    ArrowUp,
    Car as CarIcon,
    Calendar,
    DollarSign,
    Eye,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(...registerables);

interface KPI {
    value: number;
    formatted: string;
    label: string;
}

interface CarState {
    value: number;
    formatted: string;
    label: string;
    color: string;
}

interface ChartData {
    labels: string[];
    datasets: Array<{
        label: string;
        data: number[];
        backgroundColor: string;
        borderColor: string;
        borderWidth: number;
    }>;
    dailyTotals: number[];
    statusColors: Record<string, string>;
    statusLabels: Record<string, string>;
    dateRange: { start: string; end: string };
}

interface CarPerformance {
    id: number;
    car_name: string;
    license_plate: string;
    status: string;
    status_color: string;
    total_reservations: number;
    total_revenue: number;
    formatted_revenue: string;
    total_days: number;
    utilization_rate: number;
    average_per_reservation: number;
}

interface PeriodOption {
    value: string;
    label: string;
}

interface PageProps {
    kpis: {
        totalRevenue: KPI;
        platformVisits: KPI;
        activeReservations: KPI;
        newClients: KPI;
    };
    carsState: {
        totalCars: CarState;
        availableCars: CarState;
        rentedCars: CarState;
        unavailableCars: CarState;
    };
    reservationsChart: ChartData;
    carsPerformance: CarPerformance[];
    currentPeriod: string;
    periodOptions: PeriodOption[];
}

const page = usePage<PageProps>();
const selectedPeriod = ref(page.props.currentPeriod);
const reservationChart = ref<Chart | null>(null);
const chartCanvas = ref<HTMLCanvasElement | null>(null);
let themeObserver: MutationObserver | null = null;

const sortField = ref<keyof CarPerformance>('total_revenue');
const sortDirection = ref<'asc' | 'desc'>('desc');

const kpis = computed(() => page.props.kpis);
const carsState = computed(() => page.props.carsState);
const chartData = computed(() => page.props.reservationsChart);
const periodOptions = computed(() => page.props.periodOptions);

const sortedCarsPerformance = computed(() => {
    const sorted = [...(page.props.carsPerformance ?? [])].sort((a, b) => {
        const aValue = a[sortField.value];
        const bValue = b[sortField.value];

        if (typeof aValue === 'string' && typeof bValue === 'string') {
            return sortDirection.value === 'asc'
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        }

        const numA = Number(aValue);
        const numB = Number(bValue);

        return sortDirection.value === 'asc'
            ? numA - numB
            : numB - numA;
    });

    return sorted.slice(0, 10);
});

const handlePeriodChange = () => {
    router.get(
        '/admin/reports',
        { period: selectedPeriod.value },
        {
            preserveState: false,
            preserveScroll: false,
            only: [
                'kpis',
                'carsState',
                'reservationsChart',
                'carsPerformance',
                'currentPeriod',
            ],
        },
    );
};

const sortTable = (field: keyof CarPerformance) => {
    if (sortField.value === field) {
        sortDirection.value =
            sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'desc';
    }
};

const createReservationsChart = () => {
    if (!chartCanvas.value || !chartData.value) return;

    reservationChart.value?.destroy();

    const ctx = chartCanvas.value.getContext('2d');
    if (!ctx) return;

    const isDark = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
    const legendColor = isDark ? '#E2E8F0' : '#1E293B';
    const tickColor = isDark ? '#94A3B8' : '#64748B';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(88, 119, 157, 0.12)';
    const tooltipBg = isDark ? '#0F1724' : '#101D35';

    reservationChart.value = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.value.labels,
            datasets: chartData.value.datasets.map((dataset) => ({
                ...dataset,
                borderRadius: 6,
                borderSkipped: false,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: legendColor,
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'rectRounded',
                        font: { family: 'inherit', size: 12, weight: 'bold' },
                    },
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: tooltipBg,
                    titleColor: '#FFFFFF',
                    bodyColor: '#E2E8F0',
                    borderColor: isDark ? 'rgba(255,255,255,0.15)' : '#58779D',
                    borderWidth: 1,
                    cornerRadius: 10,
                    padding: 12,
                    callbacks: {
                        title: (items) => `Date: ${items[0].label}`,
                        afterBody: (items) => {
                            const total =
                                chartData.value.dailyTotals[
                                    items[0].dataIndex
                                ];
                            return ['', `Total Reservations: ${total}`];
                        },
                        label: (context) =>
                            `${context.dataset.label || ''}: ${context.parsed.y}`,
                    },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        color: tickColor,
                        maxRotation: 45,
                        minRotation: 0,
                        font: { size: 11 },
                    },
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        color: tickColor,
                        stepSize: 1,
                        callback: (value) =>
                            Number.isInteger(Number(value)) ? value : '',
                    },
                    title: {
                        display: true,
                        text: 'Number of Reservations',
                        color: legendColor,
                        font: { size: 12, weight: 'bold' },
                    },
                    grid: { color: gridColor },
                },
            },
            interaction: { intersect: false, mode: 'index' },
            animation: { duration: 600 },
        },
    });
};

watch(
    () => [chartData.value, selectedPeriod.value],
    createReservationsChart,
    { deep: true },
);

watch(
    () => page.props.currentPeriod,
    (newPeriod) => {
        selectedPeriod.value = newPeriod;
    },
);

onMounted(() => {
    createReservationsChart();

    if (typeof document !== 'undefined') {
        themeObserver = new MutationObserver(() => {
            createReservationsChart();
        });
        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }
});

onBeforeUnmount(() => {
    if (themeObserver) {
        themeObserver.disconnect();
        themeObserver = null;
    }
    reservationChart.value?.destroy();
    reservationChart.value = null;
});
</script>

<template>
    <AdminLayout>
        <div class="min-h-full space-y-7 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <section class="glass-card flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Reports & Analytics
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Operational intelligence, revenue streams, and fleet utilization metrics.
                    </p>
                </div>

                <div class="flex flex-col gap-1.5 sm:min-w-56">
                    <label for="period" class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                        Reporting Period
                    </label>
                    <select
                        id="period"
                        v-model="selectedPeriod"
                        @change="handlePeriodChange"
                        class="apple-input cursor-pointer py-2.5 text-sm font-medium"
                    >
                        <option v-for="option in periodOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>
            </section>

            <!-- Main KPIs -->
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Business Summary</h2>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Current reporting cycle</span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="glass-card p-5 transition duration-200 hover:-translate-y-0.5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                    {{ kpis.totalRevenue.label }}
                                </p>
                                <p class="mt-2.5 break-words text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    {{ kpis.totalRevenue.formatted }}
                                </p>
                            </div>
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                                <DollarSign class="h-5 w-5" />
                            </span>
                        </div>
                        <div class="mt-4 h-1 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div class="h-full w-3/4 rounded-full bg-[#58779D] dark:bg-[#7094BF]"></div>
                        </div>
                    </article>

                    <article class="glass-card p-5 transition duration-200 hover:-translate-y-0.5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                    {{ kpis.platformVisits.label }}
                                </p>
                                <p class="mt-2.5 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    {{ kpis.platformVisits.formatted }}
                                </p>
                            </div>
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                                <Eye class="h-5 w-5" />
                            </span>
                        </div>
                        <div class="mt-4 h-1 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div class="h-full w-2/3 rounded-full bg-[#58779D] dark:bg-[#7094BF]"></div>
                        </div>
                    </article>

                    <article class="glass-card p-5 transition duration-200 hover:-translate-y-0.5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                    {{ kpis.activeReservations.label }}
                                </p>
                                <p class="mt-2.5 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    {{ kpis.activeReservations.formatted }}
                                </p>
                            </div>
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                                <Calendar class="h-5 w-5" />
                            </span>
                        </div>
                        <div class="mt-4 h-1 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div class="h-full w-1/2 rounded-full bg-[#58779D] dark:bg-[#7094BF]"></div>
                        </div>
                    </article>

                    <article class="glass-card p-5 transition duration-200 hover:-translate-y-0.5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                    {{ kpis.newClients.label }}
                                </p>
                                <p class="mt-2.5 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    {{ kpis.newClients.formatted }}
                                </p>
                            </div>
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                                <Users class="h-5 w-5" />
                            </span>
                        </div>
                        <div class="mt-4 h-1 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div class="h-full w-4/5 rounded-full bg-[#58779D] dark:bg-[#7094BF]"></div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Fleet status -->
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Fleet Overview</h2>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Live operational readiness</span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article v-for="(car, key) in carsState" :key="key" class="glass-card flex items-center gap-4 p-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-white/10 text-slate-800 dark:text-slate-200">
                            <CarIcon class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ car.label }}</p>
                            <p class="mt-0.5 text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ car.formatted }}</p>
                            <span class="mt-1.5 inline-flex rounded-full bg-slate-100 dark:bg-white/10 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300">
                                Live status
                            </span>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Reservations chart -->
            <section class="glass-card overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-black/5 dark:border-white/10 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Daily Reservations</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Booking frequency across the selected timeline</p>
                    </div>
                    <span class="inline-flex w-fit rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300">
                        {{ chartData.dateRange.start }} – {{ chartData.dateRange.end }}
                    </span>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="relative h-72 sm:h-96">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                </div>
            </section>

            <!-- Car performance -->
            <section class="glass-card overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-black/5 dark:border-white/10 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Top 10 Vehicle Performance</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Compare bookings, revenue generation, and utilization rates.</p>
                    </div>
                    <span class="w-fit rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400">
                        Click column header to sort
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[900px] w-full text-left">
                        <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <tr>
                                <th v-for="column in [
                                    { key: 'car_name', label: 'Vehicle' },
                                    { key: 'status', label: 'Status' },
                                    { key: 'total_reservations', label: 'Reservations' },
                                    { key: 'total_revenue', label: 'Revenue' },
                                    { key: 'utilization_rate', label: 'Utilization' },
                                    { key: 'average_per_reservation', label: 'Avg. / Reservation' },
                                ]" :key="column.key" class="px-6 py-3.5">
                                    <button
                                        type="button"
                                        class="flex items-center gap-1.5 text-left font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition"
                                        @click="sortTable(column.key as keyof CarPerformance)"
                                    >
                                        {{ column.label }}
                                        <ArrowUp v-if="sortField === column.key && sortDirection === 'asc'" class="h-3.5 w-3.5 text-[#58779D]" />
                                        <ArrowDown v-else-if="sortField === column.key && sortDirection === 'desc'" class="h-3.5 w-3.5 text-[#58779D]" />
                                    </button>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/5 dark:divide-white/5 text-sm">
                            <tr v-for="car in sortedCarsPerformance" :key="car.id" class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ car.car_name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ car.license_plate }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold text-white"
                                        :style="{ backgroundColor: car.status_color }"
                                    >
                                        {{ car.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-300">
                                    {{ car.total_reservations }}
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ car.formatted_revenue }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex min-w-32 items-center gap-3">
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                            <div
                                                class="h-full rounded-full bg-[#58779D] dark:bg-[#7094BF]"
                                                :style="{ width: `${Math.min(Math.max(car.utilization_rate, 0), 100)}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            {{ car.utilization_rate }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-300">
                                    {{ car.average_per_reservation > 0 ? `$${car.average_per_reservation.toFixed(2)}` : '$0.00' }}
                                </td>
                            </tr>

                            <tr v-if="sortedCarsPerformance.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <p class="font-semibold text-slate-900 dark:text-white">No vehicle performance data</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try selecting a different reporting period.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-black/5 dark:border-white/10 bg-slate-50/50 dark:bg-white/[0.02] px-6 py-3 text-xs text-slate-500 dark:text-slate-400">
                    Showing top vehicles by revenue · Click a column header to reorder.
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
