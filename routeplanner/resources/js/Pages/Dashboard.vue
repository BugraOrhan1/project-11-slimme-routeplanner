<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '../Components/PageHeader.vue';
import DeadlineBadge from '../Components/DeadlineBadge.vue';
import StatusBadge from '../Components/StatusBadge.vue';
import RouteMap from '../Components/RouteMap.vue';
import Icon from '../Components/Icon.vue';
import { formatLongDate, todayIso } from '../format';

const props = defineProps({
    stats: Object,
    deadlines: Array,
    mapPoints: Array,
    todayRoutes: Array,
    availableInspectors: Array,
    recentChanges: Array,
});

// Kaartkleur op urgentie van de deadline
const points = computed(() =>
    props.mapPoints.map((p) => ({
        ...p,
        sub: `Deadline ${p.deadline}`,
        color: p.daysLeft < 0 ? '#980022' : p.daysLeft <= 14 ? '#E07A00' : '#1d6a88',
    })),
);

const tiles = computed(() => [
    { label: 'Open opdrachten', value: props.stats.open, icon: 'clipboard', href: route('assignments.index', { status: 'open' }) },
    { label: 'Ingepland', value: props.stats.ingepland, icon: 'calendar', href: route('assignments.index', { status: 'ingepland' }) },
    { label: 'Uitgevoerd', value: props.stats.uitgevoerd, icon: 'check', href: route('assignments.index', { status: 'uitgevoerd' }) },
    { label: 'Deadline verlopen', value: props.stats.overdue, icon: 'alert', href: route('assignments.index', { status: 'open', sort: 'deadline' }), danger: props.stats.overdue > 0 },
    { label: 'Routevoorstellen', value: props.stats.proposals, icon: 'route', href: route('planning.index') },
]);
</script>

<template>
    <Head title="Dashboard" />
    <PageHeader title="Dashboard" :subtitle="`Overzicht van ${formatLongDate(todayIso())}`">
        <Link :href="route('assignments.create')" class="btn btn-ghost"><Icon name="plus" :size="16" /> Opdracht</Link>
        <Link :href="route('planning.create')" class="btn btn-primary"><Icon name="route" :size="16" /> Route laten voorstellen</Link>
    </PageHeader>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <Link v-for="tile in tiles" :key="tile.label" :href="tile.href" class="card group p-4 transition hover:border-navy-300">
            <div class="flex items-center justify-between text-muted">
                <span class="text-xs font-bold tracking-wide uppercase">{{ tile.label }}</span>
                <Icon :name="tile.icon" :size="18" :class="tile.danger ? 'text-klink-500' : ''" />
            </div>
            <div class="mt-2 text-3xl font-bold" :class="tile.danger ? 'text-klink-600 dark:text-klink-300' : ''">{{ tile.value }}</div>
        </Link>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Open opdrachten op de kaart</h2>
                <div class="flex items-center gap-3 text-xs text-muted">
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-klink-600"></span>verlopen</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[#E07A00]"></span>≤ 14 dagen</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-navy-500"></span>later</span>
                </div>
            </div>
            <div class="p-3"><RouteMap :points="points" height="430px" /></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Naderende deadlines</h2>
                <Link :href="route('assignments.index', { status: 'open' })" class="link text-sm">Alle</Link>
            </div>
            <ul class="divide-y divide-line">
                <li v-for="a in deadlines" :key="a.id" class="flex items-start justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <Link :href="route('assignments.edit', a.id)" class="block truncate font-semibold hover:underline">{{ a.location.name }}</Link>
                        <div class="text-xs text-muted">{{ a.location.city }} · {{ a.location.tanks }} tanks</div>
                    </div>
                    <DeadlineBadge :deadline="a.deadline" :days-left="a.daysLeft" />
                </li>
                <li v-if="!deadlines.length" class="px-5 py-6 text-center text-sm text-muted">Geen deadlines in de komende 30 dagen.</li>
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Routes vandaag</h2></div>
            <ul class="divide-y divide-line">
                <li v-for="r in todayRoutes" :key="r.id">
                    <Link :href="route('routes.show', r.id)" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-2">
                        <span class="h-8 w-1.5 rounded" :style="{ background: r.color }"></span>
                        <div class="flex-1">
                            <div class="font-semibold">{{ r.inspector }}</div>
                            <div class="text-xs text-muted">{{ r.done }}/{{ r.stops }} stations klaar · {{ r.km }} km · terug {{ r.endTime }}</div>
                        </div>
                        <StatusBadge :status="r.status" />
                    </Link>
                </li>
                <li v-if="!todayRoutes.length" class="px-5 py-6 text-center text-sm text-muted">Nog geen routes voor vandaag.</li>
            </ul>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Beschikbare inspecteurs vandaag</h2></div>
            <div class="flex flex-wrap gap-2 p-5">
                <span v-for="i in availableInspectors" :key="i.id" class="badge bg-surface-2 py-1 ring-1 ring-line">
                    <span class="h-2 w-2 rounded-full" :style="{ background: i.color }"></span>{{ i.name }}
                </span>
                <p v-if="!availableInspectors.length" class="text-sm text-muted">Alle inspecteurs hebben vandaag een route.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Laatste wijzigingen</h2>
                <Link :href="route('changes.index')" class="link text-sm">Alle</Link>
            </div>
            <ul class="divide-y divide-line text-sm">
                <li v-for="c in recentChanges" :key="c.id" class="px-5 py-2.5">
                    <div>{{ c.description }}</div>
                    <div class="text-xs text-muted">{{ c.user }} · {{ c.at }}</div>
                </li>
                <li v-if="!recentChanges.length" class="px-5 py-6 text-center text-muted">Nog geen wijzigingen.</li>
            </ul>
        </div>
    </div>
</template>
