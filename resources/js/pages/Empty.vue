<script setup>
import { Head, useArchitecturalBackground } from '@statamic/cms/inertia';
import { EmptyStateMenu, EmptyStateItem } from '@statamic/cms/ui';
import { LABEL } from '../integration.js';
import icon from '../../svg/zapier.svg?raw';

defineProps({
    createUrl: String,
});

useArchitecturalBackground();
</script>

<template>
    <Head :title="__(LABEL)" />

    <header class="py-8 mt-8 text-center starting-style-transition" v-cloak>
        <h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
            <span class="inline-flex text-gray-500" v-html="icon" />
            <span v-text="__(LABEL)" />
        </h1>
    </header>

    <EmptyStateMenu :heading="__('Connect a Statamic form to :name and map your fields.', { name: LABEL })">
        <EmptyStateItem
            :href="createUrl"
            icon="forms"
            :heading="__('Create Form')"
            :description="__('Get started by creating a Statamic form first.')"
        />
    </EmptyStateMenu>
</template>
