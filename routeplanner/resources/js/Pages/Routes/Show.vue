<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import RouteMap from '../../Components/RouteMap.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import DeadlineBadge from '../../Components/DeadlineBadge.vue';
import Icon from '../../Components/Icon.vue';
import { formatLongDate, formatMinutes } from '../../format';
import { toMapRoute } from '../../mapRoute';

const props = defineProps({ plan: Object, conflicts: Array, siblings: Array, addable: Array });

// US17 – volgorde handmatig aanpassen (slepen of pijltjes), daarna opslaan = tijden opnieuw berekenen
const order = ref([...props.plan.stops]);
watch(() => props.plan.stops, (stops) => (order.value = [...stops]));
const changed = computed(() => order.value.map((s) => s.id).join() !== props.plan.stops.map((s) => s.id).join());

const dragIndex = ref(null);
function onDrop(target) {
    if (dragIndex.value === null || dragIndex.value === target) return;
    const [moved] = order.value.splice(dragIndex.value, 1);
    order.value.splice(target, 0, moved);
    dragIndex.value = null;
}
function move(i, dir) {
    const j = i + dir;
    if (j < 0 || j >= order.value.length) return;
    [order.value[i], order.value[j]] = [order.value[j], order.value[i]];
}
const saveOrder = () => router.put(route('routes.reorder', props.plan.id), { stop_ids: order.value.map((s) => s.id) }, { preserveScroll: true });

const removeStop = (s) => {
    if (confirm(`${s.assignment.location.name} uit de route halen?`)) {
        router.delete(route('routes.stops.remove', [props.plan.id, s.id]), { preserveScroll: true });
    }
};
const moveTo = (s, targetId) => targetId && router.post(route('routes.stops.move', [props.plan.id, s.id]), { target_route_id: targetId }, { preserveScroll: true });

const addId = ref('');
const addStop = () => router.post(route('routes.stops.add', props.plan.id), { assignment_id: addId.value }, { preserveScroll: true, onSuccess: () => (addId.value = '') });

const approve = () => router.post(route('routes.approve', props.plan.id), {}, { preserveScroll: true });
const unapprove = () => router.post(route('routes.unapprove', props.plan.id), {}, { preserveScroll: true });
const reject = () => confirm('Route afwijzen en verwijderen? De opdrachten komen weer vrij.') && router.delete(route('routes.destroy', props.plan.id));

const mapRoutes = computed(() => [toMapRoute({ ...props.plan, stops: order.value })]);
</script>

