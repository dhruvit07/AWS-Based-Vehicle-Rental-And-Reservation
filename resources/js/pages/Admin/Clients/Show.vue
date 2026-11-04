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

const statusStyle = computed(() => props.client.is_active
  ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
  : 'bg-red-50 text-red-700 ring-red-200');

const kycBadge = computed(() => {
  const status = (props.client.kyc_status || 'unsubmitted').toLowerCase();
  switch (status) {
    case 'verified':
      return {
        label: 'Verified Driver',
        class: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        dot: 'bg-emerald-500',
      };
    case 'pending':
      return {
        label: 'Under Review',
        class: 'bg-amber-50 text-amber-700 ring-amber-200',
        dot: 'bg-amber-500',
      };
    case 'rejected':
      return {
        label: 'Rejected',
        class: 'bg-rose-50 text-rose-700 ring-rose-200',
        dot: 'bg-rose-500',
      };
    default:
      return {
        label: 'Not Submitted',
        class: 'bg-slate-100 text-slate-700 ring-slate-200',
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
    return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
  }
  if (['pending', 'processing', 'in_progress'].includes(value)) {
    return 'bg-amber-50 text-amber-700 ring-amber-200';
  }
  if (['cancelled', 'canceled', 'failed', 'rejected', 'refunded', 'suspended'].includes(value)) {
    return 'bg-red-50 text-red-700 ring-red-200';
  }
  return 'bg-slate-100 text-slate-700 ring-slate-200';
}
</script>

<template>
  <Head :title="`Client ${client.name}`" />

  <AdminLayout>
    <main class="min-h-screen space-y-7 bg-[#F4F6FA] p-4 sm:p-6 lg:p-8">
      <!-- Page heading -->
      <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="flex items-start gap-3">
          <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#101D35] text-white">
            <UserRound class="size-6" />
          </div>
          <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-[#58779D]">
              Client Management
            </p>
            <h1 class="text-2xl font-bold tracking-tight text-[#101D35] sm:text-3xl">
              {{ client.name }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">{{ client.email }}</p>
            <span
              class="mt-3 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
              :class="statusStyle"
            >
              <span class="size-2 rounded-full" :class="client.is_active ? 'bg-emerald-500' : 'bg-red-500'" />
              {{ client.is_active ? 'Active Account' : 'Suspended Account' }}
            </span>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Button
            v-if="client.is_active"
            class="bg-red-600 text-white hover:bg-red-700"
            @click="showSuspendDialog = true"
          >
            Suspend User
          </Button>
          <Button
            v-else
            class="bg-emerald-600 text-white hover:bg-emerald-700"
            @click="showActivateDialog = true"
          >
            Activate User
          </Button>

          <Link :href="index()">
            <Button variant="outline" class="border-slate-300 bg-white text-[#263F60] hover:bg-slate-100">
              <ArrowLeft class="mr-2 size-4" />
              Back to Clients
            </Button>
          </Link>
        </div>
      </div>

      <!-- Client information -->
      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex items-center gap-2">
          <div class="rounded-lg bg-[#E9EEF5] p-2 text-[#263F60]">
            <ShieldCheck class="size-5" />
          </div>
          <div>
            <h2 class="font-semibold text-[#101D35]">Account Information</h2>
            <p class="text-xs text-slate-500">Client profile and account status</p>
          </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Client ID</p>
            <p class="mt-1 font-semibold text-[#101D35]">#{{ client.id }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Full Name</p>
            <p class="mt-1 font-semibold text-[#101D35]">{{ client.name }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Email Address</p>
            <p class="mt-1 break-all font-semibold text-[#101D35]">{{ client.email }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Account Created</p>
            <p class="mt-1 font-semibold text-[#101D35]">
              {{ client.created_at ? fmtDate(client.created_at) : '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Account Status</p>
            <p class="mt-1 font-semibold text-[#101D35]">{{ client.is_active ? 'Active' : 'Suspended' }}</p>
          </div>
        </div>
      </section>

      <!-- Driver Verification & License -->
      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center">
          <div class="flex items-center gap-3">
            <div class="rounded-lg bg-[#E9EEF5] p-2 text-[#263F60]">
              <ShieldCheck class="size-5" />
            </div>
            <div>
              <div class="flex items-center gap-2.5">
                <h2 class="font-semibold text-[#101D35]">Driver Verification & License</h2>
                <span
                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                  :class="kycBadge.class"
                >
                  <span class="size-1.5 rounded-full" :class="kycBadge.dot" />
                  {{ kycBadge.label }}
                </span>
              </div>
              <p class="text-xs text-slate-500">Government driving credentials inspection and verification</p>
            </div>
          </div>

          <!-- Review Controls -->
          <div class="flex flex-wrap items-center gap-2">
            <Button
              v-if="client.kyc_status !== 'verified'"
              class="gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700"
              :disabled="processingKyc"
              @click="approveKyc"
            >
              <CheckCircle2 class="size-4" />
              {{ processingKyc ? 'Processing...' : 'Approve License' }}
            </Button>
            <Button
              v-else
              variant="outline"
              class="gap-1.5 border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100"
              :disabled="processingKyc"
              @click="approveKyc"
            >
              <CheckCircle2 class="size-4 text-emerald-600" />
              Re-approve
            </Button>

            <Button
              variant="outline"
              class="gap-1.5 border-rose-300 text-rose-700 hover:bg-rose-50"
              :disabled="processingKyc"
              @click="openRejectDialog"
            >
              <ShieldAlert class="size-4 text-rose-600" />
              Reject License
            </Button>
          </div>
        </div>

        <!-- Rejection feedback banner if rejected -->
        <div
          v-if="client.kyc_status === 'rejected' && client.kyc_rejection_reason"
          class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"
        >
          <div class="flex items-start gap-2.5">
            <ShieldAlert class="mt-0.5 size-4 shrink-0 text-rose-600" />
            <div>
              <p class="font-semibold text-rose-950">Current Rejection Feedback</p>
              <p class="mt-1 text-rose-800">{{ client.kyc_rejection_reason }}</p>
            </div>
          </div>
        </div>

        <!-- License Metadata Grid -->
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">License Number</p>
            <p class="mt-1 font-mono font-semibold text-[#101D35]">{{ client.license_number || '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Expiration Date</p>
            <p class="mt-1 font-semibold text-[#101D35]">{{ client.license_expiry ? fmtDate(client.license_expiry) : '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Verification Status</p>
            <p class="mt-1 font-semibold capitalize text-[#101D35]">{{ client.kyc_status || 'Unsubmitted' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Verified At</p>
            <p class="mt-1 font-semibold text-[#101D35]">{{ client.kyc_verified_at ? fmtDateTime(client.kyc_verified_at) : '—' }}</p>
          </div>
        </div>

        <!-- Document photo thumbnails -->
        <div class="mt-6 border-t border-slate-100 pt-5">
          <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-[#58779D]">
            License Documents
          </p>

          <div class="grid gap-4 sm:grid-cols-2">
            <!-- Front photo -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3.5">
              <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-semibold text-[#101D35]">Front of License</span>
                <span v-if="client.license_front_url" class="text-[11px] text-slate-500">Click to enlarge</span>
              </div>
              <div
                v-if="client.license_front_url"
                class="group relative h-48 cursor-pointer overflow-hidden rounded-lg border border-slate-200 bg-slate-900"
                @click="previewImage = { url: client.license_front_url, title: 'License Front - ' + client.name }"
              >
                <img
                  :src="client.license_front_url"
                  alt="License Front"
                  class="h-full w-full object-cover transition duration-200 group-hover:scale-102 group-hover:opacity-90"
                />
                <div class="absolute inset-0 flex items-center justify-center bg-[#101D35]/30 opacity-0 transition group-hover:opacity-100">
                  <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-3 py-1.5 text-xs font-semibold text-[#101D35] shadow-xs">
                    <Eye class="size-3.5" />
                    Enlarge Image
                  </span>
                </div>
              </div>
              <div v-else class="flex h-48 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white text-xs text-slate-400">
                Front image not submitted
              </div>
            </div>

            <!-- Back photo -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3.5">
              <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-semibold text-[#101D35]">Back of License</span>
                <span v-if="client.license_back_url" class="text-[11px] text-slate-500">Click to enlarge</span>
              </div>
              <div
                v-if="client.license_back_url"
                class="group relative h-48 cursor-pointer overflow-hidden rounded-lg border border-slate-200 bg-slate-900"
                @click="previewImage = { url: client.license_back_url, title: 'License Back - ' + client.name }"
              >
                <img
                  :src="client.license_back_url"
                  alt="License Back"
                  class="h-full w-full object-cover transition duration-200 group-hover:scale-102 group-hover:opacity-90"
                />
                <div class="absolute inset-0 flex items-center justify-center bg-[#101D35]/30 opacity-0 transition group-hover:opacity-100">
                  <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-3 py-1.5 text-xs font-semibold text-[#101D35] shadow-xs">
                    <Eye class="size-3.5" />
                    Enlarge Image
                  </span>
                </div>
              </div>
              <div v-else class="flex h-48 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white text-xs text-slate-400">
                Back image not submitted
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Summary cards -->
      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-500">Total Spent</p>
            <div class="rounded-xl bg-[#E9EEF5] p-3 text-[#263F60]">
              <Wallet class="size-5" />
            </div>
          </div>
          <p class="mt-4 text-2xl font-bold text-[#101D35]">{{ fmtMoney(stats.total_spent) }}</p>
          <p class="mt-1 text-xs text-slate-500">Total recorded client spending</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-500">Reservations</p>
            <div class="rounded-xl bg-[#E9EEF5] p-3 text-[#263F60]">
              <CarFront class="size-5" />
            </div>
          </div>
          <p class="mt-4 text-2xl font-bold text-[#101D35]">{{ stats.total_reservations }}</p>
          <p class="mt-1 text-xs text-slate-500">Total client reservations</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 xl:col-span-1">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-500">Payments</p>
            <div class="rounded-xl bg-[#E9EEF5] p-3 text-[#263F60]">
              <CreditCard class="size-5" />
            </div>
          </div>
          <p class="mt-4 text-2xl font-bold text-[#101D35]">{{ stats.total_payments }}</p>
          <p class="mt-1 text-xs text-slate-500">Total recorded payments</p>
        </div>
      </section>

      <!-- Reservations -->
      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center">
          <div class="flex items-center gap-3">
            <div class="rounded-xl bg-[#101D35] p-2.5 text-white">
              <CalendarDays class="size-5" />
            </div>
            <div>
              <h2 class="font-semibold text-[#101D35]">Reservation History</h2>
              <p class="text-sm text-slate-500">Vehicles booked by this client</p>
            </div>
          </div>
          <span class="w-fit rounded-full bg-[#E9EEF5] px-3 py-1 text-xs font-semibold text-[#263F60]">
            {{ stats.total_reservations }} total
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[760px] w-full text-left text-sm">
            <thead class="bg-[#F4F6FA] text-xs uppercase tracking-wider text-[#58779D]">
              <tr>
                <th class="px-5 py-4 font-semibold">Reservation</th>
                <th class="px-5 py-4 font-semibold">Vehicle</th>
                <th class="px-5 py-4 font-semibold">Rental Dates</th>
                <th class="px-5 py-4 font-semibold">Total</th>
                <th class="px-5 py-4 font-semibold">Status</th>
                <th class="px-5 py-4 text-right font-semibold">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="r in reservations.data" :key="r.id" class="transition-colors hover:bg-[#F8FAFD]">
                <td class="px-5 py-4">
                  <span class="font-semibold text-[#263F60]">{{ r.reservation_number }}</span>
                </td>
                <td class="px-5 py-4">
                  <p class="font-medium text-[#101D35]">
                    {{ r.car ? `${r.car.year} ${r.car.make} ${r.car.model}` : 'Vehicle unavailable' }}
                  </p>
                  <p class="mt-1 text-xs text-slate-500">{{ r.car?.license_plate || '—' }}</p>
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                  {{ fmtDate(r.start_date) }}<br />
                  <span class="text-xs text-slate-400">to {{ fmtDate(r.end_date) }}</span>
                </td>
                <td class="px-5 py-4 font-semibold text-[#101D35]">{{ fmtMoney(r.total_amount) }}</td>
                <td class="px-5 py-4">
                  <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="badgeStyle(r.status)">
                    {{ r.status }}
                  </span>
                </td>
                <td class="px-5 py-4 text-right">
                  <Link :href="`/admin/reservations/${r.id}`">
                    <Button variant="outline" size="sm" class="border-slate-300 text-[#263F60] hover:bg-[#E9EEF5]">
                      View
                    </Button>
                  </Link>
                </td>
              </tr>
              <tr v-if="reservations.data.length === 0">
                <td colspan="6" class="px-5 py-12 text-center">
                  <CarFront class="mx-auto mb-3 size-8 text-slate-300" />
                  <p class="font-medium text-[#263F60]">No reservations found</p>
                  <p class="mt-1 text-sm text-slate-500">This client has no reservation records yet.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <nav v-if="reservations.links?.length > 3" class="flex flex-wrap gap-2 border-t border-slate-200 px-5 py-4">
          <Link
            v-for="(link, i) in reservations.links"
            :key="i"
            :href="link.url || ''"
            :class="[
              'rounded-lg px-3 py-2 text-sm transition-colors',
              link.active ? 'bg-[#101D35] font-semibold text-white' : 'bg-[#F4F6FA] text-[#263F60] hover:bg-[#E9EEF5]',
              !link.url && 'pointer-events-none opacity-40',
            ]"
          >
            <span v-html="link.label" />
          </Link>
        </nav>
      </section>

      <!-- Payments -->
      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center">
          <div class="flex items-center gap-3">
            <div class="rounded-xl bg-[#101D35] p-2.5 text-white">
              <CreditCard class="size-5" />
            </div>
            <div>
              <h2 class="font-semibold text-[#101D35]">Payment History</h2>
              <p class="text-sm text-slate-500">Transactions associated with this client</p>
            </div>
          </div>
          <span class="w-fit rounded-full bg-[#E9EEF5] px-3 py-1 text-xs font-semibold text-[#263F60]">
            {{ stats.total_payments }} total
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[760px] w-full text-left text-sm">
            <thead class="bg-[#F4F6FA] text-xs uppercase tracking-wider text-[#58779D]">
              <tr>
                <th class="px-5 py-4 font-semibold">Payment</th>
                <th class="px-5 py-4 font-semibold">Reservation</th>
                <th class="px-5 py-4 font-semibold">Amount</th>
                <th class="px-5 py-4 font-semibold">Method</th>
                <th class="px-5 py-4 font-semibold">Status</th>
                <th class="px-5 py-4 font-semibold">Processed</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="p in payments.data" :key="p.id" class="transition-colors hover:bg-[#F8FAFD]">
                <td class="px-5 py-4 font-semibold text-[#263F60]">{{ p.payment_number }}</td>
                <td class="px-5 py-4 text-slate-600">{{ p.reservation?.reservation_number || '—' }}</td>
                <td class="px-5 py-4 font-semibold text-[#101D35]">{{ fmtMoney(p.amount) }}</td>
                <td class="px-5 py-4 capitalize text-slate-600">{{ p.payment_method.replaceAll('_', ' ') }}</td>
                <td class="px-5 py-4">
                  <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="badgeStyle(p.status)">
                    {{ p.status }}
                  </span>
                </td>
                <td class="px-5 py-4 text-slate-600">{{ fmtDateTime(p.processed_at) }}</td>
              </tr>
              <tr v-if="payments.data.length === 0">
                <td colspan="6" class="px-5 py-12 text-center">
                  <CreditCard class="mx-auto mb-3 size-8 text-slate-300" />
                  <p class="font-medium text-[#263F60]">No payments found</p>
                  <p class="mt-1 text-sm text-slate-500">No payment records are available for this client.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <nav v-if="payments.links?.length > 3" class="flex flex-wrap gap-2 border-t border-slate-200 px-5 py-4">
          <Link
            v-for="(link, i) in payments.links"
            :key="i"
            :href="link.url || ''"
            :class="[
              'rounded-lg px-3 py-2 text-sm transition-colors',
              link.active ? 'bg-[#101D35] font-semibold text-white' : 'bg-[#F4F6FA] text-[#263F60] hover:bg-[#E9EEF5]',
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
      <DialogContent class="sm:max-w-[480px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-[#101D35]">
            <ShieldAlert class="size-5 text-rose-600" />
            Reject Driver License
          </DialogTitle>
          <DialogDescription>
            Specify why {{ client.name }}'s driving license is being rejected. This note will be shown to them so they can upload correct documents.
          </DialogDescription>
        </DialogHeader>

        <div class="mt-3 space-y-2">
          <label for="rejection_reason" class="text-xs font-semibold uppercase tracking-wider text-slate-600">
            Rejection Feedback Note *
          </label>
          <textarea
            id="rejection_reason"
            v-model="rejectionReason"
            rows="3"
            class="w-full rounded-xl border border-slate-300 p-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
            placeholder="e.g. Front image is blurry and license number is unreadable. Please upload a clear photo."
          ></textarea>
          <p v-if="rejectionError" class="text-xs font-medium text-rose-600">
            {{ rejectionError }}
          </p>
        </div>

        <DialogFooter class="mt-4">
          <DialogClose as-child>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button
            type="button"
            class="bg-rose-600 text-white hover:bg-rose-700"
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
      <DialogContent class="max-w-2xl p-4 sm:p-6">
        <DialogHeader class="mb-2">
          <DialogTitle class="text-base text-[#101D35]">
            {{ previewImage?.title || 'Document Inspection' }}
          </DialogTitle>
        </DialogHeader>
        <div class="overflow-hidden rounded-xl bg-slate-900">
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
      <DialogContent class="sm:max-w-[440px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-[#101D35]">
            <AlertCircle class="size-5 text-red-600" />
            Suspend Client Account?
          </DialogTitle>
          <DialogDescription>
            Are you sure you want to suspend {{ client.name }}? They will not be able to log in until their account is reactivated.
          </DialogDescription>
        </DialogHeader>
        <Alert variant="destructive" class="mt-2">
          <AlertCircle class="size-4" />
          <AlertDescription>
            This action can be reversed later by an administrator.
          </AlertDescription>
        </Alert>
        <DialogFooter class="mt-4">
          <DialogClose as-child>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button
            type="button"
            class="bg-red-600 text-white hover:bg-red-700"
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
      <DialogContent class="sm:max-w-[440px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-[#101D35]">
            <ShieldCheck class="size-5 text-emerald-600" />
            Reactivate Client Account?
          </DialogTitle>
          <DialogDescription>
            Are you sure you want to reactivate {{ client.name }}? They will be able to log in again.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="mt-4">
          <DialogClose as-child>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button
            type="button"
            class="bg-emerald-600 text-white hover:bg-emerald-700"
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
