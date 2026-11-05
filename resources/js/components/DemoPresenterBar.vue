<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarPlus,
    CalendarX2,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Compass,
    ExternalLink,
    GraduationCap,
    Play,
    QrCode,
    Radio,
    RotateCcw,
    Shield,
    ShieldCheck,
    Sparkles,
    UserCheck,
    Users,
    Wrench,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage();
const isExpanded = ref<boolean>(false);
const isSwitching = ref<boolean>(false);

const currentUser = computed(() => (page.props.auth as any)?.user || null);
const userRole = computed(() => currentUser.value?.role || 'guest');

const scenarios = [
    {
        id: 'overlap',
        title: 'Date Overlap Collision',
        role: 'Client',
        icon: CalendarX2,
        desc: 'Calendar range locking & collision prevention',
    },
    {
        id: 'kyc',
        title: 'Driver KYC Verification',
        role: 'Admin',
        icon: ShieldCheck,
        desc: 'Government license review & approval modal',
    },
    {
        id: 'voucher',
        title: 'Printable Pass & QR Voucher',
        role: 'Client',
        icon: QrCode,
        desc: 'A4 contract + live phone-scannable QR pass',
    },
    {
        id: 'radar',
        title: 'Fleet Operations Radar',
        role: 'Admin',
        icon: Radio,
        desc: 'Interactive hub radar & telemetry simulation',
    },
    {
        id: 'extend',
        title: 'Trip Extension & Ledger',
        role: 'Client',
        icon: CalendarPlus,
        desc: 'Differential pricing & prorated early return',
    },
    {
        id: 'cancel',
        title: 'Cancellation & Refund',
        role: 'Client',
        icon: RotateCcw,
        desc: 'Self-service cancel & automated refund ledger',
    },
    {
        id: 'damage',
        title: 'Condition & Damage Inspector',
        role: 'Admin',
        icon: Wrench,
        desc: 'SVG body blueprint & deposit settlement engine',
    },
];

const switchRole = (targetRole: 'admin' | 'client') => {
    isSwitching.value = true;
    router.post(
        '/demo/switch-role',
        { role: targetRole },
        {
            preserveScroll: true,
            onFinish: () => {
                isSwitching.value = false;
            },
        },
    );
};

const launchScenario = (scenarioId: string) => {
    isExpanded.value = false;
    router.post(`/demo/launch-scenario/${scenarioId}`, {}, { preserveScroll: false });
};
</script>

