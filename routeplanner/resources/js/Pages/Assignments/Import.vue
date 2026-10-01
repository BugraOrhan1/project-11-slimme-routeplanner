<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ activities: Array });

const form = useForm({ file: null });
const submit = () => form.post(route('assignments.import.store'), { forceFormData: true, onSuccess: () => form.reset() });

const example = `klant;station;adres;postcode;plaats;deadline;maand;activiteiten;tanks
Shell;Shell Dordrecht Noord;Laan der Verenigde Naties 100;;Dordrecht;2026-11-15;2026-11;${props.activities.slice(0, 3).join('|')};Diesel|Euro 95|Euro 98`;

function downloadExample() {
    const url = URL.createObjectURL(new Blob([example], { type: 'text/csv' }));
    const a = Object.assign(document.createElement('a'), { href: url, download: 'voorbeeld-opdrachten.csv' });
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <Head title="Opdrachten importeren" />
    <PageHeader
        title="Opdrachten importeren"
        subtitle="Klink werkt met jaarcontracten: begin van het jaar is 80–90% van het werk bekend. Importeer de stations per maandblok in één keer in plaats van één voor één."
    >
        <Link :href="route('assignments.index')" class="btn btn-ghost">Terug naar opdrachten</Link>
    </PageHeader>

    <div class="grid gap-6 xl:grid-cols-2">
        <form class="card p-6" @submit.prevent="submit">
            <h2 class="card-title">CSV-bestand uploaden</h2>
            <p class="mt-1 text-sm text-muted">Scheidingsteken ; of , · eerste regel bevat de kolomnamen.</p>

            <label class="mt-5 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-line px-6 py-10 text-center hover:bg-surface-2">
                <Icon name="upload" :size="28" class="text-muted" />
                <span class="text-sm font-semibold">{{ form.file ? form.file.name : 'Kies een .csv-bestand' }}</span>
                <input type="file" accept=".csv,text/csv" class="hidden" @change="form.file = $event.target.files[0]" />
            </label>
            <p v-if="form.errors.file" class="error">{{ form.errors.file }}</p>

            <div v-if="form.progress" class="mt-3 h-1.5 overflow-hidden rounded bg-surface-2">
                <div class="h-full bg-klink-600" :style="{ width: `${form.progress.percentage}%` }"></div>
            </div>

            <button type="submit" class="btn btn-primary mt-5" :disabled="!form.file || form.processing">
                {{ form.processing ? 'Bezig met importeren…' : 'Importeren' }}
            </button>
        </form>

        <div class="card p-6">
            <h2 class="card-title">Kolommen</h2>
            <ul class="mt-3 space-y-1.5 text-sm">
                <li><strong>klant</strong> – bijv. Shell, BP, TotalEnergies (wordt aangemaakt als hij nog niet bestaat)</li>
                <li><strong>station</strong> – naam van het tankstation</li>
                <li><strong>adres, postcode, plaats</strong> – wordt via PDOK omgezet naar coördinaten</li>
                <li><strong>deadline</strong> – datum (jjjj-mm-dd) die de klant oplegt</li>
                <li><strong>maand</strong> – maandblok (jjjj-mm), optioneel</li>
                <li><strong>activiteiten</strong> – namen gescheiden door <code>|</code>, optioneel</li>
                <li><strong>tanks</strong> – producten gescheiden door <code>|</code>, alleen bij nieuwe stations</li>
            </ul>
            <p class="mt-4 text-xs text-muted">Beschikbare activiteiten: {{ activities.join(', ') }}</p>
            <pre class="mt-4 overflow-x-auto rounded-md bg-surface-2 p-3 text-xs">{{ example }}</pre>
            <button class="btn btn-ghost btn-sm mt-3" @click="downloadExample">Voorbeeldbestand downloaden</button>
        </div>
    </div>
</template>
