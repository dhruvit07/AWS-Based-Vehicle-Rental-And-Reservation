<script setup lang="ts">
import { computed, ref } from 'vue';
import { ChevronLeft, ChevronRight, RotateCcw } from 'lucide-vue-next';

export interface BookedRange {
    from: string;
    to: string;
}

const props = withDefaults(
    defineProps<{
        startDate?: string;
        endDate?: string;
        bookedRanges?: BookedRange[];
        minDate?: string;
    }>(),
    {
        startDate: '',
        endDate: '',
        bookedRanges: () => [],
        minDate: '',
    }
);

const emit = defineEmits<{
    (e: 'update:startDate', val: string): void;
    (e: 'update:endDate', val: string): void;
    (e: 'change', range: { startDate: string; endDate: string }): void;
}>();

// Helper: pad to 2 digits
const pad2 = (n: number): string => String(n).padStart(2, '0');

// Helper: format YYYY-MM-DD
const toDateString = (year: number, month: number, day: number): string =>
    `${year}-${pad2(month)}-${pad2(day)}`;

// Get today's local date string
const today = new Date();
const todayString = toDateString(
    today.getFullYear(),
    today.getMonth() + 1,
    today.getDate()
);

const effectiveMinDate = computed(() => props.minDate || todayString);

// Calendar navigation: base month (month 1)
const baseYear = ref(today.getFullYear());
const baseMonth = ref(today.getMonth() + 1); // 1 - 12

// Hovered date for range preview
const hoveredDate = ref<string | null>(null);

// Month names and Day names
const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const dayHeaders = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

// Navigation controls
const canGoPrev = computed(() => {
    const todayYear = today.getFullYear();
    const todayMonth = today.getMonth() + 1;
    if (baseYear.value > todayYear) return true;
    return baseMonth.value > todayMonth;
});

const prevMonth = () => {
    if (!canGoPrev.value) return;
    if (baseMonth.value === 1) {
        baseMonth.value = 12;
        baseYear.value -= 1;
    } else {
        baseMonth.value -= 1;
    }
};

const nextMonth = () => {
    if (baseMonth.value === 12) {
        baseMonth.value = 1;
        baseYear.value += 1;
    } else {
        baseMonth.value += 1;
    }
};

// Next month year and month numbers
const nextMonthNumber = computed(() => (baseMonth.value === 12 ? 1 : baseMonth.value + 1));
const nextMonthYear = computed(() => (baseMonth.value === 12 ? baseYear.value + 1 : baseYear.value));

interface DayCell {
    date: string;
    dayNumber: number;
    isCurrentMonth: boolean;
    isPast: boolean;
    isBooked: boolean;
    isToday: boolean;
}

// Generate calendar cells for a given year & month (1-based)
const generateMonthDays = (year: number, month: number): DayCell[] => {
    const firstDayOfMonth = new Date(year, month - 1, 1);
    // Sunday is 0, convert so Monday is 0: (day + 6) % 7
    const startDayOfWeek = (firstDayOfMonth.getDay() + 6) % 7;

    const daysInMonth = new Date(year, month, 0).getDate();
    const daysInPrevMonth = new Date(year, month - 1, 0).getDate();

    const cells: DayCell[] = [];

    // Leading padding days from previous month
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const d = daysInPrevMonth - i;
        const prevMonthVal = month === 1 ? 12 : month - 1;
        const prevYearVal = month === 1 ? year - 1 : year;
        const dateStr = toDateString(prevYearVal, prevMonthVal, d);
        cells.push({
            date: dateStr,
            dayNumber: d,
            isCurrentMonth: false,
            isPast: true,
            isBooked: false,
            isToday: false,
        });
    }

    // Days in current month
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = toDateString(year, month, d);
        const isPast = dateStr < effectiveMinDate.value;
        const isBooked = isDateBooked(dateStr);
        const isToday = dateStr === todayString;

        cells.push({
            date: dateStr,
            dayNumber: d,
            isCurrentMonth: true,
            isPast,
            isBooked,
            isToday,
        });
    }

    // Trailing padding days to fill 35 or 42 grid slots
    const totalSlots = cells.length <= 35 ? 35 : 42;
    const remainingSlots = totalSlots - cells.length;
    for (let d = 1; d <= remainingSlots; d++) {
        const nextMonthVal = month === 12 ? 1 : month + 1;
        const nextYearVal = month === 12 ? year + 1 : year;
        const dateStr = toDateString(nextYearVal, nextMonthVal, d);
        cells.push({
            date: dateStr,
            dayNumber: d,
            isCurrentMonth: false,
            isPast: false,
            isBooked: false,
            isToday: false,
        });
    }

    return cells;
};