<template>
    <aside aria-label="Demo Presenter Helper" class="no-print fixed bottom-4 right-4 z-50">
        <!-- Collapsed Floating Pill -->
        <div
            v-if="!isExpanded"
            class="flex items-center gap-2 rounded-full border border-slate-200/90 bg-white/90 p-1.5 shadow-2xl backdrop-blur-xl transition hover:shadow-cyan-500/10 dark:border-white/15 dark:bg-[#0c1017]/90"
        >
            <div class="flex items-center gap-1.5 pl-2.5 pr-1">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-500"></span>
                </span>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-800 dark:text-white">
                    Demo Mode
                </span>
            </div>

            <!-- Current Persona Badge -->
            <button
                type="button"
                @click="switchRole(userRole === 'admin' ? 'client' : 'admin')"
                :disabled="isSwitching"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold transition"
                :class="userRole === 'admin'
                    ? 'border border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500/20'
                    : userRole === 'client'
                        ? 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20'
                        : 'border border-slate-200 bg-slate-100 text-slate-700 dark:border-white/10 dark:bg-white/10 dark:text-slate-300'"
                :title="`Click to switch to ${userRole === 'admin' ? 'Client' : 'Admin'}`"
            >
                <span>{{ userRole === 'admin' ? 'Admin ⚡️' : userRole === 'client' ? 'Client 🚗' : 'Guest 👤' }}</span>
                <span class="text-[9px] underline opacity-70">Switch</span>
            </button>

            <!-- Link to Deck -->
            <Link
                href="/presentation"
                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/10 dark:text-slate-200 dark:hover:bg-white/15"
            >
                <GraduationCap class="h-3 w-3 text-cyan-500" />
                <span>Deck</span>
            </Link>

            <!-- Expand Drawer Toggle -->
            <button
                type="button"
                @click="isExpanded = true"
                class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-200 dark:bg-white/10 dark:text-slate-300 dark:hover:bg-white/20"
                title="Expand Scenarios & Evaluator Guide"
            >
                <ChevronUp class="h-4 w-4" />
            </button>
        </div>

        <!-- Expanded Presenter Drawer -->
        <div
            v-else
            class="w-[360px] overflow-hidden rounded-3xl border border-slate-200/90 bg-white/95 p-5 shadow-2xl backdrop-blur-2xl transition-all dark:border-white/15 dark:bg-[#0c1017]/95 sm:w-[420px]"
        >
            <!-- Drawer Header -->
            <div class="flex items-start justify-between border-b border-slate-100 pb-3.5 dark:border-white/10">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-500/15 text-cyan-600 dark:text-cyan-400">
                        <GraduationCap class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-black tracking-wider uppercase text-slate-900 dark:text-white">
                                Presenter Helper
                            </h3>
                            <span class="rounded bg-cyan-500/10 px-1.5 py-0.2 text-[9px] font-bold text-cyan-600 dark:text-cyan-400 font-mono">
                                Viva Mode
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            1-click role toggles & curated evaluator scenarios.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="isExpanded = false"
                    class="rounded-xl p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-white"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Instant Role Switcher Section -->
            <div class="mt-3.5 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-white/10 dark:bg-white/5">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="font-bold text-slate-600 dark:text-slate-400">Active Persona:</span>
                    <span
                        class="rounded-full px-2 py-0.5 font-bold uppercase text-[10px]"
                        :class="userRole === 'admin'
                            ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400'
                            : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'"
                    >
                        {{ currentUser?.name || 'Guest Visitor' }} ({{ userRole }})
                    </span>
                </div>

                <div class="mt-2.5 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="switchRole('client')"
                        :disabled="isSwitching || userRole === 'client'"
                        class="flex items-center justify-center gap-1.5 rounded-xl border py-2 text-xs font-bold transition disabled:opacity-50"
                        :class="userRole === 'client'
                            ? 'border-emerald-500 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                    >
                        <UserCheck class="h-3.5 w-3.5" />
                        <span>Client Workspace</span>
                    </button>

                    <button
                        type="button"
                        @click="switchRole('admin')"
                        :disabled="isSwitching || userRole === 'admin'"
                        class="flex items-center justify-center gap-1.5 rounded-xl border py-2 text-xs font-bold transition disabled:opacity-50"
                        :class="userRole === 'admin'
                            ? 'border-rose-500 bg-rose-500/15 text-rose-600 dark:text-rose-400'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300'"
                    >
                        <Shield class="h-3.5 w-3.5" />
                        <span>Admin Ops Hub</span>
                    </button>
                </div>
            </div>

            <!-- Curated Walkthrough Scenarios -->
            <div class="mt-3.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Curated Evaluator Scenarios (1-Click Launch)
                </span>

                <div class="mt-2 space-y-1.5 max-h-56 overflow-y-auto pr-1">
                    <button
                        v-for="sc in scenarios"
                        :key="sc.id"
                        type="button"
                        @click="launchScenario(sc.id)"
                        class="flex w-full items-center justify-between rounded-xl border border-slate-200/80 bg-white p-2.5 text-left transition hover:border-[#58779D]/50 hover:bg-slate-50/80 dark:border-white/10 dark:bg-white/5 dark:hover:border-[#7094BF]/50 dark:hover:bg-white/10"
                    >
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[#58779D] dark:bg-white/10 dark:text-[#7094BF]">
                                <component :is="sc.icon" class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ sc.title }}
                                </p>
                                <p class="text-[10px] text-slate-400 line-clamp-1">
                                    {{ sc.desc }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="rounded px-1.5 py-0.5 text-[9px] font-mono font-bold"
                            :class="sc.role === 'Admin' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'"
                        >
                            {{ sc.role }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Footer: Link to Full Presentation Deck -->
            <div class="mt-3.5 border-t border-slate-100 pt-3 dark:border-white/10 flex items-center justify-between">
                <Link
                    href="/presentation"
                    class="apple-btn-primary flex items-center justify-center gap-2 py-2 px-3 text-xs font-semibold w-full"
                >
                    <GraduationCap class="h-3.5 w-3.5" />
                    <span>Open Full Academic Presentation Deck</span>
                </Link>
            </div>
        </div>
    </aside>
</template>
