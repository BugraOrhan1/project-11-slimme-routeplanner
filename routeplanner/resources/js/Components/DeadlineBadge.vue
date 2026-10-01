<script setup>
import { computed } from 'vue';
import { daysLabel, formatDate } from '../format';

const props = defineProps({
    deadline: { type: String, required: true },
    daysLeft: { type: Number, default: null },
    compact: { type: Boolean, default: false },
});

// Rood = verlopen, oranje = binnen 14 dagen (meldingstermijn), anders neutraal
const tone = computed(() => {
    if (props.daysLeft === null) return 'text-muted';
    if (props.daysLeft < 0) return 'bg-klink-600 text-white';
    if (props.daysLeft <= 14) return 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-100';
    return 'bg-surface-2 text-muted ring-1 ring-line';
});
</script>

<template>
    <span class="inline-flex flex-col">
        <span class="text-sm font-semibold">{{ formatDate(deadline) }}</span>
        <span v-if="!compact && daysLeft !== null" class="badge mt-0.5 w-fit" :class="tone">{{ daysLabel(daysLeft) }}</span>
    </span>
</template>
