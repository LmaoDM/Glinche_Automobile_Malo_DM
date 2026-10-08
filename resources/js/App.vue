<script setup>
const applicationName = 'Glinche Automobiles';
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-white">
        <section class="max-w-2xl text-center">
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.25em] text-sky-400">
                Laravel + Vue.js
            </p>
            <h1 class="text-4xl font-bold sm:text-5xl">{{ applicationName }}</h1>
            <p class="mt-5 text-lg text-slate-300">
                Le front-end Vue est correctement chargé. Le catalogue de véhicules arrive bientôt.
            </p>
        </section>
    </main>
</template>
