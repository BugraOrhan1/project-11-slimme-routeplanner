<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                {{ title }}
            </h1>
            <div class="mt-1 h-1 w-12 rounded bg-gradient-to-r from-klink-400 to-klink-600"></div>
            <p v-if="subtitle" class="mt-2 max-w-3xl text-sm text-muted">{{ subtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
