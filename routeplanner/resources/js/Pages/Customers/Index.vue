<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Icon from '../../Components/Icon.vue';

defineProps({ customers: Array });

const showForm = ref(false);
const form = useForm({ name: '', contact_person: '', email: '', phone: '', color: '#003D52' });
const submit = () => form.post(route('customers.store'), { onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Klanten & stations" />
    <PageHeader title="Klanten & stations" subtitle="Klink werkt voor de grote maatschappijen. Per klant beheer je de tankstations en hun tanks. Klanten krijgen zelf geen account.">
        <button class="btn btn-primary" @click="showForm = !showForm"><Icon name="plus" :size="16" /> Nieuwe klant</button>
    </PageHeader>

    <form v-if="showForm" class="card mb-6 grid gap-4 p-5 md:grid-cols-6" @submit.prevent="submit">
        <div class="md:col-span-2">
            <label class="label">Naam *</label>
            <input v-model="form.name" class="input" required />
            <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
        </div>
        <div>
            <label class="label">Contactpersoon</label>
            <input v-model="form.contact_person" class="input" />
        </div>
        <div>
            <label class="label">E-mail</label>
            <input v-model="form.email" type="email" class="input" />
            <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
        </div>
        <div>
            <label class="label">Telefoon</label>
            <input v-model="form.phone" class="input" />
        </div>
        <div>
            <label class="label">Kleur op kaart</label>
            <div class="flex gap-2">
                <input v-model="form.color" type="color" class="h-9 w-12 cursor-pointer rounded border border-line" />
                <button type="submit" class="btn btn-primary flex-1" :disabled="form.processing">Opslaan</button>
            </div>
        </div>
    </form>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <Link v-for="c in customers" :key="c.id" :href="route('customers.show', c.id)" class="card group overflow-hidden transition hover:border-navy-300">
            <div class="h-1.5" :style="{ background: c.color }"></div>
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold group-hover:underline">{{ c.name }}</h2>
                    <Icon name="right" class="text-muted" />
                </div>
                <p class="text-sm text-muted">{{ c.contact_person || 'Geen contactpersoon' }}</p>
                <div class="mt-4 flex gap-6 text-sm">
                    <div><span class="text-2xl font-bold">{{ c.locations_count }}</span> <span class="text-muted">stations</span></div>
                    <div><span class="text-2xl font-bold">{{ c.open_count }}</span> <span class="text-muted">open opdrachten</span></div>
                </div>
            </div>
        </Link>
    </div>
</template>
