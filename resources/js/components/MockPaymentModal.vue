<script setup lang="ts">
import {
    Banknote,
    Check,
    CreditCard,
    Lock,
    QrCode,
    Shield,
    Sparkles,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    amount: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', data: { payment_method: 'credit_card' | 'upi' | 'cash'; transaction_id: string }): void;
}>();

type TabType = 'credit_card' | 'upi' | 'cash';
const activeTab = ref<TabType>('credit_card');

// Card Form
const cardName = ref('');
const cardNumber = ref('');
const cardExpiry = ref('');
const cardCvv = ref('');

// UPI Form
const upiId = ref('');

// Processing Simulation
const isSimulatingGateway = ref(false);
const gatewayStepMessage = ref('');

const formatPrice = (amount: number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(amount);

const autoFillTestCard = () => {
    cardName.value = 'Johnathan Doe';
    cardNumber.value = '4242 •••• •••• 4242';
    cardExpiry.value = '12/28';
    cardCvv.value = '888';
};

const autoFillTestUpi = () => {
    upiId.value = 'client@okhdfcbank';
};

const canSubmit = computed(() => {
    if (activeTab.value === 'credit_card') {
        return Boolean(cardName.value && cardNumber.value && cardExpiry.value && cardCvv.value);
    }
    if (activeTab.value === 'upi') {
        return Boolean(upiId.value && upiId.value.includes('@'));
    }
    return true; // Cash on pickup
});

const handlePayment = () => {
    if (!canSubmit.value || isSimulatingGateway.value) return;

    isSimulatingGateway.value = true;
    gatewayStepMessage.value = 'Connecting to Secure Banking Gateway...';

    setTimeout(() => {
        gatewayStepMessage.value = 'Authorizing transaction with issuing bank...';
    }, 600);

    setTimeout(() => {
        const randomTxn = 'TXN-' + Math.random().toString(36).substring(2, 10).toUpperCase();
        isSimulatingGateway.value = false;
        emit('submit', {
            payment_method: activeTab.value,
            transaction_id: randomTxn,
        });
    }, 1200);
};

// Reset state when modal opens
watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            isSimulatingGateway.value = false;
            gatewayStepMessage.value = '';
            if (!cardNumber.value) {
                autoFillTestCard();
            }
            if (!upiId.value) {
                upiId.value = 'client@okhdfcbank';
            }
        }
    }
);
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-slate-950/70 backdrop-blur-md transition-opacity"
            @click="!isSimulatingGateway && emit('close')"
        ></div>

        <!-- Modal Container -->
        <div
            class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-black/10 bg-white text-slate-900 shadow-2xl transition-all dark:border-white/10 dark:bg-[#0F1724] dark:text-white"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-black/10 bg-[#101D35] px-6 py-5 text-white dark:border-white/10 dark:bg-[#070A0F]">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white">
                        <Lock class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Secure Checkout</h3>
                        <p class="text-xs text-slate-300">Simulated 256-Bit SSL Payment Console</p>
                    </div>
                </div>

                <button
                    type="button"
                    :disabled="isSimulatingGateway"
                    @click="emit('close')"
                    class="rounded-lg p-1.5 text-slate-300 transition hover:bg-white/10 hover:text-white disabled:opacity-50"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Total Banner -->
            <div class="flex items-center justify-between border-b border-slate-200/80 bg-slate-50 px-6 py-3.5 dark:border-white/10 dark:bg-white/5">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
                    Total payable now
                </span>
                <span class="font-mono text-lg font-bold text-slate-950 dark:text-white">
                    {{ formatPrice(amount) }}
                </span>
            </div>

            <!-- Payment Method Tabs -->
            <div class="flex border-b border-slate-200/80 bg-slate-100/60 p-2 dark:border-white/10 dark:bg-black/20">
                <button
                    type="button"
                    @click="activeTab = 'credit_card'"
                    :disabled="isSimulatingGateway"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl py-2 text-xs font-bold transition sm:text-xs"
                    :class="[
                        activeTab === 'credit_card'
                            ? 'bg-white text-slate-950 shadow-xs dark:bg-white/15 dark:text-white'
                            : 'text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white',
                    ]"
                >
                    <CreditCard class="h-4 w-4" />
                    <span>Card</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'upi'"
                    :disabled="isSimulatingGateway"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl py-2 text-xs font-bold transition sm:text-xs"
                    :class="[
                        activeTab === 'upi'
                            ? 'bg-white text-slate-950 shadow-xs dark:bg-white/15 dark:text-white'
                            : 'text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white',
                    ]"
                >
                    <QrCode class="h-4 w-4" />
                    <span>UPI / QR</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'cash'"
                    :disabled="isSimulatingGateway"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl py-2 text-xs font-bold transition sm:text-xs"
                    :class="[
                        activeTab === 'cash'
                            ? 'bg-white text-slate-950 shadow-xs dark:bg-white/15 dark:text-white'
                            : 'text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white',
                    ]"
                >
                    <Banknote class="h-4 w-4" />
                    <span>Cash on Pickup</span>
                </button>
            </div>

            <!-- Tab Body -->
            <div class="p-6">
                <!-- 1. Credit / Debit Card -->
                <div v-if="activeTab === 'credit_card'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Sandbox environment active</span>
                        <button
                            type="button"
                            @click="autoFillTestCard"
                            class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-[#58779D] transition hover:bg-slate-200 dark:bg-white/10 dark:text-[#7094BF] dark:hover:bg-white/15"
                        >
                            <Sparkles class="h-3 w-3" />
                            Auto-fill Test Card
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Cardholder Name</label>
                            <input
                                v-model="cardName"
                                type="text"
                                placeholder="Name on card"
                                class="apple-input w-full px-3.5 py-2 text-xs"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Card Number</label>
                            <div class="relative">
                                <input
                                    v-model="cardNumber"
                                    type="text"
                                    placeholder="4242 •••• •••• 4242"
                                    class="apple-input w-full px-3.5 py-2 font-mono text-xs"
                                />
                                <div class="absolute right-3 top-2 flex items-center gap-1 text-[10px] font-bold text-slate-400">
                                    <span>VISA</span>
                                    <span>MC</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Expiry (MM/YY)</label>
                                <input
                                    v-model="cardExpiry"
                                    type="text"
                                    placeholder="12/28"
                                    class="apple-input w-full px-3.5 py-2 font-mono text-xs"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">CVV</label>
                                <input
                                    v-model="cardCvv"
                                    type="password"
                                    maxlength="4"
                                    placeholder="•••"
                                    class="apple-input w-full px-3.5 py-2 font-mono text-xs"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. UPI / QR Code -->
                <div v-else-if="activeTab === 'upi'" class="space-y-4">
                    <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50 p-5 text-center dark:border-white/10 dark:bg-white/5">
                        <div class="relative flex h-32 w-32 items-center justify-center rounded-xl border border-slate-300 bg-white p-2 shadow-xs dark:border-white/20">
                            <svg class="h-full w-full text-slate-900" viewBox="0 0 100 100" fill="currentColor">
                                <rect x="5" y="5" width="30" height="30" rx="3" fill="#101D35" />
                                <rect x="10" y="10" width="20" height="20" fill="white" />
                                <rect x="15" y="15" width="10" height="10" fill="#101D35" />
                                <rect x="65" y="5" width="30" height="30" rx="3" fill="#101D35" />
                                <rect x="70" y="10" width="20" height="20" fill="white" />
                                <rect x="75" y="15" width="10" height="10" fill="#101D35" />
                                <rect x="5" y="65" width="30" height="30" rx="3" fill="#101D35" />
                                <rect x="10" y="70" width="20" height="20" fill="white" />
                                <rect x="15" y="75" width="10" height="10" fill="#101D35" />
                                <rect x="42" y="10" width="6" height="6" fill="#101D35" />
                                <rect x="52" y="10" width="6" height="6" fill="#101D35" />
                                <rect x="42" y="24" width="16" height="6" fill="#101D35" />
                                <rect x="10" y="45" width="6" height="12" fill="#101D35" />
                                <rect x="22" y="45" width="12" height="6" fill="#101D35" />
                                <rect x="42" y="42" width="16" height="16" rx="2" fill="#58779D" />
                                <rect x="65" y="45" width="25" height="6" fill="#101D35" />
                                <rect x="65" y="58" width="10" height="6" fill="#101D35" />
                                <rect x="80" y="58" width="10" height="6" fill="#101D35" />
                                <rect x="42" y="68" width="6" height="22" fill="#101D35" />
                                <rect x="55" y="68" width="16" height="6" fill="#101D35" />
                                <rect x="78" y="75" width="12" height="15" fill="#101D35" />
                            </svg>
                            <div class="absolute inset-0 m-auto flex h-6 w-6 items-center justify-center rounded-md bg-[#101D35] text-[10px] font-bold text-white shadow-xs">
                                ₹
                            </div>
                        </div>

                        <p class="mt-3 text-xs font-medium text-slate-700 dark:text-slate-300">
                            Scan with GPay, PhonePe, Paytm, or BHIM
                        </p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">UPI ID / Virtual Address</label>
                            <button
                                type="button"
                                @click="autoFillTestUpi"
                                class="text-xs font-semibold text-[#58779D] hover:underline dark:text-[#7094BF]"
                            >
                                Auto-fill Demo VPA
                            </button>
                        </div>
                        <input
                            v-model="upiId"
                            type="text"
                            placeholder="username@okhdfcbank"
                            class="apple-input w-full px-3.5 py-2 font-mono text-xs"
                        />
                    </div>
                </div>

                <!-- 3. Cash on Pickup -->
                <div v-else class="space-y-4">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#101D35] text-white dark:bg-white dark:text-slate-950">
                                <Banknote class="h-5 w-5" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-950 dark:text-white">
                                    Pay at Vehicle Handover
                                </h4>
                                <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                                    Zero upfront deduction today. Settle via cash, card, or UPI POS upon inspecting and receiving the keys.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-200/80 pt-3 dark:border-white/10">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                <Check class="h-4 w-4" />
                                <span>Guaranteed reservation locking</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gateway Simulation Status -->
                <div
                    v-if="isSimulatingGateway"
                    class="mt-4 flex flex-col items-center justify-center rounded-xl border border-white/10 bg-slate-950 p-6 text-center text-white"
                >
                    <span class="h-7 w-7 animate-spin rounded-full border-2 border-white/20 border-t-white"></span>
                    <p class="mt-3 text-xs font-bold text-white">
                        {{ gatewayStepMessage }}
                    </p>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Please do not refresh or navigate away.
                    </p>
                </div>

                <!-- Action Button -->
                <div v-else class="mt-6 space-y-3">
                    <button
                        type="button"
                        :disabled="!canSubmit"
                        @click="handlePayment"
                        class="apple-btn-primary flex w-full items-center justify-center gap-2 py-3 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Shield class="h-4 w-4" />
                        <span>
                            {{ activeTab === 'cash' ? 'Confirm Booking (Pay on Pickup)' : `Authorize & Pay ${formatPrice(amount)}` }}
                        </span>
                    </button>

                    <div class="flex items-center justify-center gap-1.5 text-center text-xs text-slate-500 dark:text-slate-400">
                        <Lock class="h-3 w-3 text-[#58779D] dark:text-[#7094BF]" />
                        <span>Encrypted • Instant reservation voucher issued</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
