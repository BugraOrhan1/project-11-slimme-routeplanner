<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ alerts: Array, alertDays: Number });

const read = (a) => router.post(route('alerts.read', a.id), {}, { preserveScroll: true });
const readAll = () => router.post(route('alerts.read-all'), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Meldingen" />
    <PageHeader title="Meldingen" :subtitle="`Planner en bedrijfsleider krijgen een melding als een deadline binnen ${alertDays} dagen verloopt en de opdracht nog niet is ingepland.`">
        <button v-if="alerts.some((a) => !a.read)" class="btn btn-ghost" @click="readAll"><Icon name="check" :size="16" /> Alles gelezen</button>
    </PageHeader>

    <div class="card divide-y divide-line">
        <div v-for="a in alerts" :key="a.id" class="flex items-start gap-4 px-5 py-4" :class="a.read ? 'opacity-60' : ''">
            <span class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="a.read ? 'bg-surface-2 text-muted' : 'bg-klink-100 text-klink-600 dark:bg-klink-900/50 dark:text-klink-200'">
                <Icon name="bell" :size="16" />
            </span>
            <div class="flex-1">
                <p class="text-sm" :class="a.read ? '' : 'font-semibold'">{{ a.message }}</p>
                <p class="text-xs text-muted">{{ a.at }}</p>
            </div>
            <div class="flex gap-2">
                <Link v-if="a.assignmentId && !$page.props.auth.user.isInspector" :href="route('assignments.edit', a.assignmentId)" class="btn btn-ghost btn-sm">Opdracht</Link>
                <button v-if="!a.read" class="btn btn-ghost btn-sm" @click="read(a)">Gelezen</button>
            </div>
        </div>
        <p v-if="!alerts.length" class="p-10 text-center text-muted">Geen meldingen.</p>
    </div>
</template>
