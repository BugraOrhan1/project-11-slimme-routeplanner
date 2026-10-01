<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: true });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Inloggen" />
    <div class="grid min-h-screen lg:grid-cols-2">
        <!-- Beeld in de stijl van ingenieursbureauklink.nl: rode diagonaal over blauw -->
        <div class="relative hidden overflow-hidden bg-[#1b8fd2] lg:block">
            <div class="absolute inset-0 origin-top-left -skew-y-12 translate-y-24 bg-gradient-to-br from-klink-400 via-klink-500 to-klink-600"></div>
            <svg class="absolute inset-0 h-full w-full opacity-25" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="46" height="46" patternUnits="userSpaceOnUse" patternTransform="rotate(28)">
                        <path d="M0 23 Q 11.5 10 23 23 T 46 23" fill="none" stroke="#fff" stroke-width="1" />
                        <path d="M23 0 Q 10 11.5 23 23 T 23 46" fill="none" stroke="#fff" stroke-width="1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
            <div class="relative flex h-full flex-col justify-end p-14 text-white">
                <h1 class="max-w-lg text-4xl leading-tight font-bold">Slimme routeplanner voor inspecteurs</h1>
                <p class="mt-4 max-w-md text-lg text-white/90">
                    Stations van Shell, BP en TotalEnergies in dezelfde regio slim combineren – de app stelt voor, de planner beslist.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-center bg-surface px-6 py-12">
            <form class="w-full max-w-sm" @submit.prevent="submit">
                <img src="/images/klink-logo.png" alt="Klink inspection & engineering" class="h-14 w-auto dark:hidden" />
                <img src="/images/klink-logo-wit.png" alt="Klink inspection & engineering" class="hidden h-14 w-auto dark:block" />
                <h2 class="mt-10 text-2xl font-bold">Inloggen</h2>
                <p class="mt-1 text-sm text-muted">Log in met je Klink-account.</p>

                <div class="mt-8 space-y-4">
                    <div>
                        <label class="label" for="email">E-mailadres</label>
                        <input id="email" v-model="form.email" type="email" class="input" autocomplete="username" autofocus required />
                        <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="label" for="password">Wachtwoord</label>
                        <input id="password" v-model="form.password" type="password" class="input" autocomplete="current-password" required />
                        <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.remember" type="checkbox" class="rounded accent-klink-600" /> Ingelogd blijven
                    </label>
                    <button type="submit" class="btn btn-primary w-full py-2.5" :disabled="form.processing">Inloggen</button>
                </div>
            </form>
        </div>
    </div>
</template>