// Check if a specific date falls within any booked range
const isDateBooked = (dateStr: string): boolean => {
    return props.bookedRanges.some((range) => {
        return dateStr >= range.from && dateStr <= range.to;
    });
};

// Check if any date between start and end is booked (inclusive)
const rangeHasBookedDate = (start: string, end: string): boolean => {
    if (!start || !end) return false;
    const [min, max] = start <= end ? [start, end] : [end, start];
    return props.bookedRanges.some((range) => {
        return !(max < range.from || min > range.to);
    });
};

const firstMonthDays = computed(() => generateMonthDays(baseYear.value, baseMonth.value));
const secondMonthDays = computed(() => generateMonthDays(nextMonthYear.value, nextMonthNumber.value));

// Handle date click
const handleDateClick = (cell: DayCell) => {
    if (!cell.isCurrentMonth || cell.isPast || cell.isBooked) return;

    const clicked = cell.date;

    // Case 1: Neither date is selected, or both were already selected -> start fresh
    if (!props.startDate || (props.startDate && props.endDate)) {
        emit('update:startDate', clicked);
        emit('update:endDate', '');
        emit('change', { startDate: clicked, endDate: '' });
        return;
    }

    // Case 2: Start date was picked, but no end date yet
    if (props.startDate && !props.endDate) {
        // If clicking earlier than start date, reset start date
        if (clicked < props.startDate) {
            emit('update:startDate', clicked);
            emit('update:endDate', '');
            emit('change', { startDate: clicked, endDate: '' });
            return;
        }

        // If clicking the same date as start date, keep it as 1-day or wait for end date
        if (clicked === props.startDate) {
            emit('update:endDate', clicked);
            emit('change', { startDate: props.startDate, endDate: clicked });
            return;
        }

        // If clicking a date after start, check if any date in between is booked
        if (rangeHasBookedDate(props.startDate, clicked)) {
            // Cannot cross a booked range: treat clicked date as new start date
            emit('update:startDate', clicked);
            emit('update:endDate', '');
            emit('change', { startDate: clicked, endDate: '' });
            return;
        }

        // Valid end date
        emit('update:endDate', clicked);
        emit('change', { startDate: props.startDate, endDate: clicked });
    }
};

const clearSelection = () => {
    emit('update:startDate', '');
    emit('update:endDate', '');
    emit('change', { startDate: '', endDate: '' });
};

