<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import HomeLayout from '@/layouts/HomeLayout.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, UserPlus, ShieldCheck, CarFront } from 'lucide-vue-next';
</script>

<template>
    <HomeLayout>
        <Head title="Create Account" />

        <div class="relative min-h-[90vh] flex items-center justify-center overflow-hidden px-4 py-12 sm:px-6 lg:px-8">
            <!-- Background atmosphere -->
            <div class="pointer-events-none absolute -left-24 top-10 h-72 w-72 rounded-full bg-[#58779D]/10 blur-3xl dark:bg-[#7094BF]/10"></div>
            <div class="pointer-events-none absolute -right-24 bottom-10 h-80 w-80 rounded-full bg-[#101D35]/15 blur-3xl dark:bg-[#070A0F]/30"></div>

            <div class="glass-card relative mx-auto grid w-full max-w-5xl overflow-hidden rounded-3xl lg:grid-cols-2">

                <!-- Left brand panel -->
                <div class="relative hidden flex-col justify-between overflow-hidden bg-[#070A0F] dark:bg-[#070A0F] p-10 text-white lg:flex xl:p-12 border-r border-white/10">
                    <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full border border-white/5"></div>
                    <div class="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-full border border-white/5"></div>
                    <div class="pointer-events-none absolute -bottom-24 -left-20 h-72 w-72 rounded-full bg-[#58779D]/15 blur-3xl"></div>

                    <div class="relative">
                        <div class="mb-10 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-white backdrop-blur-md border border-white/10">
                            <CarFront class="h-6 w-6" />
                        </div>

                        <h1 class="text-3xl font-extrabold tracking-tight leading-tight sm:text-4xl">
                            Unlock premier mobility,
                            <span class="mt-1 block text-slate-400">across every destination.</span>
                        </h1>

                        <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
                            Create your renter profile to browse the fleet, verify your license in advance, and complete vehicle pickup seamlessly.
                        </p>
                    </div>

                    <div class="relative mt-12 space-y-4">
                        <div class="flex items-start gap-3.5 rounded-2xl border border-white/10 bg-white/[0.03] p-4 backdrop-blur-xs">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-slate-200">
                                <CarFront class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-white">Curated Luxury Fleet</h3>
                                <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
                                    Direct access to performance sedans, executive SUVs, and pure electric models.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5 rounded-2xl border border-white/10 bg-white/[0.03] p-4 backdrop-blur-xs">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-slate-200">
                                <ShieldCheck class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-white">Priority Handover</h3>
                                <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
                                    Verified drivers skip the desk lines and pick up keys directly.
                                </p>
                            </div>
                        </div>

                        <p class="pt-2 text-xs text-slate-500">
                            Real Rent Car · Premium Fleet Logistics
                        </p>
                    </div>
                </div>

                <!-- Registration form -->
                <div class="flex items-center justify-center p-6 sm:p-10 lg:p-12">
                    <div class="w-full max-w-md">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                                Create Account
                            </h2>
                            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                                Enter your credentials to configure your driver profile.
                            </p>
                        </div>

                        <Form
                            v-bind="RegisteredUserController.store.form()"
                            :reset-on-success="['password', 'password_confirmation']"
                            v-slot="{ errors, processing }"
                            class="space-y-4"
                        >
                            <div>
                                <Label for="name" class="mb-1.5 block text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Full Name
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    name="name"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="name"
                                    placeholder="Alex Morgan"
                                    class="apple-input h-11"
                                />
                                <InputError :message="errors.name" class="mt-1" />
                            </div>

                            <div>
                                <Label for="email" class="mb-1.5 block text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Email Address
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    :tabindex="2"
                                    autocomplete="email"
                                    placeholder="alex@example.com"
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
                                    :tabindex="3"
                                    autocomplete="new-password"
                                    placeholder="Minimum 8 characters"
                                    class="apple-input h-11"
                                />
                                <InputError :message="errors.password" class="mt-1" />
                            </div>

                            <div>
                                <Label for="password_confirmation" class="mb-1.5 block text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Confirm Password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    required
                                    :tabindex="4"
                                    autocomplete="new-password"
                                    placeholder="Re-enter password"
                                    class="apple-input h-11"
                                />
                                <InputError :message="errors.password_confirmation" class="mt-1" />
                            </div>

                            <Button
                                type="submit"
                                class="apple-btn-primary h-11 w-full text-sm font-medium mt-2"
                                :tabindex="5"
                                :disabled="processing"
                            >
                                <LoaderCircle
                                    v-if="processing"
                                    class="mr-2 h-4 w-4 animate-spin"
                                />
                                {{ processing ? 'Creating Account...' : 'Complete Registration' }}
                            </Button>

                            <div class="border-t border-black/5 dark:border-white/10 pt-4 text-center">
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Already have an account?
                                    <TextLink
                                        :href="login()"
                                        :tabindex="6"
                                        class="ml-1 font-semibold text-[#58779D] dark:text-[#7094BF] hover:underline"
                                    >
                                        Sign in
                                    </TextLink>
                                </p>
                            </div>
                        </Form>
                    </div>
                </div>
            </div>
        </div>
    </HomeLayout>
</template>
