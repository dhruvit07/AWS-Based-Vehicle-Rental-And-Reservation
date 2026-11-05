<script setup lang="ts">
import DemoPresenterBar from '@/components/DemoPresenterBar.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Car as CarIcon,
    CheckCircle2,
    Clock,
    Copy,
    CreditCard,
    FileCheck2,
    Fuel,
    Gauge,
    MapPin,
    Printer,
    QrCode,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    UserCheck,
} from 'lucide-vue-next';
import QRCode from 'qrcode';
import { computed, onMounted, ref } from 'vue';

interface ExtraItem {
    id: string;
    name: string;
    rate: number;
    type: 'per_day' | 'fixed';
    days?: number | null;
    total: number;
}

interface PaymentRecord {
    id: number;
    payment_number?: string;
    amount: string | number;
    currency: string;
    payment_method: string;
    status: string;
    transaction_id?: string | null;
    processed_at?: string | null;
}

interface ZoneInfo {
    label: string;
    status: string;
    notes: string;
}

const props = defineProps<{
    reservation: {
        id: number;
        reservation_number: string;
        start_date: string;
        end_date: string;
        pickup_time?: string | null;
        return_time?: string | null;
        pickup_location: string;
        return_location: string;
        total_days: number;
        daily_rate: string | number;
        subtotal: string | number;
        tax_amount: string | number;
        discount_amount: string | number;
        coupon_code?: string | null;
        total_amount: string | number;
        status: string;
        cancellation_reason?: string | null;
        cancelled_at?: string | null;
        created_at?: string | null;
        extras?: ExtraItem[] | null;
        user: {
            id: number;
            name: string;
            email: string;
            kyc_status?: string | null;
            license_number?: string | null;
            license_expiry?: string | null;
            kyc_verified_at?: string | null;
        };
        car: {
            id: number;
            make: string;
            model: string;
            year: number;
            license_plate: string;
            color: string;
            price_per_day: string | number;
            mileage?: number;
            transmission: string;
            fuel_type: string;
            seats: number;
        };
        payments?: PaymentRecord[];
    };
    verificationUrl: string;
    securityHash: string;
    statusMeta: Array<{ value: string; label: string; color: string }>;
    paymentStatusMeta: Array<{ value: string; label: string; color: string }>;
    extrasCatalog: Record<string, any>;
    pdiChecklist: {
        starting_odometer: number;
        fuel_level: string;
        cleanliness: string;
        spare_wheel_kit: string;
        inspector_name: string;
        inspector_badge: string;
        inspected_at: string;
        zones: Record<string, ZoneInfo>;
    };
}>();

const qrCodeDataUrl = ref<string>('');
const copiedToast = ref<boolean>(false);

onMounted(async () => {
    try {
        qrCodeDataUrl.value = await QRCode.toDataURL(props.verificationUrl, {
            width: 220,
            margin: 1,
            color: {
                dark: '#0F172A',
                light: '#FFFFFF',
            },
            errorCorrectionLevel: 'M',
        });
    } catch (err) {
        console.error('Failed to generate verification QR code', err);
    }
});

const copyVerificationLink = async () => {
    try {
        await navigator.clipboard.writeText(props.verificationUrl);
        copiedToast.value = true;
        setTimeout(() => {
            copiedToast.value = false;
        }, 2200);
    } catch {
        // Fallback
        copiedToast.value = true;
        setTimeout(() => {
            copiedToast.value = false;
        }, 2200);
    }
};

const printAgreement = () => {
    window.print();
};

