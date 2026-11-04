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
            class="fixed inset-0 bg-[#101D35]/70 backdrop-blur-xs transition-opacity"
            @click="!isSimulatingGateway && emit('close')"
        ></div>

        <!-- Modal Container -->
        <div
            class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl transition-all"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 bg-[#101D35] px-6 py-5 text-white">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white">
                        <Lock class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">Secure Checkout</h3>
                        <p class="text-xs text-slate-300">Simulated 256-Bit SSL Payment Gateway</p>
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
            <div class="flex items-center justify-between border-b border-slate-100 bg-[#F4F6FA] px-6 py-3.5">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#58779D]">
                    Total payable now
                </span>
                <span class="font-mono text-xl font-bold text-[#101D35]">
                    {{ formatPrice(amount) }}
                </span>
            </div>

            <!-- Payment Method Tabs -->
            <div class="flex border-b border-slate-200 bg-slate-50/70 p-2">
                <button
                    type="button"
                    @click="activeTab = 'credit_card'"
                    :disabled="isSimulatingGateway"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition sm:text-sm"
                    :class="[
                        activeTab === 'credit_card'
                            ? 'bg-white text-[#101D35] shadow-xs'
                            : 'text-slate-600 hover:text-[#101D35]',
                    ]"
                >
                    <CreditCard class="h-4 w-4" />
                    <span>Card</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'upi'"
                    :disabled="isSimulatingGateway"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition sm:text-sm"
                    :class="[
                        activeTab === 'upi'
                            ? 'bg-white text-[#101D35] shadow-xs'
                            : 'text-slate-600 hover:text-[#101D35]',
                    ]"
                >
                    <QrCode class="h-4 w-4" />
                    <span>UPI / QR</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'cash'"
                    :disabled="isSimulatingGateway"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition sm:text-sm"
                    :class="[
                        activeTab === 'cash'
                            ? 'bg-white text-[#101D35] shadow-xs'
                            : 'text-slate-600 hover:text-[#101D35]',
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
                        <span class="text-xs font-medium text-slate-500">Demo sandbox mode active</span>
                        <button
                            type="button"
                            @click="autoFillTestCard"
                            class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-[#58779D] transition hover:bg-slate-200 hover:text-[#101D35]"
                        >
                            <Sparkles class="h-3.5 w-3.5" />
                            Auto-fill Test Card
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Cardholder Name</label>
                            <input
                                v-model="cardName"
                                type="text"
                                placeholder="Name on card"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#58779D] focus:outline-none focus:ring-2 focus:ring-[#58779D]/20"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Card Number</label>
                            <div class="relative">
                                <input
                                    v-model="cardNumber"
                                    type="text"
                                    placeholder="4242 •••• •••• 4242"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#58779D] focus:outline-none focus:ring-2 focus:ring-[#58779D]/20"
                                />
                                <div class="absolute right-3 top-2.5 flex items-center gap-1.5 text-xs font-bold text-slate-400">
                                    <span>VISA</span>
                                    <span>MC</span>
                                    <span>RuPay</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Expires (MM/YY)</label>
                                <input
                                    v-model="cardExpiry"
                                    type="text"
                                    placeholder="12/28"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#58779D] focus:outline-none focus:ring-2 focus:ring-[#58779D]/20"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">CVV / CVC</label>
                                <input
                                    v-model="cardCvv"
                                    type="password"
                                    maxlength="4"
                                    placeholder="•••"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#58779D] focus:outline-none focus:ring-2 focus:ring-[#58779D]/20"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. UPI / QR Code -->
                <div v-else-if="activeTab === 'upi'" class="space-y-4">
                    <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-[#F4F6FA] p-5 text-center">
                        <!-- Simulated QR Code SVG -->
                        <div class="relative flex h-36 w-36 items-center justify-center rounded-xl border-2 border-slate-800 bg-white p-2 shadow-xs">
                            <svg class="h-full w-full text-slate-900" viewBox="0 0 100 100" fill="currentColor">
                                <!-- Top-left corner finder -->
                                <rect x="5" y="5" width="30" height="30" rx="3" fill="#101D35" />
                                <rect x="10" y="10" width="20" height="20" fill="white" />
                                <rect x="15" y="15" width="10" height="10" fill="#101D35" />
                                <!-- Top-right corner finder -->
                                <rect x="65" y="5" width="30" height="30" rx="3" fill="#101D35" />
                                <rect x="70" y="10" width="20" height="20" fill="white" />
                                <rect x="75" y="15" width="10" height="10" fill="#101D35" />
                                <!-- Bottom-left corner finder -->
                                <rect x="5" y="65" width="30" height="30" rx="3" fill="#101D35" />
                                <rect x="10" y="70" width="20" height="20" fill="white" />
                                <rect x="15" y="75" width="10" height="10" fill="#101D35" />
                                <!-- Simulated QR patterns -->
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
                            <!-- Mini brand badge in center -->
                            <div class="absolute inset-0 m-auto flex h-7 w-7 items-center justify-center rounded-md bg-[#101D35] text-[10px] font-black tracking-tight text-white shadow-xs">
                                ₹
                            </div>
                        </div>

                        <p class="mt-3 text-xs font-medium text-slate-700">
                            Scan with GPay, PhonePe, Paytm, or BHIM
                        </p>
                        <div class="mt-2 flex items-center gap-2 text-[11px] font-semibold text-slate-500">
                            <span class="rounded bg-white px-2 py-0.5 border border-slate-200">GPay</span>
                            <span class="rounded bg-white px-2 py-0.5 border border-slate-200">PhonePe</span>
                            <span class="rounded bg-white px-2 py-0.5 border border-slate-200">Paytm</span>
                            <span class="rounded bg-white px-2 py-0.5 border border-slate-200">BHIM</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-700">Or enter UPI ID / VPA</label>
                            <button
                                type="button"
                                @click="autoFillTestUpi"
                                class="text-xs font-semibold text-[#58779D] hover:text-[#101D35]"
                            >
                                Auto-fill Demo VPA
                            </button>
                        </div>
                        <input
                            v-model="upiId"
                            type="text"
                            placeholder="username@okhdfcbank"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#58779D] focus:outline-none focus:ring-2 focus:ring-[#58779D]/20"
                        />
                    </div>
                </div>

                <!-- 3. Cash on Pickup -->
                <div v-else class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-[#F4F6FA] p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#101D35] text-white">
                                <Banknote class="h-5 w-5" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-[#101D35]">
                                    Pay at Vehicle Handover
                                </h4>
                                <p class="mt-1 text-xs leading-5 text-slate-600">
                                    No upfront payment charged today. You can pay with Cash, Debit/Credit Card, or UPI POS when collecting the vehicle keys at our desk.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-200 pt-3">
                            <div class="flex items-center gap-2 text-xs font-medium text-emerald-800">
                                <Check class="h-4 w-4 text-emerald-600" />
                                <span>Immediate booking confirmation guaranteed</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simulation Gateway Overlay / Status -->
                <div
                    v-if="isSimulatingGateway"
                    class="mt-4 flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-slate-900 p-6 text-center text-white"
                >
                    <svg class="h-8 w-8 animate-spin text-[#C5D0DE]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <p class="mt-3 text-sm font-bold text-white">
                        {{ gatewayStepMessage }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Please do not refresh or close this browser window.
                    </p>
                </div>

                <!-- Pay Button -->
                <div v-else class="mt-6 space-y-3">
                    <button
                        type="button"
                        :disabled="!canSubmit"
                        @click="handlePayment"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D35] px-6 py-3.5 font-bold text-white transition hover:bg-[#263F60] focus:outline-none focus:ring-2 focus:ring-[#58779D] focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500"
                    >
                        <Shield class="h-4 w-4" />
                        <span>
                            {{ activeTab === 'cash' ? 'Confirm Booking (Pay on Pickup)' : `Authorize & Pay ${formatPrice(amount)}` }}
                        </span>
                    </button>

                    <div class="flex items-center justify-center gap-2 text-center text-xs text-slate-500">
                        <Lock class="h-3.5 w-3.5 text-[#58779D]" />
                        <span>End-to-end encrypted • Instant booking receipt</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
