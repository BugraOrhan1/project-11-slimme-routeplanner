<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, required: true } });

const styles = {
    open: 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200',
    ingepland: 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-200',
    uitgevoerd: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
    geannuleerd: 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    voorstel: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
    goedgekeurd: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
};

const cls = computed(() => styles[props.status] ?? styles.geannuleerd);
</script>

<template>
    <span class="badge capitalize" :class="cls">{{ status }}</span>
</template>
