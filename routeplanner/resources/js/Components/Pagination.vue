<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ paginator: { type: Object, required: true } });
</script>

<template>
    <div v-if="paginator.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3 text-sm">
        <span class="text-muted">{{ paginator.from }}–{{ paginator.to }} van {{ paginator.total }}</span>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, i) in paginator.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="min-w-8 rounded-[5px] px-2.5 py-1 text-center"
                    :class="link.active ? 'bg-navy-700 font-semibold text-white dark:bg-navy-500' : 'hover:bg-surface-2'"
                    v-html="link.label.replace('Previous', 'Vorige').replace('Next', 'Volgende')"
                />
                <span v-else class="px-2.5 py-1 text-muted" v-html="link.label.replace('Previous', 'Vorige').replace('Next', 'Volgende')" />
            </template>
        </div>
    </div>
</template>
