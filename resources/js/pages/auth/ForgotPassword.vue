<script setup lang="ts">
import PasswordResetLinkController from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import HomeLayout from '@/layouts/HomeLayout.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <HomeLayout>
        <Head title="Forgot Password" />

        <div class="flex min-h-[80vh] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="glass-card w-full max-w-md p-8 sm:p-10 rounded-3xl">
                <div class="mb-6 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#58779D]/10 text-[#58779D] dark:bg-[#7094BF]/20 dark:text-[#7094BF]">
                        <KeyRound class="h-6 w-6" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Forgot Password
                    </h1>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Enter your account email address and we'll send a secure password reset link.
                    </p>
                </div>

                <div v-if="status" class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3 text-xs text-emerald-800 dark:text-emerald-300">
                    {{ status }}
                </div>

                <Form
                    v-bind="PasswordResetLinkController.store.form()"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <div class="space-y-1.5">
                        <Label for="email" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                            Email Address
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autocomplete="off"
                            autofocus
                            placeholder="you@example.com"
                            class="apple-input h-11"
                        />
                        <InputError :message="errors.email" class="mt-1" />
                    </div>

                    <Button
                        type="submit"
                        class="apple-btn-primary h-11 w-full text-sm font-medium mt-2"
                        :disabled="processing"
                        data-test="email-password-reset-link-button"
                    >
                        <LoaderCircle
                            v-if="processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        {{ processing ? 'Sending Link...' : 'Email Reset Link' }}
                    </Button>
                </Form>

                <div class="mt-6 border-t border-black/5 dark:border-white/10 pt-4 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Remember your password?
                        <TextLink :href="login()" class="ml-1 font-semibold text-[#58779D] dark:text-[#7094BF] hover:underline">
                            Return to sign in
                        </TextLink>
                    </p>
                </div>
            </div>
        </div>
    </HomeLayout>
</template>