<template>
    <Head :title="`Route ${plan.inspector.name}`" />
    <PageHeader :title="`Route ${plan.inspector.name}`" :subtitle="formatLongDate(plan.date)">
        <Link :href="route('planning.create', { date: plan.date })" class="btn btn-ghost"><Icon name="left" :size="16" /> Routeplanner</Link>
        <template v-if="plan.status === 'voorstel'">
            <button class="btn btn-danger" @click="reject"><Icon name="x" :size="16" /> Afwijzen</button>
            <button class="btn btn-primary" :disabled="changed" @click="approve"><Icon name="check" :size="16" /> Goedkeuren</button>
        </template>
        <button v-else class="btn btn-ghost" @click="unapprove">Terugzetten naar voorstel</button>
    </PageHeader>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="card p-4"><div class="text-xs font-bold text-muted uppercase">Status</div><div class="mt-2"><StatusBadge :status="plan.status" /></div></div>
        <div class="card p-4"><div class="text-xs font-bold text-muted uppercase">Stations</div><div class="mt-1 text-2xl font-bold">{{ plan.stops.length }}</div></div>
        <div class="card p-4"><div class="text-xs font-bold text-muted uppercase">Afstand</div><div class="mt-1 text-2xl font-bold">{{ plan.totalKm }} km</div></div>
        <div class="card p-4"><div class="text-xs font-bold text-muted uppercase">Rijden / werk</div><div class="mt-1 text-2xl font-bold">{{ formatMinutes(plan.driveMinutes) }} <span class="text-base text-muted">/ {{ formatMinutes(plan.workMinutes) }}</span></div></div>
        <div class="card p-4"><div class="text-xs font-bold text-muted uppercase">Werkdag</div><div class="mt-1 text-2xl font-bold">{{ plan.inspector.workStart }} – {{ plan.endTime }}</div></div>
    </div>

    <div v-if="conflicts.length" class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-100">
        <div class="mb-1 flex items-center gap-2 font-bold"><Icon name="alert" :size="16" /> Let op</div>
        <ul class="list-disc space-y-0.5 pl-6"><li v-for="c in conflicts" :key="c">{{ c }}</li></ul>
    </div>

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Stops</h2>
                <button v-if="changed" class="btn btn-primary btn-sm" @click="saveOrder"><Icon name="check" :size="14" /> Volgorde opslaan</button>
                <span v-else class="text-xs text-muted">Sleep om de volgorde te wijzigen</span>
            </div>

            <div class="flex items-center gap-3 border-b border-line px-5 py-2.5 text-xs text-muted">
                <Icon name="pin" :size="14" /> Vertrek {{ plan.inspector.workStart }} · {{ plan.inspector.start.address }}
            </div>
            <ol>
                <li
                    v-for="(s, i) in order"
                    :key="s.id"
                    draggable="true"
                    class="border-b border-line px-5 py-3 transition"
                    :class="dragIndex === i ? 'opacity-40' : 'hover:bg-surface-2'"
                    @dragstart="dragIndex = i"
                    @dragend="dragIndex = null"
                    @dragover.prevent
                    @drop="onDrop(i)"
                >
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 cursor-grab items-center justify-center rounded-full text-xs font-bold text-white" :style="{ background: plan.inspector.color }">{{ i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold">{{ s.assignment.location.name }}</span>
                                <span class="badge text-white" :style="{ background: s.assignment.location.customer.color }">{{ s.assignment.location.customer.name }}</span>
                            </div>
                            <div class="text-xs text-muted">{{ s.assignment.location.address }}, {{ s.assignment.location.city }}</div>
                            <div class="mt-1 text-xs">
                                <template v-if="!changed">
                                    <strong class="tabular-nums">{{ s.arrival }}–{{ s.departure }}</strong>
                                    · {{ s.driveMinutes }} min rijden ({{ s.distanceKm }} km)
                                </template>
                                <span v-else class="text-muted">tijden worden berekend na opslaan</span>
                                · {{ s.assignment.location.tanks }} tanks · {{ formatMinutes(s.assignment.minutes) }}
                            </div>
                            <div class="mt-1"><DeadlineBadge :deadline="s.assignment.deadline" :days-left="s.assignment.daysLeft" /></div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <button class="btn btn-ghost btn-sm px-1.5" title="Omhoog" :disabled="i === 0" @click="move(i, -1)"><Icon name="up" :size="14" /></button>
                            <button class="btn btn-ghost btn-sm px-1.5" title="Omlaag" :disabled="i === order.length - 1" @click="move(i, 1)"><Icon name="down" :size="14" /></button>
                        </div>
                    </div>
                    <div v-if="!changed" class="mt-2 flex flex-wrap gap-2 pl-9">
                        <select v-if="siblings.length" class="input w-auto py-1 text-xs" @change="moveTo(s, $event.target.value)">
                            <option value="">Verplaatsen naar…</option>
                            <option v-for="r in siblings" :key="r.id" :value="r.id">{{ r.inspector }} ({{ r.status }})</option>
                        </select>
                        <button class="btn btn-danger btn-sm" @click="removeStop(s)"><Icon name="trash" :size="12" /> Uit route</button>
                    </div>
                </li>
            </ol>
            <div class="flex items-center gap-3 border-b border-line px-5 py-2.5 text-xs text-muted">
                <Icon name="pin" :size="14" /> Terug {{ plan.endTime }} · {{ plan.inspector.start.address }}
            </div>

            <form class="flex gap-2 p-5" @submit.prevent="addStop">
                <select v-model="addId" class="input" required>
                    <option value="" disabled>Opdracht toevoegen… ({{ addable.length }} geschikt)</option>
                    <option v-for="a in addable" :key="a.id" :value="a.id">{{ a.location.name }} – {{ a.location.city }} (deadline {{ a.deadline }})</option>
                </select>
                <button class="btn btn-secondary" :disabled="!addId"><Icon name="plus" :size="16" /></button>
            </form>
        </div>

        <div class="card p-3 xl:sticky xl:top-20 xl:col-span-3 xl:self-start">
            <RouteMap :routes="mapRoutes" height="calc(100vh - 8rem)" />
        </div>
    </div>
</template>
