<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Calendar,
    CheckCircle2,
    Clock,
    CreditCard,
    FileImage,
    HelpCircle,
    RefreshCw,
    ShieldAlert,
    ShieldCheck,
    UploadCloud,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface KycData {
    status: 'unsubmitted' | 'pending' | 'verified' | 'rejected' | string;
    license_number: string | null;
    license_expiry: string | null;
    license_front_url: string | null;
    license_back_url: string | null;
    rejection_reason: string | null;
    verified_at: string | null;
}

const props = defineProps<{
    kyc: KycData;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Driver License',
        href: '/settings/kyc',
    },
];

const frontInputRef = ref<HTMLInputElement | null>(null);
const backInputRef = ref<HTMLInputElement | null>(null);

const frontPreview = ref<string | null>(props.kyc.license_front_url);
const backPreview = ref<string | null>(props.kyc.license_back_url);

const form = useForm({
    license_number: props.kyc.license_number || '',
    license_expiry: props.kyc.license_expiry || '',
    front_image: null as File | null,
    back_image: null as File | null,
});

const handleFrontChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.front_image = file;
        frontPreview.value = URL.createObjectURL(file);
    }
};

const handleBackChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.back_image = file;
        backPreview.value = URL.createObjectURL(file);
    }
};

const triggerFrontUpload = () => {
    frontInputRef.value?.click();
};

const triggerBackUpload = () => {
    backInputRef.value?.click();
};

const removeFront = () => {
    form.front_image = null;
    frontPreview.value = null;
    if (frontInputRef.value) frontInputRef.value.value = '';
};

const removeBack = () => {
    form.back_image = null;
    backPreview.value = null;
    if (backInputRef.value) backInputRef.value.value = '';
};

const submitKyc = () => {
    form.post('/settings/kyc', {
        forceFormData: true,
        preserveScroll: true,
    });
};

const status = computed(() => (props.kyc?.status || 'unsubmitted').toLowerCase());

