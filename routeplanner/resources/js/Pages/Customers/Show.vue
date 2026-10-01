<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import RouteMap from '../../Components/RouteMap.vue';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ customer: Object });

const editing = ref(false);
const form = useForm({
    name: props.customer.name,
    contact_person: props.customer.contact_person ?? '',
    email: props.customer.email ?? '',
    phone: props.customer.phone ?? '',
    color: props.customer.color,
});
const save = () => form.put(route('customers.update', props.customer.id), { onSuccess: () => (editing.value = false) });

const points = computed(() =>
    props.customer.locations.map((l) => ({ lat: l.latitude, lng: l.longitude, title: l.name, sub: `${l.address}, ${l.city}`, color: props.customer.color })),
);

function removeCustomer() {
    if (confirm(`Klant ${props.customer.name} met alle stations en opdrachten verwijderen?`)) {
        router.delete(route('customers.destroy', props.customer.id));
    }
}
function removeLocation(l) {
    if (confirm(`Station ${l.name} verwijderen?`)) {
        router.delete(route('locations.destroy', l.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="customer.name" />
    <PageHeader :title="customer.name" :subtitle="`${customer.locations.length} stations`">
        <Link :href="route('customers.index')" class="btn btn-ghost">Alle klanten</Link>
        <button class="btn btn-ghost" @click="editing = !editing"><Icon name="pencil" :size="16" /> Gegevens</button>
        <Link :href="route('locations.create', customer.id)" class="btn btn-primary"><Icon name="plus" :size="16" /> Station toevoegen</Link>
    </PageHeader>

    <form v-if="editing" class="card mb-6 grid gap-4 p-5 md:grid-cols-6" @submit.prevent="save">
        <div class="md:col-span-2"><label class="label">Naam</label><input v-model="form.name" class="input" required /></div>
        <div><label class="label">Contactpersoon</label><input v-model="form.contact_person" class="input" /></div>
        <div><label class="label">E-mail</label><input v-model="form.email" type="email" class="input" /></div>
        <div><label class="label">Telefoon</label><input v-model="form.phone" class="input" /></div>
        <div>
            <label class="label">Kleur</label>
            <div class="flex gap-2">
                <input v-model="form.color" type="color" class="h-9 w-12 cursor-pointer rounded border border-line" />
                <button class="btn btn-primary flex-1" :disabled="form.processing">Opslaan</button>
            </div>
        </div>
        <div class="md:col-span-6 flex justify-end">
            <button type="button" class="btn btn-danger btn-sm" @click="removeCustomer"><Icon name="trash" :size="14" /> Klant verwijderen</button>
        </div>
    </form>

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="card overflow-hidden xl:col-span-3">
            <table class="table">
                <thead>
                    <tr><th>Station</th><th>Tanks</th><th>Openingstijden</th><th>Open</th><th></th></tr>
                </thead>
                <tbody>
                    <tr v-for="l in customer.locations" :key="l.id">
                        <td>
                            <div class="font-semibold">{{ l.name }}</div>
                            <div class="text-xs text-muted">{{ l.address }}, {{ l.city }} · {{ l.region }}</div>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <span v-for="t in l.tanks" :key="t.id" class="badge bg-surface-2 ring-1 ring-line">{{ t.product }}</span>
                            </div>
                        </td>
                        <td class="text-xs whitespace-nowrap">
                            <template v-if="l.open_from || l.open_until">{{ (l.open_from || '').slice(0, 5) }} – {{ (l.open_until || '').slice(0, 5) }}</template>
                            <span v-else class="text-muted">onbekend</span>
                        </td>
                        <td>
                            <Link v-if="l.open_count" :href="route('assignments.index', { search: l.name, status: 'open' })" class="link">{{ l.open_count }}</Link>
                            <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <Link :href="route('assignments.create', { location: l.id })" class="btn btn-ghost btn-sm" title="Opdracht toevoegen"><Icon name="plus" :size="14" /></Link>
                            <Link :href="route('locations.edit', l.id)" class="btn btn-ghost btn-sm ml-1" title="Bewerken"><Icon name="pencil" :size="14" /></Link>
                            <button class="btn btn-danger btn-sm ml-1" title="Verwijderen" @click="removeLocation(l)"><Icon name="trash" :size="14" /></button>
                        </td>
                    </tr>
                    <tr v-if="!customer.locations.length"><td colspan="5" class="py-8 text-center text-muted">Nog geen stations.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="card p-3 xl:col-span-2">
            <RouteMap :points="points" height="520px" />
        </div>
    </div>
</template>
