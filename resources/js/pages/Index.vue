<script setup>
import { Head } from '@statamic/cms/inertia';
import Listing from '../components/listing/Listing.vue';
import { LABEL } from '../integration.js';

defineProps({
    createFormUrl: String,
    configureUrl: String,
    formConfigs: Array,
    columns: Array,
    localizations: Array,
    site: String,
});
</script>

<template>
    <Head :title="__(LABEL)" />
    <Listing
        :create-form-url="createFormUrl"
        :configure-url="configureUrl"
        :initial-form-configs="formConfigs"
        :columns="columns"
        :initial-localizations="localizations"
        :initial-site="site"
    />
</template>
