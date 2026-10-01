<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import { formatMinutes } from '../../format';

const props = defineProps({
    assignment: Object,
    locationId: Number,
    customers: Array,
    activities: Array,
    statuses: Array,
});

const form = useForm({
    location_id: props.assignment?.location_id ?? props.locationId ?? '',
    status: props.assignment?.status ?? 'open',
    deadline: props.assignment?.deadline ?? '',
    plan_month: props.assignment?.plan_month ?? '',
    priority: props.assignment?.priority ?? 2,
    notes: props.assignment?.notes ?? '',
    // Standaard de drie basiswerkzaamheden aanvinken (per inspectie 3–4 activiteiten)
    activity_ids: props.assignment?.activity_ids ?? props.activities.slice(0, 3).map((a) => a.id),
});

const selectedLocation = computed(() => props.customers.flatMap((c) => c.locations).find((l) => l.id === Number(form.location_id)));

// Geschatte duur live meerekenen: vaste tijd of tijd per tank
const estimate = computed(() => {
    const tanks = selectedLocation.value?.tanks ?? 1;
    return props.activities
        .filter((a) => form.activity_ids.includes(a.id))
        .reduce((sum, a) => sum + (a.perTank ? a.minutes * Math.max(1, tanks) : a.minutes), 0);
});

function submit() {
    if (props.assignment) {
        form.put(route('assignments.update', props.assignment.id));
    } else {
        form.post(route('assignments.store'));
    }
}
</script>

<template>
    <Head :title="assignment ? 'Opdracht bewerken' : 'Nieuwe opdracht'" />
    <PageHeader :title="assignment ? 'Opdracht bewerken' : 'Nieuwe inspectieopdracht'" subtitle="Eén opdracht is één bezoek aan een tankstation, met de werkzaamheden die daar gedaan moeten worden." />

    <form class="grid gap-6 xl:grid-cols-3" @submit.prevent="submit">
        <div class="card space-y-5 p-6 xl:col-span-2">
            <div>
                <label class="label" for="location">Station (locatie) *</label>
                <select id="location" v-model="form.location_id" class="input" required>
                    <option value="" disabled>Kies een station</option>
                    <optgroup v-for="c in customers" :key="c.id" :label="c.name">
                        <option v-for="l in c.locations" :key="l.id" :value="l.id">{{ l.name }} – {{ l.city }} ({{ l.tanks }} tanks)</option>
                    </optgroup>
                </select>
                <p v-if="form.errors.location_id" class="error">{{ form.errors.location_id }}</p>
                <p class="mt-1 text-xs text-muted">Staat het station er niet bij? Voeg het toe via <Link :href="route('customers.index')" class="link">Klanten &amp; stations</Link>.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label class="label" for="deadline">Deadline (van de klant) *</label>
                    <input id="deadline" v-model="form.deadline" type="date" class="input" required />
                    <p v-if="form.errors.deadline" class="error">{{ form.errors.deadline }}</p>
                </div>
                <div>
                    <label class="label" for="month">Maandblok</label>
                    <input id="month" v-model="form.plan_month" type="month" class="input" />
                    <p v-if="form.errors.plan_month" class="error">{{ form.errors.plan_month }}</p>
                </div>
                <div>
                    <label class="label" for="priority">Prioriteit</label>
                    <select id="priority" v-model="form.priority" class="input">
                        <option :value="1">Hoog (spoed)</option>
                        <option :value="2">Normaal</option>
                        <option :value="3">Laag</option>
                    </select>
                </div>
            </div>

            <div>
                <span class="label">Werkzaamheden *</span>
                <div class="grid gap-2 md:grid-cols-2">
                    <label
                        v-for="a in activities"
                        :key="a.id"
                        class="flex cursor-pointer items-start gap-3 rounded-md border border-line p-3 transition hover:bg-surface-2"
                        :class="form.activity_ids.includes(a.id) ? 'border-navy-400 bg-navy-50 dark:bg-navy-800/40' : ''"
                    >
                        <input v-model="form.activity_ids" type="checkbox" :value="a.id" class="mt-1 accent-klink-600" />
                        <span>
                            <span class="block text-sm font-semibold">{{ a.name }}</span>
                            <span class="block text-xs text-muted">
                                {{ a.minutes }} min{{ a.perTank ? ' per tank' : '' }}<template v-if="a.certificate"> · certificaat: {{ a.certificate }}</template>
                            </span>
                        </span>
                    </label>
                </div>
                <p v-if="form.errors.activity_ids" class="error">{{ form.errors.activity_ids }}</p>
            </div>

            <div>
                <label class="label" for="notes">Opmerkingen</label>
                <textarea id="notes" v-model="form.notes" rows="3" class="input" placeholder="Bijv. sleutel ophalen bij de shop"></textarea>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <div class="text-xs font-bold tracking-wide text-muted uppercase">Geschatte duur op locatie</div>
                <div class="mt-1 text-3xl font-bold">{{ formatMinutes(estimate) }}</div>
                <p class="mt-2 text-xs text-muted">
                    Som van de gekozen werkzaamheden<template v-if="selectedLocation">, met {{ selectedLocation.tanks }} tanks op dit station</template>. Meer tanks = langere inspectie.
                </p>
            </div>
            <div class="card p-6">
                <label class="label" for="status">Status</label>
                <select id="status" v-model="form.status" class="input capitalize">
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1" :disabled="form.processing">Opslaan</button>
                    <Link :href="route('assignments.index')" class="btn btn-ghost">Annuleren</Link>
                </div>
            </div>
        </div>
    </form>
</template>
