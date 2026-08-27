<script setup>
import { computed } from 'vue';
import { Combobox } from '@statamic/cms/ui';

const props = defineProps({
    value: { required: true },
    meta: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:value']);

const fields = computed(() => props.meta.options ?? []);
</script>

<template>
    <Combobox
        class="w-full"
        :model-value="value"
        @update:model-value="emit('update:value', $event)"
        :options="fields"
        option-value="id"
        option-label="label"
        :placeholder="__('Choose...')"
        clearable
        searchable
    />
</template>
