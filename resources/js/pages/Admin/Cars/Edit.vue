<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FileUpload from '@/components/ViltFilePond/FileUpload.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { index, store, update } from '@/routes/admin/cars';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    ArrowLeft,
    CarFront,
    CheckCircle2,
    CircleDollarSign,
    ClipboardList,
    ImagePlus,
} from 'lucide-vue-next';

const props = defineProps<{
    car: any | null;
    imageFiles: Array<{ id: number; url: string }>;
    enums: {
        colors: Array<{ name: string; value: string; hex: string }>;
        fuelTypes: string[];
        statuses: Array<{ value: string; label: string; color: string }>;
    };
}>();

const isEdit = computed(() => !!props.car);

const carColors = computed(() =>
    props.enums.colors.map((color) => ({
        ...color,
        value: color.value.toLowerCase(),
        name: color.name.charAt(0).toUpperCase() + color.name.slice(1),
    })),
);

const fuelTypes = computed(() =>
    props.enums.fuelTypes.map((fuel) => ({
        value: fuel.toLowerCase(),
        label: fuel.charAt(0).toUpperCase() + fuel.slice(1),
    })),
);

const statuses = computed(() =>
    props.enums.statuses.map((status) => ({
        value: status.value,
        label: status.label,
        color: status.color,
    })),
);

const form = useForm({
    make: props.car?.make ?? '',
    model: props.car?.model ?? '',
    year: props.car?.year ?? '',
    license_plate: props.car?.license_plate ?? '',
    color: (props.car?.color || 'white').toLowerCase(),
    price_per_day: props.car?.price_per_day ?? '',
    mileage: props.car?.mileage ?? '',
    transmission: props.car?.transmission ?? 'automatic',
    seats: props.car?.seats ?? '',
    fuel_type: (props.car?.fuel_type || 'gasoline').toLowerCase(),
    description: props.car?.description ?? '',
    status: props.car?.status ?? 'available',
    image: [] as string[],
    image_temp_folders: [] as string[],
    image_removed_files: [] as number[],
});

const fileUploadRef = ref<InstanceType<typeof FileUpload> | null>(null);
const tempFolders = ref<string[]>([]);
const removedFileIds = ref<number[]>([]);

watch(
    tempFolders,
    (value) => {
        form.image_temp_folders = [...value];
        if (value.length) {
            form.image = [...value];
        }
    },
    { deep: true },
);

function handleFileRemoved(data: { type: string; fileId?: number }) {
    if (data.type === 'existing' && data.fileId) {
        removedFileIds.value.push(data.fileId);
        form.image_removed_files = [...removedFileIds.value];
    }
}

