<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Icon from '../../Components/Icon.vue';
import { formatDate } from '../../format';

defineProps({ inspectors: Array });

function remove(i) {
    if (confirm(`Inspecteur ${i.name} verwijderen? Zijn routes worden ook verwijderd.`)) {
        router.delete(route('inspectors.destroy', i.id));
    }
}
</script>

<template>
    <Head title="Inspecteurs" />
    <PageHeader title="Inspecteurs" subtitle="Inspecteurs rijden meestal alleen, met een eigen bus met alle apparatuur. De planner ziet hier wie welk werk mag doen.">
        <Link :href="route('inspectors.create')" class="btn btn-primary"><Icon name="plus" :size="16" /> Nieuwe inspecteur</Link>
    </PageHeader>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <div v-for="i in inspectors" :key="i.id" class="card overflow-hidden" :class="!i.active ? 'opacity-60' : ''">
            <div class="flex items-center gap-3 border-b border-line p-5">
                <div class="flex h-11 w-11 items-center justify-center rounded-full text-sm font-bold text-white" :style="{ background: i.color }">
                    {{ i.name.split(' ').map((p) => p[0]).slice(0, 2).join('') }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-bold">{{ i.name }} <span v-if="!i.active" class="badge ml-1 bg-surface-2 text-muted">inactief</span></div>
                    <div class="truncate text-xs text-muted">{{ i.email || 'Geen account gekoppeld' }} · {{ i.phone }}</div>
                </div>
                <Link :href="route('inspectors.edit', i.id)" class="btn btn-ghost btn-sm"><Icon name="pencil" :size="14" /></Link>
                <button class="btn btn-danger btn-sm" @click="remove(i)"><Icon name="trash" :size="14" /></button>
            </div>
            <div class="space-y-3 p-5 text-sm">
                <div class="flex items-center gap-2 text-muted"><Icon name="pin" :size="15" /> {{ i.startAddress }}</div>
                <div class="flex items-center gap-2 text-muted"><Icon name="clock" :size="15" /> {{ i.workStart }} – {{ i.workEnd }}</div>
                <div>
                    <div class="mb-1.5 text-xs font-bold tracking-wide text-muted uppercase">Certificaten</div>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="c in i.certificates"
                            :key="c.name"
                            class="badge"
                            :class="c.expired
                                ? 'bg-klink-100 text-klink-700 line-through dark:bg-klink-900/50 dark:text-klink-200'
                                : c.expiresSoon
                                  ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-100'
                                  : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'"
                            :title="c.validUntil ? `Geldig tot ${formatDate(c.validUntil)}` : 'Geen einddatum'"
                        >
                            <Icon name="certificate" :size="12" />{{ c.name }}
                            <template v-if="c.expired"> (verlopen)</template>
                        </span>
                        <span v-if="!i.certificates.length" class="text-muted">Geen certificaten</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
