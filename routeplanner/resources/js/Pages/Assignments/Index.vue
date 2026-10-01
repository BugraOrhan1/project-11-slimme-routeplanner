<script setup>
import { reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import DeadlineBadge from '../../Components/DeadlineBadge.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import { formatMinutes, formatMonth } from '../../format';

const props = defineProps({ assignments: Object, filters: Object, options: Object });

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    customer: props.filters.customer ?? '',
    activity: props.filters.activity ?? '',
    region: props.filters.region ?? '',
    month: props.filters.month ?? '',
    sort: props.filters.sort ?? 'deadline',
});

let timer;
watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        const query = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''));
        router.get(route('assignments.index'), query, { preserveState: true, preserveScroll: true, replace: true });
    }, 250);
});

function reset() {
    Object.assign(filters, { search: '', status: '', customer: '', activity: '', region: '', month: '', sort: 'deadline' });
}

function remove(a) {
    if (confirm(`Opdracht voor ${a.location.name} verwijderen?`)) {
        router.delete(route('assignments.destroy', a.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Opdrachten" />
    <PageHeader title="Inspectieopdrachten" subtitle="Alle opdrachten uit de contracten. Filter op klant, regio of maandblok om te zien wat er in de buurt samen kan.">
        <Link :href="route('assignments.import')" class="btn btn-ghost"><Icon name="upload" :size="16" /> Importeren</Link>
        <Link :href="route('assignments.create')" class="btn btn-primary"><Icon name="plus" :size="16" /> Nieuwe opdracht</Link>
    </PageHeader>

    <div class="card">
        <div class="grid gap-3 border-b border-line p-4 md:grid-cols-4 xl:grid-cols-8">
            <div class="relative md:col-span-2">
                <Icon name="search" :size="16" class="absolute top-2.5 left-3 text-muted" />
                <input v-model="filters.search" type="search" class="input pl-9" placeholder="Zoek station, plaats of adres" />
            </div>
            <select v-model="filters.status" class="input">
                <option value="">Alle statussen</option>
                <option v-for="s in options.statuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
            </select>
            <select v-model="filters.customer" class="input">
                <option value="">Alle klanten</option>
                <option v-for="c in options.customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="filters.region" class="input">
                <option value="">Alle regio's</option>
                <option v-for="r in options.regions" :key="r" :value="r">{{ r }}</option>
            </select>
            <select v-model="filters.activity" class="input">
                <option value="">Alle activiteiten</option>
                <option v-for="a in options.activities" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
            <input v-model="filters.month" type="month" class="input" title="Maand (maandblok of deadline)" />
            <div class="flex gap-2">
                <select v-model="filters.sort" class="input">
                    <option value="deadline">Op deadline</option>
                    <option value="prioriteit">Op prioriteit</option>
                    <option value="nieuw">Nieuwste eerst</option>
                </select>
                <button class="btn btn-ghost px-2.5" title="Filters wissen" @click="reset"><Icon name="x" :size="16" /></button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Station</th>
                        <th>Klant</th>
                        <th>Activiteiten</th>
                        <th>Maandblok</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Route</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in assignments.data" :key="a.id">
                        <td>
                            <div class="flex items-center gap-2 font-semibold">
                                <span v-if="a.priority === 1" class="badge bg-klink-600 text-white">Spoed</span>
                                {{ a.location.name }}
                            </div>
                            <div class="text-xs text-muted">{{ a.location.address }}, {{ a.location.city }} · {{ a.location.region }}</div>
                        </td>
                        <td>
                            <span class="badge text-white" :style="{ background: a.location.customer.color }">{{ a.location.customer.name }}</span>
                        </td>
                        <td class="whitespace-nowrap" :title="a.activities.map((x) => x.name).join('\n')">
                            <div class="text-sm">{{ a.activities.length }} werkzaamheden</div>
                            <div class="mt-0.5 text-xs text-muted">{{ a.location.tanks }} tanks · ± {{ formatMinutes(a.minutes) }}</div>
                        </td>
                        <td class="text-sm capitalize">{{ a.planMonth ? formatMonth(a.planMonth) : '–' }}</td>
                        <td><DeadlineBadge :deadline="a.deadline" :days-left="a.status === 'open' ? a.daysLeft : null" /></td>
                        <td><StatusBadge :status="a.status" /></td>
                        <td class="text-xs">
                            <Link v-if="a.route" :href="route('routes.show', a.route.id)" class="link">{{ a.route.date }}<br />{{ a.route.inspector }}</Link>
                            <span v-else class="text-muted">–</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <Link :href="route('assignments.edit', a.id)" class="btn btn-ghost btn-sm" title="Bewerken"><Icon name="pencil" :size="14" /></Link>
                            <button class="btn btn-danger btn-sm ml-1" title="Verwijderen" @click="remove(a)"><Icon name="trash" :size="14" /></button>
                        </td>
                    </tr>
                    <tr v-if="!assignments.data.length">
                        <td colspan="8" class="py-10 text-center text-muted">Geen opdrachten gevonden met deze filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :paginator="assignments" />
    </div>
</template>
