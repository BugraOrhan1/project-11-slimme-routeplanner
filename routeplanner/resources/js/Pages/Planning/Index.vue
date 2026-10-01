<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Icon from '../../Components/Icon.vue';
import { formatDate, shiftDate, todayIso } from '../../format';

const props = defineProps({ week: String, weekNumber: Number, days: Array, inspectors: Array, routes: Array });

const goWeek = (week) => router.get(route('planning.index'), { week }, { preserveScroll: true });
const cell = (inspectorId, date) => props.routes.find((r) => r.inspectorId === inspectorId && r.date === date);
</script>

<template>
    <Head title="Planning" />
    <PageHeader title="Planningsoverzicht" :subtitle="`Week ${weekNumber} · ${formatDate(days[0].date)} t/m ${formatDate(days[4].date)}`">
        <button class="btn btn-ghost px-2.5" title="Vorige week" @click="goWeek(shiftDate(week, -7))"><Icon name="left" :size="16" /></button>
        <button class="btn btn-ghost" @click="goWeek(todayIso())">Deze week</button>
        <button class="btn btn-ghost px-2.5" title="Volgende week" @click="goWeek(shiftDate(week, 7))"><Icon name="right" :size="16" /></button>
    </PageHeader>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[1000px] table-fixed text-sm">
            <thead>
                <tr>
                    <th class="w-44 border-b border-line bg-surface-2 px-4 py-3 text-left text-xs font-bold text-muted uppercase">Inspecteur</th>
                    <th
                        v-for="d in days"
                        :key="d.date"
                        class="border-b border-l border-line px-3 py-3 text-left text-xs font-bold uppercase"
                        :class="d.isToday ? 'bg-klink-50 text-klink-700 dark:bg-klink-900/30 dark:text-klink-200' : 'bg-surface-2 text-muted'"
                    >
                        {{ d.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="i in inspectors" :key="i.id">
                    <td class="border-b border-line px-4 py-3 align-top font-semibold">
                        <span class="mr-2 inline-block h-2.5 w-2.5 rounded-full" :style="{ background: i.color }"></span>{{ i.name }}
                    </td>
                    <td v-for="d in days" :key="d.date" class="border-b border-l border-line p-2 align-top">
                        <Link
                            v-if="cell(i.id, d.date)"
                            :href="route('routes.show', cell(i.id, d.date).id)"
                            class="block rounded-md border-l-4 bg-surface-2 p-2 transition hover:ring-1 hover:ring-navy-300"
                            :style="{ borderColor: i.color }"
                        >
                            <div class="mb-1 flex items-center justify-between text-[11px] font-bold">
                                <span :class="cell(i.id, d.date).status === 'voorstel' ? 'text-amber-600 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300'">
                                    {{ cell(i.id, d.date).status }}
                                </span>
                                <span class="text-muted">{{ cell(i.id, d.date).km }} km</span>
                            </div>
                            <div v-for="(s, n) in cell(i.id, d.date).stops" :key="n" class="flex items-center gap-1.5 truncate text-xs leading-5" :class="s.done ? 'text-muted line-through' : ''">
                                <span class="tabular-nums text-muted">{{ s.arrival }}</span>
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" :style="{ background: s.color }"></span>
                                <span class="truncate">{{ s.name }}</span>
                            </div>
                            <div class="mt-1 text-[11px] text-muted">terug {{ cell(i.id, d.date).endTime }}</div>
                        </Link>
                        <Link
                            v-else
                            :href="route('planning.create', { date: d.date })"
                            class="flex h-full min-h-16 items-center justify-center rounded-md border border-dashed border-line text-xs text-muted hover:bg-surface-2 hover:text-ink"
                        >
                            <Icon name="plus" :size="14" /> plannen
                        </Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
