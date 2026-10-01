<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ users: Array, roles: Object });

const editing = ref(null);
const form = useForm({ name: '', email: '', role: 'planner', password: '' });

function edit(u) {
    editing.value = u.id;
    Object.assign(form, { name: u.name, email: u.email, role: u.role, password: '' });
}
function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}
function submit() {
    if (editing.value) form.put(route('users.update', editing.value), { preserveScroll: true, onSuccess: reset });
    else form.post(route('users.store'), { preserveScroll: true, onSuccess: reset });
}
function remove(u) {
    if (confirm(`Gebruiker ${u.name} verwijderen?`)) router.delete(route('users.destroy', u.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Gebruikers" />
    <PageHeader title="Gebruikers & rollen" subtitle="Planner, bedrijfsleider, technisch manager en administratie werken op kantoor; inspecteurs zien alleen hun eigen route. Klanten krijgen geen account." />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <table class="table">
                <thead><tr><th>Naam</th><th>E-mail</th><th>Rol</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id">
                        <td class="font-semibold">{{ u.name }}<div v-if="u.inspector" class="text-xs font-normal text-muted">Inspecteur: {{ u.inspector }}</div></td>
                        <td class="text-muted">{{ u.email }}</td>
                        <td><span class="badge bg-navy-100 text-navy-800 dark:bg-navy-800 dark:text-navy-100">{{ u.roleLabel }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <button class="btn btn-ghost btn-sm" @click="edit(u)"><Icon name="pencil" :size="14" /></button>
                            <button class="btn btn-danger btn-sm ml-1" @click="remove(u)"><Icon name="trash" :size="14" /></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form class="card space-y-4 p-6" @submit.prevent="submit">
            <h2 class="card-title">{{ editing ? 'Gebruiker wijzigen' : 'Nieuwe gebruiker' }}</h2>
            <div>
                <label class="label">Naam</label>
                <input v-model="form.name" class="input" required />
                <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="label">E-mailadres</label>
                <input v-model="form.email" type="email" class="input" required />
                <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="label">Rol</label>
                <select v-model="form.role" class="input">
                    <option v-for="(label, key) in roles" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div>
                <label class="label">Wachtwoord {{ editing ? '(leeg = niet wijzigen)' : '' }}</label>
                <input v-model="form.password" type="password" class="input" :required="!editing" autocomplete="new-password" />
                <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1" :disabled="form.processing">{{ editing ? 'Opslaan' : 'Aanmaken' }}</button>
                <button v-if="editing" type="button" class="btn btn-ghost" @click="reset">Annuleren</button>
            </div>
        </form>
    </div>
</template>
