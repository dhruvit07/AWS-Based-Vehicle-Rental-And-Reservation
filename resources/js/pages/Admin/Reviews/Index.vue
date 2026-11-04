<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import StarRating from '@/components/StarRating.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    Car,
    CheckCircle2,
    MessageSquare,
    Search,
    ShieldCheck,
    Star,
    Trash2,
    X,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface ReviewItem {
    id: number;
    rating: number;
    title: string | null;
    comment: string;
    is_published: boolean;
    created_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
    car?: {
        id: number;
        make: string;
        model: string;
        year: number;
        license_plate: string;
    } | null;
    reservation?: {
        id: number;
        reservation_number: string;
    } | null;
}

const props = defineProps<{
    reviews: {
        data: ReviewItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        rating?: string | number;
    };
    stats: {
        total: number;
        average: number;
        five_star: number;
        four_star: number;
        three_star_below: number;
    };
}>();

const search = ref(props.filters?.search || '');
const ratingFilter = ref(props.filters?.rating || '');
const isDeleting = ref<number | null>(null);
const deleteTarget = ref<ReviewItem | null>(null);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    const params: Record<string, string> = {};
    if (search.value.trim()) params.search = search.value.trim();
    if (ratingFilter.value) params.rating = String(ratingFilter.value);

    router.get('/admin/reviews', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

watch(ratingFilter, () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    ratingFilter.value = '';
    router.get('/admin/reviews', {}, { preserveState: true, preserveScroll: true });
};

const confirmDelete = (review: ReviewItem) => {
    deleteTarget.value = review;
};

const executeDelete = () => {
    if (!deleteTarget.value) return;

    const id = deleteTarget.value.id;
    isDeleting.value = id;

    router.delete(`/admin/reviews/${id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = null;
            deleteTarget.value = null;
        },
    });
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '—';
    const parsed = new Date(dateStr);
    return Number.isNaN(parsed.getTime())
        ? dateStr
        : new Intl.DateTimeFormat('en-US', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(parsed);
};

const getInitials = (name?: string) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
};
</script>

<template>
    <Head title="Reviews Moderation" />

    <AdminLayout>
        <main class="min-h-full space-y-7 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <section class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                        Customer Reviews
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Inspect, moderate, and manage verified renter ratings across the vehicle fleet.
                    </p>
                </div>

                <div class="flex items-center gap-3 rounded-2xl border border-black/5 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-5 py-3">
                    <Star class="h-6 w-6 fill-amber-400 text-amber-400" />
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Fleet Average</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.average }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">/ 5.0</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Overview Cards -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <article class="glass-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Reviews</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.total }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Completed bookings</p>
                </article>

                <article class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">5-Star</p>
                        <span class="flex h-5 w-5 items-center justify-center rounded-md bg-amber-500/10 text-amber-500">
                            <Star class="h-3 w-3 fill-amber-400 text-amber-500" />
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ stats.five_star }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ stats.total > 0 ? Math.round((stats.five_star / stats.total) * 100) : 0 }}% of total
                    </p>
                </article>

                <article class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">4-Star</p>
                        <span class="flex h-5 w-5 items-center justify-center rounded-md bg-amber-500/10 text-amber-500">
                            <Star class="h-3 w-3 fill-amber-400 text-amber-500" />
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.four_star }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Favorable feedback</p>
                </article>

                <article class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">3-Star & Below</p>
                        <span class="flex h-5 w-5 items-center justify-center rounded-md bg-rose-500/10 text-rose-500">
                            <Star class="h-3 w-3 fill-rose-400 text-rose-500" />
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">{{ stats.three_star_below }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Requires review</p>
                </article>
            </section>

            <!-- Filters Toolbar -->
            <section class="glass-card p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative flex-1">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Filter by comment content, vehicle make/model, or client name..."
                            class="apple-input w-full pl-10"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="ratingFilter"
                            class="apple-input cursor-pointer py-2.5 text-xs"
                        >
                            <option value="">All Star Ratings</option>
                            <option value="5">5 Stars only</option>
                            <option value="4">4 Stars only</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>

                        <button
                            v-if="search || ratingFilter"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-black/10 dark:border-white/10 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 cursor-pointer"
                            @click="clearFilters"
                        >
                            <X class="h-3.5 w-3.5" />
                            Reset
                        </button>
                    </div>
                </div>
            </section>

            <!-- Reviews Feed / List -->
            <section class="glass-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-black/5 dark:border-white/10 px-6 py-4">
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Moderation Feed</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Showing {{ reviews.data.length }} of {{ reviews.total }} total reviews
                        </p>
                    </div>
                </div>

                <div v-if="reviews.data.length === 0" class="px-6 py-16 text-center">
                    <MessageSquare class="mx-auto h-10 w-10 text-slate-400" />
                    <p class="mt-3 font-semibold text-slate-900 dark:text-white">No reviews found</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No client feedback matches your current filter criteria.</p>
                </div>

                <div v-else class="divide-y divide-black/5 dark:divide-white/5">
                    <article
                        v-for="item in reviews.data"
                        :key="item.id"
                        class="p-6 transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-3.5">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-white/10 font-bold text-slate-800 dark:text-slate-200">
                                    {{ getInitials(item.user?.name) }}
                                </span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            {{ item.user?.name || 'Verified Client' }}
                                        </p>
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:text-emerald-300">
                                            <ShieldCheck class="h-3 w-3" />
                                            Verified Renter
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ item.user?.email }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 sm:flex-col sm:items-end">
                                <StarRating :rating="item.rating" size="sm" />
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(item.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Review Text -->
                        <div class="mt-3 pl-0 sm:pl-13.5">
                            <h3 v-if="item.title" class="font-bold text-slate-900 dark:text-white text-sm">
                                "{{ item.title }}"
                            </h3>
                            <p class="mt-1 text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                                {{ item.comment }}
                            </p>

                            <!-- Linked metadata pill bar -->
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                <span v-if="item.car" class="inline-flex items-center gap-1.5 rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-1 text-slate-700 dark:text-slate-300">
                                    <Car class="h-3.5 w-3.5 text-slate-400" />
                                    {{ item.car.year }} {{ item.car.make }} {{ item.car.model }} ({{ item.car.license_plate }})
                                </span>

                                <span v-if="item.reservation" class="inline-flex items-center gap-1.5 rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-2.5 py-1 text-slate-700 dark:text-slate-300">
                                    <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                    Booking {{ item.reservation.reservation_number }}
                                </span>

                                <button
                                    type="button"
                                    class="ml-auto inline-flex items-center gap-1 rounded-lg border border-rose-500/20 px-2.5 py-1 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 cursor-pointer transition"
                                    @click="confirmDelete(item)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    Delete
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="reviews.links?.length > 3"
                    class="flex flex-wrap items-center justify-center gap-2 border-t border-black/5 dark:border-white/10 px-4 py-4"
                >
                    <Link
                        v-for="(link, i) in reviews.links"
                        :key="i"
                        :href="link.url || ''"
                        preserve-scroll
                        class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                        :class="[
                            link.active
                                ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]'
                                : 'border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10',
                            !link.url && 'pointer-events-none opacity-40',
                        ]"
                    >
                        <span v-html="link.label" />
                    </Link>
                </nav>
            </section>
        </main>

        <!-- Delete Modal -->
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
        >
            <div class="glass-card max-w-md w-full p-6 shadow-2xl">
                <div class="flex items-center gap-3 text-rose-600 dark:text-rose-400">
                    <Trash2 class="h-6 w-6" />
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Delete Review?</h3>
                </div>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Are you sure you want to delete this review by {{ deleteTarget.user?.name || 'this client' }}? This action removes the rating from the vehicle average.
                </p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-xl border border-black/10 dark:border-white/10 px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 cursor-pointer"
                        @click="deleteTarget = null"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-medium text-white hover:bg-rose-700 cursor-pointer"
                        :disabled="isDeleting === deleteTarget.id"
                        @click="executeDelete"
                    >
                        {{ isDeleting === deleteTarget.id ? 'Deleting...' : 'Confirm Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
