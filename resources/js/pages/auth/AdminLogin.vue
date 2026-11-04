<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, LockKeyhole, ShieldCheck, CarFront } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <div>
        <Head title="Admin Access" />

        <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12 sm:px-6">
            <!-- Background atmosphere -->
            <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-[#58779D]/10 blur-3xl dark:bg-[#7094BF]/10"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-[#101D35]/15 blur-3xl dark:bg-[#070A0F]/30"></div>

            <div class="glass-card relative grid w-full max-w-5xl overflow-hidden rounded-3xl lg:grid-cols-2">

                <!-- Admin branding panel -->
                <div class="relative hidden flex-col justify-between overflow-hidden bg-[#070A0F] dark:bg-[#070A0F] p-8 text-white sm:p-12 lg:flex border-r border-white/10">
                    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full border border-white/5"></div>
                    <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full border border-white/5"></div>
                    <div class="pointer-events-none absolute -bottom-24 -left-20 h-72 w-72 rounded-full bg-[#58779D]/15 blur-3xl"></div>

                    <div class="relative flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-white border border-white/10">
                            <CarFront class="h-6 w-6" />
                        </span>
                        <div>
                            <p class="font-bold tracking-tight text-white text-sm">REAL RENT CAR</p>
                            <p class="text-xs text-slate-400">Operations Control Portal</p>
                        </div>
                    </div>

                    <div class="relative my-14">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">
                            <ShieldCheck class="h-3.5 w-3.5 text-emerald-400" />
                            Restricted Console
                        </span>

                        <h1 class="mt-6 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">
                            Fleet intelligence &
                            <span class="block text-slate-400">operations management.</span>
                        </h1>

                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-400">
                            Access real-time vehicle telematics, reservation fulfillment queues, and client identity verification.
                        </p>

                        <div class="mt-8 space-y-3 text-xs text-slate-300">
                            <div class="flex items-center gap-2.5">
                                <ShieldCheck class="h-4 w-4 text-emerald-400" />
                                Protected role-based authentication
                            </div>
                            <div class="flex items-center gap-2.5">
                                <LockKeyhole class="h-4 w-4 text-slate-400" />
                                Session activity auditing enabled
                            </div>
                        </div>
                    </div>

                    <p class="relative text-xs text-slate-500">
                        Authorized personnel only · Real Rent Car
                    </p>
                </div>

                <!-- Login form -->
                <div class="flex items-center justify-center p-6 sm:p-10 lg:p-12">
                    <div class="w-full max-w-md">
                        <div class="mb-8">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                                <LockKeyhole class="h-6 w-6" />
                            </div>

                            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                                Admin Sign In
                            </h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Enter your administrative credentials to open the operations suite.
                            </p>
                        </div>

                        <Form
                            v-bind="AuthenticatedSessionController.storeAdminLogin.form()"
                            :reset-on-success="['password']"
                            v-slot="{ errors, processing }"
                            class="space-y-4"
                        >
                            <div>
                                <Label for="email" class="mb-1.5 block text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Administrator Email
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="username"
                                    placeholder="admin@example.com"
                                    class="apple-input h-11"
                                />
                                <InputError :message="errors.email" class="mt-1" />
                            </div>

                            <div>
                                <Label for="password" class="mb-1.5 block text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Password
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    :tabindex="2"
                                    autocomplete="current-password"
                                    placeholder="Enter administrator password"
                                    class="apple-input h-11"
                                />
                                <InputError :message="errors.password" class="mt-1" />
                            </div>

                            <div v-if="status" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3 text-xs text-emerald-800 dark:text-emerald-300">
                                {{ status }}
                            </div>

                            <Button
                                type="submit"
                                class="apple-btn-primary h-11 w-full text-sm font-medium mt-2"
                                :tabindex="3"
                                :disabled="processing"
                                data-test="admin-login-button"
                            >
                                <LoaderCircle
                                    v-if="processing"
                                    class="mr-2 h-4 w-4 animate-spin"
                                />
                                {{ processing ? 'Authenticating...' : 'Sign In to Operations' }}
                            </Button>

                            <div class="border-t border-black/5 dark:border-white/10 pt-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                    <ShieldCheck class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span>Protected administrative gateway</span>
                                </div>
                            </div>
                        </Form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
