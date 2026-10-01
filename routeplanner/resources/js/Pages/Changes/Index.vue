<script setup>
import { reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';

const props = defineProps({ logs: Object, filters: Object, users: Array, types: Array });

const filters = reactive({ user: props.filters.user ?? '', type: props.filters.type ?? '' });
watch(filters, () => {
    const query = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''));
    router.get(route('changes.index'), query, { preserveState: true, replace: true });
});

const open = ref(null);
const actionClass = {
    aangemaakt: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    gewijzigd: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
    verwijderd: 'bg-klink-100 text-klink-700 dark:bg-klink-900/40 dark:text-klink-200',
};
const show = (v) => (v === null || v === undefined || v === '' ? '–' : typeof v === 'object' ? JSON.stringify(v) : String(v));
</script>

<template>
    <Head title="Wijzigingen" />
    <PageHeader title="Wijzigingslog" subtitle="De planner zet de grote lijnen neer, administratie of een manager kijkt mee en past aan. Hier zie je wie wat heeft aangepast.">
        <select v-model="filters.user" class="input w-48">
            <option value="">Alle gebruikers</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
        <select v-model="filters.type" class="input w-40">
            <option value="">Alle soorten</option>
            <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
        </select>
    </PageHeader>

    <div class="card">
        <table class="table">
            <thead><tr><th class="w-40">Wanneer</th><th class="w-52">Wie</th><th>Wijziging</th></tr></thead>
            <tbody>
                <template v-for="log in logs.data" :key="log.id">
                    <tr class="cursor-pointer" @click="open = open === log.id ? null : log.id">
                        <td class="text-xs whitespace-nowrap text-muted tabular-nums">{{ log.at }}</td>
                        <td>
                            <div class="font-semibold">{{ log.user }}</div>
                            <div class="text-xs text-muted">{{ log.role }}</div>
                        </td>
                        <td>
                            <span class="badge mr-2" :class="actionClass[log.action] ?? actionClass.gewijzigd">{{ log.action }}</span>
                            {{ log.description }}
                            <span v-if="log.changes" class="ml-1 text-xs text-muted">({{ open === log.id ? 'verbergen' : 'details' }})</span>
                        </td>
                    </tr>
                    <tr v-if="open === log.id && log.changes">
                        <td colspan="3" class="bg-surface-2">
                            <table class="w-full text-xs">
                                <tr v-for="(value, key) in log.changes.nieuw" :key="key">
                                    <td class="w-48 py-1 font-semibold">{{ key }}</td>
                                    <td class="py-1 text-klink-600 line-through dark:text-klink-300">{{ show(log.changes.oud?.[key]) }}</td>
                                    <td class="py-1 text-emerald-700 dark:text-emerald-300">{{ show(value) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </template>
                <tr v-if="!logs.data.length"><td colspan="3" class="py-10 text-center text-muted">Nog geen wijzigingen vastgelegd.</td></tr>
            </tbody>
        </table>
        <Pagination :paginator="logs" />
    </div>
</template>