const formatDate = (val?: string | null) => {
    if (!val) return '—';
    try {
        return new Date(val).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return val;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Driver License Verification" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <div>
                    <HeadingSmall
                        title="Driver License Verification"
                        description="Verify your driving license for instant key handovers and frictionless rentals."
                    />
                </div>

                <!-- 1. Dynamic Status Banner -->
                <!-- State: Verified -->
                <div
                    v-if="status === 'verified'"
                    class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-5 text-emerald-900 dark:text-emerald-200 backdrop-blur-md"
                >
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-emerald-950 dark:text-emerald-100">
                                    Verified Driver
                                </h3>
                                <span class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                                    Approved
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300/90 leading-relaxed">
                                Your driving credentials are confirmed. You are fully eligible for priority vehicle pickup at any station.
                            </p>
                            <p v-if="kyc.verified_at" class="mt-2 text-xs text-emerald-700 dark:text-emerald-400">
                                Verified on {{ formatDate(kyc.verified_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- State: Pending Review -->
                <div
                    v-else-if="status === 'pending'"
                    class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5 text-amber-900 dark:text-amber-200 backdrop-blur-md"
                >
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs">
                            <Clock class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-amber-950 dark:text-amber-100">
                                    Documents Under Review
                                </h3>
                                <span class="rounded-full bg-amber-500/20 px-2.5 py-0.5 text-xs font-semibold text-amber-900 dark:text-amber-300">
                                    Pending Inspection
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-amber-800 dark:text-amber-300/90 leading-relaxed">
                                Your submitted driving license is currently being reviewed by our verification team. Reviews are typically completed within 15–30 minutes.
                            </p>
                            <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                                You can update your submission below at any time if you notice an error.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- State: Rejected -->
                <div
                    v-else-if="status === 'rejected'"
                    class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-5 text-rose-900 dark:text-rose-200 backdrop-blur-md"
                >
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-xs">
                            <ShieldAlert class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-rose-950 dark:text-rose-100">
                                    Verification Action Required
                                </h3>
                                <span class="rounded-full bg-rose-500/20 px-2.5 py-0.5 text-xs font-semibold text-rose-900 dark:text-rose-300">
                                    Needs Re-submission
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-rose-800 dark:text-rose-300/90">
                                Your documents could not be approved due to the following reason:
                            </p>
                            <div class="mt-2.5 rounded-xl border border-rose-500/20 bg-white/70 dark:bg-black/30 p-3.5 text-sm font-medium text-rose-950 dark:text-rose-200">
                                "{{ kyc.rejection_reason || 'Document photo was unreadable or details did not match.' }}"
                            </div>
                            <p class="mt-2 text-xs text-rose-700 dark:text-rose-400">
                                Please check the feedback above, upload clearer photos, and re-submit for review.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- State: Unsubmitted -->
                <div
                    v-else
                    class="rounded-2xl border border-black/5 dark:border-white/10 bg-slate-50 dark:bg-white/[0.03] p-5 text-slate-800 dark:text-slate-200"
                >
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#101D35] dark:bg-white/10 text-white">
                            <CreditCard class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                Driving License Required for Car Pickups
                            </h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                                Upload a clear copy of your government-issued driver's license. Our team verifies your credentials in advance so you can walk straight to your car at pickup.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2. Submission Form -->
                <form class="space-y-6" @submit.prevent="submitKyc">
                    <!-- License Number & Expiry Grid -->
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="license_number" class="text-sm font-medium text-slate-900 dark:text-slate-200">
                                License Number *
                            </Label>
                            <Input
                                id="license_number"
                                v-model="form.license_number"
                                type="text"
                                maxlength="50"
                                required
                                placeholder="e.g. DL-0420110023456"
                                class="apple-input font-mono tracking-wide"
                            />
                            <InputError :message="form.errors.license_number" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="license_expiry" class="text-sm font-medium text-slate-900 dark:text-slate-200">
                                Expiration Date *
                            </Label>
                            <Input
                                id="license_expiry"
                                v-model="form.license_expiry"
                                type="date"
                                required
                                class="apple-input"
                            />
                            <InputError :message="form.errors.license_expiry" />
                        </div>
                    </div>

                    <!-- Hidden file inputs -->
                    <input
                        ref="frontInputRef"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/jpg"
                        class="hidden"
                        @change="handleFrontChange"
                    />
                    <input
                        ref="backInputRef"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/jpg"
                        class="hidden"
                        @change="handleBackChange"
                    />

                    <!-- Upload Cards: Front & Back -->
                    <div class="grid gap-5 sm:grid-cols-2">
                        <!-- Front Card -->
                        <div class="flex flex-col">
                            <Label class="mb-2 text-sm font-medium text-slate-900 dark:text-slate-200">
                                License Front Photo *
                            </Label>

                            <!-- Preview mode -->
                            <div
                                v-if="frontPreview"
                                class="group relative flex h-48 w-full flex-col items-center justify-center overflow-hidden rounded-2xl border border-black/10 dark:border-white/10 bg-slate-900 shadow-2xs"
                            >
                                <img
                                    :src="frontPreview"
                                    alt="Front License Preview"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-80"
                                />
                                <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/50 backdrop-blur-xs opacity-0 transition group-hover:opacity-100">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        class="h-8 gap-1.5 bg-white text-xs font-semibold text-slate-900 hover:bg-slate-100"
                                        @click="triggerFrontUpload"
                                    >
                                        <RefreshCw class="h-3.5 w-3.5" />
                                        Replace
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        class="h-8 w-8 p-0"
                                        @click="removeFront"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <span class="absolute bottom-2.5 left-2.5 rounded-md bg-black/70 px-2 py-0.5 text-[11px] font-medium text-white backdrop-blur-md">
                                    Front Side
                                </span>
                            </div>

                            <!-- Dropzone mode -->
                            <div
                                v-else
                                class="flex h-48 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-black/15 dark:border-white/15 bg-slate-50/50 dark:bg-white/[0.02] p-5 text-center transition hover:border-[#58779D] dark:hover:border-[#7094BF] hover:bg-slate-100/60 dark:hover:bg-white/[0.05]"
                                @click="triggerFrontUpload"
                            >
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-200/70 dark:bg-white/10 text-[#58779D] dark:text-[#7094BF]">
                                    <UploadCloud class="h-5 w-5" />
                                </div>
                                <p class="mt-2.5 text-sm font-semibold text-slate-900 dark:text-white">
                                    Upload Front Side
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    JPG, PNG, or WEBP up to 5MB
                                </p>
                            </div>

                            <InputError class="mt-1.5" :message="form.errors.front_image" />
                        </div>

                        <!-- Back Card -->
                        <div class="flex flex-col">
                            <Label class="mb-2 text-sm font-medium text-slate-900 dark:text-slate-200">
                                License Back Photo *
                            </Label>

                            <!-- Preview mode -->
                            <div
                                v-if="backPreview"
                                class="group relative flex h-48 w-full flex-col items-center justify-center overflow-hidden rounded-2xl border border-black/10 dark:border-white/10 bg-slate-900 shadow-2xs"
                            >
                                <img
                                    :src="backPreview"
                                    alt="Back License Preview"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-80"
                                />
                                <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/50 backdrop-blur-xs opacity-0 transition group-hover:opacity-100">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        class="h-8 gap-1.5 bg-white text-xs font-semibold text-slate-900 hover:bg-slate-100"
                                        @click="triggerBackUpload"
                                    >
                                        <RefreshCw class="h-3.5 w-3.5" />
                                        Replace
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        class="h-8 w-8 p-0"
                                        @click="removeBack"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <span class="absolute bottom-2.5 left-2.5 rounded-md bg-black/70 px-2 py-0.5 text-[11px] font-medium text-white backdrop-blur-md">
                                    Back Side
                                </span>
                            </div>

                            <!-- Dropzone mode -->
                            <div
                                v-else
                                class="flex h-48 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-black/15 dark:border-white/15 bg-slate-50/50 dark:bg-white/[0.02] p-5 text-center transition hover:border-[#58779D] dark:hover:border-[#7094BF] hover:bg-slate-100/60 dark:hover:bg-white/[0.05]"
                                @click="triggerBackUpload"
                            >
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-200/70 dark:bg-white/10 text-[#58779D] dark:text-[#7094BF]">
                                    <UploadCloud class="h-5 w-5" />
                                </div>
                                <p class="mt-2.5 text-sm font-semibold text-slate-900 dark:text-white">
                                    Upload Back Side
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    JPG, PNG, or WEBP up to 5MB
                                </p>
                            </div>

                            <InputError class="mt-1.5" :message="form.errors.back_image" />
                        </div>
                    </div>

                    <!-- Guidelines note -->
                    <div class="rounded-2xl border border-black/5 dark:border-white/10 bg-slate-50/80 dark:bg-white/[0.02] p-4 text-xs text-slate-600 dark:text-slate-400">
                        <div class="flex items-center gap-2 font-semibold text-slate-900 dark:text-slate-200">
                            <HelpCircle class="h-4 w-4 text-[#58779D] dark:text-[#7094BF]" />
                            <span>Verification Guidelines</span>
                        </div>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-slate-500 dark:text-slate-400">
                            <li>Ensure all text, photo, and license expiration date are clearly legible.</li>
                            <li>Do not crop or conceal corners of the document. Avoid strong flash reflections.</li>
                            <li>All data is encrypted and used exclusively for rental identity verification.</li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="apple-btn-primary h-11 px-6 text-sm font-medium"
                        >
                            {{ form.processing ? 'Submitting Documents...' : (status === 'rejected' ? 'Re-submit for Review' : 'Save & Submit Credentials') }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                Submitted successfully.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
