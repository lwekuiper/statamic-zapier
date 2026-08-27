<script setup>
import { Header, Listing, Dropdown, DropdownMenu, DropdownItem, StatusIndicator, Button, ConfirmationModal } from '@statamic/cms/ui';
import { Link } from '@statamic/cms/inertia';
import SiteSelector from '../SiteSelector.vue';
import { ref } from 'vue';
import axios from 'axios';
import { HANDLE, LABEL } from '../../integration.js';
import icon from '../../../svg/zapier.svg?raw';

const props = defineProps({
    createFormUrl: { type: String, required: true },
    configureUrl: { type: String, default: null },
    initialFormConfigs: { type: Array, required: true },
    columns: { type: Array, default: () => [] },
    initialLocalizations: { type: Array, default: () => [] },
    initialSite: { type: String, default: '' },
});

const rows = ref(props.initialFormConfigs);
const localizations = ref(props.initialLocalizations);
const site = ref(props.initialSite);
const loading = ref(false);
const pendingDelete = ref(null);

const columns = [
    { field: 'title', label: __('Form'), visible: true },
    ...props.columns.map((column) => ({ field: column.field, label: __(column.label), visible: true })),
];

function localizationSelected(handle) {
    const localization = localizations.value.find(l => l.handle === handle);
    if (!localization || localization.active) return;

    loading.value = true;

    axios.get(localization.url).then(response => {
        rows.value = response.data.formConfigs;
        localizations.value = response.data.localizations;
        site.value = localization.handle;
        loading.value = false;
    }).catch(() => {
        loading.value = false;
        Statamic.$toast.error(__('Something went wrong'));
    });
}

function deleteRow(form) {
    pendingDelete.value = form;
}

function confirmDelete() {
    const form = pendingDelete.value;
    pendingDelete.value = null;

    axios.delete(form.delete_url)
        .then(() => rows.value = rows.value.filter(r => r !== form))
        .catch(() => Statamic.$toast.error(__('Something went wrong')));
}

</script>

<template>
    <div class="max-w-5xl mx-auto">
        <Header :title="__(LABEL)" :icon="icon">
            <Dropdown v-if="configureUrl" placement="left-start">
                <DropdownMenu>
                    <DropdownItem
                        :text="__('Configure')"
                        icon="cog"
                        :href="configureUrl"
                    />
                </DropdownMenu>
            </Dropdown>

            <SiteSelector
                v-if="localizations.length > 1"
                :sites="localizations"
                :model-value="site"
                @update:model-value="localizationSelected"
            />

            <Button :href="createFormUrl" :text="__('Create Form')" variant="primary" />
        </Header>

        <Listing
            v-if="!loading"
            :items="rows"
            :columns="columns"
            :allow-presets="false"
            :allow-customizing-columns="false"
            :allow-search="false"
            :preferences-prefix="HANDLE"
        >
            <template #cell-title="{ row: form }">
                <Link :href="form.edit_url" class="inline-flex items-center gap-2">
                    <StatusIndicator :status="form.status" />
                    <span>{{ form.title }}</span>
                </Link>
            </template>
            <template v-for="column in props.columns" :key="column.field" #[`cell-${column.field}`]="{ row }">
                {{ column.type === 'count' ? (row[column.field] || '') : row[column.field] }}
            </template>
            <template #prepended-row-actions="{ row: form }">
                <DropdownItem
                    :text="__('Edit')"
                    :href="form.edit_url"
                    icon="edit"
                />
                <DropdownItem
                    v-if="form.delete_url"
                    :text="__('Delete')"
                    icon="trash"
                    class="warning"
                    @click="deleteRow(form)"
                />
            </template>
        </Listing>

        <div v-else class="card p-4 text-center text-gray-500">
            {{ __('Loading...') }}
        </div>

        <ConfirmationModal
            :open="!!pendingDelete"
            :title="__('Delete')"
            :body-text="__('Are you sure?')"
            :button-text="__('Delete')"
            :danger="true"
            @confirm="confirmDelete"
            @cancel="pendingDelete = null"
        />
    </div>
</template>