function submit() {
    if (isEdit.value) {
        form.put(update(props.car.id).url);
    } else {
        form.image = [...tempFolders.value];
        form.post(store().url, {
            onSuccess: () => {
                form.reset();
                tempFolders.value = [];
                removedFileIds.value = [];
                fileUploadRef.value?.resetFiles();
            },
        });
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Car' : 'Create Car'" />

    <AdminLayout>
        <main class="min-h-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header console -->
            <div class="glass-card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                        <CarFront class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                            {{ isEdit ? 'Edit Vehicle' : 'Add New Vehicle' }}
                        </h1>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            {{ isEdit ? 'Review and update the vehicle specs and inventory profile.' : 'Enter details to configure a new fleet vehicle listing.' }}
                        </p>
                    </div>
                </div>

                <Link
                    :href="index()"
                    class="inline-flex items-center justify-center gap-2 self-start rounded-xl border border-black/10 dark:border-white/10 bg-slate-100/70 dark:bg-white/5 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 transition hover:bg-slate-200/60 dark:hover:bg-white/10"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Fleet
                </Link>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <!-- Image and pricing -->
                <section class="glass-card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-black/5 dark:border-white/10 px-6 py-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <ImagePlus class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900 dark:text-white">Vehicle Image & Rates</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Manage vehicle photo, daily rental rate, status and exterior finish.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-2">
                        <div class="min-w-0">
                            <Label class="text-sm font-medium text-slate-900 dark:text-slate-200">Vehicle Image</Label>
                            <div class="mt-2 rounded-2xl border border-dashed border-black/15 dark:border-white/15 bg-slate-50/50 dark:bg-white/[0.02] p-3">
                                <FileUpload
                                    ref="fileUploadRef"
                                    v-model="tempFolders"
                                    :initial-files="imageFiles || []"
                                    :allow-multiple="false"
                                    :max-files="1"
                                    collection="image"
                                    theme="light"
                                    width="100%"
                                    @file-removed="handleFileRemoved"
                                />
                            </div>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">High-resolution exterior photograph (JPG, PNG or WEBP).</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <Label for="status" class="text-sm font-medium text-slate-900 dark:text-slate-200">Vehicle Status</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="apple-input mt-2 cursor-pointer py-2.5 text-sm"
                                >
                                    <option v-for="status in statuses" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-1" />
                            </div>

                            <div>
                                <Label for="price_per_day" class="text-sm font-medium text-slate-900 dark:text-slate-200">Price Per Day</Label>
                                <div class="relative mt-2">
                                    <CircleDollarSign class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        id="price_per_day"
                                        v-model="form.price_per_day"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="e.g. 250.00"
                                        class="apple-input pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.price_per_day" class="mt-1" />
                            </div>

                            <div>
                                <Label class="text-sm font-medium text-slate-900 dark:text-slate-200">Exterior Finish</Label>
                                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    <label
                                        v-for="color in carColors"
                                        :key="color.value"
                                        :for="'color-' + color.value"
                                        class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border p-2.5 text-xs transition"
                                        :class="form.color === color.value
                                            ? 'border-[#58779D] bg-[#58779D]/10 dark:border-[#7094BF] dark:bg-[#7094BF]/20 ring-1 ring-[#58779D]'
                                            : 'border-black/5 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-white/5'"
                                    >
                                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ color.name }}</span>
                                        <input
                                            :id="'color-' + color.value"
                                            v-model="form.color"
                                            type="radio"
                                            :value="color.value"
                                            class="sr-only"
                                        />
                                        <span
                                            class="h-4 w-4 shrink-0 rounded-full border border-black/10 dark:border-white/10 shadow-2xs"
                                            :style="{ backgroundColor: color.hex }"
                                        />
                                    </label>
                                </div>
                                <InputError :message="form.errors.color" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Basic details -->
                <section class="glass-card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-black/5 dark:border-white/10 px-6 py-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <CarFront class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900 dark:text-white">Vehicle Details</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Make, model, year, and license plate identifiers.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
                        <div>
                            <Label for="make" class="text-sm font-medium text-slate-900 dark:text-slate-200">Make</Label>
                            <Input id="make" v-model="form.make" placeholder="e.g. Porsche, BMW, Mercedes" class="apple-input mt-2" />
                            <InputError :message="form.errors.make" class="mt-1" />
                        </div>

                        <div>
                            <Label for="model" class="text-sm font-medium text-slate-900 dark:text-slate-200">Model</Label>
                            <Input id="model" v-model="form.model" placeholder="e.g. 911 Carrera, M4, S-Class" class="apple-input mt-2" />
                            <InputError :message="form.errors.model" class="mt-1" />
                        </div>

                        <div>
                            <Label for="year" class="text-sm font-medium text-slate-900 dark:text-slate-200">Model Year</Label>
                            <Input id="year" v-model="form.year" type="number" :min="1900" :max="new Date().getFullYear() + 1" placeholder="e.g. 2025" class="apple-input mt-2" />
                            <InputError :message="form.errors.year" class="mt-1" />
                        </div>

                        <div>
                            <Label for="license_plate" class="text-sm font-medium text-slate-900 dark:text-slate-200">License Plate</Label>
                            <Input id="license_plate" v-model="form.license_plate" placeholder="e.g. DL-04-AB-1234" class="apple-input mt-2 font-mono" />
                            <InputError :message="form.errors.license_plate" class="mt-1" />
                        </div>
                    </div>
                </section>

                <!-- Specifications -->
                <section class="glass-card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-black/5 dark:border-white/10 px-6 py-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                            <ClipboardList class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900 dark:text-white">Specifications & Description</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Mileage, gearbox type, passenger capacity and editorial overview.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
                        <div>
                            <Label for="mileage" class="text-sm font-medium text-slate-900 dark:text-slate-200">Mileage (km)</Label>
                            <Input id="mileage" v-model="form.mileage" type="number" min="0" step="100" placeholder="e.g. 15000" class="apple-input mt-2" />
                            <InputError :message="form.errors.mileage" class="mt-1" />
                        </div>

                        <div>
                            <Label for="transmission" class="text-sm font-medium text-slate-900 dark:text-slate-200">Transmission</Label>
                            <select id="transmission" v-model="form.transmission" class="apple-input mt-2 cursor-pointer py-2.5 text-sm">
                                <option value="automatic">Automatic</option>
                                <option value="manual">Manual</option>
                            </select>
                            <InputError :message="form.errors.transmission" class="mt-1" />
                        </div>

                        <div>
                            <Label for="seats" class="text-sm font-medium text-slate-900 dark:text-slate-200">Passenger Seats</Label>
                            <Input id="seats" v-model="form.seats" type="number" min="1" max="20" placeholder="e.g. 5" class="apple-input mt-2" />
                            <InputError :message="form.errors.seats" class="mt-1" />
                        </div>

                        <div>
                            <Label for="fuel_type" class="text-sm font-medium text-slate-900 dark:text-slate-200">Powertrain / Fuel</Label>
                            <select id="fuel_type" v-model="form.fuel_type" class="apple-input mt-2 cursor-pointer py-2.5 text-sm">
                                <option v-for="fuel in fuelTypes" :key="fuel.value" :value="fuel.value">
                                    {{ fuel.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.fuel_type" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <Label for="description" class="text-sm font-medium text-slate-900 dark:text-slate-200">Vehicle Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                placeholder="Highlight dynamic performance characteristics, interior luxury appointments, and rental requirements..."
                                class="apple-input mt-2 w-full p-3 text-sm"
                            />
                            <InputError :message="form.errors.description" class="mt-1" />
                        </div>
                    </div>
                </section>

                <!-- Actions -->
                <div class="glass-card flex flex-col-reverse justify-between gap-3 p-4 sm:flex-row sm:items-center sm:px-6">
                    <Link
                        :href="index()"
                        class="inline-flex items-center justify-center rounded-xl border border-black/10 dark:border-white/10 px-5 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-white/10"
                    >
                        Cancel
                    </Link>

                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="apple-btn-primary h-11 px-6 text-sm font-medium"
                    >
                        <CheckCircle2 class="h-4 w-4 mr-2" />
                        {{
                            form.processing
                                ? (isEdit ? 'Saving...' : 'Creating...')
                                : (isEdit ? 'Save Changes' : 'Create Vehicle')
                        }}
                    </Button>
                </div>
            </form>
        </main>
    </AdminLayout>
</template>
