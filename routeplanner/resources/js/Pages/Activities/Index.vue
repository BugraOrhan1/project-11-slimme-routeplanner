<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ activities: Array, certificates: Array });

// Activiteiten
const editingActivity = ref(null);
const activityForm = useForm({ name: '', duration_minutes: 20, per_tank: false, certificate_id: '' });

function editActivity(a) {
    editingActivity.value = a.id;
    Object.assign(activityForm, { name: a.name, duration_minutes: a.duration_minutes, per_tank: a.per_tank, certificate_id: a.certificate_id ?? '' });
}
function resetActivity() {
    editingActivity.value = null;
    activityForm.reset();
    activityForm.clearErrors();
}
function saveActivity() {
    const data = { ...activityForm.data(), certificate_id: activityForm.certificate_id || null };
    if (editingActivity.value) {
        activityForm.transform(() => data).put(route('activities.update', editingActivity.value), { preserveScroll: true, onSuccess: resetActivity });
    } else {
        activityForm.transform(() => data).post(route('activities.store'), { preserveScroll: true, onSuccess: resetActivity });
    }
}
function removeActivity(a) {
    if (confirm(`Activiteit "${a.name}" verwijderen?`)) router.delete(route('activities.destroy', a.id), { preserveScroll: true });
}

// Certificaten
const editingCertificate = ref(null);
const certificateForm = useForm({ name: '', description: '' });
function editCertificate(c) {
    editingCertificate.value = c.id;
    Object.assign(certificateForm, { name: c.name, description: c.description ?? '' });
}
function resetCertificate() {
    editingCertificate.value = null;
    certificateForm.reset();
}
function saveCertificate() {
    if (editingCertificate.value) certificateForm.put(route('certificates.update', editingCertificate.value), { preserveScroll: true, onSuccess: resetCertificate });
    else certificateForm.post(route('certificates.store'), { preserveScroll: true, onSuccess: resetCertificate });
}
function removeCertificate(c) {
    if (confirm(`Certificaat "${c.name}" verwijderen? Het wordt ook bij inspecteurs en activiteiten weggehaald.`)) {
        router.delete(route('certificates.destroy', c.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Werkzaamheden" />
    <PageHeader
        title="Werkzaamheden & certificaten"
        subtitle="Per inspectie worden 3–4 soorten werkzaamheden gedaan, elk met een vaste tijd. Al het werk is geaccrediteerd: per soort werk is een certificaat nodig."
    />

    <div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-100">
        <Icon name="alert" class="mt-0.5" />
        <p>De activiteiten en tijden hieronder zijn een eerste aanname. Marijn stuurt het echte overzicht nog door (gesprek 23-09) – pas ze dan hier aan.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="card xl:col-span-3">
            <div class="card-header"><h2 class="card-title">Activiteiten</h2></div>
            <table class="table">
                <thead><tr><th>Activiteit</th><th>Duur</th><th>Vereist certificaat</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="a in activities" :key="a.id" :class="editingActivity === a.id ? 'bg-navy-50 dark:bg-navy-800/40' : ''">
                        <td class="font-semibold">{{ a.name }}</td>
                        <td>{{ a.duration_minutes }} min<span v-if="a.per_tank" class="text-muted"> per tank</span></td>
                        <td>
                            <span v-if="a.certificate" class="badge bg-surface-2 ring-1 ring-line"><Icon name="certificate" :size="12" />{{ a.certificate.name }}</span>
                            <span v-else class="text-muted">–</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <button class="btn btn-ghost btn-sm" @click="editActivity(a)"><Icon name="pencil" :size="14" /></button>
                            <button class="btn btn-danger btn-sm ml-1" @click="removeActivity(a)"><Icon name="trash" :size="14" /></button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <form class="grid gap-3 border-t border-line p-5 md:grid-cols-12" @submit.prevent="saveActivity">
                <div class="md:col-span-4">
                    <label class="label text-xs">{{ editingActivity ? 'Activiteit wijzigen' : 'Nieuwe activiteit' }}</label>
                    <input v-model="activityForm.name" class="input" placeholder="Naam" required />
                    <p v-if="activityForm.errors.name" class="error">{{ activityForm.errors.name }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="label text-xs">Minuten</label>
                    <input v-model="activityForm.duration_minutes" type="number" min="5" class="input" required />
                </div>
                <div class="md:col-span-3">
                    <label class="label text-xs">Certificaat</label>
                    <select v-model="activityForm.certificate_id" class="input">
                        <option value="">Geen</option>
                        <option v-for="c in certificates" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 pb-2 text-sm md:col-span-3 md:self-end">
                    <input v-model="activityForm.per_tank" type="checkbox" class="accent-klink-600" /> Tijd per tank
                </label>
                <div class="flex gap-2 md:col-span-12">
                    <button class="btn btn-primary" :disabled="activityForm.processing">{{ editingActivity ? 'Opslaan' : 'Toevoegen' }}</button>
                    <button v-if="editingActivity" type="button" class="btn btn-ghost" @click="resetActivity">Annuleren</button>
                </div>
            </form>
        </div>

        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Certificaten</h2></div>
            <ul class="divide-y divide-line">
                <li v-for="c in certificates" :key="c.id" class="flex items-start justify-between gap-3 px-5 py-3">
                    <div>
                        <div class="font-semibold">{{ c.name }}</div>
                        <div class="text-xs text-muted">{{ c.description }}</div>
                        <div class="mt-1 text-xs text-muted">{{ c.inspectors_count }} inspecteur(s) · {{ c.activities_count }} activiteit(en)</div>
                    </div>
                    <div class="whitespace-nowrap">
                        <button class="btn btn-ghost btn-sm" @click="editCertificate(c)"><Icon name="pencil" :size="14" /></button>
                        <button class="btn btn-danger btn-sm ml-1" @click="removeCertificate(c)"><Icon name="trash" :size="14" /></button>
                    </div>
                </li>
            </ul>
            <form class="space-y-3 border-t border-line p-5" @submit.prevent="saveCertificate">
                <label class="label text-xs">{{ editingCertificate ? 'Certificaat wijzigen' : 'Nieuw certificaat' }}</label>
                <input v-model="certificateForm.name" class="input" placeholder="Naam" required />
                <input v-model="certificateForm.description" class="input" placeholder="Omschrijving" />
                <div class="flex gap-2">
                    <button class="btn btn-primary" :disabled="certificateForm.processing">{{ editingCertificate ? 'Opslaan' : 'Toevoegen' }}</button>
                    <button v-if="editingCertificate" type="button" class="btn btn-ghost" @click="resetCertificate">Annuleren</button>
                </div>
            </form>
        </div>
    </div>
</template>
