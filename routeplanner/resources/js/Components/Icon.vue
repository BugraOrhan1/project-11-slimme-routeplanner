<script setup>
// Eenvoudige lijn-iconen (stijl van Lucide), zodat er geen extra pakket nodig is.
const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 18 },
});

const icons = {
    dashboard: ['M3 3h7v9H3z', 'M14 3h7v5h-7z', 'M14 12h7v9h-7z', 'M3 16h7v5H3z'],
    clipboard: ['M9 3h6a1 1 0 0 1 1 1v2H8V4a1 1 0 0 1 1-1z', 'M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2', 'M9 12h6', 'M9 16h4'],
    route: ['M6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z', 'M18 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4z', 'M8 17h7.5a3.5 3.5 0 0 0 0-7h-7a3.5 3.5 0 0 1 0-7H16'],
    calendar: ['M4 5h16a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z', 'M16 3v4', 'M8 3v4', 'M3 10h18'],
    building: ['M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16', 'M16 9h2a2 2 0 0 1 2 2v10', 'M2 21h20', 'M8 7h4', 'M8 11h4', 'M8 15h4'],
    fuel: ['M3 22V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v17', 'M2 22h14', 'M15 10h2a2 2 0 0 1 2 2v4a1.5 1.5 0 0 0 3 0V8l-3-3', 'M6 7h6v4H6z'],
    wrench: ['M14.7 6.3a4 4 0 0 0 5 5L21 10a6 6 0 0 1-8.5 5.5l-6 6a2.1 2.1 0 0 1-3-3l6-6A6 6 0 0 1 14 4z'],
    users: ['M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', 'M22 21v-2a4 4 0 0 0-3-3.9', 'M16 3.1a4 4 0 0 1 0 7.8'],
    history: ['M3 12a9 9 0 1 0 3-6.7L3 8', 'M3 3v5h5', 'M12 7v5l3 2'],
    bell: ['M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9', 'M10.3 21a1.9 1.9 0 0 0 3.4 0'],
    sun: ['M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', 'M12 2v2', 'M12 20v2', 'M4.9 4.9l1.4 1.4', 'M17.7 17.7l1.4 1.4', 'M2 12h2', 'M20 12h2', 'M4.9 19.1l1.4-1.4', 'M17.7 6.3l1.4-1.4'],
    moon: ['M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9z'],
    logout: ['M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4', 'M16 17l5-5-5-5', 'M21 12H9'],
    shield: ['M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'],
    plus: ['M12 5v14', 'M5 12h14'],
    upload: ['M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4', 'M17 8l-5-5-5 5', 'M12 3v12'],
    check: ['M20 6L9 17l-5-5'],
    x: ['M18 6L6 18', 'M6 6l12 12'],
    up: ['M18 15l-6-6-6 6'],
    down: ['M6 9l6 6 6-6'],
    left: ['M15 18l-6-6 6-6'],
    right: ['M9 18l6-6-6-6'],
    trash: ['M3 6h18', 'M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2', 'M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6'],
    pencil: ['M17 3a2.8 2.8 0 0 1 4 4L7.5 20.5 2 22l1.5-5.5z'],
    navigation: ['M3 11l19-9-9 19-2-8z'],
    search: ['M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z', 'M21 21l-4.3-4.3'],
    clock: ['M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z', 'M12 6v6l4 2'],
    alert: ['M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z', 'M12 9v4', 'M12 17h.01'],
    pin: ['M12 22s-8-6.5-8-13a8 8 0 0 1 16 0c0 6.5-8 13-8 13z', 'M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z'],
    play: ['M6 4l14 8-14 8z'],
    move: ['M5 9l-3 3 3 3', 'M9 5l3-3 3 3', 'M15 19l-3 3-3-3', 'M19 9l3 3-3 3', 'M2 12h20', 'M12 2v20'],
    certificate: ['M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12z', 'M8.2 13.9L7 22l5-3 5 3-1.2-8.1'],
};

const paths = () => icons[props.name] ?? icons.x;
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        class="shrink-0"
    >
        <path v-for="(d, i) in paths()" :key="i" :d="d" />
    </svg>
</template>
