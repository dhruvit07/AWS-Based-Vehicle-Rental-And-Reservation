<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ref, watch, computed } from 'vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { AlertCircle, CarFront, Search, Plus, SlidersHorizontal, CalendarDays, Pencil, Trash2 } from 'lucide-vue-next';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { create, index, destroy } from '@/routes/admin/cars';

const props = defineProps<{
    cars: {
        data: Array<{
            id: number;
            make: string;
            model: string;
            year: number;
            license_plate: string;
            price_per_day: string | number;
            status: string;
            status_label?: string;
            status_color?: string;
            image_url?: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        status?: string;
    };
    statuses: Record<string, {
        label: string;
        count: number;
        color: string;
    }>;
    currency: { symbol: string; code: string };
}>();

const statusColors = computed(() => {
    const colors: Record<string, { bg: string; text: string; dot: string }> = {};

    for (const [status, data] of Object.entries(props.statuses || {})) {
        const hex = data.color.replace('#', '');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);

        colors[status] = {
            bg: `rgba(${r}, ${g}, ${b}, 0.12)`,
            text: data.color,
            dot: data.color,
        };
    }

    return colors;
});

const getStatusColor = (status: string) =>
    statusColors.value[status] || {
        bg: 'rgba(148, 163, 184, 0.12)',
        text: '#64748B',
        dot: '#64748B',
    };

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');

