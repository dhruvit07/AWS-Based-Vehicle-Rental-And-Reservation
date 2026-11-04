<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ref, computed } from 'vue';
import {
  Dialog, DialogClose, DialogContent, DialogDescription,
  DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import {
  AlertCircle,
  ArrowLeft,
  CalendarDays,
  CarFront,
  CheckCircle2,
  CreditCard,
  Eye,
  ShieldAlert,
  ShieldCheck,
  UserRound,
  Wallet,
} from 'lucide-vue-next';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { index, suspend, activate } from '@/routes/admin/clients';

const props = defineProps<{
  client: {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    created_at?: string;
    kyc_status?: string;
    license_number?: string | null;
    license_expiry?: string | null;
    license_front_url?: string | null;
    license_back_url?: string | null;
    kyc_rejection_reason?: string | null;
    kyc_verified_at?: string | null;
  };
  stats: { total_reservations: number; total_payments: number; total_spent: number };
  reservations: {
    data: Array<{
      id: number;
      reservation_number: string;
      start_date: string;
      end_date: string;
      total_days?: number;
      total_amount: number | string;
      status: string;
      car?: { year: number; make: string; model: string; license_plate: string } | null;
    }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  payments: {
    data: Array<{
      id: number;
      payment_number: string;
      amount: number | string;
      currency?: string;
      payment_method: string;
      status: string;
      processed_at?: string | null;
      reservation?: { id: number; reservation_number: string } | null;
    }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  currency: { symbol: string; code: string };
}>();

const showSuspendDialog = ref(false);
const processingSuspend = ref(false);
const showActivateDialog = ref(false);
const processingActivate = ref(false);

const showRejectDialog = ref(false);
const rejectionReason = ref('');
const rejectionError = ref('');
const processingKyc = ref(false);
const previewImage = ref<{ url: string; title: string } | null>(null);

function fmtMoney(n?: number | string) {
  return `${props.currency.symbol}${Number(n ?? 0).toFixed(2)}`;
}

function fmtDate(value: string) {
  return new Date(value).toLocaleDateString(undefined, {
    day: '2-digit', month: 'short', year: 'numeric',
  });
}

function fmtDateTime(value?: string | null) {
  return value ? new Date(value).toLocaleString() : '—';
}

function suspendClient() {
  processingSuspend.value = true;
  router.patch(suspend(props.client.id), {}, {
    preserveScroll: true,
    onFinish: () => { processingSuspend.value = false; },
    onSuccess: () => { showSuspendDialog.value = false; },
  });
}

function activateClient() {
  processingActivate.value = true;
  router.patch(activate(props.client.id), {}, {
    preserveScroll: true,
    onFinish: () => { processingActivate.value = false; },
    onSuccess: () => { showActivateDialog.value = false; },
  });
}

const statusStyle = computed(() => {
  return props.client.is_active
    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
    : 'border-rose-500/20 bg-rose-500/10 text-rose-800 dark:text-rose-300';
});

const kycBadge = computed(() => {
  const status = (props.client.kyc_status || 'unsubmitted').toLowerCase();
  switch (status) {
    case 'verified':
      return {
        label: 'Verified Driver',
        class: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300',
        dot: 'bg-emerald-500',
      };
    case 'pending':
      return {
        label: 'Under Review',
        class: 'border-amber-500/20 bg-amber-500/10 text-amber-800 dark:text-amber-300',
        dot: 'bg-amber-500',
      };
    case 'rejected':
      return {
        label: 'Rejected',
        class: 'border-rose-500/20 bg-rose-500/10 text-rose-800 dark:text-rose-300',
        dot: 'bg-rose-500',
      };
    default:
      return {
        label: 'Not Submitted',
        class: 'border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300',
        dot: 'bg-slate-400',
      };
  }
});

function approveKyc() {
  processingKyc.value = true;
  router.patch(`/admin/clients/${props.client.id}/kyc/approve`, {}, {
    preserveScroll: true,
    onFinish: () => {
      processingKyc.value = false;
    },
  });
}

function openRejectDialog() {
  rejectionReason.value = props.client.kyc_rejection_reason || '';
  rejectionError.value = '';
  showRejectDialog.value = true;
}

function submitRejectKyc() {
  if (!rejectionReason.value.trim()) {
    rejectionError.value = 'Please provide a feedback reason for rejecting the license.';
    return;
  }

  processingKyc.value = true;
  router.patch(`/admin/clients/${props.client.id}/kyc/reject`, {
    rejection_reason: rejectionReason.value.trim(),
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showRejectDialog.value = false;
      rejectionReason.value = '';
    },
    onFinish: () => {
      processingKyc.value = false;
    },
  });
}

function badgeStyle(status: string) {
  const value = status.toLowerCase();
  if (['confirmed', 'completed', 'paid', 'success', 'succeeded', 'active'].includes(value)) {
    return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300';
  }
  if (['pending', 'processing', 'in_progress'].includes(value)) {
    return 'border-amber-500/20 bg-amber-500/10 text-amber-800 dark:text-amber-300';
  }
  if (['cancelled', 'canceled', 'failed', 'rejected', 'refunded', 'suspended'].includes(value)) {
    return 'border-rose-500/20 bg-rose-500/10 text-rose-800 dark:text-rose-300';
  }
  return 'border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300';
}
</script>

<template>
  <Head :title="`Client ${client.name}`" />

  <AdminLayout>
    <main class="min-h-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
      <!-- Page heading -->
      <div class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
        <div class="flex items-start gap-3.5">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
            <UserRound class="h-6 w-6" />
          </div>
          <div>
            <div class="flex items-center gap-2.5">
              <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ client.name }}
              </h1>
              <span
                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                :class="statusStyle"
              >
                <span class="h-1.5 w-1.5 rounded-full" :class="client.is_active ? 'bg-emerald-500' : 'bg-rose-500'" />
                {{ client.is_active ? 'Active Account' : 'Suspended' }}
              </span>
            </div>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ client.email }}</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Button
            v-if="client.is_active"
            class="h-9 rounded-xl bg-rose-600 text-xs font-medium text-white hover:bg-rose-700"
            @click="showSuspendDialog = true"
          >
            Suspend User
          </Button>
          <Button
            v-else
            class="h-9 rounded-xl bg-emerald-600 text-xs font-medium text-white hover:bg-emerald-700"
            @click="showActivateDialog = true"
          >
            Activate User
          </Button>

          <Link :href="index()">
            <Button variant="outline" class="h-9 rounded-xl border-black/10 dark:border-white/10 text-xs font-medium hover:bg-slate-100 dark:hover:bg-white/10">
              <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />
              Back to Clients
            </Button>
          </Link>
        </div>
      </div>

      <!-- Account information -->
      <section class="glass-card p-6">
        <div class="mb-5 flex items-center gap-2.5">
          <div class="rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF] p-2">
            <ShieldCheck class="h-4 w-4" />
          </div>
          <div>
            <h2 class="font-semibold text-slate-900 dark:text-white">Account Information</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Client profile registration details</p>
          </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Client ID</p>
            <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">#{{ client.id }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Full Name</p>
            <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">{{ client.name }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Email Address</p>
            <p class="mt-0.5 break-all font-semibold text-slate-900 dark:text-white">{{ client.email }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Account Created</p>
            <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">
              {{ client.created_at ? fmtDate(client.created_at) : '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Account Status</p>
            <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">{{ client.is_active ? 'Active' : 'Suspended' }}</p>
          </div>
        </div>
      </section>

      <!-- Driver Verification & License -->
      <section class="glass-card p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 border-b border-black/5 dark:border-white/10 pb-5 sm:flex-row sm:items-center">
          <div class="flex items-center gap-3">
            <div class="rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF] p-2">
              <ShieldCheck class="h-4 w-4" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="font-semibold text-slate-900 dark:text-white">Driver License & KYC Verification</h2>
                <span
                  class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                  :class="kycBadge.class"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="kycBadge.dot" />
                  {{ kycBadge.label }}
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400">Inspection console for government-issued credentials</p>
            </div>
          </div>

          <!-- Review Controls -->
          <div class="flex flex-wrap items-center gap-2">
            <Button
              v-if="client.kyc_status !== 'verified'"
              class="h-9 gap-1.5 rounded-xl bg-emerald-600 text-xs font-medium text-white hover:bg-emerald-700"
              :disabled="processingKyc"
              @click="approveKyc"
            >
              <CheckCircle2 class="h-3.5 w-3.5" />
              {{ processingKyc ? 'Processing...' : 'Approve License' }}
            </Button>
            <Button
              v-else
              variant="outline"
              class="h-9 gap-1.5 rounded-xl border-emerald-500/30 bg-emerald-500/10 text-xs font-medium text-emerald-800 dark:text-emerald-300 hover:bg-emerald-500/20"
              :disabled="processingKyc"
              @click="approveKyc"
            >
              <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
              Re-approve
            </Button>

            <Button
              variant="outline"
              class="h-9 gap-1.5 rounded-xl border-rose-500/30 text-xs font-medium text-rose-700 dark:text-rose-400 hover:bg-rose-500/10"
              :disabled="processingKyc"
              @click="openRejectDialog"
            >
              <ShieldAlert class="h-3.5 w-3.5 text-rose-600 dark:text-rose-400" />
              Reject License
            </Button>
          </div>
        </div>

        <!-- Rejection feedback banner if rejected -->
        <div
          v-if="client.kyc_status === 'rejected' && client.kyc_rejection_reason"
          class="mb-5 rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4 text-sm text-rose-900 dark:text-rose-200"
        >
          <div class="flex items-start gap-2.5">
            <ShieldAlert class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
            <div>
              <p class="font-semibold text-rose-950 dark:text-rose-100">Current Rejection Feedback</p>
              <p class="mt-0.5 text-xs text-rose-800 dark:text-rose-300">{{ client.kyc_rejection_reason }}</p>
            </div>
          </div>
        </div>

        <!-- License Metadata Grid -->
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">License Number</p>
            <p class="mt-0.5 font-mono font-semibold text-slate-900 dark:text-white">{{ client.license_number || '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Expiration Date</p>
            <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">{{ client.license_expiry ? fmtDate(client.license_expiry) : '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Verification Status</p>
            <p class="mt-0.5 font-semibold capitalize text-slate-900 dark:text-white">{{ client.kyc_status || 'Unsubmitted' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Verified At</p>
            <p class="mt-0.5 font-semibold text-slate-900 dark:text-white">{{ client.kyc_verified_at ? fmtDateTime(client.kyc_verified_at) : '—' }}</p>
          </div>
        </div>

        <!-- Document photo thumbnails -->
        <div class="mt-6 border-t border-black/5 dark:border-white/10 pt-5">
          <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            Document Evidence
          </p>

          <div class="grid gap-4 sm:grid-cols-2">
            <!-- Front photo -->
            <div class="rounded-2xl border border-black/5 dark:border-white/10 bg-slate-50/50 dark:bg-white/[0.02] p-4">
              <div class="mb-2.5 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-900 dark:text-white">Front of License</span>
                <span v-if="client.license_front_url" class="text-[11px] text-slate-500">Click to enlarge</span>
              </div>
              <div
                v-if="client.license_front_url"
                class="group relative h-48 cursor-pointer overflow-hidden rounded-xl border border-black/10 dark:border-white/10 bg-slate-900"
                @click="previewImage = { url: client.license_front_url, title: 'License Front - ' + client.name }"
              >
                <img
                  :src="client.license_front_url"
                  alt="License Front"
                  class="h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-85"
                />
                <div class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-xs opacity-0 transition group-hover:opacity-100">
                  <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-3 py-1.5 text-xs font-semibold text-slate-900 shadow-xs">
                    <Eye class="h-3.5 w-3.5" />
                    Enlarge Image
                  </span>
                </div>
              </div>
              <div v-else class="flex h-48 items-center justify-center rounded-xl border border-dashed border-black/15 dark:border-white/15 bg-slate-100/50 dark:bg-white/[0.02] text-xs text-slate-400">
                Front image not submitted
              </div>
            </div>

            <!-- Back photo -->
            <div class="rounded-2xl border border-black/5 dark:border-white/10 bg-slate-50/50 dark:bg-white/[0.02] p-4">
              <div class="mb-2.5 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-900 dark:text-white">Back of License</span>
                <span v-if="client.license_back_url" class="text-[11px] text-slate-500">Click to enlarge</span>
              </div>
              <div
                v-if="client.license_back_url"
                class="group relative h-48 cursor-pointer overflow-hidden rounded-xl border border-black/10 dark:border-white/10 bg-slate-900"
                @click="previewImage = { url: client.license_back_url, title: 'License Back - ' + client.name }"
              >
                <img
                  :src="client.license_back_url"
                  alt="License Back"
                  class="h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-85"
                />
                <div class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-xs opacity-0 transition group-hover:opacity-100">
                  <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-3 py-1.5 text-xs font-semibold text-slate-900 shadow-xs">
                    <Eye class="h-3.5 w-3.5" />
                    Enlarge Image
                  </span>
                </div>
              </div>
              <div v-else class="flex h-48 items-center justify-center rounded-xl border border-dashed border-black/15 dark:border-white/15 bg-slate-100/50 dark:bg-white/[0.02] text-xs text-slate-400">
                Back image not submitted
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Summary metrics cards -->
      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="glass-card p-5">
          <div class="flex items-center justify-between">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Spent</p>
            <div class="rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF] p-2.5">
              <Wallet class="h-5 w-5" />
            </div>
          </div>
          <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ fmtMoney(stats.total_spent) }}</p>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Lifetime client gross expenditure</p>
        </div>

        <div class="glass-card p-5">
          <div class="flex items-center justify-between">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Reservations</p>
            <div class="rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF] p-2.5">
              <CarFront class="h-5 w-5" />
            </div>
          </div>
          <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.total_reservations }}</p>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Completed and active bookings</p>
        </div>

        <div class="glass-card p-5 sm:col-span-2 xl:col-span-1">
          <div class="flex items-center justify-between">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Payments</p>
            <div class="rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF] p-2.5">
              <CreditCard class="h-5 w-5" />
            </div>
          </div>
          <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.total_payments }}</p>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Processed transaction receipts</p>
        </div>
      </section>

      <!-- Reservations table -->
      <section class="glass-card overflow-hidden">
        <div class="flex flex-col justify-between gap-3 border-b border-black/5 dark:border-white/10 px-6 py-4 sm:flex-row sm:items-center">
          <div class="flex items-center gap-3">
            <div class="rounded-xl bg-slate-100 dark:bg-white/10 p-2 text-slate-700 dark:text-slate-300">
              <CalendarDays class="h-4 w-4" />
            </div>
            <div>
              <h2 class="font-semibold text-slate-900 dark:text-white">Reservation History</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">Vehicles booked by this client</p>
            </div>
          </div>
          <span class="w-fit rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1 text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ stats.total_reservations }} total
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[760px] w-full text-left text-sm">
            <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
              <tr>
                <th class="px-6 py-3.5">Reservation</th>
                <th class="px-6 py-3.5">Vehicle</th>
                <th class="px-6 py-3.5">Rental Dates</th>
                <th class="px-6 py-3.5">Total</th>
                <th class="px-6 py-3.5">Status</th>
                <th class="px-6 py-3.5 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-black/5 dark:divide-white/5">
              <tr v-for="r in reservations.data" :key="r.id" class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]">
                <td class="px-6 py-4">
                  <span class="font-mono text-xs font-semibold text-slate-800 dark:text-slate-200">{{ r.reservation_number }}</span>
                </td>
                <td class="px-6 py-4">
                  <p class="font-medium text-slate-900 dark:text-white">
                    {{ r.car ? `${r.car.year} ${r.car.make} ${r.car.model}` : 'Vehicle unavailable' }}
                  </p>
                  <p class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ r.car?.license_plate || '—' }}</p>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                  {{ fmtDate(r.start_date) }} – {{ fmtDate(r.end_date) }}
                </td>
                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">{{ fmtMoney(r.total_amount) }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium" :class="badgeStyle(r.status)">
                    {{ r.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <Link :href="`/admin/reservations/${r.id}`">
                    <Button variant="outline" size="sm" class="h-8 rounded-lg border-black/10 dark:border-white/10 text-xs hover:bg-slate-100 dark:hover:bg-white/10">
                      View
                    </Button>
                  </Link>
                </td>
              </tr>
              <tr v-if="reservations.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center">
                  <CarFront class="mx-auto mb-2 h-8 w-8 text-slate-400" />
                  <p class="font-medium text-slate-900 dark:text-white">No reservations found</p>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">This client has no reservation records yet.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <nav v-if="reservations.links?.length > 3" class="flex flex-wrap gap-2 border-t border-black/5 dark:border-white/10 px-6 py-4">
          <Link
            v-for="(link, i) in reservations.links"
            :key="i"
            :href="link.url || ''"
            :class="[
              'rounded-lg border px-3 py-1.5 text-xs font-medium transition',
              link.active ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]' : 'border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10',
              !link.url && 'pointer-events-none opacity-40',
            ]"
          >
            <span v-html="link.label" />
          </Link>
        </nav>
      </section>

      <!-- Payments table -->
      <section class="glass-card overflow-hidden">
        <div class="flex flex-col justify-between gap-3 border-b border-black/5 dark:border-white/10 px-6 py-4 sm:flex-row sm:items-center">
          <div class="flex items-center gap-3">
            <div class="rounded-xl bg-slate-100 dark:bg-white/10 p-2 text-slate-700 dark:text-slate-300">
              <CreditCard class="h-4 w-4" />
            </div>
            <div>
              <h2 class="font-semibold text-slate-900 dark:text-white">Payment Ledger</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">Transactions associated with this client</p>
            </div>
          </div>
          <span class="w-fit rounded-lg border border-black/5 dark:border-white/10 bg-slate-100 dark:bg-white/5 px-3 py-1 text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ stats.total_payments }} total
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[760px] w-full text-left text-sm">
            <thead class="border-b border-black/5 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] text-xs font-semibold text-slate-600 dark:text-slate-400">
              <tr>
                <th class="px-6 py-3.5">Payment</th>
                <th class="px-6 py-3.5">Reservation</th>
                <th class="px-6 py-3.5">Amount</th>
                <th class="px-6 py-3.5">Method</th>
                <th class="px-6 py-3.5">Status</th>
                <th class="px-6 py-3.5">Processed</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-black/5 dark:divide-white/5">
              <tr v-for="p in payments.data" :key="p.id" class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]">
                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-800 dark:text-slate-200">{{ p.payment_number }}</td>
                <td class="px-6 py-4 font-mono text-xs text-slate-600 dark:text-slate-400">{{ p.reservation?.reservation_number || '—' }}</td>
                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">{{ fmtMoney(p.amount) }}</td>
                <td class="px-6 py-4 capitalize text-slate-600 dark:text-slate-300">{{ p.payment_method.replaceAll('_', ' ') }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium" :class="badgeStyle(p.status)">
                    {{ p.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">{{ fmtDateTime(p.processed_at) }}</td>
              </tr>
              <tr v-if="payments.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center">
                  <CreditCard class="mx-auto mb-2 h-8 w-8 text-slate-400" />
                  <p class="font-medium text-slate-900 dark:text-white">No payments found</p>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No payment records are available for this client.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <nav v-if="payments.links?.length > 3" class="flex flex-wrap gap-2 border-t border-black/5 dark:border-white/10 px-6 py-4">
          <Link
            v-for="(link, i) in payments.links"
            :key="i"
            :href="link.url || ''"
            :class="[
              'rounded-lg border px-3 py-1.5 text-xs font-medium transition',
              link.active ? 'border-[#58779D] bg-[#58779D] text-white dark:border-[#7094BF] dark:bg-[#7094BF]' : 'border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10',
              !link.url && 'pointer-events-none opacity-40',
            ]"
          >
            <span v-html="link.label" />
          </Link>
        </nav>
      </section>
    </main>

    <!-- Reject KYC Dialog -->
    <Dialog v-model:open="showRejectDialog">
      <DialogContent class="glass-card sm:max-w-[480px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-slate-900 dark:text-white">
            <ShieldAlert class="h-5 w-5 text-rose-600 dark:text-rose-400" />
            Reject Driver License
          </DialogTitle>
          <DialogDescription class="text-slate-600 dark:text-slate-400">
            Specify why {{ client.name }}'s driving license is being rejected. This note will be shown to them so they can upload correct documents.
          </DialogDescription>
        </DialogHeader>

        <div class="mt-3 space-y-2">
          <label for="rejection_reason" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
            Rejection Feedback Note *
          </label>
          <textarea
            id="rejection_reason"
            v-model="rejectionReason"
            rows="3"
            class="apple-input w-full p-3 text-sm"
            placeholder="e.g. Front image is blurry and license number is unreadable. Please upload a clear photo."
          ></textarea>
          <p v-if="rejectionError" class="text-xs font-medium text-rose-600 dark:text-rose-400">
            {{ rejectionError }}
          </p>
        </div>

        <DialogFooter class="mt-4 gap-2">
          <DialogClose as-child>
            <Button variant="outline" class="rounded-xl border-black/10 dark:border-white/10">Cancel</Button>
          </DialogClose>
          <Button
            type="button"
            class="rounded-xl bg-rose-600 text-white hover:bg-rose-700"
            :disabled="processingKyc"
            @click="submitRejectKyc"
          >
            {{ processingKyc ? 'Rejecting...' : 'Confirm Rejection' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Image Lightbox Modal -->
    <Dialog :open="!!previewImage" @update:open="(val) => { if (!val) previewImage = null; }">
      <DialogContent class="glass-card max-w-2xl p-4 sm:p-6">
        <DialogHeader class="mb-2">
          <DialogTitle class="text-base text-slate-900 dark:text-white">
            {{ previewImage?.title || 'Document Inspection' }}
          </DialogTitle>
        </DialogHeader>
        <div class="overflow-hidden rounded-2xl bg-black/90">
          <img
            v-if="previewImage?.url"
            :src="previewImage.url"
            :alt="previewImage.title"
            class="max-h-[70vh] w-full object-contain"
          />
        </div>
      </DialogContent>
    </Dialog>

    <!-- Suspend confirmation -->
    <Dialog v-model:open="showSuspendDialog">
      <DialogContent class="glass-card sm:max-w-[440px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-slate-900 dark:text-white">
            <AlertCircle class="h-5 w-5 text-rose-600 dark:text-rose-400" />
            Suspend Client Account?
          </DialogTitle>
          <DialogDescription class="text-slate-600 dark:text-slate-400">
            Are you sure you want to suspend {{ client.name }}? They will not be able to log in until their account is reactivated.
          </DialogDescription>
        </DialogHeader>
        <Alert variant="destructive" class="mt-2">
          <AlertCircle class="h-4 w-4" />
          <AlertDescription>
            This action can be reversed later by an administrator.
          </AlertDescription>
        </Alert>
        <DialogFooter class="mt-4 gap-2">
          <DialogClose as-child>
            <Button variant="outline" class="rounded-xl border-black/10 dark:border-white/10">Cancel</Button>
          </DialogClose>
          <Button
            type="button"
            class="rounded-xl bg-rose-600 text-white hover:bg-rose-700"
            :disabled="processingSuspend"
            @click="suspendClient"
          >
            {{ processingSuspend ? 'Suspending...' : 'Confirm Suspension' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Activate confirmation -->
    <Dialog v-model:open="showActivateDialog">
      <DialogContent class="glass-card sm:max-w-[440px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-slate-900 dark:text-white">
            <ShieldCheck class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
            Reactivate Client Account?
          </DialogTitle>
          <DialogDescription class="text-slate-600 dark:text-slate-400">
            Are you sure you want to reactivate {{ client.name }}? They will be able to log in again.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="mt-4 gap-2">
          <DialogClose as-child>
            <Button variant="outline" class="rounded-xl border-black/10 dark:border-white/10">Cancel</Button>
          </DialogClose>
          <Button
            type="button"
            class="rounded-xl bg-emerald-600 text-white hover:bg-emerald-700"
            :disabled="processingActivate"
            @click="activateClient"
          >
            {{ processingActivate ? 'Activating...' : 'Confirm Activation' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
