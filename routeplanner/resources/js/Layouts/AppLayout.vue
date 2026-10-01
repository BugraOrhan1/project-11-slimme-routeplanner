<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Icon from '../Components/Icon.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);
const unread = computed(() => page.props.unreadAlerts);

const nav = computed(() => {
    if (user.value?.isInspector) {
        return [{ label: 'Mijn route', href: route('my-route'), icon: 'route', active: 'my-route' }];
    }
    const items = [
        { label: 'Dashboard', href: route('dashboard'), icon: 'dashboard', active: 'dashboard' },
        { label: 'Routeplanner', href: route('planning.create'), icon: 'route', active: ['planning.create', 'routes.*'] },
        { label: 'Planning', href: route('planning.index'), icon: 'calendar', active: 'planning.index' },
        { label: 'Opdrachten', href: route('assignments.index'), icon: 'clipboard', active: 'assignments.*' },
        { label: 'Klanten & stations', href: route('customers.index'), icon: 'fuel', active: ['customers.*', 'locations.*'] },
        { label: 'Inspecteurs', href: route('inspectors.index'), icon: 'users', active: 'inspectors.*' },
        { label: 'Werkzaamheden', href: route('activities.index'), icon: 'wrench', active: 'activities.*' },
        { label: 'Wijzigingen', href: route('changes.index'), icon: 'history', active: 'changes.*' },
    ];
    if (user.value?.canManageUsers) {
        items.push({ label: 'Gebruikers', href: route('users.index'), icon: 'shield', active: 'users.*' });
    }
    return items;
});

const isActive = (item) => [].concat(item.active).some((name) => route().current(name));

// Donkere / lichte modus
const dark = ref(document.documentElement.classList.contains('dark'));
function toggleTheme() {
    dark.value = !dark.value;
    document.documentElement.classList.toggle('dark', dark.value);
    try {
        localStorage.setItem('theme', dark.value ? 'dark' : 'light');
    } catch (e) {
        // localStorage niet beschikbaar: alleen voor deze sessie
    }
}

// Meldingen (flash) automatisch laten verdwijnen
const toast = ref(null);
let timer;
watch(
    flash,
    (value) => {
        if (value?.success || value?.error) {
            toast.value = { ...value };
            clearTimeout(timer);
            timer = setTimeout(() => (toast.value = null), value.error ? 9000 : 4500);
        }
    },
    { immediate: true, deep: true },
);

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="flex min-h-screen">
        <!-- Zijbalk in Klink-navy -->
        <aside class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-navy-700 text-white dark:bg-navy-900">
            <div class="flex h-20 items-center border-b border-white/10 px-6">
                <Link :href="user?.isInspector ? route('my-route') : route('dashboard')" class="block">
                    <img src="/images/klink-logo-wit.png" alt="Klink inspection & engineering" class="h-10 w-auto" />
                </Link>
            </div>
            <div class="px-6 pt-4 pb-2 text-[11px] font-bold tracking-widest text-navy-200 uppercase">Routeplanner</div>
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-4">
                <Link
                    v-for="item in nav"
                    :key="item.label"
                    :href="item.href"
                    class="group relative flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition"
                    :class="isActive(item) ? 'bg-white/12 text-white' : 'text-navy-100 hover:bg-white/6 hover:text-white'"
                >
                    <span
                        v-if="isActive(item)"
                        class="absolute top-1.5 bottom-1.5 left-0 w-1 rounded-r bg-klink-400"
                    ></span>
                    <Icon :name="item.icon" />
                    {{ item.label }}
                </Link>
            </nav>
            <div class="border-t border-white/10 p-4">
                <div class="text-sm font-semibold">{{ user?.name }}</div>
                <div class="text-xs text-navy-200">{{ user?.roleLabel }}</div>
                <button type="button" class="mt-3 flex items-center gap-2 text-xs font-semibold text-navy-100 hover:text-white" @click="logout">
                    <Icon name="logout" :size="15" /> Uitloggen
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col pl-64">
            <!-- Bovenbalk -->
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-line bg-surface/90 px-8 backdrop-blur">
                <div class="min-w-0 truncate text-sm text-muted">
                    <slot name="breadcrumb">Ingenieursbureau Klink · inspection &amp; engineering</slot>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('alerts.index')"
                        class="relative rounded-full p-2 text-muted hover:bg-surface-2 hover:text-ink"
                        :title="`${unread} ongelezen melding(en)`"
                    >
                        <Icon name="bell" :size="20" />
                        <span
                            v-if="unread"
                            class="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-klink-400 px-1 text-[10px] font-bold text-white"
                        >{{ unread > 99 ? '99+' : unread }}</span>
                    </Link>
                    <button
                        type="button"
                        class="rounded-full p-2 text-muted hover:bg-surface-2 hover:text-ink"
                        :title="dark ? 'Lichte modus' : 'Donkere modus'"
                        @click="toggleTheme"
                    >
                        <Icon :name="dark ? 'sun' : 'moon'" :size="20" />
                    </button>
                </div>
            </header>

            <main class="flex-1 px-8 py-7">
                <slot />
            </main>
        </div>

        <!-- Melding na een actie -->
        <Transition
            enter-from-class="translate-y-2 opacity-0"
            enter-active-class="transition duration-200"
            leave-to-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-200"
        >
            <div v-if="toast" class="fixed right-6 bottom-6 z-50 w-96 max-w-[calc(100vw-3rem)] space-y-2">
                <div v-if="toast.success" class="flex items-start gap-3 rounded-lg border-l-4 border-emerald-500 bg-surface p-4 shadow-lg ring-1 ring-line">
                    <Icon name="check" class="mt-0.5 text-emerald-600" />
                    <p class="flex-1 text-sm">{{ toast.success }}</p>
                    <button class="text-muted hover:text-ink" @click="toast = null"><Icon name="x" :size="16" /></button>
                </div>
                <div v-if="toast.error" class="flex items-start gap-3 rounded-lg border-l-4 border-klink-400 bg-surface p-4 shadow-lg ring-1 ring-line">
                    <Icon name="alert" class="mt-0.5 text-klink-500" />
                    <p class="flex-1 text-sm whitespace-pre-line">{{ toast.error }}</p>
                    <button class="text-muted hover:text-ink" @click="toast = null"><Icon name="x" :size="16" /></button>
                </div>
            </div>
        </Transition>
    </div>
</template>
