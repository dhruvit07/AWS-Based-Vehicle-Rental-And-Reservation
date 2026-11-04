<script setup lang="ts">
import { CheckCircle2, Loader2, Tag, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

export interface AppliedCoupon {
    code: string;
    type: 'percentage' | 'fixed';
    value: number;
    discount_amount: number;
    message: string;
}

const props = defineProps<{
    subtotal: number;
    appliedCoupon: AppliedCoupon | null;
}>();

const emit = defineEmits<{
    (e: 'apply', coupon: AppliedCoupon): void;
    (e: 'remove'): void;
}>();

const inputCode = ref('');
const isLoading = ref(false);
const errorMessage = ref<string | null>(null);

const demoCodes = [
    { code: 'COLLEGE20', label: 'COLLEGE20 (20% off)' },
    { code: 'FLAT500', label: 'FLAT500 (₹500 off)' },
    { code: 'FIRSTDRIVE', label: 'FIRSTDRIVE (15% off)' },
];

const getMetaCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return meta?.content ?? '';
};

const getXsrfCookie = (): string => {
    const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : '';
};

const validateAndApply = async (codeToTest?: string) => {
    const targetCode = (codeToTest || inputCode.value).trim().toUpperCase();
    if (!targetCode) {
        errorMessage.value = 'Please enter a coupon code.';
        return;
    }

    if (props.subtotal <= 0) {
        errorMessage.value = 'Please select your rental dates first.';
        return;
    }

    isLoading.value = true;
    errorMessage.value = null;

    try {
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        };

        const csrf = getMetaCsrfToken();
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;

        const xsrf = getXsrfCookie();
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

        const response = await fetch('/api/coupons/validate', {
            method: 'POST',
            headers,
            body: JSON.stringify({
                code: targetCode,
                subtotal: props.subtotal,
            }),
        });

        const data = await response.json();

        if (data.valid) {
            emit('apply', {
                code: data.code,
                type: data.type,
                value: data.value,
                discount_amount: data.discount_amount,
                message: data.message,
            });
            inputCode.value = '';
            errorMessage.value = null;
        } else {
            errorMessage.value = data.message || 'Invalid promo code.';
        }
    } catch {
        errorMessage.value = 'Failed to validate promo code. Please try again.';
    } finally {
        isLoading.value = false;
    }
};

const handleRemove = () => {
    errorMessage.value = null;
    emit('remove');
};

const formatPrice = (amount: number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(amount);

// Re-sync discount if subtotal changes while a percentage coupon is applied
watch(
    () => props.subtotal,
    (newSubtotal) => {
        if (props.appliedCoupon && newSubtotal > 0) {
            validateAndApply(props.appliedCoupon.code);
        }
    }
);
</script>

<template>
    <div class="space-y-3">
        <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-[#58779D] dark:text-[#7094BF]">
            <Tag class="h-3.5 w-3.5" />
            Promo or referral code
        </label>

        <!-- If coupon is already applied -->
        <div
            v-if="appliedCoupon"
            class="flex items-center justify-between rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 backdrop-blur-md transition dark:bg-emerald-500/15"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white">
                    <CheckCircle2 class="h-4 w-4" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs font-bold tracking-wider text-emerald-950 dark:text-emerald-100">
                            {{ appliedCoupon.code }}
                        </span>
                        <span class="rounded bg-emerald-600/20 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:text-emerald-200">
                            -{{ formatPrice(appliedCoupon.discount_amount) }}
                        </span>
                    </div>
                    <p class="text-xs text-emerald-800 dark:text-emerald-300">
                        {{ appliedCoupon.message }}
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="handleRemove"
                class="flex h-7 w-7 items-center justify-center rounded-lg text-emerald-800 transition hover:bg-emerald-500/20 dark:text-emerald-300"
                title="Remove promo code"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <!-- Input group when not applied -->
        <div v-else class="space-y-2">
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input
                        v-model="inputCode"
                        type="text"
                        placeholder="Enter promo code (e.g. COLLEGE20)"
                        class="apple-input w-full px-3.5 py-2 font-mono text-xs tracking-wider uppercase placeholder:normal-case placeholder:font-sans placeholder:tracking-normal"
                        @keydown.enter.prevent="validateAndApply()"
                    />
                </div>

                <button
                    type="button"
                    :disabled="isLoading || !inputCode.trim()"
                    @click="validateAndApply()"
                    class="apple-btn-primary px-4 py-2 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Loader2 v-if="isLoading" class="h-3.5 w-3.5 animate-spin" />
                    <span v-else>Apply</span>
                </button>
            </div>

            <!-- Error message -->
            <p v-if="errorMessage" class="text-xs font-medium text-rose-500">
                {{ errorMessage }}
            </p>

            <!-- Clickable demo suggestions -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                <span class="text-xs text-slate-500 dark:text-slate-400">Available:</span>
                <button
                    v-for="demo in demoCodes"
                    :key="demo.code"
                    type="button"
                    @click="validateAndApply(demo.code)"
                    class="rounded-md border border-slate-200/80 bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-700 transition hover:border-[#58779D] hover:bg-slate-200 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10"
                >
                    {{ demo.code }}
                </button>
            </div>
        </div>
    </div>
</template>