function doSearch() {
    router.get(index(), {
        search: search.value,
        status: statusFilter.value === 'all' ? null : statusFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

watch(search, (value, oldValue) => {
    if (value === '' && oldValue !== '') doSearch();
});

const showDeleteDialog = ref(false);
const carToDelete = ref<number | null>(null);

const openDeleteDialog = (id: number) => {
    carToDelete.value = id;
    showDeleteDialog.value = true;
};

const destroyCar = () => {
    if (!carToDelete.value) return;

    router.delete(destroy(carToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            carToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Manage Cars" />

    <AdminLayout>
        <main class="min-h-full space-y-7 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <section class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Manage Cars
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        View, search and manage your vehicle fleet inventory.
                    </p>
                </div>

                <Link :href="create()" class="shrink-0">
                    <Button class="apple-btn-primary h-11 px-5 text-sm font-medium">
                        <Plus class="mr-2 h-4 w-4" />
                        Add New Car
                    </Button>
                </Link>
            </section>

            <!-- Summary cards -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total vehicles</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <CarFront class="h-5 w-5" />
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ Object.values(statuses).reduce((total, item) => total + item.count, 0) }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Vehicles across all operational statuses</p>
                </div>

                <div class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Current results</p>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <SlidersHorizontal class="h-5 w-5" />
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ cars.data.length }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Vehicles displayed on this page</p>
                </div>
            </section>

            <!-- Search and filters -->
            <section class="glass-card p-5 sm:p-6">
                <div class="mb-4 flex items-center gap-2">
                    <Search class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                    <h2 class="font-semibold text-slate-900 dark:text-white">Find a vehicle</h2>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            v-model="search"
                            placeholder="Search make, model or license plate..."
                            class="apple-input pl-10"
                            @keyup.enter="doSearch"
                        />
                    </div>
                    <Button
                        @click="doSearch"
                        class="apple-btn-primary h-11 px-6 text-sm font-medium"
                    >
                        Search
                    </Button>
                </div>

                <div class="mt-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Filter by status
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="statusFilter = 'all'; doSearch()"
                            class="rounded-xl border px-3.5 py-2 text-xs font-medium transition cursor-pointer"
                            :class="statusFilter === 'all'
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-white/10'"
                        >
                            All ({{ Object.values(statuses).reduce((total, item) => total + item.count, 0) }})
                        </button>

                        <button
                            v-for="(status, key) in statuses"
                            :key="key"
                            type="button"
                            @click="statusFilter = key; doSearch()"
                            class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-medium transition cursor-pointer"
                            :class="statusFilter === key
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-white/10'"
                        >
                            <span
                                class="h-2 w-2 rounded-full"
                                :style="{ backgroundColor: status.color }"
                            ></span>
                            {{ status.label }} ({{ status.count }})
                        </button>
                    </div>
                </div>
            </section>

            <!-- Vehicle table -->
            <section class="glass-card overflow-hidden">
                <div class="flex flex-col justify-between gap-2 border-b border-black/5 dark:border-white/10 px-6 py-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Vehicle Inventory</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Showing {{ cars.data.length }} vehicles on this page
                        </p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-400">
                        <CalendarDays class="h-3.5 w-3.5" />
                        Latest vehicles first
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[850px] w-full text-left">
                        <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-6 py-3.5">Vehicle</th>
                                <th class="px-6 py-3.5">License plate</th>
                                <th class="px-6 py-3.5">Price per day</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/5 dark:divide-white/5 text-sm">
                            <tr
                                v-for="car in cars.data"
                                :key="car.id"
                                class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-14 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5">
                                            <img
                                                v-if="car.image_url"
                                                :src="car.image_url"
                                                :alt="`${car.make} ${car.model}`"
                                                class="h-full w-full object-cover"
                                            />
                                            <CarFront v-else class="h-6 w-6 text-[#58779D] dark:text-[#7094BF]" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                {{ car.make }} {{ car.model }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ car.year }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-1 font-mono text-xs font-medium text-slate-700 dark:text-slate-300">
                                        {{ car.license_plate }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">
                                        {{ currency.symbol }}{{ Number(car.price_per_day).toFixed(2) }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ currency.code }} / day</p>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :style="{
                                            backgroundColor: getStatusColor(car.status).bg,
                                            color: getStatusColor(car.status).text,
                                        }"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :style="{ backgroundColor: getStatusColor(car.status).dot }"
                                        ></span>
                                        {{ car.status_label || statuses[car.status]?.label || car.status }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/admin/cars/${car.id}/edit`">
                                            <Button variant="outline" size="sm" class="h-8 rounded-lg border-black/10 dark:border-white/10 text-xs font-medium hover:bg-slate-100 dark:hover:bg-white/10">
                                                <Pencil class="mr-1 h-3 w-3" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-8 rounded-lg border-rose-500/20 text-xs font-medium text-rose-600 hover:bg-rose-500/10 dark:text-rose-400"
                                            @click="openDeleteDialog(car.id)"
                                        >
                                            <Trash2 class="mr-1 h-3 w-3" />
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="cars.data.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <CarFront class="mx-auto h-10 w-10 text-slate-400" />
                                    <p class="mt-3 font-semibold text-slate-900 dark:text-white">No vehicles found</p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try another search or status filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="cars.links?.length"
                    aria-label="Pagination"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-black/5 dark:border-white/10 px-4 py-4"
                >
                    <template v-for="(link, i) in cars.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                            :class="link.active
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10'"
                        >
                            <span v-html="link.label"></span>
                        </Link>
                        <span
                            v-else
                            class="pointer-events-none rounded-lg border border-black/5 dark:border-white/5 px-3 py-1.5 text-xs text-slate-400 opacity-60"
                        >
                            <span v-html="link.label"></span>
                        </span>
                    </template>
                </nav>
            </section>
        </main>

        <!-- Delete confirmation -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent class="glass-card rounded-2xl sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-slate-900 dark:text-white">
                        <AlertCircle class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                        Delete vehicle?
                    </DialogTitle>
                    <DialogDescription class="text-slate-600 dark:text-slate-400">
                        Are you sure you want to delete this vehicle? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <Alert variant="destructive" class="mt-2">
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>
                        The server may restrict this action in the current demo version.
                    </AlertDescription>
                </Alert>

                <DialogFooter class="mt-4 gap-2">
                    <DialogClose as-child>
                        <Button variant="outline" class="rounded-xl border-black/10 dark:border-white/10">Cancel</Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="destroyCar"
                        :disabled="!carToDelete"
                        class="rounded-xl"
                    >
                        Confirm delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
