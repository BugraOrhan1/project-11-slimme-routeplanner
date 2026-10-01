<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '../Components/PageHeader.vue';
import RouteMap from '../Components/RouteMap.vue';
import Icon from '../Components/Icon.vue';
import { formatDate, formatLongDate, formatMinutes, mapsUrl, shiftDate, todayIso } from '../format';
import { toMapRoute } from '../mapRoute';

const props = defineProps({ date: String, hasInspector: Boolean, plan: Object, upcoming: Array });

const goTo = (date) => router.get(route('my-route'), { date }, { preserveScroll: true });
const start = (s) => router.post(route('my-route.start', s.id), {}, { preserveScroll: true });
const finish = (s) => router.post(route('my-route.finish', s.id), {}, { preserveScroll: true });

const mapRoutes = computed(() => (props.plan ? [toMapRoute(props.plan)] : []));
const done = computed(() => props.plan?.stops.filter((s) => s.finishedAt).length ?? 0);
</script>

<template>
    <Head title="Mijn route" />
    <PageHeader title="Mijn route" :subtitle="formatLongDate(date)">
        <button class="btn btn-ghost px-2.5" @click="goTo(shiftDate(date, -1))"><Icon name="left" :size="16" /></button>
        <button class="btn btn-ghost" @click="goTo(todayIso())">Vandaag</button>
        <button class="btn btn-ghost px-2.5" @click="goTo(shiftDate(date, 1))"><Icon name="right" :size="16" /></button>
    </PageHeader>

    <div v-if="!hasInspector" class="card p-8 text-center text-muted">Je account is nog niet gekoppeld aan een inspecteur. Vraag de planner om dit te doen.</div>

    <div v-else-if="!plan" class="grid gap-6 xl:grid-cols-3">
        <div class="card p-10 text-center xl:col-span-2">
            <Icon name="calendar" :size="32" class="mx-auto text-muted" />
            <p class="mt-3 font-semibold">Geen goedgekeurde route voor deze dag.</p>
            <p class="text-sm text-muted">De planner zet je route klaar zodra hij is goedgekeurd.</p>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Komende routes</h2></div>
            <ul class="divide-y divide-line text-sm">
                <li v-for="u in upcoming" :key="u.date">
                    <button class="flex w-full justify-between px-5 py-3 text-left hover:bg-surface-2" @click="goTo(u.date)">
                        <span class="font-semibold capitalize">{{ formatDate(u.date, { weekday: 'short', day: 'numeric', month: 'short' }) }}</span>
                        <span class="text-muted">{{ u.stops }} stations · {{ u.km }} km</span>
                    </button>
                </li>
                <li v-if="!upcoming.length" class="px-5 py-6 text-center text-muted">Nog niets gepland.</li>
            </ul>
        </div>
    </div>

    <div v-else class="grid gap-6 xl:grid-cols-5">
        <div class="space-y-4 xl:col-span-2">
            <div class="card flex items-center justify-between p-4 text-sm">
                <span><strong>{{ done }}/{{ plan.stops.length }}</strong> stations klaar</span>
                <span class="text-muted">{{ plan.totalKm }} km · rijden {{ formatMinutes(plan.driveMinutes) }} · terug {{ plan.endTime }}</span>
            </div>
            <div
                v-for="s in plan.stops"
                :key="s.id"
                class="card p-4"
                :class="s.finishedAt ? 'opacity-60' : s.startedAt ? 'ring-2 ring-klink-400' : ''"
            >
                <div class="flex items-start gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-bold text-white" :style="{ background: plan.inspector.color }">
                        <Icon v-if="s.finishedAt" name="check" :size="16" />
                        <template v-else>{{ s.position }}</template>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-lg font-bold tabular-nums">{{ s.arrival }} <span class="text-sm font-normal text-muted">– {{ s.departure }}</span></div>
                        <div class="font-semibold">{{ s.assignment.location.name }}</div>
                        <div class="text-sm text-muted">{{ s.assignment.location.address }}, {{ s.assignment.location.city }}</div>
                        <div class="mt-2 text-xs"><strong>Werk:</strong> {{ s.assignment.activities.map((a) => a.name).join(', ') }}</div>
                        <div class="text-xs text-muted">{{ s.assignment.location.tanks }} tanks · ± {{ formatMinutes(s.assignment.minutes) }}</div>
                        <div v-if="s.assignment.notes" class="mt-1 text-xs italic">{{ s.assignment.notes }}</div>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 pl-11">
                    <a :href="mapsUrl(s.assignment.location.lat, s.assignment.location.lng)" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">
                        <Icon name="navigation" :size="14" /> Google Maps
                    </a>
                    <button v-if="!s.startedAt" class="btn btn-secondary btn-sm" @click="start(s)"><Icon name="play" :size="14" /> Starten</button>
                    <button v-if="!s.finishedAt" class="btn btn-primary btn-sm" @click="finish(s)"><Icon name="check" :size="14" /> Afronden</button>
                    <span v-if="s.finishedAt" class="self-center text-xs text-muted">Afgerond om {{ s.finishedAt }}</span>
                    <span v-else-if="s.startedAt" class="self-center text-xs font-semibold text-klink-600 dark:text-klink-300">Bezig sinds {{ s.startedAt }}</span>
                </div>
            </div>
        </div>
        <div class="card p-3 xl:col-span-3">
            <RouteMap :routes="mapRoutes" height="680px" />
        </div>
    </div>
</template>
