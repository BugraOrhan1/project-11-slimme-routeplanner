<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import RouteMap from '../../Components/RouteMap.vue';
import Icon from '../../Components/Icon.vue';
import { geocode } from '../../geocode';

const props = defineProps({ customer: Object, location: Object, products: Array });

const form = useForm({
    name: props.location?.name ?? `${props.customer.name} `,
    address: props.location?.address ?? '',
    postal_code: props.location?.postal_code ?? '',
    city: props.location?.city ?? '',
    region: props.location?.region ?? '',
    latitude: props.location?.latitude ?? null,
    longitude: props.location?.longitude ?? null,
    open_from: props.location?.open_from ?? '',
    open_until: props.location?.open_until ?? '',
    access_notes: props.location?.access_notes ?? '',
    tanks: props.location?.tanks ?? [
        { product: 'Diesel', kind: 'ondergronds', capacity_liters: null, last_inspection: null },
        { product: 'Euro 95', kind: 'ondergronds', capacity_liters: null, last_inspection: null },
    ],
});

// US11 – adres omzetten naar coördinaten (PDOK Locatieserver)
const geo = ref({ busy: false, error: '', label: '' });
async function lookup() {
    geo.value = { busy: true, error: '', label: '' };
    try {
        const json = await geocode(`${form.address} ${form.postal_code} ${form.city}`.trim());
        Object.assign(form, { latitude: json.lat, longitude: json.lng, region: json.province ?? form.region });
        if (json.postalCode) form.postal_code = json.postalCode;
        if (json.city) form.city = json.city;
        geo.value = { busy: false, error: '', label: json.label };
    } catch (e) {
        geo.value = { busy: false, error: e.message || 'Adres niet gevonden.', label: '' };
    }
}

const points = computed(() => (form.latitude ? [{ lat: form.latitude, lng: form.longitude, title: form.name, color: '#980022' }] : []));

const addTank = () => form.tanks.push({ product: 'Euro 98', kind: 'ondergronds', capacity_liters: null, last_inspection: null });

function submit() {
    if (props.location) form.put(route('locations.update', props.location.id));
    else form.post(route('locations.store', props.customer.id));
}
</script>

<template>
    <Head :title="location ? 'Station bewerken' : 'Nieuw station'" />
    <PageHeader :title="location ? `${location.name} bewerken` : 'Nieuw station'" :subtitle="`Klant: ${customer.name}`">
        <Link :href="route('customers.show', customer.id)" class="btn btn-ghost">Terug</Link>
    </PageHeader>

    <form class="grid gap-6 xl:grid-cols-5" @submit.prevent="submit">
        <div class="space-y-6 xl:col-span-3">
            <div class="card grid gap-4 p-6 md:grid-cols-6">
                <div class="md:col-span-6">
                    <label class="label">Naam station *</label>
                    <input v-model="form.name" class="input" required />
                    <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                </div>
                <div class="md:col-span-3">
                    <label class="label">Straat en huisnummer *</label>
                    <input v-model="form.address" class="input" required />
                    <p v-if="form.errors.address" class="error">{{ form.errors.address }}</p>
                </div>
                <div>
                    <label class="label">Postcode</label>
                    <input v-model="form.postal_code" class="input" />
                </div>
                <div class="md:col-span-2">
                    <label class="label">Plaats *</label>
                    <input v-model="form.city" class="input" required />
                    <p v-if="form.errors.city" class="error">{{ form.errors.city }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 md:col-span-6">
                    <button type="button" class="btn btn-secondary" :disabled="geo.busy || !form.address || !form.city" @click="lookup">
                        <Icon name="pin" :size="16" /> {{ geo.busy ? 'Zoeken…' : 'Adres opzoeken' }}
                    </button>
                    <span v-if="geo.label" class="text-sm text-emerald-700 dark:text-emerald-300">✓ Gevonden: {{ geo.label }}</span>
                    <span v-else-if="geo.error" class="error mt-0">{{ geo.error }}</span>
                    <span v-else-if="form.latitude" class="text-sm text-muted">Coördinaten: {{ Number(form.latitude).toFixed(5) }}, {{ Number(form.longitude).toFixed(5) }}</span>
                    <p v-if="form.errors.latitude" class="error w-full">{{ form.errors.latitude }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="label">Regio (provincie)</label>
                    <input v-model="form.region" class="input" />
                </div>
                <div class="md:col-span-2">
                    <label class="label">Open vanaf</label>
                    <input v-model="form.open_from" type="time" class="input" />
                </div>
                <div class="md:col-span-2">
                    <label class="label">Open tot</label>
                    <input v-model="form.open_until" type="time" class="input" />
                    <p v-if="form.errors.open_until" class="error">{{ form.errors.open_until }}</p>
                </div>
                <div class="md:col-span-6">
                    <label class="label">Toegangsinstructies</label>
                    <textarea v-model="form.access_notes" rows="2" class="input" placeholder="Bijv. melden bij de kassa"></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Tanks ({{ form.tanks.length }})</h2>
                    <button type="button" class="btn btn-ghost btn-sm" @click="addTank"><Icon name="plus" :size="14" /> Tank</button>
                </div>
                <div class="space-y-3 p-5">
                    <p class="text-xs text-muted">Altijd een tank voor diesel en benzine; soms losse tanks voor Euro 98 e.d. Meer tanks = langere inspectie.</p>
                    <div v-for="(t, i) in form.tanks" :key="i" class="grid items-end gap-3 md:grid-cols-12">
                        <div class="md:col-span-3">
                            <label class="label text-xs">Product</label>
                            <input v-model="t.product" list="products" class="input" required />
                        </div>
                        <div class="md:col-span-3">
                            <label class="label text-xs">Soort</label>
                            <select v-model="t.kind" class="input">
                                <option value="ondergronds">Ondergronds</option>
                                <option value="bovengronds">Bovengronds</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="label text-xs">Liters</label>
                            <input v-model="t.capacity_liters" type="number" min="0" class="input" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="label text-xs">Laatste keuring</label>
                            <input v-model="t.last_inspection" type="date" class="input" />
                        </div>
                        <button type="button" class="btn btn-danger md:col-span-1" title="Tank verwijderen" @click="form.tanks.splice(i, 1)"><Icon name="trash" :size="14" /></button>
                    </div>
                    <datalist id="products"><option v-for="p in products" :key="p" :value="p" /></datalist>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <div class="card p-3"><RouteMap :points="points" height="340px" :roads="false" /></div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1" :disabled="form.processing">Opslaan</button>
                <Link :href="route('customers.show', customer.id)" class="btn btn-ghost">Annuleren</Link>
            </div>
        </div>
    </form>
</template>
