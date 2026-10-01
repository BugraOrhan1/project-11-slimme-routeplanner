<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import RouteMap from '../../Components/RouteMap.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
import { formatLongDate, formatMinutes, shiftDate } from '../../format';
import { toMapRoute } from '../../mapRoute';

const props = defineProps({
    date: String,
    inspectors: Array,
    candidates: Array,
    candidateCount: Number,
    proposals: Array,
});

// US09 – beschikbare inspecteurs: standaard iedereen zonder goedgekeurde route
const form = useForm({
    date: props.date,
    inspector_ids: props.inspectors.filter((i) => i.route?.status !== 'goedgekeurd').map((i) => i.id),
});

const goTo = (date) => router.get(route('planning.create'), { date }, { preserveScroll: true });
const skipWeekend = (iso, dir) => {
    let d = shiftDate(iso, dir);
    while ([0, 6].includes(new Date(`${d}T12:00:00`).getDay())) d = shiftDate(d, dir);
    return d;
};

const propose = () => form.post(route('planning.propose'), { preserveScroll: true });

const highlight = ref(null);
const mapRoutes = computed(() => props.proposals.map(toMapRoute));
const plannedIds = computed(() => new Set(props.proposals.flatMap((r) => r.stops.map((s) => s.assignment.id))));
const openPoints = computed(() =>
    props.candidates
        .filter((a) => !plannedIds.value.has(a.id))
        .map((a) => ({ lat: a.location.lat, lng: a.location.lng, title: a.location.name, sub: `Deadline ${a.deadline}`, color: '#9aaab3' })),
);