// Styling helpers for day cells
const getCellClasses = (cell: DayCell): string => {
    if (!cell.isCurrentMonth) {
        return 'invisible pointer-events-none';
    }

    const { date, isPast, isBooked } = cell;

    // Disabled states
    if (isPast) {
        return 'text-slate-300 dark:text-slate-700 cursor-not-allowed bg-transparent';
    }

    if (isBooked) {
        return 'text-slate-400 dark:text-slate-600 bg-slate-100/80 dark:bg-white/5 line-through decoration-slate-400 dark:decoration-slate-600 cursor-not-allowed select-none';
    }

    const isStart = date === props.startDate;
    const isEnd = date === props.endDate;
    const isBetween =
        props.startDate &&
        props.endDate &&
        date > props.startDate &&
        date < props.endDate;

    // In hover preview range (when only start date is picked)
    const isHoveredRange =
        props.startDate &&
        !props.endDate &&
        hoveredDate.value &&
        hoveredDate.value > props.startDate &&
        date > props.startDate &&
        date <= hoveredDate.value &&
        !rangeHasBookedDate(props.startDate, hoveredDate.value);

    if (isStart && isEnd) {
        return 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold rounded-lg shadow-xs';
    }

    if (isStart) {
        return props.endDate
            ? 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold rounded-l-lg shadow-xs'
            : 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold rounded-lg shadow-xs';
    }

    if (isEnd) {
        return 'bg-[#101D35] text-white dark:bg-white dark:text-slate-950 font-semibold rounded-r-lg shadow-xs';
    }

    if (isBetween) {
        return 'bg-[#58779D]/15 text-slate-950 dark:bg-[#7094BF]/20 dark:text-white font-medium rounded-none';
    }

    if (isHoveredRange) {
        return 'bg-slate-200/70 text-slate-950 dark:bg-white/10 dark:text-white rounded-none';
    }

    return 'text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/10 font-medium rounded-lg transition-colors cursor-pointer';
};

// Formatted label helper for dates (e.g. "Nov 12, 2026")
const formatDisplayDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-').map(Number);
    const dateObj = new Date(y, m - 1, d);
    return dateObj.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const totalDays = computed(() => {
    if (!props.startDate || !props.endDate) return 0;
    const start = new Date(`${props.startDate}T00:00:00`);
    const end = new Date(`${props.endDate}T00:00:00`);
    const diff = end.getTime() - start.getTime();
    return Math.round(diff / (1000 * 60 * 60 * 24));
});
</script>

