<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import HomeLayout from '@/layouts/HomeLayout.vue';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    LoaderCircle,
    Shield,
    User,
    CarFront,
    LockKeyhole,
} from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const isDemoOpen = ref(false);
</script>

<template>
    <HomeLayout>
        <Head title="Log in" />

        <main class="relative min-h-[90vh] flex items-center justify-center overflow-hidden px-4 py-12 sm:px-6 lg:px-8">
            <!-- Background atmosphere -->
            <div class="pointer-events-none absolute -left-24 top-10 h-72 w-72 rounded-full bg-[#58779D]/10 blur-3xl dark:bg-[#7094BF]/10"></div>
            <div class="pointer-events-none absolute -right-24 bottom-10 h-80 w-80 rounded-full bg-[#101D35]/15 blur-3xl dark:bg-[#070A0F]/30"></div>

            <div class="glass-card relative mx-auto grid w-full max-w-5xl overflow-hidden rounded-3xl lg:grid-cols-2">

                <!-- Left Brand Panel -->
                <section class="relative hidden overflow-hidden bg-[#070A0F] dark:bg-[#070A0F] p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-12 border-r border-white/10">
                    <div class="pointer-events-none absolute -right-20 top-24 h-64 w-64 rounded-full border border-white/5"></div>
                    <div class="pointer-events-none absolute -right-8 top-36 h-40 w-40 rounded-full border border-white/5"></div>
                    <div class="pointer-events-none absolute -bottom-24 -left-20 h-72 w-72 rounded-full bg-[#58779D]/15 blur-3xl"></div>

                    <div class="relative">
                        <div class="mb-10 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-white backdrop-blur-md border border-white/10">
                            <CarFront class="h-6 w-6" />
                        </div>

                        <h1 class="text-3xl font-extrabold tracking-tight leading-tight sm:text-4xl">
                            Curated mobility,
                            <span class="mt-1 block text-slate-400">ready when you are.</span>
                        </h1>

                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-400">
                            Sign in to review vehicle allocations, manage active bookings, and access rapid contact points.
                        </p>
                    </div>

                    <div class="relative mt-12 space-y-4">
                        <div class="flex items-center gap-3.5 rounded-2xl border border-white/10 bg-white/[0.03] p-4 backdrop-blur-xs">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-slate-200">
                                <CarFront class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-white">Tier-One Fleet</h3>
                                <p class="text-xs text-slate-400">Guaranteed vehicle makes and performance models.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3.5 rounded-2xl border border-white/10 bg-white/[0.03] p-4 backdrop-blur-xs">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-slate-200">
                                <LockKeyhole class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-white">Secure Access</h3>
                                <p class="text-xs text-slate-400">Encrypted authentication and rapid digital handovers.</p>
                            </div>
                        </div>

                        <p class="pt-2 text-xs text-slate-500">
                            Real Rent Car · Premium Fleet Logistics
                        </p>
                    </div>
                </section>

                <!-- Right Login Panel -->
                <section class="p-6 sm:p-10 xl:p-12">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                            Welcome Back
                        </h2>
                        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                            Sign in with your email address to continue to your account.
                        </p>
                    </div>

                    <!-- Demo Credentials -->
                    <div class="mb-6 overflow-hidden rounded-2xl border border-black/10 dark:border-white/10 bg-slate-50 dark:bg-white/[0.02]">
                        <button
                            type="button"
                            @click="isDemoOpen = !isDemoOpen"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-slate-100/60 dark:hover:bg-white/[0.04] cursor-pointer"
                            :aria-expanded="isDemoOpen"
                        >
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                                    <Shield class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-900 dark:text-white">Demo Credentials</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Review sample login accounts</p>
                                </div>
                            </div>

                            <ChevronUp v-if="isDemoOpen" class="h-4 w-4 text-slate-400" />
                            <ChevronDown v-else class="h-4 w-4 text-slate-400" />
                        </button>

                        <div v-if="isDemoOpen" class="space-y-3 border-t border-black/5 dark:border-white/10 p-4">
                            <div class="rounded-xl border border-black/5 dark:border-white/10 bg-white dark:bg-[#0F1724] p-3 text-xs">
                                <div class="mb-2 flex items-center gap-1.5 font-semibold text-slate-900 dark:text-white">
                                    <User class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span>Client Access</span>
                                </div>
                                <div class="space-y-1 text-slate-600 dark:text-slate-400">
                                    <div class="flex justify-between">
                                        <span>Email</span>
                                        <code class="font-mono text-slate-900 dark:text-slate-200">client@example.com</code>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Password</span>
                                        <code class="font-mono text-slate-900 dark:text-slate-200">00000000</code>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-black/5 dark:border-white/10 bg-white dark:bg-[#0F1724] p-3 text-xs">
                                <div class="mb-2 flex items-center gap-1.5 font-semibold text-slate-900 dark:text-white">
                                    <Shield class="h-3.5 w-3.5 text-[#58779D] dark:text-[#7094BF]" />
                                    <span>Admin Access</span>
                                </div>
                                <div class="space-y-1 text-slate-600 dark:text-slate-400">
                                    <div class="flex justify-between">
                                        <span>Email</span>
                                        <code class="font-mono text-slate-900 dark:text-slate-200">admin@example.com</code>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Password</span>
                                        <code class="font-mono text-slate-900 dark:text-slate-200">00000000</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Message -->
                    <div
                        v-if="status"
                        class="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4"
                        role="status"
                    >
                        <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">
                            {{ status }}
                        </p>
                    </div>

                    <!-- Login Form -->
                    <Form
                        v-bind="AuthenticatedSessionController.store.form()"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="space-y-4"
                    >
                        <div>
                            <Label for="email" class="mb-1.5 block text-xs font-medium text-slate-700 dark:text-slate-300">
                                Email Address
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="you@example.com"
                                class="apple-input h-11"
                            />
                            <InputError :message="errors.email" class="mt-1" />
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <Label for="password" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Password
                                </Label>

                                <TextLink
                                    v-if="canResetPassword"
                                    :href="request()"
                                    :tabindex="5"
                                    class="text-xs font-medium text-[#58779D] dark:text-[#7094BF] hover:underline"
                                >
                                    Forgot password?
                                </TextLink>
                            </div>

                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="Enter password"
                                class="apple-input h-11"
                            />
                            <InputError :message="errors.password" class="mt-1" />
                        </div>

                        <div class="flex items-center pt-1">
                            <Label for="remember" class="flex cursor-pointer items-center gap-2.5 text-xs text-slate-600 dark:text-slate-400">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    :tabindex="3"
                                    class="rounded-md border-black/20 dark:border-white/20"
                                />
                                Keep me signed in
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            class="apple-btn-primary h-11 w-full text-sm font-medium mt-2"
                            :tabindex="4"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <LoaderCircle
                                v-if="processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            {{ processing ? 'Signing in...' : 'Sign In' }}
                        </Button>

                        <div class="border-t border-black/5 dark:border-white/10 pt-4 text-center">
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Don't have an account?
                                <TextLink
                                    :href="register()"
                                    :tabindex="6"
                                    class="ml-1 font-semibold text-[#58779D] dark:text-[#7094BF] hover:underline"
                                >
                                    Create account
                                </TextLink>
                            </p>
                        </div>
                    </Form>

                    <!-- Security Note -->
                    <div class="mt-6 flex items-center justify-center gap-2 text-center text-xs text-slate-400 dark:text-slate-500">
                        <LockKeyhole class="h-3.5 w-3.5" />
                        <span>Protected by end-to-end credential encryption</span>
                    </div>
                </section>
            </div>
        </main>
    </HomeLayout>
</template>