const pending = computed(() => props.proposals.filter((r) => r.status === 'voorstel'));
const approve = (r) => router.post(route('routes.approve', r.id), {}, { preserveScroll: true });
const approveAll = () => router.post(route('planning.approve-all'), { date: props.date }, { preserveScroll: true });
function reject(r) {
    if (confirm(`Voorstel voor ${r.inspector.name} afwijzen? De opdrachten komen weer vrij.`)) {
        router.delete(route('routes.destroy', r.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Routeplanner" />
    <PageHeader
        title="Routeplanner"
        subtitle="De app stelt efficiënte routes voor: stations van verschillende klanten in dezelfde regio, rekening houdend met deadlines, certificaten en de werkdag. Jij beslist of de route doorgaat."
    />

    <div class="grid gap-6 xl:grid-cols-12">
        <!-- Stap 1 + 2: datum en inspecteurs -->
        <div class="space-y-6 xl:col-span-4">
            <div class="card p-5">
                <div class="text-xs font-bold tracking-wide text-muted uppercase">1. Kies de dag</div>
                <div class="mt-3 flex items-center gap-2">
                    <button class="btn btn-ghost px-2" title="Vorige werkdag" @click="goTo(skipWeekend(date, -1))"><Icon name="left" :size="16" /></button>
                    <input type="date" :value="date" class="input" @change="goTo($event.target.value)" />
                    <button class="btn btn-ghost px-2" title="Volgende werkdag" @click="goTo(skipWeekend(date, 1))"><Icon name="right" :size="16" /></button>
                </div>
                <div class="mt-2 text-sm font-semibold capitalize">{{ formatLongDate(date) }}</div>
            </div>

            <form class="card" @submit.prevent="propose">
                <div class="card-header">
                    <div class="text-xs font-bold tracking-wide text-muted uppercase">2. Beschikbare inspecteurs</div>
                    <span class="text-xs text-muted">{{ form.inspector_ids.length }} gekozen</span>
                </div>
                <ul class="divide-y divide-line">
                    <li v-for="i in inspectors" :key="i.id">
                        <label class="flex cursor-pointer items-start gap-3 px-5 py-3 hover:bg-surface-2" :class="i.route?.status === 'goedgekeurd' ? 'opacity-60' : ''">
                            <input
                                v-model="form.inspector_ids"
                                type="checkbox"
                                :value="i.id"
                                :disabled="i.route?.status === 'goedgekeurd'"
                                class="mt-1 accent-klink-600"
                            />
                            <span class="mt-1 h-3 w-3 shrink-0 rounded-full" :style="{ background: i.color }"></span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2 text-sm font-semibold">
                                    {{ i.name }}
                                    <StatusBadge v-if="i.route" :status="i.route.status" />
                                </span>
                                <span class="block truncate text-xs text-muted">{{ i.workStart }}–{{ i.workEnd }} · {{ i.startAddress }}</span>
                                <span class="block text-xs text-muted">{{ i.certificates.join(', ') || 'geen geldige certificaten' }}</span>
                            </span>
                        </label>
                    </li>
                </ul>
                <div class="border-t border-line p-5">
                    <p v-if="form.errors.inspector_ids" class="error mb-2">{{ form.errors.inspector_ids }}</p>
                    <button type="submit" class="btn btn-primary w-full" :disabled="form.processing || !form.inspector_ids.length">
                        <Icon name="route" :size="16" /> {{ form.processing ? 'Routes berekenen…' : 'Routes laten voorstellen' }}
                    </button>
                    <p class="mt-2 text-xs text-muted">
                        {{ candidateCount }} open opdrachten komen in aanmerking. Bestaande voorstellen voor deze dag worden vervangen; goedgekeurde routes blijven staan.
                    </p>
                </div>
            </form>
        </div>

        <!-- Stap 3: voorstellen op de kaart -->
        <div class="space-y-6 xl:col-span-8">
            <div class="card p-3">
                <RouteMap :routes="mapRoutes" :points="openPoints" :highlight="highlight" height="480px" />
                <p class="px-2 pt-2 text-xs text-muted">Grijze punten: open opdrachten die (nog) niet in een route zitten.</p>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-xs font-bold tracking-wide text-muted uppercase">3. Controleer en keur goed</div>
                <button v-if="pending.length > 1" class="btn btn-secondary btn-sm" @click="approveAll"><Icon name="check" :size="14" /> Alle voorstellen goedkeuren</button>
            </div>

            <div v-if="!proposals.length" class="card p-10 text-center text-muted">
                Nog geen routes voor deze dag. Kies inspecteurs en klik op <strong>Routes laten voorstellen</strong>.
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <div
                    v-for="r in proposals"
                    :key="r.id"
                    class="card overflow-hidden"
                    @mouseenter="highlight = r.id"
                    @mouseleave="highlight = null"
                >
                    <div class="h-1.5" :style="{ background: r.inspector.color }"></div>
                    <div class="card-header">
                        <div>
                            <div class="font-bold">{{ r.inspector.name }}</div>
                            <div class="text-xs text-muted">
                                {{ r.stops.length }} stations · {{ r.totalKm }} km · rijden {{ formatMinutes(r.driveMinutes) }} · terug {{ r.endTime }}
                            </div>
                        </div>
                        <StatusBadge :status="r.status" />
                    </div>
                    <ol class="space-y-1.5 px-5 py-3 text-sm">
                        <li v-for="s in r.stops" :key="s.id" class="flex items-center gap-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white" :style="{ background: r.inspector.color }">{{ s.position }}</span>
                            <span class="w-11 text-xs text-muted tabular-nums">{{ s.arrival }}</span>
                            <span class="min-w-0 flex-1 truncate">{{ s.assignment.location.name }}</span>
                            <span class="badge text-white" :style="{ background: s.assignment.location.customer.color }">{{ s.assignment.location.customer.name }}</span>
                        </li>
                    </ol>
                    <div class="flex flex-wrap gap-2 border-t border-line px-5 py-3">
                        <Link :href="route('routes.show', r.id)" class="btn btn-ghost btn-sm"><Icon name="pencil" :size="14" /> Bekijken / aanpassen</Link>
                        <template v-if="r.status === 'voorstel'">
                            <button class="btn btn-primary btn-sm" @click="approve(r)"><Icon name="check" :size="14" /> Goedkeuren</button>
                            <button class="btn btn-danger btn-sm" @click="reject(r)"><Icon name="x" :size="14" /> Afwijzen</button>
                        </template>
                        <span v-else class="self-center text-xs text-muted">Goedgekeurd door {{ r.approvedBy }} op {{ r.approvedAt }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
