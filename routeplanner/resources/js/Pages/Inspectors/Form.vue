<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Icon from '../../Components/Icon.vue';
import { geocode } from '../../geocode';

const props = defineProps({ inspector: Object, certificates: Array, users: Array, office: Object });

const form = useForm({
    name: props.inspector?.name ?? '',
    phone: props.inspector?.phone ?? '',
    user_id: props.inspector?.user_id ?? '',
    start_address: props.inspector?.start_address ?? props.office.address,
    start_latitude: props.inspector?.start_latitude ?? props.office.latitude,
    start_longitude: props.inspector?.start_longitude ?? props.office.longitude,
    work_start: props.inspector?.work_start ?? '07:30',
    work_end: props.inspector?.work_end ?? '16:30',
    color: props.inspector?.color ?? '#1d6a88',
    active: props.inspector?.active ?? true,
    certificates: props.inspector?.certificates ?? [],
});

const has = (id) => form.certificates.some((c) => c.id === id);
function toggle(id) {
    if (has(id)) form.certificates = form.certificates.filter((c) => c.id !== id);
    else form.certificates.push({ id, valid_until: '' });
}
const entry = (id) => form.certificates.find((c) => c.id === id);

// Startadres opzoeken (kantoor of thuis)
const geo = ref({ busy: false, msg: '', ok: false });
async function lookup() {
    geo.value = { busy: true, msg: '', ok: false };
    try {
        const json = await geocode(form.start_address);
        Object.assign(form, { start_latitude: json.lat, start_longitude: json.lng, start_address: json.label });
        geo.value = { busy: false, msg: `Gevonden: ${json.label}`, ok: true };
    } catch (e) {
        geo.value = { busy: false, msg: e.message, ok: false };
    }
}
function useOffice() {
    Object.assign(form, { start_address: props.office.address, start_latitude: props.office.latitude, start_longitude: props.office.longitude });
    geo.value = { busy: false, msg: 'Start vanaf kantoor Dordrecht', ok: true };
}

const submit = () => (props.inspector ? form.put(route('inspectors.update', props.inspector.id)) : form.post(route('inspectors.store')));
</script>

<template>
    <Head :title="inspector ? 'Inspecteur bewerken' : 'Nieuwe inspecteur'" />
    <PageHeader :title="inspector ? inspector.name : 'Nieuwe inspecteur'">
        <Link :href="route('inspectors.index')" class="btn btn-ghost">Terug</Link>
    </PageHeader>

    <form class="grid gap-6 xl:grid-cols-2" @submit.prevent="submit">
        <div class="card grid gap-4 p-6 md:grid-cols-2">
            <div>
                <label class="label">Naam *</label>
                <input v-model="form.name" class="input" required />
                <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="label">Telefoon</label>
                <input v-model="form.phone" class="input" />
            </div>
            <div class="md:col-span-2">
                <label class="label">Gekoppeld account (rol inspecteur)</label>
                <select v-model="form.user_id" class="input">
                    <option value="">Geen account</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} – {{ u.email }}</option>
                </select>
                <p class="mt-1 text-xs text-muted">Met een eigen account ziet de inspecteur zijn eigen route. Accounts maak je aan bij Gebruikers.</p>
            </div>
            <div class="md:col-span-2">
                <label class="label">Startadres *</label>
                <div class="flex gap-2">
                    <input v-model="form.start_address" class="input" required />
                    <button type="button" class="btn btn-secondary whitespace-nowrap" :disabled="geo.busy" @click="lookup"><Icon name="pin" :size="16" /> Opzoeken</button>
                    <button type="button" class="btn btn-ghost whitespace-nowrap" @click="useOffice">Kantoor</button>
                </div>
                <p v-if="geo.msg" class="mt-1 text-xs" :class="geo.ok ? 'text-emerald-700 dark:text-emerald-300' : 'error'">{{ geo.msg }}</p>
                <p v-if="form.errors.start_latitude" class="error">{{ form.errors.start_latitude }}</p>
            </div>
            <div>
                <label class="label">Begintijd *</label>
                <input v-model="form.work_start" type="time" class="input" required />
            </div>
            <div>
                <label class="label">Eindtijd *</label>
                <input v-model="form.work_end" type="time" class="input" required />
                <p v-if="form.errors.work_end" class="error">{{ form.errors.work_end }}</p>
            </div>
            <div>
                <label class="label">Kleur op de kaart</label>
                <input v-model="form.color" type="color" class="h-9 w-16 cursor-pointer rounded border border-line" />
            </div>
            <label class="flex items-center gap-2 self-end pb-2 text-sm">
                <input v-model="form.active" type="checkbox" class="accent-klink-600" /> Actief (beschikbaar voor planning)
            </label>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Certificaten</h2></div>
                <div class="space-y-2 p-5">
                    <p class="mb-2 text-xs text-muted">Alleen met een geldig certificaat wordt de inspecteur ingepland voor dat werk.</p>
                    <div v-for="c in certificates" :key="c.id" class="flex flex-wrap items-center gap-3 rounded-md border border-line p-3">
                        <label class="flex flex-1 cursor-pointer items-center gap-2 text-sm font-semibold">
                            <input type="checkbox" class="accent-klink-600" :checked="has(c.id)" @change="toggle(c.id)" /> {{ c.name }}
                        </label>
                        <div v-if="has(c.id)" class="flex items-center gap-2 text-xs text-muted">
                            geldig tot
                            <input v-model="entry(c.id).valid_until" type="date" class="input w-40 py-1" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1" :disabled="form.processing">Opslaan</button>
                <Link :href="route('inspectors.index')" class="btn btn-ghost">Annuleren</Link>
            </div>
        </div>
    </form>
</template>