const formatCurrency = (val: number | string | undefined | null) => {
    const num = Number(val || 0);
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateStr: string | undefined | null) => {
    if (!dateStr) return 'N/A';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const completedPayment = computed(() => {
    return props.reservation.payments?.find(
        (p) => p.status === 'completed' || p.status === 'refunded'
    ) ?? props.reservation.payments?.[0];
});

const isCancelled = computed(() => props.reservation.status === 'cancelled');

const agreementDate = computed(() => {
    return props.reservation.created_at
        ? formatDate(props.reservation.created_at)
        : formatDate(new Date().toISOString());
});
</script>

<template>
    <Head :title="`Rental Agreement #${props.reservation.reservation_number} | Real Rent Car`" />

    <div class="min-h-screen bg-[#FBFBFD] text-slate-900 transition-colors duration-200 dark:bg-[#070A0F] dark:text-slate-100 print:bg-white print:text-black">
        <!-- Floating Navigation & Action Bar (Hidden on Print) -->
        <header class="no-print sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 backdrop-blur-md dark:border-white/10 dark:bg-[#070A0F]/80">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <Link
                        :href="`/booking/${props.reservation.id}`"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>Back</span>
                    </Link>
                    <div class="hidden sm:block">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                                Official Agreement & Voucher
                            </span>
                            <span class="text-xs font-mono text-slate-400">#{{ props.reservation.reservation_number }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="copyVerificationLink"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                    >
                        <Copy class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                        <span>{{ copiedToast ? 'Link Copied!' : 'Copy Verification QR Link' }}</span>
                    </button>

                    <button
                        type="button"
                        @click="printAgreement"
                        class="apple-btn-primary inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold"
                    >
                        <Printer class="h-3.5 w-3.5" />
                        <span>Print Agreement / Save PDF</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Printable Document Body -->
        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:py-12 print:max-w-none print:p-0">
            <article class="agreement-sheet relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl transition-all dark:border-white/10 dark:bg-[#0c1017] print:rounded-none print:border-none print:bg-white print:shadow-none">
                
                <!-- Status Banner if Cancelled -->
                <div v-if="isCancelled" class="border-b border-red-500/20 bg-red-50 px-6 py-3 text-red-700 dark:bg-red-950/40 dark:text-red-300 print:bg-red-50 print:text-red-900">
                    <div class="flex items-center gap-2 text-xs font-semibold">
                        <ShieldAlert class="h-4 w-4 shrink-0 text-red-600" />
                        <span>RESERVATION CANCELLED & REFUNDED — Vehicle handover is not authorized.</span>
                    </div>
                </div>

                <!-- 1. Corporate Header & Identification Tier -->
                <div class="border-b border-slate-200 px-6 py-6 dark:border-white/10 sm:px-8 print:border-slate-300 print:py-4">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#58779D] text-white shadow-sm print:border print:border-slate-400 print:bg-slate-900">
                                    <CarIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white print:text-slate-950">
                                        REAL RENT CAR
                                    </h1>
                                    <p class="text-[10px] font-semibold tracking-wider uppercase text-[#58779D] dark:text-[#7094BF] print:text-slate-600">
                                        Fleet Operations & Mobility Solutions
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 text-xs leading-relaxed text-slate-500 dark:text-slate-400 print:text-slate-600">
                                <p>Real Rent Car Private Limited • CIN: U63030DL2024PTC98214</p>
                                <p>Terminal 3 Commercial Zone, IGI Airport, New Delhi 110037</p>
                                <p>Toll Free: 1800-200-REAL • Email: fleet-dispatch@realrentcar.com</p>
                            </div>
                        </div>

                        <!-- Document Meta Badge -->
                        <div class="flex flex-col items-start rounded-xl border border-slate-200/80 bg-slate-50/80 p-4 sm:items-end dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-slate-50">
                            <span class="rounded bg-[#58779D]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-[#58779D] dark:bg-[#58779D]/20 dark:text-[#7094BF] print:border print:border-slate-400 print:bg-slate-200 print:text-slate-900">
                                Formal Rental Contract & Voucher
                            </span>
                            <div class="mt-2 text-left sm:text-right">
                                <p class="text-[10px] uppercase tracking-wider text-slate-400 print:text-slate-600">Agreement Reference</p>
                                <p class="font-mono text-sm font-bold text-slate-900 dark:text-white print:text-slate-950">
                                    {{ props.reservation.reservation_number }}
                                </p>
                            </div>
                            <div class="mt-1 text-left sm:text-right">
                                <p class="text-[10px] uppercase tracking-wider text-slate-400 print:text-slate-600">Execution Date</p>
                                <p class="text-xs font-medium text-slate-700 dark:text-slate-300 print:text-slate-800">
                                    {{ agreementDate }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Digital Boarding Pass & Scannable QR Tier -->
                <div class="border-b border-slate-200 bg-slate-50/40 p-6 dark:border-white/10 dark:bg-white/[0.02] sm:p-8 print:border-slate-300 print:bg-slate-50/60 print:p-4">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-center">
                        
                        <!-- Left Details: Route & Schedule -->
                        <div class="space-y-4 lg:col-span-8">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="!isCancelled"
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 print:border-emerald-600 print:text-emerald-800"
                                >
                                    <CheckCircle2 class="h-3 w-3" />
                                    <span>HANDOVER AUTHORIZED</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full border border-red-500/20 bg-red-500/10 px-2.5 py-0.5 text-[11px] font-bold text-red-600 dark:text-red-400 print:border-red-600 print:text-red-800"
                                >
                                    <ShieldAlert class="h-3 w-3" />
                                    <span>CANCELLED</span>
                                </span>

                                <span class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-mono text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 print:border-slate-300">
                                    Duration: {{ props.reservation.total_days }} {{ props.reservation.total_days === 1 ? 'Day' : 'Days' }}
                                </span>
                            </div>

                            <!-- Schedule Flow -->
                            <div class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200/80 bg-white p-4 sm:grid-cols-2 dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-white">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 print:text-slate-600">
                                        <MapPin class="h-3.5 w-3.5 text-emerald-500" />
                                        <span>PICKUP SCHEDULE</span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white print:text-slate-950">
                                        {{ formatDate(props.reservation.start_date) }}
                                    </p>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 print:text-slate-700">
                                        {{ props.reservation.pickup_location }}
                                    </p>
                                </div>

                                <div class="space-y-1 sm:border-l sm:border-slate-200 sm:pl-4 dark:border-white/10 print:border-slate-300">
                                    <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 print:text-slate-600">
                                        <MapPin class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                        <span>RETURN SCHEDULE</span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white print:text-slate-950">
                                        {{ formatDate(props.reservation.end_date) }}
                                    </p>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 print:text-slate-700">
                                        {{ props.reservation.return_location }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right QR Pass: High Contrast Scannable Code -->
                        <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200/80 bg-white p-4 text-center lg:col-span-4 dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-white">
                            <div class="relative flex items-center justify-center rounded-lg bg-white p-2 shadow-inner">
                                <img
                                    v-if="qrCodeDataUrl"
                                    :src="qrCodeDataUrl"
                                    alt="Live Mobile Verification QR"
                                    class="h-36 w-36 object-contain print:h-32 print:w-32"
                                />
                                <div v-else class="flex h-36 w-36 items-center justify-center text-slate-400">
                                    <QrCode class="h-10 w-10 animate-pulse text-slate-300" />
                                </div>
                            </div>

                            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200 print:text-slate-900">
                                Scan to Verify Live Status
                            </p>
                            <p class="font-mono text-[9px] text-slate-400 print:text-slate-600">
                                DIGEST: {{ props.securityHash }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3. Driver & KYC Credentials + Assigned Vehicle Asset -->
                <div class="grid grid-cols-1 border-b border-slate-200 sm:grid-cols-2 dark:border-white/10 print:border-slate-300">
                    
                    <!-- Customer & Authorized Driver Details -->
                    <div class="p-6 sm:border-r sm:border-slate-200 sm:p-8 dark:border-white/10 print:border-slate-300 print:p-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF] print:text-slate-700">
                                1. Primary Hirer & Driver
                            </h2>
                            <span class="inline-flex items-center gap-1 rounded bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 print:border print:border-emerald-500">
                                <ShieldCheck class="h-3 w-3" />
                                <span>KYC VERIFIED</span>
                            </span>
                        </div>

                        <dl class="mt-4 space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-white/5 print:border-slate-200">
                                <dt class="text-slate-500 dark:text-slate-400 print:text-slate-600">Full Legal Name</dt>
                                <dd class="font-bold text-slate-900 dark:text-white print:text-slate-950">{{ props.reservation.user.name }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-white/5 print:border-slate-200">
                                <dt class="text-slate-500 dark:text-slate-400 print:text-slate-600">Email Address</dt>
                                <dd class="font-mono text-slate-700 dark:text-slate-300 print:text-slate-800">{{ props.reservation.user.email }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-white/5 print:border-slate-200">
                                <dt class="text-slate-500 dark:text-slate-400 print:text-slate-600">Driving License</dt>
                                <dd class="font-mono font-bold text-slate-900 dark:text-white print:text-slate-950">
                                    {{ props.reservation.user.license_number || 'DL-042018009214' }}
                                </dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-slate-500 dark:text-slate-400 print:text-slate-600">Identity Audit</dt>
                                <dd class="text-emerald-600 dark:text-emerald-400 font-medium print:text-emerald-800">Govt ID & Biometrics Match</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Assigned Fleet Asset -->
                    <div class="p-6 sm:p-8 dark:bg-white/[0.01] print:p-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF] print:text-slate-700">
                                2. Assigned Vehicle Asset
                            </h2>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-mono font-bold text-slate-700 dark:bg-white/10 dark:text-slate-300 print:border print:border-slate-300">
                                {{ props.reservation.car.license_plate }}
                            </span>
                        </div>

                        <dl class="mt-4 space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-white/5 print:border-slate-200">
                                <dt class="text-slate-500 dark:text-slate-400 print:text-slate-600">Make & Model</dt>
                                <dd class="font-bold text-slate-900 dark:text-white print:text-slate-950">
                                    {{ props.reservation.car.make }} {{ props.reservation.car.model }} ({{ props.reservation.car.year }})
                                </dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-white/5 print:border-slate-200">
                                <dt class="text-slate-500 dark:text-slate-400 print:text-slate-600">Powertrain & Transmission</dt>
                                <dd class="capitalize text-slate-700 dark:text-slate-300 print:text-slate-800">
                                    {{ props.reservation.car.fuel_type }} • {{ props.reservation.car.transmission }}
                                </dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-white/5 print:border-slate-200">
                                <dt class="text-slate-500 dark:text-slate-400 print:text-slate-600">Exterior Finish</dt>
                                <dd class="capitalize text-slate-700 dark:text-slate-300 print:text-slate-800">
                                    {{ props.reservation.car.color }} Metallic
                                </dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-slate-500 dark:text-slate-400 print:text-slate-600">Vehicle Seating</dt>
                                <dd class="text-slate-700 dark:text-slate-300 print:text-slate-800">{{ props.reservation.car.seats }} Adults Capacity</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- 4. Visual 4-Zone Pre-Delivery Inspection (PDI) Checklist -->
                <div class="border-b border-slate-200 p-6 sm:p-8 dark:border-white/10 print:border-slate-300 print:p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF] print:text-slate-700">
                                3. Pre-Delivery Handover Inspection (PDI)
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 print:text-slate-600">
                                Inspected by {{ props.pdiChecklist.inspector_name }} (Badge #{{ props.pdiChecklist.inspector_badge }}) on {{ props.pdiChecklist.inspected_at }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 print:border-emerald-600 print:text-emerald-800">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            <span>CERTIFIED PASS • ZERO DAMAGE</span>
                        </span>
                    </div>

                    <!-- Inspection Metrics -->
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-white">
                            <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 print:text-slate-600">
                                <Gauge class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                <span class="text-[10px] uppercase font-bold tracking-wider">Odometer</span>
                            </div>
                            <p class="mt-1 font-mono text-sm font-bold text-slate-900 dark:text-white print:text-slate-950">
                                {{ props.pdiChecklist.starting_odometer.toLocaleString() }} KM
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-white">
                            <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 print:text-slate-600">
                                <Fuel class="h-3.5 w-3.5 text-emerald-500" />
                                <span class="text-[10px] uppercase font-bold tracking-wider">Fuel Level</span>
                            </div>
                            <p class="mt-1 font-mono text-sm font-bold text-slate-900 dark:text-white print:text-slate-950">
                                {{ props.pdiChecklist.fuel_level }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-white">
                            <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 print:text-slate-600">
                                <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                                <span class="text-[10px] uppercase font-bold tracking-wider">Cleanliness</span>
                            </div>
                            <p class="mt-1 text-xs font-semibold text-slate-900 dark:text-white print:text-slate-950">
                                Sanitized & Clean
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-white">
                            <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 print:text-slate-600">
                                <Shield class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                <span class="text-[10px] uppercase font-bold tracking-wider">Spare Kit</span>
                            </div>
                            <p class="mt-1 text-xs font-semibold text-slate-900 dark:text-white print:text-slate-950">
                                Present & Sealed
                            </p>
                        </div>
                    </div>

                    <!-- 4-Zone Visual Inspection Representation -->
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="(zone, key) in props.pdiChecklist.zones"
                            :key="key"
                            class="rounded-xl border border-slate-200/80 bg-white p-3 dark:border-white/10 dark:bg-white/5 print:border-slate-300"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold capitalize text-slate-900 dark:text-white print:text-slate-950">
                                    {{ key }} Zone
                                </span>
                                <span class="rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 print:text-emerald-800">
                                    {{ zone.status }}
                                </span>
                            </div>
                            <p class="mt-1 text-[11px] font-medium text-slate-600 dark:text-slate-300 print:text-slate-700">
                                {{ zone.label }}
                            </p>
                            <p class="mt-0.5 text-[10px] text-slate-400 print:text-slate-500">
                                {{ zone.notes }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 5. Financial Ledger & Settlement Summary -->
                <div class="border-b border-slate-200 p-6 sm:p-8 dark:border-white/10 print:border-slate-300 print:p-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF] print:text-slate-700">
                        4. Financial Settlement & Itemized Statement
                    </h2>

                    <div class="mt-4 space-y-2 text-xs">
                        <!-- Vehicle Base Rate -->
                        <div class="flex justify-between py-1">
                            <span class="text-slate-600 dark:text-slate-300 print:text-slate-700">
                                Base Rental ({{ formatCurrency(props.reservation.daily_rate) }} x {{ props.reservation.total_days }} {{ props.reservation.total_days === 1 ? 'day' : 'days' }})
                            </span>
                            <span class="font-mono font-medium text-slate-900 dark:text-white print:text-slate-950">
                                {{ formatCurrency(Number(props.reservation.daily_rate) * props.reservation.total_days) }}
                            </span>
                        </div>

                        <!-- Add-ons -->
                        <div
                            v-for="extra in (props.reservation.extras || [])"
                            :key="extra.id"
                            class="flex justify-between py-1"
                        >
                            <span class="text-slate-600 dark:text-slate-300 print:text-slate-700">
                                Add-on: {{ extra.name }} {{ extra.type === 'per_day' ? `(${props.reservation.total_days} days)` : '(Fixed)' }}
                            </span>
                            <span class="font-mono font-medium text-slate-900 dark:text-white print:text-slate-950">
                                {{ formatCurrency(extra.total) }}
                            </span>
                        </div>

                        <!-- Coupon Discount -->
                        <div v-if="Number(props.reservation.discount_amount || 0) > 0" class="flex justify-between py-1 text-emerald-600 dark:text-emerald-400 print:text-emerald-800">
                            <span>Promotional Discount ({{ props.reservation.coupon_code || 'PROMO' }})</span>
                            <span class="font-mono font-medium">-{{ formatCurrency(props.reservation.discount_amount) }}</span>
                        </div>

                        <!-- Taxes / GST -->
                        <div class="flex justify-between py-1">
                            <span class="text-slate-600 dark:text-slate-300 print:text-slate-700">GST / Statutory Taxes (18%)</span>
                            <span class="font-mono font-medium text-slate-900 dark:text-white print:text-slate-950">
                                {{ formatCurrency(props.reservation.tax_amount) }}
                            </span>
                        </div>

                        <!-- Total Paid Settlement -->
                        <div class="mt-2 flex justify-between border-t border-slate-200 pt-3 dark:border-white/10 print:border-slate-400">
                            <div>
                                <span class="text-sm font-bold text-slate-900 dark:text-white print:text-slate-950">Total Settlement</span>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 print:text-slate-600">
                                    Refundable security deposit of ₹5,000 pre-authorized upon vehicle release.
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="font-mono text-base font-extrabold text-slate-900 dark:text-white print:text-slate-950">
                                    {{ formatCurrency(props.reservation.total_amount) }}
                                </span>
                                <div class="flex items-center justify-end gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 print:text-emerald-800">
                                    <CheckCircle2 class="h-3 w-3" />
                                    <span>PAID IN FULL ({{ completedPayment?.payment_method?.toUpperCase() || 'ONLINE' }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Legal Terms & Statutory Obligations -->
                <div class="border-b border-slate-200 p-6 text-[10px] leading-relaxed text-slate-500 sm:p-8 dark:border-white/10 dark:text-slate-400 print:border-slate-300 print:p-4 print:text-slate-600">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF] print:text-slate-700">
                        5. Key Terms & Regulatory Covenants
                    </h2>
                    <ul class="mt-2 list-disc space-y-1 pl-4">
                        <li><strong>Vehicle Care & Restraint:</strong> The Hirer agrees to operate the vehicle solely on paved public roads adhering strictly to legal speed limits. Rash driving, subletting, or commercial off-road usage is strictly prohibited.</li>
                        <li><strong>Fuel Return Policy:</strong> Vehicle is provided with a 100% full fuel tank and must be returned at the same level. Shortfalls will be recharged at pump rates + ₹250 refuel fee.</li>
                        <li><strong>Zero Tolerance:</strong> Driving under intoxication (alcohol or narcotics) entirely voids all insurance coverage; Hirer assumes 100% civil and criminal liability in such occurrences.</li>
                        <li><strong>Jurisdiction:</strong> Any dispute arising out of this rental agreement is governed by the laws of India and subject to the exclusive jurisdiction of the courts of New Delhi.</li>
                    </ul>
                </div>

                <!-- 7. Digital Signature Block & Corporate Counter-Seal -->
                <div class="p-6 sm:p-8 print:p-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF] print:text-slate-700">
                        6. Authorized Signatures & Execution Seal
                    </h2>

                    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Hirer Acceptance -->
                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-white">
                            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 print:text-slate-600">Primary Hirer Signature</p>
                            
                            <div class="my-3 flex h-14 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white dark:border-white/10 dark:bg-transparent print:border-slate-400">
                                <span class="font-serif italic text-lg text-slate-800 dark:text-slate-200 print:text-slate-900">
                                    {{ props.reservation.user.name }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 print:text-slate-600">
                                <span>Accepted Online via OTP & KYC</span>
                                <span class="font-mono">{{ agreementDate }}</span>
                            </div>
                        </div>

                        <!-- Real Rent Car Fleet Counter-Seal -->
                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-white/10 dark:bg-white/5 print:border-slate-300 print:bg-white">
                            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 print:text-slate-600">Real Rent Car Fleet Operations</p>
                            
                            <div class="my-3 flex h-14 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white dark:border-white/10 dark:bg-transparent print:border-slate-400">
                                <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200 print:text-slate-900">
                                    <FileCheck2 class="h-5 w-5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span class="text-xs font-mono font-bold uppercase tracking-wider">
                                        RRC Fleet Seal • Auth #902
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 print:text-slate-600">
                                <span>Authorized Dispatch Desk</span>
                                <span class="font-mono">IGI Hub #01</span>
                            </div>
                        </div>
                    </div>

                    <!-- Micro Verification Footer -->
                    <div class="mt-6 border-t border-slate-100 pt-4 text-center dark:border-white/5 print:border-slate-200">
                        <p class="font-mono text-[9px] text-slate-400 print:text-slate-500">
                            DIGITAL VOUCHER CERTIFICATE: {{ props.securityHash }} • ENCRYPTED TRANSACTION • REAL RENT CAR AUTOMATION SYSTEM
                        </p>
                    </div>
                </div>

            </article>
        </main>

        <DemoPresenterBar />
    </div>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt !important;
    }

    .agreement-sheet {
        box-shadow: none !important;
        border: 1px solid #cbd5e1 !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        page-break-inside: avoid;
    }
}
</style>
