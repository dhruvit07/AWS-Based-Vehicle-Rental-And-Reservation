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

const cancelDelete = () => {
    deleteTarget.value = null;
};

const executeDelete = () => {
    if (!deleteTarget.value) return;

    isDeleting.value = deleteTarget.value.id;
    router.delete(`/admin/reviews/${deleteTarget.value.id}`, {
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
        : new Intl.DateTimeFormat('en-IN', {
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
        <main class="min-h-full space-y-6 bg-[#F4F6FA] px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <section class="flex flex-col gap-4 rounded-2xl bg-[#101D35] p-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#C5D0DE]">
                        <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                        Rating & Reputation Moderation
                    </div>
                    <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Customer Reviews</h1>
                    <p class="mt-2 text-sm text-slate-300">
                        Inspect, moderate, and manage verified renter vehicle ratings and written reviews across the fleet.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="rounded-xl border border-[#58779D] bg-[#263F60] px-5 py-4 text-center sm:text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#C5D0DE]">Average Fleet Rating</p>
                        <div class="mt-1 flex items-center justify-center gap-2 sm:justify-end">
                            <Star class="h-6 w-6 fill-amber-400 text-amber-400" />
                            <span class="text-3xl font-black text-white">{{ stats.average }}</span>
                            <span class="text-xs text-slate-300">/ 5.0</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Overview Cards -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <article class="rounded-2xl border border-[#DCE3ED] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Reviews</p>
                    <p class="mt-2 text-3xl font-black text-[#101D35]">{{ stats.total }}</p>
                    <p class="mt-1 text-xs text-slate-500">From completed reservations</p>
                </article>

                <article class="rounded-2xl border border-[#DCE3ED] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">5-Star Ratings</p>
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-50 text-amber-500">
                            <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-500" />
                        </span>
                    </div>
                    <p class="mt-2 text-3xl font-black text-emerald-700">{{ stats.five_star }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ stats.total > 0 ? Math.round((stats.five_star / stats.total) * 100) : 0 }}% of all reviews
                    </p>
                </article>

                <article class="rounded-2xl border border-[#DCE3ED] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">4-Star Ratings</p>
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-50 text-amber-500">
                            <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-500" />
                        </span>
                    </div>
                    <p class="mt-2 text-3xl font-black text-[#263F60]">{{ stats.four_star }}</p>
                    <p class="mt-1 text-xs text-slate-500">Great condition feedback</p>
                </article>

                <article class="rounded-2xl border border-[#DCE3ED] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">3-Star & Below</p>
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-rose-50 text-rose-500">
                            <Star class="h-3.5 w-3.5 fill-rose-400 text-rose-500" />
                        </span>
                    </div>
                    <p class="mt-2 text-3xl font-black text-amber-800">{{ stats.three_star_below }}</p>
                    <p class="mt-1 text-xs text-slate-500">Requires fleet attention</p>
                </article>
            </section>

            <!-- Filters Toolbar -->
            <section class="rounded-2xl border border-[#DCE3ED] bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative flex-1">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search by renter name, vehicle, or review keywords..."
                            class="w-full rounded-xl border border-[#DCE3ED] bg-[#F8FAFC] py-2.5 pl-10 pr-4 text-sm text-[#101D35] outline-none transition placeholder:text-slate-400 focus:border-[#58779D] focus:ring-2 focus:ring-[#58779D]/10"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <select
                            v-model="ratingFilter"
                            class="rounded-xl border border-[#DCE3ED] bg-white px-3.5 py-2.5 text-sm font-medium text-[#101D35] outline-none transition focus:border-[#58779D] focus:ring-2 focus:ring-[#58779D]/10"
                        >
                            <option value="">All Star Ratings</option>
                            <option value="5">★ 5 Stars Only</option>
                            <option value="4">★ 4 Stars Only</option>
                            <option value="3">★ 3 Stars Only</option>
                            <option value="2">★ 2 Stars Only</option>
                            <option value="1">★ 1 Star Only</option>
                        </select>

                        <button
                            v-if="search || ratingFilter"
                            type="button"
                            @click="clearFilters"
                            class="rounded-xl border border-[#DCE3ED] px-3.5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </section>

            <!-- Reviews Table -->
            <section class="overflow-hidden rounded-2xl border border-[#DCE3ED] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#E5EAF1] px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-base font-bold text-[#101D35]">Verified Renter Feedback</h2>
                        <p class="text-xs text-slate-500">
                            Showing {{ reviews.from || 0 }}–{{ reviews.to || 0 }} of {{ reviews.total }} total reviews
                        </p>
                    </div>
                    <span class="rounded-full bg-[#E8EDF5] px-3 py-1 text-xs font-bold text-[#101D35]">
                        {{ reviews.total }} reviews
                    </span>
                </div>

                <!-- Empty State -->
                <div v-if="reviews.data.length === 0" class="p-12 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F4F6FA] text-slate-400">
                        <MessageSquare class="h-7 w-7" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-[#101D35]">No customer reviews found</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Try modifying your search query or rating filter.
                    </p>
                    <button
                        v-if="search || ratingFilter"
                        type="button"
                        @click="clearFilters"
                        class="mt-4 rounded-xl bg-[#101D35] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#263F60]"
                    >
                        Reset Filters
                    </button>
                </div>

                <!-- Desktop Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="bg-[#F4F6FA] text-xs font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Renter</th>
                                <th class="px-6 py-4">Vehicle</th>
                                <th class="px-6 py-4">Rating</th>
                                <th class="px-6 py-4">Review Content</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[#E8EDF4]">
                            <tr
                                v-for="review in reviews.data"
                                :key="review.id"
                                class="transition-colors hover:bg-[#F8FAFD]"
                            >
                                <!-- Renter Column -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#101D35] text-xs font-bold text-white uppercase">
                                            {{ getInitials(review.user?.name) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-[#101D35]">
                                                {{ review.user?.name || 'Verified Client' }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ review.user?.email || '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Vehicle Column -->
                                <td class="px-6 py-4">
                                    <div v-if="review.car">
                                        <p class="font-bold text-[#263F60]">
                                            {{ review.car.year }} {{ review.car.make }} {{ review.car.model }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            Plate: {{ review.car.license_plate }}
                                        </p>
                                    </div>
                                    <span v-else class="text-xs text-slate-400">Vehicle Removed</span>
                                </td>

                                <!-- Rating Column -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <StarRating :rating="review.rating" :readonly="true" size="sm" />
                                        <span class="text-xs font-bold text-[#101D35]">{{ review.rating }}/5</span>
                                    </div>
                                    <div v-if="review.reservation" class="mt-1">
                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">
                                            {{ review.reservation.reservation_number }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Review Content Column -->
                                <td class="px-6 py-4 max-w-md">
                                    <p v-if="review.title" class="font-bold text-[#101D35]">
                                        {{ review.title }}
                                    </p>
                                    <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-slate-600">
                                        {{ review.comment }}
                                    </p>
                                </td>

                                <!-- Date Column -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                    {{ formatDate(review.created_at) }}
                                </td>

                                <!-- Action Column -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 hover:text-rose-800"
                                        @click="confirmDelete(review)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        <span>Delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="reviews.links?.length && reviews.data.length > 0"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E5EAF1] p-4 sm:px-6"
                >
                    <p class="text-xs text-slate-500">
                        Page <span class="font-bold text-[#101D35]">{{ reviews.from || 0 }}</span> to <span class="font-bold text-[#101D35]">{{ reviews.to || 0 }}</span> of {{ reviews.total }}
                    </p>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <Link
                            v-for="(link, i) in reviews.links"
                            :key="i"
                            :href="link.url || '#'"
                            :preserve-scroll="true"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                link.active
                                    ? 'bg-[#101D35] text-white'
                                    : 'border border-[#DCE3ED] bg-white text-[#263F60] hover:bg-[#F4F6FA]',
                                !link.url && 'pointer-events-none opacity-40',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="fixed inset-0 bg-[#101D35]/70 backdrop-blur-xs" @click="cancelDelete"></div>

            <div class="relative z-10 w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100">
                        <Trash2 class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-[#101D35]">Delete Customer Review</h3>
                        <p class="text-xs text-slate-500">This action cannot be undone.</p>
                    </div>
                </div>

                <div class="my-4 rounded-xl bg-slate-50 p-4 text-xs text-slate-700 space-y-1">
                    <p><strong>Author:</strong> {{ deleteTarget.user?.name || 'Client' }}</p>
                    <p><strong>Rating:</strong> {{ deleteTarget.rating }} / 5 stars</p>
                    <p class="line-clamp-2"><strong>Comment:</strong> "{{ deleteTarget.comment }}"</p>
                </div>

                <p class="text-xs text-slate-500">
                    Are you sure you want to permanently delete this review from the vehicle profile?
                </p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                        @click="cancelDelete"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isDeleting !== null"
                        class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-rose-700 disabled:opacity-50"
                        @click="executeDelete"
                    >
                        {{ isDeleting ? 'Deleting...' : 'Confirm Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
