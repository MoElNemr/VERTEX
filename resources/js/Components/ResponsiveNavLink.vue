<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-4 pe-4 py-2.5 border-s-4 border-blue-500 text-start text-sm font-bold text-blue-400 bg-blue-950/40 focus:outline-none transition duration-150 ease-in-out'
        : 'block w-full ps-4 pe-4 py-2.5 border-s-4 border-transparent text-start text-sm font-medium text-slate-400 hover:text-slate-100 hover:bg-slate-800 hover:border-slate-700 focus:outline-none transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
