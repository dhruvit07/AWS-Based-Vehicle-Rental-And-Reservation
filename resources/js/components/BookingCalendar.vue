<script setup lang="ts">
import { computed, ref } from 'vue';

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

// Check if a date string is within any booked range
const isDateBooked = (dateStr: string): boolean => {
    return props.bookedRanges.some((range) => {
        return dateStr >= range.from && dateStr <= range.to;
    });
};

// Check if range contains any booked date
const rangeHasBookedDate = (start: string, end: string): boolean => {
    return props.bookedRanges.some((range) => {
        return !(end < range.from || start > range.to);
    });
};

// Generate month days
const getMonthDays = (year: number, month: number): DayCell[] => {
    const daysInMonth = new Date(year, month, 0).getDate();
    const firstDayOfWeek = new Date(year, month - 1, 1).getDay(); // 0 is Sun
    const leadingBlanks = (firstDayOfWeek + 6) % 7; // Monday-first

    const cells: DayCell[] = [];

    // Leading blanks
    for (let i = 0; i < leadingBlanks; i++) {
        cells.push({
            date: '',
            dayNumber: 0,
            isCurrentMonth: false,
            isPast: false,
            isBooked: false,
            isToday: false,
        });
    }

    // Days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = toDateString(year, month, day);
        const isPast = dateStr < effectiveMinDate.value;
        const isBooked = isDateBooked(dateStr);
        const isToday = dateStr === todayString;

        cells.push({
            date: dateStr,
            dayNumber: day,
            isCurrentMonth: true,
            isPast,
            isBooked,
            isToday,
        });
    }

    return cells;
};

const firstMonthDays = computed(() => getMonthDays(baseYear.value, baseMonth.value));
const secondMonthDays = computed(() => getMonthDays(nextMonthYear.value, nextMonthNumber.value));

// Selection handler
const handleDateClick = (cell: DayCell) => {
    if (!cell.isCurrentMonth || cell.isPast || cell.isBooked) return;

    const clicked = cell.date;

    // Case 1: No start date, or both start & end are already selected
    if (!props.startDate || (props.startDate && props.endDate)) {
        emit('update:startDate', clicked);
        emit('update:endDate', '');
        emit('change', { startDate: clicked, endDate: '' });
        return;
    }

    // Case 2: Start date exists, no end date yet
    if (props.startDate && !props.endDate) {
        // If clicked date is before start date, treat clicked as new start date
        if (clicked < props.startDate) {
            emit('update:startDate', clicked);
            emit('update:endDate', '');
            emit('change', { startDate: clicked, endDate: '' });
            return;
        }

        // If clicked date is the same as start date, keep waiting for end date
        if (clicked === props.startDate) {
            return;
        }

        // Clicked date is after start date: check for booked dates in between
        if (rangeHasBookedDate(props.startDate, clicked)) {
            // Can't span over a booked range; make clicked the new start date
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
        return 'text-slate-300 cursor-not-allowed bg-transparent';
    }

    if (isBooked) {
        return 'text-slate-400 bg-slate-100/70 line-through decoration-slate-400 cursor-not-allowed select-none';
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
        return 'bg-[#101D35] text-white font-semibold rounded-lg shadow-sm';
    }

    if (isStart) {
        return props.endDate
            ? 'bg-[#101D35] text-white font-semibold rounded-l-lg shadow-sm'
            : 'bg-[#101D35] text-white font-semibold rounded-lg shadow-sm';
    }

    if (isEnd) {
        return 'bg-[#101D35] text-white font-semibold rounded-r-lg shadow-sm';
    }

    if (isBetween) {
        return 'bg-[#58779D]/15 text-[#101D35] font-medium rounded-none';
    }

    if (isHoveredRange) {
        return 'bg-slate-200/70 text-[#101D35] rounded-none';
    }

    return 'text-slate-800 hover:bg-slate-100 font-medium rounded-lg transition-colors cursor-pointer';
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
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
        <!-- Top bar: Header & Month controls -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-100">
            <div>
                <h4 class="text-base font-bold text-[#101D35]">
                    Select rental period
                </h4>
                <p class="text-xs text-slate-500 mt-0.5">
                    <span v-if="!startDate">Select your pickup date to begin</span>
                    <span v-else-if="startDate && !endDate">Now select your return date</span>
                    <span v-else class="text-[#101D35] font-semibold">
                        {{ totalDays }} day{{ totalDays === 1 ? '' : 's' }} selected:
                        {{ formatDisplayDate(startDate) }} – {{ formatDisplayDate(endDate) }}
                    </span>
                </p>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-auto">
                <button
                    v-if="startDate || endDate"
                    type="button"
                    class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#58779D]/30"
                    @click="clearSelection"
                >
                    Clear dates
                </button>

                <div class="flex items-center gap-1 border border-slate-200 rounded-lg p-0.5 bg-slate-50">
                    <button
                        type="button"
                        :disabled="!canGoPrev"
                        aria-label="Previous month"
                        class="flex h-8 w-8 items-center justify-center rounded-md text-slate-700 transition hover:bg-white hover:shadow-xs disabled:cursor-not-allowed disabled:text-slate-300 disabled:hover:bg-transparent"
                        @click="prevMonth"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        aria-label="Next month"
                        class="flex h-8 w-8 items-center justify-center rounded-md text-slate-700 transition hover:bg-white hover:shadow-xs"
                        @click="nextMonth"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Months Grid: 1 on mobile, 2 side-by-side on md+ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-5">
            <!-- Month 1 -->
            <div class="space-y-3">
                <div class="text-center font-bold text-[#101D35] text-sm sm:text-base">
                    {{ monthNames[baseMonth - 1] }} {{ baseYear }}
                </div>

                <div class="grid grid-cols-7 gap-1 text-center">
                    <div
                        v-for="day in dayHeaders"
                        :key="'m1-' + day"
                        class="text-xs font-semibold uppercase tracking-wider text-slate-500 py-1"
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
                        class="relative flex h-10 w-full items-center justify-center text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#58779D]"
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
                            class="absolute bottom-1 left-1/2 -translate-x-1/2 h-1 w-1 rounded-full bg-[#58779D]"
                        />
                    </button>
                </div>
            </div>

            <!-- Month 2 (Desktop / md+) -->
            <div class="hidden md:block space-y-3 md:border-l md:border-slate-100 md:pl-8">
                <div class="text-center font-bold text-[#101D35] text-sm sm:text-base">
                    {{ monthNames[nextMonthNumber - 1] }} {{ nextMonthYear }}
                </div>

                <div class="grid grid-cols-7 gap-1 text-center">
                    <div
                        v-for="day in dayHeaders"
                        :key="'m2-' + day"
                        class="text-xs font-semibold uppercase tracking-wider text-slate-500 py-1"
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
                        class="relative flex h-10 w-full items-center justify-center text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#58779D]"
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
                            class="absolute bottom-1 left-1/2 -translate-x-1/2 h-1 w-1 rounded-full bg-[#58779D]"
                        />
                    </button>
                </div>
            </div>
        </div>

        <!-- Legend & Guidelines -->
        <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm bg-[#101D35]" />
                    <span>Selected</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm bg-[#58779D]/20 border border-[#58779D]/30" />
                    <span>Rental duration</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm bg-slate-100 border border-slate-200" />
                    <span>Available</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm bg-slate-100 text-slate-400 line-through text-[10px] flex items-center justify-center font-bold">✕</span>
                    <span>Reserved</span>
                </div>
            </div>

            <span class="text-slate-400 hidden sm:inline">
                Calendar days are reserved exclusively
            </span>
        </div>
    </div>
</template>