<template>
    <div class="glass-card p-5 sm:p-6">
        <!-- Top bar: Header & Month controls -->
        <div class="flex flex-col gap-3 border-b border-slate-200/80 pb-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="text-base font-bold text-slate-950 dark:text-white">
                    Select Rental Duration
                </h4>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    <span v-if="!startDate">Select pickup date to initiate reservation</span>
                    <span v-else-if="startDate && !endDate">Now choose return date</span>
                    <span v-else class="font-semibold text-slate-950 dark:text-white">
                        {{ totalDays }} day{{ totalDays === 1 ? '' : 's' }} selected:
                        {{ formatDisplayDate(startDate) }} – {{ formatDisplayDate(endDate) }}
                    </span>
                </p>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-auto">
                <button
                    v-if="startDate || endDate"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white"
                    @click="clearSelection"
                >
                    <RotateCcw class="h-3 w-3" />
                    <span>Clear</span>
                </button>

                <div class="inline-flex items-center gap-0.5 rounded-lg border border-slate-200/80 bg-slate-50/70 p-0.5 dark:border-white/10 dark:bg-white/5">
                    <button
                        type="button"
                        :disabled="!canGoPrev"
                        aria-label="Previous month"
                        class="flex h-7 w-7 items-center justify-center rounded-md text-slate-700 transition hover:bg-white hover:shadow-xs disabled:cursor-not-allowed disabled:text-slate-300 dark:text-slate-300 dark:hover:bg-white/10 dark:disabled:text-slate-700"
                        @click="prevMonth"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        aria-label="Next month"
                        class="flex h-7 w-7 items-center justify-center rounded-md text-slate-700 transition hover:bg-white hover:shadow-xs dark:text-slate-300 dark:hover:bg-white/10"
                        @click="nextMonth"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Months Grid: 1 on mobile, 2 side-by-side on md+ -->
        <div class="grid grid-cols-1 gap-8 pt-5 md:grid-cols-2">
            <!-- Month 1 -->
            <div class="space-y-3">
                <div class="text-center text-sm font-bold text-slate-950 dark:text-white">
                    {{ monthNames[baseMonth - 1] }} {{ baseYear }}
                </div>

                <div class="grid grid-cols-7 gap-1 text-center">
                    <div
                        v-for="day in dayHeaders"
                        :key="'m1-' + day"
                        class="py-1 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        {{ day }}
                    </div>

                    <button
                        v-for="(cell, idx) in firstMonthDays"
                        :key="'m1-day-' + idx"
                        type="button"
                        :disabled="!cell.isCurrentMonth || cell.isPast || cell.isBooked"
                        :aria-label="cell.isBooked ? `Reserved, ${cell.date}` : cell.date"
                        :title="cell.isBooked ? 'Reserved' : ''"
                        class="relative flex h-9 w-full items-center justify-center text-xs transition-all focus:outline-none focus:ring-2 focus:ring-[#58779D]"
                        :class="getCellClasses(cell)"
                        @click="handleDateClick(cell)"
                        @mouseenter="hoveredDate = cell.date"
                        @mouseleave="hoveredDate = null"
                    >
                        <span v-if="cell.isCurrentMonth">
                            {{ cell.dayNumber }}
                        </span>
                        <span
                            v-if="cell.isToday && !cell.isBooked && cell.date !== startDate && cell.date !== endDate"
                            class="absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-[#58779D] dark:bg-[#7094BF]"
                        />
                    </button>
                </div>
            </div>

            <!-- Month 2 (Desktop / md+) -->
            <div class="hidden space-y-3 md:block md:border-l md:border-slate-200/80 md:pl-8 dark:md:border-white/10">
                <div class="text-center text-sm font-bold text-slate-950 dark:text-white">
                    {{ monthNames[nextMonthNumber - 1] }} {{ nextMonthYear }}
                </div>

                <div class="grid grid-cols-7 gap-1 text-center">
                    <div
                        v-for="day in dayHeaders"
                        :key="'m2-' + day"
                        class="py-1 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        {{ day }}
                    </div>

                    <button
                        v-for="(cell, idx) in secondMonthDays"
                        :key="'m2-day-' + idx"
                        type="button"
                        :disabled="!cell.isCurrentMonth || cell.isPast || cell.isBooked"
                        :aria-label="cell.isBooked ? `Reserved, ${cell.date}` : cell.date"
                        :title="cell.isBooked ? 'Reserved' : ''"
                        class="relative flex h-9 w-full items-center justify-center text-xs transition-all focus:outline-none focus:ring-2 focus:ring-[#58779D]"
                        :class="getCellClasses(cell)"
                        @click="handleDateClick(cell)"
                        @mouseenter="hoveredDate = cell.date"
                        @mouseleave="hoveredDate = null"
                    >
                        <span v-if="cell.isCurrentMonth">
                            {{ cell.dayNumber }}
                        </span>
                        <span
                            v-if="cell.isToday && !cell.isBooked && cell.date !== startDate && cell.date !== endDate"
                            class="absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-[#58779D] dark:bg-[#7094BF]"
                        />
                    </button>
                </div>
            </div>
        </div>

        <!-- Legend & Guidelines -->
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 pt-4 text-xs text-slate-600 dark:border-white/10 dark:text-slate-400">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm bg-[#101D35] dark:bg-white" />
                    <span>Selected</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm border border-[#58779D]/30 bg-[#58779D]/20 dark:border-[#7094BF]/30 dark:bg-[#7094BF]/20" />
                    <span>Duration</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm border border-slate-200 bg-slate-100 dark:border-white/10 dark:bg-white/5" />
                    <span>Available</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="flex h-3 w-3 items-center justify-center rounded-sm bg-slate-100/80 text-[9px] font-bold text-slate-400 line-through dark:bg-white/5 dark:text-slate-600">✕</span>
                    <span>Reserved</span>
                </div>
            </div>

            <span class="hidden text-slate-400 dark:text-slate-500 sm:inline">
                Real-time conflict prevention
            </span>
        </div>
    </div>
</template>
